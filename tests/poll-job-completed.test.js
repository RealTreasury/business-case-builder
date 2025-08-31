const fs = require('fs');
const vm = require('vm');
require('./jsdom-setup');

// Minimal DOM stubs for script execution
const nodeGlobal = vm.runInThisContext('this');
nodeGlobal.window = global.window;
nodeGlobal.document = global.document;

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
                    report_data: reportData,
                },
            }),
        });
        nodeGlobal.fetch = fetchMock;

        nodeGlobal.rtbcbAjax = { nonce: 'test-nonce' };

        const builder = Object.create(BusinessCaseBuilder.prototype);
        builder.ajaxUrl = 'https://example.com';
        builder.handleSuccess = jest.fn();
        builder.handleError = jest.fn();

        await builder.pollJob('123');

        expect(builder.handleSuccess).toHaveBeenCalledWith(reportData);
    });
});
