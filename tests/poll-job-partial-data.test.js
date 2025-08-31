const fs = require('fs');
const vm = require('vm');

describe('pollJob partial data', () => {
    test('renders partial fields and continues polling', async () => {
        jest.useFakeTimers();

        const nodeGlobal = vm.runInThisContext('this');
        const setTimeoutSpy = jest.spyOn(nodeGlobal, 'setTimeout');

        const progressStatus = { textContent: '' };
        const basicRoi = { textContent: '' };
        const category = { textContent: '' };
        const provisionalContainer = { style: { display: 'none' } };

        nodeGlobal.window = {};
        nodeGlobal.document = {
            getElementById: (id) => {
                if (id === 'rtbcb-progress-status') return progressStatus;
                if (id === 'rtbcb-basic-roi') return basicRoi;
                if (id === 'rtbcb-category') return category;
                if (id === 'rtbcb-provisional-data') return provisionalContainer;
                return null;
            },
            addEventListener: () => {},
            body: { style: {} }
        };

        nodeGlobal.rtbcbAjax = { nonce: 'test-nonce' };

        const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
        vm.runInThisContext(code);
        const BusinessCaseBuilder = vm.runInThisContext('BusinessCaseBuilder');

        const fetchMock = jest.fn().mockResolvedValue({
            json: () => Promise.resolve({
                success: true,
                data: {
                    status: 'processing',
                    result: {
                        basic_roi: '12%',
                        category: 'Finance'
                    }
                }
            })
        });
        nodeGlobal.fetch = fetchMock;

        const builder = Object.create(BusinessCaseBuilder.prototype);
        builder.ajaxUrl = 'https://example.com';
        builder.handleSuccess = jest.fn();
        builder.handleError = jest.fn();

        await builder.pollJob('123');

        expect(provisionalContainer.style.display).toBe('block');
        expect(basicRoi.textContent).toBe('Basic ROI: 12%');
        expect(category.textContent).toBe('Category: Finance');
        expect(setTimeoutSpy).toHaveBeenCalled();

        jest.clearAllTimers();
        setTimeoutSpy.mockRestore();
    });
});
