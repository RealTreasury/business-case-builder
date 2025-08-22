<?php
/**
 * Enhanced PDF generation for professional business case reports.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_PDF - Enhanced PDF generation with charts and professional formatting.
 */
class RTBCB_PDF {
    /**
     * mPDF instance.
     *
     * @var \Mpdf\Mpdf
     */
    private $mpdf;

    /**
     * Report data.
     *
     * @var array
     */
    private $data;

    /**
     * Constructor.
     *
     * @param array $report_data Complete report data.
     */
    public function __construct( $report_data = [] ) {
        $this->data = $report_data;
        $this->init_mpdf();
    }

    /**
     * Initialize mPDF with custom settings.
     *
     * @return void
     * @throws Exception When library missing.
     */
    private function init_mpdf() {
        if ( ! class_exists( '\\Mpdf\\Mpdf' ) ) {
            $mpdf_path = RTBCB_DIR . 'vendor/mPDF/vendor/autoload.php';
            if ( file_exists( $mpdf_path ) ) {
                require_once $mpdf_path;
            } else {
                throw new Exception( 'mPDF library not found. Please install mPDF.' );
            }
        }

        $upload_dir = wp_get_upload_dir();
        $config     = [
            'tempDir'       => $upload_dir['basedir'] . '/rtbcb-temp',
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 20,
            'margin_bottom' => 20,
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
     * Generate complete business case PDF.
     *
     * @return string PDF file path.
     */
    public function generate_business_case() {
        $this->setup_document();
        $this->add_cover_page();
        $this->add_executive_summary();
        $this->add_roi_analysis();
        $this->add_recommendation_section();
        $this->add_assumptions_section();
        $this->add_next_steps();
        $this->add_appendix();

        return $this->save_pdf();
    }

    /**
     * Generate a comprehensive consulting report.
     *
     * This placeholder method currently delegates to the standard business
     * case generator but allows callers to request a professional report
     * layout when available.
     *
     * @return string PDF file path.
     */
    public function generate_comprehensive_report() {
        return $this->generate_business_case();
    }

    /**
     * Setup document properties and styles.
     *
     * @return void
     */
    private function setup_document() {
        $this->mpdf->SetTitle( 'Treasury Technology Business Case' );
        $this->mpdf->SetAuthor( 'Real Treasury' );
        $this->mpdf->SetCreator( 'Real Treasury Business Case Builder' );
        $this->mpdf->SetSubject( 'ROI Analysis and Recommendation' );

        $css = $this->get_pdf_styles();
        $this->mpdf->WriteHTML( $css, \Mpdf\HTMLParserMode::HEADER_CSS );

        $this->setup_header_footer();
    }

    /**
     * Get PDF-specific CSS styles.
     *
     * @return string CSS content.
     */
    private function get_pdf_styles() {
        return '
        <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
        }
        
        body {
            font-family: "Helvetica", sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
        }
        
        .cover-page {
            text-align: center;
            padding-top: 100px;
        }
        
        .cover-title {
            font-size: 28pt;
            font-weight: bold;
            color: #7216f4;
            margin-bottom: 20px;
        }
        
        .cover-subtitle {
            font-size: 16pt;
            color: #666;
            margin-bottom: 40px;
        }
        
        .cover-company {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .cover-date {
            font-size: 12pt;
            color: #888;
        }
        
        h1 {
            font-size: 20pt;
            font-weight: bold;
            color: #7216f4;
            margin-top: 20px;
            margin-bottom: 15px;
            border-bottom: 2px solid #7216f4;
            padding-bottom: 5px;
        }
        
        h2 {
            font-size: 16pt;
            font-weight: bold;
            color: #333;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        
        h3 {
            font-size: 14pt;
            font-weight: bold;
            color: #555;
            margin-top: 12px;
            margin-bottom: 8px;
        }
        
        .roi-summary {
            background: #f8f9ff;
            border: 2px solid #7216f4;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .roi-scenarios {
            display: table;
            width: 100%;
            margin: 15px 0;
        }
        
        .roi-scenario {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 15px;
            background: white;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .roi-scenario:first-child {
            border-radius: 8px 0 0 8px;
        }
        
        .roi-scenario:last-child {
            border-radius: 0 8px 8px 0;
        }
        
        .roi-scenario h4 {
            margin: 0 0 10px 0;
            font-size: 12pt;
            color: #7216f4;
        }
        
        .roi-amount {
            font-size: 18pt;
            font-weight: bold;
            color: #10b981;
        }
        
        .recommendation-box {
            background: linear-gradient(135deg, #7216f4, #8f47f6);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .recommendation-title {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .features-grid {
            display: table;
            width: 100%;
            margin: 15px 0;
        }
        
        .features-column {
            display: table-cell;
            width: 50%;
            padding: 0 10px;
            vertical-align: top;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            background: #f0f8ff;
            margin: 5px 0;
            padding: 8px 12px;
            border-left: 3px solid #7216f4;
            font-size: 10pt;
        }
        
        .key-metric {
            background: #e8f5e8;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 10px 0;
        }
        
        .key-metric-label {
            font-weight: bold;
            color: #10b981;
            font-size: 10pt;
        }
        
        .key-metric-value {
            font-size: 16pt;
            font-weight: bold;
            color: #333;
        }
        
        .assumptions-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .assumptions-table th,
        .assumptions-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .assumptions-table th {
            background: #7216f4;
            color: white;
            font-weight: bold;
        }
        
        .assumptions-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .next-steps {
            background: #fff8e1;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .next-steps h3 {
            color: #f57c00;
            margin-top: 0;
        }
        
        .chart-container {
            text-align: center;
            margin: 20px 0;
        }
        
        .chart-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #7216f4;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .footer-branding {
            text-align: center;
            margin-top: 30px;
            font-size: 9pt;
            color: #888;
        }
        </style>';
    }

    /**
     * Setup header and footer.
     *
     * @return void
     */
    private function setup_header_footer() {
        $this->mpdf->SetHTMLHeader( '
            <div style="text-align: right; font-size: 9pt; color: #888; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                Treasury Technology Business Case | Real Treasury
            </div>
        ' );

        $this->mpdf->SetHTMLFooter( '
            <div style="text-align: center; font-size: 9pt; color: #888; border-top: 1px solid #ddd; padding-top: 5px;">
                Page {PAGENO} of {nb} | Generated on ' . date( 'F j, Y' ) . '
            </div>
        ' );
    }

    /**
     * Add cover page.
     *
     * @return void
     */
    private function add_cover_page() {
        $company_size = $this->data['user_inputs']['company_size'] ?? 'Mid-Market';
        $date         = date( 'F j, Y' );

        $html = '
        <div class="cover-page">
            <div class="cover-title">Treasury Technology<br/>Business Case</div>
            <div class="cover-subtitle">ROI Analysis & Strategic Recommendation</div>
            <div style="margin: 60px 0;">
                <img src="' . RTBCB_URL . 'assets/logo.png" alt="Real Treasury" style="max-width: 200px;" />
            </div>
            <div class="cover-company">Company Profile: ' . esc_html( $company_size ) . '</div>
            <div class="cover-date">' . $date . '</div>
        </div>';

        $this->mpdf->WriteHTML( $html );
        $this->mpdf->AddPage();
    }

    /**
     * Add executive summary section.
     *
     * @return void
     */
    private function add_executive_summary() {
        $base_roi       = $this->data['scenarios']['base']['total_annual_benefit'] ?? 0;
        $category       = $this->data['recommendation']['recommended'] ?? 'tms_lite';
        $category_info  = $this->data['recommendation']['category_info'] ?? [];
        $narrative      = $this->data['narrative']['narrative'] ?? '';

        $html = '
        <h1>Executive Summary</h1>
        
        <div class="roi-summary">
            <h2 style="margin-top: 0; color: #7216f4;">Investment Opportunity</h2>
            <p>Based on your current treasury operations profile, implementing a treasury technology solution could generate significant annual benefits.</p>
            
            <div class="roi-scenarios">
                <div class="roi-scenario">
                    <h4>Conservative</h4>
                    <div class="roi-amount">$' . number_format( $this->data['scenarios']['low']['total_annual_benefit'] ?? 0 ) . '</div>
                </div>
                <div class="roi-scenario">
                    <h4>Base Case</h4>
                    <div class="roi-amount">$' . number_format( $base_roi ) . '</div>
                </div>
                <div class="roi-scenario">
                    <h4>Optimistic</h4>
                    <div class="roi-amount">$' . number_format( $this->data['scenarios']['high']['total_annual_benefit'] ?? 0 ) . '</div>
                </div>
            </div>
        </div>
        
        <div class="key-metric">
            <div class="key-metric-label">RECOMMENDED SOLUTION CATEGORY</div>
            <div class="key-metric-value">' . esc_html( $category_info['name'] ?? 'Treasury Management System' ) . '</div>
        </div>
        
        <h3>Key Findings</h3>
        <p>' . esc_html( $narrative ) . '</p>
        
        <h3>Primary Benefits</h3>
        <ul>
            <li><strong>Labor Cost Savings:</strong> $' . number_format( $this->data['scenarios']['base']['labor_savings'] ?? 0 ) . ' annually</li>
            <li><strong>Bank Fee Reduction:</strong> $' . number_format( $this->data['scenarios']['base']['fee_savings'] ?? 0 ) . ' annually</li>
            <li><strong>Error Reduction Value:</strong> $' . number_format( $this->data['scenarios']['base']['error_reduction'] ?? 0 ) . ' annually</li>
        </ul>';

        $this->mpdf->WriteHTML( $html );
        $this->mpdf->AddPage();
    }

    /**
     * Add detailed ROI analysis section.
     *
     * @return void
     */
    private function add_roi_analysis() {
        $html = '
        <h1>ROI Analysis & Methodology</h1>
        
        <h2>Current State Assessment</h2>
        <p>Your treasury operations profile indicates the following baseline metrics:</p>
        
        <table class="assumptions-table">
            <tr>
                <th>Metric</th>
                <th>Current Value</th>
                <th>Industry Benchmark</th>
            </tr>
            <tr>
                <td>Weekly Reconciliation Hours</td>
                <td>' . ( $this->data['user_inputs']['hours_reconciliation'] ?? 0 ) . '</td>
                <td>5-15 hours (automated)</td>
            </tr>
            <tr>
                <td>Weekly Cash Positioning Hours</td>
                <td>' . ( $this->data['user_inputs']['hours_cash_positioning'] ?? 0 ) . '</td>
                <td>2-8 hours (automated)</td>
            </tr>
            <tr>
                <td>Banking Relationships</td>
                <td>' . ( $this->data['user_inputs']['num_banks'] ?? 0 ) . '</td>
                <td>3-12 typical</td>
            </tr>
            <tr>
                <td>Treasury Team Size (FTE)</td>
                <td>' . ( $this->data['user_inputs']['ftes'] ?? 0 ) . '</td>
                <td>1-5 typical</td>
            </tr>
        </table>';

        $html .= $this->generate_benefit_breakdown_chart();

        $html .= '
        <h2>ROI Calculation Methodology</h2>
        <p>Our ROI calculations are based on industry benchmarks and proven efficiency gains from treasury technology implementations:</p>
        
        <h3>Labor Cost Savings</h3>
        <ul>
            <li>Efficiency improvement: 20-40% reduction in manual tasks</li>
            <li>Hourly cost assumption: $100 (fully loaded)</li>
            <li>Annual calculation: (Hours saved per week × 52 weeks × $100)</li>
        </ul>
        
        <h3>Bank Fee Reduction</h3>
        <ul>
            <li>Better cash positioning reduces excess balances</li>
            <li>Automated sweeps optimize interest earnings</li>
            <li>Fee reduction: 5-12% of annual bank fees</li>
        </ul>
        
        <h3>Error Reduction Benefits</h3>
        <ul>
            <li>Automated processes reduce human error</li>
            <li>Better controls prevent costly mistakes</li>
            <li>Risk mitigation value: 20-30% of error cost baseline</li>
        </ul>';

        $this->mpdf->WriteHTML( $html );
        $this->mpdf->AddPage();
    }

    /**
     * Add recommendation section.
     *
     * @return void
     */
    private function add_recommendation_section() {
        $recommendation = $this->data['recommendation'] ?? [];
        $category_info  = $recommendation['category_info'] ?? [];
        
        $html = '
        <h1>Strategic Recommendation</h1>
        
        <div class="recommendation-box">
            <div class="recommendation-title">Recommended Solution: ' . esc_html( $category_info['name'] ?? 'Treasury Management System' ) . '</div>
            <p style="margin: 0; font-size: 12pt;">' . esc_html( $category_info['description'] ?? '' ) . '</p>
        </div>
        
        <h2>Why This Category Fits Your Needs</h2>
        <p>' . esc_html( $recommendation['reasoning'] ?? 'This recommendation is based on your company profile and operational requirements.' ) . '</p>
        
        <h2>Key Features & Capabilities</h2>
        <div class="features-grid">
            <div class="features-column">
                <ul class="feature-list">';
        
        $features = $category_info['features'] ?? [];
        $half     = ceil( count( $features ) / 2 );
        
        for ( $i = 0; $i < $half; $i++ ) {
            if ( isset( $features[ $i ] ) ) {
                $html .= '<li>' . esc_html( $features[ $i ] ) . '</li>';
            }
        }
        
        $html .= '
                </ul>
            </div>
            <div class="features-column">
                <ul class="feature-list">';
        
        for ( $i = $half; $i < count( $features ); $i++ ) {
            if ( isset( $features[ $i ] ) ) {
                $html .= '<li>' . esc_html( $features[ $i ] ) . '</li>';
            }
        }
        
        $html .= '
                </ul>
            </div>
        </div>
        
        <h2>Implementation Considerations</h2>
        <ul>
            <li><strong>Timeline:</strong> Typical implementation takes 3-6 months</li>
            <li><strong>Change Management:</strong> User training and process documentation required</li>
            <li><strong>Integration:</strong> API connections to existing ERP and banking systems</li>
            <li><strong>Data Migration:</strong> Historical data conversion and validation</li>
        </ul>';

        $alternatives = $recommendation['alternatives'] ?? [];
        if ( ! empty( $alternatives ) ) {
            $html .= '<h2>Alternative Considerations</h2>';
            foreach ( $alternatives as $alt ) {
                $alt_info = $alt['info'] ?? [];
                $html    .= '
                <h3>' . esc_html( $alt_info['name'] ?? '' ) . '</h3>
                <p>' . esc_html( $alt_info['description'] ?? '' ) . '</p>';
            }
        }

        $this->mpdf->WriteHTML( $html );
        $this->mpdf->AddPage();
    }

    /**
     * Add assumptions section.
     *
     * @return void
     */
    private function add_assumptions_section() {
        $html = '
        <h1>Key Assumptions & Risk Factors</h1>
        
        <h2>ROI Calculation Assumptions</h2>
        <table class="assumptions-table">
            <tr>
                <th>Assumption</th>
                <th>Conservative</th>
                <th>Base Case</th>
                <th>Optimistic</th>
            </tr>
            <tr>
                <td>Efficiency Improvement</td>
                <td>24%</td>
                <td>30%</td>
                <td>36%</td>
            </tr>
            <tr>
                <td>Error Reduction</td>
                <td>20%</td>
                <td>25%</td>
                <td>30%</td>
            </tr>
            <tr>
                <td>Bank Fee Reduction</td>
                <td>6.4%</td>
                <td>8%</td>
                <td>9.6%</td>
            </tr>
            <tr>
                <td>Implementation Success</td>
                <td>80%</td>
                <td>100%</td>
                <td>120%</td>
            </tr>
        </table>
        
        <h2>Risk Factors</h2>
        <ul>
            <li><strong>Implementation Risk:</strong> Timeline delays or scope creep could impact ROI realization</li>
            <li><strong>User Adoption:</strong> Success depends on team training and change management</li>
            <li><strong>Technical Integration:</strong> Complex system environments may require additional resources</li>
            <li><strong>Vendor Selection:</strong> Choosing the wrong solution could limit benefit realization</li>
        </ul>
        
        <h2>Critical Success Factors</h2>
        <ul>
            <li>Executive sponsorship and clear project governance</li>
            <li>Comprehensive user training and support</li>
            <li>Phased implementation with quick wins</li>
            <li>Regular progress monitoring and adjustment</li>
            <li>Integration with existing systems and processes</li>
        </ul>';

        $this->mpdf->WriteHTML( $html );
        $this->mpdf->AddPage();
    }

    /**
     * Add next steps section.
     *
     * @return void
     */
    private function add_next_steps() {
        $html = '
        <h1>Recommended Next Steps</h1>
        
        <div class="next-steps">
            <h3>Immediate Actions (Next 30 Days)</h3>
            <ol>
                <li><strong>Stakeholder Alignment:</strong> Present this business case to key stakeholders</li>
                <li><strong>Budget Planning:</strong> Incorporate treasury technology into next budget cycle</li>
                <li><strong>Vendor Research:</strong> Begin evaluating solution providers in the recommended category</li>
                <li><strong>Internal Assessment:</strong> Document current processes and pain points in detail</li>
            </ol>
        </div>
        
        <div class="next-steps">
            <h3>Medium-term Actions (2-3 Months)</h3>
            <ol>
                <li><strong>RFP Development:</strong> Create detailed requirements and vendor evaluation criteria</li>
                <li><strong>Vendor Demos:</strong> Schedule demonstrations with 3-5 qualified vendors</li>
                <li><strong>Reference Calls:</strong> Speak with existing customers in similar situations</li>
                <li><strong>Implementation Planning:</strong> Develop project timeline and resource requirements</li>
            </ol>
        </div>
        
        <h2>Vendor Evaluation Criteria</h2>
        <table class="assumptions-table">
            <tr>
                <th>Criteria</th>
                <th>Weight</th>
                <th>Key Considerations</th>
            </tr>
            <tr>
                <td>Functional Fit</td>
                <td>30%</td>
                <td>Core treasury features, automation capabilities</td>
            </tr>
            <tr>
                <td>Integration Capabilities</td>
                <td>25%</td>
                <td>ERP connectivity, banking APIs, data formats</td>
            </tr>
            <tr>
                <td>Implementation & Support</td>
                <td>20%</td>
                <td>Project methodology, training, ongoing support</td>
            </tr>
            <tr>
                <td>Total Cost of Ownership</td>
                <td>15%</td>
                <td>License, implementation, maintenance costs</td>
            </tr>
            <tr>
                <td>Vendor Stability</td>
                <td>10%</td>
                <td>Financial strength, market presence, roadmap</td>
            </tr>
        </table>';

        $this->mpdf->WriteHTML( $html );
        $this->mpdf->AddPage();
    }

    /**
     * Add appendix section.
     *
     * @return void
     */
    private function add_appendix() {
        $html = '
        <h1>Appendix</h1>
        
        <h2>About This Analysis</h2>
        <p>This business case was generated using the Real Treasury Business Case Builder, which leverages industry benchmarks, best practices, and proven ROI methodologies to provide data-driven investment analysis for treasury technology.</p>
        
        <h2>Data Sources</h2>
        <ul>
            <li>Association for Financial Professionals (AFP) surveys</li>
            <li>Treasury technology vendor case studies</li>
            <li>Industry consultant analysis and benchmarks</li>
            <li>Real Treasury platform data and insights</li>
        </ul>
        
        <h2>Methodology Notes</h2>
        <p>ROI calculations are based on conservative industry benchmarks and assume successful implementation. Actual results may vary based on specific circumstances, implementation quality, and user adoption rates.</p>
        
        <div class="footer-branding">
            <p><strong>Real Treasury</strong><br/>
            Empowering Treasury Teams with Data-Driven Insights<br/>
            www.realtreasury.com</p>
        </div>';

        $this->mpdf->WriteHTML( $html );
    }

    /**
     * Generate benefit breakdown visualization.
     *
     * @return string HTML for chart.
     */
    private function generate_benefit_breakdown_chart() {
        $labor  = $this->data['scenarios']['base']['labor_savings'] ?? 0;
        $fees   = $this->data['scenarios']['base']['fee_savings'] ?? 0;
        $errors = $this->data['scenarios']['base']['error_reduction'] ?? 0;
        $total  = $labor + $fees + $errors;

        if ( 0 === $total ) {
            return '';
        }

        $labor_pct  = round( ( $labor / $total ) * 100 );
        $fees_pct   = round( ( $fees / $total ) * 100 );
        $errors_pct = round( ( $errors / $total ) * 100 );

        return '
        <div class="chart-container">
            <div class="chart-title">Annual Benefit Breakdown ($' . number_format( $total ) . ')</div>
            <table style="width: 100%; margin: 20px 0;">
                <tr>
                    <td style="width: 33%; text-align: center; padding: 15px; background: #e3f2fd; border: 1px solid #90caf9;">
                        <strong>Labor Savings</strong><br/>
                        $' . number_format( $labor ) . '<br/>
                        (' . $labor_pct . '%)
                    </td>
                    <td style="width: 33%; text-align: center; padding: 15px; background: #e8f5e8; border: 1px solid #81c784;">
                        <strong>Fee Reduction</strong><br/>
                        $' . number_format( $fees ) . '<br/>
                        (' . $fees_pct . '%)
                    </td>
                    <td style="width: 33%; text-align: center; padding: 15px; background: #fff3e0; border: 1px solid #ffb74d;">
                        <strong>Error Reduction</strong><br/>
                        $' . number_format( $errors ) . '<br/>
                        (' . $errors_pct . '%)
                    </td>
                </tr>
            </table>
        </div>';
    }

    /**
     * Save PDF to file.
     *
     * @return string File path.
     */
    private function save_pdf() {
        $upload_dir = wp_get_upload_dir();
        $pdf_dir    = $upload_dir['basedir'] . '/rtbcb-reports';

        if ( ! file_exists( $pdf_dir ) ) {
            wp_mkdir_p( $pdf_dir );
        }

        // Prevent direct access and directory listing.
        if ( ! file_exists( $pdf_dir . '/index.html' ) ) {
            file_put_contents( $pdf_dir . '/index.html', '' );
        }
        if ( ! file_exists( $pdf_dir . '/.htaccess' ) ) {
            file_put_contents( $pdf_dir . '/.htaccess', "Deny from all" );
        }

        $filename  = 'treasury-business-case-' . date( 'Y-m-d-His' ) . '.pdf';
        $file_path = $pdf_dir . '/' . $filename;

        $this->mpdf->Output( $file_path, \Mpdf\Output\Destination::FILE );

        $this->cleanup_old_reports( $pdf_dir );

        return $file_path;
    }

    /**
     * Remove old reports (7 days).
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
