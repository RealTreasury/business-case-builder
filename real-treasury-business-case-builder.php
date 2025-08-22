<?php
/**
 * Plugin Name: Real Treasury - Business Case Builder (Enhanced)
 * Description: Enhanced ROI calculator and business case generator for treasury technology with PDF reports, analytics, and lead tracking.
 * Version: 2.0.0
 * Requires PHP: 7.4
 * Author: Real Treasury
 * License: GPL v2 or later
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RTBCB_VERSION', '2.0.0' );
define( 'RTBCB_FILE', __FILE__ );
define( 'RTBCB_URL', plugin_dir_url( RTBCB_FILE ) );
define( 'RTBCB_DIR', plugin_dir_path( RTBCB_FILE ) );

/**
 * Enhanced main plugin class.
 */
class Real_Treasury_BCB {
    /**
     * Singleton instance.
     *
     * @var Real_Treasury_BCB|null
     */
    private static $instance = null;

    /**
     * Plugin data.
     *
     * @var array
     */
    private $plugin_data = [];

    /**
     * Get plugin instance.
     *
     * @return Real_Treasury_BCB
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->plugin_data = get_file_data( RTBCB_FILE, [
            'Name'        => 'Plugin Name',
            'Version'     => 'Version',
            'Description' => 'Description',
            'Author'      => 'Author',
            'RequiresWP'  => 'Requires at least',
            'RequiresPHP' => 'Requires PHP',
        ] );

        $this->init_hooks();
        $this->includes();
    }

    /**
     * Initialize hooks.
     *
     * @return void
     */
    private function init_hooks() {
        register_activation_hook( RTBCB_FILE, [ $this, 'activate' ] );
        register_deactivation_hook( RTBCB_FILE, [ $this, 'deactivate' ] );
        register_uninstall_hook( RTBCB_FILE, [ __CLASS__, 'uninstall' ] );

        add_action( 'init', [ $this, 'init' ] );
        add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // Shortcode
        add_shortcode( 'rt_business_case_builder', [ $this, 'shortcode_handler' ] );

        // Portal integration hooks
        add_action( 'rt_portal_data_changed', [ $this, 'handle_portal_data_change' ] );

        // Admin notices
        add_action( 'admin_notices', [ $this, 'admin_notices' ] );

        // Plugin action links
        add_filter( 'plugin_action_links_' . plugin_basename( RTBCB_FILE ), [ $this, 'plugin_action_links' ] );

        // AJAX handlers
        add_action( 'wp_ajax_rtbcb_generate_case', [ $this, 'ajax_generate_case' ] );
        add_action( 'wp_ajax_nopriv_rtbcb_generate_case', [ $this, 'ajax_generate_case' ] );
    }

    /**
     * Include required files.
     *
     * @return void
     */
    private function includes() {
        // Core classes
        require_once RTBCB_DIR . 'inc/class-rtbcb-settings.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-calculator.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-router.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-llm.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-rag.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-leads.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-db.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-category-recommender.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-pdf.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-portal-integration.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-tests.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-validator.php';
        require_once RTBCB_DIR . 'inc/helpers.php';

        // Admin functionality
        if ( is_admin() ) {
            require_once RTBCB_DIR . 'admin/class-rtbcb-admin.php';
            new RTBCB_Admin();
        }
    }

    /**
     * Plugin initialization.
     *
     * @return void
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain( 'rtbcb', false, dirname( plugin_basename( RTBCB_FILE ) ) . '/languages' );

        // Initialize components that need early loading
        $this->maybe_upgrade();
        $this->setup_capabilities();
    }

    /**
     * Initialize components after plugins are loaded.
     *
     * @return void
     */
    public function plugins_loaded() {
        // Check compatibility
        if ( ! $this->check_compatibility() ) {
            return;
        }

        // Initialize database tables and data
        $this->init_database();

        // Setup cron jobs
        $this->setup_cron_jobs();

        // Fire action for other plugins to hook into
        do_action( 'rtbcb_loaded' );
    }

