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

async function generateProfessionalReport(businessContext, onChunk) {
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
                const headers = response.headers || {};
                let contentType = '';
                if (typeof headers.get === 'function') {
                    contentType = headers.get('content-type') || '';
                } else if (headers['content-type']) {
                    contentType = headers['content-type'];
                }
                if (contentType.includes('application/json')) {
                    let data;
                    try {
                        data = await response.json();
                    } catch (e) {
                        lastError = e;
                        continue;
                    }
                    if (data.error) {
                        lastError = new Error(data.error.message || 'Responses API error');
                        continue;
                    }
                    const htmlContent = data.output_text || '';
                    const cleanedHTML = htmlContent
                        .replace(/```html\n?/g, '')
                        .replace(/```\n?/g, '')
                        .trim();
                    return cleanedHTML;
                }

                if (response.body) {
                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';
                    let result = '';

                    while (true) {
                        const { value, done } = await reader.read();
                        if (done) {
                            break;
                        }
                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n');
                        buffer = lines.pop();
                        for (const line of lines) {
                            const trimmed = line.trim();
                            if (!trimmed.startsWith('data:')) {
                                continue;
                            }
                            const payload = trimmed.slice(5).trim();
                            if (payload === '[DONE]') {
                                continue;
                            }
                            let json;
                            try {
                                json = JSON.parse(payload);
                            } catch (e) {
                                continue;
                            }
                            if (json.delta) {
                                result += json.delta;
                                if (typeof onChunk === 'function') {
                                    onChunk(result);
                                }
                            } else if (json.output_text) {
                                result = json.output_text;
                            } else if (json.error) {
                                lastError = new Error(json.error.message || 'Responses API error');
                                break;
                            }
                        }
                    }

                    if (result) {
                        const cleanedHTML = result
                            .replace(/```html\n?/g, '')
                            .replace(/```\n?/g, '')
                            .trim();
                        return cleanedHTML;
                    }
                } else {
                    if (typeof response.json === 'function') {
                        try {
                            const data = await response.json();
                            if (data.error) {
                                lastError = new Error(data.error.message || 'Responses API error');
                                continue;
                            }
                            const htmlContent = data.output_text || '';
                            const cleanedHTML = htmlContent
                                .replace(/```html\n?/g, '')
                                .replace(/```\n?/g, '')
                                .trim();
                            return cleanedHTML;
                        } catch (e) {
                            lastError = e;
                            continue;
                        }
                    }

                    try {
                        const text = await response.text();
                        const data = JSON.parse(text);
                        if (data.error) {
                            lastError = new Error(data.error.message || 'Responses API error');
                            continue;
                        }
                        const htmlContent = data.output_text || '';
                        const cleanedHTML = htmlContent
                            .replace(/```html\n?/g, '')
                            .replace(/```\n?/g, '')
                            .trim();
                        return cleanedHTML;
                    } catch (e) {
                        lastError = e;
                        continue;
                    }
                }
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
        const htmlReport = await generateProfessionalReport(businessContext, partial => {
            reportContainer.textContent = partial;
        });

        if (!htmlReport.includes('<!DOCTYPE html>')) {
            throw new Error('Invalid HTML response from API');
        }

        const safeReport = sanitizeReportHTML(htmlReport);
        reportContainer.textContent = '';
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
