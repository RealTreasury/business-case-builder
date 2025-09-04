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
assert.strictEqual(builder.currentStep, 2, 'Wizard should advance to step 2 even with missing progress steps');
console.log('Wizard missing progress step test passed.');
