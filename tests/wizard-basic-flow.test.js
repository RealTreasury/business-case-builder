const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const { JSDOM } = require('jsdom');

require('./jsdom-setup');

const sampleReport = fs.readFileSync('templates/comprehensive-report-template.php', 'utf8');

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
        <div class="rtbcb-progress-step" data-step="8"></div>
        <div class="rtbcb-progress-step" data-step="9"></div>
      </div>
    </div>
    <div class="rtbcb-wizard-steps">
      <div class="rtbcb-wizard-step active" data-step="1">
        <div class="rtbcb-field"><div class="rtbcb-report-type-grid">
          <div class="rtbcb-report-type-card rtbcb-selected">
            <input type="radio" name="report_type" value="basic" checked />
          </div>
          <div class="rtbcb-report-type-card">
            <input type="radio" name="report_type" value="enhanced" />
          </div>
        </div></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="2">
        <div class="rtbcb-field"><input id="company_name" name="company_name" /></div>
        <div class="rtbcb-field rtbcb-enhanced-only"><select id="company_size" name="company_size"><option value="small">Small</option></select></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="3">
        <div class="rtbcb-field"><input id="num_entities" name="num_entities" type="number" /></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="4">
        <div class="rtbcb-field"><input id="hours_reconciliation" name="hours_reconciliation" type="number" /></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="5">
        <div class="rtbcb-field"><input id="treasury_automation" name="treasury_automation" /></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="6">
        <div class="rtbcb-field"><input id="annual_payment_volume" name="annual_payment_volume" /></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="7">
        <div class="rtbcb-pain-points-validation"><div class="rtbcb-validation-message"></div></div>
        <label class="rtbcb-pain-point-card"><input type="checkbox" name="pain_points[]" value="manual" /></label>
      </div>
      <div class="rtbcb-wizard-step" data-step="8">
        <div class="rtbcb-field"><input id="business_objective" name="business_objective" /></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="9">
        <div class="rtbcb-field"><input id="email" name="email" type="email" /></div>
      </div>
    </div>
    <div class="rtbcb-wizard-navigation">
      <button type="button" class="rtbcb-nav-prev"></button>
      <button type="button" class="rtbcb-nav-next"></button>
      <button type="submit" class="rtbcb-nav-submit"></button>
    </div>
  </form>
</div>
<div id="report-container"></div>
<div id="rtbcb-progress-container" style="display:none"><div class="rtbcb-progress-content"><div class="rtbcb-progress-text"></div></div></div>
</body></html>`;

const dom = new JSDOM(html, { url: 'http://localhost', runScripts: 'outside-only' });

global.window = dom.window;
global.document = dom.window.document;
global.FormData = dom.window.FormData;
global.navigator = dom.window.navigator;

global.DOMPurify = { sanitize: (html) => html };

global.rtbcb_ajax = { ajax_url: 'http://example.com/ajax', nonce: 'test' };

global.fetch = async () => ({
  ok: true,
  status: 200,
  text: async () => JSON.stringify({ success: true, report_html: sampleReport })
});

const reportCode = fs.readFileSync('public/js/rtbcb-report.js', 'utf8');
vm.runInThisContext(reportCode);
const wizardCode = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(wizardCode);

(async () => {
  const builder = new BusinessCaseBuilder();
  builder.showLoading = () => {};
  builder.hideLoading = () => {};
  builder.showEnhancedHTMLReport = (html) => {
    displayReport(html);
  };

  // Step 1 - choose basic
  await builder.handleNext();

  // Step 2
  document.getElementById('company_name').value = 'MyCo';
  await builder.handleNext();

  // Step 3 - pain points
  document.querySelector('input[name="pain_points[]"]').checked = true;
  await builder.handleNext();

  // Step 4
  document.getElementById('email').value = 'test@example.com';
  const formData = builder.collectFormData();
  assert.doesNotThrow(() => builder.validateFormData(formData));
  await builder.handleSubmit();

  const iframe = document.querySelector('#report-container iframe');
  assert.ok(iframe, 'Report iframe not injected');
  assert.ok(iframe.srcdoc.includes('Enhanced Comprehensive Report Template'), 'Sample report not loaded');
  console.log('Wizard basic flow test passed.');
})().catch(err => {
  console.error(err);
  process.exit(1);
});
