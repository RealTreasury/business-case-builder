/* Enhanced JavaScript for Real Treasury Business Case Builder plugin */

class BusinessCaseBuilder {
    constructor() {
        this.form = document.getElementById('rtbcbForm');
        this.results = document.getElementById('rtbcbResults');
        this.submitBtn = this.form?.querySelector('.rtbcb-submit-btn');
        this.progressSteps = [];
        this.init();
    }

    init() {
        if (this.form) {
            this.form.addEventListener('submit', this.handleSubmit.bind(this));
            this.initFormValidation();
            this.initProgressTracking();
        }
    }

    initFormValidation() {
        const inputs = this.form.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', this.validateField.bind(this, input));
            input.addEventListener('input', this.clearFieldError.bind(this, input));
        });
    }

    initProgressTracking() {
        this.progressSteps = [
            'Calculating ROI scenarios...',
            'Analyzing your requirements...',
            'Determining best solution category...',
            'Researching vendor data...',
            'Generating business narrative...',
            'Creating your custom report...'
        ];
    }

    validateField(field) {
        const errorElement = field.parentElement.querySelector('.rtbcb-field-error');
        
        if (field.hasAttribute('required') && !field.value.trim()) {
            this.showFieldError(field, 'This field is required.');
            return false;
        }

        if (field.type === 'email' && field.value && !this.isValidEmail(field.value)) {
            this.showFieldError(field, 'Please enter a valid email address.');
            return false;
        }

        if (field.type === 'number' && field.value) {
            const min = parseFloat(field.getAttribute('min'));
            const max = parseFloat(field.getAttribute('max'));
            const value = parseFloat(field.value);

            if (!isNaN(min) && value < min) {
                this.showFieldError(field, `Value must be at least ${min}.`);
                return false;
            }

            if (!isNaN(max) && value > max) {
                this.showFieldError(field, `Value must be no more than ${max}.`);
                return false;
            }
        }

        this.clearFieldError(field);
        return true;
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        
        const errorElement = document.createElement('div');
        errorElement.className = 'rtbcb-field-error';
        errorElement.textContent = message;
        
        field.parentElement.appendChild(errorElement);
        field.classList.add('rtbcb-field-invalid');
    }

    clearFieldError(field) {
        const errorElement = field.parentElement.querySelector('.rtbcb-field-error');
        if (errorElement) {
            errorElement.remove();
        }
        field.classList.remove('rtbcb-field-invalid');
    }

    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    validateForm() {
        const inputs = this.form.querySelectorAll('input[required], select[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        // Validate at least one pain point is selected
        const painPoints = this.form.querySelectorAll('input[name="pain_points[]"]:checked');
        if (painPoints.length === 0) {
            this.showError('Please select at least one pain point.');
            isValid = false;
        }

        return isValid;
    }

    async handleSubmit(e) {
        e.preventDefault();

        if (!this.validateForm()) {
            this.scrollToFirstError();
            return;
        }

        const originalText = this.submitBtn.textContent;

        try {
            this.showProgressIndicator();
            this.disableForm();

            const formData = new FormData(this.form);
            formData.append('action', 'rtbcb_generate_case');
            formData.append('rtbcb_nonce', RTBCB.nonce);

            // Simulate progress steps
            this.startProgressSimulation();

            const response = await fetch(RTBCB.ajax_url, {
                method: 'POST',
                body: new URLSearchParams(formData)
            });

            const data = await response.json();

            if (data.success) {
                this.completeProgress();
                this.displayResults(data.data);
                this.trackAnalytics('business_case_generated', {
                    category: data.data.recommendation?.recommended,
                    roi_base: data.data.scenarios?.base?.total_annual_benefit
                });
            } else {
                this.showError(data.data || 'An unknown error occurred.');
            }

        } catch (error) {
            this.showError('A network error occurred. Please try again.');
            console.error('Submission Error:', error);
        } finally {
            this.hideProgressIndicator();
            this.enableForm();
            this.submitBtn.textContent = originalText;
        }
    }

    showProgressIndicator() {
        const progressHtml = `
            <div class="rtbcb-progress-overlay">
                <div class="rtbcb-progress-content">
                    <div class="rtbcb-progress-spinner"></div>
                    <div class="rtbcb-progress-text">Generating your business case...</div>
                    <div class="rtbcb-progress-steps">
                        <div class="rtbcb-progress-step active">Starting analysis...</div>
                    </div>
                </div>
            </div>
        `;

        this.form.insertAdjacentHTML('afterend', progressHtml);
        this.progressOverlay = document.querySelector('.rtbcb-progress-overlay');
    }

    startProgressSimulation() {
        let currentStep = 0;
        const stepDuration = 2000; // 2 seconds per step

        this.progressInterval = setInterval(() => {
            if (currentStep < this.progressSteps.length) {
                this.updateProgressStep(this.progressSteps[currentStep]);
                currentStep++;
            }
        }, stepDuration);
    }

    updateProgressStep(stepText) {
        const stepElement = document.querySelector('.rtbcb-progress-step');
        if (stepElement) {
            stepElement.textContent = stepText;
            stepElement.classList.add('rtbcb-progress-step-animation');
            
            setTimeout(() => {
                stepElement.classList.remove('rtbcb-progress-step-animation');
            }, 500);
        }
    }

    completeProgress() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }

        const stepElement = document.querySelector('.rtbcb-progress-step');
        if (stepElement) {
            stepElement.textContent = 'Analysis complete!';
            stepElement.classList.add('rtbcb-progress-complete');
        }

        // Auto-hide after a moment
        setTimeout(() => {
            this.hideProgressIndicator();
        }, 1000);
    }

    hideProgressIndicator() {
        if (this.progressOverlay) {
            this.progressOverlay.remove();
            this.progressOverlay = null;
        }

        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
    }

    disableForm() {
        const formElements = this.form.querySelectorAll('input, select, button');
        formElements.forEach(element => {
            element.disabled = true;
        });
        this.submitBtn.classList.add('loading');
    }

    enableForm() {
        const formElements = this.form.querySelectorAll('input, select, button');
        formElements.forEach(element => {
            element.disabled = false;
        });
        this.submitBtn.classList.remove('loading');
    }

    displayResults(data) {
        const recommendation = data.recommendation || {};
        const categoryInfo = recommendation.category_info || {};
        const scenarios = data.scenarios || {};
        const narrative = data.narrative || {};

        const html = `
            <div class="rtbcb-results-header">
                <div class="rtbcb-results-badge">
                    <span class="rtbcb-badge-icon">✓</span>
                    Analysis Complete
                </div>
                <h3>Your Treasury Technology Business Case</h3>
            </div>

            <div class="rtbcb-recommendation-card">
                <div class="rtbcb-recommendation-header">
                    <h4>Recommended Solution Category</h4>
                    <div class="rtbcb-confidence-badge">
                        ${Math.round((recommendation.confidence || 0.8) * 100)}% match
                    </div>
                </div>
                <div class="rtbcb-recommendation-name">${this.escapeHtml(categoryInfo.name || 'Treasury Management System')}</div>
                <div class="rtbcb-recommendation-description">${this.escapeHtml(categoryInfo.description || '')}</div>
                <div class="rtbcb-recommendation-reasoning">${this.escapeHtml(recommendation.reasoning || '')}</div>
            </div>

            <div class="rtbcb-roi-section">
                <h4>Annual ROI Projections</h4>
                <div class="rtbcb-roi-summary">
                    <div class="rtbcb-scenario rtbcb-scenario-conservative">
                        <div class="rtbcb-scenario-label">Conservative</div>
                        <div class="rtbcb-scenario-amount">$${this.formatNumber(scenarios.low?.total_annual_benefit || 0)}</div>
                        <div class="rtbcb-scenario-confidence">80% likely</div>
                    </div>
                    <div class="rtbcb-scenario rtbcb-scenario-base">
                        <div class="rtbcb-scenario-label">Base Case</div>
                        <div class="rtbcb-scenario-amount">$${this.formatNumber(scenarios.base?.total_annual_benefit || 0)}</div>
                        <div class="rtbcb-scenario-confidence">Best estimate</div>
                    </div>
                    <div class="rtbcb-scenario rtbcb-scenario-optimistic">
                        <div class="rtbcb-scenario-label">Optimistic</div>
                        <div class="rtbcb-scenario-amount">$${this.formatNumber(scenarios.high?.total_annual_benefit || 0)}</div>
                        <div class="rtbcb-scenario-confidence">Best-case scenario</div>
                    </div>
                </div>
            </div>

            <div class="rtbcb-benefit-breakdown">
                <h4>Benefit Breakdown (Base Case)</h4>
                <div class="rtbcb-benefit-chart">
                    <canvas id="rtbcbBenefitChart" width="400" height="200"></canvas>
                </div>
                <div class="rtbcb-benefit-details">
                    <div class="rtbcb-benefit-item">
                        <span class="rtbcb-benefit-label">Labor Cost Savings</span>
                        <span class="rtbcb-benefit-amount">$${this.formatNumber(scenarios.base?.labor_savings || 0)}</span>
                    </div>
                    <div class="rtbcb-benefit-item">
                        <span class="rtbcb-benefit-label">Bank Fee Reduction</span>
                        <span class="rtbcb-benefit-amount">$${this.formatNumber(scenarios.base?.fee_savings || 0)}</span>
                    </div>
                    <div class="rtbcb-benefit-item">
                        <span class="rtbcb-benefit-label">Error Reduction Value</span>
                        <span class="rtbcb-benefit-amount">$${this.formatNumber(scenarios.base?.error_reduction || 0)}</span>
                    </div>
                </div>
            </div>

            <div class="rtbcb-narrative-section">
                <h4>Executive Summary</h4>
                <div class="rtbcb-narrative-content">${this.escapeHtml(narrative.narrative || '')}</div>
            </div>

            ${this.renderAlternatives(recommendation.alternatives || [])}

            <div class="rtbcb-actions-section">
                ${data.download_url ? `
                    <a href="${this.escapeHtml(data.download_url)}" class="rtbcb-action-btn rtbcb-btn-primary" target="_blank">
                        <span class="rtbcb-btn-icon">📄</span>
                        Download Full Report (PDF)
                    </a>
                ` : ''}
                <button type="button" class="rtbcb-action-btn rtbcb-btn-secondary" onclick="window.print()">
                    <span class="rtbcb-btn-icon">🖨️</span>
                    Print Results
                </button>
                <button type="button" class="rtbcb-action-btn rtbcb-btn-secondary" onclick="businessCaseBuilder.shareResults()">
                    <span class="rtbcb-btn-icon">📧</span>
                    Email Results
                </button>
            </div>

            <div class="rtbcb-next-steps">
                <h4>Next Steps</h4>
                <div class="rtbcb-steps-grid">
                    <div class="rtbcb-step">
                        <div class="rtbcb-step-number">1</div>
                        <div class="rtbcb-step-content">
                            <h5>Stakeholder Alignment</h5>
                            <p>Present this business case to key decision makers</p>
                        </div>
                    </div>
                    <div class="rtbcb-step">
                        <div class="rtbcb-step-number">2</div>
                        <div class="rtbcb-step-content">
                            <h5>Vendor Research</h5>
                            <p>Evaluate ${categoryInfo.name || 'treasury technology'} providers</p>
                        </div>
                    </div>
                    <div class="rtbcb-step">
                        <div class="rtbcb-step-number">3</div>
                        <div class="rtbcb-step-content">
                            <h5>Implementation Planning</h5>
                            <p>Develop project timeline and resource requirements</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.results.innerHTML = html;
        this.results.style.display = 'block';
        
        // Create the benefit breakdown chart
        this.createBenefitChart(scenarios.base);
        
        // Smooth scroll to results
        setTimeout(() => {
            this.results.scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }, 100);
    }

    renderAlternatives(alternatives) {
        if (!alternatives || alternatives.length === 0) {
            return '';
        }

        let html = '<div class="rtbcb-alternatives-section"><h4>Alternative Considerations</h4><div class="rtbcb-alternatives-grid">';
        
        alternatives.forEach(alt => {
            const info = alt.info || {};
            html += `
                <div class="rtbcb-alternative-card">
                    <div class="rtbcb-alternative-name">${this.escapeHtml(info.name || '')}</div>
                    <div class="rtbcb-alternative-description">${this.escapeHtml(info.description || '')}</div>
                    <div class="rtbcb-alternative-score">Match: ${Math.round(alt.score || 0)}%</div>
                </div>
            `;
        });
        
        html += '</div></div>';
        return html;
    }

    createBenefitChart(baseScenario) {
        const ctx = document.getElementById('rtbcbBenefitChart');
        if (!ctx || !baseScenario) return;

        const data = {
            labels: ['Labor Savings', 'Fee Reduction', 'Error Prevention'],
            datasets: [{
                data: [
                    baseScenario.labor_savings || 0,
                    baseScenario.fee_savings || 0,
                    baseScenario.error_reduction || 0
                ],
                backgroundColor: [
                    '#7216f4',
                    '#8f47f6',
                    '#c77dff'
                ],
                borderWidth: 0
            }]
        };

        new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${context.label}: $${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    shareResults() {
        const baseROI = this.getBaseROI();
        const category = this.getRecommendedCategory();
        
        const subject = encodeURIComponent('Treasury Technology Business Case Results');
        const body = encodeURIComponent(
            `I've completed a treasury technology ROI analysis with these results:\n\n` +
            `• Recommended solution: ${category}\n` +
            `• Estimated annual benefit: $${this.formatNumber(baseROI)}\n\n` +
            `The analysis suggests significant potential value from implementing treasury technology. ` +
            `I'd like to discuss this opportunity further.\n\n` +
            `Generated by Real Treasury Business Case Builder`
        );
        
        window.location.href = `mailto:?subject=${subject}&body=${body}`;
    }

    getBaseROI() {
        const results = this.results.querySelector('.rtbcb-scenario-base .rtbcb-scenario-amount');
        if (results) {
            return parseFloat(results.textContent.replace(/[\$,]/g, '')) || 0;
        }
        return 0;
    }

    getRecommendedCategory() {
        const category = this.results.querySelector('.rtbcb-recommendation-name');
        return category ? category.textContent.trim() : 'Treasury Management System';
    }

    formatNumber(num) {
        return new Intl.NumberFormat('en-US').format(Math.round(num));
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showError(message) {
        const existingError = this.form.querySelector('.rtbcb-error');
        if (existingError) {
            existingError.remove();
        }

        const errorHtml = `<div class="rtbcb-error">${this.escapeHtml(message)}</div>`;
        this.form.insertAdjacentHTML('afterend', errorHtml);

        setTimeout(() => {
            const errorElement = this.form.querySelector('.rtbcb-error');
            if (errorElement) {
                errorElement.remove();
            }
        }, 5000);
    }

    scrollToFirstError() {
        const firstError = this.form.querySelector('.rtbcb-field-invalid, .rtbcb-error');
        if (firstError) {
            firstError.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }
    }

    trackAnalytics(event, data) {
        // Google Analytics 4 tracking
        if (typeof gtag !== 'undefined') {
            gtag('event', event, {
                ...data,
                event_category: 'business_case_builder'
            });
        }

        // Custom tracking endpoint (if needed)
        // fetch('/wp-admin/admin-ajax.php', {
        //     method: 'POST',
        //     body: new URLSearchParams({
        //         action: 'rtbcb_track_event',
        //         event: event,
        //         data: JSON.stringify(data),
        //         nonce: RTBCB.nonce
        //     })
        // });
    }
}

