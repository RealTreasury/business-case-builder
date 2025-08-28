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
            this.responseText = JSON.stringify({ success: false, data: { message: 'Bad narrative' } });
        }
    };
};

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

const builder = Object.create(BusinessCaseBuilder.prototype);
builder.form = {};
builder.showProgress = () => {};
let errorMsg;
builder.showResults = () => {};
builder.showError = (msg) => { errorMsg = msg; };

builder.handleSubmit();

assert.strictEqual(errorMsg, 'Bad narrative');
console.log('Error path test passed.');
