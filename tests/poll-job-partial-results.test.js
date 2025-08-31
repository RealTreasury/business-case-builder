const fs = require('fs');
const vm = require('vm');

describe('pollJob partial updates', () => {
    test('renders provisional fields and continues polling', async () => {
        jest.useFakeTimers();
        const nodeGlobal = vm.runInThisContext('this');

        const provisionalROI = { textContent: '', style: {} };
        const provisionalCategory = { textContent: '', style: {} };
        const provisionalContainer = { style: { display: 'none' } };
        const progressContainer = { style: { display: 'block' }, querySelector: () => null };
        const resultsContainer = { innerHTML: '', style: {}, scrollIntoView: () => {} };

        nodeGlobal.window = { closeBusinessCaseModal: () => {} };
        nodeGlobal.document = {
            getElementById: (id) => {
                if (id === 'rtbcb-provisional-roi') return provisionalROI;
                if (id === 'rtbcb-provisional-category') return provisionalCategory;
                if (id === 'rtbcb-provisional-container') return provisionalContainer;
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

        nodeGlobal.fetch = jest.fn().mockResolvedValue({
            json: () => Promise.resolve({ success: true, data: { status: 'processing', basic_roi: '5%', category: 'A' } })
        });

        const builder = Object.create(BusinessCaseBuilder.prototype);
        builder.ajaxUrl = 'https://example.com';
        builder.form = { querySelector: () => ({ value: '' }), closest: () => ({ style: {} }) };
        builder.renderResults = () => '';
        builder.populateRiskAssessment = () => {};
        builder.handleError = jest.fn();

        await builder.pollJob('123');
        await Promise.resolve();

        expect(provisionalContainer.style.display).toBe('block');
        expect(provisionalROI.textContent).toContain('5%');
        expect(provisionalCategory.textContent).toContain('A');
    });
});
