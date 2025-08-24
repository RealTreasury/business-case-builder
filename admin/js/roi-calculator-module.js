/**
 * ROI Calculator Module for Unified Test Dashboard
 */

(function($) {
    const Dashboard = window.RTBCBDashboard || {};

    Object.assign(Dashboard, {
        // ROI Calculator state
        roiData: null,
        roiCharts: {},
        roiScenarios: {
            'small-company': {
                name: 'Small Company',
                'roi-company-size': 'small',
                'roi-annual-revenue': 10000000,
                'roi-treasury-staff': 2,
                'roi-avg-salary': 75000,
                'roi-hours-reconciliation': 6,
                'roi-hours-reporting': 3,
                'roi-hours-analysis': 2,
                'roi-num-banks': 4,
                'roi-monthly-bank-fees': 5000,
                'roi-wire-transfer-volume': 50,
                'roi-avg-wire-fee': 25,
                'roi-error-frequency': 2,
                'roi-avg-error-cost': 1000,
                'roi-compliance-hours': 20,
                'roi-system-integration': 'manual'
            },
            'medium-company': {
                name: 'Medium Company',
                'roi-company-size': 'medium',
                'roi-annual-revenue': 100000000,
                'roi-treasury-staff': 5,
                'roi-avg-salary': 85000,
                'roi-hours-reconciliation': 4,
                'roi-hours-reporting': 2,
                'roi-hours-analysis': 3,
                'roi-num-banks': 8,
                'roi-monthly-bank-fees': 15000,
                'roi-wire-transfer-volume': 150,
                'roi-avg-wire-fee': 25,
                'roi-error-frequency': 3,
                'roi-avg-error-cost': 2500,
                'roi-compliance-hours': 40,
                'roi-system-integration': 'partial'
            },
            'large-company': {
                name: 'Large Company',
                'roi-company-size': 'large',
                'roi-annual-revenue': 1000000000,
                'roi-treasury-staff': 15,
                'roi-avg-salary': 95000,
                'roi-hours-reconciliation': 3,
                'roi-hours-reporting': 1.5,
                'roi-hours-analysis': 4,
                'roi-num-banks': 20,
                'roi-monthly-bank-fees': 50000,
                'roi-wire-transfer-volume': 500,
                'roi-avg-wire-fee': 30,
                'roi-error-frequency': 5,
                'roi-avg-error-cost': 5000,
                'roi-compliance-hours': 80,
                'roi-system-integration': 'integrated'
            }
        },

        // Initialize ROI Calculator
        initROICalculator() {
            this.bindROIEvents();
            this.loadROIScenario('medium-company'); // Default scenario
        },

        // Bind ROI Calculator events
        bindROIEvents() {
            // Scenario switching
            $('.rtbcb-scenario-tab').on('click', this.handleScenarioSwitch.bind(this));

            // Real-time input updates
            $('.rtbcb-roi-input-grid input, .rtbcb-roi-input-grid select').on('change input', this.handleROIInputChange.bind(this));

            // Action buttons
            $('#calculate-roi').on('click', this.calculateROI.bind(this));
            $('#run-sensitivity-analysis').on('click', this.runSensitivityAnalysis.bind(this));
            $('#compare-scenarios').on('click', this.compareScenarios.bind(this));
            $('#export-roi-results').on('click', this.exportROIResults.bind(this));

            // Results actions
            $('#toggle-roi-details').on('click', this.toggleROIDetails.bind(this));
            $('#copy-roi-summary').on('click', this.copyROISummary.bind(this));
        },

        // Handle scenario switching
        handleScenarioSwitch(e) {
            const scenario = $(e.target).data('scenario');

            if (scenario === 'custom') {
                // Don't change inputs for custom scenario
                this.setActiveScenario(scenario);
                return;
            }

            this.loadROIScenario(scenario);
            this.setActiveScenario(scenario);
        },

        // Set active scenario tab
        setActiveScenario(scenario) {
            $('.rtbcb-scenario-tab').removeClass('active');
            $(`.rtbcb-scenario-tab[data-scenario="${scenario}"]`).addClass('active');
        },

        // Load scenario data into form
        loadROIScenario(scenario) {
            if (!this.roiScenarios[scenario]) return;

            const data = this.roiScenarios[scenario];

            Object.keys(data).forEach(key => {
                if (key !== 'name') {
                    $(`#${key}`).val(data[key]).trigger('change');
                }
            });

            this.updateROIInputHelpers();
        },

        // Handle input changes with real-time feedback
        handleROIInputChange(e) {
            const $input = $(e.target);
            const value = $input.val();

            // Update helper text for revenue
            if ($input.attr('id') === 'roi-annual-revenue') {
                const formatted = this.formatCurrency(parseFloat(value) || 0, 0);
                $input.siblings('.rtbcb-input-helper').text(formatted);
            }

            // Mark as custom scenario when user makes changes
            if (!$('.rtbcb-scenario-tab[data-scenario="custom"]').hasClass('active')) {
                this.setActiveScenario('custom');
            }

            // Enable calculate button if all required fields are filled
            this.validateROIForm();
        },

        // Update input helpers
        updateROIInputHelpers() {
            const revenue = parseFloat($('#roi-annual-revenue').val()) || 0;
            $('#roi-annual-revenue').siblings('.rtbcb-input-helper').text(this.formatCurrency(revenue, 0));
        },

        // Validate ROI form
        validateROIForm() {
            const requiredFields = [
                '#roi-annual-revenue',
                '#roi-treasury-staff',
                '#roi-avg-salary',
                '#roi-hours-reconciliation'
            ];

            const isValid = requiredFields.every(field => {
                const value = $(field).val();
                return value && parseFloat(value) > 0;
            });

            $('#calculate-roi').prop('disabled', !isValid);
        },

        // Calculate ROI with all scenarios
        calculateROI() {
            const inputData = this.collectROIInputData();

            if (!inputData) {
                this.showNotification('Please fill in all required fields', 'error');
                return;
            }

            this.setLoadingState(true, '#calculate-roi', 'Calculating...');

            // Make AJAX request
            $.ajax({
                url: $('#ajaxurl').val(),
                type: 'POST',
                data: {
                    action: 'rtbcb_calculate_roi_test',
                    nonce: $('#rtbcb_roi_calculator_nonce').val(),
                    roi_data: inputData
                },
                success: (response) => {
                    if (response.success) {
                        this.displayROIResults(response.data);
                        this.showNotification('ROI calculated successfully!', 'success');
                    } else {
                        this.showNotification(response.data?.message || 'Calculation failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotification('Failed to calculate ROI: ' + error, 'error');
                },
                complete: () => {
                    this.setLoadingState(false, '#calculate-roi', 'Calculate ROI');
                }
            });
        },

        // Collect ROI input data
        collectROIInputData() {
            const data = {};

            // Collect all form inputs
            $('.rtbcb-roi-input-grid input, .rtbcb-roi-input-grid select').each(function() {
                const $input = $(this);
                const id = $input.attr('id');
                let value = $input.val();

                // Convert numeric inputs
                if ($input.attr('type') === 'number') {
                    value = parseFloat(value) || 0;
                }

                data[id] = value;
            });

            // Validate required fields
            const required = ['roi-annual-revenue', 'roi-treasury-staff', 'roi-avg-salary'];
            const missing = required.filter(field => !data[field] || data[field] <= 0);

            if (missing.length > 0) {
                return null;
            }

            return data;
        },

        // Display ROI calculation results
        displayROIResults(data) {
            this.roiData = data;

            // Update summary cards
            this.updateROISummaryCards(data);

            // Create charts
            this.createROICharts(data);

            // Update breakdown
            this.updateROIBreakdown(data);

            // Show results container
            $('#roi-results-container').show().addClass('rtbcb-fade-in');
            $('#export-roi-results').prop('disabled', false);

            // Scroll to results
            $('#roi-results-container')[0].scrollIntoView({ behavior: 'smooth' });
        },

        // Update ROI summary cards
        updateROISummaryCards(data) {
            const scenarios = ['conservative', 'realistic', 'optimistic'];

            scenarios.forEach(scenario => {
                const roiData = data[scenario];
                if (!roiData) return;

                $(`#roi-${scenario}-percent`).text(`${roiData.roi_percentage}%`);
                $(`#roi-${scenario}-amount`).text(this.formatCurrency(roiData.annual_benefit));
                $(`#roi-${scenario}-payback`).text(`${roiData.payback_months} months`);

                // Add color coding based on ROI
                const $card = $(`.rtbcb-roi-${scenario}`);
                $card.removeClass('roi-excellent roi-good roi-fair roi-poor');

                if (roiData.roi_percentage >= 300) $card.addClass('roi-excellent');
                else if (roiData.roi_percentage >= 200) $card.addClass('roi-good');
                else if (roiData.roi_percentage >= 100) $card.addClass('roi-fair');
                else $card.addClass('roi-poor');
            });
        },

        // Create ROI charts
        createROICharts(data) {
            this.createROIComparisonChart(data);
            this.createROIBreakdownChart(data);
        },

        // Create ROI comparison chart
        createROIComparisonChart(data) {
            const ctx = document.getElementById('roi-comparison-chart');
            if (!ctx) return;

            // Destroy existing chart
            if (this.roiCharts.comparison) {
                this.roiCharts.comparison.destroy();
            }

            const scenarios = ['conservative', 'realistic', 'optimistic'];
            const roiData = scenarios.map(scenario => data[scenario]?.roi_percentage || 0);
            const benefitData = scenarios.map(scenario => data[scenario]?.annual_benefit || 0);

            this.roiCharts.comparison = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Conservative', 'Realistic', 'Optimistic'],
                    datasets: [{
                        label: 'ROI %',
                        data: roiData,
                        backgroundColor: [
                            'rgba(255, 193, 7, 0.8)',  // amber
                            'rgba(40, 167, 69, 0.8)',  // green
                            'rgba(0, 123, 255, 0.8)'   // blue
                        ],
                        borderColor: [
                            'rgba(255, 193, 7, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(0, 123, 255, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                afterLabel: function(context) {
                                    const index = context.dataIndex;
                                    const benefit = benefitData[index];
                                    return 'Annual Benefit: ' + Dashboard.formatCurrency(benefit);
                                }
                            }
                        }
                    }
                }
            });
        },

        // Create ROI breakdown chart
        createROIBreakdownChart(data) {
            const ctx = document.getElementById('roi-breakdown-chart');
            if (!ctx) return;

            // Destroy existing chart
            if (this.roiCharts.breakdown) {
                this.roiCharts.breakdown.destroy();
            }

            const realistic = data.realistic;
            if (!realistic || !realistic.breakdown) return;

            const breakdown = realistic.breakdown;
            const benefits = [
                breakdown.labor_savings || 0,
                breakdown.fee_savings || 0,
                breakdown.error_reduction || 0,
                breakdown.efficiency_gains || 0
            ];

            const costs = [
                breakdown.software_cost || 0,
                breakdown.implementation_cost || 0,
                breakdown.training_cost || 0,
                breakdown.maintenance_cost || 0
            ];

            this.roiCharts.breakdown = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [
                        'Labor Savings',
                        'Fee Savings',
                        'Error Reduction',
                        'Efficiency Gains',
                        'Software Cost',
                        'Implementation',
                        'Training Cost',
                        'Maintenance'
                    ],
                    datasets: [{
                        data: [...benefits, ...costs.map(cost => -cost)],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',   // benefits - green shades
                            'rgba(32, 201, 151, 0.8)',
                            'rgba(13, 202, 240, 0.8)',
                            'rgba(102, 16, 242, 0.8)',
                            'rgba(220, 53, 69, 0.8)',   // costs - red shades
                            'rgba(253, 126, 20, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(108, 117, 125, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    return data.labels.map((label, index) => {
                                        const value = data.datasets[0].data[index];
                                        const absValue = Math.abs(value);
                                        const type = value >= 0 ? 'Benefit' : 'Cost';

                                        return {
                                            text: `${label}: ${Dashboard.formatCurrency(absValue)} (${type})`,
                                            fillStyle: data.datasets[0].backgroundColor[index],
                                            index: index
                                        };
                                    });
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = Math.abs(context.raw);
                                    const type = context.raw >= 0 ? 'Benefit' : 'Cost';
                                    return `${context.label}: ${Dashboard.formatCurrency(value)} (${type})`;
                                }
                            }
                        }
                    }
                }
            });
        },

        // Update ROI breakdown details
        updateROIBreakdown(data) {
            const realistic = data.realistic;
            if (!realistic || !realistic.breakdown) return;

            const breakdown = realistic.breakdown;

            // Benefits breakdown
            const benefitsHtml = `
            <div class="rtbcb-breakdown-item">
                <span class="label">Labor Cost Savings:</span>
                <span class="value">${this.formatCurrency(breakdown.labor_savings)}</span>
            </div>
            <div class="rtbcb-breakdown-item">
                <span class="label">Banking Fee Savings:</span>
                <span class="value">${this.formatCurrency(breakdown.fee_savings)}</span>
            </div>
            <div class="rtbcb-breakdown-item">
                <span class="label">Error Reduction:</span>
                <span class="value">${this.formatCurrency(breakdown.error_reduction)}</span>
            </div>
            <div class="rtbcb-breakdown-item">
                <span class="label">Efficiency Gains:</span>
                <span class="value">${this.formatCurrency(breakdown.efficiency_gains)}</span>
            </div>
            <div class="rtbcb-breakdown-item total">
                <span class="label">Total Annual Benefits:</span>
                <span class="value">${this.formatCurrency(breakdown.total_benefits)}</span>
            </div>
        `;

            // Costs breakdown
            const costsHtml = `
            <div class="rtbcb-breakdown-item">
                <span class="label">Software License:</span>
                <span class="value">${this.formatCurrency(breakdown.software_cost)}</span>
            </div>
            <div class="rtbcb-breakdown-item">
                <span class="label">Implementation:</span>
                <span class="value">${this.formatCurrency(breakdown.implementation_cost)}</span>
            </div>
            <div class="rtbcb-breakdown-item">
                <span class="label">Training:</span>
                <span class="value">${this.formatCurrency(breakdown.training_cost)}</span>
            </div>
            <div class="rtbcb-breakdown-item">
                <span class="label">Maintenance:</span>
                <span class="value">${this.formatCurrency(breakdown.maintenance_cost)}</span>
            </div>
            <div class="rtbcb-breakdown-item total">
                <span class="label">Total Annual Costs:</span>
                <span class="value">${this.formatCurrency(breakdown.total_costs)}</span>
            </div>
        `;

            $('#roi-benefits-breakdown').html(benefitsHtml);
            $('#roi-costs-breakdown').html(costsHtml);

            // Update assumptions
            if (realistic.assumptions) {
                const assumptionsHtml = realistic.assumptions.map(assumption =>
                    `<div class="rtbcb-assumption-item">${assumption}</div>`
                ).join('');
                $('#roi-assumptions-list').html(assumptionsHtml);
            }
        },

        // Toggle ROI details visibility
        toggleROIDetails() {
            const $details = $('#roi-detailed-breakdown');
            const $button = $('#toggle-roi-details');

            $details.slideToggle();

            if ($details.is(':visible')) {
                $button.find('span').text('Hide Details');
            } else {
                $button.find('span').text('Show Details');
            }
        },

        // Copy ROI summary
        copyROISummary() {
            if (!this.roiData) {
                this.showNotification('No ROI data to copy', 'warning');
                return;
            }

            const summary = this.generateROISummaryText(this.roiData);

            this.copyToClipboard(summary)
                .then(() => {
                    this.showNotification('ROI summary copied to clipboard', 'success');
                })
                .catch(() => {
                    this.showNotification('Failed to copy to clipboard', 'error');
                });
        },

        // Generate ROI summary text
        generateROISummaryText(data) {
            let summary = 'ROI CALCULATION SUMMARY\n';
            summary += '=======================\n\n';

            const scenarios = ['conservative', 'realistic', 'optimistic'];

            scenarios.forEach(scenario => {
                const scenarioData = data[scenario];
                if (scenarioData) {
                    summary += `${scenario.toUpperCase()} SCENARIO:\n`;
                    summary += `- ROI: ${scenarioData.roi_percentage}%\n`;
                    summary += `- Annual Benefit: ${this.formatCurrency(scenarioData.annual_benefit)}\n`;
                    summary += `- Payback Period: ${scenarioData.payback_months} months\n\n`;
                }
            });

            if (data.realistic && data.realistic.breakdown) {
                const breakdown = data.realistic.breakdown;
                summary += 'KEY BENEFITS:\n';
                summary += `- Labor Savings: ${this.formatCurrency(breakdown.labor_savings)}\n`;
                summary += `- Fee Savings: ${this.formatCurrency(breakdown.fee_savings)}\n`;
                summary += `- Error Reduction: ${this.formatCurrency(breakdown.error_reduction)}\n`;
                summary += `- Efficiency Gains: ${this.formatCurrency(breakdown.efficiency_gains)}\n\n`;

                summary += 'TOTAL INVESTMENT:\n';
                summary += `- Annual Cost: ${this.formatCurrency(breakdown.total_costs)}\n`;
                summary += `- Net Annual Benefit: ${this.formatCurrency(breakdown.total_benefits - breakdown.total_costs)}\n`;
            }

            summary += `\nGenerated: ${new Date().toLocaleString()}\n`;

            return summary;
        },

        // Run sensitivity analysis
        runSensitivityAnalysis() {
            if (!this.roiData) {
                this.showNotification('Please calculate ROI first', 'warning');
                return;
            }

            this.setLoadingState(true, '#run-sensitivity-analysis', 'Analyzing...');

            const inputData = this.collectROIInputData();

            $.ajax({
                url: $('#ajaxurl').val(),
                type: 'POST',
                data: {
                    action: 'rtbcb_sensitivity_analysis',
                    nonce: $('#rtbcb_roi_calculator_nonce').val(),
                    roi_data: inputData,
                    base_roi: this.roiData
                },
                success: (response) => {
                    if (response.success) {
                        this.displaySensitivityAnalysis(response.data);
                        this.showNotification('Sensitivity analysis complete!', 'success');
                    } else {
                        this.showNotification(response.data?.message || 'Analysis failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotification('Failed to run sensitivity analysis: ' + error, 'error');
                },
                complete: () => {
                    this.setLoadingState(false, '#run-sensitivity-analysis', 'Sensitivity Analysis');
                }
            });
        },

        // Display sensitivity analysis results
        displaySensitivityAnalysis(data) {
            this.createSensitivityChart(data);
            this.updateSensitivityTable(data);

            $('#sensitivity-analysis-container').show().addClass('rtbcb-fade-in');
            $('#sensitivity-analysis-container')[0].scrollIntoView({ behavior: 'smooth' });
        },

        // Create sensitivity chart
        createSensitivityChart(data) {
            const ctx = document.getElementById('sensitivity-analysis-chart');
            if (!ctx) return;

            // Destroy existing chart
            if (this.roiCharts.sensitivity) {
                this.roiCharts.sensitivity.destroy();
            }

            const variables = Object.keys(data.sensitivity);
            const datasets = ['-20%', '-10%', '+10%', '+20%'].map((change, index) => {
                return {
                    label: change,
                    data: variables.map(variable => data.sensitivity[variable][change] || 0),
                    backgroundColor: [
                        'rgba(220, 53, 69, 0.6)',   // -20%
                        'rgba(255, 193, 7, 0.6)',   // -10%
                        'rgba(40, 167, 69, 0.6)',   // +10%
                        'rgba(0, 123, 255, 0.6)'    // +20%
                    ][index],
                    borderColor: [
                        'rgba(220, 53, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(40, 167, 69, 1)',
                        'rgba(0, 123, 255, 1)'
                    ][index],
                    borderWidth: 2
                };
            });

            this.roiCharts.sensitivity = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: variables.map(v => this.formatVariableName(v)),
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.raw}% ROI`;
                                }
                            }
                        }
                    }
                }
            });
        },

        // Update sensitivity table
        updateSensitivityTable(data) {
            const tbody = $('#sensitivity-table-body');
            tbody.empty();

            Object.keys(data.sensitivity).forEach(variable => {
                const variableData = data.sensitivity[variable];
                const row = `
                <tr>
                    <td>${this.formatVariableName(variable)}</td>
                    <td>${variableData.base_value}</td>
                    <td>${variableData['-20%']}%</td>
                    <td>${variableData['-10%']}%</td>
                    <td><strong>${variableData.base}%</strong></td>
                    <td>${variableData['+10%']}%</td>
                    <td>${variableData['+20%']}%</td>
                    <td><span class="sensitivity-${this.getSensitivityClass(variableData.sensitivity)}">${variableData.sensitivity}</span></td>
                </tr>
            `;
                tbody.append(row);
            });
        },

        // Compare scenarios
        compareScenarios() {
            this.setLoadingState(true, '#compare-scenarios', 'Comparing...');

            const scenarios = ['small-company', 'medium-company', 'large-company'];
            const comparisonData = [];

            let completed = 0;

            scenarios.forEach(scenario => {
                // Load scenario data
                const scenarioInputs = { ...this.roiScenarios[scenario] };
                delete scenarioInputs.name;

                // Convert to expected format
                Object.keys(scenarioInputs).forEach(key => {
                    if (typeof scenarioInputs[key] === 'number') {
                        // Already a number, keep as is
                    } else if (typeof scenarioInputs[key] === 'string' && !isNaN(scenarioInputs[key])) {
                        scenarioInputs[key] = parseFloat(scenarioInputs[key]);
                    }
                });

                $.ajax({
                    url: $('#ajaxurl').val(),
                    type: 'POST',
                    data: {
                        action: 'rtbcb_calculate_roi_test',
                        nonce: $('#rtbcb_roi_calculator_nonce').val(),
                        roi_data: scenarioInputs
                    },
                    success: (response) => {
                        if (response.success) {
                            comparisonData.push({
                                scenario: scenario,
                                name: this.roiScenarios[scenario].name,
                                data: response.data,
                                inputs: scenarioInputs
                            });
                        }

                        completed++;
                        if (completed === scenarios.length) {
                            this.displayScenarioComparison(comparisonData);
                            this.setLoadingState(false, '#compare-scenarios', 'Compare Scenarios');
                        }
                    },
                    error: () => {
                        completed++;
                        if (completed === scenarios.length) {
                            this.setLoadingState(false, '#compare-scenarios', 'Compare Scenarios');
                        }
                    }
                });
            });
        },

        // Display scenario comparison
        displayScenarioComparison(comparisonData) {
            this.createScenarioCharts(comparisonData);
            this.updateScenarioTable(comparisonData);

            $('#scenario-comparison-container').show().addClass('rtbcb-fade-in');
            $('#scenario-comparison-container')[0].scrollIntoView({ behavior: 'smooth' });

            this.showNotification('Scenario comparison complete!', 'success');
        },

        // Create scenario comparison charts
        createScenarioCharts(data) {
            // ROI by company size chart
            const sizeCtx = document.getElementById('scenario-size-chart');
            if (sizeCtx) {
                if (this.roiCharts.scenarioSize) {
                    this.roiCharts.scenarioSize.destroy();
                }

                this.roiCharts.scenarioSize = new Chart(sizeCtx, {
                    type: 'bar',
                    data: {
                        labels: data.map(item => item.name),
                        datasets: [{
                            label: 'ROI %',
                            data: data.map(item => item.data.realistic?.roi_percentage || 0),
                            backgroundColor: 'rgba(40, 167, 69, 0.8)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }

            // Payback period chart
            const paybackCtx = document.getElementById('scenario-payback-chart');
            if (paybackCtx) {
                if (this.roiCharts.scenarioPayback) {
                    this.roiCharts.scenarioPayback.destroy();
                }

                this.roiCharts.scenarioPayback = new Chart(paybackCtx, {
                    type: 'bar',
                    data: {
                        labels: data.map(item => item.name),
                        datasets: [{
                            label: 'Payback (Months)',
                            data: data.map(item => item.data.realistic?.payback_months || 0),
                            backgroundColor: 'rgba(0, 123, 255, 0.8)',
                            borderColor: 'rgba(0, 123, 255, 1)',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return value + ' months';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        },

        // Update scenario comparison table
        updateScenarioTable(data) {
            const tbody = $('#scenario-comparison-table-body');
            tbody.empty();

            data.forEach(item => {
                const realistic = item.data.realistic;
                if (!realistic) return;

                const recommendation = this.getROIRecommendation(realistic.roi_percentage);

                const row = `
                <tr>
                    <td><strong>${item.name}</strong></td>
                    <td>${this.formatCompanySize(item.inputs['roi-company-size'])}</td>
                    <td>${this.formatCurrency(item.inputs['roi-annual-revenue'], 0)}</td>
                    <td><span class="roi-${this.getROIClass(realistic.roi_percentage)}">${realistic.roi_percentage}%</span></td>
                    <td>${this.formatCurrency(realistic.annual_benefit)}</td>
                    <td>${realistic.payback_months}</td>
                    <td><span class="recommendation-${recommendation.class}">${recommendation.text}</span></td>
                </tr>
            `;
                tbody.append(row);
            });
        },

        // Export ROI results
        exportROIResults() {
            if (!this.roiData) {
                this.showNotification('No results to export', 'warning');
                return;
            }

            const exportData = {
                roi_results: this.roiData,
                input_data: this.collectROIInputData(),
                generated_at: new Date().toISOString(),
                scenarios: ['conservative', 'realistic', 'optimistic'].map(scenario => ({
                    scenario: scenario,
                    roi_percentage: this.roiData[scenario]?.roi_percentage,
                    annual_benefit: this.roiData[scenario]?.annual_benefit,
                    payback_months: this.roiData[scenario]?.payback_months
                })),
                summary_text: this.generateROISummaryText(this.roiData)
            };

            this.downloadJSON(exportData, `roi_analysis_${Date.now()}.json`);
            this.showNotification('ROI results exported successfully', 'success');
        },

        // Utility functions for ROI module
        formatCurrency(amount, decimals = 2) {
            if (!amount && amount !== 0) return '$0';

            const num = parseFloat(amount);
            if (isNaN(num)) return '$0';

            if (num >= 1000000) {
                return '$' + (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return '$' + (num / 1000).toFixed(0) + 'K';
            } else {
                return '$' + num.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
            }
        },

        formatVariableName(variable) {
            const names = {
                'roi-treasury-staff': 'Treasury Staff',
                'roi-avg-salary': 'Average Salary',
                'roi-hours-reconciliation': 'Reconciliation Hours',
                'roi-monthly-bank-fees': 'Bank Fees',
                'roi-error-frequency': 'Error Frequency',
                'roi-avg-error-cost': 'Error Cost'
            };
            return names[variable] || variable.replace('roi-', '').replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        formatCompanySize(size) {
            const sizes = {
                'startup': 'Startup',
                'small': 'Small',
                'medium': 'Medium',
                'large': 'Large',
                'enterprise': 'Enterprise'
            };
            return sizes[size] || size;
        },

        getSensitivityClass(sensitivity) {
            if (sensitivity >= 2.0) return 'high';
            if (sensitivity >= 1.0) return 'medium';
            return 'low';
        },

        getROIClass(roi) {
            if (roi >= 300) return 'excellent';
            if (roi >= 200) return 'good';
            if (roi >= 100) return 'fair';
            return 'poor';
        },

        getROIRecommendation(roi) {
            if (roi >= 300) return { text: 'Excellent Investment', class: 'excellent' };
            if (roi >= 200) return { text: 'Strong ROI', class: 'good' };
            if (roi >= 100) return { text: 'Good Return', class: 'fair' };
            if (roi >= 50) return { text: 'Moderate Return', class: 'fair' };
            return { text: 'Consider Alternatives', class: 'poor' };
        }
    });

    $(document).ready(function() {
        if (typeof Dashboard.initROICalculator === 'function') {
            Dashboard.initROICalculator();
        }
    });

    window.RTBCBDashboard = Dashboard;
})(jQuery);

