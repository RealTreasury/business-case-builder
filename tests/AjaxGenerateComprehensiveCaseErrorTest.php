<?php
use PHPUnit\Framework\TestCase;

// Stub WordPress environment functions and classes.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

function plugin_dir_url( $file ) {
    return '/';
}

function plugin_dir_path( $file ) {
    return __DIR__ . '/../';
}

function get_file_data( $file, $headers ) {
    return [];
}

function register_activation_hook( $file, $callback ) {}
function register_deactivation_hook( $file, $callback ) {}
function register_uninstall_hook( $file, $callback ) {}
function add_action( $hook, $callback ) {}
function add_shortcode( $tag, $func ) {}
function add_filter( $tag, $func, $priority = 10, $accepted_args = 1 ) {}
function plugin_basename( $file ) {
    return $file;
}
function admin_url( $path = '' ) {
    return $path;
}

function rtbcb_increase_memory_limit() {}
function rtbcb_log_memory_usage( $stage ) {}
function sanitize_email( $email ) {
    return $email;
}
function sanitize_text_field( $text ) {
    return $text;
}
function wp_verify_nonce( $nonce, $action ) {
    return true;
}
function is_email( $email ) {
    return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );
}
function __( $text, $domain = 'default' ) {
    return $text;
}

class WP_Error {
    public $code;
    public $message;

    public function __construct( $code = '', $message = '' ) {
        $this->code    = $code;
        $this->message = $message;
    }

    public function get_error_message() {
        return $this->message;
    }
}

function is_wp_error( $thing ) {
    return $thing instanceof WP_Error;
}

class WPJsonErrorException extends Exception {
    public $data;
    public $status_code;

    public function __construct( $data, $status_code ) {
        parent::__construct();
        $this->data        = $data;
        $this->status_code = $status_code;
    }
}

$GLOBALS['wp_error_response'] = null;
function wp_send_json_error( $data, $status_code = null ) {
    global $wp_error_response;
    if ( null === $wp_error_response ) {
        $wp_error_response = [ 'success' => false, 'data' => $data ];
    }
    throw new WPJsonErrorException( $wp_error_response, $status_code );
}

// Minimal stub classes used by the AJAX handler.
class RTBCB_Calculator {
    public static function calculate_roi( $inputs ) {
        return [
            'conservative' => [ 'total_annual_benefit' => 0, 'labor_savings' => 0, 'fee_savings' => 0, 'error_reduction' => 0 ],
            'base'         => [ 'total_annual_benefit' => 0, 'labor_savings' => 0, 'fee_savings' => 0, 'error_reduction' => 0 ],
            'optimistic'   => [ 'total_annual_benefit' => 0, 'labor_savings' => 0, 'fee_savings' => 0, 'error_reduction' => 0 ],
        ];
    }
}

class RTBCB_Category_Recommender {
    public static function recommend_category( $inputs ) {
        return [ 'recommended' => 'test' ];
    }
}

class RTBCB_RAG {
    public function search_similar( $query, $n ) {
        return [];
    }
}

class RTBCB_LLM {
    public function generate_comprehensive_business_case( $inputs, $scenarios, $rag_context ) {
        return new WP_Error( 'llm_error', 'LLM failure' );
    }
}

// Load the plugin class without running its bootstrap.
$code = file_get_contents( __DIR__ . '/../real-treasury-business-case-builder.php' );
$code = preg_replace( '/Real_Treasury_BCB::instance\(\);/', '', $code );
eval( '?>' . $code );

class AjaxGenerateComprehensiveCaseErrorTest extends TestCase {
    /**
     * Ensure WP_Error from LLM returns proper JSON and status code.
     */
    public function test_ajax_returns_error_json_on_llm_failure() {
        $_POST = [
            'rtbcb_nonce'    => 'test',
            'email'          => 'test@example.com',
            'company_name'   => 'Test Co',
            'company_size'   => '1-10',
            'pain_points'    => [ 'pain' ],
        ];

        $ref     = new ReflectionClass( Real_Treasury_BCB::class );
        $handler = $ref->newInstanceWithoutConstructor();

        set_error_handler(
            function ( $errno, $errstr ) {
                if ( false !== strpos( $errstr, 'Cannot modify header information' ) ) {
                    return true;
                }
                return false;
            }
        );

        ob_start();
        try {
            $handler->ajax_generate_comprehensive_case();
            $this->fail( 'Expected WPJsonErrorException was not thrown.' );
        } catch ( WPJsonErrorException $e ) {
            $this->assertSame( 500, $e->status_code );
            $this->assertSame(
                [ 'success' => false, 'data' => [ 'message' => 'Failed to generate business case analysis.' ] ],
                $e->data
            );
        }
        restore_error_handler();
    }
}
