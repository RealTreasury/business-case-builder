require("./jsdom-setup");
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
    scenarios: {},
    recommendation: {},
    company_name: 'Test Co'
};

const html = builder.renderResults(data);
assert.ok(html.includes('Treasury technology investment presents a compelling opportunity for operational efficiency.'));
console.log('Render results without narrative test passed.');
