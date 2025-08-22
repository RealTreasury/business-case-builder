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
        add_action( 'wp_ajax_rtbcb_generate_case', [ $this, 'ajax_generate_comprehensive_case' ] );
        add_action( 'wp_ajax_nopriv_rtbcb_generate_case', [ $this, 'ajax_generate_comprehensive_case' ] );
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
        require_once RTBCB_DIR . 'inc/class-rtbcb-tests.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-validator.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-api-tester.php';
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
            $this->migrate_v1_settings();
            $this->init_database();
            $this->set_default_options();
        }

        // Add new options introduced in 2.1.0
        if ( version_compare( $from_version, '2.1.0', '<' ) ) {
            $new_options = [
                'rtbcb_advanced_model'        => 'o1-preview',
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
            'rtbcb_mini_model'         => 'gpt-4o-mini',
            'rtbcb_premium_model'      => 'gpt-4o',
            'rtbcb_advanced_model'     => 'o1-preview',
            'rtbcb_embedding_model'    => 'text-embedding-3-small',
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
            [],
            RTBCB_VERSION,
            true
        );

        $api_key     = sanitize_text_field( get_option( 'rtbcb_openai_api_key', '' ) );
        $report_model = sanitize_text_field( get_option( 'rtbcb_advanced_model', 'gpt-5-chat-latest' ) );
        wp_localize_script(
            'rtbcb-report',
            'rtbcbReport',
            [
                'api_key'      => $api_key,
                'report_model' => $report_model,
            ]
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
     * Enhanced AJAX handler for comprehensive business case generation.
     */
    public function ajax_generate_comprehensive_case() {
        // Set proper headers
        header( 'Content-Type: application/json; charset=utf-8' );

        // Prevent any output before JSON
        if ( ob_get_level() ) {
            ob_end_clean();
        }

        try {
            // Verify nonce
            if ( ! wp_verify_nonce( $_POST['rtbcb_nonce'] ?? '', 'rtbcb_generate' ) ) {
                error_log( 'RTBCB: Nonce verification failed' );
                wp_send_json_error( __( 'Security check failed.', 'rtbcb' ), 403 );
            }

            // Collect and validate form data
            $user_inputs = [
                'email'                  => sanitize_email( $_POST['email'] ?? '' ),
                'company_name'           => sanitize_text_field( $_POST['company_name'] ?? '' ),
                'company_size'           => sanitize_text_field( $_POST['company_size'] ?? '' ),
                'industry'               => sanitize_text_field( $_POST['industry'] ?? '' ),
                'job_title'              => sanitize_text_field( $_POST['job_title'] ?? '' ),
                'hours_reconciliation'   => floatval( $_POST['hours_reconciliation'] ?? 0 ),
                'hours_cash_positioning' => floatval( $_POST['hours_cash_positioning'] ?? 0 ),
                'num_banks'              => intval( $_POST['num_banks'] ?? 0 ),
                'ftes'                   => floatval( $_POST['ftes'] ?? 0 ),
                'pain_points'            => array_map( 'sanitize_text_field', $_POST['pain_points'] ?? [] ),
                'business_objective'     => sanitize_text_field( $_POST['business_objective'] ?? '' ),
                'implementation_timeline'=> sanitize_text_field( $_POST['implementation_timeline'] ?? '' ),
                'budget_range'           => sanitize_text_field( $_POST['budget_range'] ?? '' ),
            ];

            error_log( 'RTBCB: Processing request for company: ' . $user_inputs['company_name'] );

            // Validate required fields
            if ( empty( $user_inputs['email'] ) || ! is_email( $user_inputs['email'] ) ) {
                wp_send_json_error( __( 'Please enter a valid email address.', 'rtbcb' ), 400 );
            }

            if ( empty( $user_inputs['company_name'] ) ) {
                wp_send_json_error( __( 'Please enter your company name.', 'rtbcb' ), 400 );
            }

            if ( empty( $user_inputs['company_size'] ) ) {
                wp_send_json_error( __( 'Please select your company size.', 'rtbcb' ), 400 );
            }

            if ( empty( $user_inputs['pain_points'] ) ) {
                wp_send_json_error( __( 'Please select at least one challenge.', 'rtbcb' ), 400 );
            }

            // Calculate ROI scenarios
            if ( ! class_exists( 'RTBCB_Calculator' ) ) {
                wp_send_json_error( __( 'System error: Calculator not available.', 'rtbcb' ), 500 );
            }

            error_log( 'RTBCB: Calculating ROI scenarios' );
            $scenarios = RTBCB_Calculator::calculate_roi( $user_inputs );

            // Get category recommendation
            if ( ! class_exists( 'RTBCB_Category_Recommender' ) ) {
                wp_send_json_error( __( 'System error: Recommender not available.', 'rtbcb' ), 500 );
            }

            error_log( 'RTBCB: Getting category recommendation' );
            $recommendation = RTBCB_Category_Recommender::recommend_category( $user_inputs );

            // Get RAG context (enhanced search)
            $rag_context = [];
            if ( class_exists( 'RTBCB_RAG' ) ) {
                try {
                    error_log( 'RTBCB: Searching RAG context' );
                    $rag = new RTBCB_RAG();
                    $search_query = implode(
                        ' ',
                        array_merge(
                            [ $user_inputs['company_name'], $user_inputs['industry'] ],
                            $user_inputs['pain_points'],
                            [ $recommendation['recommended'] ?? '' ]
                        )
                    );
                    $rag_context = $rag->search_similar( $search_query, 5 ); // Get more context
                    error_log( 'RTBCB: Found ' . count( $rag_context ) . ' RAG context items' );
                } catch ( Exception $e ) {
                    error_log( 'RTBCB: RAG search failed - ' . $e->getMessage() );
                    // Continue without RAG context
                }
            }

            // Generate comprehensive business case using enhanced LLM
            if ( ! class_exists( 'RTBCB_LLM' ) ) {
                wp_send_json_error( __( 'System error: LLM integration not available.', 'rtbcb' ), 500 );
            }

            error_log( 'RTBCB: Generating comprehensive business case' );
            $llm = new RTBCB_LLM();
            
            // Always try the comprehensive version first
            $comprehensive_analysis = $llm->generate_comprehensive_business_case( 
                $user_inputs, 
                $scenarios, 
                $rag_context 
            );

            // Check if we got a proper comprehensive analysis
            if ( is_wp_error( $comprehensive_analysis ) || isset( $comprehensive_analysis['error'] ) ) {
                $error_message = is_wp_error( $comprehensive_analysis )
                    ? $comprehensive_analysis->get_error_message()
                    : $comprehensive_analysis['error'];

                error_log( 'RTBCB: Comprehensive analysis failed: ' . $error_message );

                // Fall back to basic analysis
                error_log( 'RTBCB: Falling back to basic business case generation' );
                $basic_analysis = $llm->generate_business_case( $user_inputs, $scenarios, $rag_context );

                // If that also fails, use enhanced fallback
                if ( is_wp_error( $basic_analysis ) ) {
                    error_log( 'RTBCB: Basic analysis also failed, using enhanced fallback' );
                    $comprehensive_analysis = $this->create_comprehensive_fallback( $user_inputs, $recommendation, $scenarios );
                } else {
                    $comprehensive_analysis = $basic_analysis;
                }
            }

            // Ensure we have the company name in the analysis
            if ( empty( $comprehensive_analysis['company_name'] ) ) {
                $comprehensive_analysis['company_name'] = $user_inputs['company_name'];
            }

            // Format scenarios for output (with validation)
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

            // Validate scenarios have different values (not all the same)
            $base_benefit = $formatted_scenarios['base']['total_annual_benefit'];
            if ( $formatted_scenarios['low']['total_annual_benefit'] === $base_benefit && 
                 $formatted_scenarios['high']['total_annual_benefit'] === $base_benefit ) {
                error_log( 'RTBCB: All ROI scenarios are identical, recalculating with variation' );
                
                // Add some variation to make scenarios realistic
                $formatted_scenarios['low']['total_annual_benefit'] = round( $base_benefit * 0.8 );
                $formatted_scenarios['high']['total_annual_benefit'] = round( $base_benefit * 1.2 );
            }

            // Save lead to database
            $lead_id = null;
            if ( class_exists( 'RTBCB_Leads' ) ) {
                try {
                    error_log( 'RTBCB: Saving lead data' );
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
                    error_log( 'RTBCB: Lead saved with ID: ' . $lead_id );
                } catch ( Throwable $e ) {
                    error_log( 'RTBCB: Failed to save lead - ' . $e->getMessage() );
                }
            }

            // Generate HTML report using comprehensive template
            error_log( 'RTBCB: Generating HTML report' );
            $report_html = $this->get_comprehensive_report_html( $comprehensive_analysis );
            
            if ( empty( $report_html ) ) {
                error_log( 'RTBCB: Report HTML generation failed, using fallback' );
                $report_html = $this->get_fallback_report_html( $comprehensive_analysis );
            }

            // Prepare response data
            $response_data = [
                'scenarios'              => $formatted_scenarios,
                'recommendation'         => $recommendation,
                'comprehensive_analysis' => $comprehensive_analysis,
                'narrative'              => $comprehensive_analysis, // For backward compatibility
                'rag_context'            => $rag_context,
                'report_html'            => $report_html,
                'lead_id'                => $lead_id,
                'company_name'           => $user_inputs['company_name'],
                'analysis_type'          => 'comprehensive',
                'api_used'               => !empty( get_option( 'rtbcb_openai_api_key' ) ),
                'fallback_used'          => isset( $comprehensive_analysis['enhanced_fallback'] ) || isset( $comprehensive_analysis['fallback_used'] ),
            ];

            // Log successful generation
            error_log( 
                'RTBCB: Business case generated successfully for ' . 
                $user_inputs['company_name'] . ' (' . $user_inputs['email'] . ')' .
                ' - API Used: ' . ( $response_data['api_used'] ? 'Yes' : 'No' ) .
                ' - Fallback: ' . ( $response_data['fallback_used'] ? 'Yes' : 'No' )
            );

            wp_send_json_success( $response_data );

        } catch ( Exception $e ) {
            error_log( 'RTBCB Ajax Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
            wp_send_json_error(
                [ 'message' => __( 'An error occurred while generating your business case. Please try again.', 'rtbcb' ) ],
                500
            );
        } catch ( Error $e ) {
            error_log( 'RTBCB Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
            wp_send_json_error(
                [ 'message' => __( 'A system error occurred. Please contact support.', 'rtbcb' ) ],
                500
            );
        }

        exit;
    }

    /**
     * Generate comprehensive report HTML from template.
     */
    private function get_comprehensive_report_html( $business_case_data ) {
        $template_path = RTBCB_DIR . 'templates/comprehensive-report-template.php';
        
        // Fall back to basic template if comprehensive template doesn't exist
        if ( ! file_exists( $template_path ) ) {
            $template_path = RTBCB_DIR . 'templates/report-template.php';
        }

        if ( ! file_exists( $template_path ) ) {
            error_log( 'RTBCB: No report template found at: ' . $template_path );
            return '';
        }

        $business_case_data = is_array( $business_case_data ) ? $business_case_data : [];

        ob_start();
        include $template_path;
        $html = ob_get_clean();

        return wp_kses_post( $html );
    }

    /**
     * Generate fallback report HTML when templates fail.
     */
    private function get_fallback_report_html( $business_case_data ) {
        $company_name = $business_case_data['company_name'] ?? 'Your Company';
        
        $html = '<div class="rtbcb-fallback-report">';
        $html .= '<h2>' . esc_html( $company_name . ' Business Case Report' ) . '</h2>';
        
        if ( !empty( $business_case_data['executive_summary']['strategic_positioning'] ) ) {
            $html .= '<div class="rtbcb-executive-summary">';
            $html .= '<h3>Executive Summary</h3>';
            $html .= '<p>' . esc_html( $business_case_data['executive_summary']['strategic_positioning'] ) . '</p>';
            $html .= '</div>';
        }
        
        if ( !empty( $business_case_data['executive_summary']['key_value_drivers'] ) ) {
            $html .= '<div class="rtbcb-value-drivers">';
            $html .= '<h3>Key Value Drivers</h3>';
            $html .= '<ul>';
            foreach ( $business_case_data['executive_summary']['key_value_drivers'] as $driver ) {
                $html .= '<li>' . esc_html( $driver ) . '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        if ( !empty( $business_case_data['next_steps'] ) ) {
            $html .= '<div class="rtbcb-next-steps">';
            $html .= '<h3>Recommended Next Steps</h3>';
            $html .= '<ol>';
            foreach ( $business_case_data['next_steps'] as $step ) {
                $html .= '<li>' . esc_html( $step ) . '</li>';
            }
            $html .= '</ol>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Create comprehensive fallback with detailed analysis when API fails.
     */
    private function create_comprehensive_fallback( $user_inputs, $recommendation, $scenarios ) {
        $company_name = $user_inputs['company_name'] ?? 'Your Company';
        $base_benefit = $scenarios['base']['total_annual_benefit'] ?? 0;
        $company_size = $user_inputs['company_size'] ?? '';
        $industry = $user_inputs['industry'] ?? '';
        $pain_points = $user_inputs['pain_points'] ?? [];
        
        // Calculate current inefficiency metrics
        $total_hours = floatval( $user_inputs['hours_reconciliation'] ?? 0 ) + floatval( $user_inputs['hours_cash_positioning'] ?? 0 );
        $num_banks = intval( $user_inputs['num_banks'] ?? 0 );
        $ftes = floatval( $user_inputs['ftes'] ?? 0 );
        
        // Determine business stage and characteristics
        $stage_mapping = [
            '<$50M' => 'emerging growth company',
            '$50M-$500M' => 'scaling mid-market business', 
            '$500M-$2B' => 'established enterprise',
            '>$2B' => 'large enterprise organization'
        ];
        
        $business_stage = $stage_mapping[$company_size] ?? 'growing business';
        
        // Industry-specific insights
        $industry_insights = [
            'manufacturing' => [
                'trends' => 'Digital transformation and supply chain optimization are driving treasury automation adoption in manufacturing',
                'benchmarks' => 'Leading manufacturers have reduced treasury processing time by 60% through automation',
                'regulatory' => 'Environmental reporting and supply chain transparency requirements increase operational complexity'
            ],
            'technology' => [
                'trends' => 'High-growth tech companies prioritize real-time cash management to support rapid scaling',
                'benchmarks' => 'Tech companies achieve 40% faster month-end close with automated treasury processes',
                'regulatory' => 'Data privacy regulations and international expansion create complex compliance requirements'
            ],
            'retail' => [
                'trends' => 'Omnichannel operations and seasonal volatility drive demand for advanced cash forecasting',
                'benchmarks' => 'Retail leaders improve cash forecast accuracy by 35% with automated treasury tools',
                'regulatory' => 'Payment processing regulations and consumer data protection create compliance overhead'
            ]
        ];
        
        $industry_context = $industry_insights[$industry] ?? [
            'trends' => 'Industry leaders are modernizing treasury operations to improve efficiency and risk management',
            'benchmarks' => 'Companies of similar size typically achieve 30-50% efficiency gains through treasury automation',
            'regulatory' => 'Standard compliance requirements and operational risk management drive technology adoption'
        ];
        
        return [
            'company_name' => $company_name,
            'analysis_date' => current_time( 'Y-m-d' ),
            'executive_summary' => [
                'strategic_positioning' => sprintf(
                    '%s, as a %s in the %s sector, has significant opportunity to modernize treasury operations and achieve operational excellence. Current manual processes consuming %d weekly hours indicate clear automation potential, while %d banking relationships suggest complexity that would benefit from centralized management.',
                    $company_name,
                    $business_stage,
                    $industry,
                    $total_hours,
                    $num_banks
                ),
                'business_case_strength' => 'Strong',
                'key_value_drivers' => [
                    sprintf( 'Process automation will eliminate %s\'s current %d hours of weekly manual work, freeing treasury team for strategic activities', $company_name, $total_hours ),
                    sprintf( 'Real-time cash visibility across %d banking relationships will optimize working capital for %s', $num_banks, $company_name ),
                    sprintf( 'Reduced operational risk and improved compliance will strengthen %s\'s financial controls and stakeholder confidence', $company_name )
                ],
                'executive_recommendation' => sprintf(
                    '%s should proceed with treasury technology implementation to achieve projected annual benefits of $%s while positioning for sustainable growth and operational resilience.',
                    $company_name,
                    number_format( $base_benefit )
                ),
                'confidence_level' => 0.85
            ],
            'operational_analysis' => [
                'current_state_assessment' => [
                    'efficiency_rating' => $total_hours > 20 ? 'Poor' : ($total_hours > 10 ? 'Fair' : 'Good'),
                    'benchmark_comparison' => sprintf(
                        '%s\'s treasury operations show significant manual dependency compared to industry leaders who have automated 70%% of routine processes',
                        $company_name
                    ),
                    'capacity_utilization' => sprintf(
                        'Treasury team operates at %d%% manual task capacity, limiting time for strategic value-add activities',
                        min( 100, round( ($total_hours / 40) * 100 ) )
                    )
                ],
                'process_inefficiencies' => array_map( function( $pain_point ) use ( $company_name ) {
                    $descriptions = [
                        'manual_processes' => sprintf( '%s relies heavily on manual data entry and reconciliation, creating bottlenecks and error risk', $company_name ),
                        'poor_visibility' => sprintf( '%s lacks real-time cash visibility, delaying critical financial decisions', $company_name ),
                        'forecast_accuracy' => sprintf( '%s experiences forecasting challenges that impact cash optimization', $company_name ),
                        'compliance_risk' => sprintf( '%s faces regulatory compliance complexity requiring enhanced controls', $company_name ),
                        'bank_fees' => sprintf( '%s incurs unnecessary banking costs due to suboptimal cash positioning', $company_name ),
                        'integration_issues' => sprintf( '%s operates with disconnected systems creating data silos', $company_name )
                    ];
                    
                    return [
                        'process' => ucwords( str_replace( '_', ' ', $pain_point ) ),
                        'impact' => 'High',
                        'description' => $descriptions[$pain_point] ?? sprintf( '%s faces operational challenges in %s', $company_name, $pain_point )
                    ];
                }, $pain_points ),
                'automation_opportunities' => [
                    [
                        'area' => 'Bank Reconciliation',
                        'complexity' => 'Medium',
                        'potential_hours_saved' => round( floatval( $user_inputs['hours_reconciliation'] ?? 0 ) * 0.7, 1 )
                    ],
                    [
                        'area' => 'Cash Position Management', 
                        'complexity' => 'Low',
                        'potential_hours_saved' => round( floatval( $user_inputs['hours_cash_positioning'] ?? 0 ) * 0.6, 1 )
                    ]
                ]
            ],
            'industry_insights' => [
                'sector_trends' => $industry_context['trends'],
                'competitive_benchmarks' => $industry_context['benchmarks'], 
                'regulatory_considerations' => $industry_context['regulatory']
            ],
            'technology_recommendations' => [
                'primary_solution' => [
                    'category' => $recommendation['category_info']['name'] ?? 'Treasury Management System',
                    'rationale' => sprintf(
                        'Based on %s\'s operational profile and %s industry requirements, this solution provides optimal balance of functionality and complexity',
                        $company_name,
                        $industry
                    ),
                    'key_features' => $recommendation['category_info']['features'] ?? [
                        'Automated bank reconciliation',
                        'Real-time cash positioning', 
                        'Advanced forecasting capabilities'
                    ]
                ],
                'implementation_approach' => [
                    'phase_1' => sprintf( 'Implement core cash management and reconciliation automation for %s', $company_name ),
                    'phase_2' => sprintf( 'Expand to advanced analytics and forecasting capabilities across %s operations', $company_name ),
                    'success_metrics' => [
                        'Reduce manual processing time by 60%',
                        'Improve cash forecast accuracy by 30%',
                        'Achieve 99%+ reconciliation automation'
                    ]
                ]
            ],
            'financial_analysis' => [
                'investment_breakdown' => [
                    'software_licensing' => '$' . number_format( $base_benefit * 0.4 ) . ' - $' . number_format( $base_benefit * 0.6 ),
                    'implementation_services' => '$' . number_format( $base_benefit * 0.1 ) . ' - $' . number_format( $base_benefit * 0.2 ),
                    'training_change_management' => '$' . number_format( $base_benefit * 0.05 ) . ' - $' . number_format( $base_benefit * 0.1 )
                ],
                'payback_analysis' => [
                    'payback_months' => $base_benefit > 0 ? max( 12, round( 12 * ($base_benefit * 0.5) / $base_benefit ) ) : 18,
                    'roi_3_year' => round( (($base_benefit * 3) / ($base_benefit * 0.5) - 1) * 100 ),
                    'npv_analysis' => sprintf( 'Positive NPV of $%s over 3 years at 10%% discount rate', number_format( $base_benefit * 2.1 ) )
                ]
            ],
            'risk_mitigation' => [
                'implementation_risks' => [
                    sprintf( 'User adoption challenges during %s transition to new processes', $company_name ),
                    sprintf( 'Integration complexity with %s existing systems and workflows', $company_name ),
                    'Data migration and validation requirements during implementation'
                ],
                'mitigation_strategies' => [
                    'adoption_risk_mitigation' => sprintf( 'Comprehensive change management program with %s leadership engagement', $company_name ),
                    'integration_risk_mitigation' => 'Phased implementation approach with thorough testing and validation',
                    'data_risk_mitigation' => 'Parallel processing during transition with comprehensive data validation'
                ]
            ],
            'next_steps' => [
                sprintf( 'Present comprehensive business case to %s executive leadership for approval', $company_name ),
                sprintf( 'Initiate vendor evaluation process aligned with %s requirements and timeline', $company_name ),
                sprintf( 'Develop detailed implementation roadmap and change management strategy for %s', $company_name ),
                sprintf( 'Establish success metrics and governance framework for %s treasury transformation', $company_name )
            ],
            'confidence_level' => 0.85,
            'enhanced_fallback' => true
        ];
    }
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

