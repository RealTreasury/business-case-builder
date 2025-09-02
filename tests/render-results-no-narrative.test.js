const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

require('./jsdom-setup');

global.window = {};
global.document = {
    readyState: 'complete',
    addEventListener: () => {},
    getElementById: () => null,
    createElement: () => ({
        innerHTML: '',
        set textContent(value) {
            this.innerHTML = value;
        }
    })
};

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

const builder = new BusinessCaseBuilder();

const data = {
    scenarios: {},
    recommendation: {},
    companyName: 'Test Co'
};

const html = builder.renderResults(data);
assert.ok(html.includes('Treasury technology investment presents a compelling opportunity for operational efficiency.'));
assert.ok(html.includes('No operational analysis available.'));
assert.ok(html.includes('No industry insights available.'));
console.log('Render results without narrative test passed.');
