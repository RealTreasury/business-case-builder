/**
 * Generate and display professional reports using OpenAI.
 */

const RTBCB_GPT5_MAX_TOKENS = 8000;
const RTBCB_GPT5_DEFAULTS = {
    max_output_tokens: RTBCB_GPT5_MAX_TOKENS,
    text: { verbosity: 'medium' },
    temperature: 0.7,
    store: true,
    timeout: 180,
    max_retries: 3
};

function estimateTokens(words) {
    return Math.ceil(words * 1.5);
}

function supportsTemperature(model) {
    const capabilities = rtbcbReport.model_capabilities || {};
    const unsupported = (capabilities.temperature && capabilities.temperature.unsupported) || [];
    return !unsupported.includes(model);
}

async function buildEnhancedPrompt(businessContext) {
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

async function generateProfessionalReport(businessContext) {
    const cfg = {
        ...RTBCB_GPT5_DEFAULTS,
        ...(typeof rtbcbReport !== 'undefined' ? rtbcbReport : {})
    };
    cfg.model = rtbcbReport.report_model;
    const adminLimit = Math.min(RTBCB_GPT5_MAX_TOKENS, parseInt(cfg.max_output_tokens, 10) || RTBCB_GPT5_MAX_TOKENS);
    const desiredWords = 1000;
    cfg.max_output_tokens = Math.min(adminLimit, estimateTokens(desiredWords));
    if ( !supportsTemperature( cfg.model ) ) {
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
        store: cfg.store
    };
    if ( supportsTemperature( cfg.model ) ) {
        requestBody.temperature = cfg.temperature;
    }

    const maxAttempts = cfg.max_retries || 3;
    let lastError;

    for (let attempt = 1; attempt <= maxAttempts; attempt++) {
        const formData = new FormData();
        formData.append('action', 'rtbcb_openai_responses');
        formData.append('body', JSON.stringify(requestBody));

        try {
            const response = await fetch(rtbcbReport.ajax_url, {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                let data;
                try {
                    data = await response.json();
                } catch (parseError) {
                    lastError = parseError;
                    continue;
                }

                if (data.error) {
                    console.error('Attempt ' + attempt + ' API error details:', data.error);
                    lastError = new Error(data.error.message || 'Responses API error');
                    continue;
                }

                const htmlContent = data.output_text;
                const cleanedHTML = htmlContent
                    .replace(/```html\n?/g, '')
                    .replace(/```\n?/g, '')
                    .trim();
                return cleanedHTML;
            }

            if (rtbcbReport && rtbcbReport.debug) {
                const responseText = await response.text();
                console.error('Attempt ' + attempt + ' failed:', responseText);
                console.error('RTBCB request body:', requestBody);
            }
            lastError = new Error('HTTP ' + response.status + ' ' + response.statusText);
        } catch (error) {
            console.error('Error generating report (attempt ' + attempt + '):', error);
            lastError = error;
        }
    }

    console.error('All attempts to generate report failed:', lastError);
    throw new Error(lastError && lastError.message ? lastError.message : 'Unable to generate report at this time. Please try again later.');
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
    const printWindow = window.open('', '_blank');
    const doc = printWindow.document;
    doc.documentElement.innerHTML = sanitizeReportHTML(htmlContent);
    printWindow.focus();
    printWindow.print();
}

async function generateAndDisplayReport(businessContext) {
    const loadingElement = document.getElementById('loading');
    const errorElement = document.getElementById('error');
    const reportContainer = document.getElementById('report-container');

    loadingElement.style.display = 'block';
    errorElement.style.display = 'none';
    reportContainer.innerHTML = '';

    try {
        const htmlReport = await generateProfessionalReport(businessContext);

        if (!htmlReport.includes('<!DOCTYPE html>')) {
            throw new Error('Invalid HTML response from API');
        }

        const safeReport = sanitizeReportHTML(htmlReport);
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
