/**
 * Handles the display of submission errors on the frontend.
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
            try {
                const errorJson = JSON.parse(xhr.responseText);
                errorMessage = errorJson.data.message || errorMessage;
            } catch (jsonError) {
                console.error('Could not parse error response as JSON.', jsonError);
                errorMessage = xhr.responseText || errorMessage;
            }
            throw new Error(errorMessage);
        }

        const result = JSON.parse(xhr.responseText);
        if (!result.success) {
            throw new Error(result.data.message || 'An unknown error occurred.');
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
