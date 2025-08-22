/**
 * Handles the display of submission errors on the frontend.
 * @param {string} errorMessage - The error message to display.
 */
function handleSubmissionError(errorMessage) {
    console.error('Submission Error:', errorMessage);
    const progressContainer = document.getElementById('rtbcb-progress-container');
    if (progressContainer) {
        progressContainer.innerHTML = `
            <div class="rtbcb-error-content">
                <h3 style="color: #dc3545;">Generation Failed</h3>
                <p>We're sorry, but we couldn't generate your business case. Please try again later.</p>
                <p style="font-size: 0.9em; color: #6c757d; margin-top: 15px;"><strong>Error Details:</strong> ${errorMessage}</p>
            </div>
        `;
    }
}

/**
 * Handles the form submission by sending data to the backend.
 * @param {Event} e - The form submission event.
 */
async function handleSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const progressContainer = document.getElementById('rtbcb-progress-container');
    const formContainer = document.getElementById('rtbcb-form-container');

    // Show progress indicator
    if (formContainer) formContainer.style.display = 'none';
    if (progressContainer) progressContainer.style.display = 'block';

    try {
        const response = await fetch(ajaxObj.ajax_url, {
            method: 'POST',
            body: formData,
        });

        // Check for server-side errors
        if (!response.ok) {
            const errorText = await response.text();
            let errorMessage = `Server responded with status ${response.status}.`;
            try {
                const errorJson = JSON.parse(errorText);
                errorMessage = errorJson.data.message || errorMessage;
            } catch (jsonError) {
                console.error('Could not parse error response as JSON.', jsonError);
                errorMessage = errorText || errorMessage;
            }
            throw new Error(errorMessage);
        }

        const result = await response.json();

        // Application-specific errors
        if (!result.success) {
            throw new Error(result.data.message || 'An unknown error occurred.');
        }

        // On success, display the report
        const reportContainer = document.getElementById('rtbcb-report-container');
        if (progressContainer) progressContainer.style.display = 'none';
        if (reportContainer) {
            reportContainer.innerHTML = result.data.report_html;
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
