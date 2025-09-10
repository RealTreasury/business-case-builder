const fs = require('fs');
const vm = require('vm');
const { JSDOM } = require('jsdom');

require('./jsdom-setup');

const html = `<!DOCTYPE html><html><body>
<div id="rtbcbModalOverlay"></div>
<form id="rtbcbForm" class="rtbcb-wizard">
  <div class="rtbcb-wizard-steps">
    <div class="rtbcb-wizard-step active" data-step="1">
      <div class="rtbcb-report-type-grid">
        <div class="rtbcb-report-type-card">
          <label class="rtbcb-report-type-label"><input type="radio" name="report_type" value="basic" checked /></label>
        </div>
        <div class="rtbcb-report-type-card">
          <label class="rtbcb-report-type-label"><input type="radio" name="report_type" value="enhanced" /></label>
        </div>
      </div>
    </div>
  </div>
</form>
</body></html>`;

const dom = new JSDOM(html, { url: 'http://localhost', runScripts: 'outside-only' });

global.window = dom.window;
global.document = dom.window.document;
global.FormData = dom.window.FormData;
global.navigator = dom.window.navigator;

global.DOMPurify = { sanitize: (html) => html };
global.rtbcb_ajax = { ajax_url: 'http://example.com/ajax', nonce: 'test' };
global.fetch = async () => ({ ok: true, status: 200, text: async () => '{}' });

const wizardCode = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(wizardCode);

new BusinessCaseBuilder();

const enhancedCard = document
  .querySelector('.rtbcb-report-type-card input[value="enhanced"]')
  .closest('.rtbcb-report-type-card');
enhancedCard.dispatchEvent(new window.Event('click', { bubbles: true }));

const cards = document.querySelectorAll('.rtbcb-report-type-card');
if (!cards[1].classList.contains('rtbcb-selected')) {
  throw new Error('Enhanced card not selected after click');
}
if (cards[0].classList.contains('rtbcb-selected')) {
  throw new Error('Basic card still selected after click');
}

console.log('Report type click selection test passed');

