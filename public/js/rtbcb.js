/* Enhanced JavaScript for Real Treasury Business Case Builder plugin */

class BusinessCaseBuilder {
    constructor() {
        this.form = null;
        this.results = null;
        this.currentStep = 1;
        this.totalSteps = 4;
        this.stepData = {};
        this.isInitialized = false;
        this.fallbackSubmit = null;
        
        // Bind methods to preserve 'this' context
        this.nextStep = this.nextStep.bind(this);
        this.previousStep = this.previousStep.bind(this);
        this.handleSubmit = this.handleSubmit.bind(this);

        // Initialize immediately if DOM is ready, otherwise wait
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            // Use setTimeout to ensure modal is rendered
            setTimeout(() => this.init(), 100);
        }
    }

    init() {
        // Prevent multiple initializations
        if (this.isInitialized) {
            return;
        }

        // Get form elements
        this.form = document.getElementById('rtbcbForm');
        this.results = document.getElementById('rtbcbResults');

        if (!this.form) {
            console.log('Form not found, will retry when modal opens');
            return;
        }

        console.log('Initializing Business Case Builder...');

        try {
            this.initWizard();
            this.initFormValidation();
            this.bindEvents();
            this.isInitialized = true;

            if (this.fallbackSubmit) {
                this.fallbackSubmit.style.display = 'none';
            }

            console.log('Business Case Builder initialized successfully');
        } catch (error) {
            console.error('Initialization error:', error);
            this.showFallbackSubmit();
        }
    }

    initWizard() {
        // Get wizard elements with more specific selectors
        this.steps = this.form.querySelectorAll('.rtbcb-wizard-step');
        this.progressSteps = this.form.querySelectorAll('.rtbcb-progress-step');
        this.nextBtn = this.form.querySelector('.rtbcb-nav-next');
        this.prevBtn = this.form.querySelector('.rtbcb-nav-prev');
        this.submitBtn = this.form.querySelector('.rtbcb-nav-submit');
        this.fallbackSubmit = this.form.querySelector('.rtbcb-fallback-submit');

        console.log('Wizard elements found:', {
            steps: this.steps.length,
            progressSteps: this.progressSteps.length,
            nextBtn: !!this.nextBtn,
            prevBtn: !!this.prevBtn,
            submitBtn: !!this.submitBtn
        });

        if (this.steps.length === 0) {
            console.error('No wizard steps found!');
            this.showFallbackSubmit();
            throw new Error('Wizard steps missing');
        }

        if (!this.nextBtn || !this.submitBtn) {
            console.error('Navigation buttons missing');
            this.showFallbackSubmit();
            throw new Error('Navigation buttons missing');
        }

        // Set total steps based on actual step count
        this.totalSteps = this.steps.length;
        this.currentStep = 1;

        this.updateStepDisplay();
        this.updateNavigationState();
        this.updateProgress();
    }

    /**
     * Initialize basic form validation and manage submit button state.
     */
    initFormValidation() {
        console.log('Form validation is now running.');

        if (!this.form) {
            console.error('Form element not found.');
            return;
        }

        const submitButton = this.form.querySelector('#rtbcb-submit-button') || this.form.querySelector('.rtbcb-nav-submit');

        if (!submitButton) {
            console.error('Submit button not found.');
            return;
        }

        // Disable submit until the form satisfies built-in validation
        submitButton.disabled = !this.form.checkValidity();

        this.form.addEventListener('input', () => {
            submitButton.disabled = !this.form.checkValidity();
        });
    }

    bindEvents() {
        // Remove any existing event listeners first
        this.unbindEvents();

        // Navigation events with proper error handling
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Next button clicked, current step:', this.currentStep);
                const startingStep = this.currentStep;
                try {
                    this.nextStep();
                    if (this.currentStep === startingStep) {
                        console.warn('Step did not advance, showing fallback submit');
                        this.showFallbackSubmit();
                    }
                } catch (error) {
                    console.error('Next step failed:', error);
                    this.showFallbackSubmit();
                }
            });
        }

        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Previous button clicked');
                this.previousStep();
            });
        }

        // Form submission
        if (this.form) {
            this.form.addEventListener('submit', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Form submitted');
                this.handleSubmit(e);
            });
        }

        // Pain point selection
        const painPointCheckboxes = this.form.querySelectorAll('input[name="pain_points[]"]');
        painPointCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => this.handlePainPointSelection(e));
        });

        // Field validation
        const fields = this.form.querySelectorAll('input, select');
        fields.forEach(field => {
            field.addEventListener('blur', () => this.validateField(field));
            field.addEventListener('input', () => this.clearFieldError(field));
        });

        console.log('Events bound successfully');
    }

    unbindEvents() {
        // Clean up any existing event listeners to prevent duplicates
        if (this.nextBtn) {
            this.nextBtn.replaceWith(this.nextBtn.cloneNode(true));
            this.nextBtn = this.form.querySelector('.rtbcb-nav-next');
        }
        if (this.prevBtn) {
            this.prevBtn.replaceWith(this.prevBtn.cloneNode(true));
            this.prevBtn = this.form.querySelector('.rtbcb-nav-prev');
        }
    }

    nextStep() {
        console.log(`Attempting to go from step ${this.currentStep} to ${this.currentStep + 1}`);

        if (!this.validateCurrentStep()) {
            console.log('Validation failed for current step');
            return;
        }

        this.saveStepData();

        if (this.currentStep < this.totalSteps) {
            this.currentStep++;
            this.updateStepDisplay();
            this.updateNavigationState();
            this.updateProgress();
            console.log(`Successfully moved to step ${this.currentStep}`);
        }
    }

    previousStep() {
        console.log(`Going back from step ${this.currentStep} to ${this.currentStep - 1}`);

        if (this.currentStep > 1) {
            this.currentStep--;
            this.updateStepDisplay();
            this.updateNavigationState();
            this.updateProgress();
        }
    }

    updateStepDisplay() {
        this.steps.forEach((step, index) => {
            const stepNumber = index + 1;
            step.classList.remove('active', 'prev');

            if (stepNumber === this.currentStep) {
                step.classList.add('active');
            } else if (stepNumber < this.currentStep) {
                step.classList.add('prev');
            }
        });
    }

    updateNavigationState() {
        if (this.prevBtn) {
            this.prevBtn.style.display = this.currentStep === 1 ? 'none' : 'flex';
        }

        if (this.nextBtn && this.submitBtn) {
            if (this.currentStep === this.totalSteps) {
                this.nextBtn.style.display = 'none';
                this.submitBtn.style.display = 'flex';
            } else {
                this.nextBtn.style.display = 'flex';
                this.submitBtn.style.display = 'none';
            }
        }
    }

    showFallbackSubmit() {
        if (this.fallbackSubmit) {
            this.fallbackSubmit.style.display = 'inline-flex';
        }
        if (this.nextBtn) {
            this.nextBtn.style.display = 'none';
        }
        if (this.prevBtn) {
            this.prevBtn.style.display = 'none';
        }
        if (this.submitBtn) {
            this.submitBtn.style.display = 'none';
        }
    }

    updateProgress() {
        this.progressSteps.forEach((step, index) => {
            const stepNumber = index + 1;
            step.classList.remove('active', 'completed');

            if (stepNumber === this.currentStep) {
                step.classList.add('active');
            } else if (stepNumber < this.currentStep) {
                step.classList.add('completed');
            }
        });
    }

    validateCurrentStep() {
        const currentStepElement = this.steps[this.currentStep - 1];
        if (!currentStepElement) {
            console.error('Current step element not found');
            return false;
        }

        const requiredFields = currentStepElement.querySelectorAll('input[required], select[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        // Special validation for step 3 (pain points)
        if (this.currentStep === 3) {
            const painPoints = currentStepElement.querySelectorAll('input[name="pain_points[]"]:checked');
            if (painPoints.length === 0) {
                this.showPainPointsError();
                isValid = false;
            } else {
                this.hidePainPointsError();
            }
        }

        // Special validation for step 4 (consent)
        if (this.currentStep === 4) {
            const consent = currentStepElement.querySelector('input[name="consent"]');
            if (consent && !consent.checked) {
                this.showFieldError(consent, 'Please agree to receive your business case report.');
                isValid = false;
            }
        }

        return isValid;
    }

    validateField(field) {
        if (!field) return false;
        
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

    handlePainPointSelection(e) {
        const card = e.target.closest('.rtbcb-pain-point-card');
        if (card) {
            if (e.target.checked) {
                card.classList.add('rtbcb-selected');
            } else {
                card.classList.remove('rtbcb-selected');
            }
        }
        
        this.hidePainPointsError();
    }

    showPainPointsError() {
        const validationMessage = this.form.querySelector('.rtbcb-validation-message');
        if (validationMessage) {
            validationMessage.style.display = 'block';
        }
    }

    hidePainPointsError() {
        const validationMessage = this.form.querySelector('.rtbcb-validation-message');
        if (validationMessage) {
            validationMessage.style.display = 'none';
        }
    }

    saveStepData() {
        const currentStepElement = this.steps[this.currentStep - 1];
        if (!currentStepElement) return;

        const fields = currentStepElement.querySelectorAll('input, select');
        
        fields.forEach(field => {
            if (field.type === 'checkbox') {
                if (field.name === 'pain_points[]') {
                    if (!this.stepData['pain_points']) {
                        this.stepData['pain_points'] = [];
                    }
                    if (field.checked && !this.stepData['pain_points'].includes(field.value)) {
                        this.stepData['pain_points'].push(field.value);
                    } else if (!field.checked) {
                        const index = this.stepData['pain_points'].indexOf(field.value);
                        if (index > -1) {
                            this.stepData['pain_points'].splice(index, 1);
                        }
                    }
                } else {
                    this.stepData[field.name] = field.checked;
                }
            } else {
                this.stepData[field.name] = field.value;
            }
        });
    }

    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Reinitialize when modal opens
    reinitialize() {
        this.isInitialized = false;
        this.currentStep = 1;
        this.stepData = {};
        this.init();
    }

    // Continue with existing methods (handleSubmit, etc.)...
    // [Rest of the methods remain the same]
    async handleSubmit(e) {
        e.preventDefault();

        if (!this.validateCurrentStep()) {
            return;
        }

        this.saveStepData();

        try {
            this.showProgressIndicator();
            this.disableNavigation();

            const formData = new FormData(this.form);
            formData.append('action', 'rtbcb_generate_case');
            formData.set('rtbcb_nonce', ajaxObj.rtbcb_nonce);

            this.startProgressSimulation();

            const response = await fetch(ajaxObj.ajax_url, {
                method: 'POST',
                body: new URLSearchParams(formData)
            });

            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                this.showError('An unexpected server response was received. Please try again.');
                console.error('Invalid JSON response:', responseText);
                return;
            }

            const data = result.data || {};
            const narrativeError = data?.narrative?.error;
            if (narrativeError) {
                this.hideProgressIndicator();
                this.enableNavigation();
                this.showError(narrativeError);
                return;
            }

            if (result.success) {
                this.completeProgress();
                this.displayResults(result.data);
                this.showSuccess(result.data.download_url);
                if (this.form) {
                    this.form.style.display = 'none';
                }
                this.trackAnalytics('business_case_generated', {
                    category: result.data.recommendation?.recommended,
                    roi_base: result.data.scenarios?.base?.total_annual_benefit
                });
            } else {
                this.showError(result.data || 'An unknown error occurred.');
            }

        } catch (error) {
            this.showError('A network error occurred. Please try again.');
            console.error('Submission Error:', error);
        } finally {
            this.hideProgressIndicator();
            this.enableNavigation();
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

        document.body.insertAdjacentHTML('beforeend', progressHtml);
        this.progressOverlay = document.querySelector('.rtbcb-progress-overlay');
    }

    startProgressSimulation() {
        const steps = [
            'Calculating ROI scenarios...',
            'Analyzing your requirements...',
            'Determining best solution category...',
            'Researching vendor data...',
            'Generating business narrative...',
            'Creating your custom report...'
        ];

        let currentStep = 0;
        this.progressInterval = setInterval(() => {
            if (currentStep < steps.length) {
                this.updateProgressStep(steps[currentStep]);
                currentStep++;
            }
        }, 2000);
    }

    updateProgressStep(stepText) {
        const stepElement = document.querySelector('.rtbcb-progress-overlay .rtbcb-progress-step');
        if (stepElement) {
            stepElement.textContent = stepText;
        }
    }

    completeProgress() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }

        const stepElement = document.querySelector('.rtbcb-progress-overlay .rtbcb-progress-step');
        if (stepElement) {
            stepElement.textContent = 'Analysis complete!';
        }

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

    disableNavigation() {
        this.nextBtn.disabled = true;
        this.prevBtn.disabled = true;
        this.submitBtn.disabled = true;
        this.submitBtn.classList.add('loading');
    }

    enableNavigation() {
        this.nextBtn.disabled = false;
        this.prevBtn.disabled = false;
        this.submitBtn.disabled = false;
        this.submitBtn.classList.remove('loading');
    }

    showSuccess(downloadUrl) {
        const container = document.getElementById('rtbcbSuccessMessage');
        if (!container) {
            return;
        }

        let message = 'Your business case is ready!';
        if (downloadUrl) {
            const url = this.escapeHtml(downloadUrl);
            message += ` <a href="${url}" target="_blank" rel="noopener noreferrer">Download your report</a>`;
        }

        container.innerHTML = message;
        container.style.display = 'block';
    }

    showError(message) {
        const errorHtml = `<div class="rtbcb-error">${this.escapeHtml(message)}</div>`;
        this.form.insertAdjacentHTML('afterend', errorHtml);

        setTimeout(() => {
            const errorElement = document.querySelector('.rtbcb-error');
            if (errorElement) {
                errorElement.remove();
            }
        }, 5000);
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

        this.createBenefitChart(scenarios.base);

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

    async createBenefitChart(baseScenario) {
        if (!baseScenario) {
            return;
        }

        const canvas = document.getElementById('rtbcbBenefitChart');
        if (!canvas) {
            return;
        }
        const ctx = canvas.getContext('2d');

        const data = {
            labels: ['Labor Savings', 'Bank Fee Reduction', 'Error Reduction'],
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

        const renderChart = () => {
            try {
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
            } catch (error) {
                console.error('Chart rendering error:', error);
                const message = document.createElement('div');
                message.textContent = 'Chart unavailable';
                canvas.replaceWith(message);
            }
        };

        if (typeof Chart === 'undefined') {
            try {
                const module = await import('https://cdn.jsdelivr.net/npm/chart.js');
                window.Chart = module.default;
                renderChart();
            } catch (error) {
                console.error('Chart.js failed to load:', error);
                const message = document.createElement('div');
                message.textContent = 'Chart unavailable';
                canvas.replaceWith(message);
            }
            return;
        }

        renderChart();
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

    trackAnalytics(event, data) {
        if (typeof gtag !== 'undefined') {
            gtag('event', event, {
                ...data,
                event_category: 'business_case_builder'
            });
        }
    }
}

// Modal control functions
function openBusinessCaseModal() {
    const overlay = document.getElementById('rtbcbModalOverlay');
    if (overlay) {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Initialize builder if not already done
        if (!window.businessCaseBuilder) {
            window.businessCaseBuilder = new BusinessCaseBuilder();
        }
    }
}

function closeBusinessCaseModal() {
    const overlay = document.getElementById('rtbcbModalOverlay');
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal on overlay click
document.addEventListener('click', (e) => {
    const overlay = document.getElementById('rtbcbModalOverlay');
    if (e.target === overlay) {
        closeBusinessCaseModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeBusinessCaseModal();
    }
});

// Initialize with better error handling
document.addEventListener('DOMContentLoaded', () => {
    try {
        window.businessCaseBuilder = new BusinessCaseBuilder();
    } catch (error) {
        console.error('Failed to initialize Business Case Builder:', error);
    }
});
