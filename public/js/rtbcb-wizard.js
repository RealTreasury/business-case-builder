/**
 * Business Case Builder Wizard Controller
 * Handles multi-step form navigation, validation, and submission
 */

// Ensure modal functions are available immediately
window.openBusinessCaseModal = function() {
    const overlay = document.getElementById('rtbcbModalOverlay');
    if (overlay) {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Force re-initialization without blocking rendering
        const initBuilder = () => {
            if (window.businessCaseBuilder) {
                window.businessCaseBuilder.reinitialize();
            } else {
                window.businessCaseBuilder = new BusinessCaseBuilder();
            }
        };

        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(initBuilder);
        } else {
            setTimeout(initBuilder, 0);
        }
    }
};

window.closeBusinessCaseModal = function() {
    const overlay = document.getElementById('rtbcbModalOverlay');
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
};

// Initialize on DOM ready with error handling
document.addEventListener('DOMContentLoaded', function() {
    try {
        if (document.getElementById('rtbcbForm')) {
            window.businessCaseBuilder = new BusinessCaseBuilder();
        }
    } catch (error) {
        console.error('BusinessCaseBuilder initialization failed:', error);
    }
});

class BusinessCaseBuilder {
    constructor() {
        this.currentStep = 1;
        this.totalSteps = 5;
        this.form = document.getElementById('rtbcbForm');
        this.overlay = document.getElementById('rtbcbModalOverlay');
        
        if (!this.form) return;
        
        this.init();
    }

    init() {
        this.cacheElements();
        this.bindEvents();
        this.updateStepVisibility();
        this.updateProgressIndicator();
    }

    cacheElements() {
        // Navigation buttons
        this.nextBtn = this.form.querySelector('.rtbcb-nav-next');
        this.prevBtn = this.form.querySelector('.rtbcb-nav-prev');
        this.submitBtn = this.form.querySelector('.rtbcb-nav-submit');
        
        // Steps
        this.steps = this.form.querySelectorAll('.rtbcb-wizard-step');
        this.progressSteps = this.form.querySelectorAll('.rtbcb-progress-step');
        
        // UPDATED: Form fields by step - now includes company_name and industry
        this.stepFields = {
            1: ['company_name', 'company_size', 'industry'], // Added company_name and industry
            2: ['hours_reconciliation', 'hours_cash_positioning', 'num_banks', 'ftes'],
            3: ['pain_points'],
            4: ['business_objective', 'implementation_timeline', 'budget_range'],
            5: ['email']
        };
    }

