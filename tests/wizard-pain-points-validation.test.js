const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const { JSDOM } = require('jsdom');

require('./jsdom-setup');

const html = `<!DOCTYPE html><html><body>
<div id="rtbcbModalOverlay">
  <form id="rtbcbForm" class="rtbcb-wizard">
    <div class="rtbcb-wizard-step active" data-step="1">
      <div class="rtbcb-pain-points-validation"><div class="rtbcb-validation-message"></div></div>
      <label><input type="checkbox" name="pain_points[]" value="manual" /></label>
    </div>
    <div class="rtbcb-wizard-navigation">
      <button type="submit" class="rtbcb-nav-submit"></button>
    </div>
  </form>
</div>
<div id="report-container"></div>
</body></html>`;

const dom = new JSDOM(html, { url: 'http://localhost', runScripts: 'outside-only' });

global.window = dom.window;
global.document = dom.window.document;
global.FormData = dom.window.FormData;
global.navigator = dom.window.navigator;

global.DOMPurify = { sanitize: (html) => html };

global.rtbcb_ajax = { ajax_url: 'http://example.com/ajax', nonce: 'test' };

const wizardCode = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(wizardCode);

(async () => {
  const builder = new BusinessCaseBuilder();
  builder.reportType = 'enhanced';
  builder.totalSteps = 1;
  builder.getStepFields = () => ['pain_points[]'];

  let threw = false;
  try {
    const formData = builder.collectFormData();
    builder.validateFormData(formData);
  } catch (err) {
    threw = true;
    assert.strictEqual(err.message, 'Please select at least one challenge.');
    // Expect unified challenge message
  }
  assert.ok(threw, 'Validation should fail when no pain points selected');

  document.querySelector('input[name="pain_points[]"]').checked = true;
  const formData2 = builder.collectFormData();
  builder.validateFormData(formData2);
  console.log('Wizard challenges validation test passed.');
})().catch(err => {
  console.error(err);
  process.exit(1);
});
