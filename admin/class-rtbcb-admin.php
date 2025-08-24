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
        add_action( 'wp_ajax_rtbcb_generate_report_preview', [ $this, 'ajax_generate_report_preview' ] );
        add_action( 'wp_ajax_rtbcb_generate_sample_report', [ $this, 'ajax_generate_sample_report' ] );
        add_action( 'wp_ajax_rtbcb_sync_to_local', [ $this, 'sync_to_local' ] );
        add_action( 'wp_ajax_nopriv_rtbcb_sync_to_local', [ $this, 'sync_to_local' ] );
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
        wp_enqueue_style(
            'rtbcb-admin',
            RTBCB_URL . 'admin/css/rtbcb-admin.css',
            [],
            RTBCB_VERSION
        );

        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

        if ( 'rtbcb-unified-tests' === $page ) {
            wp_enqueue_style(
                'rtbcb-unified-dashboard',
                RTBCB_URL . 'admin/css/unified-test-dashboard.css',
                [],
                RTBCB_VERSION
            );

            wp_enqueue_script(
                'rtbcb-unified-dashboard',
                RTBCB_URL . 'admin/js/unified-test-dashboard.js',
                [ 'jquery' ],
                RTBCB_VERSION,
                true
            );

            wp_localize_script(
                'rtbcb-unified-dashboard',
                'rtbcbDashboard',
                [
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'rtbcb_unified_test_dashboard' ),
                    'strings' => [
                        'generating'   => __( 'Generating...', 'rtbcb' ),
                        'complete'     => __( 'Complete!', 'rtbcb' ),
                        'error'        => __( 'Error occurred', 'rtbcb' ),
                        'confirm_clear'=> __( 'Are you sure you want to clear all results?', 'rtbcb' ),
                    ],
                ]
            );
        }
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
            __( 'Unified Test Dashboard', 'rtbcb' ),
            __( 'Unified Tests', 'rtbcb' ),
            'manage_options',
            'rtbcb-unified-tests',
            [ $this, 'render_unified_test_dashboard' ]
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
            __( 'Calculation Info', 'rtbcb' ),
            __( 'Calculation Info', 'rtbcb' ),
            'manage_options',
            'rtbcb-calculations',
            [ $this, 'render_calculation_info' ]
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
        $page = isset( $_GET['paged'] ) ? intval( wp_unslash( $_GET['paged'] ) ) : 1;
        $search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
        $category = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '';
        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
        $allowed_orderby = [ 'email', 'created_at' ];
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'created_at';
        }

        $order = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'DESC';
        $order = strtoupper( $order );
        if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
            $order = 'DESC';
        }

        $args = [
            'page'      => $page,
            'search'    => $search,
            'category'  => $category,
            'date_from' => $date_from,
            'date_to'   => $date_to,
            'orderby'   => $orderby,
            'order'     => $order,
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
     * Render report preview page.
     *
     * @return void
     */
    public function render_report_preview() {
        include RTBCB_DIR . 'admin/report-preview-page.php';
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
     * Render unified test dashboard page.
     *
     * @return void
     */
    public function render_unified_test_dashboard() {
        include RTBCB_DIR . 'admin/unified-test-dashboard-page.php';
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

    public function ajax_generate_report_preview() {
        check_ajax_referer( 'rtbcb_generate_report_preview', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied', 'rtbcb' ), 403 );
        }

        $company = rtbcb_get_current_company();
        if ( empty( $company ) ) {
            wp_send_json_error( [ 'message' => __( 'No company data found. Please run the company overview first.', 'rtbcb' ) ], 400 );
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

        $company = rtbcb_get_current_company();
        if ( empty( $company ) ) {
            wp_send_json_error( [ 'message' => __( 'No company data found. Please run the company overview first.', 'rtbcb' ) ], 400 );
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
     * AJAX handler for ROI calculation testing.
     *
     * @return void
     */

}
