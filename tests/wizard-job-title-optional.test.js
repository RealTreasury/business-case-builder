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
 </div>
 </div>
 <div class="rtbcb-wizard-steps">
 <div class="rtbcb-wizard-step active" data-step="1">
 <div class="rtbcb-field"><input type="radio" name="report_type" value="enhanced" checked /></div>
 </div>
 <div class="rtbcb-wizard-step" data-step="2">
 <div class="rtbcb-field"><input id="company_name" name="company_name" /></div>
 <div class="rtbcb-field"><select id="company_size" name="company_size"><option value="small">Small</option></select></div>
 <div class="rtbcb-field"><select id="industry" name="industry"><option value="tech">Tech</option></select></div>
 <div class="rtbcb-field"><select id="job_title" name="job_title"><option value="">Select...</option><option value="cfo">CFO</option></select></div>
 </div>
 <div class="rtbcb-wizard-step" data-step="3">
 <div class="rtbcb-field"><input id="dummy" name="dummy" /></div>
 </div>
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

global.DOMPurify = { sanitize: (html) => html };

global.rtbcb_ajax = { ajax_url: 'http://example.com/ajax', nonce: 'test' };

global.fetch = async () => ({ ok: true, status: 200, text: async () => '{}' });

const wizardCode = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(wizardCode);

(() => {
 const builder = new BusinessCaseBuilder();
 builder.handleNext();
 document.getElementById('company_name').value = 'MyCo';
 document.getElementById('company_size').value = 'small';
 document.getElementById('industry').value = 'tech';
 builder.handleNext();
 assert.strictEqual(builder.currentStep, 3, 'Wizard should advance to step 3 without job title');
 console.log('Wizard job title optional test passed.');
})();
