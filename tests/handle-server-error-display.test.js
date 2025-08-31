require("./jsdom-setup");
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

global.fetch = function() {
    const response = {
        ok: false,
        status: 500,
        json: async () => ({}),
        text: async () => '',
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
let errorMessage = null;
builder.showProgress = () => {};
builder.showResults = () => {};
builder.showEnhancedError = (msg) => { errorMessage = msg; };
builder.showTimeoutError = (msg) => { errorMessage = msg; };

(async () => {
    await builder.handleSubmit();
    assert.strictEqual(errorMessage, 'An error occurred while processing your request. Please try again.');
    console.log('Server error display test passed.');
})().catch(err => {
    console.error(err);
    process.exit(1);
});
