<?php
use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private $code;
        private $message;
        public function __construct( $code = '', $message = '' ) {
            $this->code    = $code;
            $this->message = $message;
        }
        public function get_error_message() {
            return $this->message;
        }
        public function get_error_code() {
            return $this->code;
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

if ( ! function_exists( 'rtbcb_get_user_friendly_error' ) ) {
    function rtbcb_get_user_friendly_error( $code ) {
        $messages = [
            'generation_failed' => 'Failed to generate business case. Please try again.',
            'no_api_key'        => 'API configuration is missing. Please contact support.',
        ];

        return $messages[ $code ] ?? '';
    }
}

if ( ! function_exists( 'rtbcb_send_standardized_error' ) ) {
    function rtbcb_send_standardized_error( $error_code, $user_message, $status = 500 ) {
        wp_send_json_error(
            [
                'code'    => $error_code,
                'message' => $user_message,
            ],
            $status
        );
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

if ( ! function_exists( 'rtbcb_log_error' ) ) {
    function rtbcb_log_error( $message, $context = [] ) {}
}

if ( ! class_exists( 'RTBCB_LLM' ) ) {
    class RTBCB_LLM {
        public static $mode = 'generic';
        public function generate_comprehensive_business_case( $user_inputs, $scenarios, $rag_context ) {
            if ( 'no_api_key' === self::$mode ) {
                return new WP_Error( 'no_api_key', 'OpenAI API key not configured.' );
            }
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

if ( ! class_exists( 'RTBCB_Plugin' ) ) {
    class RTBCB_Plugin {
        public function ajax_generate_comprehensive_case() {
            $llm                  = new RTBCB_LLM();
            $comprehensive_analysis = $llm->generate_comprehensive_business_case( [], [], [] );
            if ( is_wp_error( $comprehensive_analysis ) ) {
                $error_message = $comprehensive_analysis->get_error_message();
                $error_code    = $comprehensive_analysis->get_error_code();
                rtbcb_log_error( 'LLM generation failed', [ 'code' => $error_code, 'message' => $error_message ] );
                if ( 'no_api_key' === $error_code ) {
                    rtbcb_send_standardized_error(
                        'no_api_key',
                        rtbcb_get_user_friendly_error( 'no_api_key' ),
                        400
                    );
                }

                rtbcb_send_standardized_error(
                    'generation_failed',
                    rtbcb_get_user_friendly_error( 'generation_failed' ),
                    500
                );
            }
        }
    }
}

final class RTBCB_AjaxGenerateComprehensiveCaseErrorTest extends TestCase {
    public function test_ajax_returns_error_json_when_llm_fails() {
        RTBCB_LLM::$mode = 'generic';
        $plugin          = new RTBCB_Plugin();
        try {
            $plugin->ajax_generate_comprehensive_case();
            $this->fail( 'Expected RTBCB_JSON_Error was not thrown.' );
        } catch ( RTBCB_JSON_Error $e ) {
            $this->assertSame( 500, $e->status );
            $this->assertSame(
                [
                    'success' => false,
                    'data'    => [
                        'code'    => 'generation_failed',
                        'message' => 'Failed to generate business case. Please try again.',
                    ],
                ],
                $e->data
            );
        }
    }

    public function test_ajax_returns_api_key_error_when_missing() {
        RTBCB_LLM::$mode = 'no_api_key';
        $plugin          = new RTBCB_Plugin();
        try {
            $plugin->ajax_generate_comprehensive_case();
            $this->fail( 'Expected RTBCB_JSON_Error was not thrown.' );
        } catch ( RTBCB_JSON_Error $e ) {
            $this->assertSame( 400, $e->status );
            $this->assertSame(
                [
                    'success' => false,
                    'data'    => [
                        'code'    => 'no_api_key',
                        'message' => 'API configuration is missing. Please contact support.',
                    ],
                ],
                $e->data
            );
        }
    }
}