// Global instance
let businessCaseBuilder;

document.addEventListener('DOMContentLoaded', () => {
    businessCaseBuilder = new BusinessCaseBuilder();
    
    // Add some progressive enhancement
    enhanceFormExperience();
});

function enhanceFormExperience() {
    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Add form section auto-expansion
    const sections = document.querySelectorAll('.rtbcb-section');
    sections.forEach((section, index) => {
        if (index > 0) {
            section.style.opacity = '0.6';
            section.style.pointerEvents = 'none';
        }
    });

    // Enable sections progressively as user completes them
    const requiredFields = document.querySelectorAll('input[required], select[required]');
    requiredFields.forEach(field => {
        field.addEventListener('change', checkSectionCompletion);
    });
}

function checkSectionCompletion() {
    const sections = document.querySelectorAll('.rtbcb-section');
    
    sections.forEach((section, index) => {
        const sectionFields = section.querySelectorAll('input[required], select[required]');
        const completedFields = Array.from(sectionFields).filter(field => {
            if (field.type === 'checkbox') {
                const checkboxGroup = section.querySelectorAll(`input[name="${field.name}"]`);
                return Array.from(checkboxGroup).some(cb => cb.checked);
            }
            return field.value.trim() !== '';
        });

        if (completedFields.length === sectionFields.length) {
            // Enable next section
            const nextSection = sections[index + 1];
            if (nextSection) {
                nextSection.style.opacity = '1';
                nextSection.style.pointerEvents = 'auto';
                nextSection.style.transition = 'opacity 0.3s ease';
            }
        }
    });
}
