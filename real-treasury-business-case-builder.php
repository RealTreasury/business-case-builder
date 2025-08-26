<?php
/**
 * Plugin Name: Real Treasury - Business Case Builder (Enhanced Pro)
 * Description: Professional-grade ROI calculator and comprehensive business case generator for treasury technology with advanced analysis and consultant-style reports.
 * Version: 2.1.0
 * Requires PHP: 7.4
 * Author: Real Treasury
 * Text Domain: rtbcb
 * License: GPL v2 or later
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RTBCB_VERSION', '2.1.0' );
define( 'RTBCB_FILE', __FILE__ );
define( 'RTBCB_URL', plugin_dir_url( RTBCB_FILE ) );
define( 'RTBCB_DIR', plugin_dir_path( RTBCB_FILE ) );

/**
 * Enhanced main plugin class.
 */
class RTBCB_Plugin {
    /**
     * Singleton instance.
     *
     * @var RTBCB_Plugin|null
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
     * @return RTBCB_Plugin
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
        add_action( 'wp_ajax_rtbcb_generate_case', [ $this, 'ajax_generate_comprehensive_case_debug' ] );
        add_action( 'wp_ajax_nopriv_rtbcb_generate_case', [ $this, 'ajax_generate_comprehensive_case_debug' ] );

        $this->init_hooks_debug();
    }

    /**
     * Initialize debug-related hooks.
     *
     * @return void
     */
    private function init_hooks_debug() {
        add_action( 'wp_ajax_rtbcb_debug_test', [ $this, 'debug_ajax_handler' ] );
        add_action( 'wp_ajax_nopriv_rtbcb_debug_test', [ $this, 'debug_ajax_handler' ] );
        add_action( 'wp_ajax_rtbcb_simple_test', [ $this, 'ajax_generate_case_simple' ] );
        add_action( 'wp_ajax_nopriv_rtbcb_simple_test', [ $this, 'ajax_generate_case_simple' ] );
    }

    /**
     * Include required files.
     *
     * @return void
     */
    private function includes() {
        // Core classes
        require_once RTBCB_DIR . 'inc/config.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-calculator.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-router.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-llm.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-rag.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-leads.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-db.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-category-recommender.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-validator.php';
        require_once RTBCB_DIR . 'inc/helpers.php';
        require_once RTBCB_DIR . 'inc/enhanced-ajax-handlers.php';

        // Admin functionality
        if ( is_admin() ) {
            require_once RTBCB_DIR . 'admin/classes/Admin.php';
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
            $this->migrate_v1_settings();
            $this->init_database();
            $this->set_default_options();
        }

        // Add new options introduced in 2.1.0
        if ( version_compare( $from_version, '2.1.0', '<' ) ) {
            $new_options = [
                'rtbcb_advanced_model'        => rtbcb_get_default_model( 'advanced' ),
                'rtbcb_comprehensive_analysis' => true,
            ];

            foreach ( $new_options as $option => $value ) {
                if ( get_option( $option ) === false ) {
                    add_option( $option, $value );
                }
            }
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
            'rtbcb_mini_model'         => rtbcb_get_default_model( 'mini' ),
            'rtbcb_premium_model'      => rtbcb_get_default_model( 'premium' ),
            'rtbcb_advanced_model'     => rtbcb_get_default_model( 'advanced' ),
            'rtbcb_embedding_model'    => rtbcb_get_default_model( 'embedding' ),
            'rtbcb_labor_cost_per_hour'=> 100,
            'rtbcb_bank_fee_baseline'  => 15000,
            'rtbcb_comprehensive_analysis' => true,
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
        // DOMPurify ensures any injected HTML is sanitized client-side.
        wp_enqueue_script(
            'dompurify',
            RTBCB_URL . 'public/js/dompurify.min.js',
            [],
            '3.0.2',
            true
        );

        wp_enqueue_script(
            'rtbcb-wizard',
            RTBCB_URL . 'public/js/rtbcb-wizard.js',
            [ 'jquery', 'dompurify' ],
            RTBCB_VERSION,
            true
        );

        wp_enqueue_script(
            'rtbcb-script',
            RTBCB_URL . 'public/js/rtbcb.js',
            [ 'jquery', 'rtbcb-wizard', 'dompurify' ],
            RTBCB_VERSION,
            true
        );

        wp_localize_script(
            'rtbcb-script',
            'rtbcbAjax',
            [
                'ajax_url'    => admin_url( 'admin-ajax.php' ),
                'strings'     => [
                    'error'                   => __( 'An error occurred. Please try again.', 'rtbcb' ),
                    'generating'              => __( 'Generating your comprehensive business case...', 'rtbcb' ),
                    'analyzing'               => __( 'Analyzing your treasury operations...', 'rtbcb' ),
                    'financial_modeling'      => __( 'Building financial models...', 'rtbcb' ),
                    'risk_assessment'         => __( 'Conducting risk assessment...', 'rtbcb' ),
                    'industry_benchmarking'   => __( 'Performing industry benchmarking...', 'rtbcb' ),
                    'implementation_planning' => __( 'Creating implementation roadmap...', 'rtbcb' ),
                    'vendor_evaluation'       => __( 'Preparing vendor evaluation framework...', 'rtbcb' ),
                    'finalizing_report'       => __( 'Finalizing professional report...', 'rtbcb' ),
                    'invalid_email'           => __( 'Please enter a valid email address.', 'rtbcb' ),
                    'required_field'          => __( 'This field is required.', 'rtbcb' ),
                    'select_pain_points'      => __( 'Please select at least one pain point.', 'rtbcb' ),
                ],
                'settings'    => [
                    'pdf_enabled'            => get_option( 'rtbcb_pdf_enabled', true ),
                    'comprehensive_analysis' => get_option( 'rtbcb_comprehensive_analysis', true ),
                    'professional_reports'   => get_option( 'rtbcb_professional_reports', true ),
                ],
            ]
        );

        wp_enqueue_script(
            'rtbcb-report',
            RTBCB_URL . 'public/js/rtbcb-report.js',
            [ 'dompurify' ],
            RTBCB_VERSION,
            true
        );

        $report_model = sanitize_text_field( get_option( 'rtbcb_advanced_model', rtbcb_get_default_model( 'advanced' ) ) );

        $config = rtbcb_get_gpt5_config( get_option( 'rtbcb_gpt5_config', [] ) );

        $config_localized = [
            'model'              => sanitize_text_field( $config['model'] ),
            'max_output_tokens'  => intval( $config['max_output_tokens'] ),
            'text'               => [ 'verbosity' => sanitize_text_field( $config['text']['verbosity'] ?? '' ) ],
            'store'              => (bool) $config['store'],
            'timeout'            => intval( $config['timeout'] ),
            'max_retries'        => intval( $config['max_retries'] ),
        ];

        if ( rtbcb_model_supports_temperature( $config['model'] ) ) {
            $config_localized['temperature'] = floatval( $config['temperature'] );
        }

        $supported = [ 'model', 'max_output_tokens', 'text', 'temperature', 'store', 'timeout', 'max_retries' ];
        $config_localized = array_intersect_key( $config_localized, array_flip( $supported ) );

        $model_capabilities = rtbcb_get_model_capabilities();

        wp_localize_script(
            'rtbcb-report',
            'rtbcbReport',
            [
                'ajax_url'          => admin_url( 'admin-ajax.php' ),
                'nonce'             => wp_create_nonce( 'rtbcb_generate_report' ),
                'report_model'      => $report_model,
                'defaults'          => $config_localized,
                'model_capabilities' => $model_capabilities,
            ]
        );
    }

    /**
     * Check if assets should be loaded on current page.
     *
     * @return bool
     */
    private function should_load_assets() {
        if ( is_admin() ) {
            if ( isset( $_GET['page'] ) ) {
                $page = sanitize_key( wp_unslash( $_GET['page'] ) );

                if ( strpos( $page, 'rtbcb' ) !== false ) {
                    return true;
                }
            }

            return false;
        }

        $post = get_post();
        if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'rt_business_case_builder' ) ) {
            return true;
        }

