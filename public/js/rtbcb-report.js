/**
 * Enhanced Report JavaScript with Chart.js Integration
 * Handles interactive dashboard features, charts, and collapsible sections
 */

// Default GPT-5 token limits synchronized with PHP config.
const RTBCB_GPT5_MIN_TOKENS = 256;
const RTBCB_GPT5_MAX_TOKENS = 8000;

// Ensure Chart.js date adapter is registered in Node environments.
if ( typeof require === 'function' ) {
    try {
        require( './public/js/chartjs-adapter-date-fns.bundle.min.js' );
    } catch ( e ) {
        // Adapter not loaded in tests; ignore.
    }
}

if ( typeof document !== 'undefined' && typeof document.addEventListener === 'function' ) {
    document.addEventListener( 'DOMContentLoaded', function() {
        console.log( 'RTBCB Report: Initializing enhanced dashboard' );

        // Initialize all interactive features
        initializeCharts();
        initializeSectionToggles();
        initializeInteractiveMetrics();
        initializeResponsiveFeatures();
        initializeAIHighlights();

        // Add loading animation completion
        if ( typeof document.querySelector === 'function' ) {
            document.querySelector( '.rtbcb-enhanced-report' )?.classList.add( 'loaded' );
        }
    } );
}

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
 * Create a mini chart for an individual scenario
 */
