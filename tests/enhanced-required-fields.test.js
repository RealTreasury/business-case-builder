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
      </div>
    </div>
    <div class="rtbcb-wizard-steps">
      <div class="rtbcb-wizard-step active" data-step="1">
        <div class="rtbcb-field"><input type="radio" name="report_type" value="enhanced" /></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="2">
        <div class="rtbcb-field"><input id="company_name" name="company_name" required /></div>
        <div class="rtbcb-field"><select id="company_size" name="company_size" required><option value="s">Small</option></select></div>
        <div class="rtbcb-field"><select id="industry" name="industry" required><option value="tech">Tech</option></select></div>
        <div class="rtbcb-field"><input id="job_title" name="job_title" required /></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="3">
        <div class="rtbcb-field"><input id="num_entities" name="num_entities" type="number" required /></div>
        <div class="rtbcb-field"><input id="num_currencies" name="num_currencies" type="number" required /></div>
        <div class="rtbcb-field"><input id="num_banks" name="num_banks" type="number" required /></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="4">
        <div class="rtbcb-field"><input id="hours_reconciliation" name="hours_reconciliation" type="number" required /></div>
        <div class="rtbcb-field"><input id="hours_cash_positioning" name="hours_cash_positioning" type="number" required /></div>
        <div class="rtbcb-field"><input id="ftes" name="ftes" type="number" required /></div>
        <div class="rtbcb-field"><select id="treasury_automation" name="treasury_automation" required><option value="manual">Manual</option></select></div>
        <div class="rtbcb-field"><select id="primary_systems" name="primary_systems[]" multiple required><option value="erp" selected>ERP</option></select></div>
        <div class="rtbcb-field"><select id="bank_import_frequency" name="bank_import_frequency" required><option value="daily" selected>Daily</option></select></div>
        <div class="rtbcb-field"><select id="reporting_cadence" name="reporting_cadence" required><option value="monthly" selected>Monthly</option></select></div>
        <div class="rtbcb-field"><input id="annual_payment_volume" name="annual_payment_volume" type="number" required /></div>
        <div class="rtbcb-field"><select id="forecast_horizon" name="forecast_horizon" required><option value="1_3" selected>1-3</option></select></div>
        <div class="rtbcb-field"><select id="fx_management" name="fx_management" required><option value="none" selected>None</option></select></div>
        <div class="rtbcb-field"><select id="investment_activities" name="investment_activities[]" multiple required><option value="mmf" selected>MMF</option></select></div>
        <div class="rtbcb-field"><select id="intercompany_lending" name="intercompany_lending" required><option value="none" selected>None</option></select></div>
        <div class="rtbcb-field"><select id="audit_trail" name="audit_trail" required><option value="basic" selected>Basic</option></select></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="5">
        <div class="rtbcb-pain-points-validation"><div class="rtbcb-validation-message"></div></div>
        <label class="rtbcb-pain-point-card"><input type="checkbox" name="pain_points[]" value="manual" /></label>
      </div>
      <div class="rtbcb-wizard-step" data-step="6">
        <div class="rtbcb-field"><input id="business_objective" name="business_objective" required /></div>
        <div class="rtbcb-field"><input id="implementation_timeline" name="implementation_timeline" required /></div>
        <div class="rtbcb-field"><input id="budget_range" name="budget_range" required /></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="7">
        <div class="rtbcb-field"><input id="email" name="email" type="email" required /></div>
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

global.fetch = async () => ({ ok: true, status: 200, text: async () => JSON.stringify({ success: true, report_html: sampleReport }) });

const reportCode = fs.readFileSync('public/js/rtbcb-report.js', 'utf8');
vm.runInThisContext(reportCode);
const wizardCode = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(wizardCode);

(async () => {
  const builder = new BusinessCaseBuilder();
  builder.showLoading = () => {};
  builder.hideLoading = () => {};
  builder.startProgressiveLoading = () => {};
  builder.cancelProgressiveLoading = () => {};
  builder.showEnhancedHTMLReport = (html) => { displayReport(html); };
  builder.handleError = (err) => { throw err; };

  document.querySelector('input[name="report_type"]').checked = true;
  await builder.handleNext();

  document.getElementById('company_name').value = 'MyCo';
  document.getElementById('company_size').value = 's';
  document.getElementById('industry').value = 'tech';
  document.getElementById('job_title').value = 'CFO';
  await builder.handleNext();

  document.getElementById('num_entities').value = '1';
  document.getElementById('num_currencies').value = '1';
  document.getElementById('num_banks').value = '1';
  await builder.handleNext();

  document.getElementById('hours_reconciliation').value = '1';
  document.getElementById('hours_cash_positioning').value = '1';
  document.getElementById('ftes').value = '1';
  document.getElementById('treasury_automation').value = 'manual';
  document.getElementById('bank_import_frequency').value = 'daily';
  document.getElementById('reporting_cadence').value = 'monthly';
  document.getElementById('annual_payment_volume').value = '1';
  document.getElementById('forecast_horizon').value = '1_3';
  document.getElementById('fx_management').value = 'none';
  document.getElementById('intercompany_lending').value = 'none';
  document.getElementById('audit_trail').value = 'basic';
  document.querySelector('#investment_activities option').selected = true;
  document.querySelector('#primary_systems option').selected = true;
  await builder.handleNext();

  document.querySelector('input[name="pain_points[]"]').checked = true;
  await builder.handleNext();

  document.getElementById('business_objective').value = 'growth';
  document.getElementById('implementation_timeline').value = 'Q4';
  document.getElementById('budget_range').value = '1000';
  await builder.handleNext();

  document.getElementById('email').value = 'test@example.com';

  document.getElementById('num_entities').value = '';
  await assert.rejects(async () => { await builder.handleSubmit(); });
  document.getElementById('num_entities').value = '1';

  document.getElementById('treasury_automation').value = '';
  await assert.rejects(async () => { await builder.handleSubmit(); });
  document.getElementById('treasury_automation').value = 'manual';

  await builder.handleSubmit();
  const iframe = document.querySelector('#report-container iframe');
  assert.ok(iframe, 'Report iframe not injected');
  console.log('Enhanced required fields test passed.');
})().catch(err => {
  console.error(err);
  process.exit(1);
});
