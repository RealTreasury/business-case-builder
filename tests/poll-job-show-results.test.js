const fs = require('fs');
const vm = require('vm');

describe('pollJob completion', () => {
    test('invokes showResults with report data and clears progress', async () => {
        const nodeGlobal = vm.runInThisContext('this');

        const progressContainer = { style: { display: 'block' }, innerHTML: 'loading' };
        const resultsContainer = { innerHTML: '', style: {}, scrollIntoView: () => {} };

        nodeGlobal.window = { closeBusinessCaseModal: () => {} };
        nodeGlobal.document = {
            getElementById: (id) => {
                if (id === 'rtbcb-progress-container') return progressContainer;
                if (id === 'rtbcbResults') return resultsContainer;
                return null;
            },
            addEventListener: () => {},
            body: { style: {} }
        };
        nodeGlobal.rtbcbAjax = { nonce: 'test-nonce' };

        const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
        vm.runInThisContext(code);
        const BusinessCaseBuilder = vm.runInThisContext('BusinessCaseBuilder');

        const reportData = { company_name: 'Test Co', scenarios: {} };
        const fetchMock = jest.fn().mockResolvedValue({
            json: () => Promise.resolve({
                success: true,
                data: {
                    status: 'completed',
                    result: { report_data: reportData }
                }
            })
        });
        nodeGlobal.fetch = fetchMock;

        const builder = Object.create(BusinessCaseBuilder.prototype);
        builder.ajaxUrl = 'https://example.com';
        builder.form = { querySelector: () => ({ value: '' }), closest: () => ({ style: {} }) };
        builder.renderResults = () => '';
        builder.populateRiskAssessment = () => {};
        builder.handleError = jest.fn();

        const showResultsSpy = jest.spyOn(builder, 'showResults');

        await builder.pollJob('123');

        expect(showResultsSpy).toHaveBeenCalledWith(reportData);
        expect(progressContainer.style.display).toBe('none');
        expect(progressContainer.innerHTML).toBe('');
    });
});

describe('pollJob partial results', () => {
    test('renders provisional fields while continuing to poll', async () => {
        const nodeGlobal = vm.runInThisContext('this');

        const roiContainer = { textContent: '', style: { display: 'none' } };
        const categoryContainer = { textContent: '', style: { display: 'none' } };

        nodeGlobal.window = { closeBusinessCaseModal: () => {} };
        nodeGlobal.document = {
            getElementById: (id) => {
                if (id === 'rtbcb-basic-roi') return roiContainer;
                if (id === 'rtbcb-category') return categoryContainer;
                return null;
            },
            createElement: () => {
                const el = { textContent: '' };
                Object.defineProperty(el, 'innerHTML', {
                    get() { return this.textContent; },
                    set(v) { this.textContent = v; }
                });
                return el;
            },
            addEventListener: () => {},
            body: { style: {} }
        };
        nodeGlobal.rtbcbAjax = { nonce: 'test-nonce' };

        const BusinessCaseBuilder = vm.runInThisContext('BusinessCaseBuilder');

        const fetchMock = jest
            .fn()
            .mockResolvedValueOnce({
                json: () =>
                    Promise.resolve({
                        success: true,
                        data: {
                            status: 'processing',
                            result: { basic_roi: '5x', category: 'Payments' }
                        }
                    })
            })
            .mockResolvedValueOnce({
                json: () =>
                    Promise.resolve({
                        success: true,
                        data: {
                            status: 'completed',
                            result: { report_html: '<div>Done</div>' }
                        }
                    })
            });

        nodeGlobal.fetch = fetchMock;

        const originalSetTimeout = nodeGlobal.setTimeout;
        nodeGlobal.setTimeout = (fn) => {
            fn();
        };

        const builder = Object.create(BusinessCaseBuilder.prototype);
        builder.ajaxUrl = 'https://example.com';
        builder.handleSuccess = jest.fn();
        builder.handleError = jest.fn();
        builder.partialResults = {};

        await builder.pollJob('123');
        await Promise.resolve();

        expect(roiContainer.textContent).toContain('5x');
        expect(categoryContainer.textContent).toContain('Payments');
        expect(fetchMock).toHaveBeenCalledTimes(2);

        nodeGlobal.setTimeout = originalSetTimeout;
    });
});