    bindEvents() {
        // Navigation buttons
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleNext();
            });
        }

        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handlePrev();
            });
        }

        // Form submission
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            if (this.validateStep(this.totalSteps)) {
                this.handleSubmit();
            }
        });

        // Pain point cards
        const painPointCards = this.form.querySelectorAll('.rtbcb-pain-point-card');
        painPointCards.forEach(card => {
            const checkbox = card.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.addEventListener('change', () => {
                    card.classList.toggle('rtbcb-selected', checkbox.checked);
                });
            }
        });

        // Real-time validation
        this.form.querySelectorAll('input, select').forEach(field => {
            field.addEventListener('blur', () => this.validateField(field));
            field.addEventListener('input', () => this.clearFieldError(field));
        });

        // Modal close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.overlay.classList.contains('active')) {
                window.closeBusinessCaseModal();
            }
        });
    }

    handleNext() {
        if (this.validateStep(this.currentStep)) {
            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                this.updateStepVisibility();
                this.updateProgressIndicator();
                this.scrollToTop();

                // Safeguard to ensure navigation buttons reflect the current step
                this.updateStepVisibility();
            }
        }
    }

    handlePrev() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.updateStepVisibility();
            this.updateProgressIndicator();
            this.scrollToTop();
        }
    }

    validateStep(stepNumber) {
        let isValid = true;
        const fieldsToValidate = this.stepFields[stepNumber];

        if (stepNumber === 3) {
            // Special validation for pain points
            const checkedBoxes = this.form.querySelectorAll('input[name="pain_points[]"]:checked');
            if (checkedBoxes.length === 0) {
                this.showStepError(3, 'Please select at least one challenge');
                return false;
            }
        } else {
            // Standard field validation
            fieldsToValidate.forEach(fieldName => {
                const field = this.form.querySelector(`[name="${fieldName}"]`);
                if (field && !this.validateField(field)) {
                    isValid = false;
                }
            });
        }

        return isValid;
    }

    // NEW: Show field warning (non-blocking)
    showFieldWarning(field, message) {
        const fieldContainer = field.closest('.rtbcb-field');
        if (!fieldContainer) return;

        // Remove existing warning
        const existingWarning = fieldContainer.querySelector('.rtbcb-field-warning');
        if (existingWarning) {
            existingWarning.remove();
        }

        // Create warning element
        const warningEl = document.createElement('div');
        warningEl.className = 'rtbcb-field-warning';
        warningEl.textContent = message;
        warningEl.style.color = '#f59e0b';
        warningEl.style.fontSize = '12px';
        warningEl.style.marginTop = '4px';
        fieldContainer.appendChild(warningEl);
    }

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Required field check
        if (field.hasAttribute('required') && !value) {
            errorMessage = 'This field is required';
            isValid = false;
        }

        // NEW: Company name validation
        if (field.name === 'company_name' && value) {
            if (value.length < 2) {
                errorMessage = 'Company name must be at least 2 characters';
                isValid = false;
            } else if (value.length > 100) {
                errorMessage = 'Company name must be less than 100 characters';
                isValid = false;
            } else if (/^[^a-zA-Z]*$/.test(value)) {
                errorMessage = 'Please enter a valid company name';
                isValid = false;
            }
        }

        // Email validation
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                errorMessage = 'Please enter a valid email address';
                isValid = false;
            }

            // Enhanced business email validation
            const domain = value.split('@')[1]?.toLowerCase();
            const consumerDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com'];
            if (domain && consumerDomains.includes(domain)) {
                // Warning but don't block
                this.showFieldWarning(field, 'Consider using your business email for better results');
            }
        }

        // Number validation
        if (field.type === 'number' && value) {
            const num = Number(value);
            const min = parseFloat(field.min);
            const max = parseFloat(field.max);

            if (!Number.isFinite(num)) {
                errorMessage = 'Please enter a valid number';
                isValid = false;
            } else if (min !== undefined && num < min) {
                errorMessage = `Value must be at least ${min}`;
                isValid = false;
            } else if (max !== undefined && num > max) {
                errorMessage = `Value must not exceed ${max}`;
                isValid = false;
            }
        }

        // Show/hide error
        if (!isValid) {
            this.showFieldError(field, errorMessage);
        } else {
            this.clearFieldError(field);
        }

        return isValid;
    }

    showFieldError(field, message) {
        const fieldContainer = field.closest('.rtbcb-field');
        if (!fieldContainer) return;

        // Remove existing error
        this.clearFieldError(field);

        // Add error class
        field.classList.add('rtbcb-field-invalid');

        // Create error element
        const errorEl = document.createElement('div');
        errorEl.className = 'rtbcb-field-error';
        errorEl.textContent = message;
        fieldContainer.appendChild(errorEl);
    }

    clearFieldError(field) {
        const fieldContainer = field.closest('.rtbcb-field');
        if (!fieldContainer) return;

        field.classList.remove('rtbcb-field-invalid');
        const existingError = fieldContainer.querySelector('.rtbcb-field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    showStepError(stepNumber, message) {
        const step = this.steps[stepNumber - 1];
        if (!step) return;

        const validationDiv = step.querySelector('.rtbcb-pain-points-validation');
        if (validationDiv) {
            const messageDiv = validationDiv.querySelector('.rtbcb-validation-message');
            if (messageDiv) {
                messageDiv.textContent = message;
                messageDiv.style.display = 'block';
                messageDiv.style.color = '#ef4444';

                if (this.messageHideTimeout) {
                    clearTimeout(this.messageHideTimeout);
                }

                this.messageHideTimeout = setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            }
        }
    }

    updateStepVisibility() {
        // Update step visibility
        this.steps.forEach((step, index) => {
            const stepNum = index + 1;
            if (stepNum === this.currentStep) {
                step.classList.add('active');
                step.style.display = 'block';
            } else {
                step.classList.remove('active');
                step.style.display = 'none';
            }
        });

        // Update navigation buttons
        if (this.prevBtn) {
            this.prevBtn.style.display = this.currentStep === 1 ? 'none' : 'inline-flex';
        }

        if (this.currentStep === this.totalSteps) {
            if (this.nextBtn) {
                this.nextBtn.style.display = 'none';
            }

            if (this.submitBtn) {
                this.submitBtn.style.display = 'inline-flex';
            }
        } else {
            if (this.nextBtn) {
                this.nextBtn.style.display = 'inline-flex';
            }

            if (this.submitBtn) {
                this.submitBtn.style.display = 'none';
            }
        }

    }

    updateProgressIndicator() {
        this.progressSteps.forEach((step, index) => {
            const stepNum = index + 1;
            
            if (stepNum < this.currentStep) {
                step.classList.add('completed');
                step.classList.remove('active');
            } else if (stepNum === this.currentStep) {
                step.classList.add('active');
                step.classList.remove('completed');
            } else {
                step.classList.remove('active', 'completed');
            }
        });
    }

    scrollToTop() {
        const modalBody = this.form.closest('.rtbcb-modal-body');
        if (modalBody) {
            modalBody.scrollTop = 0;
        }
    }

    async handleSubmit() {
        // Show loading state
        this.showProgress();

        try {
            const formData = new FormData(this.form);

            const step2Fields = ['hours_reconciliation', 'hours_cash_positioning', 'num_banks', 'ftes'];
            step2Fields.forEach(fieldName => {
                const rawValue = Number(this.form.querySelector(`[name="${fieldName}"]`)?.value);
                formData.set(fieldName, Number.isFinite(rawValue) ? rawValue : 0);
            });

            formData.append('action', 'rtbcb_generate_case');

            console.log('RTBCB: Submitting form data:', Object.fromEntries(formData));

            const response = await fetch(ajaxObj.ajax_url, {
                method: 'POST',
                body: formData
            });

            console.log('RTBCB: Response status:', response.status);
            console.log('RTBCB: Response headers:', Object.fromEntries(response.headers));

            if (!response.ok) {
                const errorText = await response.text();
                console.error('RTBCB: Server error response:', errorText);

                let errorMessage = `Server responded with status ${response.status}`;
                try {
                    const errorJson = JSON.parse(errorText);
                    errorMessage = errorJson.data?.message || errorMessage;
                } catch (parseError) {
                    console.error('RTBCB: Could not parse error response as JSON:', parseError);
                }

                throw new Error(errorMessage);
            }

            const result = await response.json();
            console.log('RTBCB: Parsed response:', result);

            if (result.success) {
                console.log('RTBCB: Business case generated successfully');
                this.showResults(result.data);
            } else {
                const errorMessage = result.data?.message || 'Failed to generate business case';
                console.error('RTBCB: Business case generation failed:', errorMessage);
                throw new Error(errorMessage);
            }
        } catch (error) {
            console.error('RTBCB: Submission error details:', {
                message: error.message,
                stack: error.stack,
                type: error.constructor.name
            });

            const displayMessage = error.name === 'TypeError'
                ? 'Network error. Please check your connection and try again.'
                : error.message;

            this.showError(displayMessage);
        }
    }

    showProgress() {
        // Hide form
        const formContainer = this.form.closest('.rtbcb-form-container');
        if (formContainer) {
            formContainer.style.display = 'none';
        }

        // Create and show progress overlay
        const progressHTML = `
            <div class="rtbcb-progress-overlay" style="position: relative; background: none;">
                <div class="rtbcb-progress-content">
                    <div class="rtbcb-progress-spinner"></div>
                    <div class="rtbcb-progress-text">Generating Your Business Case</div>
                    <div class="rtbcb-progress-step">
                        <span class="rtbcb-progress-step-text">Analyzing your treasury operations...</span>
                    </div>
                </div>
            </div>
        `;

        const modalBody = this.form.closest('.rtbcb-modal-body');
        if (modalBody) {
            modalBody.insertAdjacentHTML('beforeend', progressHTML);
        }

        // Animate progress steps
        this.animateProgressSteps();
    }

    animateProgressSteps() {
        const companyName = this.form.querySelector('[name="company_name"]')?.value || 'your company';

        const steps = [
            `Analyzing ${companyName}'s treasury operations...`,
            `Calculating ROI projections for ${companyName}...`,
            `Generating personalized recommendations...`,
            `Preparing ${companyName}'s business case report...`
        ];

        let currentStep = 0;
        const stepElement = document.querySelector('.rtbcb-progress-step-text');

        if (stepElement) {
            this.progressInterval = setInterval(() => {
                currentStep = (currentStep + 1) % steps.length;
                stepElement.textContent = steps[currentStep];
                stepElement.parentElement.classList.add('rtbcb-progress-step-animation');

                setTimeout(() => {
                    stepElement.parentElement.classList.remove('rtbcb-progress-step-animation');
                }, 500);
            }, 2500); // Increased interval for longer messages
        }
    }

    showResults(data) {
        // Clear progress interval
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }

        // Close modal
        window.closeBusinessCaseModal();

        // Render results
        const resultsContainer = document.getElementById('rtbcbResults');
        if (resultsContainer) {
            resultsContainer.innerHTML = this.renderResults(data);
            this.populateRiskAssessment(data.narrative?.risks || []);
            resultsContainer.style.display = 'block';
            resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    renderResults(data) {
        const { scenarios, recommendation, company_name } = data;
        const narrative = data.narrative || {};
        const displayName = company_name || 'Your Company';

        return `
            <div class="rtbcb-results-container">
                <div class="rtbcb-results-header">
                    <div class="rtbcb-results-badge">
                        <span class="rtbcb-badge-icon">✓</span>
                        Business Case Generated Successfully
                    </div>
                    <h2>${displayName} Treasury Technology Business Case</h2>
                    <p class="rtbcb-results-subtitle">Personalized ROI analysis and strategic recommendations</p>
                </div>

                ${this.renderRecommendation(recommendation, displayName)}
                ${this.renderROISummary(scenarios, displayName)}
                ${this.renderNarrative(narrative)}
                ${this.renderRiskAssessmentSection()}
                ${this.renderNextSteps(narrative?.next_actions || [], displayName)}
                ${this.renderActions()}
            </div>
        `;
    }

    renderRecommendation(recommendation, companyName) {
        const category = recommendation.category_info || {};
        const confidence = Math.round((recommendation.confidence || 0.75) * 100);

        return `
            <div class="rtbcb-recommendation-card">
                <div class="rtbcb-recommendation-header">
                    <h3>Recommended Solution for ${companyName}</h3>
                    <span class="rtbcb-confidence-badge">${confidence}% Confidence</span>
                </div>
                <div class="rtbcb-recommendation-name">${category.name || 'Treasury Management System'}</div>
                <div class="rtbcb-recommendation-description">
                    ${category.description || 'Modern treasury platform with automation and analytics'}
                </div>
                <div class="rtbcb-recommendation-reasoning">
                    ${recommendation.reasoning || `Based on ${companyName}'s profile, this solution best fits your needs.`}
                </div>
            </div>
        `;
    }

    renderROISummary(scenarios, companyName) {
        return `
            <div class="rtbcb-roi-section">
                <h3>${companyName} Projected Annual Benefits</h3>
                <div class="rtbcb-roi-summary">
                    <div class="rtbcb-scenario">
                        <div class="rtbcb-scenario-label">Conservative</div>
                        <div class="rtbcb-scenario-amount">$${this.formatNumber(scenarios.low?.total_annual_benefit || 0)}</div>
                        <div class="rtbcb-scenario-confidence">80% probability</div>
                    </div>
                    <div class="rtbcb-scenario rtbcb-scenario-base">
                        <div class="rtbcb-scenario-label">Base Case</div>
                        <div class="rtbcb-scenario-amount">$${this.formatNumber(scenarios.base?.total_annual_benefit || 0)}</div>
                        <div class="rtbcb-scenario-confidence">Most likely outcome</div>
                    </div>
                    <div class="rtbcb-scenario">
                        <div class="rtbcb-scenario-label">Optimistic</div>
                        <div class="rtbcb-scenario-amount">$${this.formatNumber(scenarios.high?.total_annual_benefit || 0)}</div>
                        <div class="rtbcb-scenario-confidence">Best case scenario</div>
                    </div>
                </div>
                ${this.renderBenefitBreakdown(scenarios.base)}
            </div>
        `;
    }

    renderBenefitBreakdown(scenario) {
        if (!scenario) return '';

        const benefits = [
            { label: 'Labor Savings', amount: scenario.labor_savings || 0 },
            { label: 'Fee Reduction', amount: scenario.fee_savings || 0 },
            { label: 'Error Prevention', amount: scenario.error_reduction || 0 }
        ];

        const total = scenario.total_annual_benefit || 0;

        return `
            <div class="rtbcb-benefit-breakdown">
                <h4>Benefit Breakdown</h4>
                <div class="rtbcb-chart-fallback">
                    <div class="rtbcb-benefit-bars">
                        ${benefits.map(b => {
                            const percentage = total > 0 ? (b.amount / total * 100).toFixed(0) : 0;
                            return `
                                <div class="rtbcb-benefit-bar">
                                    <div class="rtbcb-benefit-bar-label">${b.label}</div>
                                    <div class="rtbcb-benefit-bar-fill" style="width: ${percentage}%; background: linear-gradient(90deg, #7216f4, #8f47f6);">
                                        $${this.formatNumber(b.amount)}
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    renderNarrative(narrative = {}) {
        return `
            <div class="rtbcb-narrative-section">
                <h3>Executive Summary</h3>
                <div class="rtbcb-narrative-content">
                    ${narrative?.narrative || 'Treasury technology investment presents a compelling opportunity for operational efficiency.'}
                </div>
            </div>
        `;
    }

    renderRiskAssessmentSection() {
        return `
            <div class="rtbcb-risk-assessment">
                <h3>Risk Assessment</h3>
                <ul class="rtbcb-risk-list"></ul>
            </div>
        `;
    }

    populateRiskAssessment(risks) {
        const list = document.querySelector('.rtbcb-risk-list');
        if (!list) return;

        list.innerHTML = '';
        (risks || []).forEach(risk => {
            const item = document.createElement('li');
            if (risk && typeof risk === 'object') {
                const values = Object.values(risk).filter(Boolean);
                item.textContent = values.join(' - ');
            } else if (risk != null) {
                item.textContent = String(risk);
            }
            list.appendChild(item);
        });
    }

    renderNextSteps(steps) {
        if (!steps || steps.length === 0) {
            steps = [
                'Present business case to stakeholders',
                'Evaluate solution providers',
                'Develop implementation timeline',
                'Plan change management strategy'
            ];
        }

        return `
            <div class="rtbcb-next-steps">
                <h3>Recommended Next Steps</h3>
                <div class="rtbcb-steps-grid">
                    ${steps.map((step, index) => `
                        <div class="rtbcb-step">
                            <div class="rtbcb-step-number">${index + 1}</div>
                            <div class="rtbcb-step-content">
                                <p>${step}</p>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    copyResultsHTML() {
        const container = document.querySelector('.rtbcb-results-container');
        if (!container || !navigator.clipboard) {
            return;
        }
        navigator.clipboard.writeText(container.innerHTML)
            .then(() => alert('Results HTML copied to clipboard'))
            .catch(err => console.error('Copy failed:', err));
    }

    renderActions() {
        return `
            <div class="rtbcb-actions-section">
                <button type="button" class="rtbcb-action-btn rtbcb-btn-secondary" onclick="window.print()">
                    <span class="rtbcb-btn-icon">🖨️</span>
                    Print Results
                </button>
                <button type="button" class="rtbcb-action-btn rtbcb-btn-secondary" onclick="window.businessCaseBuilder.copyResultsHTML()">
                    <span class="rtbcb-btn-icon">📋</span>
                    Copy HTML
                </button>
                <button type="button" class="rtbcb-action-btn rtbcb-btn-secondary" onclick="location.reload()">
                    <span class="rtbcb-btn-icon">🔄</span>
                    Start Over
                </button>
            </div>
        `;
    }

    showError(message) {
        // Clear progress
        const progressOverlay = document.querySelector('.rtbcb-progress-overlay');
        if (progressOverlay) {
            progressOverlay.remove();
        }

        // Show error in modal
        const modalBody = this.form.closest('.rtbcb-modal-body');
        if (modalBody) {
            const safeMessage = this.escapeHTML(message);
            const errorHTML = `
                <div class="rtbcb-error-container" style="padding: 40px; text-align: center;">
                    <div class="rtbcb-error-icon" style="font-size: 48px; color: #ef4444; margin-bottom: 20px;">⚠️</div>
                    <h3 style="color: #ef4444; margin-bottom: 16px;">Unable to Generate Business Case</h3>
                    <p style="color: #4b5563; margin-bottom: 24px;">${safeMessage}</p>
                    <button type="button" class="rtbcb-action-btn rtbcb-btn-primary" onclick="location.reload()">
                        Try Again
                    </button>
                </div>
            `;
            modalBody.innerHTML = errorHTML;
        }
    }

    escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    formatNumber(num) {
        return new Intl.NumberFormat('en-US').format(Math.round(num));
    }

    reinitialize() {
        this.currentStep = 1;
        this.updateStepVisibility();
        this.updateProgressIndicator();
    }
}
