<?php
/**
 * Enhanced admin functionality for Real Treasury Business Case Builder plugin.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enhanced admin class with full feature integration.
 */
class RTBCB_Admin {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        
        // AJAX handlers
        add_action( 'wp_ajax_rtbcb_test_connection', [ $this, 'test_api_connection' ] );
        add_action( 'wp_ajax_rtbcb_rebuild_index', [ $this, 'rebuild_rag_index' ] );
        add_action( 'wp_ajax_rtbcb_export_leads', [ $this, 'export_leads_csv' ] );
        add_action( 'wp_ajax_rtbcb_delete_lead', [ $this, 'delete_lead' ] );
        add_action( 'wp_ajax_rtbcb_bulk_action_leads', [ $this, 'bulk_action_leads' ] );
        add_action( 'wp_ajax_rtbcb_run_tests', [ $this, 'run_integration_tests' ] );
        add_action( 'wp_ajax_rtbcb_test_api', [ $this, 'ajax_test_api' ] );
        add_action( 'wp_ajax_rtbcb_run_diagnostics', [ $this, 'ajax_run_diagnostics' ] );
        add_action( 'wp_ajax_rtbcb_generate_report_preview', [ $this, 'ajax_generate_report_preview' ] );
        add_action( 'wp_ajax_rtbcb_generate_sample_report', [ $this, 'ajax_generate_sample_report' ] );
        add_action( 'wp_ajax_rtbcb_sync_to_local', [ $this, 'sync_to_local' ] );
        add_action( 'wp_ajax_nopriv_rtbcb_sync_to_local', [ $this, 'sync_to_local' ] );
        add_action( 'wp_ajax_rtbcb_test_commentary', [ $this, 'ajax_test_commentary' ] );
        add_action( 'wp_ajax_rtbcb_test_company_overview', [ $this, 'ajax_test_company_overview' ] );
        add_action( 'wp_ajax_rtbcb_test_treasury_tech_overview', [ $this, 'ajax_test_treasury_tech_overview' ] );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Page hook.
     * @return void
     */
    public function enqueue_admin_assets( $hook ) {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( strpos( $hook, 'rtbcb' ) === false && strpos( $page, 'rtbcb' ) === false ) {
            return;
        }

        wp_enqueue_script( 'chart-js', RTBCB_URL . 'public/js/chart.min.js', [], '3.9.1', true );
        wp_enqueue_script( 
            'rtbcb-admin', 
            RTBCB_URL . 'admin/js/rtbcb-admin.js', 
            [ 'jquery', 'chart-js' ], 
            RTBCB_VERSION, 
            true 
        );
        wp_enqueue_style( 
            'rtbcb-admin', 
            RTBCB_URL . 'admin/css/rtbcb-admin.css', 
            [], 
            RTBCB_VERSION 
        );

        wp_localize_script( 'rtbcb-admin', 'rtbcbAdmin', [
            'ajax_url'             => admin_url( 'admin-ajax.php' ),
            'nonce'                => wp_create_nonce( 'rtbcb_nonce' ),
            'diagnostics_nonce'    => wp_create_nonce( 'rtbcb_diagnostics' ),
            'report_preview_nonce' => wp_create_nonce( 'rtbcb_generate_report_preview' ),
            'company_overview_nonce' => wp_create_nonce( 'rtbcb_test_company_overview' ),
            'treasury_tech_overview_nonce' => wp_create_nonce( 'rtbcb_test_treasury_tech_overview' ),
            'page'                 => $page,
            'strings'              => [
                'confirm_delete'      => __( 'Are you sure you want to delete this lead?', 'rtbcb' ),
                'confirm_bulk_delete' => __( 'Are you sure you want to delete the selected leads?', 'rtbcb' ),
                'processing'          => __( 'Processing...', 'rtbcb' ),
                'error'               => __( 'An error occurred. Please try again.', 'rtbcb' ),
                'testing'             => __( 'Testing...', 'rtbcb' ),
                'generating'          => __( 'Generating...', 'rtbcb' ),
                'copied'              => __( 'Copied to clipboard.', 'rtbcb' ),
            ],
        ] );

        $rtbcb_sample_forms = rtbcb_get_sample_report_forms();
        $rtbcb_sample_data  = [];

        foreach ( $rtbcb_sample_forms as $key => $scenario ) {
            $rtbcb_sample_data[ $key ] = $scenario['data'];
        }

        wp_add_inline_script(
            'rtbcb-admin',
            'rtbcbAdmin.sampleForms = ' . wp_json_encode( $rtbcb_sample_data ) . ';',
            'after'
        );
    }

