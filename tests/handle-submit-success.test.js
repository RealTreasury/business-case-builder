const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

global.rtbcbAjax = { ajax_url: 'test-url', nonce: 'test-nonce' };

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

let receivedHeaders = null;
let warnCalled = false;
const originalWarn = console.warn;
console.warn = () => { warnCalled = true; };

global.fetch = function(url, options) {
    receivedHeaders = options.headers;
    const payload = {
        success: true,
        data: { job_id: '123' }
    };
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
    querySelector: (selector) => selector === '[name="company_name"]' ? { value: 'Test Co' } : null,
    querySelectorAll: () => [],
    addEventListener: () => {},
    closest: () => ({ style: {} })
};

global.document = {
    readyState: 'complete',
    addEventListener: () => {},
    getElementById: (id) => {
        if (id === 'rtbcbForm') return form;
        if (id === 'rtbcbModalOverlay') return {};
        return null;
    }
};

global.window = {};

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

const builder = new BusinessCaseBuilder();
builder.form = form;
let polledJobId = null;
builder.showProgress = () => {};
builder.pollJob = (jobId) => { polledJobId = jobId; };
builder.showError = () => {};

(async () => {
    await builder.handleSubmit();
    assert.strictEqual(polledJobId, '123');
    assert.strictEqual(receivedHeaders['Accept'], 'application/json');
    assert.strictEqual(warnCalled, false);
    console.warn = originalWarn;
    console.log('Success path test passed.');
})().catch(err => {
    console.error(err);
    process.exit(1);
});