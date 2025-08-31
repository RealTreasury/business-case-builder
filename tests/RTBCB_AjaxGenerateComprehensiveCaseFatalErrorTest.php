<?php
use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private $code;
        private $message;
        private $data;
        public function __construct( $code = '', $message = '', $data = [] ) {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = $data;
        }
        public function get_error_message() {
            return $this->message;
        }
        public function get_error_code() {
            return $this->code;
        }
        public function get_error_data() {
            return $this->data;
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

if ( ! function_exists( 'rtbcb_is_openai_configuration_error' ) ) {
    function rtbcb_is_openai_configuration_error( $e ) {
        $message = strtolower( $e->getMessage() );
        return false !== strpos( $message, 'api key' ) || false !== strpos( $message, 'model' );
    }
}

if ( ! class_exists( 'RTBCB_LLM' ) ) {
    class RTBCB_LLM {
        public static $mode = 'ok';
        public function generate_comprehensive_business_case( $user_inputs, $scenarios, $context_fetcher ) {
            if ( 'fatal_config' === self::$mode ) {
                throw new Error( 'Missing API key' );
            }
            if ( 'fatal_internal' === self::$mode ) {
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
                $llm->generate_comprehensive_business_case( [], [], null );
            } catch ( Error $e ) {
                $error_code    = 'E_LLM_FATAL';
                $error_message = $e->getMessage();

                rtbcb_log_error( $error_code . ': ' . $error_message, $e->getTraceAsString() );

                if ( rtbcb_is_openai_configuration_error( $e ) ) {
                    $guidance        = __( 'Check the OpenAI API key setting in plugin options.', 'rtbcb' );
                    $response_message = __( 'Our AI analysis service is temporarily unavailable.', 'rtbcb' ) . ' ' . $guidance;
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
            }
        }
    }
}

final class RTBCB_AjaxGenerateComprehensiveCaseFatalErrorTest extends TestCase {
    public function test_ajax_returns_guidance_when_configuration_error() {
        RTBCB_LLM::$mode = 'fatal_config';
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

    public function test_ajax_returns_generic_message_when_internal_error() {
        RTBCB_LLM::$mode = 'fatal_internal';
        $plugin          = new Real_Treasury_BCB_Fatal();
        try {
            $plugin->ajax_generate_comprehensive_case();
            $this->fail( 'Expected RTBCB_JSON_Error was not thrown.' );
        } catch ( RTBCB_JSON_Error $e ) {
            $this->assertSame( 500, $e->status );
            $this->assertSame( 'E_LLM_FATAL', $e->data['data']['error_code'] );
            $this->assertSame(
                'Internal error. Please try again later.',
                $e->data['data']['message']
            );
        }
    }
}