    /**
     * Register admin menu and submenus.
     *
     * @return void
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Business Case Builder', 'rtbcb' ),
            __( 'Real Treasury', 'rtbcb' ),
            'manage_options',
            'rtbcb-dashboard',
            [ $this, 'render_dashboard' ],
            'dashicons-calculator',
            30
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Dashboard', 'rtbcb' ),
            __( 'Dashboard', 'rtbcb' ),
            'manage_options',
            'rtbcb-dashboard',
            [ $this, 'render_dashboard' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Settings', 'rtbcb' ),
            __( 'Settings', 'rtbcb' ),
            'manage_options',
            'rtbcb-settings',
            [ $this, 'render_settings' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Leads', 'rtbcb' ),
            __( 'Leads', 'rtbcb' ),
            'manage_options',
            'rtbcb-leads',
            [ $this, 'render_leads' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Analytics', 'rtbcb' ),
            __( 'Analytics', 'rtbcb' ),
            'manage_options',
            'rtbcb-analytics',
            [ $this, 'render_analytics' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Data Health', 'rtbcb' ),
            __( 'Data Health', 'rtbcb' ),
            'manage_options',
            'rtbcb-data-health',
            [ $this, 'render_data_health' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'API Test', 'rtbcb' ),
            __( 'API Test', 'rtbcb' ),
            'manage_options',
            'rtbcb-api-test',
            [ $this, 'render_api_test' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Report Preview', 'rtbcb' ),
            __( 'Report Preview', 'rtbcb' ),
            'manage_options',
            'rtbcb-report-preview',
            [ $this, 'render_report_preview' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Report Test', 'rtbcb' ),
            __( 'Report Test', 'rtbcb' ),
            'manage_options',
            'rtbcb-report-test',
            [ $this, 'render_report_test' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Calculation Info', 'rtbcb' ),
            __( 'Calculation Info', 'rtbcb' ),
            'manage_options',
            'rtbcb-calculations',
            [ $this, 'render_calculation_info' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Test Company Overview', 'rtbcb' ),
            __( 'Test Company Overview', 'rtbcb' ),
            'manage_options',
            'rtbcb-test-company-overview',
            [ $this, 'render_test_company_overview' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Test Treasury Tech Overview', 'rtbcb' ),
            __( 'Test Treasury Tech Overview', 'rtbcb' ),
            'manage_options',
            'rtbcb-test-treasury-tech-overview',
            [ $this, 'render_test_treasury_tech_overview' ]
        );
    }

    /**
     * Render enhanced dashboard with statistics.
     *
     * @return void
     */
    public function render_dashboard() {
        $stats = RTBCB_Leads::get_statistics();
        $recent_leads = RTBCB_Leads::get_all_leads( [ 'per_page' => 5, 'orderby' => 'created_at', 'order' => 'DESC' ] );
        
        include RTBCB_DIR . 'admin/dashboard-page.php';
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public function render_settings() {
        include RTBCB_DIR . 'admin/settings-page.php';
    }

    /**
     * Render enhanced leads page with filtering and export.
     *
     * @return void
     */
    public function render_leads() {
        $page = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;
        $search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
        $category = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '';
        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

        $args = [
            'page'      => $page,
            'search'    => $search,
            'category'  => $category,
            'date_from' => $date_from,
            'date_to'   => $date_to,
        ];

        $leads_data = RTBCB_Leads::get_all_leads( $args );
        $categories = RTBCB_Category_Recommender::get_all_categories();
        
        include RTBCB_DIR . 'admin/leads-page-enhanced.php';
    }

    /**
     * Render analytics page with charts and insights.
     *
     * @return void
     */
    public function render_analytics() {
        $stats = RTBCB_Leads::get_statistics();
        $monthly_trends = $this->get_monthly_trends();
        
        include RTBCB_DIR . 'admin/analytics-page.php';
    }

    /**
     * Render data health page.
     *
     * @return void
     */
    public function render_data_health() {
        $portal_active = $this->check_portal_integration();
        $last_indexed = get_option( 'rtbcb_last_indexed', '' );
       $vendor_count = $this->get_vendor_count();
        $rag_health = $this->check_rag_health();

        include RTBCB_DIR . 'admin/data-health-page.php';
    }

    /**
     * Render OpenAI API test page.
     *
     * @return void
     */
    public function render_api_test() {
        include RTBCB_DIR . 'admin/api-test-page.php';
    }

    /**
     * Render report preview page.
     *
     * @return void
     */
    public function render_report_preview() {
        include RTBCB_DIR . 'admin/report-preview-page.php';
    }

    /**
     * Render report test page.
     *
     * @return void
     */
    public function render_report_test() {
        include RTBCB_DIR . 'admin/report-test-page.php';
    }

    /**
     * Render calculation info page.
     *
     * @return void
     */
    public function render_calculation_info() {
        include RTBCB_DIR . 'admin/calculations-page.php';
    }

    /**
     * Render test company overview page.
     *
     * @return void
     */
    public function render_test_company_overview() {
        include RTBCB_DIR . 'admin/test-company-overview-page.php';
    }

    /**
     * Render test treasury tech overview page.
     *
     * @return void
     */
    public function render_test_treasury_tech_overview() {
        include RTBCB_DIR . 'admin/test-treasury-tech-overview-page.php';
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings() {
        register_setting( 'rtbcb_settings', 'rtbcb_openai_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_mini_model', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_premium_model', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_advanced_model', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_embedding_model', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_labor_cost_per_hour', [ 'sanitize_callback' => 'floatval' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_bank_fee_baseline', [ 'sanitize_callback' => 'floatval' ] );
    }

    /**
     * Export leads to CSV.
     *
     * @return void
     */
    public function export_leads_csv() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $filters = [
            'search'    => sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) ),
            'category'  => sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) ),
            'date_from' => sanitize_text_field( wp_unslash( $_POST['date_from'] ?? '' ) ),
            'date_to'   => sanitize_text_field( wp_unslash( $_POST['date_to'] ?? '' ) ),
        ];

