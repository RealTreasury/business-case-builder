/**
 * Business Case Builder Wizard Controller - FIXED VERSION
 * Handles multi-step form navigation, validation, and submission
 */

// Ensure Chart.js date adapter is registered in Node environments.
if ( typeof require === 'function' ) {
    try {
        require( './public/js/chartjs-adapter-date-fns.bundle.min.js' );
    } catch ( e ) {
        // Adapter not loaded in tests; ignore.
    }
}

/* global wp */
const __ = ( typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ) ? wp.i18n.__ : ( s ) => s;

/**
 * Check if a URL uses http or https scheme.
 *
 * @param {string} url URL to validate.
 * @return {boolean} True if URL is valid.
 */
function isValidUrl(url) {
    if (!url) {
        return false;
    }
    try {
        const parsed = new URL(url);
        return parsed.protocol === 'http:' || parsed.protocol === 'https:';
    } catch (e) {
        return false;
    }
}

// Normalize global AJAX URL if provided.
if ( typeof rtbcb_ajax !== 'undefined' && ! isValidUrl( rtbcb_ajax.ajax_url ) ) {
    if ( typeof ajaxurl !== 'undefined' && isValidUrl( ajaxurl ) ) {
        rtbcb_ajax.ajax_url = ajaxurl;
    }
}

/**
 * Refresh the AJAX nonce.
 *
 * @return {Promise<boolean>} True on success.
 */
