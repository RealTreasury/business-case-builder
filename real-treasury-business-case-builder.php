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
 * Network: false
 *
 * @package RealTreasuryBusinessCaseBuilder
 * @since 2.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct access not permitted.' );
}

// Core plugin constants
define( 'RTBCB_VERSION', '2.1.0' );
define( 'RTBCB_PLUGIN_FILE', __FILE__ );
define( 'RTBCB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RTBCB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RTBCB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'RTBCB_MIN_WP_VERSION', '6.0' );
define( 'RTBCB_MIN_PHP_VERSION', '7.4' );

/**
 * Main Plugin Class - Modern Enterprise Architecture
 * 
 * Complete rebuild implementing best-in-class patterns:
 * - Dependency injection container
 * - Service locator pattern
 * - Clean component separation
 * - Security-first design
 * - Performance optimization
 */
final class RTBCB_Business_Case_Builder {
    
    /**
     * Singleton instance
     * 
     * @var RTBCB_Business_Case_Builder|null
     */
    private static $instance = null;
    
    /**
     * Service container
     * 
     * @var array
     */
    private $services = array();
    
    /**
     * Plugin initialization state
     * 
     * @var bool
     */
    private $initialized = false;
    
    /**
     * Plugin data cache
     * 
     * @var array
     */
    private $plugin_data = array();
    
    /**
     * Get singleton instance
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
     * Private constructor for singleton
     */
    private function __construct() {
        $this->validate_environment();
        $this->define_constants();
        $this->register_autoloader();
        $this->register_hooks();
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        // Log the attempt but don't crash with Exception
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'RTBCB: Attempted unserialization of singleton prevented' );
        }
        
        // Return early instead of throwing exception to prevent crashes
        return;
    }
    
    /**
     * Validate environment requirements
     */
    private function validate_environment() {
        // PHP version check
        if ( version_compare( PHP_VERSION, RTBCB_MIN_PHP_VERSION, '<' ) ) {
            add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
            return;
        }
        
        // WordPress version check
        global $wp_version;
        if ( version_compare( $wp_version, RTBCB_MIN_WP_VERSION, '<' ) ) {
            add_action( 'admin_notices', array( $this, 'wp_version_notice' ) );
            return;
        }
    }
    
    /**
     * Define additional constants
     */
    private function define_constants() {
        if ( ! defined( 'RTBCB_DEBUG' ) ) {
            define( 'RTBCB_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );
        }
        
        if ( ! defined( 'RTBCB_UPLOADS_DIR' ) ) {
            $upload_dir = wp_upload_dir();
            define( 'RTBCB_UPLOADS_DIR', $upload_dir['basedir'] . '/rtbcb/' );
        }
    }
    
    /**
     * Register PSR-4 compatible autoloader
     */
    private function register_autoloader() {
        spl_autoload_register( array( $this, 'autoload' ) );
    }
    
    /**
     * Autoload classes
     * 
     * @param string $class_name Class name to load
     */
    public function autoload( $class_name ) {
        if ( 0 !== strpos( $class_name, 'RTBCB_' ) ) {
            return;
        }
        
        // Convert class name to file path
        $class_name = str_replace( 'RTBCB_', '', $class_name );
        $class_name = str_replace( '_', '-', strtolower( $class_name ) );
        
        // Define potential file locations
        $locations = array(
            RTBCB_PLUGIN_DIR . 'inc/class-rtbcb-' . $class_name . '.php',
            RTBCB_PLUGIN_DIR . 'inc/classes/class-rtbcb-' . $class_name . '.php',
            RTBCB_PLUGIN_DIR . 'admin/classes/class-rtbcb-' . $class_name . '.php',
        );
        
        foreach ( $locations as $file ) {
            if ( file_exists( $file ) ) {
                require_once $file;
                break;
            }
        }
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // Core WordPress hooks
        add_action( 'init', array( $this, 'init' ), 0 );
        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
        
        // Activation/Deactivation
        register_activation_hook( RTBCB_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( RTBCB_PLUGIN_FILE, array( $this, 'deactivate' ) );
        
        // Asset loading
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        
        // Shortcode
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_rtbcb_generate_case', array( $this, 'handle_ajax_generate_case' ) );
        add_action( 'wp_ajax_nopriv_rtbcb_generate_case', array( $this, 'handle_ajax_generate_case' ) );
        
        // Admin hooks
        if ( is_admin() ) {
            // Note: Admin menu registration is handled by RTBCB_Admin class
            add_action( 'admin_init', array( $this, 'admin_init' ) );
            add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        }
        
        // Plugin links
        add_filter( 'plugin_action_links_' . RTBCB_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
        add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        if ( $this->initialized ) {
            return;
        }
        
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize services
        $this->init_services();
        
        // Load text domain
        $this->load_textdomain();
        
        // Create database tables if needed
        $this->maybe_create_tables();
        
        // Mark as initialized
        $this->initialized = true;
        
        // Fire initialization action
        do_action( 'rtbcb_initialized' );
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load utilities first with safety check
        $helpers_file = RTBCB_PLUGIN_DIR . 'inc/utils/helpers.php';
        if ( file_exists( $helpers_file ) ) {
            require_once $helpers_file;
        } else {
            error_log( 'RTBCB: Missing critical file: ' . $helpers_file );
        }
        
        // Load API components with safety check
        $api_file = RTBCB_PLUGIN_DIR . 'inc/api/openai-client.php';
        if ( file_exists( $api_file ) ) {
            require_once $api_file;
        } else {
            error_log( 'RTBCB: Missing API file: ' . $api_file );
        }
        
        // Load core business logic classes
        $core_classes = array(
            'calculator',
            'validator',
            'leads',
            'router',
            'llm',
            'rag',
            'db',
            'category-recommender',
            'error-handler',
            'performance-monitor'
        );
        
        foreach ( $core_classes as $class ) {
            $file = RTBCB_PLUGIN_DIR . 'inc/class-rtbcb-' . $class . '.php';
            if ( file_exists( $file ) ) {
                require_once $file;
            } else {
                error_log( 'RTBCB: Missing class file: ' . $file );
            }
        }
        
        // Load configuration with safety check
        $config_file = RTBCB_PLUGIN_DIR . 'inc/config.php';
        if ( file_exists( $config_file ) ) {
            require_once $config_file;
        }
        
        // Load admin components if in admin with safety check
        if ( is_admin() ) {
            $admin_file = RTBCB_PLUGIN_DIR . 'admin/classes/Admin.php';
            if ( file_exists( $admin_file ) ) {
                require_once $admin_file;
            } else {
                error_log( 'RTBCB: Missing admin file: ' . $admin_file );
            }
        }
    }
    
    /**
     * Initialize services using dependency injection with proper order
     */
    private function init_services() {
        // Initialize core services in dependency order
        
        // 1. Foundation services (no dependencies)
        if ( class_exists( 'RTBCB_Error_Handler' ) ) {
            $this->services['error_handler'] = new RTBCB_Error_Handler();
        }
        
        if ( class_exists( 'RTBCB_Performance_Monitor' ) ) {
            $this->services['performance_monitor'] = new RTBCB_Performance_Monitor();
        }
        
        // 2. Core data services
        if ( class_exists( 'RTBCB_DB' ) ) {
            $this->services['db'] = new RTBCB_DB();
        }
        
        if ( class_exists( 'RTBCB_Validator' ) ) {
            $this->services['validator'] = new RTBCB_Validator();
        }
        
        // 3. Initialize Calculator static dependencies
        if ( class_exists( 'RTBCB_Calculator' ) ) {
            RTBCB_Calculator::initialize();
            $this->services['calculator'] = 'RTBCB_Calculator'; // Static class reference
        }
        
        if ( class_exists( 'RTBCB_Leads' ) ) {
            $this->services['leads'] = new RTBCB_Leads();
        }
        
        // 4. API and processing services (may depend on foundation services)
        if ( class_exists( 'RTBCB_RAG' ) ) {
            $this->services['rag'] = new RTBCB_RAG();
        }
        
        if ( class_exists( 'RTBCB_LLM' ) ) {
            $this->services['llm'] = new RTBCB_LLM(
                null, // API client - will use default
                $this->services['error_handler'] ?? null,
                $this->services['performance_monitor'] ?? null
            );
        }
        
        // 5. Router service (depends on all other services)
        if ( class_exists( 'RTBCB_Router' ) ) {
            $this->services['router'] = new RTBCB_Router(
                $this->services['error_handler'] ?? null,
                $this->services['performance_monitor'] ?? null
            );
        }
        
        // 6. Admin service (loads last)
        if ( is_admin() && class_exists( 'RTBCB_Admin' ) ) {
            $this->services['admin'] = new RTBCB_Admin();
        }
        
        // Log service initialization
        if ( isset( $this->services['performance_monitor'] ) ) {
            $this->services['performance_monitor']->log_event( 'plugin_services_initialized', array(
                'services_count' => count( $this->services ),
                'services' => array_keys( $this->services )
            ));
        }
    }
    
    /**
     * Load plugin text domain
     */
    private function load_textdomain() {
        load_plugin_textdomain(
            'rtbcb',
            false,
            dirname( RTBCB_PLUGIN_BASENAME ) . '/languages'
        );
    }
    
    /**
     * Maybe create database tables
     */
    private function maybe_create_tables() {
        $db_version = get_option( 'rtbcb_db_version', '0.0.0' );
        
        if ( version_compare( $db_version, RTBCB_VERSION, '<' ) ) {
            $this->create_database_tables();
            update_option( 'rtbcb_db_version', RTBCB_VERSION );
        }
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Leads table
        $leads_table = $wpdb->prefix . 'rtbcb_leads';
        $leads_sql = "CREATE TABLE $leads_table (
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
            KEY created_at (created_at),
            KEY company (company)
        ) $charset_collate;";
        
        // RAG index table (if RAG is enabled)
        $rag_table = $wpdb->prefix . 'rtbcb_rag_index';
        $rag_sql = "CREATE TABLE $rag_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            content_hash varchar(64) NOT NULL,
            content_type varchar(50) NOT NULL,
            content longtext NOT NULL,
            embeddings longtext,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY content_hash (content_hash),
            KEY content_type (content_type)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $leads_sql );
        dbDelta( $rag_sql );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Verify system requirements
        if ( version_compare( PHP_VERSION, RTBCB_MIN_PHP_VERSION, '<' ) ) {
            deactivate_plugins( RTBCB_PLUGIN_BASENAME );
            wp_die( sprintf(
                __( 'Real Treasury Business Case Builder requires PHP %s or higher.', 'rtbcb' ),
                RTBCB_MIN_PHP_VERSION
            ) );
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
        update_option( 'rtbcb_activated_time', time() );
        update_option( 'rtbcb_version', RTBCB_VERSION );
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        do_action( 'rtbcb_activated' );
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook( 'rtbcb_daily_cleanup' );
        
        // Clear caches
        $this->clear_caches();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        do_action( 'rtbcb_deactivated' );
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
            'rtbcb_log_level' => 'info',
            'rtbcb_data_retention_days' => 90,
            'rtbcb_rate_limit_enabled' => true,
            'rtbcb_rate_limit_requests' => 60,
            'rtbcb_cache_enabled' => true,
            'rtbcb_cache_ttl' => 3600
        );
        
        foreach ( $defaults as $option => $value ) {
            if ( false === get_option( $option ) ) {
                update_option( $option, $value );
            }
        }
    }
    
    /**
     * Clear all plugin caches
     */
    private function clear_caches() {
        global $wpdb;
        
        // Clear transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_rtbcb_%',
                '_transient_timeout_rtbcb_%'
            )
        );
        
        // Clear object cache
        wp_cache_flush();
    }
    
    /**
     * Plugins loaded hook
     */
    public function plugins_loaded() {
        do_action( 'rtbcb_plugins_loaded' );
    }
    
    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        // Only load on pages with our shortcode
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
        
        // Localize script with AJAX data
        wp_localize_script( 'rtbcb-wizard', 'rtbcb_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'rtbcb_generate_case' ),
            'strings' => array(
                'processing' => __( 'Processing your business case...', 'rtbcb' ),
                'error' => __( 'An error occurred. Please try again.', 'rtbcb' ),
                'success' => __( 'Business case generated successfully!', 'rtbcb' )
            )
        ) );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on plugin admin pages
        if ( false === strpos( $hook, 'rtbcb' ) ) {
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
        
        wp_localize_script( 'rtbcb-admin', 'rtbcb_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'rtbcb_admin_action' )
        ) );
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
        
        // Check for shortcode in widgets
        if ( is_active_widget( false, false, 'text' ) ) {
            $text_widgets = get_option( 'widget_text', array() );
            foreach ( $text_widgets as $widget ) {
                if ( isset( $widget['text'] ) && has_shortcode( $widget['text'], 'rt_business_case_builder' ) ) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode( 'rt_business_case_builder', array( $this, 'render_shortcode' ) );
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
            'show_scenarios' => 'true',
            'enable_analytics' => 'true'
        ), $atts, 'rt_business_case_builder' );
        
        // Security check
        if ( ! $this->verify_shortcode_permissions() ) {
            return '<p>' . __( 'Insufficient permissions to display business case builder.', 'rtbcb' ) . '</p>';
        }
        
        ob_start();
        
        $template_path = RTBCB_PLUGIN_DIR . 'templates/business-case-form.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<p>' . __( 'Business case builder template not found.', 'rtbcb' ) . '</p>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Verify shortcode permissions
     * 
     * @return bool
     */
    private function verify_shortcode_permissions() {
        // Allow public access by default
        return true;
    }
    
    /**
     * Handle AJAX case generation
     */
    public function handle_ajax_generate_case() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rtbcb_generate_case' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security verification failed.', 'rtbcb' )
            ) );
        }
        
        // Rate limiting
        if ( $this->is_rate_limited() ) {
            wp_send_json_error( array(
                'message' => __( 'Too many requests. Please try again later.', 'rtbcb' )
            ) );
        }
        
        // Sanitize and validate inputs
        $validator = $this->get_service( 'validator' );
        if ( ! $validator ) {
            wp_send_json_error( array(
                'message' => __( 'Validation service unavailable.', 'rtbcb' )
            ) );
        }
        
        $inputs = $validator->sanitize_and_validate( $_POST );
        if ( is_wp_error( $inputs ) ) {
            wp_send_json_error( array(
                'message' => $inputs->get_error_message()
            ) );
        }
        
        // Generate business case
        $router = $this->get_service( 'router' );
        if ( ! $router ) {
            wp_send_json_error( array(
                'message' => __( 'Business case generation service unavailable.', 'rtbcb' )
            ) );
        }
        
        $result = $router->generate_comprehensive_case( $inputs );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message()
            ) );
        }
        
        wp_send_json_success( $result );
    }
    
    /**
     * Check if request is rate limited
     * 
     * @return bool
     */
    private function is_rate_limited() {
        if ( ! get_option( 'rtbcb_rate_limit_enabled', true ) ) {
            return false;
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $limit = get_option( 'rtbcb_rate_limit_requests', 60 );
        $window = 3600; // 1 hour
        
        $transient_key = 'rtbcb_rate_limit_' . md5( $ip );
        $requests = get_transient( $transient_key );
        
        if ( false === $requests ) {
            set_transient( $transient_key, 1, $window );
            return false;
        }
        
        if ( $requests >= $limit ) {
            return true;
        }
        
        set_transient( $transient_key, $requests + 1, $window );
        return false;
    }
    
    /**
     * Register admin menu
     */
    /**
     * Admin initialization - basic functionality only
     * Note: Admin menu and pages are handled by RTBCB_Admin class
     */
    public function admin_init() {
        // Register settings
        register_setting( 'rtbcb_settings', 'rtbcb_openai_api_key', array(
            'sanitize_callback' => array( $this, 'sanitize_api_key' )
        ) );
        
        register_setting( 'rtbcb_settings', 'rtbcb_openai_model' );
        register_setting( 'rtbcb_settings', 'rtbcb_enable_logging' );
        register_setting( 'rtbcb_settings', 'rtbcb_data_retention_days' );
    }
    
    /**
     * Sanitize API key
     * 
     * @param string $api_key API key to sanitize
     * @return string Sanitized API key
     */
    public function sanitize_api_key( $api_key ) {
        $api_key = sanitize_text_field( $api_key );
        
        if ( ! empty( $api_key ) && ! preg_match( '/^sk-[a-zA-Z0-9]{48,}$/', $api_key ) ) {
            add_settings_error(
                'rtbcb_openai_api_key',
                'invalid_api_key',
                __( 'Invalid OpenAI API key format.', 'rtbcb' )
            );
            return get_option( 'rtbcb_openai_api_key', '' );
        }
        
        return $api_key;
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // API key configuration notice
        if ( empty( get_option( 'rtbcb_openai_api_key' ) ) ) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>' . sprintf(
                __( '<strong>Business Case Builder:</strong> <a href="%s">Configure your OpenAI API key</a> to enable AI-powered business case generation.', 'rtbcb' ),
                esc_url( admin_url( 'admin.php?page=rtbcb-settings' ) )
            ) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * PHP version notice
     */
    public function php_version_notice() {
        echo '<div class="notice notice-error">';
        echo '<p>' . sprintf(
            __( '<strong>Real Treasury Business Case Builder</strong> requires PHP %s or higher. You are running %s.', 'rtbcb' ),
            esc_html( RTBCB_MIN_PHP_VERSION ),
            esc_html( PHP_VERSION )
        ) . '</p>';
        echo '</div>';
    }
    
    /**
     * WordPress version notice
     */
    public function wp_version_notice() {
        global $wp_version;
        echo '<div class="notice notice-error">';
        echo '<p>' . sprintf(
            __( '<strong>Real Treasury Business Case Builder</strong> requires WordPress %s or higher. You are running %s.', 'rtbcb' ),
            esc_html( RTBCB_MIN_WP_VERSION ),
            esc_html( $wp_version )
        ) . '</p>';
        echo '</div>';
    }
    
    /**
     * Plugin action links
     * 
     * @param array $links Existing action links
     * @return array Modified action links
     */
    public function plugin_action_links( $links ) {
        $plugin_links = array(
            'dashboard' => '<a href="' . esc_url( admin_url( 'admin.php?page=rtbcb-dashboard' ) ) . '">' . __( 'Dashboard', 'rtbcb' ) . '</a>',
            'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=rtbcb-settings' ) ) . '">' . __( 'Settings', 'rtbcb' ) . '</a>',
        );
        
        return array_merge( $plugin_links, $links );
    }
    
    /**
     * Plugin row meta
     * 
     * @param array  $links Plugin row meta
     * @param string $file  Plugin file
     * @return array Modified plugin row meta
     */
    public function plugin_row_meta( $links, $file ) {
        if ( RTBCB_PLUGIN_BASENAME === $file ) {
            $row_meta = array(
                'docs' => '<a href="https://realtreasury.com/docs/business-case-builder" target="_blank">' . __( 'Documentation', 'rtbcb' ) . '</a>',
                'support' => '<a href="https://realtreasury.com/support" target="_blank">' . __( 'Support', 'rtbcb' ) . '</a>',
            );
            
            return array_merge( $links, $row_meta );
        }
        
        return $links;
    }
    
    /**
     * Get service from container
     * 
     * @param string $service_name Service name
     * @return object|null Service instance or null if not found
     */
    public function get_service( $service_name ) {
        return isset( $this->services[ $service_name ] ) ? $this->services[ $service_name ] : null;
    }
    
    /**
     * Register service in container
     * 
     * @param string $service_name Service name
     * @param object $service      Service instance
     */
    public function register_service( $service_name, $service ) {
        $this->services[ $service_name ] = $service;
    }
    
    /**
     * Get plugin data
     * 
     * @param string $key Optional specific data key
     * @return mixed Plugin data
     */
    public function get_plugin_data( $key = null ) {
        if ( empty( $this->plugin_data ) ) {
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
        
        if ( null === $key ) {
            return $this->plugin_data;
        }
        
        return isset( $this->plugin_data[ $key ] ) ? $this->plugin_data[ $key ] : null;
    }
    
    /**
     * Check if plugin is initialized
     * 
     * @return bool
     */
    public function is_initialized() {
        return $this->initialized;
    }
}

/**
 * Get main plugin instance
 * 
 * @return RTBCB_Business_Case_Builder
 */
function rtbcb() {
    return RTBCB_Business_Case_Builder::get_instance();
}

/**
 * Initialize plugin safely
 * Main entry point - completely rebuilt from scratch with safety checks
 */
function rtbcb_safe_init() {
    // Only initialize if WordPress is fully loaded
    if ( ! function_exists( 'add_action' ) ) {
        return;
    }
    
    // Add safety check for critical WordPress functions
    if ( ! function_exists( 'wp_verify_nonce' ) || ! function_exists( 'sanitize_text_field' ) ) {
        return;
    }
    
    try {
        rtbcb();
    } catch ( Exception $e ) {
        // Log the error but don't crash WordPress
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'RTBCB Plugin Error: ' . $e->getMessage() );
        }
        
        // Add admin notice about the error
        if ( is_admin() ) {
            add_action( 'admin_notices', function() use ( $e ) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>Real Treasury Business Case Builder:</strong> Plugin initialization failed. Please check error logs for details.</p>';
                echo '</div>';
            } );
        }
    }
}

// Safe initialization - only if WordPress is loaded
if ( defined( 'ABSPATH' ) ) {
    rtbcb_safe_init();
}