        $csv_content = RTBCB_Leads::export_to_csv( $filters );
        $filename = 'rtbcb-leads-' . date( 'Y-m-d' ) . '.csv';

        wp_send_json_success( [
            'content'  => $csv_content,
            'filename' => $filename,
        ] );
    }

    /**
     * Delete a single lead.
     *
     * @return void
     */
    public function delete_lead() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $lead_id = intval( wp_unslash( $_POST['lead_id'] ?? 0 ) );
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        
        $result = $wpdb->delete( $table_name, [ 'id' => $lead_id ], [ '%d' ] );

        if ( $result ) {
            wp_send_json_success( [ 'message' => __( 'Lead deleted successfully.', 'rtbcb' ) ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Failed to delete lead.', 'rtbcb' ) ] );
        }
    }

    /**
     * Handle bulk actions on leads.
     *
     * @return void
     */
    public function bulk_action_leads() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $action = sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) );
        $lead_ids = array_map( 'intval', wp_unslash( $_POST['lead_ids'] ?? [] ) );

        if ( empty( $lead_ids ) ) {
            wp_send_json_error( [ 'message' => __( 'No leads selected.', 'rtbcb' ) ] );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        $placeholders = implode( ',', array_fill( 0, count( $lead_ids ), '%d' ) );

        switch ( $action ) {
            case 'delete':
                $result = $wpdb->query( 
                    $wpdb->prepare( 
                        "DELETE FROM {$table_name} WHERE id IN ({$placeholders})", 
                        $lead_ids 
                    ) 
                );
                
                if ( $result ) {
                    wp_send_json_success( [ 
                        'message' => sprintf( __( '%d leads deleted successfully.', 'rtbcb' ), $result ) 
                    ] );
                } else {
                    wp_send_json_error( [ 'message' => __( 'Failed to delete leads.', 'rtbcb' ) ] );
                }
                break;

            default:
                wp_send_json_error( [ 'message' => __( 'Invalid action.', 'rtbcb' ) ] );
        }
    }

    /**
     * Test the OpenAI API connection.
     *
     * @return void
     */
    public function test_api_connection() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $api_key = get_option( 'rtbcb_openai_api_key' );
        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => __( 'Missing API key.', 'rtbcb' ) ] );
        }

        $response = wp_remote_get( 'https://api.openai.com/v1/models', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 20,
        ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => $response->get_error_message() ] );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            wp_send_json_error( [ 'message' => sprintf( __( 'API request failed (%d).', 'rtbcb' ), $code ) ] );
        }

        wp_send_json_success( [ 'message' => __( 'Connection successful.', 'rtbcb' ) ] );
    }

    /**
     * AJAX handler for comprehensive API test.
     *
     * @return void
     */
    public function ajax_test_api() {
        check_ajax_referer( 'rtbcb_test_api', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied', 'rtbcb' ) );
        }

        $result = RTBCB_API_Tester::test_connection();

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        }

        wp_send_json_error( $result );
    }

    /**
     * AJAX handler for industry commentary testing.
     *
     * @return void
     */
    public function ajax_test_commentary() {
        check_ajax_referer( 'rtbcb_test_commentary', 'nonce' );

        $industry = isset( $_POST['industry'] ) ? sanitize_text_field( wp_unslash( $_POST['industry'] ) ) : '';

        if ( empty( $industry ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid industry.', 'rtbcb' ) ] );
        }

        $commentary = rtbcb_test_generate_industry_commentary( $industry );

        if ( is_wp_error( $commentary ) ) {
            wp_send_json_error( [ 'message' => sanitize_text_field( $commentary->get_error_message() ) ] );
        }

        wp_send_json_success( [ 'commentary' => sanitize_text_field( $commentary ) ] );
    }

    /**
     * AJAX handler for company overview testing.
     *
     * @return void
     */
    public function ajax_test_company_overview() {
        check_ajax_referer( 'rtbcb_test_company_overview', 'nonce' );

        $company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';

        if ( empty( $company_name ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid company name.', 'rtbcb' ) ] );
        }

        $start    = microtime( true );
        $overview = rtbcb_test_generate_company_overview( $company_name );
        $elapsed  = round( microtime( true ) - $start, 2 );

        if ( is_wp_error( $overview ) ) {
            wp_send_json_error( [ 'message' => sanitize_text_field( $overview->get_error_message() ) ] );
        }

        $word_count = str_word_count( $overview );

        wp_send_json_success(
            [
                'overview'   => sanitize_textarea_field( $overview ),
                'word_count' => $word_count,
                'elapsed'    => $elapsed,
                'generated'  => current_time( 'mysql' ),
            ]
        );
    }

    /**
     * AJAX handler for treasury tech overview testing.
     *
     * @return void
     */
    public function ajax_test_treasury_tech_overview() {
        check_ajax_referer( 'rtbcb_test_treasury_tech_overview', 'nonce' );

        $focus_areas = isset( $_POST['focus_areas'] ) ? (array) wp_unslash( $_POST['focus_areas'] ) : [];
        $focus_areas = array_map( 'sanitize_text_field', $focus_areas );
        $focus_areas = array_filter( $focus_areas );
        $complexity  = isset( $_POST['complexity'] ) ? sanitize_text_field( wp_unslash( $_POST['complexity'] ) ) : '';

        if ( empty( $focus_areas ) ) {
            wp_send_json_error( [ 'message' => __( 'Please select at least one focus area.', 'rtbcb' ) ] );
        }

        $start    = microtime( true );
        $overview = rtbcb_test_generate_treasury_tech_overview( $focus_areas, $complexity );
        $elapsed  = round( microtime( true ) - $start, 2 );

        if ( is_wp_error( $overview ) ) {
            wp_send_json_error( [ 'message' => sanitize_text_field( $overview->get_error_message() ) ] );
        }

        $word_count = str_word_count( $overview );

        wp_send_json_success(
            [
                'overview'   => sanitize_textarea_field( $overview ),
                'word_count' => $word_count,
                'elapsed'    => $elapsed,
                'generated'  => current_time( 'mysql' ),
            ]
        );
    }

    /**
     * Rebuild the RAG index.
     *
     * @return void
     */
    public function rebuild_rag_index() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $rag = new RTBCB_RAG();
        $rag->rebuild_index();

        wp_send_json_success( [ 'message' => __( 'RAG index rebuilt successfully.', 'rtbcb' ) ] );
    }

    /**
     * Run integration diagnostics.
     *
     * @return void
     */
    public function run_integration_tests() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $results = RTBCB_Tests::run_integration_tests();

        wp_send_json_success( $results );
    }

    /**
     * AJAX handler for generating report preview.
     *
     * @return void
     */
    public function ajax_generate_report_preview() {
        check_ajax_referer( 'rtbcb_generate_report_preview', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied', 'rtbcb' ), 403 );
        }

        $context_raw  = isset( $_POST['context'] ) ? wp_unslash( $_POST['context'] ) : '';
        $template_raw = isset( $_POST['template'] ) ? wp_unslash( $_POST['template'] ) : '';

        $context = json_decode( $context_raw, true );
        if ( ! is_array( $context ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid context data.', 'rtbcb' ) ], 400 );
        }

        try {
            $html = '';

            if ( ! empty( $template_raw ) ) {
                $business_case_data = $context;
                ob_start();
                eval( '?>' . $template_raw ); // phpcs:ignore WordPress.PHP.DiscouragedFunctions.eval
                $html = ob_get_clean();
            } else {
                $template_path = RTBCB_DIR . 'templates/comprehensive-report-template.php';
                if ( ! file_exists( $template_path ) ) {
                    $template_path = RTBCB_DIR . 'templates/report-template.php';
                }

                if ( ! file_exists( $template_path ) ) {
                    wp_send_json_error( [ 'message' => __( 'Report template not found.', 'rtbcb' ) ], 500 );
                }

                $business_case_data = $context;
                ob_start();
                include $template_path;
                $html = ob_get_clean();
            }

            $allowed_tags = wp_kses_allowed_html( 'post' );
            $allowed_tags['style'] = [];
            $html = wp_kses( $html, $allowed_tags );

            wp_send_json_success( [ 'html' => $html ] );
        } catch ( RTBCB_JSON_Response $e ) {
            throw $e;
        } catch ( \Throwable $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
        }
    }

    /**
     * AJAX handler to generate a sample report.
     *
     * @return void
     */
    public function ajax_generate_sample_report() {
        check_ajax_referer( 'rtbcb_generate_report_preview', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'rtbcb' ), 403 );
        }

        $scenario_key = isset( $_POST['scenario_key'] ) ? sanitize_key( wp_unslash( $_POST['scenario_key'] ) ) : '';

        $inputs = apply_filters( 'rtbcb_sample_report_inputs', rtbcb_get_sample_inputs(), $scenario_key );
        if ( empty( $inputs ) || ! is_array( $inputs ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid scenario selected.', 'rtbcb' ) ], 400 );
        }

        try {
            $roi_data = RTBCB_Calculator::calculate_roi( $inputs );

            $llm           = new RTBCB_LLM();
            $business_case = $llm->generate_business_case( $inputs, $roi_data );
            if ( is_wp_error( $business_case ) ) {
                wp_send_json_error( [ 'message' => $business_case->get_error_message() ], 500 );
            }

            $router = new RTBCB_Router();
            $html   = $router->get_report_html( $business_case );
            if ( empty( $html ) ) {
                wp_send_json_error( [ 'message' => __( 'Report template not found.', 'rtbcb' ) ], 500 );
            }

            wp_send_json_success( [ 'report_html' => $html ] );
        } catch ( RTBCB_JSON_Response $e ) {
            throw $e;
        } catch ( \Throwable $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
        }
    }

    /**
     * Sync portal data to the local site.
     *
     * @return void
     */
    public function sync_to_local() {
        check_ajax_referer( 'rtbcb_sync_local', 'nonce' );

        if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        wp_send_json_success( [ 'message' => __( 'Data synchronized.', 'rtbcb' ) ] );
    }

    /**
     * Get monthly trends data.
     *
     * @return array Monthly trends.
     */
    private function get_monthly_trends() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_leads';

        $results = $wpdb->get_results(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as leads,
                AVG(roi_base) as avg_roi
             FROM {$table_name} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month",
            ARRAY_A
        );

        return $results ?: [];
    }

    /**
     * Check if Portal integration is active.
     *
     * @return bool
     */
    private function check_portal_integration() {
        return (bool) ( has_filter( 'rt_portal_get_vendors' ) || has_filter( 'rt_portal_get_vendor_notes' ) );
    }

    /**
     * Get vendor count from portal.
     *
     * @return int
     */
    private function get_vendor_count() {
        $vendors = apply_filters( 'rt_portal_get_vendors', [] );
        return is_array( $vendors ) ? count( $vendors ) : 0;
    }

    /**
     * Check RAG index health.
     *
     * @return array Health status.
     */
    private function check_rag_health() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_rag_index';

        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
        $last_updated = $wpdb->get_var( "SELECT MAX(updated_at) FROM {$table_name}" );

        return [
            'indexed_items' => intval( $count ),
            'last_updated'  => $last_updated,
            'status'        => $count > 0 ? 'healthy' : 'needs_rebuild',
        ];
    }

    /**
     * Run comprehensive diagnostics to identify issues.
     *
     * @return array Diagnostic results.
     */
    public static function run_comprehensive_diagnostics() {
        $diagnostics = [];

        // 1. Check PHP Version
        $diagnostics['php_version'] = [
            'version'            => PHP_VERSION,
            'meets_requirement'  => version_compare( PHP_VERSION, '7.4', '>=' ),
            'status'             => version_compare( PHP_VERSION, '7.4', '>=' ) ? 'OK' : 'FAIL',
        ];

        // 2. Check WordPress Version
        $wp_version = get_bloginfo( 'version' );
        $diagnostics['wp_version'] = [
            'version'            => $wp_version,
            'meets_requirement'  => version_compare( $wp_version, '5.0', '>=' ),
            'status'             => version_compare( $wp_version, '5.0', '>=' ) ? 'OK' : 'FAIL',
        ];

        // 3. Check Database Connection
        global $wpdb;
        try {
            $wpdb->get_var( 'SELECT 1' );
            $diagnostics['database'] = [
                'status'  => 'OK',
                'message' => __( 'Database connection successful', 'rtbcb' ),
            ];
        } catch ( Exception $e ) {
            $diagnostics['database'] = [
                'status'  => 'FAIL',
                'message' => sprintf( __( 'Database connection failed: %s', 'rtbcb' ), $e->getMessage() ),
            ];
        }

        // 4. Check Required Classes
        $required_classes = [
            'RTBCB_Calculator',
            'RTBCB_Category_Recommender',
            'RTBCB_LLM',
            'RTBCB_RAG',
            'RTBCB_Leads',
            'RTBCB_Validator',
        ];

        foreach ( $required_classes as $class ) {
            $diagnostics['classes'][ $class ] = [
                'exists' => class_exists( $class ),
                'status' => class_exists( $class ) ? 'OK' : 'FAIL',
            ];
        }

        // 5. Check Database Tables
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
                DB_NAME,
                $table_name
            )
        );

        $diagnostics['database_tables']['rtbcb_leads'] = [
            'exists' => (bool) $table_exists,
            'status' => $table_exists ? 'OK' : 'FAIL',
        ];

        // 6. Check Table Structure
        if ( $table_exists ) {
            $columns = $wpdb->get_results( "DESCRIBE {$table_name}" );
            $expected_columns = [
                'id',
                'email',
                'company_size',
                'industry',
                'hours_reconciliation',
                'hours_cash_positioning',
                'num_banks',
                'ftes',
                'pain_points',
                'recommended_category',
                'roi_low',
                'roi_base',
                'roi_high',
                'report_html',
                'ip_address',
                'user_agent',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'created_at',
                'updated_at',
            ];

            $actual_columns  = array_column( $columns, 'Field' );
            $missing_columns = array_diff( $expected_columns, $actual_columns );

            $diagnostics['table_structure'] = [
                'expected_columns' => count( $expected_columns ),
                'actual_columns'   => count( $actual_columns ),
                'missing_columns'  => $missing_columns,
                'status'           => empty( $missing_columns ) ? 'OK' : 'FAIL',
            ];
        }

        // 7. Check OpenAI API Configuration
        $api_key      = get_option( 'rtbcb_openai_api_key' );
        $configured   = ! empty( $api_key );
        $valid_format = $configured ? rtbcb_is_valid_openai_api_key( $api_key ) : false;
        $api_test     = RTBCB_API_Tester::test_connection();

        $status = $api_test['success'] ? 'OK' : 'FAIL';
        if ( $configured && ! $valid_format ) {
            $status = 'INVALID_FORMAT';
        }

        $diagnostics['openai_api'] = [
            'configured'   => $configured,
            'valid_format' => $valid_format,
            'success'      => $api_test['success'],
            'message'      => $api_test['message'],
            'details'      => $api_test['details'] ?? '',
            'status'       => $status,
        ];

        // 8. Check Memory Limit
        $memory_limit = ini_get( 'memory_limit' );
        $memory_bytes = wp_convert_hr_to_bytes( $memory_limit );
        $diagnostics['memory_limit'] = [
            'current'    => $memory_limit,
            'bytes'      => $memory_bytes,
            'sufficient' => $memory_bytes >= 128 * 1024 * 1024,
            'status'     => $memory_bytes >= 128 * 1024 * 1024 ? 'OK' : 'LOW',
        ];

        // 9. Check Error Logging
        $diagnostics['error_logging'] = [
            'wp_debug'      => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'wp_debug_log'  => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
            'log_errors'    => ini_get( 'log_errors' ),
            'error_log_path'=> ini_get( 'error_log' ),
        ];

        // 10. Test Basic Functionality
        try {
            $test_inputs = rtbcb_get_sample_inputs();

            if ( class_exists( 'RTBCB_Calculator' ) ) {
                $roi_result = RTBCB_Calculator::calculate_roi( $test_inputs );
                $diagnostics['functionality']['calculator'] = [
                    'status' => ! empty( $roi_result ) ? 'OK' : 'FAIL',
                    'result' => ! empty( $roi_result ),
                ];
            }

            if ( class_exists( 'RTBCB_Category_Recommender' ) ) {
                $recommendation = RTBCB_Category_Recommender::recommend_category( $test_inputs );
                $diagnostics['functionality']['recommender'] = [
                    'status' => ! empty( $recommendation ) ? 'OK' : 'FAIL',
                    'result' => ! empty( $recommendation ),
                ];
            }
        } catch ( Exception $e ) {
            $diagnostics['functionality']['error'] = $e->getMessage();
        } catch ( Error $e ) {
            $diagnostics['functionality']['fatal_error'] = $e->getMessage();
        }

        return $diagnostics;
    }

    /**
     * AJAX handler for running diagnostics.
     *
     * @return void
     */
    public function ajax_run_diagnostics() {
        check_ajax_referer( 'rtbcb_diagnostics', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions', 'rtbcb' ) );
        }

        try {
            $diagnostics = self::run_comprehensive_diagnostics();
            wp_send_json_success( $diagnostics );
        } catch ( Exception $e ) {
            wp_send_json_error( sprintf( __( 'Diagnostics failed: %s', 'rtbcb' ), $e->getMessage() ) );
        } catch ( Error $e ) {
            wp_send_json_error( sprintf( __( 'Diagnostics failed: %s', 'rtbcb' ), $e->getMessage() ) );
        }
    }
}
