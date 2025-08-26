<?php
/**
 * Plugin Name: Real Treasury - Business Case Builder (Enhanced Pro)
 * Plugin URI: https://realtreasury.com/business-case-builder
 * Description: Professional-grade ROI calculator and comprehensive business case generator for treasury technology with advanced analysis and consultant-style reports.
 * Version: 2.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Real Treasury
 * Author URI: https://realtreasury.com
 * Text Domain: rtbcb
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package RealTreasuryBusinessCaseBuilder
 * @since 2.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'RTBCB_VERSION', '2.1.0' );
define( 'RTBCB_PLUGIN_FILE', __FILE__ );
define( 'RTBCB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RTBCB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RTBCB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Plugin Class
 * 
 * Modern, clean implementation following WordPress best practices
 */
final class RTBCB_Business_Case_Builder {
    
    /**
     * Plugin instance
     * 
     * @var RTBCB_Business_Case_Builder|null
     */
    private static $instance = null;
    
    /**
     * Plugin data
     * 
     * @var array
     */
    private $plugin_data = array();
    
    /**
     * Component instances
     * 
     * @var array
     */
    private $components = array();
    
    /**
     * Get plugin instance (Singleton pattern)
     * 
     * @return RTBCB_Business_Case_Builder
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_plugin_data();
        $this->init_hooks();
        $this->load_dependencies();
        $this->init_components();
    }
    
    /**
     * Initialize plugin data
     */
    private function init_plugin_data() {
        $this->plugin_data = get_file_data( RTBCB_PLUGIN_FILE, array(
            'name' => 'Plugin Name',
            'version' => 'Version',
            'description' => 'Description',
            'author' => 'Author',
            'requires_wp' => 'Requires at least',
            'requires_php' => 'Requires PHP',
            'text_domain' => 'Text Domain'
        ) );
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook( RTBCB_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( RTBCB_PLUGIN_FILE, array( $this, 'deactivate' ) );
        
        // Core WordPress hooks
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        
        // Shortcode registration
        add_shortcode( 'rt_business_case_builder', array( $this, 'render_shortcode' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_rtbcb_generate_case', array( $this, 'handle_ajax_generate_case' ) );
        add_action( 'wp_ajax_nopriv_rtbcb_generate_case', array( $this, 'handle_ajax_generate_case' ) );
        
        // Admin functionality
        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
            add_action( 'admin_init', array( $this, 'admin_init' ) );
            add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        }
        
        // Plugin action links
        add_filter( 'plugin_action_links_' . RTBCB_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load utilities first
        require_once RTBCB_PLUGIN_DIR . 'inc/utils/helpers.php';
        
        // Load API components
        require_once RTBCB_PLUGIN_DIR . 'inc/api/openai-client.php';
        
        // Load core classes
        require_once RTBCB_PLUGIN_DIR . 'inc/class-rtbcb-calculator.php';
        require_once RTBCB_PLUGIN_DIR . 'inc/class-rtbcb-llm.php';
        require_once RTBCB_PLUGIN_DIR . 'inc/class-rtbcb-leads.php';
        require_once RTBCB_PLUGIN_DIR . 'inc/class-rtbcb-validator.php';
        require_once RTBCB_PLUGIN_DIR . 'inc/class-rtbcb-router.php';
        
        // Load admin classes if in admin
        if ( is_admin() ) {
            require_once RTBCB_PLUGIN_DIR . 'admin/classes/Admin.php';
        }
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize core components
        $this->components['calculator'] = new RTBCB_Calculator();
        $this->components['validator'] = new RTBCB_Validator();
        $this->components['leads'] = new RTBCB_Leads();
        
        // Initialize admin component if in admin
        if ( is_admin() ) {
            $this->components['admin'] = new RTBCB_Admin();
        }
    }
    
    /**
     * Plugin initialization
     */
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain(
            'rtbcb',
            false,
            dirname( RTBCB_PLUGIN_BASENAME ) . '/languages'
        );
        
        // Initialize database if needed
        $this->maybe_upgrade_database();
        
        do_action( 'rtbcb_init' );
    }
    
    /**
     * Plugins loaded hook
     */
    public function plugins_loaded() {
        // Check WordPress and PHP version compatibility
        if ( ! $this->check_compatibility() ) {
            return;
        }
        
        do_action( 'rtbcb_loaded' );
    }
    
    /**
     * Check WordPress and PHP compatibility
     * 
     * @return bool True if compatible
     */
    private function check_compatibility() {
        global $wp_version;
        
        $wp_required = '6.0';
        $php_required = '7.4';
        
        if ( version_compare( $wp_version, $wp_required, '<' ) ) {
            add_action( 'admin_notices', function() use ( $wp_required ) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    __( 'Real Treasury Business Case Builder requires WordPress %s or higher. You are running %s.', 'rtbcb' ),
                    esc_html( $wp_required ),
                    esc_html( $GLOBALS['wp_version'] )
                );
                echo '</p></div>';
            } );
            return false;
        }
        
        if ( version_compare( PHP_VERSION, $php_required, '<' ) ) {
            add_action( 'admin_notices', function() use ( $php_required ) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    __( 'Real Treasury Business Case Builder requires PHP %s or higher. You are running %s.', 'rtbcb' ),
                    esc_html( $php_required ),
                    esc_html( PHP_VERSION )
                );
                echo '</p></div>';
            } );
            return false;
        }
        
        return true;
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check system requirements
        if ( ! $this->check_compatibility() ) {
            wp_die( __( 'Plugin activation failed due to system requirements.', 'rtbcb' ) );
        }
        
        // Create database tables
        $this->create_database_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cleanup events
        if ( ! wp_next_scheduled( 'rtbcb_daily_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'rtbcb_daily_cleanup' );
        }
        
        // Record activation
        update_option( 'rtbcb_activated', time() );
        update_option( 'rtbcb_version', RTBCB_VERSION );
        
        do_action( 'rtbcb_activated' );
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook( 'rtbcb_daily_cleanup' );
        
        // Clear transients
        $this->clear_transients();
        
        do_action( 'rtbcb_deactivated' );
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Leads table
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            company varchar(255) NOT NULL,
            industry varchar(100) DEFAULT '',
            company_size varchar(50) DEFAULT '',
            roi_data longtext,
            status varchar(20) DEFAULT 'new',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'rtbcb_openai_model' => 'gpt-4o-mini',
            'rtbcb_max_tokens' => 2000,
            'rtbcb_temperature' => 0.7,
            'rtbcb_enable_logging' => true,
            'rtbcb_data_retention_days' => 90
        );
        
        foreach ( $defaults as $option => $value ) {
            if ( false === get_option( $option ) ) {
                update_option( $option, $value );
            }
        }
    }
    
    /**
     * Clear plugin transients
     */
    private function clear_transients() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_rtbcb_%'
            )
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_rtbcb_%'
            )
        );
    }
    
    /**
     * Maybe upgrade database
     */
    private function maybe_upgrade_database() {
        $current_version = get_option( 'rtbcb_version', '0.0.0' );
        
        if ( version_compare( $current_version, RTBCB_VERSION, '<' ) ) {
            $this->create_database_tables();
            update_option( 'rtbcb_version', RTBCB_VERSION );
        }
    }
    
    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        // Only load on pages with shortcode
        if ( ! $this->should_load_public_assets() ) {
            return;
        }
        
        wp_enqueue_style(
            'rtbcb-public',
            RTBCB_PLUGIN_URL . 'public/css/rtbcb.css',
            array(),
            RTBCB_VERSION
        );
        
        wp_enqueue_script(
            'rtbcb-wizard',
            RTBCB_PLUGIN_URL . 'public/js/rtbcb-wizard.js',
            array( 'jquery' ),
            RTBCB_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script( 'rtbcb-wizard', 'rtbcb_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => rtbcb_create_nonce( 'generate_case' ),
            'strings' => array(
                'processing' => __( 'Processing...', 'rtbcb' ),
                'error' => __( 'An error occurred. Please try again.', 'rtbcb' )
            )
        ) );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on plugin admin pages
        if ( strpos( $hook, 'rtbcb' ) === false ) {
            return;
        }
        
        wp_enqueue_style(
            'rtbcb-admin',
            RTBCB_PLUGIN_URL . 'admin/assets/css/admin-modern.css',
            array(),
            RTBCB_VERSION
        );
        
        wp_enqueue_script(
            'rtbcb-admin',
            RTBCB_PLUGIN_URL . 'admin/assets/js/admin-modern.js',
            array( 'jquery' ),
            RTBCB_VERSION,
            true
        );
    }
    
    /**
     * Check if public assets should be loaded
     * 
     * @return bool
     */
    private function should_load_public_assets() {
        global $post;
        
        if ( is_singular() && $post && has_shortcode( $post->post_content, 'rt_business_case_builder' ) ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Render shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'theme' => 'default',
            'show_scenarios' => 'true'
        ), $atts, 'rt_business_case_builder' );
        
        ob_start();
        include RTBCB_PLUGIN_DIR . 'templates/business-case-form.php';
        return ob_get_clean();
    }
    
    /**
     * Handle AJAX case generation
     */
    public function handle_ajax_generate_case() {
        // Verify nonce
        if ( ! rtbcb_verify_nonce( $_POST['nonce'] ?? '', 'generate_case' ) ) {
            wp_send_json_error( array(
                'message' => rtbcb_get_user_friendly_error( 'security_check_failed' )
            ) );
        }
        
        // Sanitize inputs
        $inputs = rtbcb_sanitize_calculation_inputs( $_POST );
        
        if ( is_wp_error( $inputs ) ) {
            wp_send_json_error( array(
                'message' => $inputs->get_error_message()
            ) );
        }
        
        // Generate case using router
        $router = new RTBCB_Router();
        $result = $router->generate_comprehensive_case( $inputs );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message()
            ) );
        }
        
        wp_send_json_success( $result );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Business Case Builder', 'rtbcb' ),
            __( 'Business Cases', 'rtbcb' ),
            'manage_options',
            'rtbcb-dashboard',
            array( $this, 'admin_dashboard_page' ),
            'dashicons-chart-line',
            30
        );
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        // Register settings
        register_setting( 'rtbcb_settings', 'rtbcb_openai_api_key' );
        register_setting( 'rtbcb_settings', 'rtbcb_openai_model' );
    }
    
    /**
     * Admin dashboard page
     */
    public function admin_dashboard_page() {
        if ( ! rtbcb_user_can_manage_settings() ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'rtbcb' ) );
        }
        
        include RTBCB_PLUGIN_DIR . 'admin/views/dashboard/main.php';
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Check if API key is configured
        if ( empty( get_option( 'rtbcb_openai_api_key' ) ) ) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>' . sprintf(
                __( 'Business Case Builder: Please <a href="%s">configure your OpenAI API key</a> to enable AI-powered reports.', 'rtbcb' ),
                admin_url( 'admin.php?page=rtbcb-settings' )
            ) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Plugin action links
     * 
     * @param array $links Existing links
     * @return array Modified links
     */
    public function plugin_action_links( $links ) {
        $plugin_links = array(
            '<a href="' . admin_url( 'admin.php?page=rtbcb-dashboard' ) . '">' . __( 'Dashboard', 'rtbcb' ) . '</a>',
            '<a href="' . admin_url( 'admin.php?page=rtbcb-settings' ) . '">' . __( 'Settings', 'rtbcb' ) . '</a>',
        );
        
        return array_merge( $plugin_links, $links );
    }
    
    /**
     * Get component instance
     * 
     * @param string $component Component name
     * @return object|null Component instance or null if not found
     */
    public function get_component( $component ) {
        return isset( $this->components[ $component ] ) ? $this->components[ $component ] : null;
    }
    
    /**
     * Get plugin data
     * 
     * @param string $key Optional specific data key
     * @return mixed Plugin data
     */
    public function get_plugin_data( $key = null ) {
        if ( null === $key ) {
            return $this->plugin_data;
        }
        
        return isset( $this->plugin_data[ $key ] ) ? $this->plugin_data[ $key ] : null;
    }
}

/**
 * Initialize the plugin
 * 
 * @return RTBCB_Business_Case_Builder
 */
function rtbcb() {
    return RTBCB_Business_Case_Builder::get_instance();
}

// Initialize the plugin
rtbcb();