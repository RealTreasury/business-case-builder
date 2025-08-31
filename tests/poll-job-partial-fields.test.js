const fs = require('fs');
const fs = require('fs');
const vm = require('vm');
require('./jsdom-setup');

describe('pollJob partial updates', () => {
test('renders provisional ROI and category while processing', async () => {
jest.useFakeTimers();

const nodeGlobal = vm.runInThisContext('this');

const progressStatus = document.createElement('div');
progressStatus.id = 'rtbcb-progress-status';
const roiElem = document.createElement('div');
roiElem.id = 'rtbcb-partial-basic-roi';
roiElem.style.display = 'none';
const categoryElem = document.createElement('div');
categoryElem.id = 'rtbcb-partial-category';
categoryElem.style.display = 'none';

document.body.appendChild(progressStatus);
document.body.appendChild(roiElem);
document.body.appendChild(categoryElem);

nodeGlobal.window = {};
nodeGlobal.document = global.document;

nodeGlobal.rtbcbAjax = { nonce: 'test-nonce' };

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);
const BusinessCaseBuilder = vm.runInThisContext('BusinessCaseBuilder');

const fetchMock = jest.fn()
.mockResolvedValueOnce({
json: () => Promise.resolve({
success: true,
data: {
status: 'processing',
step: 'Calculating',
basic_roi: {
financial_analysis: {
roi_scenarios: {
base: { total_annual_benefit: 12345 }
}
}
},
category: 'tms'
}
})
})
.mockResolvedValueOnce({
json: () => Promise.resolve({
success: true,
data: { status: 'completed', report_data: {} }
})
});

nodeGlobal.fetch = fetchMock;

const builder = Object.create(BusinessCaseBuilder.prototype);
builder.ajaxUrl = 'https://example.com';
builder.handleError = jest.fn();
builder.handleSuccess = jest.fn();
builder.hideLoading = jest.fn();

await builder.pollJob('123');
await Promise.resolve();

expect(roiElem.style.display).toBe('block');
expect(roiElem.textContent).toContain('$12,345');
expect(categoryElem.style.display).toBe('block');
expect(categoryElem.textContent).toContain('tms');

jest.advanceTimersByTime(2000);
await Promise.resolve();
await Promise.resolve();
});
});

