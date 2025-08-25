const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

// Simple DOM element stubs
const button = { disabled: true };
const apiInput = { value: '' };
const companyInput = { value: '' };

function jQueryStub(selector) {
    if (selector === document) {
        return { ready: () => {}, on: () => {}, off: () => {} };
    }
    if (selector === '#rtbcb_openai_api_key') {
        return { val: () => apiInput.value };
    }
    if (selector === '#company-name-input') {
        return {
            val: () => companyInput.value,
            addClass: () => {},
            removeClass: () => {}
        };
    }
    if (selector === '[data-action="run-company-overview"]') {
        return {
            prop: (name, value) => {
                if (typeof value !== 'undefined') {
                    button[name] = value;
                }
                return button[name];
            }
        };
    }
    return {};
}

global.$ = jQueryStub;
global.jQuery = jQueryStub;
global.window = {};
global.document = { getElementById: () => ({}) };
global.rtbcbDashboard = { ajaxurl: '', nonces: { saveSettings: 'nonce' } };

global.FormData = function () {
    this._data = {
        nonce: 'nonce',
        rtbcb_openai_api_key: apiInput.value
    };
    this.get = (name) => this._data[name];
    this.entries = () => Object.entries(this._data);
};

const code = fs.readFileSync('admin/js/unified-test-dashboard.js', 'utf8');
vm.runInThisContext(code);

const RTBCBDashboard = window.RTBCBDashboard;
RTBCBDashboard.showNotification = () => {};
RTBCBDashboard.showError = () => {};
RTBCBDashboard.isGenerating = false;

// Prepare form values
companyInput.value = 'Acme Corp';
apiInput.value = 'sk-valid-key';

// Synchronous makeRequest stub
RTBCBDashboard.makeRequest = () => ({
    then: (cb) => {
        cb({ message: 'Settings saved' });
        return { catch: () => {} };
    }
});

button.disabled = true;
RTBCBDashboard.saveSettings();
assert.strictEqual(button.disabled, false);
console.log('saveSettings enables button test passed.');
