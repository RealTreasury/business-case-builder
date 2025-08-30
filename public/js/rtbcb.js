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
    if (formContainer) formContainer.style.display = 'none';
    if (progressContainer) progressContainer.style.display = 'block';
    if (typeof rtbcbAjax === 'undefined' || !rtbcbAjax.ajax_url) {
        handleSubmissionError('Unable to submit form. Please refresh the page and try again.', '');
        rtbcbIsSubmitting = false;
        return;
    }

    var loggedData = typeof formData.entries === 'function' ? Object.fromEntries(formData.entries()) : {};
    console.log('RTBCB: Submitting form data:', loggedData);

    var response;
    var responseText;
    try {
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

    if (!result.success) {
        var errorMessage = result.data && result.data.message ? result.data.message : 'An unknown error occurred.';
        var errorCode = result.data && result.data.error_code ? result.data.error_code : '';
        handleSubmissionError(errorMessage, errorCode);
        rtbcbIsSubmitting = false;
        return;
    }

    // On success, display the report
    var reportContainer = document.getElementById('rtbcb-report-container');
    if (progressContainer) progressContainer.style.display = 'none';
    if (reportContainer) {
        // Sanitize server-provided HTML before injecting to prevent XSS.
        // Only allow expected markup needed for business case output.
        var allowedTags = [
            'a', 'p', 'br', 'strong', 'em', 'ul', 'ol', 'li',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span',
            'table', 'thead', 'tbody', 'tr', 'th', 'td'
        ];
        var allowedAttr = { a: [ 'href', 'title', 'target', 'rel' ], '*': [ 'style' ] };
        var sanitized = typeof DOMPurify !== 'undefined'
            ? DOMPurify.sanitize(
                result.data.report_html,
                { ALLOWED_TAGS: allowedTags, ALLOWED_ATTR: allowedAttr }
            )
            : result.data.report_html;
        reportContainer.innerHTML = sanitized;
        reportContainer.style.display = 'block';
    }

    rtbcbIsSubmitting = false;
}

// Ensure the form submission is handled by our new function
// eslint-disable-next-line @wordpress/no-global-event-listener
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rtbcbForm');
    if (form) {
        form.addEventListener('submit', handleSubmit);
    }
});
