const fs = require('fs');
const vm = require('vm');

describe('pollJob partial updates', () => {
test('renders provisional ROI and category while processing', async () => {
jest.useFakeTimers();

const nodeGlobal = vm.runInThisContext('this');

const progressStatus = { textContent: '' };
const roiElem = { textContent: '', style: { display: 'none' } };
const categoryElem = { textContent: '', style: { display: 'none' } };

nodeGlobal.window = {};
nodeGlobal.document = {
getElementById: (id) => {
if (id === 'rtbcb-progress-status') {
return progressStatus;
}
if (id === 'rtbcb-partial-basic-roi') {
return roiElem;
}
if (id === 'rtbcb-partial-category') {
return categoryElem;
}
return null;
},
addEventListener: () => {},
createElement: () => {
const elem = { innerHTML: '' };
Object.defineProperty(elem, 'textContent', {
get() {
return this.innerHTML;
},
set(v) {
this.innerHTML = v;
}
});
return elem;
},
body: { style: {} }
};

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

