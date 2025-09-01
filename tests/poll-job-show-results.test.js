const fs = require('fs');
const vm = require('vm');
require('./jsdom-setup');

describe('pollJob completion', () => {
    test('invokes showResults with report data and clears progress', async () => {
        const nodeGlobal = vm.runInThisContext('this');

        const progressContainer = document.createElement('div');
        progressContainer.id = 'rtbcb-progress-container';
        progressContainer.style.display = 'block';
        progressContainer.innerHTML = 'loading';

        const resultsContainer = document.createElement('div');
        resultsContainer.id = 'rtbcbResults';
        resultsContainer.innerHTML = '';
        resultsContainer.style = {};
        resultsContainer.scrollIntoView = () => {};

        document.body.appendChild(progressContainer);
        document.body.appendChild(resultsContainer);

        nodeGlobal.window = { closeBusinessCaseModal: () => {} };
        nodeGlobal.document = global.document;
        nodeGlobal.rtbcb_ajax = { nonce: 'test-nonce' };

        const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
        vm.runInThisContext(code);
        const BusinessCaseBuilder = vm.runInThisContext('BusinessCaseBuilder');

        const reportData = { company_name: 'Test Co', scenarios: {} };
        const fetchMock = jest.fn().mockResolvedValue({
            json: () => Promise.resolve({
                success: true,
                data: {
                    status: 'completed',
                    report_data: reportData
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
