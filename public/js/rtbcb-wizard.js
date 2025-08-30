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

        if (window.businessCaseBuilder) {
            window.businessCaseBuilder.reinitialize();
        } else {
            window.businessCaseBuilder = new BusinessCaseBuilder();
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
        this.ajaxUrl = ( typeof rtbcbAjax !== 'undefined' && rtbcbAjax.ajax_url ) ? rtbcbAjax.ajax_url : '';

        if ( ! this.form ) {
            return;
        }

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
        this.progressLine = this.form.querySelector('.rtbcb-progress-line');
        
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
            if (this.validateStep(this.totalSteps)) {
                try {
                    this.handleSubmit(e);
                } catch (error) {
                    console.error('RTBCB: handleSubmit error:', error);
                    this.showError('An unexpected error occurred. Please try again.');
                }
            }
        });

        // Pain point cards
        const painPointCards = this.form.querySelectorAll('.rtbcb-pain-point-card');
        painPointCards.forEach(card => {
            const checkbox = card.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.addEventListener('change', () => {
                    card.classList.toggle('rtbcb-selected', checkbox.checked);
                    const checkedBoxes = this.form.querySelectorAll('input[name="pain_points[]"]:checked');
                    if (checkedBoxes.length > 0) {
                        this.clearStepError(3);
                    }
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
            this.clearStepError(3);
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
            if (!Number.isFinite(Number(value))) {
                errorMessage = 'Please enter a valid number';
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
            }
        }
    }

    clearStepError(stepNumber) {
        const step = this.steps[stepNumber - 1];
        if (!step) return;

        const validationDiv = step.querySelector('.rtbcb-pain-points-validation');
        if (validationDiv) {
            const messageDiv = validationDiv.querySelector('.rtbcb-validation-message');
            if (messageDiv) {
                messageDiv.style.display = 'none';
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
        } else {
            if (this.nextBtn) {
                this.nextBtn.style.display = 'inline-flex';
            }
        }

        if (this.submitBtn) {
            const isLastStep = this.currentStep === this.totalSteps;
            if (isLastStep) {
                let stepsValid = true;
                for (let i = 1; i < this.currentStep; i++) {
                    if (!this.validateStep(i)) {
                        stepsValid = false;
                        break;
                    }
                }
                this.submitBtn.style.display = stepsValid ? 'inline-flex' : 'none';
                this.submitBtn.disabled = !stepsValid;
            } else {
                this.submitBtn.style.display = 'none';
                this.submitBtn.disabled = true;
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

        if (this.progressLine) {
            const progress = (this.currentStep / this.totalSteps) * 100;
            this.progressLine.style.width = `${progress}%`;
        }
    }

    scrollToTop() {
        const modalBody = this.form.closest('.rtbcb-modal-body');
        if (modalBody) {
            modalBody.scrollTop = 0;
        }
    }

    async handleSubmit(event) {
        if (event && event.preventDefault) {
            event.preventDefault();
        }
        if (!this.ajaxUrl) {
            this.showError('Service unavailable. Please reload the page.');
            return;
        }

        try {
            console.log('RTBCB: Starting form submission');
            this.showLoading();

            const formData = this.collectFormData();
            this.validateFormData(formData);
            this.lastFormData = formData;

            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            console.log('RTBCB: Response status:', response.status);
            const contentType = response.headers && response.headers.get ? response.headers.get('content-type') : null;
            console.log('RTBCB: Content-Type:', contentType);

            const responseText = await response.text();
            console.log('RTBCB: Raw response length:', responseText.length);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            if (!contentType || !contentType.includes('application/json')) {
                console.warn('RTBCB: Unexpected server response:', responseText);
                const error = new Error('Unexpected server response');
                error.status = response.status;
                throw error;
            }

            if (responseText.includes('Fatal error') ||
                responseText.includes('Parse error') ||
                responseText.includes('<b>Warning</b>') ||
                responseText.includes('<b>Notice</b>')) {

                console.error('RTBCB: PHP error detected in response');
                throw new Error('Server error detected in response');
            }

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('RTBCB: JSON parse error:', parseError);
                console.error('RTBCB: Response text (first 1000 chars):', responseText.substring(0, 1000));

                const errorMatch = responseText.match(/Fatal error[^<]*/i) ||
                                  responseText.match(/Warning[^<]*/i) ||
                                  responseText.match(/Notice[^<]*/i);

                if (errorMatch) {
                    throw new Error(`Server error: ${errorMatch[0]}`);
                }

                throw new Error('Server returned invalid JSON response');
            }

            if (data.success && data.data && data.data.job_id) {
                console.log('RTBCB: Job queued');
                this.pollJob(data.data.job_id);
            } else {
                console.error('RTBCB: Error response:', data.data);
                this.handleError(data.data);
            }

        } catch (error) {
            console.error('RTBCB: Submission error:', error);
            this.handleError({
                message: error.message || 'An unexpected error occurred',
                type: 'submission_error'
            });
        } finally {
            this.hideLoading();
        }
    }

    collectFormData() {
        const rawData = new FormData(this.form);
        const formData = new FormData();
        const numericFields = ['hours_reconciliation', 'hours_cash_positioning', 'num_banks', 'ftes'];

        for (const [key, value] of rawData.entries()) {
            if (numericFields.includes(key)) {
                const num = parseFloat(value);
                formData.append(key, Number.isFinite(num) ? num : 0);
            } else {
                formData.append(key, value);
            }
        }

        formData.append('action', 'rtbcb_generate_case');
        if (typeof rtbcbAjax !== 'undefined' && rtbcbAjax.nonce) {
            formData.append('rtbcb_nonce', rtbcbAjax.nonce);
        }
        return formData;
    }

    showLoading() {
        this.showProgress();
    }

    hideLoading() {
        // progress is hidden by success or error handlers
    }

    handleSuccess(data) {
        this.showResults(data);
    }

    showErrorMessage(message) {
        this.showError(message);
    }

    async pollJob(jobId) {
        try {
            const response = await fetch(`${this.ajaxUrl}?action=rtbcb_job_status&job_id=${encodeURIComponent(jobId)}&rtbcb_nonce=${rtbcbAjax.nonce}`, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (!data.success) {
                this.handleError({ message: 'Unable to retrieve job status', type: 'polling_error' });
                return;
            }
            const status = data.data.status;
            if (status === 'completed') {
                const report = data.data.report_data;
                if (report) {
                    this.handleSuccess(report);
                } else {
                    this.handleError({ message: 'Report data missing', type: 'job_error' });
                }
            } else if (status === 'error') {
                this.handleError({ message: data.data.message || 'Job failed', type: 'job_error' });
            } else {
                setTimeout(() => this.pollJob(jobId), 2000);
            }
        } catch (error) {
            this.handleError({ message: error.message || 'An unexpected error occurred', type: 'polling_error' });
        }
    }

    // Enhanced form validation
    validateFormData(formData) {
        const getValue = (field) => {
            if (typeof formData.get === 'function') {
                return formData.get(field);
            }
            for (const [k, v] of formData.entries()) {
                if (k === field) {
                    return v;
                }
            }
            return null;
        };

        const getAllValues = (field) => {
            if (typeof formData.getAll === 'function') {
                return formData.getAll(field);
            }
            const values = [];
            for (const [k, v] of formData.entries()) {
                if (k === field) {
                    values.push(v);
                }
            }
            return values;
        };

        const requiredFields = [
            'email', 'company_name', 'company_size', 'industry',
            'hours_reconciliation', 'hours_cash_positioning',
            'num_banks', 'ftes', 'business_objective',
            'implementation_timeline', 'budget_range'
        ];

        for (const field of requiredFields) {
            if (!getValue(field)) {
                throw new Error(`Missing required field: ${field.replace('_', ' ')}`);
            }
        }

        const email = getValue('email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            throw new Error('Please enter a valid email address');
        }

        const numericFields = ['hours_reconciliation', 'hours_cash_positioning', 'num_banks', 'ftes'];
        for (const field of numericFields) {
            const value = parseFloat(getValue(field));
            if (isNaN(value) || value <= 0) {
                throw new Error(`${field.replace('_', ' ')} must be a positive number`);
            }
        }

        const painPoints = getAllValues('pain_points[]');
        if (painPoints.length === 0) {
            throw new Error('Please select at least one pain point');
        }
    }

    // Enhanced error display
    handleError(errorData) {
        const message = errorData.message || 'An unexpected error occurred';

        console.group('RTBCB Error Details');
        console.error('Message:', message);
        console.error('Type:', errorData.type);
        console.error('Debug Info:', errorData.debug_info);
        console.error('Timestamp:', errorData.timestamp);
        console.groupEnd();

        this.showErrorMessage(this.getUserFriendlyMessage(message));
    }

    getUserFriendlyMessage(serverMessage) {
        const errorMappings = {
            'Security check failed': 'Session expired. Please refresh the page and try again.',
            'OpenAI API key not configured': 'Service temporarily unavailable. Please try again later.',
            'API connection failed': 'Unable to connect to analysis service. Please try again.',
            'Missing required field': 'Please fill in all required fields.',
            'Invalid email address': 'Please enter a valid email address.',
            'PHP error occurred': 'Server error encountered. Please try again.',
            'Server returned invalid JSON response': 'Server communication error. Please try again.',
            'Unexpected server response': 'Server communication error. Please try again.'
        };

        for (const [key, message] of Object.entries(errorMappings)) {
            if (serverMessage.includes(key)) {
                return message;
            }
        }

        return 'An error occurred while processing your request. Please try again.';
    }


    showProgress() {
        // Hide form
        const formContainer = this.form.closest('.rtbcb-form-container');
        if (formContainer) {
            formContainer.style.display = 'none';
        }

        const progressContainer = document.getElementById('rtbcb-progress-container');
        if (progressContainer) {
            const companyName = this.form.querySelector('[name="company_name"]')?.value || 'your company';
            progressContainer.innerHTML = `
                <div class="rtbcb-progress-content">
                    <div class="rtbcb-progress-spinner"></div>
                    <div class="rtbcb-progress-text">Generating Your Business Case</div>
                    <div class="rtbcb-progress-step">
                        <span class="rtbcb-progress-step-text">Analyzing ${this.escapeHTML(companyName)}'s treasury operations...</span>
                    </div>
                </div>
            `;
            progressContainer.style.display = 'flex';
        }
    }

    showResults(data) {
        const progressContainer = document.getElementById('rtbcb-progress-container');
        if (progressContainer) {
            progressContainer.style.display = 'none';
            progressContainer.innerHTML = '';
        }

        // Map nested report data
        const mapped = {
            companyName: data.metadata?.company_name || 'Your Company',
            scenarios: data.financial_analysis?.roi_scenarios || {},
            recommendation: {
                category_info: data.technology_strategy?.category_details || {},
                confidence: data.metadata?.confidence_level || 0.75,
                reasoning: data.technology_strategy?.recommended_category || ''
            },
            narrative: {
                narrative: data.executive_summary?.executive_recommendation || '',
                next_actions: [
                    ...(data.action_plan?.immediate_steps || []),
                    ...(data.action_plan?.short_term_milestones || []),
                    ...(data.action_plan?.long_term_objectives || [])
                ]
            },
            risks: data.risk_analysis?.implementation_risks || []
        };

        // Close modal
        window.closeBusinessCaseModal();

        // Render results
        const resultsContainer = document.getElementById('rtbcbResults');
        if (resultsContainer) {
            resultsContainer.innerHTML = this.renderResults(mapped);
            this.populateRiskAssessment(mapped.risks);
            resultsContainer.style.display = 'block';
            resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    renderResults(data) {
        const { scenarios, recommendation, companyName, narrative } = data;
        const displayName = companyName || 'Your Company';

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

    showTimeoutError(message, diagnostics = {}) {
        const progressContainer = document.getElementById('rtbcb-progress-container');
        if (progressContainer) {
            const safeMessage = this.escapeHTML(message);
            const requestInfo = diagnostics.requestId ? `<div>Request ID: ${this.escapeHTML(diagnostics.requestId)}</div>` : '';
            const timestampInfo = diagnostics.timestamp ? `<div>Timestamp: ${this.escapeHTML(diagnostics.timestamp)}</div>` : '';
            const diagHTML = requestInfo || timestampInfo ? `<div class="rtbcb-error-diagnostics" style="color: #6b7280; margin-bottom: 24px;">${requestInfo}${timestampInfo}</div>` : '';
            progressContainer.innerHTML = `
                <div class="rtbcb-error-overlay" style="padding: 40px; text-align: center;">
                    <div class="rtbcb-error-icon" style="font-size: 48px; color: #ef4444; margin-bottom: 20px;">⚠️</div>
                    <h3 style="color: #ef4444; margin-bottom: 16px;">Server Timeout</h3>
                    <p style="color: #4b5563; margin-bottom: 24px;">${safeMessage}</p>
                    ${diagHTML}
                    <div class="rtbcb-error-actions">
                        <button type="button" class="rtbcb-action-btn rtbcb-btn-primary rtbcb-retry-btn">Retry</button>
                        <a href="mailto:contact@realtreasury.com" class="rtbcb-action-btn rtbcb-btn-secondary" style="text-decoration: none;">Contact Support</a>
                    </div>
                </div>
            `;
            progressContainer.style.display = 'flex';
            const retryBtn = progressContainer.querySelector('.rtbcb-retry-btn');
            if (retryBtn) {
                retryBtn.addEventListener('click', () => {
                    retryBtn.disabled = true;
                    progressContainer.innerHTML = '';
                    this.handleSubmit(this.lastFormData);
                });
            }
        }
    }

    showError(message) {
        const progressContainer = document.getElementById('rtbcb-progress-container');
        if (progressContainer) {
            const safeMessage = this.escapeHTML(message);
            progressContainer.innerHTML = `
                <div class="rtbcb-error-container" style="padding: 40px; text-align: center;">
                    <div class="rtbcb-error-icon" style="font-size: 48px; color: #ef4444; margin-bottom: 20px;">⚠️</div>
                    <h3 style="color: #ef4444; margin-bottom: 16px;">Unable to Generate Business Case</h3>
                    <p style="color: #4b5563; margin-bottom: 24px;">${safeMessage}</p>
                    <div class="rtbcb-error-actions">
                        <button type="button" class="rtbcb-action-btn rtbcb-btn-primary" onclick="location.reload()">
                            Try Again
                        </button>
                        <a href="mailto:contact@realtreasury.com" class="rtbcb-action-btn rtbcb-btn-secondary" style="text-decoration: none;">
                            Contact Support
                        </a>
                    </div>
                </div>
            `;
            progressContainer.style.display = 'flex';
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