    /**
     * Check plugin compatibility.
     *
     * @return bool
     */
    private function check_compatibility() {
        // Check PHP version
        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                printf(
                    esc_html__( 'Real Treasury Business Case Builder requires PHP %1$s or higher. You are running %2$s.', 'rtbcb' ),
                    '7.4',
                    PHP_VERSION
                );
                echo '</p></div>';
            } );
            return false;
        }

        // Check WordPress version
        if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                printf(
                    esc_html__( 'Real Treasury Business Case Builder requires WordPress %1$s or higher. You are running %2$s.', 'rtbcb' ),
                    '5.0',
                    get_bloginfo( 'version' )
                );
                echo '</p></div>';
            } );
            return false;
        }

        return true;
    }

    /**
     * Initialize database tables.
     *
     * @return void
     */
    private function init_database() {
        // Initialize database and tables
        RTBCB_DB::init();

        // Initialize RAG database if needed
        if ( class_exists( 'RTBCB_RAG' ) ) {
            new RTBCB_RAG();
        }
    }

    /**
     * Setup user capabilities.
     *
     * @return void
     */
    private function setup_capabilities() {
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( 'manage_rtbcb' );
            $admin->add_cap( 'view_rtbcb_leads' );
            $admin->add_cap( 'export_rtbcb_data' );
        }
    }

    /**
     * Setup cron jobs for maintenance tasks.
     *
     * @return void
     */
    private function setup_cron_jobs() {
        // Schedule RAG index rebuilds
        if ( ! wp_next_scheduled( 'rtbcb_rebuild_rag_index' ) ) {
            wp_schedule_event( time(), 'daily', 'rtbcb_rebuild_rag_index' );
        }

        add_action( 'rtbcb_rebuild_rag_index', [ $this, 'scheduled_rag_rebuild' ] );

        // Schedule data cleanup
        if ( ! wp_next_scheduled( 'rtbcb_cleanup_data' ) ) {
            wp_schedule_event( time(), 'weekly', 'rtbcb_cleanup_data' );
        }

        add_action( 'rtbcb_cleanup_data', [ $this, 'scheduled_data_cleanup' ] );
    }

    /**
     * Handle version upgrades.
     *
     * @return void
     */
    private function maybe_upgrade() {
        $current_version = get_option( 'rtbcb_version', '1.0.0' );

        if ( version_compare( $current_version, RTBCB_VERSION, '<' ) ) {
            $this->upgrade_plugin( $current_version );
            update_option( 'rtbcb_version', RTBCB_VERSION );
        }
    }

    /**
     * Upgrade plugin data and settings.
     *
     * @param string $from_version Previous version.
     * @return void
     */
    private function upgrade_plugin( $from_version ) {
        // Upgrade from 1.x to 2.x
        if ( version_compare( $from_version, '2.0.0', '<' ) ) {
            // Migrate old settings format if needed
            $this->migrate_v1_settings();

            // Create new database tables
            $this->init_database();

            // Set default values for new options
            $this->set_default_options();
        }

        // Clear any caches
        wp_cache_flush();

        // Log upgrade
        error_log( "RTBCB: Upgraded from version {$from_version} to " . RTBCB_VERSION );
    }

    /**
     * Migrate v1 settings to v2 format.
     *
     * @return void
     */
    private function migrate_v1_settings() {
        // Migration logic for old settings format
        $old_settings = get_option( 'rtbcb_old_settings', [] );

        if ( ! empty( $old_settings ) ) {
            // Convert old format to new format
            foreach ( $old_settings as $key => $value ) {
                update_option( 'rtbcb_' . $key, $value );
            }

            // Remove old settings
            delete_option( 'rtbcb_old_settings' );
        }
    }

    /**
     * Set default options for new installation.
     *
     * @return void
     */
    private function set_default_options() {
        $defaults = [
            'rtbcb_mini_model'        => 'gpt-4o-mini',
            'rtbcb_premium_model'     => 'gpt-4o',
            'rtbcb_embedding_model'   => 'text-embedding-3-small',
            'rtbcb_labor_cost_per_hour' => 100,
            'rtbcb_bank_fee_baseline' => 15000,
            'rtbcb_pdf_enabled'       => true,
        ];

        foreach ( $defaults as $option => $value ) {
            if ( get_option( $option ) === false ) {
                add_option( $option, $value );
            }
        }
    }

    /**
     * Enqueue frontend assets.
     *
     * @return void
     */
    public function enqueue_assets() {
        if ( ! $this->should_load_assets() ) {
            return;
        }

        // Styles
        wp_enqueue_style(
            'rtbcb-style',
            RTBCB_URL . 'public/css/rtbcb.css',
            [],
            RTBCB_VERSION
        );

        // Scripts
        wp_enqueue_script(
            'rtbcb-wizard',
            RTBCB_URL . 'public/js/rtbcb-wizard.js',
            [ 'jquery' ],
            RTBCB_VERSION,
            true
        );

        wp_enqueue_script(
            'rtbcb-script',
            RTBCB_URL . 'public/js/rtbcb.js',
            [ 'jquery', 'rtbcb-wizard' ],
            RTBCB_VERSION,
            true
        );

        // CRITICAL FIX: Always localize script, even if ajaxObj exists
        wp_localize_script(
            'rtbcb-script',
            'ajaxObj',
            [
                'ajax_url'    => admin_url( 'admin-ajax.php' ),
                'rtbcb_nonce' => wp_create_nonce( 'rtbcb_generate' ),
                'strings'     => [
                    'error'              => __( 'An error occurred. Please try again.', 'rtbcb' ),
                    'generating'         => __( 'Generating your business case...', 'rtbcb' ),
                    'invalid_email'      => __( 'Please enter a valid email address.', 'rtbcb' ),
                    'required_field'     => __( 'This field is required.', 'rtbcb' ),
                    'select_pain_points' => __( 'Please select at least one pain point.', 'rtbcb' ),
                ],
                'settings'    => [
                    'pdf_enabled' => get_option( 'rtbcb_pdf_enabled', true ),
                ],
            ]
        );

        wp_enqueue_script(
            'rtbcb-report',
            RTBCB_URL . 'public/js/rtbcb-report.js',
            [],
            RTBCB_VERSION,
            true
        );

        $api_key = sanitize_text_field( get_option( 'rtbcb_openai_api_key', '' ) );
        wp_localize_script(
            'rtbcb-report',
            'rtbcbReport',
            [ 'api_key' => $api_key ]
        );
    }

    /**
     * Check if assets should be loaded on current page.
     *
     * @return bool
     */
    private function should_load_assets() {
        // Always load on admin pages for this plugin
        if ( is_admin() && isset( $_GET['page'] ) && strpos( $_GET['page'], 'rtbcb' ) !== false ) {
            return true;
        }

        // Load on any page - let WordPress handle caching
        return true;
    }

    /**
     * Shortcode handler.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function shortcode_handler( $atts = [] ) {
        // Parse attributes
        $atts = shortcode_atts( [
            'style'    => 'default',
            'title'    => __( 'Treasury Technology Business Case Builder', 'rtbcb' ),
            'subtitle' => __( 'Generate a data-driven business case for your treasury technology investment.', 'rtbcb' ),
        ], $atts, 'rt_business_case_builder' );

        // Start output buffering
        ob_start();

        // Pass attributes to template
        $template_args = [
            'style'    => sanitize_text_field( $atts['style'] ),
            'title'    => sanitize_text_field( $atts['title'] ),
            'subtitle' => sanitize_text_field( $atts['subtitle'] ),
        ];

        // Load template
        $this->load_template( 'business-case-form', $template_args );

        return ob_get_clean();
    }

    /**
     * Load a template file with arguments.
     *
     * @param string $template Template name.
     * @param array  $args     Template arguments.
     * @return void
     */
    private function load_template( $template, $args = [] ) {
        $template_path = RTBCB_DIR . "templates/{$template}.php";

        if ( file_exists( $template_path ) ) {
            // Extract arguments to variables
            extract( $args );
            include $template_path;
        } else {
            echo '<div class="rtbcb-error">' . esc_html__( 'Template not found.', 'rtbcb' ) . '</div>';
        }
    }

    /**
     * Handle portal data changes.
     *
     * @return void
     */
    public function handle_portal_data_change() {
        // Rebuild RAG index when portal data changes
        if ( class_exists( 'RTBCB_RAG' ) ) {
            wp_schedule_single_event( time() + 60, 'rtbcb_rebuild_rag_index' );
        }

        // Log the event
        error_log( 'RTBCB: Portal data change detected, RAG index rebuild scheduled' );
    }

    /**
     * Scheduled RAG index rebuild.
     *
     * @return void
     */
    public function scheduled_rag_rebuild() {
        if ( ! class_exists( 'RTBCB_RAG' ) ) {
            return;
        }

        try {
            $rag = new RTBCB_RAG();
            $rag->rebuild_index();
            error_log( 'RTBCB: Scheduled RAG index rebuild completed successfully' );
        } catch ( Exception $e ) {
            error_log( 'RTBCB: Scheduled RAG index rebuild failed: ' . $e->getMessage() );
        }
    }

    /**
     * Scheduled data cleanup.
     *
     * @return void
     */
    public function scheduled_data_cleanup() {
        global $wpdb;

        // Clean up old temporary files
        $upload_dir = wp_get_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/rtbcb-temp';

        if ( is_dir( $temp_dir ) ) {
            $files = glob( $temp_dir . '/*' );
            $old_time = time() - ( 7 * DAY_IN_SECONDS ); // 7 days old

            foreach ( $files as $file ) {
                if ( is_file( $file ) && filemtime( $file ) < $old_time ) {
                    unlink( $file );
                }
            }
        }

        // Clean up old lead data (optional, configurable)
        $retention_days = apply_filters( 'rtbcb_lead_retention_days', 0 ); // 0 = keep forever

        if ( $retention_days > 0 ) {
            $cutoff_date = date( 'Y-m-d H:i:s', time() - ( $retention_days * DAY_IN_SECONDS ) );
            $table_name = $wpdb->prefix . 'rtbcb_leads';

            $deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$table_name} WHERE created_at < %s",
                    $cutoff_date
                )
            );

            if ( $deleted > 0 ) {
                error_log( "RTBCB: Cleaned up {$deleted} old lead records" );
            }
        }
    }

    /**
     * Handle AJAX request for business case generation.
     *
     * @return void
     */
    public function ajax_generate_case() {
        // Set proper headers
        header( 'Content-Type: application/json; charset=utf-8' );

        // Prevent any output before JSON
        if ( ob_get_level() ) {
            ob_end_clean();
        }

        try {
            // Verify nonce
            if ( ! wp_verify_nonce( $_POST['rtbcb_nonce'] ?? '', 'rtbcb_generate' ) ) {
                wp_send_json_error( __( 'Security check failed.', 'rtbcb' ), 403 );
            }

            // Collect and validate form data
            $user_inputs = [
                'email'                  => sanitize_email( $_POST['email'] ?? '' ),
                'company_size'           => sanitize_text_field( $_POST['company_size'] ?? '' ),
                'industry'               => sanitize_text_field( $_POST['industry'] ?? '' ),
                'hours_reconciliation'   => floatval( $_POST['hours_reconciliation'] ?? 0 ),
                'hours_cash_positioning' => floatval( $_POST['hours_cash_positioning'] ?? 0 ),
                'num_banks'              => intval( $_POST['num_banks'] ?? 0 ),
                'ftes'                   => floatval( $_POST['ftes'] ?? 0 ),
                'pain_points'            => array_map( 'sanitize_text_field', $_POST['pain_points'] ?? [] ),
            ];

            // Validate email
            if ( empty( $user_inputs['email'] ) || ! is_email( $user_inputs['email'] ) ) {
                wp_send_json_error( __( 'Please enter a valid email address.', 'rtbcb' ), 400 );
            }

            // Calculate ROI
            if ( ! class_exists( 'RTBCB_Calculator' ) ) {
                wp_send_json_error( __( 'System error: Calculator not available.', 'rtbcb' ), 500 );
            }

            $scenarios = RTBCB_Calculator::calculate_roi( $user_inputs );

            // Get recommendation
            if ( ! class_exists( 'RTBCB_Category_Recommender' ) ) {
                wp_send_json_error( __( 'System error: Recommender not available.', 'rtbcb' ), 500 );
            }

            $recommendation = RTBCB_Category_Recommender::recommend_category( $user_inputs );

            // Create narrative (simplified, no LLM call)
            $narrative = [
                'narrative' => sprintf(
                    __( 'Based on your %s company profile, implementing %s could generate annual benefits of approximately $%s through process automation and improved efficiency. This solution aligns perfectly with your operational needs and will address your key pain points.', 'rtbcb' ),
                    $user_inputs['company_size'],
                    $recommendation['category_info']['name'] ?? __( 'treasury technology', 'rtbcb' ),
                    number_format( $scenarios['base']['total_annual_benefit'] ?? 0 )
                ),
                'risks' => [
                    __( 'Implementation complexity may impact timeline', 'rtbcb' ),
                    __( 'User adoption requires proper change management', 'rtbcb' ),
                    __( 'Integration challenges with existing systems', 'rtbcb' ),
                ],
                'assumptions_explained' => [
                    __( 'Labor cost savings based on 30% efficiency improvement', 'rtbcb' ),
                    __( 'Bank fee reduction through optimized cash positioning', 'rtbcb' ),
                    __( 'Error reduction value from automated reconciliation', 'rtbcb' ),
                ],
                'citations' => [],
                'next_actions' => [
                    __( 'Present business case to stakeholders', 'rtbcb' ),
                    __( 'Evaluate solution providers', 'rtbcb' ),
                    __( 'Plan implementation timeline', 'rtbcb' ),
                ],
                'confidence' => 0.85,
                'recommended_category' => $recommendation['recommended'] ?? '',
            ];

            // Format scenarios for output
            $formatted_scenarios = [
                'low'  => [
                    'total_annual_benefit' => $scenarios['conservative']['total_annual_benefit'] ?? 0,
                    'labor_savings'        => $scenarios['conservative']['labor_savings'] ?? 0,
                    'fee_savings'          => $scenarios['conservative']['fee_savings'] ?? 0,
                    'error_reduction'      => $scenarios['conservative']['error_reduction'] ?? 0,
                ],
                'base' => [
                    'total_annual_benefit' => $scenarios['base']['total_annual_benefit'] ?? 0,
                    'labor_savings'        => $scenarios['base']['labor_savings'] ?? 0,
                    'fee_savings'          => $scenarios['base']['fee_savings'] ?? 0,
                    'error_reduction'      => $scenarios['base']['error_reduction'] ?? 0,
                ],
                'high' => [
                    'total_annual_benefit' => $scenarios['optimistic']['total_annual_benefit'] ?? 0,
                    'labor_savings'        => $scenarios['optimistic']['labor_savings'] ?? 0,
                    'fee_savings'          => $scenarios['optimistic']['fee_savings'] ?? 0,
                    'error_reduction'      => $scenarios['optimistic']['error_reduction'] ?? 0,
                ],
            ];

            // Save lead if possible
            $lead_id = null;
            if ( class_exists( 'RTBCB_Leads' ) ) {
                try {
                    $lead_data = array_merge(
                        $user_inputs,
                        [
                            'recommended_category' => $recommendation['recommended'] ?? '',
                            'roi_low'              => $formatted_scenarios['low']['total_annual_benefit'],
                            'roi_base'             => $formatted_scenarios['base']['total_annual_benefit'],
                            'roi_high'             => $formatted_scenarios['high']['total_annual_benefit'],
                        ]
                    );
                    $lead_id = RTBCB_Leads::save_lead( $lead_data );
                } catch ( Throwable $e ) {
                    // Don't fail the request for lead saving issues
                    error_log( 'RTBCB: Failed to save lead - ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() );
                }
            }

            $response_data = [
                'scenarios'      => $formatted_scenarios,
                'recommendation' => $recommendation,
                'narrative'      => $narrative,
                'rag_context'    => [],
                'download_url'   => null,
                'lead_id'        => $lead_id,
            ];

            // Add success logging
            error_log( 'RTBCB: Business case generated successfully for ' . $user_inputs['email'] );

            wp_send_json_success( $response_data );

        } catch ( Exception $e ) {
            error_log( 'RTBCB Ajax Error: ' . $e->getMessage() );
            wp_send_json_error(
                [ 'message' => 'An error occurred. Please try again.' ],
                500
            );
        } catch ( Error $e ) {
            error_log( 'RTBCB Fatal Error: ' . $e->getMessage() );
            wp_send_json_error(
                [ 'message' => 'A system error occurred. Please contact support.' ],
                500
            );
        }

        exit; // Ensure no additional output
    }

    /**
     * Display admin notices.
     *
     * @return void
     */
    public function admin_notices() {
        // Check if API key is configured
        if ( current_user_can( 'manage_options' ) && empty( get_option( 'rtbcb_openai_api_key' ) ) ) {
            $settings_url = admin_url( 'admin.php?page=rtbcb-settings' );
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>';
            printf(
                wp_kses(
                    __( '<strong>Real Treasury Business Case Builder:</strong> Please <a href="%s">configure your OpenAI API key</a> to enable business case generation.', 'rtbcb' ),
                    [ 'strong' => [], 'a' => [ 'href' => [] ] ]
                ),
                esc_url( $settings_url )
            );
            echo '</p>';
            echo '</div>';
        }

        // Show upgrade notice if applicable
        if ( get_transient( 'rtbcb_show_upgrade_notice' ) ) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>';
            printf(
                esc_html__( 'Real Treasury Business Case Builder has been upgraded to version %s with new features including PDF reports, analytics, and enhanced lead tracking!', 'rtbcb' ),
                RTBCB_VERSION
            );
            echo '</p>';
            echo '</div>';
            delete_transient( 'rtbcb_show_upgrade_notice' );
        }
    }

    /**
     * Add plugin action links.
     *
     * @param array $links Existing links.
     * @return array Modified links.
     */
    public function plugin_action_links( $links ) {
        $custom_links = [
            'settings' => sprintf(
                '<a href="%s">%s</a>',
                admin_url( 'admin.php?page=rtbcb-settings' ),
                __( 'Settings', 'rtbcb' )
            ),
            'dashboard' => sprintf(
                '<a href="%s">%s</a>',
                admin_url( 'admin.php?page=rtbcb-dashboard' ),
                __( 'Dashboard', 'rtbcb' )
            ),
        ];

        return array_merge( $custom_links, $links );
    }

    /**
     * Plugin activation.
     *
     * @return void
     */
    public function activate() {
        // Create database tables
        $this->init_database();

        // Set default options
        $this->set_default_options();

        // Setup capabilities
        $this->setup_capabilities();

        // Schedule cron jobs
        $this->setup_cron_jobs();

        // Set activation flag
        set_transient( 'rtbcb_show_upgrade_notice', true, 30 );

        // Flush rewrite rules
        flush_rewrite_rules();

        error_log( 'RTBCB: Plugin activated successfully' );
    }

    /**
     * Plugin deactivation.
     *
     * @return void
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook( 'rtbcb_rebuild_rag_index' );
        wp_clear_scheduled_hook( 'rtbcb_cleanup_data' );

        // Flush rewrite rules
        flush_rewrite_rules();

        error_log( 'RTBCB: Plugin deactivated' );
    }

    /**
     * Plugin uninstall.
     *
     * @return void
     */
    public static function uninstall() {
        global $wpdb;

        // Remove database tables
        $tables = [
            $wpdb->prefix . 'rtbcb_leads',
            $wpdb->prefix . 'rtbcb_rag_index',
        ];

        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
        }

        // Remove options
        $options = [
            'rtbcb_version',
            'rtbcb_db_version',
            'rtbcb_openai_api_key',
            'rtbcb_mini_model',
            'rtbcb_premium_model',
            'rtbcb_embedding_model',
            'rtbcb_labor_cost_per_hour',
            'rtbcb_bank_fee_baseline',
            'rtbcb_pdf_enabled',
            'rtbcb_last_indexed',
            'rtbcb_settings',
        ];

        foreach ( $options as $option ) {
            delete_option( $option );
        }

        // Remove user capabilities
        $roles = wp_roles();
        foreach ( $roles->roles as $role_name => $role_info ) {
            $role = get_role( $role_name );
            if ( $role ) {
                $role->remove_cap( 'manage_rtbcb' );
                $role->remove_cap( 'view_rtbcb_leads' );
                $role->remove_cap( 'export_rtbcb_data' );
            }
        }

        // Remove uploaded files
        $upload_dir = wp_get_upload_dir();
        $plugin_dirs = [
            $upload_dir['basedir'] . '/rtbcb-reports',
            $upload_dir['basedir'] . '/rtbcb-temp',
        ];

        foreach ( $plugin_dirs as $dir ) {
            if ( is_dir( $dir ) ) {
                $files = glob( $dir . '/*' );
                foreach ( $files as $file ) {
                    if ( is_file( $file ) ) {
                        unlink( $file );
                    }
                }
                rmdir( $dir );
            }
        }

        error_log( 'RTBCB: Plugin uninstalled and data cleaned up' );
    }

    /**
     * Get plugin data.
     *
     * @param string $key Data key.
     * @return mixed
     */
    public function get_plugin_data( $key = null ) {
        if ( $key ) {
            return $this->plugin_data[ $key ] ?? null;
        }
        return $this->plugin_data;
    }
}

