const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
require('./jsdom-setup');

global.rtbcb_ajax = { ajax_url: 'http://example.com', nonce: 'test' };
document.body.innerHTML = '<div id="rtbcb-progress-container"></div><form id="rtbcbForm"></form>';

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);
const builder = new BusinessCaseBuilder();
let cancelled = false;
let hidden = false;
let reset = false;
builder.cancelPolling = () => { cancelled = true; };
builder.hideLoading = () => { hidden = true; };
builder.reinitialize = () => { reset = true; };
builder.showLoading();

const btn = document.getElementById('rtbcb-cancel-btn');
btn.click();

assert.ok(cancelled, 'cancelPolling not called');
assert.ok(hidden, 'hideLoading not called');
assert.ok(reset, 'reinitialize not called');
console.log('Cancel button test passed.');
