const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

const form = { fields: {} };

global.window = {};
global.document = {
    addEventListener: () => {},
    getElementById: (id) => (id === 'rtbcbForm' ? form : null),
    body: { style: {} }
};

global.ajaxObj = { ajax_url: 'test-url' };

global.FormData = class {
    constructor(form) {
        this.store = form && form.fields ? { ...form.fields } : {};
    }
    append(key, value) { this.store[key] = value; }
    entries() { return Object.entries(this.store); }
    [Symbol.iterator]() { return this.entries()[Symbol.iterator](); }
};

class MockXHR {
    open() {}
    send() {
        this.status = 200;
        this.responseText = JSON.stringify({ success: false, data: { message: 'Bad narrative' } });
    }
}
global.XMLHttpRequest = MockXHR;

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

BusinessCaseBuilder.prototype.init = function() {};
const builder = new BusinessCaseBuilder();
builder.form = form;
builder.showProgress = () => {};

let errorMessage = null;
builder.showResults = () => {};
builder.showError = (msg) => { errorMessage = msg; };

builder.handleSubmit();
assert.strictEqual(errorMessage, 'Bad narrative');
console.log('Error path test passed.');

