/**
 * Enhanced Report JavaScript with Chart.js Integration
 * Handles interactive dashboard features, charts, and collapsible sections
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('RTBCB Report: Initializing enhanced dashboard');
    
    // Initialize all interactive features
    initializeCharts();
    initializeSectionToggles();
    initializeInteractiveMetrics();
    initializeResponsiveFeatures();
    
    // Add loading animation completion
    document.querySelector('.rtbcb-enhanced-report')?.classList.add('loaded');
});

/**
 * Initialize Chart.js visualizations
 */
function initializeCharts() {
    if (typeof Chart === 'undefined') {
        console.warn('RTBCB: Chart.js not loaded, skipping chart initialization');
        return;
    }
    
    // Initialize ROI Chart
    const roiChart = initializeROIChart();
    
    // Initialize comparison charts if multiple scenarios exist
    initializeComparisonCharts();
    
    // Initialize sensitivity analysis chart
    initializeSensitivityChart();
    
    console.log('RTBCB: Charts initialized successfully');
}

/**
 * Initialize main ROI scenario chart
 */
function initializeROIChart() {
    const ctx = document.getElementById('rtbcb-roi-chart');
    if (!ctx) {
        console.log('RTBCB: ROI chart canvas not found');
        return null;
    }
    
    // Get chart data from global variables or generate fallback
    const chartData = window.rtbcbChartData || generateFallbackChartData();
    
    if (!chartData || !chartData.datasets || chartData.datasets.length === 0) {
        console.warn('RTBCB: No chart data available');
        return null;
    }
    
    try {
        const chart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'ROI Analysis by Component',
                        font: {
                            size: 16,
                            weight: 'bold'
                        },
                        padding: 20
                    },
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#333',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + 
                                       new Intl.NumberFormat().format(context.raw);
                            },
                            footer: function(tooltipItems) {
                                if (tooltipItems.length > 0) {
                                    const dataIndex = tooltipItems[0].dataIndex;
                                    const componentInfo = getComponentInfo(dataIndex);
                                    return componentInfo ? '\n' + componentInfo : '';
                                }
                                return '';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [5, 5],
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + new Intl.NumberFormat().format(value);
                            },
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeInOutQuart'
                }
            }
        });
        
        // Add click handler for drill-down functionality
        ctx.onclick = function(evt) {
            const points = chart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
            if (points.length) {
                const firstPoint = points[0];
                handleChartClick(firstPoint.datasetIndex, firstPoint.index);
            }
        };
        
        return chart;
        
    } catch (error) {
        console.error('RTBCB: Error initializing ROI chart:', error);
        showChartError(ctx, 'Error loading ROI chart');
        return null;
    }
}

/**
 * Initialize comparison charts for multiple scenarios
 */
function initializeComparisonCharts() {
    const comparisonContainer = document.querySelector('.rtbcb-scenario-comparison');
    if (!comparisonContainer) return;

    // Create mini charts for each scenario
    const scenarios = ['conservative', 'base', 'optimistic'];
    scenarios.forEach(scenario => {
        const canvas = document.querySelector(`#rtbcb-${scenario}-chart`);
        if (canvas) {
            createScenarioMiniChart(canvas, scenario);
        }
    });
}

/**
 * Render mini comparison chart for a scenario card
 */
function createScenarioMiniChart(canvas, scenario) {
    const card = document.querySelector(`.rtbcb-scenario-card.${scenario}`);
    if (!card) {
        return;
    }

    const extract = metric => {
        const el = card.querySelector(`[data-metric="${metric}"]`);
        return el ? parseFloat(el.textContent.replace(/[$,]/g, '')) || 0 : 0;
    };

    const colors = {
        conservative: 'rgba(239, 68, 68, 0.8)',
        base: 'rgba(59, 130, 246, 0.8)',
        optimistic: 'rgba(16, 185, 129, 0.8)'
    };

    const data = {
        labels: ['Labor Savings', 'Fee Reduction', 'Error Prevention', 'Total Benefit'],
        datasets: [{
            data: [
                extract('labor_savings'),
                extract('fee_savings'),
                extract('error_reduction'),
                extract('total_benefit')
            ],
            backgroundColor: colors[scenario] || 'rgba(59, 130, 246, 0.8)'
        }]
    };

    new Chart(canvas, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + new Intl.NumberFormat().format(context.raw);
                        }
                    }
                }
            },
            scales: {
                x: { display: false },
                y: { display: false }
            }
        }
    });
}

