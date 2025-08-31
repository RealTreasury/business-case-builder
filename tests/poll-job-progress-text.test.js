const fs = require('fs');
const vm = require('vm');
require('./jsdom-setup');

describe('pollJob progress updates', () => {
    test('updates progress text with job status', async () => {
        jest.useFakeTimers();

        const nodeGlobal = vm.runInThisContext('this');

        const progressStatus = document.createElement('div');
        progressStatus.id = 'rtbcb-progress-status';
        progressStatus.textContent = '';
        document.body.appendChild(progressStatus);

        nodeGlobal.window = {};
        nodeGlobal.document = global.document;

        nodeGlobal.rtbcbAjax = { nonce: 'test-nonce' };

        const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
        vm.runInThisContext(code);
        const BusinessCaseBuilder = vm.runInThisContext('BusinessCaseBuilder');

        const fetchMock = jest.fn().mockResolvedValue({
            json: () => Promise.resolve({
                success: true,
                data: {
                    status: 'processing',
                    step: 'Gathering data'
                }
            })
        });
        nodeGlobal.fetch = fetchMock;

        const builder = Object.create(BusinessCaseBuilder.prototype);
        builder.ajaxUrl = 'https://example.com';
        builder.handleError = jest.fn();

        await builder.pollJob('123');

        expect(progressStatus.textContent).toBe('Gathering data');

        jest.clearAllTimers();
    });
});

