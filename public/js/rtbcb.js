/* JavaScript for Real Treasury Business Case Builder plugin */

class BusinessCaseBuilder {
    constructor() {
        this.form = document.getElementById('rtbcbForm');
        this.results = document.getElementById('rtbcbResults');
        this.init();
    }

    init() {
        if (this.form) {
            this.form.addEventListener('submit', this.handleSubmit.bind(this));
        }
    }

    async handleSubmit(e) {
        e.preventDefault();

        const submitBtn = this.form.querySelector('.rtbcb-submit-btn');
        const originalText = submitBtn.textContent;

        try {
            submitBtn.textContent = 'Generating...';
            submitBtn.disabled = true;

            const formData = new FormData(this.form);
            formData.append('action', 'rtbcb_generate_case');

            const response = await fetch(RTBCB.ajax_url, {
                method: 'POST',
                body: new URLSearchParams(formData)
            });

            const data = await response.json();

            if (data.success) {
                this.displayResults(data.data);
            } else {
                this.showError(data.data || 'An unknown error occurred.');
            }

        } catch (error) {
            this.showError('A network error occurred. Please try again.');
            console.error('Submission Error:', error);
        } finally {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    }

    displayResults(data) {
        const html = `
            <div class="rtbcb-result-header">
                <h3>Your Treasury Technology Business Case</h3>
                <div class="rtbcb-roi-summary">
                    <div class="rtbcb-scenario">
                        <h4>Conservative</h4>
                        <span class="rtbcb-amount">$${this.formatNumber(data.scenarios.low.total_annual_benefit)}</span>
                    </div>
                    <div class="rtbcb-scenario rtbcb-scenario-primary">
                        <h4>Base Case</h4>
                        <span class="rtbcb-amount">$${this.formatNumber(data.scenarios.base.total_annual_benefit)}</span>
                    </div>
                    <div class="rtbcb-scenario">
                        <h4>Optimistic</h4>
                        <span class="rtbcb-amount">$${this.formatNumber(data.scenarios.high.total_annual_benefit)}</span>
                    </div>
                </div>
            </div>

            <div class="rtbcb-narrative">
                <h4>Executive Summary</h4>
                <p>${data.narrative.narrative}</p>
            </div>

            <div class="rtbcb-actions-final">
                <a href="${data.download_url}" class="rtbcb-download-btn" target="_blank">Download Full Report</a>
                <button type="button" class="rtbcb-email-btn" onclick="window.location.href='mailto:?subject=Treasury Technology Business Case&body=See attached business case for treasury technology investment.'">Email Report</button>
            </div>
        `;

        this.results.innerHTML = html;
        this.results.style.display = 'block';
        this.results.scrollIntoView({ behavior: 'smooth' });
    }

    formatNumber(num) {
        return new Intl.NumberFormat('en-US').format(Math.round(num));
    }

    showError(message) {
        this.results.innerHTML = `<div class="rtbcb-error">Error: ${message}</div>`;
        this.results.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new BusinessCaseBuilder();
});

