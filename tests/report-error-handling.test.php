<?php
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private $code;
        private $message;
        public function __construct( $code = '', $message = '' ) {
            $this->code    = $code;
            $this->message = $message;
        }
        public function get_error_message() { return $this->message; }
        public function get_error_code() { return $this->code; }
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $text ) { return is_string( $text ) ? trim( $text ) : $text; }
}

if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $email ) { return filter_var( $email, FILTER_SANITIZE_EMAIL ); }
}

if ( ! function_exists( 'is_email' ) ) {
    function is_email( $email ) { return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL ); }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) { return $value; }
}

if ( ! function_exists( 'check_ajax_referer' ) ) {
    function check_ajax_referer( $action, $query_arg = false, $die = true ) { return true; }
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action ) { return true; }
}

class RTBCB_JSON_Error extends Exception {
    public $data;
    public $status;
    public function __construct( $data, $status ) {
        parent::__construct();
        $this->data   = $data;
        $this->status = $status;
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

if ( ! function_exists( 'wp_send_json_success' ) ) {
    function wp_send_json_success( $data = null, $status_code = null ) {
        return [
            'success' => true,
            'data'    => $data,
            'status'  => $status_code,
        ];
    }
}

class RTBCB_Ajax {
    public static function generate_comprehensive_case() {
        if ( ! check_ajax_referer( 'rtbcb_generate', 'rtbcb_nonce', false ) ) {
            wp_send_json_error( 'Security check failed.', 403 );
            return;
        }
        $inputs = self::collect_and_validate_user_inputs();
        if ( is_wp_error( $inputs ) ) {
            wp_send_json_error( $inputs->get_error_message(), 400 );
            return;
        }
        wp_send_json_success( [ 'job_id' => 'demo' ] );
    }

    private static function collect_and_validate_user_inputs() {
        $email        = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $company_name = sanitize_text_field( wp_unslash( $_POST['company_name'] ?? '' ) );
        $hours        = wp_unslash( $_POST['hours_reconciliation'] ?? '' );

        if ( empty( $company_name ) ) {
            return new WP_Error( 'missing_company_name', 'Company name is required.' );
        }
        if ( empty( $email ) || ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', 'Invalid email address.' );
        }
        if ( ! is_numeric( $hours ) ) {
            return new WP_Error( 'invalid_hours_reconciliation', 'Hours reconciliation must be numeric.' );
        }

        return [
            'email'                => $email,
            'company_name'         => $company_name,
            'hours_reconciliation' => (float) $hours,
        ];
    }
}

class RTBCB_Validator {
    public function validate( array $data ): array {
        $email        = sanitize_email( $data['email'] ?? '' );
        $company_name = sanitize_text_field( $data['company_name'] ?? '' );
        $company_size = sanitize_text_field( $data['company_size'] ?? '' );
        $hours        = $data['hours_reconciliation'] ?? '';

        if ( empty( $company_name ) ) {
            return [ 'error' => 'Company name is required.' ];
        }
        if ( empty( $email ) || ! is_email( $email ) ) {
            return [ 'error' => 'Invalid email address.' ];
        }
        if ( empty( $company_size ) ) {
            return [ 'error' => 'Company size is required.' ];
        }
        if ( ! is_numeric( $hours ) ) {
            return [ 'error' => 'Hours reconciliation must be numeric.' ];
        }

        return [
            'email'                => $email,
            'company_name'         => $company_name,
            'company_size'         => $company_size,
            'hours_reconciliation' => (float) $hours,
        ];
    }
}

class RTBCB_Router {
    public function handle_form_submission( $report_type = 'basic' ) {
        if ( ! isset( $_POST['rtbcb_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rtbcb_nonce'] ) ), 'rtbcb_generate' ) ) {
            wp_send_json_error( [ 'message' => 'Nonce verification failed.' ], 403 );
            return;
        }

        $validator      = new RTBCB_Validator();
        $validated_data = $validator->validate( $_POST );
        if ( isset( $validated_data['error'] ) ) {
            wp_send_json_error( [ 'message' => $validated_data['error'] ], 400 );
            return;
        }

        wp_send_json_success( [ 'status' => 'ok' ] );
    }
}

function run_ajax_test( $post_data, $expected_message ) {
    $_POST = $post_data;
    try {
        RTBCB_Ajax::generate_comprehensive_case();
        echo "AJAX test failed: expected error not thrown\n";
        exit( 1 );
    } catch ( RTBCB_JSON_Error $e ) {
        if ( 400 !== $e->status || $expected_message !== $e->data['data'] ) {
            echo "AJAX test failed: unexpected response\n";
            exit( 1 );
        }
    }
}

function run_router_test( $post_data, $expected_message ) {
    $_POST  = $post_data;
    $router = new RTBCB_Router();
    try {
        $router->handle_form_submission();
        echo "Router test failed: expected error not thrown\n";
        exit( 1 );
    } catch ( RTBCB_JSON_Error $e ) {
        if ( 400 !== $e->status || $expected_message !== $e->data['data']['message'] ) {
            echo "Router test failed: unexpected response\n";
            exit( 1 );
        }
    }
}

// Missing required field
run_ajax_test(
    [ 'rtbcb_nonce' => 'n', 'email' => 'user@example.com', 'hours_reconciliation' => '10' ],
    'Company name is required.'
);
run_router_test(
    [ 'rtbcb_nonce' => 'n', 'email' => 'user@example.com', 'company_size' => 'small', 'hours_reconciliation' => '10' ],
    'Company name is required.'
);

// Invalid email
run_ajax_test(
    [ 'rtbcb_nonce' => 'n', 'company_name' => 'Acme', 'email' => 'invalid', 'hours_reconciliation' => '10' ],
    'Invalid email address.'
);
run_router_test(
    [ 'rtbcb_nonce' => 'n', 'company_name' => 'Acme', 'company_size' => 'small', 'email' => 'invalid', 'hours_reconciliation' => '10' ],
    'Invalid email address.'
);

// Malformed numeric
run_ajax_test(
    [ 'rtbcb_nonce' => 'n', 'company_name' => 'Acme', 'email' => 'user@example.com', 'hours_reconciliation' => 'foo' ],
    'Hours reconciliation must be numeric.'
);
run_router_test(
    [ 'rtbcb_nonce' => 'n', 'company_name' => 'Acme', 'company_size' => 'small', 'email' => 'user@example.com', 'hours_reconciliation' => 'foo' ],
    'Hours reconciliation must be numeric.'
);

$_POST = [];

echo "report-error-handling.test.php passed\n";
