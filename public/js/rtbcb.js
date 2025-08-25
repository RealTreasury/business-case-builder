/**
 * Handles the display of submission errors on the frontend.
 * @param {string} errorMessage - The error message to display.
 * @param {string} errorCode - Optional error code for specific handling.
 */
function handleSubmissionError(errorMessage, errorCode = '') {
    console.error('Submission Error:', errorMessage, errorCode ? `Code: ${errorCode}` : '');
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

        // Determine user-friendly message and action based on error
        let displayMessage = errorMessage;
        let actionText = '';
        let actionClass = '';

        if (errorCode === 'no_api_key' || errorMessage.includes('API key')) {
            displayMessage = 'OpenAI API key is not configured. Please contact your administrator to set up the API key.';
            actionText = 'Contact Administrator';
            actionClass = 'contact-admin';
        } else if (errorCode === 'llm_generation_failed' || errorMessage.includes('Failed to generate')) {
            displayMessage = 'We encountered an issue generating your business case. This might be due to high demand or a temporary service issue.';
            actionText = 'Try Again';
            actionClass = 'retry-action';
        } else if (errorMessage.includes('Security check failed')) {
            displayMessage = 'Session expired. Please refresh the page and try again.';
            actionText = 'Refresh Page';
            actionClass = 'refresh-page';
        } else if (errorMessage.includes('server') || errorMessage.includes('500')) {
            displayMessage = 'A temporary server issue occurred. Please try again in a few moments.';
            actionText = 'Try Again';
            actionClass = 'retry-action';
        }

        if (typeof document.createElement === 'function') {
            const errorContent = document.createElement('div');
            errorContent.className = 'rtbcb-error-content';

            const heading = document.createElement('h3');
            heading.style.color = '#dc3545';
            heading.textContent = 'Business Case Generation Failed';

            const message = document.createElement('p');
            message.textContent = displayMessage;

            const actionContainer = document.createElement('div');
            actionContainer.className = 'rtbcb-error-actions';
            actionContainer.style.marginTop = '15px';

            if (actionText) {
                const actionButton = document.createElement('button');
                actionButton.textContent = actionText;
                actionButton.className = `rtbcb-error-action ${actionClass}`;
                actionButton.style.cssText = 'padding: 8px 16px; margin-right: 10px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;';
                
                // Add click handlers for different actions
                if (actionClass === 'refresh-page') {
                    actionButton.onclick = () => window.location.reload();
                } else if (actionClass === 'retry-action') {
                    actionButton.onclick = () => {
                        const form = document.getElementById('rtbcb-form');
                        if (form) {
                            const formContainer = document.getElementById('rtbcb-form-container');
                            if (formContainer) formContainer.style.display = 'block';
                            progressContainer.style.display = 'none';
                        }
                    };
                }
                
                actionContainer.appendChild(actionButton);
            }

            errorContent.appendChild(heading);
            errorContent.appendChild(message);
            errorContent.appendChild(actionContainer);

            if (typeof progressContainer.appendChild === 'function') {
                progressContainer.appendChild(errorContent);
            } else {
                progressContainer.innerHTML = errorContent.outerHTML;
            }
        } else {
            // Fallback for older browsers
            const actionHtml = actionText ? `<div class="rtbcb-error-actions" style="margin-top: 15px;">
                <button onclick="window.location.reload()" style="padding: 8px 16px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    ${escapeHtml(actionText)}
                </button>
            </div>` : '';
            
            progressContainer.innerHTML = '<div class="rtbcb-error-content">' +
                '<h3 style="color: #dc3545;">Business Case Generation Failed</h3>' +
                '<p>' + escapeHtml(displayMessage) + '</p>' +
                actionHtml +
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
        xhr.open('POST', ajaxObj.ajax_url, false);
        xhr.send(formData);

        if (xhr.status < 200 || xhr.status >= 300) {
            let errorMessage = `Server responded with status ${xhr.status}.`;
            let errorCode = '';
            try {
                const errorJson = JSON.parse(xhr.responseText);
                if (errorJson.data) {
                    errorMessage = errorJson.data.message || errorMessage;
                    errorCode = errorJson.data.code || '';
                }
            } catch (jsonError) {
                console.error('Could not parse error response as JSON.', jsonError);
                errorMessage = xhr.responseText || errorMessage;
            }
            throw new Error(JSON.stringify({message: errorMessage, code: errorCode}));
        }

        const result = JSON.parse(xhr.responseText);
        if (!result.success) {
            const errorMessage = result.data?.message || 'An unknown error occurred.';
            const errorCode = result.data?.code || '';
            throw new Error(JSON.stringify({message: errorMessage, code: errorCode}));
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
        let errorMessage = error.message;
        let errorCode = '';
        
        // Try to parse structured error if it was serialized
        try {
            const errorData = JSON.parse(error.message);
            errorMessage = errorData.message || error.message;
            errorCode = errorData.code || '';
        } catch (parseError) {
            // Error message is plain text, use as-is
            errorMessage = error.message;
        }
        
        handleSubmissionError(errorMessage, errorCode);
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
