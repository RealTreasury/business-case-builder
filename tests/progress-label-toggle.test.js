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
        <div class="rtbcb-progress-step active" data-step="1">
          <div class="rtbcb-progress-number">1</div>
          <div class="rtbcb-progress-short">R.</div>
          <div class="rtbcb-progress-label" title="Report">Report</div>
        </div>
        <div class="rtbcb-progress-step" data-step="2">
          <div class="rtbcb-progress-number">2</div>
          <div class="rtbcb-progress-short">C.</div>
          <div class="rtbcb-progress-label" title="Company">Company</div>
        </div>
      </div>
    </div>
    <div class="rtbcb-wizard-steps">
      <div class="rtbcb-wizard-step active" data-step="1"></div>
      <div class="rtbcb-wizard-step" data-step="2"></div>
    </div>
    <div class="rtbcb-wizard-navigation">
      <button type="button" class="rtbcb-nav-prev"></button>
      <button type="button" class="rtbcb-nav-next"></button>
      <button type="submit" class="rtbcb-nav-submit"></button>
    </div>
  </form>
</div>
</body></html>`;

const dom = new JSDOM(html, { url: 'http://localhost', runScripts: 'outside-only' });

global.window = dom.window;
global.document = dom.window.document;
global.FormData = dom.window.FormData;
global.navigator = dom.window.navigator;

global.rtbcb_ajax = { ajax_url: 'http://example.com', nonce: 'test' };

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

BusinessCaseBuilder.prototype.initializePath = function () {
  this.steps = this.form.querySelectorAll('.rtbcb-wizard-step');
  this.progressSteps = this.form.querySelectorAll('.rtbcb-progress-step');
  this.totalSteps = this.steps.length;
  this.reportType = 'enhanced';
  this.updateStepVisibility();
  this.updateProgressIndicator();
};

const builder = new BusinessCaseBuilder();

const step1Short = document.querySelector('.rtbcb-progress-step[data-step="1"] .rtbcb-progress-short');
const step1Label = document.querySelector('.rtbcb-progress-step[data-step="1"] .rtbcb-progress-label');
const step2Short = document.querySelector('.rtbcb-progress-step[data-step="2"] .rtbcb-progress-short');
const step2Label = document.querySelector('.rtbcb-progress-step[data-step="2"] .rtbcb-progress-label');

assert.strictEqual(step1Short.style.display, 'none');
assert.strictEqual(step1Label.style.display, '');
assert.strictEqual(step2Short.style.display, '');
assert.strictEqual(step2Label.style.display, 'none');

builder.currentStep = 2;
builder.updateProgressIndicator();

assert.strictEqual(step1Short.style.display, '');
assert.strictEqual(step1Label.style.display, 'none');
assert.strictEqual(step2Short.style.display, 'none');
assert.strictEqual(step2Label.style.display, '');

console.log('Progress label toggle test passed.');

