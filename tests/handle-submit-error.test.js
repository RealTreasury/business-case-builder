const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

// Minimal DOM and globals
global.window = {};
global.document = {
    readyState: 'loading',
    addEventListener: () => {},
    body: { insertAdjacentHTML: () => {}, style: {} },
    getElementById: () => ({}),
    querySelector: () => null
};

global.ajaxObj = { ajax_url: '', rtbcb_nonce: '' };

global.fetch = async () => ({
    text: async () => JSON.stringify({
        success: true,
        data: { narrative: { error: 'Bad narrative' } }
    })
});

global.FormData = class {
    append() {}
    set() {}
};

global.URLSearchParams = class {
    constructor() {}
};

const code = fs.readFileSync('public/js/rtbcb.js', 'utf8');
vm.runInThisContext(code);

const builder = new BusinessCaseBuilder();
builder.form = {};
builder.validateCurrentStep = () => true;
builder.saveStepData = () => {};
builder.showProgressIndicator = () => {};
builder.disableNavigation = () => {};
builder.startProgressSimulation = () => {};

let errorMsg = '';
let hid = false;
let enabled = false;
let displayCalled = false;

builder.showError = msg => { errorMsg = msg; };
builder.hideProgressIndicator = () => { hid = true; };
builder.enableNavigation = () => { enabled = true; };
builder.displayResults = () => { displayCalled = true; };

(async () => {
    await builder.handleSubmit({ preventDefault() {} });
    assert.strictEqual(errorMsg, 'Bad narrative');
    assert.ok(hid);
    assert.ok(enabled);
    assert.strictEqual(displayCalled, false);
    console.log('Error path test passed.');
})();
