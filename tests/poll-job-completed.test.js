const fs = require('fs');
const vm = require('vm');

// Minimal DOM stubs for script execution
const nodeGlobal = vm.runInThisContext('this');
nodeGlobal.window = {};
nodeGlobal.document = {
    getElementById: () => null,
    addEventListener: () => null,
    body: { style: {} },
};

// Load the BusinessCaseBuilder script
const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);
const BusinessCaseBuilder = vm.runInThisContext('BusinessCaseBuilder');

describe('pollJob', () => {
    test('calls handleSuccess when job is completed', async () => {
        const reportData = { result: 'ok' };

        const fetchMock = jest.fn().mockResolvedValue({
            json: () => Promise.resolve({
                success: true,
                data: {
                    status: 'completed',
                    result: { report_data: reportData },
                },
            }),
        });
        nodeGlobal.fetch = fetchMock;

        nodeGlobal.rtbcbAjax = { nonce: 'test-nonce' };

        const builder = Object.create(BusinessCaseBuilder.prototype);
        builder.ajaxUrl = 'https://example.com';
        builder.handleSuccess = jest.fn();
        builder.handleError = jest.fn();
        builder.activeJobId = '123';

        await builder.pollJob('123');

        expect(builder.handleSuccess).toHaveBeenCalledWith(reportData);
    });
});
