const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

require('./jsdom-setup');

global.rtbcb_ajax = { ajax_url: '', nonce: 'bad-nonce' };
global.ajaxurl = 'https://fallback.example.com';

class SimpleFormData {
constructor(form) {
this._data = [];
if (form && form.fields) {
for (const [key, value] of Object.entries(form.fields)) {
this._data.push([key, value]);
}
}
}
append(key, value) {
this._data.push([key, value]);
}
entries() {
return this._data[Symbol.iterator]();
}
[Symbol.iterator]() {
return this.entries();
}
}

global.FormData = SimpleFormData;

const fetchCalls = [];
global.fetch = function(url, options) {
fetchCalls.push({ url, options });
if (fetchCalls.length === 1) {
const payload = { success: false, data: { message: 'Security check failed.' } };
const response = {
ok: true,
status: 200,
json: async () => payload,
text: async () => JSON.stringify(payload),
headers: { get: () => 'application/json' },
clone() { return this; }
};
return Promise.resolve(response);
}
const payload = { success: true, data: { job_id: 'job-123' } };
const response = {
ok: true,
status: 200,
json: async () => payload,
text: async () => JSON.stringify(payload),
headers: { get: () => 'application/json' },
clone() { return this; }
};
return Promise.resolve(response);
};

const form = {
fields: {
email: 'test@example.com',
company_name: 'Test Co',
company_size: '100',
industry: 'Finance',
hours_reconciliation: '1',
hours_cash_positioning: '1',
num_banks: '1',
ftes: '1',
business_objective: 'growth',
implementation_timeline: '3 months',
budget_range: '1000',
'pain_points[]': 'manual'
},
querySelector: (sel) => sel === '[name="company_name"]' ? { value: 'Test Co' } : null,
querySelectorAll: () => [],
addEventListener: () => {},
closest: () => ({ style: {} })
};

const formElem = document.createElement('form');
formElem.id = 'rtbcbForm';
Object.assign(formElem, form);
document.body.appendChild(formElem);

document.readyState = 'complete';
const overlay = document.createElement('div');
overlay.id = 'rtbcbModalOverlay';
document.body.appendChild(overlay);
const progressContainer = document.createElement('div');
progressContainer.id = 'rtbcb-progress-container';
document.body.appendChild(progressContainer);

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

const builder = new BusinessCaseBuilder();
builder.form = form;
let errorMessage = null;
builder.showProgress = () => {};
builder.showResults = () => {};
builder.showEnhancedError = (msg) => { errorMessage = msg; };
builder.pollJob = () => {};

(async () => {
await builder.handleSubmit();
assert.strictEqual(errorMessage, 'Session expired. Please refresh the page and try again.');
errorMessage = null;
global.rtbcb_ajax.nonce = 'good-nonce';
await builder.handleSubmit();
assert.strictEqual(fetchCalls.length, 2);
assert.strictEqual(fetchCalls[0].url, 'https://fallback.example.com');
assert.strictEqual(fetchCalls[1].url, 'https://fallback.example.com');
console.log('Nonce retry test passed.');
})().catch(err => { console.error(err); process.exit(1); });
