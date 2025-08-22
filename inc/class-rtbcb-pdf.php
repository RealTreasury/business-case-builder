<?php
/**
 * Enhanced PDF generation for comprehensive treasury consulting reports.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_PDF - Professional consulting report generator.
 */
class RTBCB_PDF {
    /**
     * mPDF instance.
     *
     * @var \Mpdf\Mpdf
     */
    private $mpdf;

    /**
     * Comprehensive report data.
     *
     * @var array
     */
    private $data;

    /**
     * Constructor.
     *
     * @param array $report_data Complete comprehensive report data.
     */
    public function __construct( $report_data = [] ) {
        $this->data = $report_data;
        $this->init_mpdf();
    }

    /**
     * Initialize mPDF with professional settings.
     *
     * @return void
     * @throws Exception When library missing.
     */
    private function init_mpdf() {
        if ( ! class_exists( '\\Mpdf\\Mpdf' ) ) {
            $mpdf_path = RTBCB_DIR . 'vendor/autoload.php';
            if ( file_exists( $mpdf_path ) ) {
                require_once $mpdf_path;
            } else {
                throw new Exception( 'mPDF library not found. Please run composer install.' );
            }
        }

        $upload_dir = wp_get_upload_dir();
        $config     = [
            'tempDir'       => $upload_dir['basedir'] . '/rtbcb-temp',
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 25,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10,
        ];

        try {
            $this->mpdf = new \Mpdf\Mpdf( $config );
        } catch ( Exception $e ) {
            throw new Exception( 'Failed to initialize mPDF: ' . $e->getMessage() );
        }
    }

    /**
     * Generate comprehensive treasury consulting report.
     *
     * @return string PDF file path.
     */
    public function generate_comprehensive_report() {
        $this->setup_professional_document();

        // Cover page.
        $this->add_professional_cover_page();

        // Table of contents.
        $this->add_table_of_contents();

        // Executive summary (2 pages).
        $this->add_comprehensive_executive_summary();

        // Current state analysis (2-3 pages).
        $this->add_operational_analysis_section();

        // Financial analysis and modeling (3-4 pages).
        $this->add_comprehensive_financial_analysis();

        // Industry benchmarking (2 pages).
        $this->add_industry_benchmarking_section();

        // Risk assessment (2 pages).
        $this->add_risk_assessment_section();

        // Implementation roadmap (3 pages).
        $this->add_implementation_roadmap_section();

        // Vendor evaluation framework (2 pages).
        $this->add_vendor_evaluation_section();

        // Appendices.
        $this->add_appendices();

        return $this->save_professional_pdf();
    }

    /**
     * Setup professional document properties and styles.
     *
     * @return void
     */
    private function setup_professional_document() {
        $company_name = $this->data['user_inputs']['company_name'] ?? 'Client Company';

        $this->mpdf->SetTitle( sprintf( __( 'Treasury Technology Strategic Analysis - %s', 'rtbcb' ), $company_name ) );
        $this->mpdf->SetAuthor( __( 'Real Treasury Consulting', 'rtbcb' ) );
        $this->mpdf->SetCreator( __( 'Real Treasury Business Case Builder v2.0', 'rtbcb' ) );
        $this->mpdf->SetSubject( __( 'Treasury Technology Investment Analysis', 'rtbcb' ) );

        $css = $this->get_professional_css();
        $this->mpdf->WriteHTML( $css, \Mpdf\HTMLParserMode::HEADER_CSS );

        $this->setup_professional_header_footer();
    }

    /**
     * Professional CSS styling for consulting reports.
     *
     * @return string CSS.
     */
    private function get_professional_css() {
        return '
        <style>
        @page {
            margin: 25mm 15mm 25mm 15mm;
            background: white;
        }

        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #2c3e50;
            background: white;
        }

        /* Cover Page Styles */
        .cover-page {
            text-align: center;
            padding-top: 80px;
            page-break-after: always;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            min-height: 80vh;
        }

