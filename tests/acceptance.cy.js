// Cypress acceptance tests for Real Treasury Business Case Builder

// Clickable Buttons Test
// Test Spec: Button Click Functionality
describe('Dashboard Button Interactions', () => {
    it('should handle button clicks properly', () => {
        cy.visit('/wp-admin/admin.php?page=rtbcb-unified-tests');

        // Test each action button
        const buttonTests = [
            { selector: '[data-action="run-company-overview"]', expectedSpinner: true },
            { selector: '[data-action="run-llm-test"]', expectedSpinner: true },
            { selector: '[data-action="run-rag-test"]', expectedSpinner: true },
            { selector: '[data-action="api-health-ping"]', expectedSpinner: true }
        ];

        buttonTests.forEach(test => {
            cy.get(test.selector).should('exist').should('not.be.disabled');
            cy.get(test.selector).click();

            if (test.expectedSpinner) {
                cy.get(test.selector).should('contain', 'Loading...');
                cy.get(test.selector).should('have.class', 'rtbcb-loading');
            }

            // Wait for response or timeout
            cy.get(test.selector, { timeout: 60000 }).should('not.have.class', 'rtbcb-loading');
        });
    });
});

// Red Notice Logic Test
describe('OpenAI API Notice System', () => {
    it('should show correct notices based on API health', () => {
        // Simulate failed ping
        cy.window().then((win) => {
            win.localStorage.setItem('rtbcb_api_last_error', Date.now() - 30000); // 30 seconds ago
            win.localStorage.removeItem('rtbcb_api_last_ok');
        });

        cy.visit('/wp-admin/admin.php?page=rtbcb-unified-tests');
        cy.get('.notice-error').should('contain', 'OpenAI API Error');

        // Simulate successful ping
        cy.window().then((win) => {
            win.localStorage.setItem('rtbcb_api_last_ok', Date.now() - 60000); // 1 minute ago
            win.localStorage.removeItem('rtbcb_api_last_error');
        });

        cy.reload();
        cy.get('.notice-success').should('contain', 'Connection healthy');
    });
});

// LLM Matrix Test
describe('LLM Integration Tests', () => {
    it('should run model comparison and display results', () => {
        cy.visit('/wp-admin/admin.php?page=rtbcb-unified-tests#llm-tests');

        // Configure test parameters
        cy.get('#llm-user-prompt').type('Analyze treasury operations for a mid-market company');
        cy.get('input[name="llm-models[]"][value="mini"]').check();
        cy.get('input[name="llm-models[]"][value="premium"]').check();

        // Run comparison
        cy.get('[data-action="run-llm-test"]').click();

        // Verify results table appears
        cy.get('#model-comparison-results', { timeout: 120000 }).should('be.visible');
        cy.get('#model-summary-cards .rtbcb-model-summary-card').should('have.length', 2);

        // Verify metrics are populated
        cy.get('.rtbcb-metric-value').each(($el) => {
            cy.wrap($el).should('not.be.empty');
            cy.wrap($el).should('not.contain', '--');
        });

        // Test export functionality
        cy.get('#export-llm-comparison').should('not.be.disabled').click();
    });
});

// RAG Metrics Test
describe('RAG System Tests', () => {
    it('should calculate correct nDCG@5 and Recall@3 metrics', () => {
        const testQuery = 'treasury management software';
        const expectedNdcg = 0.8; // Minimum acceptable nDCG@5
        const expectedRecall = 0.6; // Minimum acceptable Recall@3

        cy.visit('/wp-admin/admin.php?page=rtbcb-unified-tests#rag-system');

        cy.get('#rtbcb-rag-query').type(testQuery);
        cy.get('#rtbcb-rag-top-k').clear().type('5');
        cy.get('[data-action="run-rag-test"]').click();

        cy.get('#rtbcb-rag-results', { timeout: 60000 }).should('be.visible');

        // Verify metrics calculation
        cy.get('#rtbcb-rag-metrics').should('contain', 'nDCG@5');
        cy.get('#rtbcb-rag-metrics').should('contain', 'Recall@3');

        // Extract and validate metrics
        cy.get('#rtbcb-rag-metrics').invoke('text').then((text) => {
            const ndcgMatch = text.match(/nDCG@5:\s*([\d.]+)/);
            const recallMatch = text.match(/Recall@3:\s*([\d.]+)/);

            expect(parseFloat(ndcgMatch[1])).to.be.at.least(expectedNdcg);
            expect(parseFloat(recallMatch[1])).to.be.at.least(expectedRecall);
        });
    });
});

// API Health Test
describe('API Health Monitoring', () => {
    it('should handle different error scenarios correctly', () => {
        cy.visit('/wp-admin/admin.php?page=rtbcb-unified-tests#api-health');

        // Test successful ping (200 response)
        cy.intercept('POST', '**/admin-ajax.php', { fixture: 'api-success.json' }).as('successPing');
        cy.get('[data-action="api-health-ping"]').click();
        cy.wait('@successPing');
        cy.get('.rtbcb-status-indicator.status-good').should('exist');

        // Test rate limit (429 response)
        cy.intercept('POST', '**/admin-ajax.php', {
            statusCode: 429,
            body: { success: false, data: { code: 'rate_limited' } }
        }).as('rateLimitPing');
        cy.get('[data-action="api-health-ping"]').click();
        cy.wait('@rateLimitPing');
        cy.get('.notice-warning').should('contain', 'Rate limit exceeded');

        // Test server error (5xx response)
        cy.intercept('POST', '**/admin-ajax.php', {
            statusCode: 500,
            body: { success: false, data: { code: 'server_error' } }
        }).as('serverErrorPing');
        cy.get('[data-action="api-health-ping"]').click();
        cy.wait('@serverErrorPing');
        cy.get('.rtbcb-status-indicator.status-error').should('exist');
    });
});