        return false;
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
            'title'    => __( 'Treasury Tech Business Case Builder', 'rtbcb' ),
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
     * Debug wrapper for comprehensive case generation AJAX handler.
     */
    public function ajax_generate_comprehensive_case_debug() {
        error_log( 'RTBCB: Enter ajax_generate_comprehensive_case_debug' );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            ini_set( 'display_errors', '1' );
        }

        $post_keys = implode( ', ', array_keys( $_POST ) );
        error_log( 'RTBCB: POST keys: ' . $post_keys );

        $nonce_present = isset( $_POST['rtbcb_nonce'] );
        error_log( 'RTBCB: nonce present: ' . ( $nonce_present ? 'yes' : 'no' ) );

        $nonce_valid = check_ajax_referer( 'rtbcb_generate', 'rtbcb_nonce', false );
        error_log( 'RTBCB: nonce verification: ' . ( $nonce_valid ? 'passed' : 'failed' ) );

        if ( ! $nonce_valid ) {
            wp_send_json_error( __( 'Security check failed.', 'rtbcb' ), 403 );
        }

        $company = rtbcb_get_current_company();
        if ( empty( $company ) ) {
            wp_send_json_error( __( 'No company data found. Please run the company overview first.', 'rtbcb' ), 400 );
        }

        $required_classes = [ 'RTBCB_Calculator', 'RTBCB_DB' ];
        $missing_classes  = [];
        foreach ( $required_classes as $class ) {
            $exists = class_exists( $class );
            error_log( 'RTBCB: class ' . $class . ' exists: ' . ( $exists ? 'yes' : 'no' ) );
            if ( ! $exists ) {
                $missing_classes[] = $class;
            }
        }

        if ( ! empty( $missing_classes ) ) {
            wp_send_json_error( __( 'Required components missing.', 'rtbcb' ), 500 );
        }

        rtbcb_setup_ajax_logging();
        rtbcb_increase_memory_limit();
        if ( ! ini_get( 'safe_mode' ) ) {
            set_time_limit( 300 );
        }

