<?php
/**
 * Plugin Name: Real Treasury - Business Case Builder (Enhanced Pro)
 * Description: Professional-grade ROI calculator and comprehensive business case generator for treasury technology with advanced analysis and consultant-style reports.
 * Version: 2.1.8
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

define( 'RTBCB_VERSION', '2.1.8' );
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
     * Request start time.
     *
     * @var float
     */
    private $request_start_time = 0.0;

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

		// AJAX handlers - Use the enhanced version
		add_action( 'wp_ajax_rtbcb_generate_case', [ $this, 'ajax_generate_comprehensive_case_enhanced' ] );
		add_action( 'wp_ajax_nopriv_rtbcb_generate_case', [ $this, 'ajax_generate_comprehensive_case_enhanced' ] );
		
		// Job status handlers
		add_action( 'wp_ajax_rtbcb_job_status', [ 'RTBCB_Ajax', 'get_job_status' ] );
		add_action( 'wp_ajax_nopriv_rtbcb_job_status', [ 'RTBCB_Ajax', 'get_job_status' ] );
		
		// OpenAI proxy handlers
		add_action( 'wp_ajax_rtbcb_openai_responses', 'rtbcb_proxy_openai_responses' );
		add_action( 'wp_ajax_nopriv_rtbcb_openai_responses', 'rtbcb_proxy_openai_responses' );
		
		// Debug handlers
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
		add_action( 'wp_ajax_rtbcb_debug_report', [ $this, 'debug_report_generation' ] );
		add_action( 'wp_ajax_nopriv_rtbcb_debug_report', [ $this, 'debug_report_generation' ] );
		}

    /**
     * Include required files.
     *
     * @return void
     */
    private function includes() {
        // Core classes
        require_once RTBCB_DIR . 'inc/helpers.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-settings.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-calculator.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-router.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-llm.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-rag.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-leads.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-api-log.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-db.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-category-recommender.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-tests.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-maturity-model.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-validator.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-api-tester.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-workflow-tracker.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-enhanced-calculator.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-intelligent-recommender.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-background-job.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-ajax.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-logger.php';

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

// Schedule background job cleanup
if ( ! wp_next_scheduled( 'rtbcb_cleanup_jobs' ) ) {
wp_schedule_event( time(), 'hourly', 'rtbcb_cleanup_jobs' );
}

add_action( 'rtbcb_cleanup_jobs', [ 'RTBCB_Background_Job', 'cleanup' ] );

        // Schedule lead metrics refresh
        if ( ! wp_next_scheduled( 'rtbcb_refresh_lead_metrics' ) ) {
            wp_schedule_event( time(), 'hourly', 'rtbcb_refresh_lead_metrics' );
        }

        add_action( 'rtbcb_refresh_lead_metrics', [ 'RTBCB_Leads', 'update_cached_statistics' ] );
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
                'rtbcb_advanced_model'        => 'gpt-5-mini',
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
     * Enqueue frontend assets with enhanced report support.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
	    if ( ! $this->should_load_assets() ) {
	        return;
	    }

	    // Base Styles
	    wp_enqueue_style(
	        'rtbcb-style',
	        RTBCB_URL . 'public/css/rtbcb.css',
	        [],
	        RTBCB_VERSION
	    );

		// Enhanced Report Styles
		if ( $this->should_use_comprehensive_template() ) {
			$enhanced_css = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'enhanced-report.css' : 'enhanced-report.min.css';
			wp_enqueue_style(
				'rtbcb-enhanced-report',
				RTBCB_URL . 'public/css/' . $enhanced_css,
				[ 'rtbcb-style' ],
				RTBCB_VERSION
			);
		}

	    // Chart.js for report visualizations
	    wp_enqueue_script(
	        'chartjs',
	        RTBCB_URL . 'public/js/chart.min.js',
	        [],
	        '3.9.1',
	        true
	    );

	    // DOMPurify for sanitization with CDN fallback
	    $dompurify_cdn   = 'https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.2/purify.min.js';
	    $dompurify_local = RTBCB_URL . 'public/js/dompurify.min.js';
	    wp_enqueue_script(
	        'dompurify',
	        $dompurify_cdn,
	        [],
	        '3.0.2',
	        true
	    );
	    wp_add_inline_script(
	        'dompurify',
	        sprintf(
	            'window.DOMPurify||function(){var s=document.createElement("script");s.src="%s";document.head.appendChild(s);}();',
	            esc_url( $dompurify_local )
	        )
	    );

	    // Wizard script (loaded early for modal functions)
	    $wizard_file = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'rtbcb-wizard.js' : 'rtbcb-wizard.min.js';
	    wp_enqueue_script(
	        'rtbcb-wizard',
	        RTBCB_URL . 'public/js/' . $wizard_file,
	        [ 'jquery' ],
	        RTBCB_VERSION,
	        false // Load in header
	    );

		// Main report functionality
		$report_file = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'rtbcb-report.js' : 'rtbcb-report.min.js';
		wp_enqueue_script(
			'rtbcb-report',
			RTBCB_URL . 'public/js/' . $report_file,
			[ 'chartjs', 'dompurify' ],
			RTBCB_VERSION,
			true
		);

		// Main plugin script
		$main_script = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'rtbcb.js' : 'rtbcb.min.js';
		wp_enqueue_script(
			'rtbcb-script',
			RTBCB_URL . 'public/js/' . $main_script,
			[ 'jquery', 'rtbcb-wizard', 'rtbcb-report' ],
			RTBCB_VERSION,
			true
		);
	    // Localize scripts with configuration
	    $this->localize_scripts();
	}

	/**
	 * Localize scripts with proper configuration data.
	 */
	private function localize_scripts() {
	    // Wizard configuration
	    wp_localize_script(
	        'rtbcb-wizard',
	        'rtbcbAjax',
	        [
	            'ajax_url'    => admin_url( 'admin-ajax.php' ),
	            'nonce'       => wp_create_nonce( 'rtbcb_generate' ),
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
	                'email_confirmation'      => __( 'Your report will arrive by email shortly.', 'rtbcb' ),
	            ],
	            'settings'    => [
	                'pdf_enabled'            => get_option( 'rtbcb_pdf_enabled', true ),
	                'comprehensive_analysis' => get_option( 'rtbcb_comprehensive_analysis', true ),
	                'professional_reports'   => get_option( 'rtbcb_professional_reports', true ),
	            ],
	        ]
	    );

	    // Report configuration
	    $config             = rtbcb_get_gpt5_config();
	    $model_capabilities = rtbcb_get_model_capabilities();

	    wp_localize_script(
	        'rtbcb-report',
	        'rtbcbReport',
	        [
	            'report_model'       => get_option( 'rtbcb_advanced_model', 'gpt-5-mini' ),
	            'max_output_tokens'  => intval( $config['max_output_tokens'] ),
	            'min_output_tokens'  => intval( $config['min_output_tokens'] ),
	            'model_capabilities' => $model_capabilities,
	            'ajax_url'           => admin_url( 'admin-ajax.php' ),
	            'template_url'       => RTBCB_URL . 'public/templates/report-template.html',
	            'timeout_ms'         => rtbcb_get_api_timeout() * 1000,
	            'nonce'              => wp_create_nonce( 'rtbcb_generate' ),
	            'strings'            => [
	                'exportPDF'      => __( 'Export as PDF', 'rtbcb' ),
	                'printReport'    => __( 'Print Report', 'rtbcb' ),
	                'expandSection'  => __( 'Expand Section', 'rtbcb' ),
	                'collapseSection' => __( 'Collapse Section', 'rtbcb' ),
	            ],
	        ]
	    );
	}

	/**
	 * Check if assets should be loaded on current page.
	 *
	 * @return bool
	 */
	private function should_load_assets() {
		// Always load on admin pages for this plugin.
		if ( is_admin() ) {
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			if ( false !== strpos( $page, 'rtbcb' ) ) {
				return true;
			}
		}

		// Load when shortcode is present on the page.
		if ( is_singular() ) {
			$post = get_post();
			if ( $post && has_shortcode( $post->post_content, 'rt_business_case_builder' ) ) {
				return true;
			}
		}

		// Load on specific public pages.
		$slugs = (array) apply_filters(
			'rtbcb_asset_page_slugs',
			[ 'business-case', 'business-case-report', 'business-case-builder' ]
		);
		if ( is_page( $slugs ) ) {
			return true;
		}

		return false;
	}

               /**
                * Determine if comprehensive template should be used.
                *
                * @return bool
                */
               private function should_use_comprehensive_template() {
$comprehensive_enabled = get_option( 'rtbcb_comprehensive_analysis', true );

$template_path  = RTBCB_DIR . 'templates/comprehensive-report-template.php';
$template_exists = file_exists( $template_path );

$css_path   = RTBCB_DIR . 'public/css/enhanced-report.css';
$css_exists = file_exists( $css_path );

$use_comprehensive = $comprehensive_enabled && $template_exists && $css_exists;
rtbcb_log_api_debug(
'Comprehensive template check',
[
'enabled'          => $comprehensive_enabled,
'template_exists'  => $template_exists,
'css_exists'       => $css_exists,
'template_path'    => $template_path,
'css_path'         => $css_path,
'use_comprehensive' => $use_comprehensive,
]
);

return $use_comprehensive;
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
		 * Enhanced AJAX handler for comprehensive case generation.
		 */
		public function ajax_generate_comprehensive_case_enhanced() {
			// Security and setup.
			if ( ! check_ajax_referer( 'rtbcb_generate', 'rtbcb_nonce', false ) ) {
				wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
				return;
			}

			// Setup environment.
			rtbcb_setup_ajax_logging();
			rtbcb_increase_memory_limit();

			$timeout = absint( rtbcb_get_api_timeout() );
			if ( ! ini_get( 'safe_mode' ) && $timeout > 0 ) {
				set_time_limit( $timeout );
			}

			// Collect and validate user inputs.
			$user_inputs = $this->collect_and_validate_inputs();
			if ( is_wp_error( $user_inputs ) ) {
				wp_send_json_error( [ 'message' => $user_inputs->get_error_message() ], 400 );
				return;
			}

                        // Handle simple inputs synchronously; queue complex cases for background processing.
			if ( ! rtbcb_is_simple_case( $user_inputs ) ) {
				$job_id = RTBCB_Background_Job::enqueue( $user_inputs );
				wp_send_json_success( [ 'job_id' => $job_id ] );
				return;
			}

			try {
				// Calculate ROI scenarios.
				if ( ! class_exists( 'RTBCB_Calculator' ) ) {
					wp_send_json_error( [ 'message' => __( 'System error: Calculator not available.', 'rtbcb' ) ], 500 );
					return;
				}

				$scenarios = RTBCB_Calculator::calculate_roi( $user_inputs );

				// Get category recommendation.
				if ( ! class_exists( 'RTBCB_Category_Recommender' ) ) {
					wp_send_json_error( [ 'message' => __( 'System error: Recommender not available.', 'rtbcb' ) ], 500 );
					return;
				}

				$recommendation = RTBCB_Category_Recommender::recommend_category( $user_inputs );

				// Get RAG context if available.
				$rag_context = $this->get_rag_context( $user_inputs, $recommendation );

				// Generate business case analysis.
				$comprehensive_analysis = $this->generate_business_analysis( $user_inputs, $scenarios, $rag_context );

				if ( is_wp_error( $comprehensive_analysis ) ) {
					wp_send_json_error( [ 'message' => $comprehensive_analysis->get_error_message() ], 500 );
					return;
				}

				// Merge all data for report generation.
				$report_data = array_merge(
					$comprehensive_analysis,
					[
						'company_name'    => $user_inputs['company_name'],
						'scenarios'       => $scenarios,
						'recommendation'  => $recommendation,
						'rag_context'     => $rag_context,
						'processing_time' => microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'],
					]
				);

				// Generate HTML report using our fixed method.
				$report_html = $this->get_comprehensive_report_html( $report_data );

				if ( is_wp_error( $report_html ) ) {
					wp_send_json_error( [ 'message' => $report_html->get_error_message() ], 500 );
					return;
				}

				if ( empty( $report_html ) ) {
					wp_send_json_error( [ 'message' => __( 'Failed to generate report HTML.', 'rtbcb' ) ], 500 );
					return;
				}

				// Save lead data.
				$lead_id = $this->save_lead_data( $user_inputs, $scenarios, $recommendation, $report_html );

				// Format response data.
				$formatted_scenarios = $this->format_scenarios_for_response( $scenarios );

				$response_data = [
					'scenarios'              => $formatted_scenarios,
					'recommendation'         => $recommendation,
					'comprehensive_analysis' => $comprehensive_analysis,
					'report_html'            => $report_html,
					'lead_id'                => $lead_id,
					'company_name'           => $user_inputs['company_name'],
                                        'analysis_type'          => rtbcb_get_analysis_type(),
					'memory_info'            => rtbcb_get_memory_status(),
				];

				wp_send_json_success( $response_data );
			} catch ( Exception $e ) {
				rtbcb_log_error( 'Ajax exception', $e->getMessage() );
				wp_send_json_error( [ 'message' => __( 'An error occurred while generating your business case.', 'rtbcb' ) ], 500 );
			} catch ( Error $e ) {
				rtbcb_log_error( 'Ajax fatal error', $e->getMessage() );
				wp_send_json_error( [ 'message' => __( 'A system error occurred. Please contact support.', 'rtbcb' ) ], 500 );
			}
		}
	
	/**
	 * Collect and validate user inputs from POST data.
	 */
	private function collect_and_validate_inputs() {
	// Get company data
	$company      = rtbcb_get_current_company();
	$company_name = sanitize_text_field( wp_unslash( $_POST['company_name'] ?? '' ) );
	
	if ( empty( $company_name ) && ! empty( $company['name'] ) ) {
	$company_name = $company['name'];
	}
	
        $raw_hours_reconciliation   = wp_unslash( $_POST['hours_reconciliation'] ?? '' );
        $raw_hours_cash_positioning = wp_unslash( $_POST['hours_cash_positioning'] ?? '' );
        $raw_num_banks              = wp_unslash( $_POST['num_banks'] ?? '' );
        $raw_ftes                   = wp_unslash( $_POST['ftes'] ?? '' );

        $user_inputs = [
        'email'                  => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
        'company_name'           => $company_name,
        'company_size'           => sanitize_text_field( wp_unslash( $_POST['company_size'] ?? $company['size'] ?? '' ) ),
        'industry'               => sanitize_text_field( wp_unslash( $_POST['industry'] ?? $company['industry'] ?? '' ) ),
        'hours_reconciliation'   => '' === $raw_hours_reconciliation ? 0 : floatval( $raw_hours_reconciliation ),
        'hours_cash_positioning' => max( 0, floatval( $raw_hours_cash_positioning ) ),
        'num_banks'              => max( 0, intval( $raw_num_banks ) ),
        'ftes'                   => max( 0, floatval( $raw_ftes ) ),
        'pain_points'            => array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['pain_points'] ?? [] ) ),
        'business_objective'     => sanitize_text_field( wp_unslash( $_POST['business_objective'] ?? '' ) ),
        'implementation_timeline'=> sanitize_text_field( wp_unslash( $_POST['implementation_timeline'] ?? '' ) ),
        'budget_range'           => sanitize_text_field( wp_unslash( $_POST['budget_range'] ?? '' ) ),
        ];
	
	// Validate required fields
	$validation_errors = [];
	
	if ( empty( $user_inputs['email'] ) || ! is_email( $user_inputs['email'] ) ) {
	$validation_errors[] = __( 'Please enter a valid email address.', 'rtbcb' );
	}
	
	if ( empty( $user_inputs['company_name'] ) ) {
	$validation_errors[] = __( 'Please enter your company name.', 'rtbcb' );
	}
	
        if ( '' !== $raw_hours_reconciliation && ! is_numeric( $raw_hours_reconciliation ) ) {
                $validation_errors[] = __( 'Please enter valid reconciliation hours.', 'rtbcb' );
        } elseif ( $user_inputs['hours_reconciliation'] < 0 ) {
                $validation_errors[] = __( 'Please enter valid reconciliation hours.', 'rtbcb' );
        }

        if ( '' !== $raw_hours_cash_positioning && ! is_numeric( $raw_hours_cash_positioning ) ) {
                $validation_errors[] = __( 'Please enter valid cash positioning hours.', 'rtbcb' );
        }

        if ( '' !== $raw_num_banks && ! is_numeric( $raw_num_banks ) ) {
                $validation_errors[] = __( 'Please enter a valid number of banks.', 'rtbcb' );
        }

        if ( '' !== $raw_ftes && ! is_numeric( $raw_ftes ) ) {
                $validation_errors[] = __( 'Please enter valid FTEs.', 'rtbcb' );
        }

	
	if ( ! empty( $validation_errors ) ) {
	return new WP_Error( 'validation_failed', implode( ' ', $validation_errors ) );
	}
	
	return $user_inputs;
	}
	
	/**
	 * Get RAG context for enhanced analysis.
	 */
	private function get_rag_context( $user_inputs, $recommendation ) {
	if ( ! class_exists( 'RTBCB_RAG' ) ) {
	return [];
	}
	
	try {
        $rag          = new RTBCB_RAG();
        $search_query = implode(
            ' ',
            array_merge(
                [ $user_inputs['company_name'], $user_inputs['industry'] ],
                $user_inputs['pain_points'],
                [ $recommendation['recommended'] ?? '' ]
            )
        );

        $results   = $rag->search_similar( $search_query, 3 );
        $sanitized = [];

        foreach ( $results as $result ) {
            $text = '';

            if ( is_array( $result ) && isset( $result['metadata'] ) ) {
                $metadata = $result['metadata'];
                $text     = is_array( $metadata ) ? wp_json_encode( $metadata ) : (string) $metadata;
            } elseif ( is_scalar( $result ) ) {
                $text = (string) $result;
            } else {
                $text = wp_json_encode( $result );
            }

            $text        = sanitize_text_field( (string) $text );
            $sanitized[] = mb_substr( $text, 0, 1000 );
        }

        return $sanitized;
	} catch ( Exception $e ) {
	rtbcb_log_error( 'RAG search failed', $e->getMessage() );
	return [];
	}
	}
	
	/**
	 * Generate comprehensive business analysis using LLM.
	 */
        private function generate_business_analysis( $user_inputs, $scenarios, $rag_context ) {
            $start_time = microtime( true );
            $timeout    = absint( rtbcb_get_api_timeout() );

            $time_remaining = static function() use ( $start_time, $timeout ) {
                return $timeout - ( microtime( true ) - $start_time );
            };

            if ( ! class_exists( 'RTBCB_LLM' ) ) {
                return new WP_Error( 'llm_unavailable', __( 'AI analysis service unavailable.', 'rtbcb' ) );
            }

            if ( ! rtbcb_has_openai_api_key() ) {
                // Return fallback analysis instead of failing.
                return $this->generate_fallback_analysis( $user_inputs, $scenarios );
            }

            // Skip RAG lookup if time is nearly exhausted.
            if ( $time_remaining() < 10 ) {
                $rag_context = [];
            }

            // If there's insufficient time for enhanced analysis, return fallback early.
            if ( $time_remaining() < 5 ) {
                return $this->generate_fallback_analysis( $user_inputs, $scenarios );
            }

            try {
                $llm    = new RTBCB_LLM();
                $result = $llm->generate_comprehensive_business_case( $user_inputs, $scenarios, $rag_context );

                if ( is_wp_error( $result ) ) {
                    // Fall back to structured analysis.
                    return $this->generate_fallback_analysis( $user_inputs, $scenarios );
                }

                // If time is running out, return partial results without further processing.
                if ( $time_remaining() < 2 ) {
                    return [
                        'executive_summary' => $result['executive_summary'] ?? '',
                        'partial'          => true,
                    ];
                }

                $required_keys = [ 'executive_summary', 'financial_analysis', 'industry_analysis', 'implementation_roadmap', 'risk_mitigation', 'next_steps' ];
                $missing_keys  = array_diff( $required_keys, array_keys( $result ) );

                if ( ! empty( $missing_keys ) ) {
                    rtbcb_log_error( 'LLM missing required sections', [ 'missing' => $missing_keys ] );
                    return $this->generate_fallback_analysis( $user_inputs, $scenarios );
                }

                return $result;
            } catch ( Exception $e ) {
                rtbcb_log_error( 'LLM analysis failed', $e->getMessage() );
                return $this->generate_fallback_analysis( $user_inputs, $scenarios );
            }
        }
	
	/**
	 * Generate fallback analysis when LLM is unavailable.
	 */
	private function generate_fallback_analysis( $user_inputs, $scenarios ) {
	$company_name = $user_inputs['company_name'];
	$base_roi     = $scenarios['base']['total_annual_benefit'] ?? 0;
	
	return [
	'executive_summary' => sprintf(
	__( '%s has significant opportunities to improve treasury operations through technology automation. Based on current processes, implementing a modern treasury management system could deliver substantial ROI while reducing operational risk.', 'rtbcb' ),
	$company_name
	),
	'narrative'         => sprintf(
	__( 'Our analysis of %s treasury operations reveals opportunities for process automation and efficiency gains. Key areas for improvement include cash management, bank reconciliation, and reporting processes.', 'rtbcb' ),
	$company_name
	),
	'key_benefits'      => [
	__( 'Automated cash positioning and forecasting', 'rtbcb' ),
	__( 'Streamlined bank reconciliation processes', 'rtbcb' ),
	__( 'Enhanced regulatory compliance and reporting', 'rtbcb' ),
	__( 'Improved operational risk management', 'rtbcb' ),
	],
	'risks'             => [
	__( 'Implementation complexity and timeline risk', 'rtbcb' ),
	__( 'User adoption and change management challenges', 'rtbcb' ),
	__( 'Integration complexity with existing systems', 'rtbcb' ),
	],
        'next_actions'      => [
        __( 'Secure executive sponsorship and project funding', 'rtbcb' ),
        __( 'Conduct detailed requirements analysis', 'rtbcb' ),
        __( 'Evaluate treasury technology vendors', 'rtbcb' ),
        __( 'Develop implementation roadmap and timeline', 'rtbcb' ),
        ],
       'company_name'      => $company_name,
       'base_roi'          => $base_roi,
        'confidence'        => 0.75,
        'enhanced_fallback' => true,
        ];
	}
	
	/**
	 * Save lead data to database.
	 */
	private function save_lead_data( $user_inputs, $scenarios, $recommendation, $report_html ) {
	if ( ! class_exists( 'RTBCB_Leads' ) ) {
	return null;
	}
	
	try {
	$lead_data = [
	'email'                  => $user_inputs['email'],
	'company_name'           => $user_inputs['company_name'],
	'company_size'           => $user_inputs['company_size'],
	'industry'               => $user_inputs['industry'],
	'hours_reconciliation'   => $user_inputs['hours_reconciliation'],
	'hours_cash_positioning' => $user_inputs['hours_cash_positioning'],
	'num_banks'              => $user_inputs['num_banks'],
	'ftes'                   => $user_inputs['ftes'],
	'pain_points'            => $user_inputs['pain_points'],
	'recommended_category'   => $recommendation['recommended'] ?? '',
	'roi_low'                => $scenarios['conservative']['total_annual_benefit'] ?? 0,
	'roi_base'               => $scenarios['base']['total_annual_benefit'] ?? 0,
	'roi_high'               => $scenarios['optimistic']['total_annual_benefit'] ?? 0,
	'report_html'            => $report_html,
	];
	
	return RTBCB_Leads::save_lead( $lead_data );
	} catch ( Exception $e ) {
	rtbcb_log_error( 'Failed to save lead', $e->getMessage() );
	return null;
	}
	}
	
	/**
	 * Format scenarios for JSON response.
	 */
	private function format_scenarios_for_response( $scenarios ) {
		$map       = [
			'low'  => 'conservative',
			'base' => 'base',
			'high' => 'optimistic',
		];
		$formatted = [];

		foreach ( $map as $key => $source ) {
			$scenario    = $scenarios[ $source ] ?? [];
			$assumptions = $scenario['assumptions'] ?? [];

			$formatted[ $key ] = [
				'total_annual_benefit' => floatval( $scenario['total_annual_benefit'] ?? 0 ),
				'labor_savings'        => floatval( $scenario['labor_savings'] ?? 0 ),
				'fee_savings'          => floatval( $scenario['fee_savings'] ?? 0 ),
				'error_reduction'      => floatval( $scenario['error_reduction'] ?? 0 ),
				'roi_percentage'       => floatval( $scenario['roi_percentage'] ?? 0 ),
				'assumptions'          => [
					'name'                  => $assumptions['name'] ?? '',
					'efficiency_improvement' => floatval( $assumptions['efficiency_improvement'] ?? 0 ),
					'error_reduction'       => floatval( $assumptions['error_reduction'] ?? 0 ),
					'fee_reduction'         => floatval( $assumptions['fee_reduction'] ?? 0 ),
					'industry_benchmark'    => floatval( $assumptions['industry_benchmark'] ?? 0 ),
				],
			];
		}

		return $formatted;
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
	$timeout = absint( rtbcb_get_api_timeout() );
	if ( ! ini_get( 'safe_mode' ) && $timeout > 0 ) {
		set_time_limit( $timeout );
	}

        try {
            RTBCB_Ajax::generate_comprehensive_case();
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
    public function ajax_generate_comprehensive_case_legacy() {
        $request_start   = microtime( true );
        $request_payload = rtbcb_recursive_sanitize_text_field( wp_unslash( $_POST ) );
        register_shutdown_function( [ 'RTBCB_Logger', 'log_shutdown' ], $request_start, $request_payload );

        rtbcb_setup_ajax_logging();

        // STEP 1: Increase memory limit and log initial state
        rtbcb_increase_memory_limit();
        rtbcb_log_memory_usage( 'start' );

        // STEP 2: Set longer execution time
        $timeout    = absint( rtbcb_get_api_timeout() );
        $start_time = time();

        if ( ! ini_get( 'safe_mode' ) ) {
            if ( $timeout <= 0 ) {
                wp_send_json_error(
                    [ 'message' => __( 'Request timed out; please retry.', 'rtbcb' ) ],
                    504
                );
                return;
            }
            set_time_limit( $timeout );
        }

        // Clear any buffered output before sending JSON responses.
        $buffer_content = ob_get_level() ? ob_get_clean() : '';
        if ( '' !== $buffer_content ) {
            rtbcb_log_api_debug( 'Output buffer not empty before JSON response', $buffer_content );
        }

        try {
            // Verify nonce
            if ( ! check_ajax_referer( 'rtbcb_generate', 'rtbcb_nonce', false ) ) {
                rtbcb_log_error( 'Nonce verification failed', $_POST );
                wp_send_json_error( __( 'Security check failed.', 'rtbcb' ), 403 );
                return;
            }

            $company_name = sanitize_text_field( wp_unslash( $_POST['company_name'] ?? '' ) );
            $company_size = sanitize_text_field( wp_unslash( $_POST['company_size'] ?? '' ) );
            $industry     = sanitize_text_field( wp_unslash( $_POST['industry'] ?? '' ) );

            $company = rtbcb_get_current_company();
            if ( empty( $company ) ) {
                if ( $company_name && $company_size && $industry ) {
                    $company = [
                        'name'     => $company_name,
                        'size'     => $company_size,
                        'industry' => $industry,
                    ];
                    update_option( 'rtbcb_current_company', $company );
                } else {
                    if ( empty( $company_name ) ) {
                        wp_send_json_error( __( 'Please enter your company name.', 'rtbcb' ), 400 );
                        return;
                    }

                    if ( empty( $company_size ) ) {
                        wp_send_json_error( __( 'Please select your company size.', 'rtbcb' ), 400 );
                        return;
                    }

                    if ( empty( $industry ) ) {
                        wp_send_json_error( __( 'Please select your industry.', 'rtbcb' ), 400 );
                        return;
                    }
                }
            }

            rtbcb_log_memory_usage( 'after_nonce_verification' );

            // Collect and validate form data
            $hours_reconciliation_raw   = isset( $_POST['hours_reconciliation'] ) ? wp_unslash( $_POST['hours_reconciliation'] ) : null;
            $hours_cash_positioning_raw = isset( $_POST['hours_cash_positioning'] ) ? wp_unslash( $_POST['hours_cash_positioning'] ) : null;
            $num_banks_raw              = isset( $_POST['num_banks'] ) ? wp_unslash( $_POST['num_banks'] ) : null;
            $ftes_raw                   = isset( $_POST['ftes'] ) ? wp_unslash( $_POST['ftes'] ) : null;

            if ( ! is_numeric( $hours_reconciliation_raw ) ) {
                wp_send_json_error( __( 'Please enter your weekly reconciliation hours.', 'rtbcb' ), 400 );
                return;
            }
            if ( ! is_numeric( $hours_cash_positioning_raw ) ) {
                wp_send_json_error( __( 'Please enter your weekly cash positioning hours.', 'rtbcb' ), 400 );
                return;
            }
            if ( ! is_numeric( $num_banks_raw ) ) {
                wp_send_json_error( __( 'Please enter the number of banking relationships.', 'rtbcb' ), 400 );
                return;
            }
            if ( ! is_numeric( $ftes_raw ) ) {
                wp_send_json_error( __( 'Please enter your treasury team size.', 'rtbcb' ), 400 );
                return;
            }

            $user_inputs = [
                'email'                  => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
                'company_name'           => $company_name,
                'company_size'           => $company_size,
                'industry'               => $industry,
                'job_title'              => sanitize_text_field( wp_unslash( $_POST['job_title'] ?? '' ) ),
                'hours_reconciliation'   => floatval( $hours_reconciliation_raw ),
                'hours_cash_positioning' => floatval( $hours_cash_positioning_raw ),
                'num_banks'              => intval( $num_banks_raw ),
                'ftes'                   => floatval( $ftes_raw ),
                'pain_points'            => array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['pain_points'] ?? [] ) ),
                'business_objective'     => sanitize_text_field( wp_unslash( $_POST['business_objective'] ?? '' ) ),
                'implementation_timeline'=> sanitize_text_field( wp_unslash( $_POST['implementation_timeline'] ?? '' ) ),
                'budget_range'           => sanitize_text_field( wp_unslash( $_POST['budget_range'] ?? '' ) ),
            ];

            rtbcb_log_api_debug( 'Collected user inputs', $user_inputs );

            rtbcb_log_api_debug( 'Validating user inputs' );

            // Validate required fields
            if ( empty( $user_inputs['email'] ) || ! is_email( $user_inputs['email'] ) ) {
                rtbcb_log_error( 'Invalid email address', $user_inputs );
                wp_send_json_error( __( 'Please enter a valid email address.', 'rtbcb' ), 400 );
                return;
            }

            if ( empty( $user_inputs['company_name'] ) ) {
                rtbcb_log_error( 'Missing company name', $user_inputs );
                wp_send_json_error( __( 'Please enter your company name.', 'rtbcb' ), 400 );
                return;
            }

            if ( empty( $user_inputs['company_size'] ) ) {
                rtbcb_log_error( 'Missing company size', $user_inputs );
                wp_send_json_error( __( 'Please select your company size.', 'rtbcb' ), 400 );
                return;
            }

            if ( empty( $user_inputs['industry'] ) ) {
                rtbcb_log_error( 'Missing industry', $user_inputs );
                wp_send_json_error( __( 'Please select your industry.', 'rtbcb' ), 400 );
                return;
            }

			if ( $user_inputs['hours_reconciliation'] < 0 ) {
				rtbcb_log_error( 'Invalid reconciliation hours', $user_inputs );
				wp_send_json_error( __( 'Please enter your weekly reconciliation hours.', 'rtbcb' ), 400 );
				return;
			}

            if ( $user_inputs['hours_cash_positioning'] <= 0 ) {
                rtbcb_log_error( 'Invalid cash positioning hours', $user_inputs );
                wp_send_json_error( __( 'Please enter your weekly cash positioning hours.', 'rtbcb' ), 400 );
                return;
            }

            if ( $user_inputs['num_banks'] <= 0 ) {
                rtbcb_log_error( 'Invalid number of banks', $user_inputs );
                wp_send_json_error( __( 'Please enter the number of banking relationships.', 'rtbcb' ), 400 );
                return;
            }

            if ( $user_inputs['ftes'] <= 0 ) {
                rtbcb_log_error( 'Invalid treasury team size', $user_inputs );
                wp_send_json_error( __( 'Please enter your treasury team size.', 'rtbcb' ), 400 );
                return;
            }

			if ( empty( $user_inputs['business_objective'] ) ) {
				rtbcb_log_error( 'Missing business objective', $user_inputs );
				wp_send_json_error( __( 'Please select a primary business objective.', 'rtbcb' ), 400 );
				return;
			}

            if ( empty( $user_inputs['implementation_timeline'] ) ) {
                rtbcb_log_error( 'Missing implementation timeline', $user_inputs );
                wp_send_json_error( __( 'Please select an implementation timeline.', 'rtbcb' ), 400 );
                return;
            }

            if ( empty( $user_inputs['budget_range'] ) ) {
                rtbcb_log_error( 'Missing budget range', $user_inputs );
                wp_send_json_error( __( 'Please select a budget range.', 'rtbcb' ), 400 );
                return;
            }

            rtbcb_log_api_debug( 'Validation passed', $user_inputs );
            rtbcb_log_memory_usage( 'after_validation' );

            // Calculate ROI scenarios
            if ( ! class_exists( 'RTBCB_Calculator' ) ) {
                rtbcb_log_error( 'Calculator class not found' );
                wp_send_json_error( __( 'System error: Calculator not available.', 'rtbcb' ), 500 );
                return;
            }

            rtbcb_log_api_debug( 'Starting ROI calculation' );
            $scenarios = RTBCB_Calculator::calculate_roi( $user_inputs );
            rtbcb_log_api_debug( 'ROI scenarios calculated', $scenarios );
            rtbcb_log_memory_usage( 'after_roi_calculation' );

            // Get category recommendation
            if ( ! class_exists( 'RTBCB_Category_Recommender' ) ) {
                rtbcb_log_error( 'Category Recommender class not found' );
                wp_send_json_error( __( 'System error: Recommender not available.', 'rtbcb' ), 500 );
                return;
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

                    if ( ! rtbcb_has_openai_api_key() ) {
                        $error_code = 'E_API_KEY_MISSING';
                        rtbcb_log_error( $error_code . ': ' . __( 'OpenAI API key not configured.', 'rtbcb' ) );
                        wp_send_json_error(
                            [
                                'message'    => __( 'OpenAI API key not configured.', 'rtbcb' ),
                                'error_code' => $error_code,
                            ],
                            500
                        );
                        return;
                    }

                    $api_key = rtbcb_get_openai_api_key();
                    if ( class_exists( 'RTBCB_API_Tester' ) ) {
                        $connection_test = RTBCB_API_Tester::test_connection( $api_key );
                        if ( empty( $connection_test['success'] ) ) {
                            $error_code = 'E_API_TEST_FAILURE';
                            rtbcb_log_error( $error_code . ': ' . $connection_test['message'] );
                            wp_send_json_error(
                                [
                                    'message'    => $connection_test['message'],
                                    'details'    => $connection_test['details'] ?? '',
                                    'error_code' => $error_code,
                                ],
                                500
                            );
                            return;
                        }
                    }

                    // Short-circuit if the remaining time is insufficient for LLM processing.
                    if ( ( time() - $start_time ) > ( $timeout - 5 ) ) {
                        wp_send_json_error(
                            [ 'message' => __( 'Request timed out; please retry.', 'rtbcb' ) ],
                            504
                        );
                        return;
                    }

                    // Consider offloading this LLM call to a background task and polling for completion to
                    // avoid keeping the HTTP connection open.
                    rtbcb_log_api_debug( 'Calling LLM for comprehensive business case' );
                    $llm = new RTBCB_LLM();
                    $comprehensive_analysis = $llm->generate_comprehensive_business_case(
                        $user_inputs,
                        $scenarios,
                        $rag_context
                    );

                    rtbcb_log_memory_usage( 'after_llm_generation' );

                    if ( is_wp_error( $comprehensive_analysis ) ) {
                        $error_message  = $comprehensive_analysis->get_error_message();
                        $llm_error_code = method_exists( $comprehensive_analysis, 'get_error_code' ) ? $comprehensive_analysis->get_error_code() : '';
                        $error_data     = $comprehensive_analysis->get_error_data();
                        $status         = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : 500;

                        if ( 'llm_http_status' === $llm_error_code ) {
                            rtbcb_log_error( 'E_LLM_HTTP_STATUS: ' . $error_message, [ 'status' => $status ] );
                            wp_send_json_error(
                                [
                                    'message'    => $error_message,
                                    'error_code' => 'E_LLM_HTTP_STATUS',
                                ],
                                $status
                            );
                            return;
                        }

                        $error_code      = 'no_api_key' === $llm_error_code ? 'E_NO_API_KEY' : 'E_LLM_WP_ERROR';
                        rtbcb_log_error( $error_code . ': ' . $error_message, [ 'wp_error_code' => $llm_error_code ] );
                        $guidance        = __( 'Check the OpenAI API key setting in plugin options.', 'rtbcb' );
                        $response_message = __( 'Our AI analysis service is temporarily unavailable.', 'rtbcb' ) . ' ' . $guidance;
                        if ( function_exists( 'wp_get_environment_type' ) && 'production' !== wp_get_environment_type() ) {
                            $response_message = $error_message . ' ' . $guidance;
                        }
                        wp_send_json_error(
                            [
                                'message'    => $response_message,
                                'error_code' => $error_code,
                            ],
                            500
                        );
                        return;
                    }

                    if ( isset( $comprehensive_analysis['error'] ) ) {
                        $error_code = 'E_LLM_RESPONSE_ERROR';
                        rtbcb_log_error( $error_code . ': ' . $comprehensive_analysis['error'] );
                        $guidance        = __( 'Check the OpenAI API key setting in plugin options.', 'rtbcb' );
                        $response_message = __( 'Our AI analysis service is temporarily unavailable.', 'rtbcb' ) . ' ' . $guidance;
                        if ( function_exists( 'wp_get_environment_type' ) && 'production' !== wp_get_environment_type() ) {
                            $response_message = $comprehensive_analysis['error'] . ' ' . $guidance;
                        }
                        wp_send_json_error(
                            [
                                'message'    => $response_message,
                                'error_code' => $error_code,
                            ],
                            500
                        );
                        return;
                    }
                    $required_sections = [ 'executive_summary', 'financial_analysis', 'industry_analysis', 'implementation_roadmap', 'risk_mitigation', 'next_steps' ];
                    $missing_sections  = array_diff( $required_sections, array_keys( $comprehensive_analysis ) );

                    if ( ! empty( $missing_sections ) ) {
                        rtbcb_log_error( 'LLM missing required sections', [ 'missing' => $missing_sections ] );
                        $comprehensive_analysis = $this->generate_fallback_analysis( $user_inputs, $scenarios );
                    } else {
                        rtbcb_log_api_debug( 'LLM generation succeeded' );
                    }
                } catch ( Exception $e ) {
                    $error_code = 'E_LLM_EXCEPTION';
                    rtbcb_log_error( $error_code . ': ' . $e->getMessage() );
                    $guidance        = __( 'Check the OpenAI API key setting in plugin options.', 'rtbcb' );
                    $response_message = __( 'Our AI analysis service is temporarily unavailable.', 'rtbcb' ) . ' ' . $guidance;
                    if ( function_exists( 'wp_get_environment_type' ) && 'production' !== wp_get_environment_type() ) {
                        $response_message = $e->getMessage() . ' ' . $guidance;
                    }
                    wp_send_json_error(
                        [
                            'message'    => $response_message,
                            'error_code' => $error_code,
                        ],
                        500
                    );
                    return;
                } catch ( Error $e ) {
                    // Developers: check server logs for E_LLM_FATAL stack trace.
                    $error_code    = 'E_LLM_FATAL';
                    $error_message = $e->getMessage();

                    rtbcb_log_error( $error_code . ': ' . $error_message, $e->getTraceAsString() );

                    if ( rtbcb_is_openai_configuration_error( $e ) ) {
                        $guidance          = __( 'Check the OpenAI API key setting in plugin options.', 'rtbcb' );
                        $sanitized_message = esc_html( $error_message );
                        $response_message  = __( 'Our AI analysis service is temporarily unavailable.', 'rtbcb' ) . ' ' . $guidance;

                        if ( function_exists( 'wp_get_environment_type' ) && 'production' !== wp_get_environment_type() ) {
                            $response_message = $sanitized_message . ' ' . $guidance;
                        } elseif ( current_user_can( 'manage_options' ) ) {
                            $response_message .= ' ' . $sanitized_message;
                        }
                    } else {
                        $response_message = __( 'Internal error. Please try again later.', 'rtbcb' );
                    }

                        wp_send_json_error(
                            [
                                'message'    => $response_message,
                                'error_code' => $error_code,
                            ],
                            500
                        );
                        return;
                    }
                }

            if ( empty( $comprehensive_analysis ) ) {
                $error_code = 'E_LLM_EMPTY';
                rtbcb_log_error( $error_code . ': LLM returned empty analysis', $user_inputs );
                $guidance        = __( 'Check the OpenAI API key setting in plugin options.', 'rtbcb' );
                $response_message = __( 'Our AI analysis service is temporarily unavailable.', 'rtbcb' ) . ' ' . $guidance;
                if ( function_exists( 'wp_get_environment_type' ) && 'production' !== wp_get_environment_type() ) {
                    $response_message = __( 'LLM returned empty analysis.', 'rtbcb' ) . ' ' . $guidance;
                }
                wp_send_json_error(
                    [
                        'message'    => $response_message,
                        'error_code' => $error_code,
                    ],
                    500
                );
                return;
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
                if ( is_wp_error( $report_html ) || empty( $report_html ) ) {
                    rtbcb_log_error(
                        'Report HTML generation failed',
                        is_wp_error( $report_html ) ? $report_html->get_error_message() : $comprehensive_analysis
                    );
                    wp_send_json_error(
                        [ 'message' => __( 'Failed to render business case report.', 'rtbcb' ) ],
                        500
                    );
                    return;
                }
                rtbcb_log_memory_usage( 'after_report_generation' );
            } catch ( Exception $e ) {
                rtbcb_log_error( 'Report generation failed', $e->getMessage() );
                wp_send_json_error(
                    [ 'message' => __( 'Failed to render business case report.', 'rtbcb' ) ],
                    500
                );
                return;
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
                'analysis_type'          => rtbcb_get_analysis_type(),
                'api_used'               => ! empty( get_option( 'rtbcb_openai_api_key' ) ),
                'fallback_used'          => isset( $comprehensive_analysis['enhanced_fallback'] ),
                'memory_info'            => rtbcb_get_memory_status(),
            ];

            rtbcb_log_memory_usage( 'before_response' );

            wp_send_json_success( $response_data );
            return;

        } catch ( Exception $e ) {
            rtbcb_log_memory_usage( 'exception_occurred' );
            rtbcb_log_error(
                'Ajax exception',
                $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
            );
            wp_send_json_error(
                [ 'message' => __( 'An error occurred while generating your business case. Please try again.', 'rtbcb' ) ],
                500
            );
            return;
        } catch ( Error $e ) {
            rtbcb_log_memory_usage( 'fatal_error_occurred' );
            rtbcb_log_error(
                'Ajax fatal error',
                $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
            );
            wp_send_json_error(
                [ 'message' => __( 'A system error occurred. Please contact support.', 'rtbcb' ) ],
                500
            );
            return;
        }
    }
   /**
    * Generate comprehensive report HTML from template with proper data transformation.
    *
	* @param array $business_case_data Business case data.
	*
	* @return string|WP_Error
	*/
	private function get_comprehensive_report_html( $business_case_data ) {
	$use_comprehensive = $this->should_use_comprehensive_template();

	if ( $use_comprehensive ) {
	$template_path = RTBCB_DIR . 'templates/comprehensive-report-template.php';
	rtbcb_log_api_debug(
	'Using comprehensive report template',
	[
	'template_path'    => $template_path,
	'use_comprehensive' => $use_comprehensive,
	]
	);
	} else {
	$template_path = RTBCB_DIR . 'templates/report-template.php';
	rtbcb_log_api_debug(
	'Using basic report template',
	[
	'template_path'    => $template_path,
	'use_comprehensive' => $use_comprehensive,
	]
	);
	}

	if ( ! file_exists( $template_path ) ) {
	rtbcb_log_error( 'Report template missing', [ 'template_path' => $template_path ] );

	return new WP_Error(
	'rtbcb_template_missing',
	__( 'Report template not found.', 'rtbcb' )
	);
	}

	if ( ! is_readable( $template_path ) ) {
	rtbcb_log_error( 'Report template not readable', [ 'template_path' => $template_path ] );

	return new WP_Error(
	'rtbcb_template_unreadable',
	__( 'Report template is not readable.', 'rtbcb' )
	);
	}

        $business_case_data = is_array( $business_case_data ) ? $business_case_data : [];

        $report_data = $business_case_data['report_data'] ?? null;
        $hash_source = $report_data ?: $business_case_data;
        $data_hash  = md5( wp_json_encode( $hash_source ) );
        $cache_key  = md5( $template_path . ':' . $data_hash );

        $cached_html = wp_cache_get( $cache_key, 'rtbcb_reports' );
        if ( false !== $cached_html ) {
            return $cached_html;
        }

        if ( null === $report_data ) {
            // Transform data structure for template.
            $report_data = $this->transform_data_for_template( $business_case_data );

            if ( is_wp_error( $report_data ) ) {
                $error_data = $report_data->get_error_data();
                rtbcb_log_error(
                    'Report data transformation failed',
                    [
                        'error'        => $report_data->get_error_message(),
                        'missing_keys' => $error_data['status']['missing_keys'] ?? [],
                    ]
                );

                return $report_data;
            }

            if ( isset( $report_data['status'] ) && empty( $report_data['status']['valid'] ) ) {
                rtbcb_log_error(
                    'Report data validation failed',
                    [
                        'missing_keys' => $report_data['status']['missing_keys'],
                    ]
                );
            }
        }

        try {
	set_error_handler(
	static function ( $severity, $message, $file, $line ) {
	throw new ErrorException( $message, 0, $severity, $file, $line );
	}
	);

	ob_start();
	include $template_path;
	$html = ob_get_clean();
	restore_error_handler();

	if ( false === $html ) {
	rtbcb_log_error( 'ob_get_clean returned false', [ 'template_path' => $template_path ] );

	return new WP_Error(
	'rtbcb_ob_get_clean_failed',
	__( 'Failed to capture report output.', 'rtbcb' )
	);
	}
	} catch ( Throwable $e ) {
	if ( ob_get_level() > 0 ) {
	ob_end_clean();
	}
	restore_error_handler();
	rtbcb_log_error(
	'Report template include failed',
	[
	'template_path' => $template_path,
	'error'         => $e->getMessage(),
	]
	);

	return new WP_Error(
	'rtbcb_template_include_failed',
	__( 'Error rendering report template.', 'rtbcb' )
	);
        }

        $html = wp_kses( $html, rtbcb_get_report_allowed_html() );
        wp_cache_set( $cache_key, $html, 'rtbcb_reports', HOUR_IN_SECONDS );

        return $html;
}

   /**
    * Transform LLM response data into the structure expected by comprehensive template.
    *
    * @param array $business_case_data Business case data.
    *
    * @return array
    */
   private function transform_data_for_template( $business_case_data ) {
       $defaults = [
           'company_name'           => '',
           'base_roi'               => 0,
           'roi_base'               => 0,
           'recommended_category'   => '',
           'category_info'          => [],
           'executive_summary'      => '',
           'narrative'              => '',
           'executive_recommendation' => '',
           'recommendation'         => '',
           'payback_months'         => 'N/A',
           'sensitivity_analysis'   => [],
           'company_analysis'       => '',
           'maturity_level'         => 'intermediate',
           'current_state_analysis' => '',
           'market_analysis'        => '',
           'tech_adoption_level'    => 'medium',
           'operational_analysis'   => [],
           'risks'                  => [],
           'confidence'             => 0.85,
           'processing_time'        => 0,
       ];
       $business_case_data = wp_parse_args( (array) $business_case_data, $defaults );

       // Get current company data.
       $company      = rtbcb_get_current_company();
       $company_name = sanitize_text_field( $business_case_data['company_name'] ?: ( $company['name'] ?? __( 'Your Company', 'rtbcb' ) ) );
       $base_roi     = floatval( $business_case_data['base_roi'] ?: $business_case_data['roi_base'] );
       $business_case_data['roi_base'] = $base_roi;

       // Derive recommended category and details from recommendation if not provided.
       $recommended_category = sanitize_text_field( $business_case_data['recommended_category'] ?: ( $business_case_data['recommendation']['recommended'] ?? 'treasury_management_system' ) );
       $category_details     = $business_case_data['category_info'] ?: ( $business_case_data['recommendation']['category_info'] ?? [] );

       // Prepare operational and risk data with fallbacks.
       $operational_analysis = array_map( 'sanitize_text_field', (array) $business_case_data['operational_analysis'] );
       if ( empty( $operational_analysis ) ) {
           $operational_analysis = [ __( 'No data provided', 'rtbcb' ) ];
       }

       $implementation_risks = array_map( 'sanitize_text_field', (array) $business_case_data['risks'] );
       if ( empty( $implementation_risks ) ) {
           $implementation_risks = [ __( 'No data provided', 'rtbcb' ) ];
       }

       // Create structured data format expected by template.
       $report_data = [
           'metadata'           => [
               'company_name'     => $company_name,
               'analysis_date'    => current_time( 'Y-m-d' ),
               'analysis_type'    => rtbcb_get_analysis_type(),
               'confidence_level' => floatval( $business_case_data['confidence'] ),
               'processing_time'  => intval( $business_case_data['processing_time'] ),
           ],
           'executive_summary'  => [
               'strategic_positioning'    => wp_kses_post( $business_case_data['executive_summary'] ?: $business_case_data['narrative'] ),
               'key_value_drivers'       => $this->extract_value_drivers( $business_case_data ),
               'executive_recommendation' => wp_kses_post( $business_case_data['executive_recommendation'] ?: $business_case_data['recommendation'] ),
               'business_case_strength'  => $this->determine_business_case_strength( $business_case_data ),
           ],
           'financial_analysis' => [
               'roi_scenarios'      => $this->format_roi_scenarios( $business_case_data ),
               'payback_analysis'   => [
                   'payback_months' => sanitize_text_field( $business_case_data['payback_months'] ),
               ],
               'sensitivity_analysis' => $business_case_data['sensitivity_analysis'],
           ],
           'company_intelligence' => [
               'enriched_profile' => [
                   'enhanced_description' => wp_kses_post( $business_case_data['company_analysis'] ),
                   'maturity_level'       => sanitize_text_field( $business_case_data['maturity_level'] ),
                   'treasury_maturity'    => [
                       'current_state' => wp_kses_post( $business_case_data['current_state_analysis'] ),
                   ],
               ],
               'industry_context' => [
                   'sector_analysis' => [
                       'market_dynamics' => wp_kses_post( $business_case_data['market_analysis'] ),
                   ],
                   'benchmarking'   => [
                       'technology_penetration' => sanitize_text_field( $business_case_data['tech_adoption_level'] ),
                   ],
               ],
           ],
           'technology_strategy' => [
               'recommended_category' => $recommended_category,
               'category_details'     => $category_details,
           ],
           'operational_insights' => $operational_analysis,
           'risk_analysis'        => [
               'implementation_risks' => $implementation_risks,
           ],
           'action_plan'          => [
               'immediate_steps'      => $this->extract_immediate_steps( $business_case_data ),
               'short_term_milestones'=> $this->extract_short_term_steps( $business_case_data ),
               'long_term_objectives' => $this->extract_long_term_steps( $business_case_data ),
           ],
       ];

       $required_sections = [
           'metadata',
           'executive_summary',
           'financial_analysis',
           'company_intelligence',
           'technology_strategy',
           'operational_insights',
           'risk_analysis',
           'action_plan',
       ];

       $section_defaults = [
           'metadata'           => [
               'company_name'     => '',
               'analysis_date'    => '',
               'analysis_type'    => '',
               'confidence_level' => 0,
               'processing_time'  => 0,
           ],
           'executive_summary'  => [
               'strategic_positioning'    => '',
               'key_value_drivers'       => [],
               'executive_recommendation' => '',
               'business_case_strength'  => '',
           ],
           'financial_analysis' => [
               'roi_scenarios'      => [],
               'payback_analysis'   => [ 'payback_months' => '' ],
               'sensitivity_analysis' => [],
           ],
           'company_intelligence' => [],
           'technology_strategy'  => [],
           'operational_insights' => [],
           'risk_analysis'        => [ 'implementation_risks' => [] ],
           'action_plan'          => [
               'immediate_steps'       => [],
               'short_term_milestones' => [],
               'long_term_objectives'  => [],
           ],
       ];

       $missing_sections = [];

       foreach ( $required_sections as $section ) {
           if ( empty( $report_data[ $section ] ) ) {
               $missing_sections[] = $section;
               rtbcb_log_error(
                   'Missing report data section',
                   [
                       'section' => $section,
                   ]
               );
               $report_data[ $section ] = $section_defaults[ $section ];
           }
       }

       $report_data['status'] = [
           'valid'        => empty( $missing_sections ),
           'missing_keys' => $missing_sections,
       ];

       if ( ! empty( $missing_sections ) ) {
           return new WP_Error(
               'rtbcb_missing_report_sections',
               __( 'Missing required report sections.', 'rtbcb' ),
               $report_data
           );
       }

       return $report_data;
   }

   /**
    * Extract value drivers from business case data.
    *
    * @param array $data Business case data.
    *
    * @return array
    */
   private function extract_value_drivers( $data ) {
       $drivers = [];

       // Extract from various possible sources.
       if ( ! empty( $data['value_drivers'] ) ) {
           $drivers = (array) $data['value_drivers'];
       } elseif ( ! empty( $data['key_benefits'] ) ) {
           $drivers = (array) $data['key_benefits'];
       } else {
           // Default value drivers.
           $drivers = [
               __( 'Automated cash management processes', 'rtbcb' ),
               __( 'Enhanced financial visibility and reporting', 'rtbcb' ),
               __( 'Reduced operational risk and errors', 'rtbcb' ),
               __( 'Improved regulatory compliance', 'rtbcb' ),
           ];
       }

       return array_slice( $drivers, 0, 4 );
   }

   /**
    * Format ROI scenarios for template.
    *
    * @param array $data Business case data.
    *
    * @return array
    */
   private function format_roi_scenarios( $data ) {
       // Try to get ROI data from various possible locations.
       if ( ! empty( $data['scenarios'] ) ) {
           return $data['scenarios'];
       }

       if ( ! empty( $data['roi_scenarios'] ) ) {
           return $data['roi_scenarios'];
       }

       // Fallback to default structure.
       return [
           'conservative' => [
               'total_annual_benefit' => $data['roi_low'] ?? 0,
               'labor_savings'        => ( $data['roi_low'] ?? 0 ) * 0.6,
               'fee_savings'          => ( $data['roi_low'] ?? 0 ) * 0.3,
               'error_reduction'      => ( $data['roi_low'] ?? 0 ) * 0.1,
           ],
           'base' => [
               'total_annual_benefit' => $data['roi_base'] ?? 0,
               'labor_savings'        => ( $data['roi_base'] ?? 0 ) * 0.6,
               'fee_savings'          => ( $data['roi_base'] ?? 0 ) * 0.3,
               'error_reduction'      => ( $data['roi_base'] ?? 0 ) * 0.1,
           ],
           'optimistic' => [
               'total_annual_benefit' => $data['roi_high'] ?? 0,
               'labor_savings'        => ( $data['roi_high'] ?? 0 ) * 0.6,
               'fee_savings'          => ( $data['roi_high'] ?? 0 ) * 0.3,
               'error_reduction'      => ( $data['roi_high'] ?? 0 ) * 0.1,
           ],
       ];
   }

   /**
    * Determine business case strength based on ROI.
    *
    * @param array $data Business case data.
    *
    * @return string
    */
   private function determine_business_case_strength( $data ) {
       $base_roi = $data['roi_base'] ?? $data['scenarios']['base']['total_annual_benefit'] ?? 0;

       if ( $base_roi > 500000 ) {
           return 'Compelling';
       } elseif ( $base_roi > 200000 ) {
           return 'Strong';
       } elseif ( $base_roi > 50000 ) {
           return 'Moderate';
       } else {
           return 'Developing';
       }
   }

   /**
    * Extract action steps from business case data.
    *
    * @param array $data Business case data.
    *
    * @return array
    */
   private function extract_immediate_steps( $data ) {
       if ( ! empty( $data['next_actions'] ) ) {
           $all_actions = (array) $data['next_actions'];
           return array_slice( $all_actions, 0, 3 );
       }

       return [
           __( 'Secure executive sponsorship and budget approval', 'rtbcb' ),
           __( 'Form project steering committee', 'rtbcb' ),
           __( 'Conduct detailed requirements gathering', 'rtbcb' ),
       ];
   }

   /**
    * Extract short term action steps.
    *
    * @param array $data Business case data.
    *
    * @return array
    */
   private function extract_short_term_steps( $data ) {
       if ( ! empty( $data['implementation_steps'] ) ) {
           $steps = (array) $data['implementation_steps'];
           return array_slice( $steps, 0, 4 );
       }

       return [
           __( 'Issue RFP to qualified vendors', 'rtbcb' ),
           __( 'Conduct vendor demonstrations and evaluations', 'rtbcb' ),
           __( 'Negotiate contracts and terms', 'rtbcb' ),
           __( 'Begin system implementation planning', 'rtbcb' ),
       ];
   }

   /**
    * Extract long term action steps.
    *
    * @param array $data Business case data.
    *
    * @return array
    */
   private function extract_long_term_steps( $data ) {
       return [
           __( 'Complete system implementation and testing', 'rtbcb' ),
           __( 'Conduct user training and change management', 'rtbcb' ),
           __( 'Measure and optimize system performance', 'rtbcb' ),
           __( 'Expand functionality and integration capabilities', 'rtbcb' ),
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
        wp_clear_scheduled_hook( 'rtbcb_refresh_lead_metrics' );

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
            'rtbcb_contact_form_id',
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

		/**
		* Output debug information for report generation.
		*
		* @return void
		*/
		public function debug_report_generation() {
		if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
		}
		$debug_info = [
		'comprehensive_template_exists' => file_exists( RTBCB_DIR . 'templates/comprehensive-report-template.php' ),
		'basic_template_exists'        => file_exists( RTBCB_DIR . 'templates/report-template.php' ),
		'enhanced_css_exists'         => file_exists( RTBCB_DIR . 'public/css/enhanced-report.css' ),
		'chart_js_exists'             => file_exists( RTBCB_DIR . 'public/js/chart.min.js' ),
		'comprehensive_analysis_enabled' => get_option( 'rtbcb_comprehensive_analysis', true ),
		'openai_key_configured'       => ! empty( get_option( 'rtbcb_openai_api_key' ) ),
		'required_classes'            => [
		'RTBCB_Calculator'          => class_exists( 'RTBCB_Calculator' ),
		'RTBCB_Category_Recommender'=> class_exists( 'RTBCB_Category_Recommender' ),
		'RTBCB_LLM'                 => class_exists( 'RTBCB_LLM' ),
		'RTBCB_Leads'               => class_exists( 'RTBCB_Leads' ),
		'RTBCB_RAG'                 => class_exists( 'RTBCB_RAG' ),
		],
		'memory_limit'               => ini_get( 'memory_limit' ),
		'max_execution_time'         => ini_get( 'max_execution_time' ),
		'wordpress_version'          => get_bloginfo( 'version' ),
		'php_version'                => PHP_VERSION,
		];
		header( 'Content-Type: application/json' );
		echo wp_json_encode( $debug_info, JSON_PRETTY_PRINT );
		exit;
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
        $stats = RTBCB_Leads::get_cached_statistics();
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
        $stats = RTBCB_Leads::get_cached_statistics();
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
add_action( 'wp_ajax_rtbcb_generate_company_overview', 'rtbcb_ajax_generate_company_overview' );
add_action( 'wp_ajax_rtbcb_generate_real_treasury_overview', 'rtbcb_ajax_generate_real_treasury_overview' );
add_action( 'wp_ajax_rtbcb_generate_category_recommendation', 'rtbcb_ajax_generate_category_recommendation' );
add_action( 'wp_ajax_rtbcb_clear_current_company', 'rtbcb_ajax_clear_current_company' );
add_action( 'wp_ajax_rtbcb_company_overview_simple', 'rtbcb_handle_company_overview_simple' );

/**
 * Simple AJAX handler to test company overview generation.
 *
 * @return void
 */
function rtbcb_handle_company_overview_simple() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rtbcb_test_company_overview' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ] );
        return;
    }

    $company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';

    if ( empty( $company_name ) ) {
        wp_send_json_error( [ 'message' => __( 'Company name required', 'rtbcb' ) ] );
        return;
    }

    wp_send_json_success(
        [
            'message'         => sprintf( __( 'Processing started for %s', 'rtbcb' ), $company_name ),
            'status'          => 'processing',
            'simple_analysis' => rtbcb_get_simple_company_info( $company_name ),
        ]
    );
}

