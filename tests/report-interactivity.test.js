const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

const container = {
    childNodes: [],
    innerHTML: '',
    appendChild(node) {
        this.childNodes.push(node);
    }
};

global.document = {
    getElementById(id) {
        if (id === 'loading') {
            return { style: { display: 'none' } };
        }
        if (id === 'error') {
            return { style: { display: 'none' }, textContent: '' };
        }
        if (id === 'report-container') {
            return container;
        }
        return null;
    },
    createElement(tag) {
        return {
            tagName: tag,
            style: {},
            appendChild() {},
            set srcdoc(v) { this.srcdoc = v; },
            srcdoc: '',
            className: '',
            textContent: '',
            onclick: null
        };
    }
};

global.window = {};

global.rtbcbReport = { ajax_url: '', model_capabilities: {}, template_url: '' };

const code = fs.readFileSync('public/js/rtbcb-report.js', 'utf8');
vm.runInThisContext(code);

generateProfessionalReport = () => '<!DOCTYPE html><html><body>Report</body></html>';

(async () => {
    await generateAndDisplayReport({});
    const exportBtn = container.childNodes.find(node => node.textContent === 'Export to PDF');
    assert.ok(exportBtn, 'Export button not created');
    console.log('Report interactivity test passed.');
})();