        try {
            $this->ajax_generate_comprehensive_case();
        } catch ( Exception $e ) {
            error_log( 'RTBCB Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
            wp_send_json_error( __( 'An error occurred. Please try again later.', 'rtbcb' ), 500 );
        } catch ( Error $e ) {
            error_log( 'RTBCB Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
            wp_send_json_error( __( 'An error occurred. Please try again later.', 'rtbcb' ), 500 );
        }

        error_log( 'RTBCB: Exit ajax_generate_comprehensive_case_debug' );
    }


    /**
     * Enhanced AJAX handler with memory management
     */
    public function ajax_generate_comprehensive_case() {
        rtbcb_setup_ajax_logging();

        // STEP 1: Increase memory limit and log initial state
        rtbcb_increase_memory_limit();
        rtbcb_log_memory_usage( 'start' );

        // STEP 2: Set longer execution time
        if ( ! ini_get( 'safe_mode' ) ) {
            set_time_limit( 300 ); // 5 minutes
        }

        // Prevent any output before JSON
        if ( ob_get_level() ) {
            ob_end_clean();
        }

        try {
            // Verify nonce
            if ( ! check_ajax_referer( 'rtbcb_generate', 'rtbcb_nonce', false ) ) {
                rtbcb_send_standardized_error( 
                    'security_check_failed', 
                    rtbcb_get_user_friendly_error( 'security_check_failed' ), 
                    403,
                    'Nonce verification failed'
                );
            }

            $company = rtbcb_get_current_company();
            if ( empty( $company ) ) {
                rtbcb_send_standardized_error(
                    'missing_company_data',
                    __( 'No company data found. Please run the company overview first.', 'rtbcb' ),
                    400,
                    'Company data not found in session/database'
                );
            }

            rtbcb_log_memory_usage( 'after_nonce_verification' );

            // Collect and validate form data
            $hours_reconciliation_raw   = $_POST['hours_reconciliation'] ?? null;
            $hours_cash_positioning_raw = $_POST['hours_cash_positioning'] ?? null;
            $num_banks_raw              = $_POST['num_banks'] ?? null;
            $ftes_raw                   = $_POST['ftes'] ?? null;

            if ( ! is_numeric( $hours_reconciliation_raw ) ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please enter your weekly reconciliation hours.', 'rtbcb' ),
                    400,
                    'Invalid hours_reconciliation value'
                );
            }
            if ( ! is_numeric( $hours_cash_positioning_raw ) ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please enter your weekly cash positioning hours.', 'rtbcb' ),
                    400,
                    'Invalid hours_cash_positioning value'
                );
            }
            if ( ! is_numeric( $num_banks_raw ) ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please enter the number of banking relationships.', 'rtbcb' ),
                    400,
                    'Invalid num_banks value'
                );
            }
            if ( ! is_numeric( $ftes_raw ) ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please enter your treasury team size.', 'rtbcb' ),
                    400,
                    'Invalid ftes value'
                );
            }

            $user_inputs = [
                'email'                  => sanitize_email( $_POST['email'] ?? '' ),
                'company_name'           => sanitize_text_field( $_POST['company_name'] ?? '' ),
                'company_size'           => sanitize_text_field( $_POST['company_size'] ?? '' ),
                'industry'               => sanitize_text_field( $_POST['industry'] ?? '' ),
                'job_title'              => sanitize_text_field( $_POST['job_title'] ?? '' ),
                'hours_reconciliation'   => floatval( $hours_reconciliation_raw ),
                'hours_cash_positioning' => floatval( $hours_cash_positioning_raw ),
                'num_banks'              => intval( $num_banks_raw ),
                'ftes'                   => floatval( $ftes_raw ),
                'pain_points'            => array_map( 'sanitize_text_field', (array) ( $_POST['pain_points'] ?? [] ) ),
                'business_objective'     => sanitize_text_field( $_POST['business_objective'] ?? '' ),
                'implementation_timeline'=> sanitize_text_field( $_POST['implementation_timeline'] ?? '' ),
                'budget_range'           => sanitize_text_field( $_POST['budget_range'] ?? '' ),
            ];

            rtbcb_log_api_debug( 'Collected user inputs', $user_inputs );

            rtbcb_log_api_debug( 'Validating user inputs' );

            // Validate required fields
            if ( empty( $user_inputs['email'] ) || ! is_email( $user_inputs['email'] ) ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please enter a valid email address.', 'rtbcb' ),
                    400,
                    'Invalid or missing email'
                );
            }

            if ( empty( $user_inputs['company_name'] ) ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please enter your company name.', 'rtbcb' ),
                    400,
                    'Missing company_name'
                );
            }

            if ( empty( $user_inputs['company_size'] ) ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please select your company size.', 'rtbcb' ),
                    400,
                    'Missing company_size'
                );
            }

            if ( empty( $user_inputs['industry'] ) ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please select your industry.', 'rtbcb' ),
                    400,
                    'Missing industry'
                );
            }

            if ( $user_inputs['hours_reconciliation'] <= 0 ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please enter your weekly reconciliation hours.', 'rtbcb' ),
                    400,
                    'Invalid reconciliation hours value'
                );
            }

            if ( $user_inputs['hours_cash_positioning'] <= 0 ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please enter your weekly cash positioning hours.', 'rtbcb' ),
                    400,
                    'Invalid cash positioning hours value'
                );
            }

            if ( $user_inputs['num_banks'] <= 0 ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please enter the number of banking relationships.', 'rtbcb' ),
                    400,
                    'Invalid number of banks'
                );
            }

            if ( $user_inputs['ftes'] <= 0 ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please enter your treasury team size.', 'rtbcb' ),
                    400,
                    'Invalid treasury team size'
                );
            }

            if ( empty( $user_inputs['pain_points'] ) ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please select at least one challenge.', 'rtbcb' ),
                    400,
                    'No pain points selected'
                );
            }

            if ( empty( $user_inputs['business_objective'] ) ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please select a primary business objective.', 'rtbcb' ),
                    400,
                    'Missing business objective'
                );
            }

            if ( empty( $user_inputs['implementation_timeline'] ) ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please select an implementation timeline.', 'rtbcb' ),
                    400,
                    'Missing implementation timeline'
                );
            }

            if ( empty( $user_inputs['budget_range'] ) ) {
                rtbcb_send_standardized_error(
                    'validation_failed',
                    __( 'Please select a budget range.', 'rtbcb' ),
                    400,
                    'Missing budget range'
                );
            }

            rtbcb_log_api_debug( 'Validation passed', $user_inputs );
            rtbcb_log_memory_usage( 'after_validation' );

            // Calculate ROI scenarios
            if ( ! class_exists( 'RTBCB_Calculator' ) ) {
                rtbcb_send_standardized_error(
                    'system_error',
                    rtbcb_get_user_friendly_error( 'system_error' ),
                    500,
                    'Calculator class not found'
                );
            }

            rtbcb_log_api_debug( 'Starting ROI calculation' );
            $scenarios = RTBCB_Calculator::calculate_roi( $user_inputs );
            rtbcb_log_api_debug( 'ROI scenarios calculated', $scenarios );
            rtbcb_log_memory_usage( 'after_roi_calculation' );

            // Get category recommendation
            if ( ! class_exists( 'RTBCB_Category_Recommender' ) ) {
                rtbcb_send_standardized_error(
                    'system_error',
                    rtbcb_get_user_friendly_error( 'system_error' ),
                    500,
                    'Category Recommender class not found'
                );
            }

            rtbcb_log_api_debug( 'Running category recommendation' );
            $recommendation = RTBCB_Category_Recommender::recommend_category( $user_inputs );
            rtbcb_log_api_debug( 'Category recommendation result', $recommendation );
            rtbcb_log_memory_usage( 'after_category_recommendation' );

            // Get RAG context (with memory monitoring)
            $rag_context = [];
            if ( class_exists( 'RTBCB_RAG' ) ) {
                try {
                    $rag = new RTBCB_RAG();
                    $search_query = implode(
                        ' ',
                        array_merge(
                            [ $user_inputs['company_name'], $user_inputs['industry'] ],
                            $user_inputs['pain_points'],
                            [ $recommendation['recommended'] ?? '' ]
                        )
                    );
                    rtbcb_log_api_debug( 'Performing RAG search', [ 'query' => $search_query ] );
                    $rag_context = $rag->search_similar( $search_query, 3 );
                    rtbcb_log_api_debug( 'RAG search results', $rag_context );
                    rtbcb_log_memory_usage( 'after_rag_search' );
                } catch ( Exception $e ) {
                    rtbcb_log_error( 'RAG search failed', $e->getMessage() );
                } catch ( Error $e ) {
                    rtbcb_log_error( 'RAG search fatal error', $e->getMessage() );
                }
            }

            // Generate business case with memory optimization
            $comprehensive_analysis = null;
            if ( class_exists( 'RTBCB_LLM' ) ) {
                try {
                    if ( function_exists( 'gc_collect_cycles' ) ) {
                        gc_collect_cycles();
                    }

                    rtbcb_log_memory_usage( 'before_llm_generation' );

                    if ( empty( get_option( 'rtbcb_openai_api_key' ) ) ) {
                        rtbcb_log_api_debug( 'OpenAI API key not configured' );
                        rtbcb_send_standardized_error(
                            'no_api_key',
                            rtbcb_get_user_friendly_error( 'no_api_key' ),
                            400,
                            'OpenAI API key not configured'
                        );
                    }

                    rtbcb_log_api_debug( 'Calling LLM for comprehensive business case' );
                    $llm = new RTBCB_LLM();
                    $comprehensive_analysis = $llm->generate_comprehensive_business_case(
                        $user_inputs,
                        $scenarios,
                        $rag_context
                    );

                    rtbcb_log_memory_usage( 'after_llm_generation' );

                    if ( is_wp_error( $comprehensive_analysis ) ) {
                        $error_message = $comprehensive_analysis->get_error_message();
                        $error_code    = method_exists( $comprehensive_analysis, 'get_error_code' ) ? $comprehensive_analysis->get_error_code() : '';
                        $error_data    = method_exists( $comprehensive_analysis, 'get_error_data' ) ? $comprehensive_analysis->get_error_data() : null;
                        rtbcb_log_error(
                            'LLM generation failed',
                            [
                                'code'   => $error_code,
                                'message' => $error_message,
                                'data'   => $error_data,
                                'errors' => isset( $comprehensive_analysis->errors ) ? $comprehensive_analysis->errors : [],
                            ]
                        );
                        if ( 'no_api_key' === $error_code ) {
                            rtbcb_send_standardized_error(
                                'no_api_key',
                                rtbcb_get_user_friendly_error( 'no_api_key' ),
                                400,
                                'OpenAI API key not configured'
                            );
                        }
                        
                        rtbcb_send_standardized_error(
                            'generation_failed',
                            rtbcb_get_user_friendly_error( 'generation_failed' ),
                            500,
                            $error_message
                        );
                    }

                    if ( isset( $comprehensive_analysis['error'] ) ) {
                        rtbcb_send_standardized_error(
                            'generation_failed',
                            rtbcb_get_user_friendly_error( 'generation_failed' ),
                            500,
                            'LLM returned error response'
                        );
                    }
                    rtbcb_log_api_debug( 'LLM generation succeeded' );
                } catch ( Exception $e ) {
                    rtbcb_send_standardized_error(
                        'generation_failed',
                        rtbcb_get_user_friendly_error( 'generation_failed' ),
                        500,
                        'LLM generation exception: ' . $e->getMessage()
                    );
                } catch ( Error $e ) {
                    rtbcb_send_standardized_error(
                        'system_error',
                        rtbcb_get_user_friendly_error( 'system_error' ),
                        500,
                        'LLM generation fatal error: ' . $e->getMessage()
                    );
                }
            }

            if ( empty( $comprehensive_analysis ) ) {
                rtbcb_send_standardized_error(
                    'generation_failed',
                    rtbcb_get_user_friendly_error( 'generation_failed' ),
                    500,
                    'LLM returned empty analysis'
                );
            }

            if ( empty( $comprehensive_analysis['company_name'] ) ) {
                $comprehensive_analysis['company_name'] = $user_inputs['company_name'];
            }

            // Format scenarios
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

            rtbcb_log_memory_usage( 'after_scenario_formatting' );

            // Generate HTML report
            $report_html = '';
            try {
                $report_html = $this->get_comprehensive_report_html( $comprehensive_analysis );
                if ( empty( $report_html ) ) {
                    rtbcb_send_standardized_error(
                        'template_not_found',
                        rtbcb_get_user_friendly_error( 'template_not_found' ),
                        500,
                        'Report HTML generation returned empty'
                    );
                }
                rtbcb_log_memory_usage( 'after_report_generation' );
            } catch ( Exception $e ) {
                rtbcb_send_standardized_error(
                    'template_not_found',
                    rtbcb_get_user_friendly_error( 'template_not_found' ),
                    500,
                    'Report generation failed: ' . $e->getMessage()
                );
            }

            // Save lead data (non-blocking)
            $lead_id = null;
            if ( class_exists( 'RTBCB_Leads' ) ) {
                try {
                    $lead_data = [
                        'email'                  => $user_inputs['email'],
                        'company_size'           => $user_inputs['company_size'],
                        'industry'               => $user_inputs['industry'],
                        'hours_reconciliation'   => $user_inputs['hours_reconciliation'],
                        'hours_cash_positioning' => $user_inputs['hours_cash_positioning'],
                        'num_banks'              => $user_inputs['num_banks'],
                        'ftes'                   => $user_inputs['ftes'],
                        'pain_points'            => $user_inputs['pain_points'],
                        'recommended_category'   => $recommendation['recommended'] ?? '',
                        'roi_low'                => $formatted_scenarios['low']['total_annual_benefit'],
                        'roi_base'               => $formatted_scenarios['base']['total_annual_benefit'],
                        'roi_high'               => $formatted_scenarios['high']['total_annual_benefit'],
                        'report_html'            => $report_html,
                    ];

                    $lead_id = RTBCB_Leads::save_lead( $lead_data );
                    if ( false === $lead_id ) {
                        rtbcb_log_error( 'Failed to save lead', $lead_data );
                    }
                    rtbcb_log_memory_usage( 'after_lead_save' );
                } catch ( Throwable $e ) {
                    rtbcb_log_error( 'Failed to save lead', $e->getMessage() );
                }
            }

            // Prepare final response
            $response_data = [
                'scenarios'              => $formatted_scenarios,
                'recommendation'         => $recommendation,
                'comprehensive_analysis' => $comprehensive_analysis,
                'narrative'              => $comprehensive_analysis,
                'rag_context'            => $rag_context,
                'report_html'            => $report_html,
                'lead_id'                => $lead_id,
                'company_name'           => $user_inputs['company_name'],
                'analysis_type'          => 'comprehensive',
                'api_used'               => ! empty( get_option( 'rtbcb_openai_api_key' ) ),
                'fallback_used'          => isset( $comprehensive_analysis['enhanced_fallback'] ),
                'memory_info'            => rtbcb_get_memory_status(),
            ];

            rtbcb_log_memory_usage( 'before_response' );

            rtbcb_send_standardized_success( $response_data );

        } catch ( Exception $e ) {
            rtbcb_log_memory_usage( 'exception_occurred' );
            rtbcb_send_standardized_error(
                'generation_failed',
                rtbcb_get_user_friendly_error( 'generation_failed' ),
                500,
                'Ajax exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
            );
        } catch ( Error $e ) {
            rtbcb_log_memory_usage( 'fatal_error_occurred' );
            rtbcb_send_standardized_error(
                'system_error',
                rtbcb_get_user_friendly_error( 'system_error' ),
                500,
                'Ajax fatal error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
            );
        }

        exit;
    }
    /**
     * Generate comprehensive report HTML from template with improved fallback handling.
     */
    private function get_comprehensive_report_html( $business_case_data ) {
        $primary_template = RTBCB_DIR . 'templates/comprehensive-report-template.php';
        $fallback_template = RTBCB_DIR . 'templates/report-template.php';
        $selected_template = '';
        
        // Validate and select the best available template
        if ( file_exists( $primary_template ) && is_readable( $primary_template ) ) {
            $selected_template = $primary_template;
        } elseif ( file_exists( $fallback_template ) && is_readable( $fallback_template ) ) {
            $selected_template = $fallback_template;
            rtbcb_log_error( 'Comprehensive template not found, using fallback', [ 'primary' => $primary_template ] );
        } else {
            rtbcb_log_error( 'No report templates found', [ 
                'primary' => $primary_template, 
                'fallback' => $fallback_template 
            ] );
            return '';
        }

        // Ensure business case data is valid
        if ( ! is_array( $business_case_data ) ) {
            $business_case_data = [];
        }

        // Generate report HTML with error handling
        ob_start();
        try {
            include $selected_template;
            $html = ob_get_clean();
            
            // Validate generated HTML is not empty or just whitespace
            if ( empty( trim( $html ) ) ) {
                rtbcb_log_error( 'Template generated empty HTML', [ 'template' => $selected_template ] );
                return '';
            }
            
            return wp_kses_post( $html );
        } catch ( Exception $e ) {
            ob_end_clean(); // Clean the buffer if template failed
            rtbcb_log_error( 'Template rendering failed', [ 
                'template' => $selected_template, 
                'error' => $e->getMessage() 
            ] );
            return '';
        }
    }

    public function admin_notices() {
        // Check if API key is configured
        if ( current_user_can( 'manage_options' ) && empty( get_option( 'rtbcb_openai_api_key' ) ) ) {
            $settings_url = admin_url( 'admin.php?page=rtbcb-unified-tests#settings' );
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
                admin_url( 'admin.php?page=rtbcb-unified-tests#settings' ),
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

    /**
     * Handle debug diagnostics via AJAX.
     *
     * @return void
     */
    public function debug_ajax_handler() {
        $nonce       = isset( $_POST['rtbcb_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['rtbcb_nonce'] ) ) : '';
        $nonce_valid = wp_verify_nonce( $nonce, 'rtbcb_debug' );

        $post_keys = array_map( 'sanitize_key', array_keys( $_POST ) );
        $api_key   = get_option( 'rtbcb_openai_api_key', '' );

        global $wpdb;
        $table_name   = $wpdb->prefix . 'rtbcb_leads';
        $table_exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name );

        $diagnostics = [
            'wp_functions'     => function_exists( 'add_action' ) && function_exists( 'wp_send_json_success' ),
            'required_classes' => class_exists( 'RTBCB_Calculator' ) && class_exists( 'RTBCB_DB' ),
            'nonce_valid'      => $nonce_valid,
            'post_keys'        => $post_keys,
            'api_key_present'  => ! empty( $api_key ),
            'db_table_exists'  => $table_exists,
            'memory_usage'     => size_format( memory_get_usage( true ) ),
        ];

        wp_send_json_success( $diagnostics );
    }

    /**
     * Handle simplified ROI test via AJAX.
     *
     * @return void
     */
    public function ajax_generate_case_simple() {
        if ( ! check_ajax_referer( 'rtbcb_simple', 'rtbcb_nonce', false ) ) {
            wp_send_json_error( __( 'Security check failed.', 'rtbcb' ), 403 );
        }

        $investment_raw = wp_unslash( $_POST['investment'] ?? '' );
        $returns_raw    = wp_unslash( $_POST['returns'] ?? '' );

        if ( ! is_numeric( $investment_raw ) || ! is_numeric( $returns_raw ) ) {
            wp_send_json_error( __( 'Invalid values provided.', 'rtbcb' ), 400 );
        }

        $investment = floatval( $investment_raw );
        $returns    = floatval( $returns_raw );

        if ( $investment <= 0 || $returns <= 0 ) {
            wp_send_json_error( __( 'Invalid values provided.', 'rtbcb' ), 400 );
        }

        $roi = 0;
        if ( $investment > 0 ) {
            $roi = ( ( $returns - $investment ) / $investment ) * 100;
        }

        wp_send_json_success(
            [
                'roi' => round( $roi, 2 ),
            ]
        );
    }
}

// Initialize the plugin
RTBCB_Plugin::instance();

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


// Add AJAX handlers for overview generation.

/**
 * Handle AJAX request for company overview generation.
 *
 * @return void
 */


/**
 * Check progress of company overview generation.
 *
 * @return void
 */


/**
 * AJAX handler for generating company overview.
 *
 * @return void
 */


/**
 * AJAX handler to clear current company data.
 *
 * @return void
 */


/**
 * AJAX handler for generating Real Treasury platform overview.
 *
 * @return void
 */


/**
 * AJAX handler for generating category recommendation.
 *
 * @return void
 */


// Enqueue admin scripts for company overview page.

/**
 * Enqueue admin scripts for company overview page.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */


/**
 * Enqueue admin scripts for treasury tech overview page.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */


/**
 * Enqueue admin scripts for real treasury overview page.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */


/**
 * Enqueue admin scripts for category recommendation page.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */


/**
 * Two-phase company analysis AJAX handler.
 */
class RTBCB_TwoPhase_Analysis {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'wp_ajax_rtbcb_openai_request', [ $this, 'handle_openai_request' ] );
    }

    /**
     * Handle generic OpenAI requests for two-phase analysis.
     */
    public function handle_openai_request() {
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'rtbcb_admin_nonce' ) ) {
            wp_die( __( 'Security check failed', 'rtbcb' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Insufficient permissions', 'rtbcb' ) );
        }

        $prompt      = sanitize_textarea_field( wp_unslash( $_POST['prompt'] ?? '' ) );
        $max_tokens  = intval( $_POST['max_tokens'] ?? 800 );
        $temperature = floatval( $_POST['temperature'] ?? 0.3 );

        if ( empty( $prompt ) ) {
            wp_send_json_error( [ 'message' => __( 'Prompt is required', 'rtbcb' ) ] );
        }

        try {
            $response = $this->call_openai_api( $prompt, $max_tokens, $temperature );

            wp_send_json_success(
                [
                    'response'    => $response,
                    'tokens_used' => $max_tokens,
                    'timestamp'   => current_time( 'mysql' ),
                ]
            );
        } catch ( Exception $e ) {
            error_log( 'RTBCB OpenAI API Error: ' . $e->getMessage() );
            wp_send_json_error(
                [
                    'message' => sprintf( __( 'API request failed: %s', 'rtbcb' ), $e->getMessage() ),
                ]
            );
        }
    }

    /**
     * Make OpenAI API call.
     *
     * @param string $prompt      Prompt to send.
     * @param int    $max_tokens  Maximum tokens.
     * @param float  $temperature Temperature.
     *
     * @return string
     * @throws Exception When API fails.
     */
    private function call_openai_api( $prompt, $max_tokens, $temperature ) {
        $api_key = get_option( 'rtbcb_openai_api_key' );

        if ( empty( $api_key ) ) {
            throw new Exception( __( 'OpenAI API key not configured', 'rtbcb' ) );
        }

        $url  = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model'       => 'gpt-4',
            'messages'    => [
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens'  => $max_tokens,
            'temperature' => $temperature,
            'timeout'     => 60,
        ];

        $args = [
            'timeout' => 60,
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'    => wp_json_encode( $data ),
        ];

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            throw new Exception( sprintf( __( 'HTTP request failed: %s', 'rtbcb' ), $response->get_error_message() ) );
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $response_code ) {
            throw new Exception( sprintf( __( 'API returned error code: %s', 'rtbcb' ), $response_code ) );
        }

        $body    = wp_remote_retrieve_body( $response );
        $decoded = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            throw new Exception( __( 'Invalid JSON response', 'rtbcb' ) );
        }

        if ( ! isset( $decoded['choices'][0]['message']['content'] ) ) {
            throw new Exception( __( 'Unexpected API response structure', 'rtbcb' ) );
        }

        return $decoded['choices'][0]['message']['content'];
    }

    /**
     * Validate JSON response from OpenAI.
     *
     * @param string $response Response string.
     *
     * @return array|false
     */
    private function validate_json_response( $response ) {
        $decoded = json_decode( $response, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return false;
        }

        return $decoded;
    }

    /**
     * Log analysis attempts for debugging.
     *
     * @param string $company_name Company name.
     * @param string $phase        Phase identifier.
     * @param bool   $success      Whether phase succeeded.
     */
    private function log_analysis_attempt( $company_name, $phase, $success = true ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log(
                sprintf(
                    'RTBCB Analysis: Company=%s, Phase=%s, Success=%s',
                    $company_name,
                    $phase,
                    $success ? 'Yes' : 'No'
                )
            );
        }
    }
}

