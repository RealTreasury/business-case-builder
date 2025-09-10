const fs = require('fs');
const vm = require('vm');
const { JSDOM } = require('jsdom');

require('./jsdom-setup');

const html = `<!DOCTYPE html><html><body>
<div id="rtbcbModalOverlay"></div>
<form id="rtbcbForm" class="rtbcb-wizard">
  <div class="rtbcb-wizard-progress">
    <div class="rtbcb-progress-line"></div>
    <div class="rtbcb-progress-steps">
      <div class="rtbcb-progress-step active" data-step="1"></div>
      <div class="rtbcb-progress-step" data-step="2"></div>
    </div>
  </div>
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
    <div class="rtbcb-wizard-step" data-step="2"></div>
  </div>
  <div class="rtbcb-wizard-navigation">
    <button type="button" class="rtbcb-nav-prev"></button>
    <button type="button" class="rtbcb-nav-next"></button>
    <button type="submit" class="rtbcb-nav-submit"></button>
  </div>
</form>
<div id="report-container"></div>
<div id="rtbcb-progress-container"><div class="rtbcb-progress-content"><div class="rtbcb-progress-text"></div></div></div>
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

const builder = new BusinessCaseBuilder();

// Simulate restoring saved data with enhanced report type
builder.populateForm({ report_type: 'enhanced' });

const cards = document.querySelectorAll('.rtbcb-report-type-card');
if (!cards[1].classList.contains('rtbcb-selected')) {
  throw new Error('Enhanced card not selected after populateForm');
}
if (cards[0].classList.contains('rtbcb-selected')) {
  throw new Error('Basic card still selected after populateForm');
}

console.log('Report type restore selection test passed');
