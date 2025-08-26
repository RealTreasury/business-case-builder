<?php
/**
 * Modern admin functionality for Real Treasury Business Case Builder plugin.
 * Fresh architecture with responsive design and modern UX patterns.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Modern admin class with clean architecture and responsive design.
 */
class RTBCB_Admin {
    /**
     * Plugin version for cache busting.
     *
     * @var string
     */
    private $version;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->version = RTBCB_VERSION;
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks.
     *
     * @return void
     */
    private function init_hooks() {
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'admin_init', [ $this, 'handle_admin_actions' ] );

        // AJAX handlers for modern admin interactions
        add_action( 'wp_ajax_rtbcb_admin_action', [ $this, 'handle_ajax_action' ] );
        add_action( 'wp_ajax_rtbcb_export_leads', [ $this, 'export_leads' ] );
        add_action( 'wp_ajax_rtbcb_delete_leads', [ $this, 'delete_leads' ] );
        add_action( 'wp_ajax_rtbcb_get_analytics_data', [ $this, 'get_analytics_data' ] );
    }

    /**
     * Get the appropriate capability for WordPress.com compatibility.
     * 
     * WordPress.com has different capability requirements than self-hosted WordPress.
     * This method ensures the admin menu works across all WordPress environments.
     *
     * @return string The capability required for admin access
     */
    private function get_admin_capability() {
        // Check if we're on WordPress.com
        if ( $this->is_wordpress_com() ) {
            // WordPress.com uses different capabilities
            // Check for editor capability first, then fall back to read
            if ( current_user_can( 'edit_pages' ) ) {
                return 'edit_pages';
            } elseif ( current_user_can( 'edit_posts' ) ) {
                return 'edit_posts';
            } else {
                return 'read';
            }
        }
        
        // For regular WordPress installations, use manage_options if available
        if ( current_user_can( 'manage_options' ) ) {
            return 'manage_options';
        }
        
        // Fallback capability chain for other managed WordPress environments
        $fallback_caps = [ 'edit_pages', 'edit_posts', 'upload_files', 'read' ];
        
        foreach ( $fallback_caps as $cap ) {
            if ( current_user_can( $cap ) ) {
                return $cap;
            }
        }
        
        // Final fallback
        return 'read';
    }

    /**
     * Detect if we're running on WordPress.com
     *
     * @return bool True if running on WordPress.com
     */
    private function is_wordpress_com() {
        // Check for WordPress.com specific constants and functions
        if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
            return true;
        }
        
        // Check for WordPress.com VIP
        if ( defined( 'WPCOM_VIP' ) && WPCOM_VIP ) {
            return true;
        }
        
        // Check for WordPress.com specific functions
        if ( function_exists( 'wpcom_vip_file_get_contents' ) ) {
            return true;
        }
        
        // Check server environment indicators
        $server_name = $_SERVER['SERVER_NAME'] ?? '';
        if ( strpos( $server_name, '.wordpress.com' ) !== false ) {
            return true;
        }
        
        // Check for Automattic environment
        if ( defined( 'AUTOMATTIC_DOMAIN' ) ) {
            return true;
        }
        
        return false;
    }

    /**
     * Register admin menu and submenus with modern structure.
     * Now with WordPress.com compatibility for capability requirements.
     *
     * @return void
     */
    public function register_admin_menu() {
        // Get the appropriate capability for this environment
        $capability = $this->get_admin_capability();
        
        // Log capability selection for debugging (only in WP_DEBUG mode)
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf( 
                'RTBCB: Using capability "%s" for admin menu (WordPress.com: %s)', 
                $capability, 
                $this->is_wordpress_com() ? 'yes' : 'no' 
            ) );
        }

        // Main menu page
        add_menu_page(
            __( 'Business Case Builder', 'rtbcb' ),
            __( 'Real Treasury', 'rtbcb' ),
            $capability,
            'rtbcb-dashboard',
            [ $this, 'render_dashboard_page' ],
            'dashicons-chart-line',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Dashboard', 'rtbcb' ),
            __( 'Dashboard', 'rtbcb' ),
            $capability,
            'rtbcb-dashboard',
            [ $this, 'render_dashboard_page' ]
        );

        // Leads Management
        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Leads Management', 'rtbcb' ),
            __( 'Leads', 'rtbcb' ),
            $capability,
            'rtbcb-leads',
            [ $this, 'render_leads_page' ]
        );

        // Analytics
        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Analytics & Reports', 'rtbcb' ),
            __( 'Analytics', 'rtbcb' ),
            $capability,
            'rtbcb-analytics',
            [ $this, 'render_analytics_page' ]
        );

        // Settings & Testing - Keep manage_options for settings if available, otherwise use main capability
        $settings_capability = current_user_can( 'manage_options' ) ? 'manage_options' : $capability;
        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Settings & Testing', 'rtbcb' ),
            __( 'Settings', 'rtbcb' ),
            $settings_capability,
            'rtbcb-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Enqueue modern admin assets with proper dependency management.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on our admin pages
        if ( ! $this->is_rtbcb_admin_page( $hook ) ) {
            return;
        }

        // Modern admin CSS with responsive design
        wp_enqueue_style(
            'rtbcb-admin-modern',
            RTBCB_PLUGIN_URL . 'admin/assets/css/admin-modern.css',
            [],
            $this->version
        );

        // Chart.js for analytics
        wp_enqueue_script(
            'rtbcb-chartjs',
            RTBCB_PLUGIN_URL . 'public/js/chart.min.js',
            [],
            '3.9.1',
            true
        );

        // Modern admin JavaScript with AJAX handling
        wp_enqueue_script(
            'rtbcb-admin-modern',
            RTBCB_PLUGIN_URL . 'admin/assets/js/admin-modern.js',
            [ 'jquery', 'rtbcb-chartjs' ],
            $this->version,
            true
        );

        // Localize script with admin data
        wp_localize_script(
            'rtbcb-admin-modern',
            'rtbcbAdmin',
            [
                'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
                'nonce'      => wp_create_nonce( 'rtbcb_admin_nonce' ),
                'strings'    => [
                    'confirm_delete' => __( 'Are you sure you want to delete this item?', 'rtbcb' ),
                    'bulk_confirm'   => __( 'Are you sure you want to perform this bulk action?', 'rtbcb' ),
                    'processing'     => __( 'Processing...', 'rtbcb' ),
                    'error'          => __( 'An error occurred. Please try again.', 'rtbcb' ),
                    'success'        => __( 'Action completed successfully.', 'rtbcb' ),
                ],
                'capabilities' => [
                    'manage_options' => current_user_can( 'manage_options' ),
                    'export_data'    => current_user_can( 'export_rtbcb_data' ),
                ],
            ]
        );
    }

    /**
     * Check if current page is an RTBCB admin page.
     *
     * @param string $hook Current admin page hook.
     * @return bool
     */
    private function is_rtbcb_admin_page( $hook ) {
        $rtbcb_pages = [
            'toplevel_page_rtbcb-dashboard',
            'real-treasury_page_rtbcb-leads',
            'real-treasury_page_rtbcb-analytics',
            'real-treasury_page_rtbcb-settings',
        ];

        return in_array( $hook, $rtbcb_pages, true );
    }

    /**
     * Handle admin form submissions and actions.
     *
     * @return void
     */
    public function handle_admin_actions() {
        // Handle settings form submission
        if ( isset( $_POST['rtbcb_save_settings'] ) ) {
            $this->handle_settings_save();
        }

        // Handle bulk actions
        if ( isset( $_POST['rtbcb_bulk_action'] ) ) {
            $this->handle_bulk_action();
        }
    }

    /**
     * Check if current user has admin access with WordPress.com compatibility.
     *
     * @return bool True if user has sufficient permissions
     */
    private function current_user_can_admin() {
        $capability = $this->get_admin_capability();
        return current_user_can( $capability );
    }

    /**
     * Render dashboard page.
     *
     * @return void
     */
    public function render_dashboard_page() {
        if ( ! $this->current_user_can_admin() ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'rtbcb' ) );
        }

        include RTBCB_PLUGIN_DIR . 'admin/views/dashboard/main.php';
    }

    /**
     * Render leads management page.
     *
     * @return void
     */
    public function render_leads_page() {
        if ( ! $this->current_user_can_admin() ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'rtbcb' ) );
        }

        include RTBCB_PLUGIN_DIR . 'admin/views/leads/main.php';
    }

    /**
     * Render analytics page.
     *
     * @return void
     */
    public function render_analytics_page() {
        if ( ! $this->current_user_can_admin() ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'rtbcb' ) );
        }

        include RTBCB_PLUGIN_DIR . 'admin/views/analytics/main.php';
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public function render_settings_page() {
        // Settings page requires manage_options if available, otherwise use admin capability
        if ( ! current_user_can( 'manage_options' ) && ! $this->current_user_can_admin() ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'rtbcb' ) );
        }

        include RTBCB_PLUGIN_DIR . 'admin/views/settings/main.php';
    }

    /**
     * Handle AJAX actions with proper security checks.
     *
     * @return void
     */
    public function handle_ajax_action() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rtbcb_admin_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'rtbcb' ) );
        }

        // Check capabilities with WordPress.com compatibility
        if ( ! $this->current_user_can_admin() ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'rtbcb' ) );
        }

        $action = sanitize_text_field( $_POST['action_type'] ?? '' );

        switch ( $action ) {
            case 'get_lead_details':
                $this->ajax_get_lead_details();
                break;
            case 'update_lead_status':
                $this->ajax_update_lead_status();
                break;
            case 'get_chart_data':
                $this->ajax_get_chart_data();
                break;
            default:
                wp_send_json_error( __( 'Invalid action.', 'rtbcb' ) );
        }
    }

    /**
     * Export leads to CSV.
     *
     * @return void
     */
    public function export_leads() {
        // Security checks
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rtbcb_admin_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'rtbcb' ) );
        }

        if ( ! current_user_can( 'export_rtbcb_data' ) ) {
            wp_die( esc_html__( 'You do not have permission to export data.', 'rtbcb' ) );
        }

        // Include the leads exporter
        require_once RTBCB_PLUGIN_DIR . 'admin/includes/leads-exporter.php';
        $exporter = new RTBCB_Leads_Exporter();
        $exporter->export_to_csv();
    }

    /**
     * Delete leads with bulk action support.
     *
     * @return void
     */
    public function delete_leads() {
        // Security checks
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rtbcb_admin_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'rtbcb' ) );
        }

        if ( ! $this->current_user_can_admin() ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'rtbcb' ) );
        }

        $lead_ids = array_map( 'intval', $_POST['lead_ids'] ?? [] );

        if ( empty( $lead_ids ) ) {
            wp_send_json_error( __( 'No leads selected.', 'rtbcb' ) );
        }

        $deleted_count = RTBCB_Leads::delete_leads( $lead_ids );

        wp_send_json_success( [
            'message' => sprintf(
                /* translators: %d: number of deleted leads */
                _n( '%d lead deleted.', '%d leads deleted.', $deleted_count, 'rtbcb' ),
                $deleted_count
            ),
            'deleted_count' => $deleted_count,
        ] );
    }

    /**
     * Get analytics data for charts and reports.
     *
     * @return void
     */
    public function get_analytics_data() {
        // Security checks
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rtbcb_admin_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'rtbcb' ) );
        }

        if ( ! $this->current_user_can_admin() ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'rtbcb' ) );
        }

        $chart_type = sanitize_text_field( $_POST['chart_type'] ?? '' );
        $date_range = sanitize_text_field( $_POST['date_range'] ?? '30' );

        // Include analytics processor
        require_once RTBCB_PLUGIN_DIR . 'admin/includes/analytics-processor.php';
        $processor = new RTBCB_Analytics_Processor();
        $data = $processor->get_chart_data( $chart_type, $date_range );

        wp_send_json_success( $data );
    }

    /**
     * Handle settings form save.
     *
     * @return void
     */
    private function handle_settings_save() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['rtbcb_settings_nonce'] ?? '', 'rtbcb_save_settings' ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Security check failed.', 'rtbcb' ) . '</p></div>';
            } );
            return;
        }

        // Check capabilities - settings require manage_options if available
        if ( ! current_user_can( 'manage_options' ) && ! $this->current_user_can_admin() ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Insufficient permissions.', 'rtbcb' ) . '</p></div>';
            } );
            return;
        }

        // Save settings
        $settings = [
            'openai_api_key' => sanitize_text_field( $_POST['rtbcb_openai_api_key'] ?? '' ),
            'default_model'  => sanitize_text_field( $_POST['rtbcb_default_model'] ?? 'gpt-4o-mini' ),
            'enable_caching' => isset( $_POST['rtbcb_enable_caching'] ),
            'debug_mode'     => isset( $_POST['rtbcb_debug_mode'] ),
        ];

        foreach ( $settings as $key => $value ) {
            update_option( "rtbcb_{$key}", $value );
        }

        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully.', 'rtbcb' ) . '</p></div>';
        } );
    }

    /**
     * Handle bulk actions on leads.
     *
     * @return void
     */
    private function handle_bulk_action() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['rtbcb_bulk_nonce'] ?? '', 'rtbcb_bulk_action' ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Security check failed.', 'rtbcb' ) . '</p></div>';
            } );
            return;
        }

        // Check capabilities
        if ( ! $this->current_user_can_admin() ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Insufficient permissions.', 'rtbcb' ) . '</p></div>';
            } );
            return;
        }

        $action = sanitize_text_field( $_POST['bulk_action'] ?? '' );
        $lead_ids = array_map( 'intval', $_POST['lead_ids'] ?? [] );

        if ( empty( $lead_ids ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-warning"><p>' . esc_html__( 'No items selected.', 'rtbcb' ) . '</p></div>';
            } );
            return;
        }

        switch ( $action ) {
            case 'delete':
                $deleted = RTBCB_Leads::delete_leads( $lead_ids );
                add_action( 'admin_notices', function() use ( $deleted ) {
                    echo '<div class="notice notice-success"><p>' . 
                         sprintf(
                             /* translators: %d: number of deleted leads */
                             esc_html( _n( '%d lead deleted.', '%d leads deleted.', $deleted, 'rtbcb' ) ),
                             $deleted
                         ) . '</p></div>';
                } );
                break;

            case 'export':
                // Redirect to export with selected IDs
                wp_redirect( add_query_arg( [
                    'page' => 'rtbcb-leads',
                    'action' => 'export',
                    'lead_ids' => implode( ',', $lead_ids ),
                    'nonce' => wp_create_nonce( 'rtbcb_export_leads' ),
                ], admin_url( 'admin.php' ) ) );
                exit;

            default:
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid bulk action.', 'rtbcb' ) . '</p></div>';
                } );
        }
    }

    /**
     * AJAX: Get lead details for modal display.
     *
     * @return void
     */
    private function ajax_get_lead_details() {
        $lead_id = intval( $_POST['lead_id'] ?? 0 );

        if ( ! $lead_id ) {
            wp_send_json_error( __( 'Invalid lead ID.', 'rtbcb' ) );
        }

        $lead = RTBCB_Leads::get_lead( $lead_id );

        if ( ! $lead ) {
            wp_send_json_error( __( 'Lead not found.', 'rtbcb' ) );
        }

        wp_send_json_success( $lead );
    }

    /**
     * AJAX: Update lead status.
     *
     * @return void
     */
    private function ajax_update_lead_status() {
        $lead_id = intval( $_POST['lead_id'] ?? 0 );
        $status = sanitize_text_field( $_POST['status'] ?? '' );

        if ( ! $lead_id || ! $status ) {
            wp_send_json_error( __( 'Invalid parameters.', 'rtbcb' ) );
        }

        $valid_statuses = [ 'new', 'contacted', 'qualified', 'converted', 'lost' ];
        if ( ! in_array( $status, $valid_statuses, true ) ) {
            wp_send_json_error( __( 'Invalid status.', 'rtbcb' ) );
        }

        $updated = RTBCB_Leads::update_lead_status( $lead_id, $status );

        if ( $updated ) {
            wp_send_json_success( __( 'Status updated successfully.', 'rtbcb' ) );
        } else {
            wp_send_json_error( __( 'Failed to update status.', 'rtbcb' ) );
        }
    }

    /**
     * AJAX: Get chart data for analytics.
     *
     * @return void
     */
    private function ajax_get_chart_data() {
        $chart_type = sanitize_text_field( $_POST['chart_type'] ?? '' );
        $date_range = sanitize_text_field( $_POST['date_range'] ?? '30' );

        // Include analytics processor
        require_once RTBCB_PLUGIN_DIR . 'admin/includes/analytics-processor.php';
        $processor = new RTBCB_Analytics_Processor();
        $data = $processor->get_chart_data( $chart_type, $date_range );

        wp_send_json_success( $data );
    }
}