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

        const reportData = { report_html: '<div>ok</div>' };
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
        builder.form = { querySelector: () => ({ value: '' }) };
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