async function rtbcbRefreshNonce() {
    if ( typeof rtbcb_ajax === 'undefined' || ! isValidUrl( rtbcb_ajax.ajax_url ) ) {
        return false;
    }
    try {
        const response = await fetch( `${ rtbcb_ajax.ajax_url }?action=rtbcb_get_nonce` );
        if ( ! response.ok ) {
            return false;
        }
        const data = await response.json();
        if ( data && data.success ) {
            rtbcb_ajax.nonce = data.data || data.nonce || data;
            return true;
        }
    } catch ( err ) {
        console.error( 'RTBCB: Nonce refresh failed', err );
    }
    return false;
}

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
    if (overlay && overlay.classList && overlay.classList.remove) {
        overlay.classList.remove('active');
    }
    if (document.body && document.body.style) {
        document.body.style.overflow = '';
    }
    if (
        window.businessCaseBuilder &&
        typeof window.businessCaseBuilder.cancelPolling === 'function'
    ) {
        window.businessCaseBuilder.cancelPolling();
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
        this.ajaxUrl = ( typeof rtbcb_ajax !== 'undefined' && isValidUrl( rtbcb_ajax.ajax_url ) ) ? rtbcb_ajax.ajax_url : '';
        this.pollTimeout = null;
        this.pollingCancelled = false;
        this.activeJobId = null;
        this.progressStates = [];
        this.currentProgressIndex = 0;
        this.progressTimer = null;

        if ( ! this.form ) {
            return;
        }

        // Bind event handlers to maintain context when used as callbacks.
        this.handleNext = this.handleNext.bind( this );
        this.handlePrev = this.handlePrev.bind( this );
        this.handleSubmit = this.handleSubmit.bind( this );

        this.init();
    }

    init() {
        this.cacheElements();
        this.bindEvents();
        this.updateStepVisibility();
        this.updateProgressIndicator();
        this.restorePersistentState();
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
        if ( this.nextBtn ) {
            this.nextBtn.addEventListener( 'click', this.handleNext );
        }

        if ( this.prevBtn ) {
            this.prevBtn.addEventListener( 'click', this.handlePrev );
        }

        // Form submission
        this.form.addEventListener( 'submit', this.handleSubmit );

// Pain point cards
        this.form.addEventListener('change', (event) => {
            const target = event.target;
            if (target.matches('input[name="pain_points[]"]')) {
                const card = target.closest('.rtbcb-pain-point-card');
                if (card) {
                    card.classList.toggle('rtbcb-selected', target.checked);
                }
                const checkedBoxes = this.form.querySelectorAll('input[name="pain_points[]"]:checked');
                if (checkedBoxes.length > 0) {
                    this.clearStepError(3);
                }
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

    saveFormData( formData ) {
        try {
            const storage = window.sessionStorage;
            if ( ! storage ) {
                return;
            }
            const data = {};
            for ( const [ key, value ] of formData.entries() ) {
                if ( data[ key ] ) {
                    if ( Array.isArray( data[ key ] ) ) {
                        data[ key ].push( value );
                    } else {
                        data[ key ] = [ data[ key ], value ];
                    }
                } else {
                    data[ key ] = value;
                }
            }
            storage.setItem( 'rtbcbFormData', JSON.stringify( data ) );
        } catch ( e ) {
            console.warn( 'RTBCB: Session storage unavailable', e );
        }
    }

    restorePersistentState() {
        try {
            const storage = window.sessionStorage;
            if ( ! storage ) {
                return;
            }
            const reportHtml = storage.getItem( 'rtbcbFinalReport' );
            if ( reportHtml ) {
                this.showEnhancedHTMLReport( reportHtml );
                return;
            }
            const savedData = storage.getItem( 'rtbcbFormData' );
            if ( savedData ) {
                try {
                    const parsed = JSON.parse( savedData );
                    this.populateForm( parsed );
                } catch ( err ) {}
            }
            const jobId = storage.getItem( 'rtbcbJobId' );
            if ( jobId ) {
                this.activeJobId = jobId;
                this.showLoading();
                this.startProgressiveLoading();
                this.pollingCancelled = false;
                this.pollJob( jobId, Date.now(), 0 );
            }
        } catch ( e ) {
            console.warn( 'RTBCB: Session storage unavailable', e );
        }
    }

    populateForm( data ) {
        Object.entries( data ).forEach( ( [ key, value ] ) => {
            if ( Array.isArray( value ) ) {
                value.forEach( ( val ) => {
                    const field = this.form.querySelector( `[name="${ key }"][value="${ val }"]` );
                    if ( field ) {
                        field.checked = true;
                    }
                } );
            } else {
                const field = this.form.querySelector( `[name="${ key }"]` );
                if ( ! field ) {
                    return;
                }
                if ( field.type === 'checkbox' ) {
                    field.checked = true;
                } else if ( field.type === 'radio' ) {
                    const radio = this.form.querySelector( `[name="${ key }"][value="${ value }"]` );
                    if ( radio ) {
                        radio.checked = true;
                    }
                } else {
                    field.value = value;
                }
            }
        } );
    }

    clearPersistentState() {
        try {
            const storage = window.sessionStorage;
            if ( storage ) {
                storage.removeItem( 'rtbcbFormData' );
                storage.removeItem( 'rtbcbJobId' );
            }
        } catch ( e ) {}
    }

    saveFinalReport( html ) {
        try {
            const storage = window.sessionStorage;
            if ( storage ) {
                storage.setItem( 'rtbcbFinalReport', html );
            }
        } catch ( e ) {
            console.warn( 'RTBCB: Session storage unavailable', e );
        }
    }

    handleNext( event ) {
        if ( event && event.preventDefault ) {
            event.preventDefault();
        }

        if ( this.validateStep( this.currentStep ) ) {
            if ( this.currentStep < this.totalSteps ) {
                this.currentStep++;
                this.updateStepVisibility();
                this.updateProgressIndicator();
                this.scrollToTop();
            }
        }
    }

    handlePrev( event ) {
        if ( event && event.preventDefault ) {
            event.preventDefault();
        }

        if ( this.currentStep > 1 ) {
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
                this.showStepError(3, __( 'Please select at least one challenge', 'rtbcb' ) );
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
            errorMessage = __( 'This field is required', 'rtbcb' );
            isValid = false;
        }

        // Company name validation
        if (field.name === 'company_name' && value) {
            if (value.length < 2) {
                errorMessage = __( 'Company name must be at least 2 characters', 'rtbcb' );
                isValid = false;
            } else if (value.length > 100) {
                errorMessage = __( 'Company name must be less than 100 characters', 'rtbcb' );
                isValid = false;
            } else if (/^[^a-zA-Z]*$/.test(value)) {
                errorMessage = __( 'Please enter a valid company name', 'rtbcb' );
                isValid = false;
            }
        }

        // Email validation
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                errorMessage = __( 'Please enter a valid email address', 'rtbcb' );
                isValid = false;
            }
        }

        // Number validation
        if (field.type === 'number' && value) {
            if (!Number.isFinite(Number(value))) {
                errorMessage = __( 'Please enter a valid number', 'rtbcb' );
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

    // Progressive loading states
    initializeProgressStates() {
        this.progressStates = [
            { step: 'initializing', message: __( 'Initializing analysis...', 'rtbcb' ), duration: 2000 },
            { step: 'collecting_data', message: __( 'Collecting your data...', 'rtbcb' ), duration: 3000 },
            { step: 'calculating_roi', message: __( 'Calculating ROI scenarios...', 'rtbcb' ), duration: 8000 },
            { step: 'analyzing_company', message: __( 'Analyzing company profile...', 'rtbcb' ), duration: 6000 },
            { step: 'market_research', message: __( 'Researching market insights...', 'rtbcb' ), duration: 5000 },
            { step: 'generating_recommendations', message: __( 'Generating recommendations...', 'rtbcb' ), duration: 10000 },
            { step: 'finalizing', message: __( 'Finalizing your report...', 'rtbcb' ), duration: 5000 }
        ];
        this.currentProgressIndex = 0;
        this.progressTimer = null;
    }

    startProgressiveLoading() {
        this.initializeProgressStates();
        this.currentProgressIndex = 0;
        this.showProgressState(this.progressStates[0]);
        this.scheduleNextProgressState();
    }

    showProgressState(state) {
        const statusElement = document.getElementById('rtbcb-progress-status');
        if (!statusElement) {
            return;
        }

        statusElement.style.transition = 'opacity 0.3s ease';
        statusElement.style.opacity = '0.5';

        setTimeout(() => {
            statusElement.textContent = state.message;
            statusElement.style.opacity = '1';

            statusElement.style.transform = 'scale(1.02)';
            setTimeout(() => {
                statusElement.style.transform = 'scale(1)';
            }, 200);
        }, 300);

        console.log(`RTBCB: Progress state: ${state.step} - ${state.message}`);
    }

    scheduleNextProgressState() {
        if (this.pollingCancelled) {
            return;
        }

        const currentState = this.progressStates[this.currentProgressIndex];
        if (!currentState) {
            return;
        }

        this.progressTimer = setTimeout(() => {
            this.currentProgressIndex++;
            if (this.currentProgressIndex < this.progressStates.length && !this.pollingCancelled) {
                const nextState = this.progressStates[this.currentProgressIndex];
                this.showProgressState(nextState);
                this.scheduleNextProgressState();
            } else if (!this.pollingCancelled) {
                this.currentProgressIndex = Math.max(0, this.progressStates.length - 3);
                this.scheduleNextProgressState();
            }
        }, currentState.duration);
    }

    cancelProgressiveLoading() {
        if (this.progressTimer) {
            clearTimeout(this.progressTimer);
            this.progressTimer = null;
        }
        this.currentProgressIndex = 0;
    }

    async handleSubmit(event) {
        if (event && event.preventDefault) {
            event.preventDefault();
        }

        // Final step validation before submission
        if (!this.validateStep(this.totalSteps)) {
            return;
        }

        if (!isValidUrl(this.ajaxUrl)) {
            this.showEnhancedError( __( 'Service unavailable. Please reload the page.', 'rtbcb' ) );
            return;
        }

        try {
            console.log('RTBCB: Starting form submission');
            this.showLoading();

            // Start progressive loading animation
            this.startProgressiveLoading();

            const formData = this.collectFormData();
            this.saveFormData( formData );
            this.validateFormData( formData );

            // Submit form
            if (!isValidUrl(this.ajaxUrl)) {
                this.showEnhancedError( __( 'Service unavailable. Please reload the page.', 'rtbcb' ) );
                return;
            }

            let attempt = 0;
            let response;
            let responseText;
            while (attempt < 2) {
                response = await fetch(this.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json, text/html'
                    }
                });

                responseText = await response.text();

                if (response.status === 403 && attempt === 0) {
                    const refreshed = await rtbcbRefreshNonce();
                    if (refreshed) {
                        if ( typeof formData.set === 'function' ) {
                            formData.set('rtbcb_nonce', rtbcb_ajax.nonce);
                        } else {
                            formData.append('rtbcb_nonce', rtbcb_ajax.nonce);
                        }
                        attempt++;
                        continue;
                    }
                    this.cancelProgressiveLoading();
                    this.handleError({
                        message: __( 'Security validation failed. Please reload the page.', 'rtbcb' ),
                        type: 'nonce_error'
                    });
                    return;
                }
                break;
            }

            console.log('RTBCB: Response status:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            console.log('RTBCB: Response received, length:', responseText.length);
            console.log('RTBCB: Response received, length:', responseText.length);

            // Try to parse as JSON first
            let data;
            try {
                data = JSON.parse(responseText);
                console.log('RTBCB: Parsed JSON response:', data);

                if (typeof data === 'string') {
                    try {
                        console.log('RTBCB: Response is a string, attempting second parse');
                        data = JSON.parse(data);
                        console.log('RTBCB: Parsed stringified JSON response:', data);
                    } catch (stringError) {
                        console.log('RTBCB: Failed to parse stringified JSON, attempting to clean');
                        const cleanedString = data
                            .replaceAll('\\/', '/')
                            .replaceAll('\\"', '"')
                            .replace(/(^"|"$)/g, '');
                        data = JSON.parse(cleanedString);
                        console.log('RTBCB: Parsed cleaned stringified JSON response:', data);
                    }
                }
            } catch (parseError) {
                console.log('RTBCB: JSON parse failed, attempting to clean response');
                try {
                    const cleaned = responseText
                        .replaceAll('\\/', '/')
                        .replaceAll('\\"', '"')
                        .replace(/(^"|"$)/g, '');
                    data = JSON.parse(cleaned);
                    console.log('RTBCB: Parsed cleaned JSON response:', data);
                } catch (cleanError) {
                    console.log('RTBCB: Response is not JSON, treating as HTML');
                    if (responseText.includes('<div class="rtbcb-enhanced-report"') ||
                        responseText.includes('<div class="rtbcb-report"')) {
                        this.cancelProgressiveLoading();
                        this.showEnhancedHTMLReport(responseText);
                        return;
                    }
                    throw new Error( __( 'Invalid response format', 'rtbcb' ) );
                }
            }

            // Handle JSON response
            if (data.success) {
                const payload = data.data || data;
                if (payload.job_id) {
                    // Background job approach
                    this.cancelPolling();
                    this.pollingCancelled = false;
                    this.activeJobId = payload.job_id;
                    try {
                        window.sessionStorage && window.sessionStorage.setItem( 'rtbcbJobId', payload.job_id );
                    } catch ( e ) {}

                    // Replace progressive loading with polling updates
                    this.cancelProgressiveLoading();
                    this.pollJob( this.activeJobId, Date.now(), 0 );
                } else {
                    // Direct response
                    this.cancelProgressiveLoading();
                    if (payload.report_html) {
                        this.handleSuccess(payload);
                    } else if (payload.report_data) {
                        this.handleSuccess(payload.report_data);
                    } else {
                        this.handleSuccess(payload);
                    }
                }
            } else {
                const payload = data.data || data;
                console.error('RTBCB: Error response:', payload);
                this.cancelProgressiveLoading();
                this.handleError(payload || { message: __( 'Unknown error occurred', 'rtbcb' ) });
            }

        } catch (error) {
            console.error('RTBCB: Submission error:', error);
            this.cancelProgressiveLoading();
            this.handleError({
                message: error.message || __( 'An unexpected error occurred', 'rtbcb' ),
                type: 'submission_error'
            });
        }
    }

    collectFormData() {
        const rawData = new FormData(this.form);
        const formData = new FormData();
        const numericFields = ['hours_reconciliation', 'hours_cash_positioning', 'num_banks', 'ftes'];

        const skipFields = ['fast_mode', 'report_type'];
        for (const [key, value] of rawData.entries()) {
            if (skipFields.includes(key)) {
                continue;
            }
            if (numericFields.includes(key)) {
                const num = parseFloat(value);
                formData.append(key, Number.isFinite(num) ? num : 0);
            } else {
                formData.append(key, value);
            }
        }

        formData.append('action', 'rtbcb_generate_case');
        if (typeof rtbcb_ajax !== 'undefined' && rtbcb_ajax.nonce) {
            formData.append('rtbcb_nonce', rtbcb_ajax.nonce);
        }

        const selectedReport = this.form.querySelector('input[name="report_type"]:checked');
        const reportType = selectedReport ? selectedReport.value : 'basic';
        formData.append('report_type', reportType);
        formData.append('fast_mode', reportType === 'fast' ? '1' : '0');
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
                throw new Error( `${ __( 'Missing required field:', 'rtbcb' )} ${field.replace('_', ' ')}` );
            }
        }

        const email = getValue('email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            throw new Error( __( 'Please enter a valid email address', 'rtbcb' ) );
        }

        const numericFields = ['hours_reconciliation', 'hours_cash_positioning', 'num_banks', 'ftes'];
        for (const field of numericFields) {
            const value = parseFloat(getValue(field));
            if (isNaN(value) || value <= 0) {
                throw new Error(`${field.replace('_', ' ')} ${ __( 'must be a positive number', 'rtbcb' ) }`);
            }
        }

        const painPoints = getAllValues('pain_points[]');
        if (painPoints.length === 0) {
            throw new Error( __( 'Please select at least one pain point', 'rtbcb' ) );
        }
    }

    showLoading() {
        // Hide entire modal while loading
        const modalContainer = document.querySelector('.rtbcb-modal-container');
        if (modalContainer) {
            modalContainer.style.display = 'none';
        }

        const progressContainer = document.getElementById('rtbcb-progress-container');
        if (progressContainer) {
            // Get company name for personalization
            const companyName = this.form.querySelector('[name="company_name"]')?.value || 'your company';
            const escapedCompanyName = this.escapeHTML(companyName);

            // Determine initial text based on AI settings
            const enableAI = window.rtbcb_ajax?.settings?.enable_ai_analysis !== false;
            const initialText = enableAI
                ? `Analyzing ${escapedCompanyName}'s treasury operations...`
                : 'Calculating ROI scenarios...';

            // Create properly structured loader content
            progressContainer.innerHTML = `
                <div class="rtbcb-progress-content">
                    <div class="rtbcb-progress-spinner"></div>
                    
                    <div class="rtbcb-progress-text">
                        Generating Your Business Case
                    </div>
                    
                    <div class="rtbcb-progress-step">
                        <span class="rtbcb-progress-step-text" id="rtbcb-progress-status">
                            ${initialText}
                        </span>
                    </div>
                    
                    <div class="rtbcb-progress-partial">
                        <div id="rtbcb-partial-basic-roi" style="display: none;"></div>
                        <div id="rtbcb-partial-category" style="display: none;"></div>
                        <div id="rtbcb-partial-analysis" style="display: none;"></div>
                    </div>
                </div>
            `;

            // Show the progress overlay
            progressContainer.style.display = 'flex';
            progressContainer.classList.add('active');

            // Prevent body scrolling
            document.body.style.overflow = 'hidden';

            // Add accessibility attributes
            progressContainer.setAttribute('role', 'dialog');
            progressContainer.setAttribute('aria-label', 'Generating business case report');
            progressContainer.setAttribute('aria-live', 'polite');

            console.log('RTBCB: Progress overlay shown');
        }
    }

    hideLoading() {
        const progressContainer = document.getElementById('rtbcb-progress-container');
        if (progressContainer) {
            progressContainer.style.display = 'none';
            progressContainer.classList.remove('active');
            progressContainer.innerHTML = '';
            progressContainer.removeAttribute('role');
            progressContainer.removeAttribute('aria-label');
            progressContainer.removeAttribute('aria-live');
        }

        // Restore body scrolling
        document.body.style.overflow = '';

        // Show modal and form containers
        const modalContainer = document.querySelector('.rtbcb-modal-container');
        if (modalContainer) {
            modalContainer.style.display = 'block';
        }

        const formContainer = ( this.form && typeof this.form.closest === 'function' ) ?
            this.form.closest('.rtbcb-form-container') : null;
        if (formContainer) {
            formContainer.style.display = 'block';
        }
        
        console.log('RTBCB: Progress overlay hidden');
    }

    cancelPolling() {
        this.pollingCancelled = true;
        this.cancelProgressiveLoading();
        if (this.pollTimeout) {
            clearTimeout(this.pollTimeout);
            this.pollTimeout = null;
        }
        this.clearPersistentState();
        console.log('RTBCB: All polling and progress timers cancelled');
    }

    async pollJob(jobId, startTime = Date.now(), attempt = 0) {
        const MAX_DURATION = 20 * 60 * 1000; // 20 minutes
        const MAX_ATTEMPTS = 600;

        if (this.pollingCancelled) {
            return;
        }

        if (Date.now() - startTime > MAX_DURATION || attempt > MAX_ATTEMPTS) {
            this.handleError({
                message: __( 'The request timed out after 20 minutes. Please try again later.', 'rtbcb' ),
                type: 'timeout'
            });
            this.cancelPolling();
            return;
        }

        try {
            let nonce = (typeof rtbcb_ajax !== 'undefined' && rtbcb_ajax.nonce) ? rtbcb_ajax.nonce : '';
            let attemptNonce = 0;
            let response;
            let responseText;
            while (attemptNonce < 2) {
                response = await fetch(`${this.ajaxUrl}?action=rtbcb_job_status&job_id=${encodeURIComponent(jobId)}&rtbcb_nonce=${nonce}`, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                responseText = await response.text();
                if ((response.status === 403 || responseText.includes('Security check failed')) && attemptNonce === 0) {
                    const refreshed = await rtbcbRefreshNonce();
                    if (refreshed) {
                        nonce = rtbcb_ajax.nonce;
                        attemptNonce++;
                        continue;
                    }
                    this.cancelPolling();
                    this.hideLoading();
                    this.handleError({
                        message: __( 'Security validation failed. Please reload the page.', 'rtbcb' ),
                        type: 'nonce_error'
                    });
                    return;
                }
                break;
            }

            if (!response.ok) {
                this.cancelPolling();
                this.handleError({
                    message: response.statusText,
                    type: 'polling_error'
                });
                return;
            }

            const data = JSON.parse(responseText);

            if (!data.success) {
                this.handleError({
                    message: __( 'Unable to retrieve job status', 'rtbcb' ),
                    type: 'polling_error'
                });
                this.cancelPolling();
                return;
            }

            const statusData = data.data || data;
            const { status, step, message, percent } = statusData;

            console.log(`RTBCB: Job status: ${status} (attempt ${attempt})`);

            // Update status with server data, but maintain smooth UX
            this.updateJobProgressStatus(statusData, attempt);
            this.updatePartialResults(statusData);

            if (status === 'completed') {
                this.cancelPolling();
                // Show completion animation before hiding
                this.showCompletionAnimation(() => {
                    this.hideLoading();
                    if (statusData.report_html) {
                        this.handleSuccess({
                            report_html: statusData.report_html,
                            report_data: statusData.report_data
                        });
                    } else if (statusData.report_data) {
                        this.handleSuccess(statusData.report_data);
                    } else {
                        this.handleError({
                            message: __( 'Report data missing from completed job', 'rtbcb' ),
                            type: 'job_error'
                        });
                    }
                });
            } else if (status === 'error') {
                this.cancelPolling();
                this.hideLoading();
                this.handleError({
                    message: statusData.message || __( 'Job failed', 'rtbcb' ),
                    type: 'job_error'
                });
            } else if (typeof percent === 'number' && percent >= 100) {
                this.cancelPolling();
                this.showCompletionAnimation(() => {
                    this.hideLoading();
                    if (statusData.report_html) {
                        this.handleSuccess({
                            report_html: statusData.report_html,
                            report_data: statusData.report_data
                        });
                    } else if (statusData.report_data) {
                        this.handleSuccess(statusData.report_data);
                    } else {
                        this.handleError({
                            message: 'Report data missing from completed job',
                            type: 'job_error'
                        });
                    }
                });
            } else if (!this.pollingCancelled) {
                // Continue polling
                this.pollTimeout = setTimeout(
                    () => this.pollJob(jobId, startTime, attempt + 1),
                    2000
                );
            }
        } catch (error) {
            console.error('RTBCB: Job polling error:', error);
            this.cancelPolling();
            this.handleError({
                message: error.message || 'An unexpected error occurred',
                type: 'polling_error'
            });
        }
    }

    // Show completion animation
    showCompletionAnimation(callback) {
        const statusElement = document.getElementById('rtbcb-progress-status');
        const spinner = document.querySelector('.rtbcb-progress-spinner');

        if (statusElement) {
            statusElement.textContent = __( 'Report completed successfully! ✓', 'rtbcb' );
            statusElement.style.color = 'var(--success-green)';
            statusElement.style.fontWeight = '600';
        }

        if (spinner) {
            spinner.style.borderTopColor = 'var(--success-green)';
            spinner.style.animation = 'rtbcb-spin 0.5s ease-out';

            setTimeout(() => {
                spinner.style.display = 'none';
                const checkmark = document.createElement('div');
                checkmark.innerHTML = '✓';
                checkmark.style.cssText = `
                    font-size: 60px;
                    color: var(--success-green);
                    animation: rtbcb-bounce 0.6s ease-out;
                    margin: 0 auto 24px;
                    width: 60px;
                    height: 60px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                `;
                spinner.parentNode.insertBefore(checkmark, spinner);
            }, 500);
        }

        setTimeout(callback, 1500);
    }

    // Update job progress status with server data
    updateJobProgressStatus(statusData, attempt) {
        const progressStatus = document.getElementById('rtbcb-progress-status');
        if (!progressStatus) {
            return;
        }

        const { status, step, message, percent } = statusData;
        let displayText = '';

        if (step && step !== 'processing') {
            displayText = this.formatProgressStep(step);
        } else if (message && message.trim()) {
            displayText = this.formatProgressMessage(message);
        } else {
            const currentText = progressStatus.textContent || __( 'Processing...', 'rtbcb' );
            displayText = currentText;
        }

        if (typeof percent === 'number' && percent > 0 && percent < 100) {
            displayText += ` (${Math.round(percent)}%)`;
        }

        if (displayText !== progressStatus.textContent) {
            progressStatus.style.transition = 'opacity 0.2s ease';
            progressStatus.style.opacity = '0.7';
            setTimeout(() => {
                progressStatus.textContent = displayText;
                progressStatus.style.opacity = '1';
            }, 200);
        }
    }

    // New method to show partial results
    updatePartialResults(statusData) {
        // Show basic ROI if available
        const roiContainer = document.getElementById('rtbcb-partial-basic-roi');
        if (statusData.basic_roi && roiContainer) {
            const baseROI = statusData.basic_roi?.financial_analysis?.roi_scenarios?.base?.total_annual_benefit;
            if (typeof baseROI === 'number' && baseROI > 0) {
                roiContainer.innerHTML = `
                    <strong>✓ ROI Analysis Complete</strong><br>
                    Projected Annual Benefit: $${this.formatNumber(baseROI)}
                `;
                roiContainer.style.display = 'block';
            }
        }

        // Show category recommendation if available
        const categoryContainer = document.getElementById('rtbcb-partial-category');
        if (statusData.category && categoryContainer) {
            categoryContainer.innerHTML = `
                <strong>✓ Solution Identified</strong><br>
                Recommended: ${this.escapeHTML(statusData.category)}
            `;
            categoryContainer.style.display = 'block';
        }

        // Show analysis progress if available
        const analysisContainer = document.getElementById('rtbcb-partial-analysis');
        if (statusData.analysis_step && analysisContainer) {
            analysisContainer.innerHTML = `
                <strong>✓ AI Analysis</strong><br>
                ${this.escapeHTML(statusData.analysis_step)}
            `;
            analysisContainer.style.display = 'block';
        }
    }

    // Format progress steps for better readability
    formatProgressStep(step) {
        const stepMap = {
            'roi_calculation': __( 'Calculating ROI scenarios...', 'rtbcb' ),
            'category_recommendation': __( 'Identifying optimal solution category...', 'rtbcb' ),
            'rag_search': __( 'Researching market insights...', 'rtbcb' ),
            'ai_analysis': __( 'Generating AI-powered analysis...', 'rtbcb' ),
            'report_generation': __( 'Assembling your business case report...', 'rtbcb' ),
            'finalizing': __( 'Finalizing recommendations...', 'rtbcb' )
        };

        return stepMap[step] || step.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + '...';
    }

    // Format progress messages for consistency
    formatProgressMessage(message) {
        if (!message) {
            return '';
        }
        
        // Ensure message ends with ellipsis for processing indication
        const cleanMessage = message.replace(/\.+$/, '');
        return cleanMessage + '...';
    }

    handleSuccess(data) {
        this.clearPersistentState();
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
        this.saveFinalReport( htmlContent );

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

    }

    initializeReportCharts(container) {
        if ( window.rtbcb_ajax?.settings && window.rtbcb_ajax.settings.enable_charts === false ) {
            console.log('RTBCB: Charts disabled in settings');
            return;
        }
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

    showEnhancedError(message, details = null) {
        this.hideLoading();

        const progressContainer = document.getElementById('rtbcb-progress-container');
        if (progressContainer) {
            progressContainer.innerHTML = `
                <div class="rtbcb-progress-content">
                    <div class="rtbcb-error-icon" style="font-size: 48px; color: #ef4444; margin-bottom: 20px;">
                        ⚠️
                    </div>
                    
                    <div class="rtbcb-progress-text" style="color: #ef4444; margin-bottom: 16px;">
                        Unable to Generate Report
                    </div>
                    
                    <div class="rtbcb-progress-step">
                        <span class="rtbcb-progress-step-text" style="color: #4b5563;">
                            ${window.DOMPurify ? DOMPurify.sanitize(message) : this.escapeHTML(message)}
                        </span>
                    </div>
                    
                    ${details ? `
                    <details style="margin-top: 16px; text-align: left; width: 100%; max-width: 400px;">
                        <summary style="cursor: pointer; color: #7c3aed; font-weight: 600; margin-bottom: 8px;">
                            Technical Details
                        </summary>
                        <pre style="background: #f3f4f6; padding: 12px; border-radius: 6px; font-size: 12px; overflow-x: auto; white-space: pre-wrap;">
                            ${this.escapeHTML(JSON.stringify(details, null, 2))}
                        </pre>
                    </details>
                    ` : ''}
                    
                    <div class="rtbcb-progress-partial" style="margin-top: 20px;">
                        <button type="button" onclick="location.reload()"
                                style="background: #7216f4; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; margin: 0 8px 8px 0;">
                            Try Again
                        </button>
                        <a href="/request-processing/" target="_blank"
                           style="background: #f3f4f6; color: #4b5563; border: none; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block; margin: 0 8px 8px 0;">
                            Request Processing
                        </a>
                    </div>
                </div>
            `;
            
            progressContainer.style.display = 'flex';
            progressContainer.classList.add('active');
            progressContainer.setAttribute('role', 'alert');
            progressContainer.setAttribute('aria-label', 'Error occurred');
        }
    }

    showResults(data) {
        console.log('RTBCB: Processing structured data results');
        this.hideLoading();
        
        // Map nested report data to expected structure
        const context = data.company_intelligence?.industry_context || data.industry_context || data.industry_insights || {};
        const mapped = {
            companyName: data.company_name || data.metadata?.company_name || 'Your Company',
            scenarios: data.scenarios || data.financial_analysis?.roi_scenarios || {},
            recommendation: {
                category_info: data.recommendation?.category_info || data.technology_strategy?.category_details || {},
                confidence: data.recommendation?.confidence || data.metadata?.confidence_level || 0.75,
                reasoning: data.recommendation?.reasoning || data.technology_strategy?.recommended_category || ''
            },
            executiveSummary: data.executive_summary || data.narrative || {},
            operationalAnalysis: data.operational_insights || data.operational_analysis || {},
            industryContext: {
                sector_analysis: context.sector_analysis || {},
                benchmarking: context.benchmarking || {},
                regulatory_landscape: context.regulatory_landscape || {}
            },
            nextActions: data.narrative?.next_actions || [
                ...(data.action_plan?.immediate_steps || []),
                ...(data.action_plan?.short_term_milestones || []),
                ...(data.action_plan?.long_term_objectives || [])
            ],
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
            this.saveFinalReport( resultsContainer.innerHTML );
            resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            console.error('RTBCB: Results container not found');
            this.showEnhancedError( __( 'Unable to display results. Please refresh the page.', 'rtbcb' ) );
        }
    }

    renderResults(data) {
        const {
            scenarios,
            recommendation,
            companyName,
            executiveSummary,
            operationalAnalysis,
industryContext,
            nextActions
        } = data;
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
                ${this.renderExecutiveSummary(executiveSummary)}
                ${this.renderOperationalAnalysis(operationalAnalysis)}
${this.renderIndustryInsights(industryContext)}
                ${this.renderRiskAssessmentSection()}
                ${this.renderNextSteps(nextActions || [], displayName)}
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

    renderExecutiveSummary(summary = {}) {
        const text = summary?.narrative || summary?.executive_recommendation ||
            'Treasury technology investment presents a compelling opportunity for operational efficiency.';
        const keyDrivers = Array.isArray(summary?.key_value_drivers) ? summary.key_value_drivers : [];
        const renderList = items => items.map(item => `<li>${this.escapeHTML(item)}</li>`).join('');
        return `
            <div class="rtbcb-narrative-section">
                <h3>Executive Summary</h3>
                <div class="rtbcb-narrative-content">${this.escapeHTML(text)}</div>
                ${keyDrivers.length ? `<div class="rtbcb-industry-group"><h4>Key Value Drivers</h4><ul>${renderList(keyDrivers)}</ul></div>` : ''}
            </div>
        `;
    }

    renderOperationalAnalysis(analysis = {}) {
        const {
            current_state_assessment = [],
            process_improvements = [],
            automation_opportunities = []
        } = analysis || {};

        const hasProcess = Object.prototype.hasOwnProperty.call(analysis, 'process_improvements');
        const hasAutomation = Object.prototype.hasOwnProperty.call(analysis, 'automation_opportunities');
        const hasCurrent = Array.isArray( current_state_assessment )
            ? current_state_assessment.some( item => item )
            : !! current_state_assessment;
        if ( ! hasCurrent && ! hasProcess && ! hasAutomation ) {
            return '';
        }

        const sanitizeItems = items => ( Array.isArray( items ) ? items.filter( item => {
            if ( item && typeof item === 'object' ) {
                return Object.values( item ).some( val => val );
            }
            return item;
        } ) : [] );

        const renderCurrentState = items => {
            if ( Array.isArray( items ) ) {
                const valid = sanitizeItems( items );
                if ( ! valid.length ) {
                    return `<p>${this.escapeHTML( __('No data provided') )}</p>`;
                }
                return `<ul>${valid.map( item => `<li>${this.escapeHTML( item )}</li>` ).join( '' )}</ul>`;
            }
            return `<p>${this.escapeHTML( items )}</p>`;
        };

        const renderProcessImprovements = items => {
            const valid = sanitizeItems( items );
            if ( ! valid.length ) {
                return `<li>${this.escapeHTML( __('No data provided') )}</li>`;
            }
            return valid.map( item => {
                if ( item && typeof item === 'object' ) {
                    const process = this.escapeHTML( item.process || item.process_area || '' );
                    const current = this.escapeHTML( item.current_state || '' );
                    const improved = this.escapeHTML( item.improved_state || '' );
                    const impact = this.escapeHTML( item.impact || item.impact_level || '' );
                    let details = '';
                    if ( current || improved ) {
                        details += `${current} \u2192 ${improved}`;
                    }
                    if ( impact ) {
                        details += details ? ` (${impact})` : `(${impact})`;
                    }
                    return `<li><strong>${process}</strong>${details ? `: ${details}` : ''}</li>`;
                }
                return `<li>${this.escapeHTML( item )}</li>`;
            } ).join( '' );
        };

        const renderAutomationOpportunities = items => {
            const valid = sanitizeItems( items );
            if ( ! valid.length ) {
                return `<li>${this.escapeHTML( __('No data provided') )}</li>`;
            }
            return valid.map( item => {
                if ( item && typeof item === 'object' ) {
                    const opportunity = this.escapeHTML( item.opportunity || '' );
                    const complexity = this.escapeHTML( item.complexity || '' );
                    const savings = this.escapeHTML( item.savings || item.time_savings || '' );
                    const parts = [];
                    if ( complexity ) {
                        parts.push( `${complexity} ${this.escapeHTML( __('complexity') )}` );
                    }
                    if ( savings ) {
                        parts.push( savings );
                    }
                    const detail = parts.length ? `: ${parts.join( ' \u2192 ' )}` : '';
                    return `<li><strong>${opportunity}</strong>${detail}</li>`;
                }
                return `<li>${this.escapeHTML( item )}</li>`;
            } ).join( '' );
        };

        const currentSection = hasCurrent ? renderCurrentState( current_state_assessment ) : '';
        const processSection = hasProcess
            ? `<div class="rtbcb-industry-group"><h4>Process Improvements</h4><ul>${renderProcessImprovements( process_improvements )}</ul></div>`
            : '';
        const automationSection = hasAutomation
            ? `<div class="rtbcb-industry-group"><h4>Automation Opportunities</h4><ul>${renderAutomationOpportunities( automation_opportunities )}</ul></div>`
            : '';

        return `
            <div class="rtbcb-operational-analysis">
                <h3>Operational Analysis</h3>
                ${currentSection}
                ${processSection}
                ${automationSection}
            </div>
        `;
    }

renderIndustryInsights(context = {}) {
const { sector_analysis = {}, benchmarking = {}, regulatory_landscape = {} } = context || {};
const {
market_dynamics = '',
growth_trends = '',
disruption_factors = [],
technology_adoption = ''
} = sector_analysis;
const {
typical_treasury_setup = '',
common_pain_points = [],
investment_patterns = ''
} = benchmarking;
const {
key_regulations = [],
compliance_complexity = '',
upcoming_changes = []
} = regulatory_landscape;
if (
!market_dynamics &&
!growth_trends &&
disruption_factors.length === 0 &&
!technology_adoption &&
!typical_treasury_setup &&
common_pain_points.length === 0 &&
!investment_patterns &&
key_regulations.length === 0 &&
!compliance_complexity &&
upcoming_changes.length === 0
) {
return '';
}
const renderList = items => items.map(item => `<li>${this.escapeHTML(item)}</li>`).join('');
return `
<div class="rtbcb-industry-insights">
<h3>Industry Insights</h3>
${market_dynamics ? `<div class="rtbcb-industry-group"><h4>Market Dynamics</h4><p>${this.escapeHTML(market_dynamics)}</p></div>` : ''}
${growth_trends ? `<div class="rtbcb-industry-group"><h4>Growth Trends</h4><p>${this.escapeHTML(growth_trends)}</p></div>` : ''}
${disruption_factors.length ? `<div class="rtbcb-industry-group"><h4>Disruption Factors</h4><ul>${renderList(disruption_factors)}</ul></div>` : ''}
${technology_adoption ? `<div class="rtbcb-industry-group"><h4>Technology Adoption</h4><p>${this.escapeHTML(technology_adoption)}</p></div>` : ''}
${typical_treasury_setup ? `<div class="rtbcb-industry-group"><h4>Typical Treasury Setup</h4><p>${this.escapeHTML(typical_treasury_setup)}</p></div>` : ''}
${common_pain_points.length ? `<div class="rtbcb-industry-group"><h4>Common Pain Points</h4><ul>${renderList(common_pain_points)}</ul></div>` : ''}
${investment_patterns ? `<div class="rtbcb-industry-group"><h4>Investment Patterns</h4><p>${this.escapeHTML(investment_patterns)}</p></div>` : ''}
${key_regulations.length ? `<div class="rtbcb-industry-group"><h4>Key Regulations</h4><ul>${renderList(key_regulations)}</ul></div>` : ''}
${compliance_complexity ? `<div class="rtbcb-industry-group"><h4>Compliance Complexity</h4><p>${this.escapeHTML(compliance_complexity)}</p></div>` : ''}
${upcoming_changes.length ? `<div class="rtbcb-industry-group"><h4>Upcoming Changes</h4><ul>${renderList(upcoming_changes)}</ul></div>` : ''}
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
                __( 'Present business case to stakeholders', 'rtbcb' ),
                __( 'Evaluate solution providers', 'rtbcb' ),
                __( 'Develop implementation timeline', 'rtbcb' ),
                __( 'Plan change management strategy', 'rtbcb' )
            ];
        }

        // Sanitize steps before output for security
        const sanitize = (str) => (window.DOMPurify && typeof window.DOMPurify.sanitize === 'function')
            ? window.DOMPurify.sanitize(str)
            : this.escapeHTML(str);

        return `
            <div class="rtbcb-next-steps">
                <h3>${ __( 'Recommended Next Steps', 'rtbcb' ) }</h3>
                <div class="rtbcb-steps-grid">
                    ${steps.map((step, index) => `
                        <div class="rtbcb-step">
                            <div class="rtbcb-step-number">${index + 1}</div>
                            <div class="rtbcb-step-content">
                                <p>${sanitize(step)}</p>
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
                <button type="button" class="rtbcb-action-btn rtbcb-btn-secondary" onclick="window.businessCaseBuilder.copyResultsHTML()">
                    <span class="rtbcb-btn-icon">📋</span>
                    ${ __( 'Copy HTML', 'rtbcb' ) }
                </button>
                <button type="button" class="rtbcb-action-btn rtbcb-btn-secondary" onclick="location.reload()">
                    <span class="rtbcb-btn-icon">🔄</span>
                    ${ __( 'Start Over', 'rtbcb' ) }
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
            .then(() => alert( __( 'Results HTML copied to clipboard', 'rtbcb' ) ))
            .catch(err => console.error('Copy failed:', err));
    }

    handleError(errorData) {
        this.clearPersistentState();
        const message = errorData.message || __( 'An unexpected error occurred', 'rtbcb' );

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
            'Security check failed': __( 'Session expired. Please refresh the page and try again.', 'rtbcb' ),
            'OpenAI API key not configured': __( 'Service temporarily unavailable. Please try again later.', 'rtbcb' ),
            'API connection failed': __( 'Unable to connect to analysis service. Please try again.', 'rtbcb' ),
            'Missing required field': __( 'Please fill in all required fields.', 'rtbcb' ),
            'Invalid email address': __( 'Please enter a valid email address.', 'rtbcb' ),
            'request took longer than our 5-minute limit': __( 'Your request exceeded the 5-minute limit. Visit the <a href="/request-processing/" target="_blank">Request Processing page</a> or <a href="#" onclick="window.location.href=&apos;mailto:contact@realtreasury.com?subject=Business%20Case%20Request&apos;">request email delivery</a>.', 'rtbcb' ),
            'PHP error occurred': __( 'Server error encountered. Please try again.', 'rtbcb' ),
            'Server returned invalid JSON response': __( 'Server communication error. Please try again.', 'rtbcb' ),
            'Unexpected server response': __( 'Server communication error. Please try again.', 'rtbcb' ),
            'Job not found': __( 'Unable to locate your report request. Please resubmit the form.', 'rtbcb' )
        };

        for (const [key, message] of Object.entries(errorMappings)) {
            if (serverMessage.includes(key)) {
                return message;
            }
        }

        return __( 'An error occurred while processing your request. Please try again.', 'rtbcb' );
    }

    escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    formatNumber(num) {
        if (typeof num !== 'number' || isNaN(num)) return '0';
        return new Intl.NumberFormat('en-US', {
            maximumFractionDigits: 0,
            minimumFractionDigits: 0
        }).format(num);
    }

    reinitialize() {
        this.currentStep = 1;
        this.updateStepVisibility();
        this.updateProgressIndicator();
        try {
            window.sessionStorage && window.sessionStorage.removeItem( 'rtbcbFinalReport' );
        } catch ( e ) {}
    }
}
