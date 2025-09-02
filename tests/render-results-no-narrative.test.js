const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

require('./jsdom-setup');

global.window = {};
global.document = {
    readyState: 'complete',
    addEventListener: () => {},
    getElementById: () => null
};

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

const builder = new BusinessCaseBuilder();
builder.escapeHTML = (s) => s;

const data = {
    scenarios: {},
    recommendation: {},
    companyName: 'Test Co',
    industryInsights: { sector_trends: ['Growth trend'] }
};

const html = builder.renderResults(data);
assert.ok(html.includes('Treasury technology investment presents a compelling opportunity for operational efficiency.'));
assert.ok(html.includes('Industry Insights'));
assert.ok(html.includes('Growth trend'));
console.log('Render results without narrative test passed.');
