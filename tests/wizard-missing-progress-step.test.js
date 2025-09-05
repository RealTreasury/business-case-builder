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
        <div class="rtbcb-progress-step" data-step="1"></div>
        <div class="rtbcb-progress-step" data-step="2"></div>
        <!-- Step 7 intentionally missing -->
      </div>
    </div>
    <div class="rtbcb-wizard-steps">
      <div class="rtbcb-wizard-step active" data-step="1">
        <div class="rtbcb-field"><input type="radio" name="report_type" value="basic" checked /></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="2">
        <div class="rtbcb-field"><input name="company_name" /></div>
      </div>
      <div class="rtbcb-wizard-step" data-step="7">
        <div class="rtbcb-field"><input name="email" /></div>
      </div>
    </div>
    <div class="rtbcb-wizard-navigation">
      <button type="button" class="rtbcb-nav-next"></button>
    </div>
  </form>
</div>
</body></html>`;

const dom = new JSDOM(html, { url: 'http://localhost', runScripts: 'outside-only' });

global.window = dom.window;
global.document = dom.window.document;
global.FormData = dom.window.FormData;
global.navigator = dom.window.navigator;

const wizardCode = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(wizardCode);

const builder = new BusinessCaseBuilder();

builder.nextBtn.click();
let activeStep = dom.window.document.querySelector('.rtbcb-progress-step.active');
assert.ok(activeStep, 'Active progress step should exist');
assert.strictEqual(activeStep.dataset.step, '2', 'Active progress indicator should match current step');
assert.strictEqual(builder.currentStep, 2, 'Wizard should advance to step 2 even with missing progress steps');

// Move to final step and ensure progress remains consistent
document.querySelector('input[name="company_name"]').value = 'ACME';
builder.nextBtn.click();
document.querySelector('input[name="email"]').value = 'test@example.com';
builder.nextBtn.click();

const progressLine = dom.window.document.querySelector('.rtbcb-progress-line');
activeStep = dom.window.document.querySelector('.rtbcb-progress-step.active');
assert.strictEqual(builder.currentStep, 3, 'Wizard should stop at the last valid step');
assert.ok(activeStep, 'Active progress step should exist at final step');
assert.strictEqual(activeStep.dataset.step, '2', 'Last progress indicator should remain active');
assert.strictEqual(progressLine.style.width, '100%', 'Progress bar width should be 100% at final step');

console.log('Wizard missing progress step test passed.');
