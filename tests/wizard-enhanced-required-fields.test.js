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
        <div class="rtbcb-report-type-grid">
          <div class="rtbcb-report-type-card rtbcb-selected">
            <input type="radio" name="report_type" value="basic" checked />
          </div>
          <div class="rtbcb-report-type-card">
            <input type="radio" name="report_type" value="enhanced" />
          </div>
        </div>
      </div>
      <div class="rtbcb-wizard-step" data-step="2">
        <input id="company_name" name="company_name" />
        <select id="company_size" name="company_size"><option value="small">Small</option></select>
        <select id="industry" name="industry"><option value="tech">Tech</option></select>
      </div>
      <div class="rtbcb-wizard-step" data-step="3">
        <input id="num_entities" name="num_entities" type="number" />
        <input id="num_currencies" name="num_currencies" type="number" />
        <input id="num_banks" name="num_banks" type="number" />
      </div>
      <div class="rtbcb-wizard-step" data-step="4">
        <input id="hours_reconciliation" name="hours_reconciliation" type="number" />
        <input id="hours_cash_positioning" name="hours_cash_positioning" type="number" />
        <input id="ftes" name="ftes" type="number" />
      </div>
      <div class="rtbcb-wizard-step" data-step="5">
        <input id="treasury_automation" name="treasury_automation" />
        <select id="primary_systems" name="primary_systems[]"><option value="erp">ERP</option></select>
        <input id="bank_import_frequency" name="bank_import_frequency" />
        <input id="reporting_cadence" name="reporting_cadence" />
      </div>
      <div class="rtbcb-wizard-step" data-step="6">
        <input id="annual_payment_volume" name="annual_payment_volume" />
        <input id="payment_approval_workflow" name="payment_approval_workflow" />
        <input id="reconciliation_method" name="reconciliation_method" />
        <input id="cash_update_frequency" name="cash_update_frequency" />
      </div>
      <div class="rtbcb-wizard-step" data-step="7">
        <div class="rtbcb-pain-points-validation"><div class="rtbcb-validation-message"></div></div>
        <label><input type="checkbox" name="pain_points[]" value="manual" /></label>
      </div>
      <div class="rtbcb-wizard-step" data-step="8">
        <input id="business_objective" name="business_objective" />
        <input id="implementation_timeline" name="implementation_timeline" />
        <input id="budget_range" name="budget_range" />
      </div>
      <div class="rtbcb-wizard-step" data-step="9">
        <input id="email" name="email" type="email" />
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
  let errorData = null;
  builder.handleError = (data) => { errorData = data; };
  builder.showEnhancedHTMLReport = (html) => { displayReport(html); };

  const enhancedCard = document.querySelector('.rtbcb-report-type-card input[value="enhanced"]').closest('.rtbcb-report-type-card');
  enhancedCard.dispatchEvent(new window.Event('click', { bubbles: true }));
  await builder.handleNext();

  document.getElementById('company_name').value = 'MyCo';
  document.getElementById('company_size').value = 'small';
  document.getElementById('industry').value = 'tech';
  await builder.handleNext();

  document.getElementById('num_entities').value = '1';
  document.getElementById('num_currencies').value = '1';
  document.getElementById('num_banks').value = '1';
  await builder.handleNext();

  document.getElementById('hours_reconciliation').value = '1';
  document.getElementById('hours_cash_positioning').value = '1';
  document.getElementById('ftes').value = '1';
  await builder.handleNext();

  document.getElementById('treasury_automation').value = 'manual';
  document.getElementById('primary_systems').value = 'erp';
  document.getElementById('bank_import_frequency').value = 'daily';
  document.getElementById('reporting_cadence').value = 'monthly';
  await builder.handleNext();

  // Clear a previously required field after progressing
  document.getElementById('hours_reconciliation').value = '';

  document.getElementById('annual_payment_volume').value = '10';
  document.getElementById('payment_approval_workflow').value = 'single';
  document.getElementById('reconciliation_method').value = 'manual';
  document.getElementById('cash_update_frequency').value = 'daily';
  await builder.handleNext();

  document.querySelector('input[name="pain_points[]"]').checked = true;
  await builder.handleNext();

  document.getElementById('business_objective').value = 'growth';
  document.getElementById('implementation_timeline').value = 'Q4';
  document.getElementById('budget_range').value = '1000';
  await builder.handleNext();

  document.getElementById('email').value = 'test@example.com';
  await builder.handleSubmit();
  assert.ok(errorData && /Missing required field/i.test(errorData.message));

  // Fix the missing field and submit again
  document.getElementById('hours_reconciliation').value = '1';
  errorData = null;
  await builder.handleSubmit();
  const iframe = document.querySelector('#report-container iframe');
  assert.ok(iframe, 'Report iframe not injected');
  assert.ok(iframe.srcdoc.includes('Enhanced Comprehensive Report Template'));
  console.log('Wizard enhanced required fields test passed.');
})().catch(err => {
  console.error(err);
  process.exit(1);
});
