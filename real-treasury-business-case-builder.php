<?php
/**
 * Plugin Name: Real Treasury - Business Case Builder
 * Description: ROI calculator and business case generator for treasury technology.
 * Version: 1.0.0
 * Requires PHP: 7.4
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RTBCB_VERSION', '1.0.0' );
define( 'RTBCB_FILE', __FILE__ );
define( 'RTBCB_URL', plugin_dir_url( RTBCB_FILE ) );
define( 'RTBCB_DIR', plugin_dir_path( RTBCB_FILE ) );

/**
 * Main plugin class.
 */
class Real_Treasury_BCB {
    /**
     * Singleton instance.
     *
     * @var Real_Treasury_BCB|null
     */
    private static $instance = null;

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
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files.
     *
     * @return void
     */
    private function includes() {
        require_once RTBCB_DIR . 'inc/class-rtbcb-settings.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-calculator.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-portal-integration.php';
        require_once RTBCB_DIR . 'inc/class-rtbcb-leads.php';
        require_once RTBCB_DIR . 'admin/class-rtbcb-admin.php';
        new RTBCB_Admin();
        // TODO: Add remaining includes as classes are created.
    }

    /**
     * Initialize hooks.
     *
     * @return void
     */
    private function init_hooks() {
        add_action( 'init', [ $this, 'init' ] );
        add_shortcode( 'rt_business_case_builder', [ $this, 'shortcode_handler' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // Portal integration hooks.
        add_action( 'rt_portal_data_changed', [ $this, 'handle_portal_data_change' ] );
    }

    /**
     * Enqueue frontend assets.
     *
     * @return void
     */
    public function enqueue_assets() {
        if ( is_singular() && has_shortcode( get_post()->post_content, 'rt_business_case_builder' ) ) {
            wp_enqueue_style( 'rtbcb-style', RTBCB_URL . 'public/css/rtbcb.css' );

            wp_enqueue_script( 'rtbcb-script', RTBCB_URL . 'public/js/rtbcb.js', [ 'jquery' ], RTBCB_VERSION, true );

            wp_localize_script(
                'rtbcb-script',
                'RTBCB',
                [
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'rtbcb_nonce' ),
                ]
            );
        }
    }

    /**
     * Shortcode handler.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function shortcode_handler( $atts = [] ) {
        ob_start();
        include RTBCB_DIR . 'templates/business-case-form.php';
        return ob_get_clean();
    }

    /**
     * Plugin initialization.
     *
     * @return void
     */
    public function init() {
        // Initialization tasks.
    }

    /**
     * Handle portal data changes.
     *
     * @return void
     */
    public function handle_portal_data_change() {
        // Logic to handle data changes from the portal.
    }
}

Real_Treasury_BCB::instance();