new RTBCB_TwoPhase_Analysis();

// Optional: Add settings for API configuration.
add_action(
    'admin_init',
    function () {
        register_setting( 'rtbcb_settings', 'rtbcb_openai_api_key' );

        add_settings_field(
            'rtbcb_openai_api_key',
            __( 'OpenAI API Key', 'rtbcb' ),
            function () {
                $value = get_option( 'rtbcb_openai_api_key', '' );
                echo '<input type="password" name="rtbcb_openai_api_key" value="' . esc_attr( $value ) . '" class="regular-text" />';
                echo '<p class="description">' . esc_html__( 'Enter your OpenAI API key for company analysis.', 'rtbcb' ) . '</p>';
            },
            'rtbcb_settings',
            'rtbcb_main_section'
        );
    }
);

/**
 * Enhanced error handling for two-phase analysis.
 */
class RTBCB_Analysis_Logger {

    /**
     * Log phase start.
     *
     * @param string $company Company name.
     * @param string $phase   Phase identifier.
     */
    public static function log_phase_start( $company, $phase ) {
        self::log( "Started Phase {$phase} for {$company}" );
    }

    /**
     * Log phase completion.
     *
     * @param string $company  Company name.
     * @param string $phase    Phase identifier.
     * @param int    $duration Duration in ms.
     */
    public static function log_phase_complete( $company, $phase, $duration ) {
        self::log( "Completed Phase {$phase} for {$company} in {$duration}ms" );
    }

