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
    const payload = {
        data: { message: 'Server exploded', error_code: 'AI_CONFIG_ERROR' }
    };
    const response = {
        ok: false,
        status: 500,
        json: async () => payload,
        text: async () => JSON.stringify(payload),
        clone() { return this; }
    };
    return Promise.resolve(response);
};

const form = {
    fields: { company_name: 'Test Co' },
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
builder.showError = (msg) => { errorMessage = msg; };

(async () => {
    await builder.handleSubmit();
    assert.ok(errorMessage.includes('Server exploded'));
    assert.ok(errorMessage.includes('AI_CONFIG_ERROR'));
    assert.ok(errorMessage.includes('try again later'));
    console.log('Server error display test passed.');
})().catch(err => {
    console.error(err);
    process.exit(1);
});
