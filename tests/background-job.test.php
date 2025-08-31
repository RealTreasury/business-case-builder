<?php
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

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $name, $value, $expiration ) {
		global $transients, $transient_log;
		$transients[ $name ]     = $value;
		$transient_log[ $name ][] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $name ) {
		global $transients;
		return $transients[ $name ] ?? false;
	}
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
	function wp_schedule_single_event( $timestamp, $hook, $args ) {
		global $scheduled_events;
		$scheduled_events[] = [
			'timestamp' => $timestamp,
			'hook'      => $hook,
			'args'      => $args,
		];
	}
}

if ( ! function_exists( 'spawn_cron' ) ) {
	function spawn_cron() {
		global $spawned_cron;
		$spawned_cron = true;
	}
}

if ( ! function_exists( 'wp_doing_cron' ) ) {
	function wp_doing_cron() {
		return false;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! class_exists( 'RTBCB_Ajax' ) ) {
	class RTBCB_Ajax {
		public static $mode = 'success';
		public static function process_comprehensive_case( $user_inputs, $job_id = '' ) {
			if ( 'error' === self::$mode ) {
				return new WP_Error( 'failed', 'Processing failed.' );
			}
			if ( $job_id ) {
				RTBCB_Background_Job::update_status( $job_id, 'processing', [ 'basic_roi' => 123 ] );
			}
			return [ 'result' => 'ok' ];
		}
	}
}

require_once __DIR__ . '/../inc/class-rtbcb-background-job.php';

function assert_true( $condition, $message ) {
	if ( ! $condition ) {
		echo $message . "\n";
		exit( 1 );
	}
}

// Successful job flow.
$user_inputs = [ 'email' => 'test@example.com' ];
$job_id      = RTBCB_Background_Job::enqueue( $user_inputs );
assert_true( 'queued' === get_transient( $job_id )['status'], 'Job not queued' );

RTBCB_Background_Job::process_job( $job_id, $user_inputs );

global $transient_log;
$statuses = array_column( $transient_log[ $job_id ], 'status' );
assert_true( $statuses === [ 'queued', 'processing', 'processing', 'completed' ], 'Status flow incorrect: ' . json_encode( $statuses ) );
$status = RTBCB_Background_Job::get_status( $job_id );
assert_true( 123 === $status['basic_roi'], 'Partial data missing' );
assert_true( 'completed' === $status['status'], 'Job not completed' );

// Error job flow.
RTBCB_Ajax::$mode = 'error';
$job_id2          = RTBCB_Background_Job::enqueue( $user_inputs );
RTBCB_Background_Job::process_job( $job_id2, $user_inputs );
$status   = RTBCB_Background_Job::get_status( $job_id2 );
$statuses = array_column( $transient_log[ $job_id2 ], 'status' );
assert_true( $statuses === [ 'queued', 'processing', 'error' ], 'Error status flow incorrect: ' . json_encode( $statuses ) );
assert_true( 'error' === $status['status'], 'Job did not error' );
assert_true( 'Processing failed.' === $status['message'], 'Error message missing' );

echo "background-job.test.php passed\n";