/**
 * Initialize sensitivity analysis chart
 */
function initializeSensitivityChart() {
    const ctx = document.getElementById('rtbcb-sensitivity-chart');
    if (!ctx) return;
    
    const sensitivityData = window.rtbcbSensitivityData || generateFallbackSensitivityData();
    
    try {
        new Chart(ctx, {
            type: 'bar',
            data: sensitivityData,
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Sensitivity Analysis - Impact on ROI',
                        font: { size: 14, weight: 'bold' }
                    },
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    y: {
                        ticks: {
                            font: { size: 10 }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('RTBCB: Error initializing sensitivity chart:', error);
    }
}

/**
 * Initialize section toggle functionality
 */
function initializeSectionToggles() {
    document.querySelectorAll('.rtbcb-section-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('data-target');
            const content = document.getElementById(targetId);
            const arrow = this.querySelector('.rtbcb-toggle-arrow');
            const text = this.querySelector('.rtbcb-toggle-text');
            const section = this.closest('.rtbcb-section-enhanced');
            
            if (content) {
                const isVisible = content.style.display !== 'none';
                
                // Toggle content visibility with animation
                if (isVisible) {
                    content.style.maxHeight = content.scrollHeight + 'px';
                    content.offsetHeight; // Force reflow
                    content.style.maxHeight = '0px';
                    content.style.opacity = '0';
                    
                    setTimeout(() => {
                        content.style.display = 'none';
                    }, 300);
                } else {
                    content.style.display = 'block';
                    content.style.maxHeight = '0px';
                    content.style.opacity = '0';
                    content.offsetHeight; // Force reflow
                    content.style.maxHeight = content.scrollHeight + 'px';
                    content.style.opacity = '1';
                    
                    setTimeout(() => {
                        content.style.maxHeight = 'none';
                    }, 300);
                }
                
                // Update toggle button
                if (arrow) {
                    arrow.textContent = isVisible ? '▼' : '▲';
                }
                if (text) {
                    text.textContent = isVisible ? 'Expand' : 'Collapse';
                }
                
                // Update section state
                if (section) {
                    section.classList.toggle('collapsed', isVisible);
                }
                
                // Track analytics
                trackSectionToggle(targetId, !isVisible);
            }
        });
    });
}

/**
 * Initialize interactive metric cards
 */
function initializeInteractiveMetrics() {
    document.querySelectorAll('.rtbcb-metric-card').forEach(card => {
        // Add hover effects and click handlers
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
        });
        
        card.addEventListener('click', function() {
            expandMetricDetails(this);
        });
    });
    
    // Initialize value driver animations
    initializeValueDriverAnimations();
    
    // Initialize progress bars if present
    initializeProgressBars();
}

/**
 * Initialize responsive features
 */
function initializeResponsiveFeatures() {
    // Handle mobile navigation
    const mobileToggle = document.querySelector('.rtbcb-mobile-toggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', toggleMobileMenu);
    }
    
    // Handle window resize for charts
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (window.Chart) {
                Chart.helpers.each(Chart.instances, function(instance) {
                    instance.resize();
                });
            }
        }, 250);
    });
    
    // Initialize print styles
    window.addEventListener('beforeprint', function() {
        document.body.classList.add('rtbcb-printing');
    });
    
    window.addEventListener('afterprint', function() {
        document.body.classList.remove('rtbcb-printing');
    });
}

/**
 * Utility functions
 */

