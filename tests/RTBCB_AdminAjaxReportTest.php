<?php
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! defined( 'RTBCB_DIR' ) ) {
    define( 'RTBCB_DIR', __DIR__ . '/../' );
}

// -----------------------------------------------------------------------------
// WordPress function stubs
// -----------------------------------------------------------------------------

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private $message;

        public function __construct( $code = '', $message = '' ) {
            $this->message = $message;
        }

        public function get_error_message() {
            return $this->message;
        }
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = null ) {
        return $text;
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) {
        return $text;
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url ) {
        return $url;
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return $value;
    }
}

if ( ! function_exists( 'wp_kses_post' ) ) {
    function wp_kses_post( $text ) {
        return $text;
    }
}

if ( ! function_exists( 'wp_kses' ) ) {
    function wp_kses( $text, $allowed_html = [] ) {
        return $text;
    }
}

if ( ! function_exists( 'wp_kses_allowed_html' ) ) {
    function wp_kses_allowed_html( $context = '' ) {
        return [];
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) {
        $key = strtolower( $key );
        return preg_replace( '/[^a-z0-9_]/', '', $key );
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        return $value;
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        // No-op for testing.
    }
}

if ( ! function_exists( 'check_ajax_referer' ) ) {
    function check_ajax_referer( $action, $query_arg ) {
        return true;
    }
}

if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $capability ) {
        return true;
    }
}

if ( ! function_exists( 'rtbcb_get_sample_inputs' ) ) {
    function rtbcb_get_sample_inputs() {
        return [ 'company_name' => 'Acme Corp' ];
    }
}

if ( ! function_exists( 'rtbcb_get_current_company' ) ) {
    function rtbcb_get_current_company() {
        return [ 'company_name' => 'Acme Corp' ];
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( $format ) {
        return '2025-01-01';
    }
}

// -----------------------------------------------------------------------------
// Stub classes
// -----------------------------------------------------------------------------

if ( ! class_exists( 'RTBCB_Calculator' ) ) {
    class RTBCB_Calculator {
        public static function calculate_roi( $inputs ) {
            return [];
        }
    }
}

if ( ! class_exists( 'RTBCB_LLM' ) ) {
    class RTBCB_LLM {
        public function generate_business_case( $inputs, $roi_data ) {
            return [ 'narrative' => 'Sample narrative.' ];
        }
    }
}

class RTBCB_JSON_Response extends Exception {
    public $success;
    public $data;

    public function __construct( $success, $data ) {
        parent::__construct();
        $this->success = $success;
        $this->data    = $data;
    }
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
    function wp_send_json_success( $data = null ) {
        throw new RTBCB_JSON_Response( true, $data );
    }
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
    function wp_send_json_error( $data = null ) {
        throw new RTBCB_JSON_Response( false, $data );
    }
}

// Load plugin classes.
require_once __DIR__ . '/../admin/class-rtbcb-admin.php';
require_once __DIR__ . '/../inc/class-rtbcb-router.php';

// -----------------------------------------------------------------------------
// Test cases
// -----------------------------------------------------------------------------

final class RTBCB_AdminAjaxReportTest extends TestCase {
    protected function setUp(): void {
        $_POST = [];
    }

    public function test_generate_report_preview_returns_html() {
        $_POST['nonce']   = '123';
        $_POST['context'] = json_encode( [ 'narrative' => 'Preview narrative.' ] );

        $admin = new RTBCB_Admin();

        try {
            $admin->ajax_generate_report_preview();
            $this->fail( 'Expected RTBCB_JSON_Response was not thrown.' );
        } catch ( RTBCB_JSON_Response $e ) {
            $this->assertTrue( $e->success );
            $this->assertNotEmpty( $e->data['html'] );
            $this->assertStringContainsString( 'rtbcb-report', $e->data['html'] );
            $this->assertStringContainsString( '<style>', $e->data['html'] );
        }
    }

    public function test_generate_sample_report_returns_html() {
        $_POST['nonce']       = '123';
        $_POST['scenario_key'] = 'enterprise_manufacturer';

        $admin = new RTBCB_Admin();

        try {
            $admin->ajax_generate_sample_report();
            $this->fail( 'Expected RTBCB_JSON_Response was not thrown.' );
        } catch ( RTBCB_JSON_Response $e ) {
            $this->assertTrue( $e->success );
            $this->assertNotEmpty( $e->data['report_html'] );
            $this->assertStringContainsString( 'rtbcb-report', $e->data['report_html'] );
        }
    }
}

