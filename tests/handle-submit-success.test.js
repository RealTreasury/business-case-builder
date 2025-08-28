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

global.ajaxObj = { ajax_url: 'test-url' };

global.DOMPurify = { sanitize: (html) => html };


global.XMLHttpRequest = function() {
    this.open = function(method, url, async) {
        this.method = method;
        this.url = url;
        this.async = async;
    };
    this.send = function() {
        this.status = 200;
        this.responseText = JSON.stringify({
            success: true,
            data: {
                report_html: '<div>Report</div>',
                download_url: 'http://example.com/test.pdf'
            }
        });
    };
};

global.FormData = class { constructor() {} };

const code = fs.readFileSync('public/js/rtbcb.js', 'utf8');
vm.runInThisContext(code);

try {
    handleSubmit({ preventDefault() {}, target: {} });
    assert.strictEqual(reportContainer.innerHTML, '<div>Report</div>');
    assert.ok(!reportContainer.innerHTML.includes('test.pdf'));
    console.log('Success path test passed.');
} catch (error) {
    console.error(error);
    process.exit(1);
}
