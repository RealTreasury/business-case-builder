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

global.ajaxObj = { ajax_url: '', rtbcb_nonce: '' };

global.fetch = async () => ({
    ok: true,
    json: async () => ({ success: false, data: { message: 'Bad narrative' } }),
    text: async () => ''
});

global.FormData = class { constructor() {} };

const code = fs.readFileSync('public/js/rtbcb.js', 'utf8');
vm.runInThisContext(code);

(async () => {
    await handleSubmit({ preventDefault() {}, target: {} });
    assert.ok(progressContainer.innerHTML.includes('Bad narrative'));
    console.log('Error path test passed.');
})();