    /**
     * Log phase error.
     *
     * @param string $company Company name.
     * @param string $phase   Phase identifier.
     * @param string $error   Error message.
     */
    public static function log_phase_error( $company, $phase, $error ) {
        self::log( "Phase {$phase} failed for {$company}: {$error}", 'error' );
    }

    /**
     * Write to debug log.
     *
     * @param string $message Message.
     * @param string $level   Log level.
     */
    private static function log( $message, $level = 'info' ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( "[RTBCB-{$level}] {$message}" );
        }
    }
}

/**
 * Usage tracking for rate limiting and optimization.
 */
class RTBCB_Usage_Tracker {

    /**
     * Track API request usage.
     *
     * @param string $company     Company name.
     * @param string $phase       Phase identifier.
     * @param int    $tokens_used Tokens used.
     */
    public static function track_request( $company, $phase, $tokens_used ) {
        $usage = get_option( 'rtbcb_api_usage', [] );

        $today = date( 'Y-m-d' );
        if ( ! isset( $usage[ $today ] ) ) {
            $usage[ $today ] = [
                'requests'  => 0,
                'tokens'    => 0,
                'companies' => [],
            ];
        }

        $usage[ $today ]['requests']++;
        $usage[ $today ]['tokens']      += $tokens_used;
        $usage[ $today ]['companies'][] = $company;

        $cutoff = date( 'Y-m-d', strtotime( '-30 days' ) );
        foreach ( $usage as $date => $data ) {
            if ( $date < $cutoff ) {
                unset( $usage[ $date ] );
            }
        }

        update_option( 'rtbcb_api_usage', $usage );
    }

