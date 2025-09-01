/**
 * Handles the display of submission errors on the frontend.
 * @param {string} errorMessage - The error message to display.
 * @param {string} [errorCode] - Optional error code for debugging.
 */
function handleSubmissionError(errorMessage, errorCode) {
    console.error('Submission Error:', errorCode ? errorCode + ': ' + errorMessage : errorMessage);
    const progressContainer = document.getElementById('rtbcb-progress-container');
    if (progressContainer) {
        progressContainer.style.display = 'block';
        progressContainer.innerHTML = '';

        const escapeHtml = (str) => str.replace(/[&<>"']/g, (m) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        })[m]);

        if (typeof document.createElement === 'function') {
            const errorContent = document.createElement('div');
            errorContent.className = 'rtbcb-error-content';

            const heading = document.createElement('h3');
            heading.style.color = '#dc3545';
            heading.textContent = 'Generation Failed';

            const message = document.createElement('p');
            message.textContent = "We're sorry, but we couldn't generate your business case. Please try again later.";

            const details = document.createElement('p');
            details.style.fontSize = '0.9em';
            details.style.color = '#6c757d';
            details.style.marginTop = '15px';

            const strong = document.createElement('strong');
            strong.textContent = 'Error Details:';

            details.appendChild(strong);
            if (errorCode) {
                details.appendChild(document.createTextNode(' [' + errorCode + ']'));
            }
            details.appendChild(document.createTextNode(' ' + errorMessage));

            errorContent.appendChild(heading);
            errorContent.appendChild(message);
            errorContent.appendChild(details);

            if (typeof progressContainer.appendChild === 'function') {
                progressContainer.appendChild(errorContent);
            } else {
                progressContainer.innerHTML = errorContent.outerHTML;
            }
        } else {
            progressContainer.innerHTML = '<div class="rtbcb-error-content">' +
                '<h3 style="color: #dc3545;">Generation Failed</h3>' +
                "<p>We're sorry, but we couldn't generate your business case. Please try again later.</p>" +
                '<p style="font-size: 0.9em; color: #6c757d; margin-top: 15px;"><strong>Error Details:</strong> ' +
                (errorCode ? '[' + escapeHtml(errorCode) + '] ' : '') +
                escapeHtml(errorMessage) +
                '</p>' +
                '</div>';
        }
    }
}

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

/**
 * Handles the form submission by sending data to the backend.
 * @param {Event} e - The form submission event.
 */
let rtbcbIsSubmitting = false;

async function handleSubmit(e) {
    e.preventDefault();

    // If the wizard controller is present, let it handle submission to avoid duplicate posts
    if (window.businessCaseBuilder && typeof window.businessCaseBuilder.handleSubmit === 'function') {
        return;
    }

    if (rtbcbIsSubmitting) {
        return;
    }
    rtbcbIsSubmitting = true;

    var form = e.target;
    var formData = new FormData(form);
    formData.append('action', 'rtbcb_generate_case');
    formData.append('rtbcb_nonce', rtbcbAjax.nonce);
    var progressContainer = document.getElementById('rtbcb-progress-container');
    var formContainer = document.querySelector('.rtbcb-form-container');

    // Show progress indicator
    if (formContainer) {
        formContainer.style.display = 'none';
    }
    if (progressContainer) {
        var progressText = progressContainer.querySelector('.rtbcb-progress-text');
        if (rtbcbAjax && rtbcbAjax.strings && rtbcbAjax.strings.generating) {
            if (progressText) {
                progressText.textContent = rtbcbAjax.strings.generating;
            } else {
                progressContainer.textContent = rtbcbAjax.strings.generating;
            }
        }
        progressContainer.style.display = 'block';
    }
    if (typeof rtbcbAjax === 'undefined' || !isValidUrl(rtbcbAjax.ajax_url)) {
        handleSubmissionError('Service unavailable. Please reload the page.', '');
        rtbcbIsSubmitting = false;
        return;
    }

    var loggedData = typeof formData.entries === 'function' ? Object.fromEntries(formData.entries()) : {};
    console.log('RTBCB: Submitting form data:', loggedData);

    var response;
    var responseText;
    try {
        if (!isValidUrl(rtbcbAjax.ajax_url)) {
            handleSubmissionError('Service unavailable. Please reload the page.', '');
            rtbcbIsSubmitting = false;
            return;
        }
        response = await fetch(rtbcbAjax.ajax_url, {
            method: 'POST',
            body: formData
        });
        responseText = await response.text();
    } catch (networkError) {
        handleSubmissionError('Network error. Please try again later.', '');
        rtbcbIsSubmitting = false;
        return;
    }

    if (!response.ok) {
        var errorMessage = 'Server responded with status ' + response.status + '.';
        var errorCode = '';
        try {
            var errorJson = JSON.parse(responseText);
            errorMessage = errorJson.data && errorJson.data.message ? errorJson.data.message : errorMessage;
            errorCode = errorJson.data && errorJson.data.error_code ? errorJson.data.error_code : '';
        } catch (jsonError) {
            console.error('Could not parse error response as JSON.', jsonError);
            errorMessage = responseText || errorMessage;
        }
        handleSubmissionError(errorMessage, errorCode);
        rtbcbIsSubmitting = false;
        return;
    }

    var result;
    try {
        result = JSON.parse(responseText);
    } catch (parseError) {
        handleSubmissionError('Invalid server response.', '');
        rtbcbIsSubmitting = false;
        return;
    }

    if (!result.success || !result.data || !result.data.job_id) {
        var errorMessage = result.data && result.data.message ? result.data.message : 'An unknown error occurred.';
        var errorCode = result.data && result.data.error_code ? result.data.error_code : '';
        handleSubmissionError(errorMessage, errorCode);
        rtbcbIsSubmitting = false;
        return;
    }

    pollJobStatus(result.data.job_id, progressContainer, formContainer);
}

async function pollJobStatus(jobId, progressContainer, formContainer) {
    try {
        if (!isValidUrl(rtbcbAjax.ajax_url)) {
            handleSubmissionError('Service unavailable. Please reload the page.', '');
            rtbcbIsSubmitting = false;
            return;
        }
        const response = await fetch(`${rtbcbAjax.ajax_url}?action=rtbcb_job_status&job_id=${encodeURIComponent(jobId)}&rtbcb_nonce=${rtbcbAjax.nonce}`);
        const data = await response.json();

        if (!data.success) {
            handleSubmissionError('Unable to retrieve job status.', '');
            rtbcbIsSubmitting = false;
            return;
        }

        const statusData = data.data;
        const status = statusData.status;
        if (status === 'completed') {
            if (progressContainer) {
                var progressTextSuccess = progressContainer.querySelector('.rtbcb-progress-text');
                if (rtbcbAjax && rtbcbAjax.strings && rtbcbAjax.strings.email_confirmation) {
                    if (progressTextSuccess) {
                        progressTextSuccess.textContent = rtbcbAjax.strings.email_confirmation;
                    } else {
                        progressContainer.textContent = rtbcbAjax.strings.email_confirmation;
                    }
                }
                setTimeout(() => {
                    progressContainer.style.display = 'none';
                    if (formContainer) {
                        formContainer.style.display = 'block';
                    }
                }, 3000);
            }
            rtbcbIsSubmitting = false;
        } else if (status === 'error') {
            handleSubmissionError(statusData.message || 'Job failed.', '');
            rtbcbIsSubmitting = false;
        } else {
            setTimeout(() => pollJobStatus(jobId, progressContainer, formContainer), 2000);
        }
    } catch (err) {
        handleSubmissionError('Network error. Please try again later.', '');
        rtbcbIsSubmitting = false;
    }
}

// Ensure the form submission is handled by our new function
// eslint-disable-next-line @wordpress/no-global-event-listener
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rtbcbForm');
    if (form) {
        form.addEventListener('submit', handleSubmit);
    }
});

/**
 * Stream analysis chunks to the UI.
 *
 * @param {FormData} formData Form data to submit.
 * @param {Function} onChunk  Callback for each streamed chunk.
 * @return {Promise<void>} Promise that resolves when streaming completes.
 */
export async function rtbcbStreamAnalysis(formData, onChunk) {
    if (!rtbcbAjax || !isValidUrl(rtbcbAjax.ajax_url)) {
        handleSubmissionError('Service unavailable. Please reload the page.', '');
        return;
    }
    formData.append('action', 'rtbcb_stream_analysis');
    formData.append('rtbcb_nonce', rtbcbAjax.nonce);
    const response = await fetch(rtbcbAjax.ajax_url, {
        method: 'POST',
        body: formData
    });
    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    while (true) {
        const { value, done } = await reader.read();
        if (done) {
            break;
        }
        const chunk = decoder.decode(value);
        if (typeof onChunk === 'function') {
            onChunk(chunk);
        }
    }
}
