const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

global.window = {};
global.document = {
    readyState: 'complete',
    addEventListener: () => {},
    getElementById: () => null
};

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

const builder = new BusinessCaseBuilder();

const data = {
    report_html: '<div id="custom-report">Custom Report</div>'
};

const html = builder.renderResults(data);
assert.ok(html.includes('Custom Report'));
console.log('Render results with report_html test passed.');