/**
 * Provide simple placeholder analysis for testing connections.
 *
 * @param string $company_name Company name.
 * @return array
 */
function rtbcb_get_simple_company_info( $company_name ) {
    return [
        'analysis'        => sprintf( __( 'Analysis requested for %s. This is a placeholder response to test the connection without LLM calls.', 'rtbcb' ), $company_name ),
        'recommendations' => [
            __( 'Implement treasury management system', 'rtbcb' ),
            __( 'Automate cash forecasting', 'rtbcb' ),
            __( 'Improve bank connectivity', 'rtbcb' ),
        ],
        'references'      => [
            esc_url_raw( 'https://www.afponline.org' ),
            esc_url_raw( 'https://www.treasury.gov' ),
        ],
        'generated_at'    => current_time( 'Y-m-d H:i:s' ),
    ];
}

/**
 * AJAX handler for generating company overview.
 *
 * @return void
 */
function rtbcb_ajax_generate_company_overview() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rtbcb_test_company_overview' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ] );
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ] );
        return;
    }

    $company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
    $company_size = isset( $_POST['company_size'] ) ? sanitize_text_field( wp_unslash( $_POST['company_size'] ) ) : '';
    $industry     = isset( $_POST['industry'] ) ? sanitize_text_field( wp_unslash( $_POST['industry'] ) ) : '';

    if ( empty( $company_name ) ) {
        wp_send_json_error( [ 'message' => __( 'Company name is required.', 'rtbcb' ) ] );
        return;
    }

    // Increase timeout for comprehensive analysis
    ini_set( 'max_execution_time', 300 ); // 5 minutes
    wp_raise_memory_limit( '512M' ); // Increase memory limit if needed

    $start_time = microtime( true );

    try {
        $overview = rtbcb_test_generate_company_overview( $company_name );

        if ( is_wp_error( $overview ) ) {
            wp_send_json_error( [
                'message' => sanitize_text_field( $overview->get_error_message() ),
            ] );
            return;
        }

        $analysis        = $overview['analysis'] ?? '';
        $recommendations = array_map( 'sanitize_text_field', $overview['recommendations'] ?? [] );
        $references      = array_map( 'esc_url_raw', $overview['references'] ?? [] );

        $word_count   = str_word_count( wp_strip_all_tags( $analysis ) );
        $elapsed_time = microtime( true ) - $start_time;
        $timestamp    = current_time( 'mysql' );

        $company_data = [
            'name'            => $company_name,
            'summary'         => sanitize_textarea_field( wp_strip_all_tags( $analysis ) ),
            'size'            => $company_size,
            'industry'        => $industry,
            'recommendations' => $recommendations,
            'references'      => $references,
            'word_count'      => intval( $word_count ),
            'generated_at'    => $timestamp,
        ];

        update_option( 'rtbcb_current_company', $company_data );

        wp_send_json_success(
            [
                'overview'        => wp_kses_post( $analysis ),
                'company_name'    => $company_name,
                'recommendations' => $recommendations,
                'references'      => $references,
                'word_count'      => intval( $word_count ),
                'elapsed_time'    => round( $elapsed_time, 2 ),
                'generated_at'    => $timestamp,
            ]
        );
    } catch ( Exception $e ) {
        error_log( 'RTBCB Company Overview Error: ' . $e->getMessage() );
        wp_send_json_error(
            [
                'message' => __( 'An error occurred while generating the overview. Please try again.', 'rtbcb' ),
            ]
        );
    }
}

