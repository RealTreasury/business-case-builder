const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

const progressContainer = { innerHTML: '', style: {} };
const formContainer = { style: {} };
const reportContainer = { innerHTML: '', style: {} };

global.document = {
    readyState: 'complete',
    addEventListener: () => {},
    getElementById: (id) => {
        if (id === 'rtbcb-progress-container') return progressContainer;
        if (id === 'rtbcb-form-container') return formContainer;
        if (id === 'rtbcb-report-container') return reportContainer;
        if (id === 'rtbcb-form') return {};
        return null;
    }
};

global.rtbcbAjax = { ajax_url: '' };

global.DOMPurify = { sanitize: (html) => html };

global.XMLHttpRequest = function () {
    this.open = function () {};
    this.setRequestHeader = function () {};
    this.send = function () {
        this.status = 500;
        this.responseText = JSON.stringify({ success: false, data: { message: 'Server exploded' } });
    };
};

global.FormData = class { constructor() {} };

const code = fs.readFileSync('public/js/rtbcb.js', 'utf8');
vm.runInThisContext(code);

handleSubmit({ preventDefault() {}, target: {} });
assert.ok(progressContainer.innerHTML.includes('Server exploded'));
console.log('Server error display test passed.');
