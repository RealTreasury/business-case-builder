const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const { JSDOM } = require('jsdom');

require('./jsdom-setup');

const html = `<!DOCTYPE html><html><body>
<div id="rtbcbModalOverlay">
  <form id="rtbcbForm" class="rtbcb-wizard">
    <div class="rtbcb-wizard-progress">
      <div class="rtbcb-progress-line"></div>
      <div class="rtbcb-progress-steps">
        <div class="rtbcb-progress-step active" data-step="1"></div>
        <div class="rtbcb-progress-step" data-step="2"></div>
        <div class="rtbcb-progress-step" data-step="3"></div>
        <div class="rtbcb-progress-step" data-step="4"></div>
        <div class="rtbcb-progress-step" data-step="5"></div>
        <div class="rtbcb-progress-step" data-step="6"></div>
        <div class="rtbcb-progress-step" data-step="7"></div>
      </div>
    </div>
    <div class="rtbcb-wizard-steps">
      <div class="rtbcb-wizard-step active" data-step="1">
        <input type="radio" name="report_type" value="basic" />
        <input type="radio" name="report_type" value="enhanced" />
      </div>
      <div class="rtbcb-wizard-step" data-step="2">
        <input id="company_name" name="company_name" required />
        <select id="company_size" name="company_size" required><option value="small">Small</option></select>
        <select id="industry" name="industry" required><option value="tech">Tech</option></select>
        <select id="job_title" name="job_title"><option value="">Select</option><option value="cfo">CFO</option></select>
      </div>
      <div class="rtbcb-wizard-step" data-step="3"></div>
      <div class="rtbcb-wizard-step" data-step="4"></div>
      <div class="rtbcb-wizard-step" data-step="5"></div>
      <div class="rtbcb-wizard-step" data-step="6"></div>
      <div class="rtbcb-wizard-step" data-step="7"></div>
    </div>
    <div class="rtbcb-wizard-navigation">
      <button type="button" class="rtbcb-nav-prev"></button>
      <button type="button" class="rtbcb-nav-next"></button>
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

global.fetch = async () => ({ ok: true, status: 200, text: async () => '{}' });

const wizardCode = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(wizardCode);

(async () => {
  const builder = new BusinessCaseBuilder();
  document.querySelector('input[name="report_type"][value="enhanced"]').checked = true;
  await builder.handleNext();

  document.getElementById('company_name').value = 'MyCo';
  document.getElementById('company_size').value = 'small';
  document.getElementById('industry').value = 'tech';
  // job_title left empty on purpose
  await builder.handleNext();

  assert.strictEqual(builder.currentStep, 3, 'Wizard should progress to step 3 without job title');

  const data = builder.collectFormData();
  assert.ok(!data.has('job_title'), 'Form data should omit empty job title');

  console.log('Wizard job title optional test passed.');
})().catch(err => {
  console.error(err);
  process.exit(1);
});
