const fs = require('fs');
const vm = require('vm');

describe('pollJob provisional data', () => {
    test('renders partial fields while continuing to poll', async () => {
        jest.useFakeTimers();

        const nodeGlobal = vm.runInThisContext('this');

        const progressCategory = { style: { display: 'none' }, textContent: '' };
        const progressROI = { style: { display: 'none' }, textContent: '' };
        const progressStatus = { textContent: '' };

        nodeGlobal.window = {};
        nodeGlobal.document = {
            getElementById: (id) => {
                if (id === 'rtbcb-progress-category') return progressCategory;
                if (id === 'rtbcb-progress-basic-roi') return progressROI;
                if (id === 'rtbcb-progress-status') return progressStatus;
                return null;
            },
            addEventListener: () => {},
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
                        basic_roi: '50%',
                        category: 'Treasury Tech'
                    }
                })
            })
            .mockResolvedValueOnce({
                json: () => Promise.resolve({
                    success: true,
                    data: {
                        status: 'completed',
                        report_data: { result: 'ok' }
                    }
                })
            });
        nodeGlobal.fetch = fetchMock;

        const builder = Object.create(BusinessCaseBuilder.prototype);
        builder.ajaxUrl = 'https://example.com';
        builder.handleSuccess = jest.fn();
        builder.handleError = jest.fn();

        await builder.pollJob('123');
        expect(progressCategory.textContent).toBe('Category: Treasury Tech');
        expect(progressCategory.style.display).toBe('block');
        expect(progressROI.textContent).toBe('ROI: 50%');
        expect(progressROI.style.display).toBe('block');
        expect(builder.handleSuccess).not.toHaveBeenCalled();

        await builder.pollJob('123');
        expect(builder.handleSuccess).toHaveBeenCalled();
        jest.useRealTimers();
    });
});

