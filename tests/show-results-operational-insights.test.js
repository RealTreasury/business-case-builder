const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

require('./jsdom-setup');

const nodeGlobal = vm.runInThisContext('this');

const resultsContainer = {
    innerHTML: '',
    style: {},
    scrollIntoView: () => {}
};

nodeGlobal.window = { closeBusinessCaseModal: () => {} };
nodeGlobal.document = {
    readyState: 'complete',
    addEventListener: () => {},
    getElementById: (id) => (id === 'rtbcbResults' ? resultsContainer : null)
};

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

const builder = new BusinessCaseBuilder();
builder.hideLoading = () => {};
builder.populateRiskAssessment = () => {};

let received;
builder.renderResults = (data) => {
    received = data;
    return '';
};

const data = {
    company_name: 'Test Co',
    operational_insights: {
        current_state_assessment: ['Insight']
    }
};

builder.showResults(data);

assert.deepStrictEqual(received.operational_analysis, {
    current_state_assessment: ['Insight']
});

console.log('showResults maps operational_insights test passed.');