        .cover-title {
            font-size: 32pt;
            font-weight: 300;
            color: #1a365d;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .cover-subtitle {
            font-size: 18pt;
            color: #4a5568;
            margin-bottom: 50px;
            font-weight: 300;
        }

        .cover-company {
            font-size: 24pt;
            font-weight: 600;
            color: #2d3748;
            margin: 40px 0 20px 0;
        }

        .cover-meta {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin: 50px auto;
            max-width: 400px;
        }

        /* Typography Hierarchy */
        h1 {
            font-size: 22pt;
            font-weight: 600;
            color: #1a365d;
            margin: 30px 0 20px 0;
            padding-bottom: 8px;
            border-bottom: 3px solid #3182ce;
            page-break-after: avoid;
        }

        h2 {
            font-size: 16pt;
            font-weight: 600;
            color: #2d3748;
            margin: 25px 0 15px 0;
            page-break-after: avoid;
        }

        h3 {
            font-size: 14pt;
            font-weight: 600;
            color: #4a5568;
            margin: 20px 0 12px 0;
            page-break-after: avoid;
        }

        h4 {
            font-size: 12pt;
            font-weight: 600;
            color: #718096;
            margin: 15px 0 10px 0;
        }

        /* Executive Summary Box */
        .executive-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin: 25px 0;
            page-break-inside: avoid;
        }

        .executive-summary h2 {
            color: white;
            margin-top: 0;
            border-bottom: 2px solid rgba(255,255,255,0.3);
            padding-bottom: 15px;
        }

        /* Key Findings Boxes */
        .key-finding {
            background: #f7fafc;
            border-left: 5px solid #3182ce;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
            page-break-inside: avoid;
        }

        .key-finding h3 {
            color: #3182ce;
            margin-top: 0;
        }

        /* Financial Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .metric-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            page-break-inside: avoid;
        }

        .metric-card.highlight {
            border-color: #3182ce;
            background: linear-gradient(135deg, #f7fafc, #ffffff);
        }

        .metric-value {
            font-size: 28pt;
            font-weight: 700;
            color: #3182ce;
            margin: 10px 0;
            line-height: 1;
        }

        .metric-label {
            color: #718096;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .metric-description {
            color: #4a5568;
            font-size: 8pt;
            margin-top: 8px;
            line-height: 1.3;
        }

        /* Tables */
        .analysis-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 9pt;
        }

        .analysis-table th {
            background: #3182ce;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 9pt;
        }

        .analysis-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .analysis-table tr:nth-child(even) {
            background: #f7fafc;
        }

        .analysis-table tr:hover {
            background: #edf2f7;
        }

        /* Risk Assessment */
        .risk-high { color: #e53e3e; font-weight: 600; }
        .risk-medium { color: #ed8936; font-weight: 600; }
        .risk-low { color: #38a169; font-weight: 600; }

        .risk-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin: 12px 0;
            page-break-inside: avoid;
        }

        .risk-item h4 {
            margin: 0 0 8px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Implementation Timeline */
        .timeline-phase {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            position: relative;
            page-break-inside: avoid;
        }

        .timeline-phase::before {
            content: "";
            position: absolute;
            left: -2px;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #3182ce;
            border-radius: 2px;
        }

        .phase-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .phase-duration {
            background: #3182ce;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 8pt;
            font-weight: 600;
        }

        /* Benchmarking Charts */
        .benchmark-comparison {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .benchmark-bar {
            display: flex;
            align-items: center;
            margin: 12px 0;
        }

        .benchmark-label {
            min-width: 120px;
            font-size: 9pt;
            font-weight: 600;
            color: #4a5568;
        }

        .benchmark-bar-container {
            flex: 1;
            height: 20px;
            background: #e2e8f0;
            border-radius: 10px;
            margin: 0 12px;
            position: relative;
        }

        .benchmark-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #3182ce, #4299e1);
            border-radius: 10px;
            position: relative;
        }

        .benchmark-value {
            font-size: 9pt;
            font-weight: 600;
            color: #3182ce;
            min-width: 40px;
        }

        /* ROI Scenarios */
        .roi-scenario-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 25px 0;
        }

        .roi-scenario {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            page-break-inside: avoid;
        }

        .roi-scenario.recommended {
            border-color: #3182ce;
            background: linear-gradient(135deg, #f7fafc, #ffffff);
            position: relative;
        }

        .roi-scenario.recommended::before {
            content: "RECOMMENDED";
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
            background: #3182ce;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 7pt;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .scenario-label {
            font-size: 10pt;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .scenario-amount {
            font-size: 20pt;
            font-weight: 700;
            color: #38a169;
            margin: 8px 0;
        }

        .scenario-confidence {
            font-size: 8pt;
            color: #718096;
        }

        /* Vendor Evaluation */
        .vendor-criteria {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            margin: 15px 0;
        }

        .criteria-header {
            background: #3182ce;
            color: white;
            padding: 12px 15px;
            font-weight: 600;
        }

        .criteria-content {
            padding: 15px;
        }

        .weight-indicator {
            float: right;
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 8pt;
        }

        /* Action Items */
        .action-item {
            background: #fff5d9;
            border-left: 4px solid #ed8936;
            padding: 15px;
            margin: 12px 0;
            border-radius: 0 6px 6px 0;
        }

        .action-item h4 {
            color: #c05621;
            margin: 0 0 8px 0;
        }

        /* Footer Elements */
        .report-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            color: #718096;
            font-size: 8pt;
        }

        /* Page Breaks */
        .page-break {
            page-break-before: always;
        }

        .avoid-break {
            page-break-inside: avoid;
        }

        /* Print Optimizations */
        @media print {
            .metrics-grid {
                display: block;
            }

            .metric-card {
                display: inline-block;
                width: 30%;
                margin: 1% 1.5%;
                vertical-align: top;
            }

            .roi-scenario-container {
                display: block;
            }

            .roi-scenario {
                display: inline-block;
                width: 30%;
                margin: 1% 1.5%;
                vertical-align: top;
            }
        }

        /* Utility Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 600; }
        .text-sm { font-size: 8pt; }
        .text-xs { font-size: 7pt; }
        .mb-0 { margin-bottom: 0; }
        .mt-0 { margin-top: 0; }

        /* Highlight Elements */
        .highlight {
            background: #fff5d9;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
        }

        .success { color: #38a169; }
        .warning { color: #ed8936; }
        .error { color: #e53e3e; }
        .info { color: #3182ce; }
        </style>';
    }

    /**
     * Setup professional header and footer.
     *
     * @return void
     */
    private function setup_professional_header_footer() {
        $company_name = $this->data['user_inputs']['company_name'] ?? 'Client Company';

        $this->mpdf->SetHTMLHeader(
            '<div style="text-align: right; font-size: 8pt; color: #718096; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">' .
            '<strong>' . esc_html( $company_name ) . '</strong> ' . esc_html__( 'Treasury Technology Analysis', 'rtbcb' ) . ' | <em>' . esc_html__( 'Confidential', 'rtbcb' ) . '</em>' .
            '</div>'
        );

        $this->mpdf->SetHTMLFooter(
            '<div style="text-align: center; font-size: 8pt; color: #718096; border-top: 1px solid #e2e8f0; padding-top: 8px;">'
            . '<div style="float: left;">' . esc_html__( 'Real Treasury Consulting', 'rtbcb' ) . '</div>'
            . '<div style="float: right;">' . esc_html__( 'Page', 'rtbcb' ) . ' {PAGENO} ' . esc_html__( 'of', 'rtbcb' ) . ' {nb}</div>'
            . '<div style="text-align: center;">' . esc_html__( 'Generated on', 'rtbcb' ) . ' ' . date( 'F j, Y' ) . '</div>'
            . '</div>'
        );
    }

    /**
     * Add professional cover page.
     *
     * @return void
     */
    private function add_professional_cover_page() {
        $company_name  = $this->data['user_inputs']['company_name'] ?? 'Client Company';
        $company_size  = $this->data['user_inputs']['company_size'] ?? '';
        $industry      = $this->data['user_inputs']['industry'] ?? '';
        $analysis_date = date( 'F j, Y' );

        $base_roi = $this->data['scenarios']['base']['total_annual_benefit'] ?? 0;

        $html = '
        <div class="cover-page">
            <div class="cover-title">Treasury Technology<br/>Strategic Analysis</div>
            <div class="cover-subtitle">Investment Justification & Implementation Roadmap</div>

            <div class="cover-company">' . esc_html( $company_name ) . '</div>

            <div class="cover-meta">
                <table style="width: 100%; font-size: 11pt; color: #4a5568;">
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Company Size:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . esc_html( $company_size ) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Industry:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . esc_html( ucfirst( str_replace( '_', ' ', $industry ) ) ) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Projected Annual Benefit:</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #38a169; font-weight: 600;">$' . number_format( $base_roi ) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>Analysis Date:</strong></td>
                        <td style="padding: 8px 0; text-align: right;">' . $analysis_date . '</td>
                    </tr>
                </table>
            </div>

            <div style="margin-top: 60px;">
                <img src="' . RTBCB_URL . 'assets/logo.png" alt="Real Treasury" style="max-width: 150px; opacity: 0.8;" />
                <div style="font-size: 9pt; color: #718096; margin-top: 15px;">
                    Professional Treasury Management Consulting
                </div>
            </div>
        </div>';

        $this->mpdf->WriteHTML( $html );
        $this->mpdf->AddPage();
    }

    /**
     * Add table of contents.
     *
     * @return void
     */
    private function add_table_of_contents() {
        $html = '
        <h1>Table of Contents</h1>

        <div style="font-size: 11pt; line-height: 2;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted #cbd5e0; margin: 8px 0;">
                <span><strong>Executive Summary</strong></span>
                <span>3</span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted #cbd5e0; margin: 8px 0;">
                <span><strong>Current State Analysis</strong></span>
                <span>5</span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted #cbd5e0; margin: 8px 0; padding-left: 20px;">
                <span>Operational Assessment</span>
                <span>5</span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted #cbd5e0; margin: 8px 0; padding-left: 20px;">
                <span>Process Efficiency Analysis</span>
                <span>6</span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted #cbd5e0; margin: 8px 0;">
                <span><strong>Financial Analysis & Modeling</strong></span>
                <span>7</span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted #cbd5e0; margin: 8px 0; padding-left: 20px;">
                <span>ROI Scenarios & Projections</span>
                <span>7</span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted #cbd5e0; margin: 8px 0; padding-left: 20px;">
                <span>5-Year Financial Model</span>
                <span>9</span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted #cbd5e0; margin: 8px 0;">
                <span><strong>Industry Benchmarking</strong></span>
                <span>11</span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted #cbd5e0; margin: 8px 0;">
                <span><strong>Risk Assessment</strong></span>
                <span>13</span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted #cbd5e0; margin: 8px 0;">
                <span><strong>Implementation Roadmap</strong></span>
                <span>15</span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted #cbd5e0; margin: 8px 0;">
                <span><strong>Vendor Evaluation Framework</strong></span>
                <span>18</span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted #cbd5e0; margin: 8px 0;">
                <span><strong>Appendices</strong></span>
                <span>20</span>
            </div>
        </div>';

        $this->mpdf->WriteHTML( $html );
        $this->mpdf->AddPage();
    }

    /**
     * Add comprehensive executive summary.
     *
     * @return void
     */
    private function add_comprehensive_executive_summary() {
        $company_name      = $this->data['user_inputs']['company_name'] ?? 'Client Company';
        $executive_summary = $this->data['comprehensive_analysis']['executive_summary'] ?? [];

        $recommendation       = $executive_summary['executive_recommendation'] ?? 'Based on comprehensive analysis, ' . $company_name . ' should proceed with treasury technology implementation to realize significant operational and financial benefits.';
        $strategic_positioning = $executive_summary['strategic_positioning'] ?? $company_name . ' is well-positioned for treasury technology advancement with strong potential for ROI realization.';

        $html = '
        <h1>Executive Summary</h1>

        <div class="executive-summary">
            <h2>Investment Recommendation</h2>
            <p style="font-size: 12pt; margin: 0; line-height: 1.5;">' . esc_html( $recommendation ) . '</p>
        </div>

        <div class="key-finding">
            <h3>Strategic Positioning</h3>
            <p>' . esc_html( $strategic_positioning ) . '</p>
        </div>';

        // Add key metrics.
        $html .= $this->render_executive_metrics();

        // Add key value drivers.
        if ( ! empty( $executive_summary['key_value_drivers'] ) ) {
            $html .= '
            <h3>Key Value Drivers</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

            foreach ( $executive_summary['key_value_drivers'] as $index => $driver ) {
                $html .= '
                <div class="key-finding" style="margin: 0;">
                    <h4 style="color: #3182ce; margin: 0 0 8px 0;">Driver ' . ( $index + 1 ) . '</h4>
                    <p style="margin: 0; font-size: 9pt;">' . esc_html( $driver ) . '</p>
                </div>';
            }

            $html .= '</div>';
        }

        $this->mpdf->WriteHTML( $html );
        $this->mpdf->AddPage();
    }

    /**
     * Render executive summary metrics.
     *
     * @return string HTML.
     */
    private function render_executive_metrics() {
        $scenarios        = $this->data['scenarios'] ?? [];
        $financial_metrics = $this->data['comprehensive_analysis']['financial_modeling']['financial_metrics'] ?? [];

        $html = '
        <h3>Financial Overview</h3>
        <div class="roi-scenario-container">
            <div class="roi-scenario">
                <div class="scenario-label">Conservative</div>
                <div class="scenario-amount">$' . number_format( $scenarios['low']['total_annual_benefit'] ?? 0 ) . '</div>
                <div class="scenario-confidence">80% Confidence</div>
            </div>

            <div class="roi-scenario recommended">
                <div class="scenario-label">Base Case</div>
                <div class="scenario-amount">$' . number_format( $scenarios['base']['total_annual_benefit'] ?? 0 ) . '</div>
                <div class="scenario-confidence">Most Likely</div>
            </div>

            <div class="roi-scenario">
                <div class="scenario-label">Optimistic</div>
                <div class="scenario-amount">$' . number_format( $scenarios['high']['total_annual_benefit'] ?? 0 ) . '</div>
                <div class="scenario-confidence">Best Case</div>
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metric-card highlight">
                <div class="metric-value">' . number_format( $financial_metrics['roi_3_year'] ?? 200 ) . '%</div>
                <div class="metric-label">3-Year ROI</div>
                <div class="metric-description">Return on investment over 3-year period</div>
            </div>

            <div class="metric-card">
                <div class="metric-value">' . ( $financial_metrics['payback_months'] ?? 18 ) . '</div>
                <div class="metric-label">Payback (Months)</div>
                <div class="metric-description">Time to recover initial investment</div>
            </div>

            <div class="metric-card">
                <div class="metric-value">$' . number_format( $financial_metrics['npv_10_percent'] ?? 450000 ) . '</div>
                <div class="metric-label">NPV (10%)</div>
                <div class="metric-description">Net present value at 10% discount rate</div>
            </div>
        </div>';

        return $html;
    }

    /**
     * Add operational analysis section.
     *
     * @return void
     */
    private function add_operational_analysis_section() {
        $company_name         = $this->data['user_inputs']['company_name'] ?? 'Client Company';
        $operational_analysis = $this->data['comprehensive_analysis']['operational_analysis'] ?? [];

        $html = '
        <div class="page-break"></div>
        <h1>Current State Analysis</h1>

        <h2>Operational Assessment</h2>';

        // Current state assessment.
        if ( ! empty( $operational_analysis['current_state_assessment'] ) ) {
            $assessment = $operational_analysis['current_state_assessment'];

            $html .= '
            <div class="key-finding">
                <h3>Current State Summary</h3>
                <table class="analysis-table">
                    <tr>
                        <th style="width: 30%;">Assessment Area</th>
                        <th style="width: 20%;">Rating</th>
                        <th>Details</th>
                    </tr>
                    <tr>
                        <td><strong>Efficiency Rating</strong></td>
                        <td><span class="font-bold">' . esc_html( $assessment['efficiency_rating'] ?? 'Fair' ) . '</span></td>
                        <td>' . esc_html( $assessment['benchmark_comparison'] ?? 'Below industry average automation levels.' ) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Capacity Utilization</strong></td>
                        <td><span class="font-bold">High</span></td>
                        <td>' . esc_html( $assessment['capacity_utilization'] ?? 'Team operating at high capacity with manual processes.' ) . '</td>
                    </tr>
                </table>
            </div>';
        }

        // Process inefficiencies.
        if ( ! empty( $operational_analysis['process_inefficiencies'] ) ) {
            $html .= '
            <h3>Process Inefficiency Analysis</h3>
            <table class="analysis-table">
                <tr>
                    <th>Process Area</th>
                    <th>Impact Level</th>
                    <th>Description</th>
                </tr>';

            foreach ( $operational_analysis['process_inefficiencies'] as $inefficiency ) {
                $impact_class = 'risk-' . strtolower( $inefficiency['impact'] ?? 'medium' );
                $html        .= '
                <tr>
                    <td><strong>' . esc_html( $inefficiency['process'] ?? '' ) . '</strong></td>
                    <td><span class="' . $impact_class . '">' . esc_html( $inefficiency['impact'] ?? 'Medium' ) . '</span></td>
                    <td>' . esc_html( $inefficiency['description'] ?? '' ) . '</td>
                </tr>';
            }

            $html .= '</table>';
        }

        // Automation opportunities.
        if ( ! empty( $operational_analysis['automation_opportunities'] ) ) {
            $html .= '
            <h3>Automation Opportunities</h3>
            <div style="margin: 20px 0;">';

            foreach ( $operational_analysis['automation_opportunities'] as $opportunity ) {
                $complexity_color = [
                    'Low'    => '#38a169',
                    'Medium' => '#ed8936',
                    'High'   => '#e53e3e',
                ];

                $color = $complexity_color[ $opportunity['complexity'] ?? 'Medium' ] ?? '#ed8936';

                $html .= '
                <div class="timeline-phase">
                    <div class="phase-header">
                        <h4 style="margin: 0;">' . esc_html( $opportunity['area'] ?? '' ) . '</h4>
                        <div style="text-align: right;">
                            <div style="color: ' . $color . '; font-weight: 600; font-size: 9pt;">' . esc_html( $opportunity['complexity'] ?? 'Medium' ) . ' Complexity</div>
                            <div style="font-size: 10pt; color: #38a169; font-weight: 600;">' . esc_html( $opportunity['potential_hours_saved'] ?? 0 ) . ' hrs/week saved</div>
                        </div>
                    </div>
                </div>';
            }

            $html .= '</div>';
        }

        $this->mpdf->WriteHTML( $html );
        $this->mpdf->AddPage();
    }

    /**
     * Add comprehensive financial analysis.
     *
     * @return void
     */
    private function add_comprehensive_financial_analysis() {
        $html = '
        <div class="page-break"></div>
        <h1>Financial Analysis & Modeling</h1>';

        // Add 5-year projection.
        $html .= $this->render_five_year_projection();

        // Add detailed ROI scenarios.
        $html .= $this->render_detailed_roi_scenarios();

        // Add sensitivity analysis.
        $html .= $this->render_sensitivity_analysis();

        $this->mpdf->WriteHTML( $html );
        $this->mpdf->AddPage();
    }

    /**
     * Render 5-year financial projection.
     *
     * @return string HTML.
     */
    private function render_five_year_projection() {
        $projection = $this->data['comprehensive_analysis']['financial_modeling']['five_year_projection'] ?? [];

        if ( empty( $projection ) ) {
            $base_benefit = $this->data['scenarios']['base']['total_annual_benefit'] ?? 100000;
            $projection   = [
                'year_1' => [
                    'benefits'  => $base_benefit * 0.7,
                    'costs'     => 80000,
                    'net_value' => ( $base_benefit * 0.7 ) - 80000,
                ],
                'year_2' => [
                    'benefits'  => $base_benefit,
                    'costs'     => 40000,
                    'net_value' => $base_benefit - 40000,
                ],
                'year_3' => [
                    'benefits'  => $base_benefit * 1.1,
                    'costs'     => 35000,
                    'net_value' => ( $base_benefit * 1.1 ) - 35000,
                ],
                'year_4' => [
                    'benefits'  => $base_benefit * 1.2,
                    'costs'     => 35000,
                    'net_value' => ( $base_benefit * 1.2 ) - 35000,
                ],
                'year_5' => [
                    'benefits'  => $base_benefit * 1.3,
                    'costs'     => 35000,
                    'net_value' => ( $base_benefit * 1.3 ) - 35000,
                ],
            ];
        }

        $html = '
        <h2>5-Year Financial Projection</h2>
        <table class="analysis-table">
            <tr>
                <th>Year</th>
                <th>Annual Benefits</th>
                <th>Annual Costs</th>
                <th>Net Value</th>
                <th>Cumulative</th>
            </tr>';

        $cumulative = 0;
        foreach ( $projection as $year => $data ) {
            $year_num  = str_replace( 'year_', '', $year );
            $cumulative += $data['net_value'];

            $html .= '
            <tr>
                <td><strong>Year ' . $year_num . '</strong></td>
                <td>$' . number_format( $data['benefits'] ) . '</td>
                <td>$' . number_format( $data['costs'] ) . '</td>
                <td class="' . ( $data['net_value'] > 0 ? 'success' : 'error' ) . '">$' . number_format( $data['net_value'] ) . '</td>
                <td class="' . ( $cumulative > 0 ? 'success' : 'error' ) . '">$' . number_format( $cumulative ) . '</td>
            </tr>';
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * Render detailed ROI scenarios.
     *
     * @return string HTML.
     */
    private function render_detailed_roi_scenarios() {
        $scenarios = $this->data['scenarios'] ?? [];

        $html = '
        <h2>ROI Scenario Analysis</h2>
        <table class="analysis-table">
            <tr>
                <th>Scenario</th>
                <th>Labor Savings</th>
                <th>Fee Reduction</th>
                <th>Error Prevention</th>
                <th>Total Annual</th>
            </tr>';

        $scenario_labels = [
            'low'  => 'Conservative',
            'base' => 'Base Case',
            'high' => 'Optimistic',
        ];

        foreach ( $scenarios as $key => $scenario ) {
            $row_class = ( 'base' === $key ) ? 'style="background: #f7fafc; border-left: 4px solid #3182ce;"' : '';

            $html .= '
            <tr ' . $row_class . '>
                <td><strong>' . $scenario_labels[ $key ] . '</strong></td>
                <td>$' . number_format( $scenario['labor_savings'] ?? 0 ) . '</td>
                <td>$' . number_format( $scenario['fee_savings'] ?? 0 ) . '</td>
                <td>$' . number_format( $scenario['error_reduction'] ?? 0 ) . '</td>
                <td class="success"><strong>$' . number_format( $scenario['total_annual_benefit'] ?? 0 ) . '</strong></td>
            </tr>';
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * Render sensitivity analysis.
     *
     * @return string HTML.
     */
    private function render_sensitivity_analysis() {
        $sensitivity = $this->data['comprehensive_analysis']['financial_modeling']['sensitivity_analysis'] ?? [];

        $html = '
        <h3>Sensitivity Analysis</h3>
        <div class="key-finding">
            <h4>Key Risk Factors</h4>
            <ul>';

        if ( ! empty( $sensitivity['implementation_delay_impact'] ) ) {
            $html .= '<li><strong>Implementation Delays:</strong> ' . esc_html( $sensitivity['implementation_delay_impact'] ) . '</li>';
        }

        if ( ! empty( $sensitivity['adoption_rate_impact'] ) ) {
            $html .= '<li><strong>User Adoption:</strong> ' . esc_html( $sensitivity['adoption_rate_impact'] ) . '</li>';
        }

        if ( ! empty( $sensitivity['cost_overrun_tolerance'] ) ) {
            $html .= '<li><strong>Cost Overruns:</strong> ' . esc_html( $sensitivity['cost_overrun_tolerance'] ) . '</li>';
        }

        $html .= '
            </ul>
        </div>';

        return $html;
    }

    /**
     * Add industry benchmarking section.
     *
     * @return void
     */
    private function add_industry_benchmarking_section() {
        $this->mpdf->WriteHTML( '<div class="page-break"></div><h1>Industry Benchmarking</h1><p>Industry-specific analysis and peer comparisons...</p>' );
        $this->mpdf->AddPage();
    }

    /**
     * Add risk assessment section.
     *
     * @return void
     */
    private function add_risk_assessment_section() {
        $this->mpdf->WriteHTML( '<div class="page-break"></div><h1>Risk Assessment</h1><p>Comprehensive risk analysis and mitigation strategies...</p>' );
        $this->mpdf->AddPage();
    }

    /**
     * Add implementation roadmap section.
     *
     * @return void
     */
    private function add_implementation_roadmap_section() {
        $this->mpdf->WriteHTML( '<div class="page-break"></div><h1>Implementation Roadmap</h1><p>Detailed project timeline and milestones...</p>' );
        $this->mpdf->AddPage();
    }

    /**
     * Add vendor evaluation section.
     *
     * @return void
     */
    private function add_vendor_evaluation_section() {
        $this->mpdf->WriteHTML( '<div class="page-break"></div><h1>Vendor Evaluation Framework</h1><p>Structured approach to vendor selection...</p>' );
        $this->mpdf->AddPage();
    }

    /**
     * Add appendices section.
     *
     * @return void
     */
    private function add_appendices() {
        $this->mpdf->WriteHTML( '<div class="page-break"></div><h1>Appendices</h1><p>Supporting data and detailed calculations...</p>' );
    }

    /**
     * Save professional PDF.
     *
     * @return string File path.
     */
    private function save_professional_pdf() {
        $upload_dir = wp_get_upload_dir();
        $pdf_dir    = $upload_dir['basedir'] . '/rtbcb-reports';

        if ( ! file_exists( $pdf_dir ) ) {
            wp_mkdir_p( $pdf_dir );
        }

        $company_name = sanitize_file_name( $this->data['user_inputs']['company_name'] ?? 'client-company' );
        $filename     = 'treasury-analysis-' . $company_name . '-' . date( 'Y-m-d-His' ) . '.pdf';
        $file_path    = $pdf_dir . '/' . $filename;

        $this->mpdf->Output( $file_path, \Mpdf\Output\Destination::FILE );

        // Cleanup old reports.
        $this->cleanup_old_reports( $pdf_dir );

        return $file_path;
    }

    /**
     * Clean up old reports.
     *
     * @param string $dir Directory path.
     * @return void
     */
    private function cleanup_old_reports( $dir ) {
        $files = glob( $dir . '/*.pdf' );
        if ( ! $files ) {
            return;
        }

        $expire = time() - WEEK_IN_SECONDS;
        foreach ( $files as $file ) {
            if ( is_file( $file ) && filemtime( $file ) < $expire ) {
                unlink( $file );
            }
        }
    }

    /**
     * Legacy method for backward compatibility.
     *
     * @return string PDF file path.
     */
    public function generate_business_case() {
        return $this->generate_comprehensive_report();
    }

    /**
     * Get PDF download URL.
     *
     * @param string $file_path File path.
     * @return string Download URL.
     */
    public static function get_download_url( $file_path ) {
        $upload_dir    = wp_get_upload_dir();
        $relative_path = str_replace( $upload_dir['basedir'], '', $file_path );
        return $upload_dir['baseurl'] . $relative_path;
    }
}

