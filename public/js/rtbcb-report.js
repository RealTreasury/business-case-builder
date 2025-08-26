/**
 * Generate and display professional reports using OpenAI.
 */

const RTBCB_GPT5_DEFAULTS = {
    max_output_tokens: 20000,
    text: { verbosity: 'medium' },
    temperature: 0.7,
    store: true,
    timeout: 120,
    max_retries: 3
};

function supportsTemperature(model) {
    const capabilities = rtbcbReport.model_capabilities || {};
    const unsupported = (capabilities.temperature && capabilities.temperature.unsupported) || [];
    return !unsupported.includes(model);
}

function buildEnhancedPrompt(businessContext) {
    return `
Generate a professional business consulting report in HTML format with the following requirements:

IMPORTANT: Output ONLY valid HTML code starting with <!DOCTYPE html>. Do not include any markdown formatting or explanation text outside the HTML.

The report should follow this exact structure:

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCG Strategic Analysis Report</title>
    <style>
        /* Professional Report Styling */
        @page {
            size: A4;
            margin: 2cm;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }
        
        .header {
            border-bottom: 3px solid #0066cc;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .report-title {
            color: #0066cc;
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 10px 0;
        }
        
        .report-subtitle {
            color: #666;
            font-size: 16px;
            margin: 5px 0;
        }
        
        .report-date {
            color: #999;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .executive-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .executive-summary h2 {
            margin-top: 0;
            font-size: 20px;
            border-bottom: 2px solid rgba(255,255,255,0.3);
            padding-bottom: 10px;
        }
        
        .key-findings {
            background: #f8f9fa;
            border-left: 4px solid #0066cc;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        
        .key-findings h3 {
            color: #0066cc;
            margin-top: 0;
            font-size: 18px;
        }
        
        .key-findings ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .key-findings li {
            margin: 8px 0;
            line-height: 1.5;
        }
        
        .recommendation-box {
            background: white;
            border: 2px solid #0066cc;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .recommendation-box h3 {
            color: #0066cc;
            margin-top: 0;
            display: flex;
            align-items: center;
        }
        
        .recommendation-number {
            background: #0066cc;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .metric-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .metric-value {
            font-size: 32px;
            font-weight: bold;
            color: #0066cc;
            margin: 10px 0;
        }
        
        .metric-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        h2 {
            color: #2c3e50;
            font-size: 22px;
            margin-top: 35px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        h3 {
            color: #34495e;
            font-size: 18px;
            margin-top: 25px;
            margin-bottom: 12px;
        }
        
        p {
            margin: 12px 0;
            text-align: justify;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
        
        .highlight {
            background: #fffacd;
            padding: 2px 4px;
            border-radius: 3px;
        }
        
        strong {
            color: #2c3e50;
            font-weight: 600;
        }
        
        @media print {
            body {
                padding: 0;
            }
            .executive-summary {
                page-break-after: avoid;
            }
            .recommendation-box {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    [GENERATE THE REPORT CONTENT HERE FOLLOWING THIS STRUCTURE:]
    
    <div class="header">
        <h1 class="report-title">[Company Name] Strategic Analysis</h1>
        <div class="report-subtitle">Boston Consulting Group Assessment</div>
        <div class="report-date">[Current Date]</div>
    </div>
    
    <div class="executive-summary">
        <h2>Executive Summary</h2>
        <p>[Provide a concise 2-3 sentence overview of the strategic position and main recommendations]</p>
    </div>
    
    <div class="key-findings">
        <h3>Key Strategic Findings</h3>
        <ul>
            <li><strong>[Finding 1]:</strong> [Brief explanation]</li>
            <li><strong>[Finding 2]:</strong> [Brief explanation]</li>
            <li><strong>[Finding 3]:</strong> [Brief explanation]</li>
        </ul>
    </div>
    
    [Continue with main content sections - Analysis, Recommendations, etc.]
    
    <div class="footer">
        <p>© 2024 Strategic Analysis Report | Confidential</p>
    </div>
</body>
</html>

Context for analysis: ${businessContext}

Ensure the report is:
- Exactly 2 pages when printed (approximately 800-1000 words)
- Professional and executive-ready
- Data-driven with specific metrics where applicable
- Action-oriented with clear next steps
`;
}

function generateProfessionalReport(businessContext) {
    const cfg = {
        ...RTBCB_GPT5_DEFAULTS,
        ...(typeof rtbcbReport !== 'undefined' ? rtbcbReport : {})
    };
    cfg.model = rtbcbReport.report_model;
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
                content: buildEnhancedPrompt(businessContext)
            }
        ],
        max_output_tokens: cfg.max_output_tokens,
        text: cfg.text,
        store: cfg.store
    };
    if ( supportsTemperature( cfg.model ) ) {
        requestBody.temperature = cfg.temperature;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', rtbcbReport.ajax_url, false);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    const params = new URLSearchParams({
        action: 'rtbcb_generate_report',
        nonce: rtbcbReport.nonce,
        request: JSON.stringify(requestBody)
    });
    xhr.send(params.toString());

    if (xhr.status < 200 || xhr.status >= 300) {
        throw new Error('HTTP ' + xhr.status);
    }

    const data = JSON.parse(xhr.responseText);

    if (!data.success) {
        const errorMessage = (data.data && data.data.message) ? data.data.message : 'Server error';
        throw new Error(errorMessage);
    }

    const htmlContent = data.data.html;
    const cleanedHTML = htmlContent
        .replace(/```html\n?/g, '')
        .replace(/```\n?/g, '')
        .trim();

    return cleanedHTML;
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

    const triggerPrint = () => {
        printWindow.focus();
        printWindow.print();
    };

    if (printWindow.requestAnimationFrame) {
        printWindow.requestAnimationFrame(triggerPrint);
    } else {
        setTimeout(triggerPrint, 0);
    }
}

function generateAndDisplayReport(businessContext) {
    const loadingElement = document.getElementById('loading');
    const errorElement = document.getElementById('error');
    const reportContainer = document.getElementById('report-container');

    try {
        if (loadingElement) {
            loadingElement.style.display = 'block';
        }
        if (errorElement) {
            errorElement.style.display = 'none';
        }
        if (reportContainer) {
            reportContainer.innerHTML = '';
        }

        const htmlReport = generateProfessionalReport(businessContext);

        if (!htmlReport.includes('<!DOCTYPE html>')) {
            throw new Error('Invalid HTML response from API');
        }

        const safeReport = sanitizeReportHTML(htmlReport);
        displayReport(safeReport);

        const exportBtn = document.createElement('button');
        exportBtn.textContent = 'Export to PDF';
        exportBtn.className = 'export-btn';
        exportBtn.onclick = () => exportToPDF(safeReport);
        reportContainer.appendChild(exportBtn);

    } catch (error) {
        if (errorElement) {
            errorElement.textContent = `Error: ${error.message}`;
            errorElement.style.display = 'block';
        }
    } finally {
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }
}
