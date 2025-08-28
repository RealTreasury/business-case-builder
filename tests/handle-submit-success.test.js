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

global.fetch = () => Promise.resolve({
    ok: true,
    json: () => Promise.resolve({
        success: true,
        data: {
            report_html: '<div>Report</div>',
            download_url: 'http://example.com/test.pdf'
        }
    }),
    text: () => Promise.resolve('')
});

global.FormData = class { constructor() {} };

const code = fs.readFileSync('public/js/rtbcb.js', 'utf8');
vm.runInThisContext(code);

handleSubmit({ preventDefault() {}, target: {} })
    .then(() => {
        assert.strictEqual(reportContainer.innerHTML, '<div>Report</div>');
        assert.ok(!reportContainer.innerHTML.includes('test.pdf'));
        console.log('Success path test passed.');
    })
    .catch((error) => {
        console.error(error);
        process.exit(1);
    });