    /**
     * Get today's usage statistics.
     *
     * @return array
     */
    public static function get_daily_usage() {
        $usage = get_option( 'rtbcb_api_usage', [] );
        $today = date( 'Y-m-d' );

        return isset( $usage[ $today ] ) ? $usage[ $today ] : [
            'requests'  => 0,
            'tokens'    => 0,
            'companies' => [],
        ];
    }
}

add_action( 'admin_notices', 'rtbcb_show_api_health_notice' );

/**
 * Display OpenAI API health notices.
 */
function rtbcb_show_api_health_notice() {
    $screen = get_current_screen();
    if ( ! $screen || strpos( $screen->id, 'rtbcb' ) === false ) {
        return;
    }

    $last_ok      = get_option( 'rtbcb_openai_last_ok', 0 );
    $last_error   = get_option( 'rtbcb_openai_last_error_at', 0 );
    $error_info   = get_transient( 'rtbcb_openai_error' );
    $now          = time();

    if ( $last_ok && ( $now - $last_ok ) < 180 ) {
        echo '<div class="notice notice-success"><p>';
        echo '<strong>' . esc_html__( 'OpenAI API:', 'rtbcb' ) . '</strong> ';
        echo esc_html__( 'Connection healthy', 'rtbcb' );
        echo '</p></div>';
        return;
    }

    if ( $error_info && $last_error && ( $now - $last_error ) < 600 && ( ! $last_ok || ( $now - $last_ok ) > 600 ) ) {
        $message = rtbcb_get_error_message_for_code( $error_info['code'], $error_info['httpStatus'] );

        echo '<div class="notice notice-error"><p>';
        echo '<strong>' . esc_html__( 'OpenAI API Error:', 'rtbcb' ) . '</strong> ';
        echo esc_html( $message );

        if ( 'unauthorized' === $error_info['code'] ) {
            $settings_url = admin_url( 'admin.php?page=rtbcb-unified-tests#settings' );
            echo ' <a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Check Settings', 'rtbcb' ) . '</a>';
        }

        echo '</p></div>';
        return;
    }

    if ( $error_info && 'rate_limited' === $error_info['code'] ) {
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>' . esc_html__( 'OpenAI API:', 'rtbcb' ) . '</strong> ';
        echo esc_html__( 'Rate limit exceeded. Please wait before retrying.', 'rtbcb' );
        echo '</p></div>';
    }
}

/**
 * Get error message for a given error code.
 *
 * @param string $code       Error code.
 * @param int    $httpStatus HTTP status code.
 * @return string
 */
function rtbcb_get_error_message_for_code( $code, $httpStatus ) {
    $messages = [
        'unauthorized'     => __( 'Invalid API key. Please check your OpenAI API key configuration.', 'rtbcb' ),
        'rate_limited'     => __( 'Rate limit exceeded. Please wait before making more requests.', 'rtbcb' ),
        'api_error'        => sprintf( __( 'API error (HTTP %d). Please try again later.', 'rtbcb' ), $httpStatus ),
        'missing_api_key'  => __( 'No API key configured. Please add your OpenAI API key.', 'rtbcb' ),
        'connection_failed'=> __( 'Unable to connect to OpenAI API. Please check your internet connection.', 'rtbcb' ),
    ];

    return $messages[ $code ] ?? __( 'Unknown API error occurred.', 'rtbcb' );
}


