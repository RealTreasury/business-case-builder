<?php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

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
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $text ) {
        return is_string( $text ) ? trim( $text ) : '';
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return $value;
    }
}

if ( ! function_exists( 'check_ajax_referer' ) ) {
    function check_ajax_referer( $action, $query_arg = false, $die = true ) {
        return true;
    }
}

if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $url ) {
        return $url;
    }
}

class RTBCB_JSON_Response extends Exception {
    public $data;
    public function __construct( $data ) {
        parent::__construct();
        $this->data = $data;
    }
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
    function wp_send_json_success( $data = null ) {
        throw new RTBCB_JSON_Response(
            [
                'success' => true,
                'data'    => $data,
            ]
        );
    }
}

class RTBCB_Background_Job {
    public static $data = [];
    public static function get_status( $job_id ) {
        return self::$data[ $job_id ] ?? new WP_Error( 'not_found', 'Job not found.' );
    }
}

require_once __DIR__ . '/../inc/class-rtbcb-ajax.php';

function assert_same( $expected, $actual, $message ) {
    if ( $expected !== $actual ) {
        echo $message . "\n";
        exit( 1 );
    }
}

$_GET['rtbcb_nonce'] = 'nonce';
$_GET['job_id']      = 'job1';
RTBCB_Background_Job::$data['job1'] = [
    'status'  => 'processing',
    'step'    => 'enrich',
    'message' => 'Working',
    'percent' => 50,
];
try {
    RTBCB_Ajax::get_job_status();
} catch ( RTBCB_JSON_Response $e ) {
    $data = $e->data;
}
assert_same( true, $data['success'], 'Expected success true' );
assert_same( 'processing', $data['data']['status'], 'Processing status mismatch' );
assert_same( 'enrich', $data['data']['step'], 'Step mismatch' );
assert_same( 'Working', $data['data']['message'], 'Message mismatch' );
assert_same( 50.0, $data['data']['percent'], 'Percent mismatch' );

$_GET['job_id'] = 'job2';
RTBCB_Background_Job::$data['job2'] = [
    'status' => 'completed',
    'result' => [ 'report_data' => [ 'foo' => 'bar' ], 'lead_id' => 5 ],
    'download_url' => 'https://example.com/report.pdf',
];
try {
    RTBCB_Ajax::get_job_status();
} catch ( RTBCB_JSON_Response $e ) {
    $data = $e->data;
}
assert_same( 'completed', $data['data']['status'], 'Completed status mismatch' );
assert_same( [ 'foo' => 'bar' ], $data['data']['report_data'], 'Report data missing' );
assert_same( 5, $data['data']['lead_id'], 'Lead ID mismatch' );
assert_same( 'https://example.com/report.pdf', $data['data']['download_url'], 'Download URL mismatch' );

$_GET['job_id'] = 'missing';
try {
    RTBCB_Ajax::get_job_status();
} catch ( RTBCB_JSON_Response $e ) {
    $data = $e->data;
}
assert_same( 'error', $data['data']['status'], 'Missing job not error' );
assert_same( 'Job not found.', $data['data']['message'], 'Missing job message mismatch' );

echo "job-status.test.php passed\n";
