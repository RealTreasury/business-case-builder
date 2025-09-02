/**
 * Generate and display professional reports using OpenAI.
 */

const RTBCB_GPT5_MAX_TOKENS = 128000;
const RTBCB_GPT5_MIN_TOKENS = 256;
const RTBCB_API_TIMEOUT =
    typeof rtbcbReport !== 'undefined' && rtbcbReport.timeout_ms
        ? rtbcbReport.timeout_ms / 1000
        : 300;
const RTBCB_GPT5_DEFAULTS = {
    max_output_tokens: RTBCB_GPT5_MAX_TOKENS,
    min_output_tokens: RTBCB_GPT5_MIN_TOKENS,
    text: { verbosity: 'medium' },
    temperature: 0.7,
    store: true,
    timeout: RTBCB_API_TIMEOUT,
    max_retries: 3,
    max_retry_time: RTBCB_API_TIMEOUT
};

function estimateTokens(words) {
    return Math.ceil(words * 1.5);
}

function supportsTemperature(model) {
    const capabilities = rtbcbReport.model_capabilities || {};
    const unsupported = (capabilities.temperature && capabilities.temperature.unsupported) || [];
    return !unsupported.includes(model);
}

/**
 * Validate a URL string.
 *
 * @param {string} url URL to validate.
 * @return {boolean} True if the URL uses http or https.
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

// Fallback for missing or invalid AJAX URL to avoid about:blank requests.
if ( typeof rtbcbReport !== 'undefined' && ! isValidUrl( rtbcbReport.ajax_url ) ) {
    if ( typeof ajaxurl !== 'undefined' && isValidUrl( ajaxurl ) ) {
        rtbcbReport.ajax_url = ajaxurl;
    }
}

async function buildEnhancedPrompt(businessContext) {
    if (!isValidUrl(rtbcbReport.template_url)) {
        throw new Error('Template URL must use HTTP or HTTPS protocol');
    }
    const response = await fetch(rtbcbReport.template_url);
    const template = await response.text();
    const companyName = businessContext && businessContext.companyName ? businessContext.companyName : 'Company';
    const currentDate = businessContext && businessContext.currentDate ? businessContext.currentDate : new Date().toLocaleDateString();
    const contextText = typeof businessContext === 'string'
        ? businessContext
        : (businessContext && businessContext.context ? businessContext.context : '');
    const filledTemplate = template
        .replace(/{{COMPANY_NAME}}/g, companyName)
        .replace(/{{CURRENT_DATE}}/g, currentDate)
        .replace(/{{BUSINESS_CONTEXT}}/g, contextText);
    return `
Generate a professional business consulting report in HTML format with the following requirements:

IMPORTANT: Output ONLY valid HTML code starting with <!DOCTYPE html>. Do not include any markdown formatting or explanation text outside the HTML.

The report should follow this exact structure:

${filledTemplate}

Ensure the report is:
- Exactly 2 pages when printed (approximately 800-1000 words)
- Professional and executive-ready
- Data-driven with specific metrics where applicable
- Action-oriented with clear next steps
`;
}

async function generateProfessionalReport(businessContext, onChunk) {
    const cfg = {
        ...RTBCB_GPT5_DEFAULTS,
        ...(typeof rtbcbReport !== 'undefined' ? rtbcbReport : {})
    };
    cfg.model = rtbcbReport.report_model;
    const adminLimit = Math.min(
        RTBCB_GPT5_MAX_TOKENS,
        parseInt(cfg.max_output_tokens, 10) || RTBCB_GPT5_MAX_TOKENS
    );
    const adminMin = Math.max(
        1,
        parseInt(cfg.min_output_tokens, 10) || RTBCB_GPT5_MIN_TOKENS
    );
    const desiredWords = 1000;
    const bufferedTokens = estimateTokens(desiredWords) * 2;
    cfg.max_output_tokens = Math.min(adminLimit, Math.max(adminMin, bufferedTokens));
    if (!supportsTemperature(cfg.model)) {
        delete cfg.temperature;
    }
    const requestBody = {
        model: cfg.model,
        input: [
            {
                role: 'system',
                content: 'You are a senior BCG consultant creating professional HTML-formatted strategic reports. Output only valid HTML code with no additional text or markdown.'
            },
            {
                role: 'user',
                content: await buildEnhancedPrompt(businessContext)
            }
        ],
        max_output_tokens: cfg.max_output_tokens,
        text: cfg.text,
        store: cfg.store,
        stream: true
    };
    if (supportsTemperature(cfg.model)) {
        requestBody.temperature = cfg.temperature;
    }

    const formData = new FormData();
    formData.append('action', 'rtbcb_openai_responses');
    formData.append('body', JSON.stringify(requestBody));
    if (rtbcbReport.nonce) {
        formData.append('nonce', rtbcbReport.nonce);
    }

    if (!isValidUrl(rtbcbReport.ajax_url)) {
        throw new Error('Invalid AJAX URL');
    }
    const response = await fetch(rtbcbReport.ajax_url, {
        method: 'POST',
        body: formData
    });
    if (!response.ok || !response.body) {
        throw new Error('HTTP ' + response.status + ' ' + response.statusText);
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';
    let html = '';

    while (true) {
        const { done, value } = await reader.read();
        if (done) {
            break;
        }
        buffer += decoder.decode(value, { stream: true });
        const parts = buffer.split('\n\n');
        buffer = parts.pop();
        for (const part of parts) {
            const line = part.trim();
            if (!line.startsWith('data:')) {
                continue;
            }
            const data = line.replace(/^data:\s*/, '');
            if (data === '[DONE]') {
                continue;
            }
            try {
                const json = JSON.parse(data);
                if (json.type === 'response.output_text.delta') {
                    html += json.delta;
                    if (onChunk) {
                        onChunk(html);
                    }
                }
            } catch (e) {
                // Ignore parse errors for incomplete chunks.
            }
        }
    }

    return html.trim();
}

