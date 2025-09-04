const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

require('./jsdom-setup');

// Test for initializeSectionToggles (collapsed by default)
(() => {
    const content = { style: { display: 'none' } };
    const arrow = { textContent: '▼' };
    const text = { textContent: 'Expand' };
    const toggle = {
        getAttribute(name) {
            if (name === 'data-target') {
                return 'content';
            }
            return null;
        },
        querySelector(selector) {
            if (selector === '.rtbcb-toggle-arrow') {
                return arrow;
            }
            if (selector === '.rtbcb-toggle-text') {
                return text;
            }
            return null;
        },
        addEventListener(event, handler) {
            this.handler = handler;
        }
    };

    global.document = {
        querySelectorAll(selector) {
            if (selector === '.rtbcb-section-toggle') {
                return [toggle];
            }
            return [];
        },
        getElementById(id) {
            if (id === 'content') {
                return content;
            }
            return null;
        }
    };

    function initializeSectionToggles() {
        document.querySelectorAll('.rtbcb-section-toggle').forEach(toggle => {
            const targetId = toggle.getAttribute('data-target');
            const content = document.getElementById(targetId);
            const arrow = toggle.querySelector('.rtbcb-toggle-arrow');
            const text = toggle.querySelector('.rtbcb-toggle-text');

            if (content) {
                const initiallyVisible = content.style.display !== 'none';
                arrow.textContent = initiallyVisible ? '▲' : '▼';
                text.textContent = initiallyVisible ? 'Collapse' : 'Expand';
            }

            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const content = document.getElementById(targetId);
                const arrow = this.querySelector('.rtbcb-toggle-arrow');
                const text = this.querySelector('.rtbcb-toggle-text');

                if (content) {
                    const isVisible = content.style.display !== 'none';
                    content.style.display = isVisible ? 'none' : 'block';
                    const nowVisible = !isVisible;
                    arrow.textContent = nowVisible ? '▲' : '▼';
                    text.textContent = nowVisible ? 'Collapse' : 'Expand';
                }
            });
        });
    }

    initializeSectionToggles();
    toggle.handler();
    assert.strictEqual(content.style.display, 'block');
    assert.strictEqual(arrow.textContent, '▲');
    assert.strictEqual(text.textContent, 'Collapse');
    toggle.handler();
    assert.strictEqual(content.style.display, 'none');
    assert.strictEqual(arrow.textContent, '▼');
    assert.strictEqual(text.textContent, 'Expand');
    console.log('initializeSectionToggles test passed.');
})();

// Test that initializeSectionToggles sets initial state when content visible
(() => {
    const content = { style: { display: 'block' } };
    const arrow = { textContent: '▼' };
    const text = { textContent: 'Expand' };
    const toggle = {
        getAttribute(name) {
            if (name === 'data-target') {
                return 'content';
            }
            return null;
        },
        querySelector(selector) {
            if (selector === '.rtbcb-toggle-arrow') {
                return arrow;
            }
            if (selector === '.rtbcb-toggle-text') {
                return text;
            }
            return null;
        },
        addEventListener(event, handler) {
            this.handler = handler;
        }
    };

    global.document = {
        querySelectorAll(selector) {
            if (selector === '.rtbcb-section-toggle') {
                return [toggle];
            }
            return [];
        },
        getElementById(id) {
            if (id === 'content') {
                return content;
            }
            return null;
        }
    };

    function initializeSectionToggles() {
        document.querySelectorAll('.rtbcb-section-toggle').forEach(toggle => {
            const targetId = toggle.getAttribute('data-target');
            const content = document.getElementById(targetId);
            const arrow = toggle.querySelector('.rtbcb-toggle-arrow');
            const text = toggle.querySelector('.rtbcb-toggle-text');

            if (content) {
                const initiallyVisible = content.style.display !== 'none';
                arrow.textContent = initiallyVisible ? '▲' : '▼';
                text.textContent = initiallyVisible ? 'Collapse' : 'Expand';
            }

            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const content = document.getElementById(targetId);
                const arrow = this.querySelector('.rtbcb-toggle-arrow');
                const text = this.querySelector('.rtbcb-toggle-text');

                if (content) {
                    const isVisible = content.style.display !== 'none';
                    content.style.display = isVisible ? 'none' : 'block';
                    const nowVisible = !isVisible;
                    arrow.textContent = nowVisible ? '▲' : '▼';
                    text.textContent = nowVisible ? 'Collapse' : 'Expand';
                }
            });
        });
    }

    initializeSectionToggles();
    // Initial state should be set to collapse since content is visible
    assert.strictEqual(arrow.textContent, '▲');
    assert.strictEqual(text.textContent, 'Collapse');
    toggle.handler();
    assert.strictEqual(content.style.display, 'none');
    assert.strictEqual(arrow.textContent, '▼');
    assert.strictEqual(text.textContent, 'Expand');
    console.log('initializeSectionToggles initial state test passed.');
})();

// Test for initializeReportCharts
(() => {
    global.document = { addEventListener: () => {}, getElementById: () => null };
    global.window = {};

    const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
    vm.runInThisContext(code);
    const builder = new BusinessCaseBuilder();

    const canvas = { getContext: () => ({}) };

    function createCard(cls, values) {
        const metrics = Object.entries(values).map(([label, value]) => ({
            querySelector(selector) {
                if (selector === '.rtbcb-metric-label') {
                    return { textContent: label };
                }
                if (selector === '.rtbcb-metric-value') {
                    return { textContent: value };
                }
                return null;
            }
        }));
        return {
            classList: { contains: name => name === cls },
            querySelectorAll(selector) {
                if (selector === '.rtbcb-scenario-metric') {
                    return metrics;
                }
                return [];
            },
            querySelector(selector) {
                if (selector === 'h4') {
                    return null;
                }
                return null;
            }
        };
    }

    const cards = [
        createCard('conservative', {
            'Labor Savings': '$1,000',
            'Fee Savings': '$2,000',
            'Error Reduction': '$3,000',
            'Total Annual Benefit': '$6,000'
        }),
        createCard('base', {
            'Labor Savings': '$10,000',
            'Fee Savings': '$20,000',
            'Error Reduction': '$30,000',
            'Total Annual Benefit': '$60,000'
        }),
        createCard('optimistic', {
            'Labor Savings': '$100,000',
            'Fee Savings': '$200,000',
            'Error Reduction': '$300,000',
            'Total Annual Benefit': '$600,000'
        })
    ];

    const container = {
        querySelector(selector) {
            if (selector === '#rtbcb-roi-chart') {
                return canvas;
            }
            return null;
        },
        querySelectorAll(selector) {
            if (selector === '.rtbcb-scenario-card') {
                return cards;
            }
            return [];
        }
    };

    global.Chart = function(ctx, config) {
        global.__chartConfig = config;
    };

    builder.initializeReportCharts(container);
    const chartConfig = global.__chartConfig;
    assert.ok(chartConfig, 'Chart was not initialized');
    assert.strictEqual(chartConfig.data.datasets.length, 3);
    assert.deepStrictEqual(
        chartConfig.data.datasets.map(d => d.label),
        ['Conservative', 'Base Case', 'Optimistic']
    );
    assert.deepStrictEqual(chartConfig.data.datasets[0].data, [1000, 2000, 3000, 6000]);
    console.log('initializeReportCharts test passed.');
})();
