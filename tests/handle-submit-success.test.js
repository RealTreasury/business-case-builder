const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

global.window = {};
global.ajaxObj = { ajax_url: 'test-url' };
global.document = {
    addEventListener: () => {},
    getElementById: () => null
};

global.FormData = class {
    constructor() { this._data = []; }
    append(key, value) { this._data.push([key, value]); }
    entries() { return this._data[Symbol.iterator](); }
    [Symbol.iterator]() { return this._data[Symbol.iterator](); }
};

global.XMLHttpRequest = function() {
    return {
        open() {},
        send() {
            this.status = 200;
            this.responseText = JSON.stringify({ success: true, data: { report_html: '<div>Report</div>' } });
        }
    };
};

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

const builder = Object.create(BusinessCaseBuilder.prototype);
builder.form = {};
builder.showProgress = () => {};
let result;
builder.showResults = (data) => { result = data; };
builder.showError = () => { throw new Error('Should not fail'); };

builder.handleSubmit();

assert.strictEqual(result.report_html, '<div>Report</div>');
console.log('Success path test passed.');
