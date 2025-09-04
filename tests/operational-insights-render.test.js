const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

// Minimal stubs for browser globals used in rtbcb-wizard.js
global.window = {};
global.document = {
addEventListener: () => {},
getElementById: () => null,
};

const originalRequire = global.require;
global.require = undefined;
const wizardCode = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(wizardCode);
global.require = originalRequire;

const renderOperationalAnalysis = BusinessCaseBuilder.prototype.renderOperationalAnalysis;
const context = { escapeHTML: (s) => s };
const html = renderOperationalAnalysis.call(context, {
    current_state_assessment: ['Assessment'],
    process_improvements: [
        {
            process_area: 'Reconciliation',
            current_state: 'Manual',
            improved_state: 'Automated',
            impact_level: 'High',
        },
    ],
    automation_opportunities: [
        {
            opportunity: 'Cash Forecasting',
            complexity: 'Medium',
            time_savings: '10 hours',
        },
    ],
});

const htmlCamel = renderOperationalAnalysis.call(context, {
    currentStateAssessment: ['Assessment'],
    processImprovements: [
        {
            process: 'Reconciliation',
            currentState: 'Manual',
            improvedState: 'Automated',
            impactLevel: 'High',
        },
    ],
    automationOpportunities: [
        {
            opportunity: 'Cash Forecasting',
            complexity: 'Medium',
            timeSavings: '10 hours',
        },
    ],
});

assert.ok(html.includes('<li>Assessment</li>'), 'Assessment missing');
assert.ok(html.includes('Reconciliation'), 'Process name missing');
assert.ok(html.includes('Manual'), 'Current state missing');
assert.ok(html.includes('Automated'), 'Improved state missing');
assert.ok(html.includes('High'), 'Impact missing');
assert.ok(html.includes('Cash Forecasting'), 'Opportunity missing');
assert.ok(html.includes('Medium'), 'Complexity missing');
assert.ok(html.includes('10 hours'), 'Savings missing');
assert.ok(htmlCamel.includes('Reconciliation'), 'CamelCase process missing');
assert.ok(htmlCamel.includes('Manual'), 'CamelCase current state missing');
assert.ok(htmlCamel.includes('Automated'), 'CamelCase improved state missing');
assert.ok(htmlCamel.includes('High'), 'CamelCase impact missing');
assert.ok(htmlCamel.includes('Cash Forecasting'), 'CamelCase opportunity missing');
assert.ok(htmlCamel.includes('Medium'), 'CamelCase complexity missing');
assert.ok(htmlCamel.includes('10 hours'), 'CamelCase savings missing');
console.log('Operational analysis render test passed.');
