const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

require('./jsdom-setup');

global.rtbcb_ajax = { ajax_url: 'ftp://example.com', nonce: 'test-nonce' };

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

let fetchCalled = false;
global.fetch = function() {
    fetchCalled = true;
    return Promise.resolve();
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

const formElem = document.createElement('form');
formElem.id = 'rtbcbForm';
Object.assign(formElem, form);
document.body.appendChild(formElem);

document.readyState = 'complete';
const modalOverlay = document.createElement('div');
modalOverlay.id = 'rtbcbModalOverlay';
document.body.appendChild(modalOverlay);
const progressContainer = document.createElement('div');
progressContainer.id = 'rtbcb-progress-container';
document.body.appendChild(progressContainer);

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

const builder = new BusinessCaseBuilder();
let errorMessage = null;
builder.showProgress = () => {};
builder.showResults = () => {};
builder.showEnhancedError = (msg) => { errorMessage = msg; };

(async () => {
    await builder.handleSubmit();
    assert.strictEqual(fetchCalled, false);
    assert.strictEqual(errorMessage, 'Service unavailable. Please reload the page.');
    console.log('Invalid ajaxUrl test passed.');
})().catch(err => {
    console.error(err);
    process.exit(1);
});
