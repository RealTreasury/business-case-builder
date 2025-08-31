/**
 * Business Case Builder Wizard Controller - FIXED VERSION
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
        
        // Form fields by step
        this.stepFields = {
            1: ['company_name', 'company_size', 'industry'],
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
                try {
                    this.handleSubmit(e);
                } catch (error) {
                    console.error('RTBCB: handleSubmit error:', error);
                    this.showEnhancedError('An unexpected error occurred. Please try again.');
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

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Required field check
        if (field.hasAttribute('required') && !value) {
            errorMessage = 'This field is required';
            isValid = false;
        }

        // Company name validation
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

        this.clearFieldError(field);
        field.classList.add('rtbcb-field-invalid');

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
                this.submitBtn.style.display = 'inline-flex';
                this.submitBtn.disabled = false;
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
            this.showEnhancedError('Service unavailable. Please reload the page.');
            return;
        }

        try {
            console.log('RTBCB: Starting form submission');
            this.showLoading();

            const formData = this.collectFormData();
            this.validateFormData(formData);

            // SIMPLIFIED APPROACH: Direct submission instead of background jobs
            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json, text/html'
                }
            });

            console.log('RTBCB: Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const responseText = await response.text();
            console.log('RTBCB: Response received, length:', responseText.length);

            // Try to parse as JSON first
            let data;
            try {
                data = JSON.parse(responseText);
                console.log('RTBCB: Parsed JSON response:', data);
            } catch (parseError) {
                console.log('RTBCB: Response is not JSON, treating as HTML');
                // If it's not JSON, it might be the direct HTML report
                if (responseText.includes('<div class="rtbcb-enhanced-report"') ||
                    responseText.includes('<div class="rtbcb-report"')) {
                    this.showEnhancedHTMLReport(responseText);
                    return;
                } else {
                    throw new Error('Invalid response format');
                }
            }

            // Handle JSON response
            if (data.success) {
                if (data.data && data.data.job_id) {
                    const redirectUrl = (typeof rtbcbAjax !== 'undefined' && rtbcbAjax.processing_url)
                        ? `${rtbcbAjax.processing_url}?job_id=${encodeURIComponent(data.data.job_id)}`
                        : `?job_id=${encodeURIComponent(data.data.job_id)}`;
                    window.location.href = redirectUrl;
                    return;
                } else if (data.data) {
                    // Direct HTML or structured data response
                    if (data.data.report_html) {
                        this.handleSuccess(data.data);
                    } else if (data.data.report_data) {
                        this.handleSuccess(data.data.report_data);
                    } else {
                        this.handleSuccess(data.data);
                    }
                } else {
                    throw new Error('No report data received');
                }
            } else {
                console.error('RTBCB: Error response:', data.data);
                this.handleError(data.data || { message: 'Unknown error occurred' });
            }

        } catch (error) {
            console.error('RTBCB: Submission error:', error);
            this.handleError({
                message: error.message || 'An unexpected error occurred',
                type: 'submission_error'
            });
        } finally {
            // Don't hide loading here - let success/error handlers do it
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
        const fastMode = this.form.querySelector('#fast_mode');
        formData.append('fast_mode', fastMode && fastMode.checked ? '1' : '0');
        return formData;
    }

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

    showLoading() {
        // Hide form
        const formContainer = ( this.form && typeof this.form.closest === 'function' ) ? this.form.closest('.rtbcb-form-container') : null;
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
                        <span class="rtbcb-progress-step-text" id="rtbcb-progress-status">Analyzing ${this.escapeHTML(companyName)}'s treasury operations...</span>
                    </div>
                </div>
            `;
            progressContainer.style.display = 'flex';
        }
    }

    hideLoading() {
        const progressContainer = document.getElementById('rtbcb-progress-container');
        if (progressContainer) {
            progressContainer.style.display = 'none';
            progressContainer.innerHTML = '';
        }

        const formContainer = ( this.form && typeof this.form.closest === 'function' ) ? this.form.closest('.rtbcb-form-container') : null;
        if (formContainer) {
            formContainer.style.display = 'block';
        }
    }

    async pollJob(jobId, startTime = Date.now(), attempt = 0) {
        const MAX_DURATION = 20 * 60 * 1000; // 20 minutes
        const MAX_ATTEMPTS = 600; // 600 attempts * 2s = 20 minutes max
        
        if (Date.now() - startTime > MAX_DURATION || attempt > MAX_ATTEMPTS) {
            this.handleError({ 
                message: 'The request timed out after 20 minutes. Please try again later.', 
                type: 'timeout' 
            });
            return;
        }
        
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
            
            const statusData = data.data;
            const status = statusData.status;
            console.log(`RTBCB: Job status: ${status} (attempt ${attempt})`);

            const progressStatus = document.getElementById('rtbcb-progress-status');
            const progressMessage = statusData.step || statusData.message;
            if (progressStatus && progressMessage) {
                progressStatus.textContent = progressMessage;
            }

            if (status === 'completed') {
                this.hideLoading();
                if (statusData.report_html) {
                    this.handleSuccess(statusData);
                } else if (statusData.report_data) {
                    this.handleSuccess(statusData.report_data);
                } else {
                    this.handleError({ message: 'Report data missing from completed job', type: 'job_error' });
                }
            } else if (status === 'error') {
                this.hideLoading();
                this.handleError({ message: statusData.message || 'Job failed', type: 'job_error' });
            } else {
                // Continue polling
                setTimeout(() => this.pollJob(jobId, startTime, attempt + 1), 2000);
            }
        } catch (error) {
            console.error('RTBCB: Job polling error:', error);
            this.handleError({ message: error.message || 'An unexpected error occurred', type: 'polling_error' });
        }
    }

    handleSuccess(data) {
        console.log('RTBCB: Success data received:', data);

        if (typeof data === 'string') {
            this.showEnhancedHTMLReport(data);
            return;
        }

        // Check if we have HTML report data
        if (data.report_html && data.report_html.trim()) {
            this.showEnhancedHTMLReport(data.report_html);
        } else {
            // Fallback to structured data rendering
            this.showResults(data);
        }
    }

    showEnhancedHTMLReport(htmlContent) {
        console.log('RTBCB: Displaying enhanced HTML report');
        this.hideLoading();

        // Close modal
        window.closeBusinessCaseModal();

        // Create or find results container
        let resultsContainer = document.getElementById('rtbcb-results-enhanced');
        if (!resultsContainer) {
            console.log('RTBCB: Creating results container');
            resultsContainer = document.createElement('div');
            resultsContainer.id = 'rtbcb-results-enhanced';
            resultsContainer.className = 'rtbcb-enhanced-results-container';

            // Insert after the modal
            const modal = document.getElementById('rtbcbModalOverlay');
            if (modal && modal.parentNode) {
                modal.parentNode.insertBefore(resultsContainer, modal.nextSibling);
                console.log('RTBCB: Results container inserted after modal');
            } else {
                document.body.appendChild(resultsContainer);
                console.log('RTBCB: Results container appended to body');
            }
        } else {
            console.log('RTBCB: Reusing existing results container');
        }

        // Set content and make visible
        console.log('RTBCB: Injecting report content');
        resultsContainer.innerHTML = htmlContent;
        resultsContainer.style.display = 'block';

        // Initialize interactive features for the enhanced report
        this.initializeEnhancedReport(resultsContainer);

        // Smooth scroll to results
        resultsContainer.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }

    initializeEnhancedReport(container) {
        // Initialize Chart.js charts
        this.initializeReportCharts(container);

        // Initialize collapsible sections
        this.initializeCollapsibleSections(container);

        // Initialize interactive metrics
        this.initializeInteractiveMetrics(container);

        // Add print and export functionality
        this.initializeExportFunctions(container);
    }

    initializeReportCharts(container) {
        const chartCanvas = container.querySelector('#rtbcb-roi-chart');
        if (!chartCanvas) return;

        console.log('RTBCB: Initializing ROI chart');

        if (typeof Chart === 'undefined') {
            console.warn('RTBCB: Chart.js library is not available');
            return;
        }

        try {
            // Get chart data from the page or from localized data
            const roiData = this.extractROIDataFromReport(container);
            console.log('RTBCB: Extracted ROI data', roiData);

            if (!roiData || Object.keys(roiData).length === 0) {
                console.warn('RTBCB: ROI data is empty. Chart will not be rendered');
                return;
            }

            this.createROIChart(chartCanvas, roiData);
        } catch (error) {
            console.error('Failed to initialize chart:', error);
        }
    }

    extractROIDataFromReport(container) {
        const roiData = {};

        // Look for scenario data in the DOM
        const scenarioCards = container.querySelectorAll('.rtbcb-scenario-card');
        scenarioCards.forEach(card => {
            const scenarioName = this.getScenarioNameFromCard(card);
            if (scenarioName) {
                roiData[scenarioName] = this.extractMetricsFromCard(card);
            }
        });

        return roiData;
    }

    getScenarioNameFromCard(card) {
        if (card.classList.contains('conservative')) return 'conservative';
        if (card.classList.contains('base')) return 'base';
        if (card.classList.contains('optimistic')) return 'optimistic';

        // Try to extract from heading
        const heading = card.querySelector('h4');
        if (heading) {
            const text = heading.textContent.toLowerCase();
            if (text.includes('conservative')) return 'conservative';
            if (text.includes('base')) return 'base';
            if (text.includes('optimistic')) return 'optimistic';
        }

        return null;
    }

    extractMetricsFromCard(card) {
        const metrics = {};

        const metricElements = card.querySelectorAll('.rtbcb-scenario-metric');
        metricElements.forEach(metric => {
            const label = metric.querySelector('.rtbcb-metric-label');
            const value = metric.querySelector('.rtbcb-metric-value');

            if (label && value) {
                const labelText = label.textContent.trim();
                const valueText = value.textContent.replace(/[$,]/g, '').trim();
                const numValue = parseFloat(valueText) || 0;

                if (labelText.includes('Total Annual')) {
                    metrics.total_annual_benefit = numValue;
                } else if (labelText.includes('Labor')) {
                    metrics.labor_savings = numValue;
                } else if (labelText.includes('Fee')) {
                    metrics.fee_savings = numValue;
                } else if (labelText.includes('Error')) {
                    metrics.error_reduction = numValue;
                }
            }
        });

        return metrics;
    }

    createROIChart(canvas, roiData) {
        console.log('RTBCB: Creating ROI chart');
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            console.error('RTBCB: Failed to get canvas context for ROI chart.');
            if (canvas.parentNode) {
                const message = window.wp?.i18n?.__('Chart rendering failed', 'rtbcb') || 'Chart rendering failed';
                const errorEl = document.createElement('p');
                errorEl.textContent = message;
                errorEl.classList.add('rtbcb-chart-error');
                canvas.parentNode.insertBefore(errorEl, canvas);
                canvas.remove();
            }
            return;
        }

        const chartData = {
            labels: ['Labor Savings', 'Fee Savings', 'Error Reduction', 'Total Benefit'],
            datasets: []
        };

        const colors = {
            conservative: { bg: 'rgba(239, 68, 68, 0.8)', border: 'rgba(239, 68, 68, 1)' },
            base: { bg: 'rgba(59, 130, 246, 0.8)', border: 'rgba(59, 130, 246, 1)' },
            optimistic: { bg: 'rgba(16, 185, 129, 0.8)', border: 'rgba(16, 185, 129, 1)' }
        };

        // Create datasets for each scenario
        Object.keys(roiData).forEach(scenario => {
            const data = roiData[scenario];
            const color = colors[scenario] || colors.base;

            chartData.datasets.push({
                label: this.formatScenarioLabel(scenario),
                data: [
                    data.labor_savings || 0,
                    data.fee_savings || 0,
                    data.error_reduction || 0,
                    data.total_annual_benefit || 0
                ],
                backgroundColor: color.bg,
                borderColor: color.border,
                borderWidth: 1
            });
        });

        new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + new Intl.NumberFormat().format(value);
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + 
                                       new Intl.NumberFormat().format(context.raw);
                            }
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
        console.log('RTBCB: ROI chart created');
    }

    formatScenarioLabel(scenario) {
        switch (scenario) {
            case 'conservative': return 'Conservative';
            case 'base': return 'Base Case';
            case 'optimistic': return 'Optimistic';
            default: return scenario.charAt(0).toUpperCase() + scenario.slice(1);
        }
    }

    initializeCollapsibleSections(container) {
        const toggles = container.querySelectorAll('.rtbcb-section-toggle');
        toggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const content = document.getElementById(targetId);
                const arrow = this.querySelector('.rtbcb-toggle-arrow');
                const text = this.querySelector('.rtbcb-toggle-text');

                if (content) {
                    const isHidden = content.style.display === 'none';
                    content.style.display = isHidden ? 'block' : 'none';

                    if (arrow) {
                        arrow.textContent = isHidden ? '▲' : '▼';
                    }
                    if (text) {
                        text.textContent = isHidden ? 'Collapse' : 'Expand';
                    }
                }
            });
        });
    }

    initializeInteractiveMetrics(container) {
        const metricCards = container.querySelectorAll('.rtbcb-metric-card');
        metricCards.forEach(card => {
            card.addEventListener('click', function() {
                this.classList.toggle('expanded');
            });
        });
    }

    initializeExportFunctions(container) {
        // Print button
        const printButtons = container.querySelectorAll('[onclick*="print"], .rtbcb-print-btn');
        printButtons.forEach(btn => {
            btn.onclick = null; // Remove inline handler
            btn.addEventListener('click', () => {
                window.print();
            });
        });

        // PDF export button (same as print for now)
        const pdfButtons = container.querySelectorAll('.rtbcb-export-pdf, .rtbcb-pdf-btn');
        pdfButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                window.print(); // Browser will handle PDF export
            });
        });
    }

    showEnhancedError(message, details = null) {
        this.hideLoading();

        const progressContainer = document.getElementById('rtbcb-progress-container');
        if (progressContainer) {
            progressContainer.innerHTML = `
                <div class="rtbcb-enhanced-error" style="padding: 40px; text-align: center; max-width: 600px;">
                    <div class="rtbcb-error-icon" style="font-size: 48px; color: #ef4444; margin-bottom: 20px;">
                        ⚠️
                    </div>
                    <h3 style="color: #ef4444; margin-bottom: 16px; font-size: 24px;">
                        Unable to Generate Dashboard Report
                    </h3>
                    <p style="color: #4b5563; margin-bottom: 24px; font-size: 16px; line-height: 1.5;">
                        ${this.escapeHTML(message)}
                    </p>
                    ${details ? `
                    <details style="margin-bottom: 24px; text-align: left;">
                        <summary style="cursor: pointer; color: #7c3aed; font-weight: 600;">
                            Technical Details
                        </summary>
                        <pre style="background: #f3f4f6; padding: 16px; border-radius: 8px; font-size: 14px; margin-top: 8px; overflow-x: auto;">
                            ${this.escapeHTML(JSON.stringify(details, null, 2))}
                        </pre>
                    </details>
                    ` : ''}
                    <div class="rtbcb-error-actions" style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        <button type="button" onclick="location.reload()" 
                                style="background: #7216f4; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            Try Again
                        </button>
                        <a href="mailto:contact@realtreasury.com" 
                           style="background: #f3f4f6; color: #4b5563; border: none; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                            Contact Support
                        </a>
                    </div>
                </div>
            `;
            progressContainer.style.display = 'flex';
            progressContainer.style.alignItems = 'center';
            progressContainer.style.justifyContent = 'center';
        }
    }

    showResults(data) {
        console.log('RTBCB: Processing structured data results');
        this.hideLoading();
        
        // Map nested report data to expected structure
        const mapped = {
            companyName: data.company_name || data.metadata?.company_name || 'Your Company',
            scenarios: data.scenarios || data.financial_analysis?.roi_scenarios || {},
            recommendation: {
                category_info: data.recommendation?.category_info || data.technology_strategy?.category_details || {},
                confidence: data.recommendation?.confidence || data.metadata?.confidence_level || 0.75,
                reasoning: data.recommendation?.reasoning || data.technology_strategy?.recommended_category || ''
            },
            narrative: {
                narrative: data.narrative?.narrative || data.executive_summary?.executive_recommendation || '',
                next_actions: data.narrative?.next_actions || [
                    ...(data.action_plan?.immediate_steps || []),
                    ...(data.action_plan?.short_term_milestones || []),
                    ...(data.action_plan?.long_term_objectives || [])
                ]
            },
            risks: data.risks || data.risk_analysis?.implementation_risks || []
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
        } else {
            console.error('RTBCB: Results container not found');
            this.showEnhancedError('Unable to display results. Please refresh the page.');
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
                        <div class="rtbcb-scenario-amount">$${this.formatNumber(scenarios.low?.total_annual_benefit || scenarios.conservative?.total_annual_benefit || 0)}</div>
                        <div class="rtbcb-scenario-confidence">80% probability</div>
                    </div>
                    <div class="rtbcb-scenario rtbcb-scenario-base">
                        <div class="rtbcb-scenario-label">Base Case</div>
                        <div class="rtbcb-scenario-amount">$${this.formatNumber(scenarios.base?.total_annual_benefit || 0)}</div>
                        <div class="rtbcb-scenario-confidence">Most likely outcome</div>
                    </div>
                    <div class="rtbcb-scenario">
                        <div class="rtbcb-scenario-label">Optimistic</div>
                        <div class="rtbcb-scenario-amount">$${this.formatNumber(scenarios.high?.total_annual_benefit || scenarios.optimistic?.total_annual_benefit || 0)}</div>
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

    copyResultsHTML() {
        const container = document.querySelector('.rtbcb-results-container');
        if (!container || !navigator.clipboard) {
            return;
        }
        navigator.clipboard.writeText(container.innerHTML)
            .then(() => alert('Results HTML copied to clipboard'))
            .catch(err => console.error('Copy failed:', err));
    }

    handleError(errorData) {
        const message = errorData.message || 'An unexpected error occurred';

        console.group('RTBCB Error Details');
        console.error('Message:', message);
        console.error('Type:', errorData.type);
        console.error('Debug Info:', errorData.debug_info);
        console.error('Timestamp:', errorData.timestamp);
        console.groupEnd();

        this.showEnhancedError(this.getUserFriendlyMessage(message), errorData);
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