/**
 * AJAX handler to clear current company data.
 *
 * @return void
 */
function rtbcb_ajax_clear_current_company() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rtbcb_test_company_overview' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ] );
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ] );
        return;
    }

    rtbcb_clear_current_company();

    wp_send_json_success();
}

/**
 * AJAX handler for generating Real Treasury platform overview.
 *
 * @return void
 */
function rtbcb_ajax_generate_real_treasury_overview() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rtbcb_test_real_treasury_overview' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ] );
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ] );
        return;
    }

    $company = rtbcb_get_current_company();
    if ( empty( $company ) ) {
        wp_send_json_error( [ 'message' => __( 'No company data found. Please run the company overview first.', 'rtbcb' ) ] );
        return;
    }

    $include_portal = isset( $_POST['include_portal'] ) ? rest_sanitize_boolean( wp_unslash( $_POST['include_portal'] ) ) : false;
    $categories     = [];
    if ( isset( $_POST['vendor_categories'] ) ) {
        $categories = array_filter( array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['vendor_categories'] ) ) );
    }

    $start_time = microtime( true );

    try {
        $overview = rtbcb_test_generate_real_treasury_overview( $include_portal, $categories );

        if ( is_wp_error( $overview ) ) {
            wp_send_json_error( [ 'message' => sanitize_text_field( $overview->get_error_message() ) ] );
            return;
        }

        $word_count   = str_word_count( wp_strip_all_tags( $overview ) );
        $elapsed_time = microtime( true ) - $start_time;
        $timestamp    = current_time( 'mysql' );

        wp_send_json_success(
            [
                'overview'       => wp_kses_post( $overview ),
                'include_portal' => $include_portal,
                'categories'     => $categories,
                'word_count'     => intval( $word_count ),
                'elapsed_time'   => round( $elapsed_time, 2 ),
                'generated_at'   => $timestamp,
            ]
        );
    } catch ( Exception $e ) {
        error_log( 'RTBCB Real Treasury Overview Error: ' . $e->getMessage() );
        wp_send_json_error(
            [
                'message' => __( 'An error occurred while generating the overview. Please try again.', 'rtbcb' ),
            ]
        );
    }
}