function createScenarioMiniChart(canvas, scenario) {
    const chartData = window.rtbcbChartData || generateFallbackChartData();
    if (!chartData || !chartData.labels || !chartData.datasets) return;

    const scenarioIndex = { conservative: 0, base: 1, optimistic: 2 }[scenario];
    const dataset = chartData.datasets[scenarioIndex];
    if (!dataset) return;

    try {
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: dataset.label,
                    data: dataset.data,
                    backgroundColor: dataset.backgroundColor
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { display: false },
                    y: { display: false }
                }
            }
        });
    } catch (error) {
        console.error('RTBCB: Error initializing scenario chart:', error);
    }
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
function initializeSectionToggles(doc = document) {
    if ( typeof doc === 'undefined' || typeof doc.querySelectorAll !== 'function' ) {
        return;
    }
    doc.querySelectorAll('.rtbcb-section-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();

            const targetId = this.getAttribute('data-target');
            const content = doc.getElementById(targetId);
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
    if ( typeof document === 'undefined' || typeof document.querySelectorAll !== 'function' ) {
        return;
    }
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
    if ( typeof document === 'undefined' || typeof document.querySelector !== 'function' ) {
        return;
    }

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

async function generateProfessionalReport(businessContext, onProgress = () => {}) {
    const templateResponse = await fetch(rtbcbReport.template_url);
    if (!templateResponse.ok) {
        throw new Error('Failed to load template');
    }
    const templateHtml = await templateResponse.text();

    const minTokens = parseInt(rtbcbReport.min_output_tokens, 10) || 0;
    const maxTokens = parseInt(rtbcbReport.max_output_tokens, 10) || 0;
    let tokenEstimate = Math.max(minTokens, 3000);
    if (maxTokens > 0) {
        tokenEstimate = Math.min(tokenEstimate, maxTokens);
    }

    const requestBody = {
        model: rtbcbReport.report_model,
        context: businessContext,
        max_output_tokens: tokenEstimate
    };

    const unsupportedTemps = rtbcbReport.model_capabilities?.temperature?.unsupported || [];
    if (!unsupportedTemps.includes(rtbcbReport.report_model)) {
        requestBody.temperature = 0.7;
    }

    const formData = new FormData();
    formData.append('action', 'rtbcb_generate_report');
    if (rtbcbReport.nonce) {
        formData.append('rtbcb_nonce', rtbcbReport.nonce);
    }
    formData.append('body', JSON.stringify(requestBody));

    const response = await fetch(rtbcbReport.ajax_url, {
        method: 'POST',
        body: formData
    });

    if (!response.ok) {
        throw new Error(response.statusText || 'Report generation failed');
    }

    let reportContent = '';
    if (response.body) {
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        while (true) {
            const { value, done } = await reader.read();
            if (done) break;
            const chunk = decoder.decode(value, { stream: true });
            reportContent += chunk;
            onProgress(reportContent);
        }
    } else {
        reportContent = await response.text();
        onProgress(reportContent);
    }

    const safeContext = typeof DOMPurify !== 'undefined'
        ? DOMPurify.sanitize(businessContext)
        : businessContext;

    return templateHtml
        .replace('{{BUSINESS_CONTEXT}}', safeContext)
        .replace('<!-- GENERATE THE REPORT CONTENT HERE FOLLOWING THIS STRUCTURE: -->', reportContent);
}

// Report generation utilities retained for backward compatibility
function sanitizeReportHTML(htmlContent) {
    // Sanitize OpenAI-generated HTML before embedding.
    // Explicitly whitelist tags and attributes required for reports.
    const allowedTags = [
        'a', 'p', 'br', 'strong', 'em', 'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span',
        'table', 'thead', 'tbody', 'tr', 'th', 'td'
    ];
    const allowedAttr = [ 'href', 'title', 'target', 'rel', 'style' ];
    return typeof DOMPurify !== 'undefined'
        ? DOMPurify.sanitize(htmlContent, { ALLOWED_TAGS: allowedTags, ALLOWED_ATTR: allowedAttr })
        : htmlContent;
}

function displayReport(htmlContent) {
    const iframe = document.createElement('iframe');
    iframe.style.width = '100%';
    iframe.style.height = '800px';
    iframe.style.border = '1px solid #ddd';
    iframe.srcdoc = sanitizeReportHTML(htmlContent);
    iframe.addEventListener('load', () => {
        if ( iframe.contentDocument ) {
            initializeAIHighlights( iframe.contentDocument );
            initializeSectionToggles( iframe.contentDocument );
        }
    });
    document.getElementById('report-container').appendChild(iframe);
}


async function generateAndDisplayReport(businessContext) {
    const loadingElement = document.getElementById('loading');
    const errorElement = document.getElementById('error');
    const reportContainer = document.getElementById('report-container');

    if (loadingElement) {
        loadingElement.style.display = 'block';
    }
    if (errorElement) {
        errorElement.style.display = 'none';
    }
    if (reportContainer) {
        reportContainer.innerHTML = '';
    }

    try {
        const htmlReport = await generateProfessionalReport(businessContext, partial => {
            if (reportContainer) {
                reportContainer.textContent = partial;
            }
        });

        if (!htmlReport || !htmlReport.trim()) {
            throw new Error('Empty response from API');
        }

        if (!htmlReport.includes('<html')) {
            console.warn('RTBCB: HTML fragment received from API');
        }

        const safeReport = sanitizeReportHTML(htmlReport);
        if (!safeReport || !safeReport.trim()) {
            console.error('RTBCB: Sanitized report is empty or invalid');
            if (errorElement) {
                errorElement.textContent = 'Error: Malformed report content.';
                errorElement.style.display = 'block';
            } else {
                console.error('RTBCB: Malformed report content.');
            }
            return;
        }

        if (reportContainer) {
            reportContainer.innerHTML = '';
        }
        displayReport(safeReport);
    } catch (error) {
        if (errorElement) {
            errorElement.textContent = 'Error: ' + error.message;
            errorElement.style.display = 'block';
        } else {
            console.error('RTBCB: ' + error.message);
        }
    } finally {
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }
}

function initializeAIHighlights(doc = document) {
    if ( typeof doc.getElementById !== 'function' || typeof doc.querySelector !== 'function' ) {
        return;
    }

    const aiToggle = doc.getElementById( 'rtbcb-ai-toggle' );
    const reportContainer = doc.querySelector( '.rtbcb-enhanced-report' );

    if ( ! aiToggle || ! reportContainer ) {
        return;
    }

    aiToggle.addEventListener( 'change', () => {
        reportContainer.classList.toggle( 'show-ai-highlights', aiToggle.checked );
    } );

    addHighlight( doc, '.rtbcb-company-intelligence', 'This section was enriched by AI.' );
}

function addHighlight(doc, selector, tooltipText) {
    const element = doc.querySelector(selector);
    if ( element ) {
        element.classList.add('ai-highlight');
        const tooltip = doc.createElement('div');
        tooltip.className = 'ai-tooltip';
        tooltip.textContent = tooltipText;
        element.appendChild(tooltip);
    }
}

// No export functions are available; users should access the report online.
