const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

// Test for initializeSectionToggles
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
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const content = document.getElementById(targetId);
                const arrow = this.querySelector('.rtbcb-toggle-arrow');
                const text = this.querySelector('.rtbcb-toggle-text');

                if (content) {
                    content.style.display = content.style.display === 'none' ? 'block' : 'none';
                    arrow.textContent = content.style.display === 'none' ? '▼' : '▲';
                    text.textContent = content.style.display === 'none' ? 'Expand' : 'Collapse';
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

// Test for initializeReportCharts
(() => {
    global.document = { addEventListener: () => {}, getElementById: () => null };
    global.window = {
        rtbcbReportData: {
            chartData: {
                labels: ['Labor Savings', 'Fee Savings', 'Error Reduction', 'Total Benefit'],
                datasets: [
                    { label: 'Conservative', data: [1000, 2000, 3000, 6000] },
                    { label: 'Base Case', data: [10000, 20000, 30000, 60000] },
                    { label: 'Optimistic', data: [100000, 200000, 300000, 600000] }
                ]
            }
        }
    };

    const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
    vm.runInThisContext(code);
    const builder = new BusinessCaseBuilder();

    const canvas = { getContext: () => ({}) };
    const container = {
        querySelector(selector) {
            if (selector === '#rtbcb-roi-chart') {
                return canvas;
            }
            return null;
        }
    };

    global.Chart = function(ctx, config) {
        global.__chartConfig = config;
    };

    builder.initializeReportCharts(container);
    const chartConfig = global.__chartConfig;
    assert.ok(chartConfig, 'Chart was not initialized');
    assert.strictEqual(chartConfig.data.datasets.length, 3);
    assert.deepStrictEqual(chartConfig.data.datasets[0].data, [1000, 2000, 3000, 6000]);
    console.log('initializeReportCharts test passed.');
})();
