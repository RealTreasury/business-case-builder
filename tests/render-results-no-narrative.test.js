const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

global.window = {};
global.document = {
    readyState: 'complete',
    addEventListener: () => {},
    getElementById: () => null,
    querySelector: () => null
};

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

const builder = new BusinessCaseBuilder();

const data = {
    scenarios: {},
    recommendation: {},
    company_name: 'Test Co'
};

const html = builder.renderResults(data);
assert.ok(html.includes('Treasury technology investment presents a compelling opportunity for operational efficiency.'));
console.log('Render results without narrative test passed.');

// Test synchronous handleSubmit
builder.form = {
    _fields: {
        company_name: 'Sync Co',
        hours_reconciliation: '1',
        hours_cash_positioning: '2',
        num_banks: '3',
        ftes: '4',
        pain_points: 'A',
        business_objective: 'B',
        implementation_timeline: 'C',
        budget_range: 'D',
        email: 'test@example.com'
    },
    closest: () => null,
    querySelector: () => null
};

let openAsync = null;
global.XMLHttpRequest = class {
    open(method, url, async) { openAsync = async; this.status = 200; }
    send() { this.responseText = JSON.stringify({ success: true, data: { scenarios: {}, recommendation: {}, company_name: 'Sync Co' } }); }
};

global.FormData = class {
    constructor(form) {
        this._data = [];
        if (form && form._fields) {
            for (const [k, v] of Object.entries(form._fields)) {
                this._data.push([k, v]);
            }
        }
    }
    append(k, v) { this._data.push([k, v]); }
    entries() { return this._data[Symbol.iterator](); }
    [Symbol.iterator]() { return this.entries(); }
};

global.ajaxObj = { ajax_url: 'test-url' };
let resultData = null;
builder.showProgress = () => {};
builder.showResults = (res) => { resultData = res; };
builder.showError = (msg) => { throw new Error(msg); };

builder.handleSubmit();

assert.strictEqual(openAsync, false);
assert.strictEqual(resultData.company_name, 'Sync Co');
console.log('Synchronous handleSubmit test passed.');