function generateFallbackChartData() {
    // Extract data from DOM if available
    const conservativeCard = document.querySelector('.rtbcb-scenario-card.conservative');
    const baseCard = document.querySelector('.rtbcb-scenario-card.base');
    const optimisticCard = document.querySelector('.rtbcb-scenario-card.optimistic');
    
    if (!conservativeCard || !baseCard || !optimisticCard) {
        console.warn('RTBCB: Scenario cards not found, using minimal fallback data');
        return {
            labels: ['Labor Savings', 'Fee Reduction', 'Error Prevention', 'Total Benefit'],
            datasets: [{
                label: 'Estimated Benefits',
                data: [50000, 20000, 30000, 100000],
                backgroundColor: 'rgba(59, 130, 246, 0.8)'
            }]
        };
    }
    
    // Extract values from DOM
    const extractValue = (card, selector) => {
        const element = card.querySelector(selector);
        if (element) {
            const text = element.textContent.replace(/[$,]/g, '');
            return parseFloat(text) || 0;
        }
        return 0;
    };
    
    return {
        labels: ['Labor Savings', 'Fee Reduction', 'Error Prevention', 'Total Benefit'],
        datasets: [
            {
                label: 'Conservative',
                data: [
                    extractValue(conservativeCard, '[data-metric="labor_savings"]'),
                    extractValue(conservativeCard, '[data-metric="fee_savings"]'),
                    extractValue(conservativeCard, '[data-metric="error_reduction"]'),
                    extractValue(conservativeCard, '[data-metric="total_benefit"]')
                ],
                backgroundColor: 'rgba(239, 68, 68, 0.8)',
                borderColor: 'rgba(239, 68, 68, 1)',
                borderWidth: 2
            },
            {
                label: 'Base Case',
                data: [
                    extractValue(baseCard, '[data-metric="labor_savings"]'),
                    extractValue(baseCard, '[data-metric="fee_savings"]'),
                    extractValue(baseCard, '[data-metric="error_reduction"]'),
                    extractValue(baseCard, '[data-metric="total_benefit"]')
                ],
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2
            },
            {
                label: 'Optimistic',
                data: [
                    extractValue(optimisticCard, '[data-metric="labor_savings"]'),
                    extractValue(optimisticCard, '[data-metric="fee_savings"]'),
                    extractValue(optimisticCard, '[data-metric="error_reduction"]'),
                    extractValue(optimisticCard, '[data-metric="total_benefit"]')
                ],
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2
            }
        ]
    };
}

function generateFallbackSensitivityData() {
    return {
        labels: ['Implementation Delay', 'Adoption Challenges', 'Technology Evolution', 'Market Conditions'],
        datasets: [{
            label: 'Impact %',
            data: [-15, -25, 10, 5],
            backgroundColor: function(context) {
                const value = context.parsed.x;
                return value < 0 ? 'rgba(239, 68, 68, 0.8)' : 'rgba(16, 185, 129, 0.8)';
            }
        }]
    };
}

function getComponentInfo(dataIndex) {
    const info = [
        'Automated processes reduce manual labor costs',
        'Optimized banking relationships lower transaction fees',
        'Reduced errors prevent costly mistakes and rework',
        'Combined benefits drive strong ROI'
    ];
    return info[dataIndex] || '';
}

function showChartError(canvas, message) {
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#666';
    ctx.font = '14px Arial';
    ctx.textAlign = 'center';
    ctx.fillText(message, canvas.width / 2, canvas.height / 2);
}

function handleChartClick(datasetIndex, index) {
    console.log('Chart clicked:', datasetIndex, index);
    // Implement drill-down functionality here
}

function expandMetricDetails(card) {
    // Toggle expanded state
    card.classList.toggle('expanded');
    
    // Add detailed information if expanded
    if (card.classList.contains('expanded')) {
        showMetricDetails(card);
    } else {
        hideMetricDetails(card);
    }
}

function showMetricDetails(card) {
    const existing = card.querySelector('.rtbcb-metric-details');
    if (existing) return;
    
    const details = document.createElement('div');
    details.className = 'rtbcb-metric-details';
    details.innerHTML = '<p>Click for detailed breakdown and analysis</p>';
    card.appendChild(details);
}

function hideMetricDetails(card) {
    const details = card.querySelector('.rtbcb-metric-details');
    if (details) {
        details.remove();
    }
}

function initializeValueDriverAnimations() {
    const drivers = document.querySelectorAll('.rtbcb-value-driver-enhanced');
    drivers.forEach((driver, index) => {
        setTimeout(() => {
            driver.style.opacity = '1';
            driver.style.transform = 'translateX(0)';
        }, index * 200);
    });
}

function initializeProgressBars() {
    const progressBars = document.querySelectorAll('.rtbcb-progress-bar');
    progressBars.forEach(bar => {
        const progress = bar.dataset.progress || '0';
        setTimeout(() => {
            bar.style.width = progress + '%';
        }, 500);
    });
}

function trackSectionToggle(sectionId, expanded) {
    // Analytics tracking
    if (typeof gtag !== 'undefined') {
        gtag('event', 'section_toggle', {
            'section_id': sectionId,
            'expanded': expanded
        });
    }
}

function toggleMobileMenu() {
    const menu = document.querySelector('.rtbcb-mobile-menu');
    if (menu) {
        menu.classList.toggle('open');
    }
}

// Export PDF functionality
function rtbcbExportPDF() {
    // Trigger browser print dialog
    window.print();
}

// Make functions available globally
window.rtbcbExportPDF = rtbcbExportPDF;
