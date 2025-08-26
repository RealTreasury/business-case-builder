/**
 * Handles the display of submission errors on the frontend with improved UX.
 * @param {string} errorMessage - The error message to display.
 */
function handleSubmissionError(errorMessage) {
    console.error('Submission Error:', errorMessage);
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

        // Provide user-friendly error messages based on common error patterns
        let displayMessage = errorMessage;
        let actionSuggestion = '';
        
        if (errorMessage.includes('Security verification failed') || errorMessage.includes('Security check failed')) {
            displayMessage = 'Security verification failed. Please refresh the page and try again.';
            actionSuggestion = 'Please refresh this page and try submitting your form again.';
        } else if (errorMessage.includes('API configuration') || errorMessage.includes('API key')) {
            displayMessage = 'Service configuration issue. Please contact support.';
            actionSuggestion = 'If this problem persists, please contact our support team.';
        } else if (errorMessage.includes('Rate limit') || errorMessage.includes('busy')) {
            displayMessage = 'Service is temporarily busy. Please try again in a few minutes.';
            actionSuggestion = 'Please wait a few minutes and try again.';
        } else if (errorMessage.includes('validation') || errorMessage.includes('check your input')) {
            displayMessage = 'Please check your form inputs and try again.';
            actionSuggestion = 'Please review your form entries for any missing or invalid information.';
        } else if (errorMessage.includes('template') || errorMessage.includes('report')) {
            displayMessage = 'Report generation is temporarily unavailable.';
            actionSuggestion = 'Please try again later or contact support if the issue persists.';
        }

        if (typeof document.createElement === 'function') {
            const errorContent = document.createElement('div');
            errorContent.className = 'rtbcb-error-content';
            errorContent.style.cssText = 'padding: 20px; background: #fff3f3; border: 1px solid #dc3545; border-radius: 4px; margin: 10px 0;';

            const heading = document.createElement('h3');
            heading.style.cssText = 'color: #dc3545; margin: 0 0 10px 0; font-size: 18px;';
            heading.textContent = 'Unable to Generate Business Case';

            const message = document.createElement('p');
            message.style.cssText = 'margin: 0 0 10px 0; line-height: 1.4;';
            message.textContent = displayMessage;

            errorContent.appendChild(heading);
            errorContent.appendChild(message);

            if (actionSuggestion) {
                const suggestion = document.createElement('p');
                suggestion.style.cssText = 'margin: 10px 0 0 0; font-weight: bold; color: #555;';
                suggestion.textContent = actionSuggestion;
                errorContent.appendChild(suggestion);
            }

            // Add retry button for appropriate errors
            if (!errorMessage.includes('Security') && !errorMessage.includes('template')) {
                const retryButton = document.createElement('button');
                retryButton.textContent = 'Try Again';
                retryButton.style.cssText = 'background: #0073aa; color: white; padding: 8px 16px; border: none; border-radius: 3px; cursor: pointer; margin-top: 15px;';
                retryButton.onclick = function() {
                    const form = document.getElementById('rtbcb-form');
                    if (form) {
                        progressContainer.style.display = 'none';
                        form.style.display = 'block';
                    }
                };
                errorContent.appendChild(retryButton);
            }

            if (typeof progressContainer.appendChild === 'function') {
                progressContainer.appendChild(errorContent);
            } else {
                progressContainer.innerHTML = errorContent.outerHTML;
            }
        } else {
            progressContainer.innerHTML = '<div class="rtbcb-error-content" style="padding: 20px; background: #fff3f3; border: 1px solid #dc3545; border-radius: 4px; margin: 10px 0;">' +
                '<h3 style="color: #dc3545; margin: 0 0 10px 0; font-size: 18px;">Unable to Generate Business Case</h3>' +
                '<p style="margin: 0 0 10px 0; line-height: 1.4;">' + escapeHtml(displayMessage) + '</p>' +
                (actionSuggestion ? '<p style="margin: 10px 0 0 0; font-weight: bold; color: #555;">' + escapeHtml(actionSuggestion) + '</p>' : '') +
                '</div>';
        }
    }
}

/**
 * Handles the form submission by sending data to the backend.
 * @param {Event} e - The form submission event.
 */
function handleSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const progressContainer = document.getElementById('rtbcb-progress-container');
    const formContainer = document.getElementById('rtbcb-form-container');

    // Show progress indicator
    if (formContainer) {
        formContainer.style.display = 'none';
    }
    if (progressContainer) {
        progressContainer.style.display = 'block';
    }

    try {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', rtbcbAjax.ajax_url, false);
        xhr.send(formData);

        if (xhr.status < 200 || xhr.status >= 300) {
            let errorMessage = `Server responded with status ${xhr.status}.`;
            try {
                const errorJson = JSON.parse(xhr.responseText);
                // Handle both old and new error response formats
                if (errorJson.data && errorJson.data.message) {
                    errorMessage = errorJson.data.message;
                } else if (errorJson.message) {
                    errorMessage = errorJson.message;
                } else {
                    errorMessage = 'An error occurred while processing your request.';
                }
            } catch (jsonError) {
                console.error('Could not parse error response as JSON.', jsonError);
                errorMessage = xhr.responseText || errorMessage;
            }
            throw new Error(errorMessage);
        }

        const result = JSON.parse(xhr.responseText);
        if (!result.success) {
            let errorMessage = 'An unknown error occurred.';
            if (result.data) {
                // Handle new standardized error format
                if (result.data.message) {
                    errorMessage = result.data.message;
                } else if (result.data.code) {
                    // Fallback to code if message is missing
                    errorMessage = `Error: ${result.data.code}`;
                }
            }
            throw new Error(errorMessage);
        }

        const reportContainer = document.getElementById('rtbcb-report-container');
        if (progressContainer) {
            progressContainer.style.display = 'none';
        }
        if (reportContainer) {
            // Sanitize server-provided HTML before injecting to prevent XSS.
            // Only allow expected markup needed for business case output.
            const allowedTags = [
                'a', 'p', 'br', 'strong', 'em', 'ul', 'ol', 'li',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span',
                'table', 'thead', 'tbody', 'tr', 'th', 'td'
            ];
            const allowedAttr = { a: [ 'href', 'title', 'target', 'rel' ], '*': [ 'style' ] };
            const sanitized = typeof DOMPurify !== 'undefined'
                ? DOMPurify.sanitize(
                    result.data.report_html,
                    { ALLOWED_TAGS: allowedTags, ALLOWED_ATTR: allowedAttr }
                )
                : result.data.report_html;
            reportContainer.innerHTML = sanitized;
            reportContainer.style.display = 'block';
        }
    } catch (error) {
        handleSubmissionError(error.message);
    }
}

// Ensure the form submission is handled by our new function
// eslint-disable-next-line @wordpress/no-global-event-listener
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rtbcb-form');
    if (form) {
        form.addEventListener('submit', handleSubmit);
    }
});
