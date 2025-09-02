const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

require('./jsdom-setup');

global.rtbcb_ajax = {
    ajax_url: 'ftp://example.com',
    nonce: 'test-nonce',
    strings: { generating: 'Generating...' }
};

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

let fetchUrl = '';
global.fetch = function(url) {
    fetchUrl = url;
    return Promise.resolve({
        ok: false,
        status: 500,
        statusText: 'Error',
        text: () => Promise.resolve('{"data":{"message":"Server error","error_code":"E1"}}')
    });
};

const form = {
    fields: { email: 'test@example.com' },
    querySelector: () => null,
    querySelectorAll: () => [],
    addEventListener: () => {},
    closest: () => ({ style: {} })
};

const formElem = document.createElement('form');
formElem.id = 'rtbcbForm';
Object.assign(formElem, form);
document.body.appendChild(formElem);

document.readyState = 'complete';
const progressContainer = document.createElement('div');
progressContainer.id = 'rtbcb-progress-container';
document.body.appendChild(progressContainer);

let code = fs.readFileSync('public/js/rtbcb.js', 'utf8');
code = code.replace('export async function rtbcbStreamAnalysis', 'async function rtbcbStreamAnalysis');
vm.runInThisContext(code);

let errorMessage = null;
handleSubmissionError = (msg) => { errorMessage = msg; };

(async () => {
    await handleSubmit({ preventDefault: () => {}, target: formElem });
    assert.strictEqual(fetchUrl, '/wp-admin/admin-ajax.php');
    assert.strictEqual(errorMessage, 'Server error');
    console.log('rtbcb handleSubmit invalid ajaxUrl fallback test passed.');
})().catch(err => {
    console.error(err);
    process.exit(1);
});