function sanitizeReportHTML(htmlContent) {
    // Sanitize OpenAI-generated HTML before embedding or exporting.
    // Explicitly whitelist tags and attributes required for reports.
    const allowedTags = [
        'a', 'p', 'br', 'strong', 'em', 'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span',
        'table', 'thead', 'tbody', 'tr', 'th', 'td'
    ];
    const allowedAttr = { a: [ 'href', 'title', 'target', 'rel' ], '*': [ 'style' ] };
    return typeof DOMPurify !== 'undefined'
        ? DOMPurify.sanitize(htmlContent, { ALLOWED_TAGS: allowedTags, ALLOWED_ATTR: allowedAttr })
        : htmlContent;
}

function displayReport(htmlContent) {
    const iframe = document.createElement('iframe');
    iframe.style.width = '100%';
    iframe.style.height = '800px';
    iframe.style.border = '1px solid #ddd';
    iframe.srcdoc = sanitizeReportHTML(htmlContent);
    document.getElementById('report-container').appendChild(iframe);
}

function exportToPDF(htmlContent) {
    const sanitized = sanitizeReportHTML(htmlContent);
    const blob = new Blob([sanitized], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const printWindow = window.open(url, '_blank');

    if (printWindow) {
        printWindow.focus();
        printWindow.onload = function() {
            printWindow.print();
            URL.revokeObjectURL(url);
        };
    } else {
        console.error('Failed to open print window');
    }
}

async function generateAndDisplayReport(businessContext) {
    const loadingElement = document.getElementById('loading');
    const errorElement = document.getElementById('error');
    const reportContainer = document.getElementById('report-container');

    loadingElement.style.display = 'block';
    errorElement.style.display = 'none';
    reportContainer.innerHTML = '';

    try {
        const htmlReport = await generateProfessionalReport(businessContext, partial => {
            reportContainer.textContent = partial;
        });

        if (!htmlReport.includes('<!DOCTYPE html>')) {
            throw new Error('Invalid HTML response from API');
        }

        const safeReport = sanitizeReportHTML(htmlReport);
        reportContainer.innerHTML = '';
        displayReport(safeReport);

        const exportBtn = document.createElement('button');
        exportBtn.textContent = 'Export to PDF';
        exportBtn.className = 'export-btn';
        exportBtn.onclick = function() { exportToPDF(safeReport); };
        reportContainer.appendChild(exportBtn);
    } catch (error) {
        errorElement.textContent = 'Error: ' + error.message;
        errorElement.style.display = 'block';
    } finally {
        loadingElement.style.display = 'none';
    }
}

function initializeROIChart() {
    const ctx = document.getElementById('rtbcb-roi-chart');
    const roiData = rtbcbReportData.roiScenarios;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Conservative', 'Base Case', 'Optimistic'],
            datasets: [{
                label: 'Annual Benefit ($)',
                data: [
                    roiData.conservative?.total_annual_benefit || 0,
                    roiData.base?.total_annual_benefit || 0,
                    roiData.optimistic?.total_annual_benefit || 0
                ]
            }]
        }
    });
}