/**
 * AJAX handler for generating category recommendation.
 *
 * @return void
 */
function rtbcb_ajax_generate_category_recommendation() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rtbcb_test_category_recommendation' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ] );
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ] );
        return;
    }

    $company = rtbcb_get_current_company();
    if ( empty( $company ) ) {
        wp_send_json_error( [ 'message' => __( 'No company data found. Please run the company overview first.', 'rtbcb' ) ] );
        return;
    }

    $extra_requirements = isset( $_POST['extra_requirements'] ) ? sanitize_textarea_field( wp_unslash( $_POST['extra_requirements'] ) ) : '';

    $analysis = [
        'company_overview'    => sanitize_textarea_field( get_option( 'rtbcb_company_overview', '' ) ),
        'industry_insights'   => sanitize_textarea_field( get_option( 'rtbcb_industry_insights', '' ) ),
        'maturity_model'      => sanitize_textarea_field( get_option( 'rtbcb_maturity_model', '' ) ),
        'rag_market_analysis' => get_option( 'rtbcb_rag_market_analysis', [] ),
        'value_proposition'   => sanitize_textarea_field( get_option( 'rtbcb_value_proposition', '' ) ),
        'treasury_challenges' => sanitize_textarea_field( get_option( 'rtbcb_treasury_challenges', '' ) ),
        'extra_requirements'  => $extra_requirements,
    ];

    try {
        $recommendation = rtbcb_test_generate_category_recommendation( $analysis );
        wp_send_json_success( $recommendation );
    } catch ( Exception $e ) {
        wp_send_json_error( [ 'message' => __( 'An error occurred while generating the recommendation.', 'rtbcb' ) ] );
    }
}

