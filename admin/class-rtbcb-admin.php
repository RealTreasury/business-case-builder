<?php
/**
 * Admin functionality for Real Treasury Business Case Builder plugin.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles admin menus, settings, and AJAX actions.
 */
class RTBCB_Admin {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_ajax_rtbcb_test_connection', [ $this, 'test_api_connection' ] );
        add_action( 'wp_ajax_rtbcb_rebuild_index', [ $this, 'rebuild_rag_index' ] );
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
            __( 'Data Health', 'rtbcb' ),
            __( 'Data Health', 'rtbcb' ),
            'manage_options',
            'rtbcb-data-health',
            [ $this, 'render_data_health' ]
        );
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
     * Render leads page.
     *
     * @return void
     */
    public function render_leads() {
        // $leads = RTBCB_Leads::get_all_leads(); // Uncomment when RTBCB_Leads is built.
        include RTBCB_DIR . 'admin/leads-page.php';
    }

    /**
     * Render data health page.
     *
     * @return void
     */
    public function render_data_health() {
        // $portal_active = $this->check_portal_integration(); // Uncomment when helper is built.
        // $vendor_count  = count( apply_filters( 'rt_portal_get_vendors', [] ) );
        // $last_indexed  = get_option( 'rtbcb_last_indexed', 'Never' );
        include RTBCB_DIR . 'admin/data-health-page.php';
    }

    /**
     * Render dashboard.
     *
     * @return void
     */
    public function render_dashboard() {
        echo '<h1>' . esc_html__( 'Business Case Builder Dashboard', 'rtbcb' ) . '</h1>';
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            'rtbcb_settings',
            'rtbcb_openai_api_key',
            [
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );

        register_setting(
            'rtbcb_settings',
            'rtbcb_mini_model',
            [
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );

        register_setting(
            'rtbcb_settings',
            'rtbcb_premium_model',
            [
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );

        register_setting(
            'rtbcb_settings',
            'rtbcb_embedding_model',
            [
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );
    }

    /**
     * Test the OpenAI API connection.
     *
     * @return void
     */
    public function test_api_connection() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                [ 'message' => __( 'Permission denied.', 'rtbcb' ) ],
                403
            );
        }

        $api_key = get_option( 'rtbcb_openai_api_key' );
        if ( empty( $api_key ) ) {
            wp_send_json_error(
                [ 'message' => __( 'Missing API key.', 'rtbcb' ) ]
            );
        }

        $response = wp_remote_get(
            'https://api.openai.com/v1/models',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => 20,
            ]
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error(
                [ 'message' => $response->get_error_message() ]
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            wp_send_json_error(
                [ 'message' => sprintf( __( 'API request failed (%d).', 'rtbcb' ), $code ) ]
            );
        }

        wp_send_json_success(
            [ 'message' => __( 'Connection successful.', 'rtbcb' ) ]
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
            wp_send_json_error(
                [ 'message' => __( 'Permission denied.', 'rtbcb' ) ],
                403
            );
        }

        require_once RTBCB_DIR . 'inc/class-rtbcb-rag.php';

        $rag = new RTBCB_RAG();
        $rag->rebuild_index();

        wp_send_json_success(
            [ 'message' => __( 'RAG index rebuilt.', 'rtbcb' ) ]
        );
    }

    /**
     * Check if Portal integration hooks exist.
     *
     * @return bool
     */
    private function check_portal_integration() {
        return (bool) ( has_filter( 'rt_portal_get_vendors' ) || has_filter( 'rt_portal_get_vendor_notes' ) );
    }
}

