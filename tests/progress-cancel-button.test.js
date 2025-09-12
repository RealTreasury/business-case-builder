const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const { JSDOM } = require('jsdom');

require('./jsdom-setup');

const html = `<!DOCTYPE html><html><body>
<div id="rtbcbModalOverlay"><div class="rtbcb-modal-container"></div><form id="rtbcbForm"></form></div>
<div id="rtbcb-progress-container" style="display:none" aria-hidden="true"></div>
</body></html>`;
const dom = new JSDOM(html, { url: 'http://localhost', runScripts: 'outside-only' });

global.window = dom.window;
global.document = dom.window.document;
global.FormData = dom.window.FormData;
global.navigator = dom.window.navigator;

global.rtbcb_ajax = { ajax_url: 'http://example.com', nonce: 'test' };

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

const builder = new BusinessCaseBuilder();

let cancelled = false;
let hidden = false;
let reinit = false;

builder.cancelPolling = () => { cancelled = true; };
const originalHideLoading = builder.hideLoading.bind(builder);
builder.hideLoading = () => { hidden = true; originalHideLoading(); };
builder.reinitialize = () => { reinit = true; };

builder.showLoading();

const progressContainer = document.getElementById('rtbcb-progress-container');
assert.strictEqual(progressContainer.getAttribute('aria-hidden'), null, 'aria-hidden not removed');

const btn = document.querySelector('.rtbcb-progress-cancel');
assert.ok(btn, 'Cancel button not rendered');

btn.click();

assert.ok(cancelled, 'cancelPolling not called');
assert.ok(hidden, 'hideLoading not called');
assert.ok(reinit, 'reinitialize not called');
assert.strictEqual(progressContainer.getAttribute('aria-hidden'), 'true', 'aria-hidden not restored');

console.log('Progress cancel button test passed.');