// Enqueue admin scripts for company overview page.
add_action( 'admin_enqueue_scripts', 'rtbcb_enqueue_company_overview_scripts' );
add_action( 'admin_enqueue_scripts', 'rtbcb_enqueue_real_treasury_overview_scripts' );
add_action( 'admin_enqueue_scripts', 'rtbcb_enqueue_recommended_category_scripts' );

/**
 * Enqueue admin scripts for company overview page.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function rtbcb_enqueue_company_overview_scripts( $hook ) {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    if ( strpos( $hook, 'rtbcb' ) !== false && ( strpos( $hook, 'company-overview' ) !== false || 'rtbcb-test-dashboard' === $page ) ) {
        wp_enqueue_script(
            'rtbcb-test-utils',
            plugin_dir_url( __FILE__ ) . 'admin/js/rtbcb-test-utils.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );
        wp_enqueue_script(
            'rtbcb-company-overview',
            plugin_dir_url( __FILE__ ) . 'admin/js/company-overview.js',
            [ 'jquery', 'rtbcb-test-utils' ],
            '1.0.0',
            true
        );

        wp_localize_script(
            'rtbcb-company-overview',
            'rtbcb_ajax',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'rtbcb_test_company_overview' ),
                'timeout'  => rtbcb_get_api_timeout() * 1000,
            ]
        );
    }
}

/**
 * Enqueue admin scripts for real treasury overview page.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function rtbcb_enqueue_real_treasury_overview_scripts( $hook ) {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    if ( strpos( $hook, 'rtbcb' ) !== false && ( strpos( $hook, 'real-treasury-overview' ) !== false || 'rtbcb-test-dashboard' === $page ) ) {
        wp_enqueue_script(
            'rtbcb-test-utils',
            plugin_dir_url( __FILE__ ) . 'admin/js/rtbcb-test-utils.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );
        wp_enqueue_script(
            'rtbcb-real-treasury-overview',
            plugin_dir_url( __FILE__ ) . 'admin/js/real-treasury-overview.js',
            [ 'jquery', 'rtbcb-test-utils' ],
            '1.0.0',
            true
        );

        wp_localize_script(
            'rtbcb-real-treasury-overview',
            'rtbcb_ajax',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'rtbcb_test_real_treasury_overview' ),
            ]
        );
    }
}

/**
 * Enqueue admin scripts for category recommendation page.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function rtbcb_enqueue_recommended_category_scripts( $hook ) {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

    if ( false !== strpos( $page, 'recommended-category' ) || 'rtbcb-test-dashboard' === $page ) {
        wp_enqueue_script(
            'rtbcb-test-utils',
            plugin_dir_url( __FILE__ ) . 'admin/js/rtbcb-test-utils.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );
        wp_enqueue_script(
            'rtbcb-recommended-category',
            plugin_dir_url( __FILE__ ) . 'admin/js/recommended-category.js',
            [ 'jquery', 'rtbcb-test-utils' ],
            '1.0.0',
            true
        );

        wp_localize_script(
            'rtbcb-recommended-category',
            'rtbcb_ajax',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'rtbcb_test_category_recommendation' ),
            ]
        );
    }
}

