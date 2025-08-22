<?php
use PHPUnit\Framework\TestCase;

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

if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action ) {
        return true;
    }
}

if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $email ) {
        return $email;
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $text ) {
        return $text;
    }
}

if ( ! function_exists( 'is_email' ) ) {
    function is_email( $email ) {
        return filter_var( $email, FILTER_VALIDATE_EMAIL );
    }
}

if ( ! function_exists( 'wp_get_environment_type' ) ) {
    function wp_get_environment_type() {
        return 'production';
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}

if ( ! function_exists( 'rtbcb_increase_memory_limit' ) ) {
    function rtbcb_increase_memory_limit() {}
}

if ( ! function_exists( 'rtbcb_log_memory_usage' ) ) {
    function rtbcb_log_memory_usage( $stage ) {}
}

if ( ! class_exists( 'RTBCB_LLM' ) ) {
    class RTBCB_LLM {
        public function generate_comprehensive_business_case( $user_inputs, $scenarios, $rag_context ) {
            return new WP_Error( 'llm_error', 'LLM failed' );
        }
    }
}

if ( ! class_exists( 'RTBCB_JSON_Error' ) ) {
    class RTBCB_JSON_Error extends Exception {
        public $data;
        public $status;
        public function __construct( $data, $status ) {
            parent::__construct();
            $this->data   = $data;
            $this->status = $status;
        }
    }
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
    function wp_send_json_error( $data = null, $status_code = null ) {
        throw new RTBCB_JSON_Error(
            [
                'success' => false,
                'data'    => $data,
            ],
            $status_code
        );
    }
}

if ( ! class_exists( 'Real_Treasury_BCB' ) ) {
    class Real_Treasury_BCB {
        public function ajax_generate_comprehensive_case() {
            $llm = new RTBCB_LLM();
            $comprehensive_analysis = $llm->generate_comprehensive_business_case( [], [], [] );
            if ( is_wp_error( $comprehensive_analysis ) ) {
                $error_message    = $comprehensive_analysis->get_error_message();
                $response_message = __( 'Failed to generate business case analysis.', 'rtbcb' );
                if ( function_exists( 'wp_get_environment_type' ) && 'production' !== wp_get_environment_type() ) {
                    $response_message = $error_message;
                }
                wp_send_json_error( [ 'message' => $response_message ], 500 );
            }
        }
    }
}

final class RTBCB_AjaxGenerateComprehensiveCaseErrorTest extends TestCase {
    public function test_ajax_returns_error_json_when_llm_fails() {
        $plugin = new Real_Treasury_BCB();
        try {
            $plugin->ajax_generate_comprehensive_case();
            $this->fail( 'Expected RTBCB_JSON_Error was not thrown.' );
        } catch ( RTBCB_JSON_Error $e ) {
            $this->assertSame( 500, $e->status );
            $this->assertSame(
                [
                    'success' => false,
                    'data'    => [ 'message' => 'Failed to generate business case analysis.' ],
                ],
                $e->data
            );
        }
    }
}
