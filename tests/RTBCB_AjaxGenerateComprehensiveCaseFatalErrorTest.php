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

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}

if ( ! function_exists( 'wp_get_environment_type' ) ) {
    function wp_get_environment_type() {
        return 'production';
    }
}

if ( ! function_exists( 'rtbcb_log_error' ) ) {
    function rtbcb_log_error( $message, $context = null ) {}
}

if ( ! class_exists( 'RTBCB_LLM' ) ) {
    class RTBCB_LLM {
        public static $mode = 'ok';
        public function generate_comprehensive_business_case( $user_inputs, $scenarios, $rag_context ) {
            if ( 'fatal' === self::$mode ) {
                throw new Error( 'LLM fatal error' );
            }
            return [];
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

if ( ! class_exists( 'Real_Treasury_BCB_Fatal' ) ) {
    class Real_Treasury_BCB_Fatal {
        public function ajax_generate_comprehensive_case() {
            $llm = new RTBCB_LLM();
            try {
                $llm->generate_comprehensive_business_case( [], [], [] );
            } catch ( Error $e ) {
                $error_code      = 'E_LLM_FATAL';
                rtbcb_log_error( $error_code . ': ' . $e->getMessage(), $e->getTraceAsString() );
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
            }
        }
    }
}

final class RTBCB_AjaxGenerateComprehensiveCaseFatalErrorTest extends TestCase {
    public function test_ajax_returns_fatal_error_json_when_llm_throws_error() {
        RTBCB_LLM::$mode = 'fatal';
        $plugin          = new Real_Treasury_BCB_Fatal();
        try {
            $plugin->ajax_generate_comprehensive_case();
            $this->fail( 'Expected RTBCB_JSON_Error was not thrown.' );
        } catch ( RTBCB_JSON_Error $e ) {
            $this->assertSame( 500, $e->status );
            $this->assertSame( 'E_LLM_FATAL', $e->data['data']['error_code'] );
            $this->assertSame(
                'Our AI analysis service is temporarily unavailable. Check the OpenAI API key setting in plugin options.',
                $e->data['data']['message']
            );
        }
    }
}