// Initialize the plugin
Real_Treasury_BCB::instance();

// Helper functions for use in templates and other plugins
if ( ! function_exists( 'rtbcb_get_leads_count' ) ) {
    /**
     * Get total number of leads.
     *
     * @return int
     */
    function rtbcb_get_leads_count() {
        $stats = RTBCB_Leads::get_statistics();
        return intval( $stats['total_leads'] ?? 0 );
    }
}

if ( ! function_exists( 'rtbcb_get_average_roi' ) ) {
    /**
     * Get average ROI across all leads.
     *
     * @return float
     */
    function rtbcb_get_average_roi() {
        $stats = RTBCB_Leads::get_statistics();
        return floatval( $stats['average_roi']['avg_base'] ?? 0 );
    }
}

if ( ! function_exists( 'rtbcb_is_configured' ) ) {
    /**
     * Check if plugin is properly configured.
     *
     * @return bool
     */
    function rtbcb_is_configured() {
        return ! empty( get_option( 'rtbcb_openai_api_key' ) );
    }
}

// AJAX action: my_action.
add_action( 'wp_ajax_my_action', 'rtbcb_my_action_callback' );
add_action( 'wp_ajax_nopriv_my_action', 'rtbcb_my_action_callback' );

/**
 * Handle the my_action AJAX request.
 *
 * @return void
 */
function rtbcb_my_action_callback() {
    check_ajax_referer( 'my_action_nonce', 'nonce' );

    // Execute desired logic.
    $data = [
        'message' => __( 'Action completed successfully.', 'rtbcb' ),
    ];

    wp_send_json_success( $data );
}
