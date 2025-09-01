<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
defined( 'ABSPATH' ) || exit;

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

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $name ) {
		global $transients;
		unset( $transients[ $name ] );
	}
}

global $sent_report_email;
$sent_report_email = [];
if ( ! function_exists( 'rtbcb_send_report_email' ) ) {
	function rtbcb_send_report_email( $form_data, $path ) {
		global $sent_report_email;
		$sent_report_email = [
			'form_data' => $form_data,
			'path'      => $path,
		];
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

global $wp_actions;
$wp_actions = [];

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		global $wp_actions;
		$wp_actions[ $hook ][] = [
			'callback'      => $callback,
			'accepted_args' => $accepted_args,
		];
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {
		global $wp_actions;
		if ( isset( $wp_actions[ $hook ] ) ) {
			foreach ( $wp_actions[ $hook ] as $action ) {
				call_user_func_array( $action['callback'], array_slice( $args, 0, $action['accepted_args'] ) );
			}
		}
	}
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! class_exists( 'RTBCB_Ajax' ) ) {
	class RTBCB_Ajax {
                public static $mode = 'success';
                public static function process_basic_roi_step( $user_inputs ) {
                        return [ 'financial_analysis' => [] ];
                }
                public static function process_comprehensive_case( $user_inputs, $job_id ) {
                        do_action( 'rtbcb_workflow_step_completed', 'ai_enrichment' );
                        RTBCB_Background_Job::update_status( $job_id, 'processing', [ 'enriched_profile' => [] ] );
                        do_action( 'rtbcb_workflow_step_completed', 'enhanced_roi_calculation' );
                        RTBCB_Background_Job::update_status( $job_id, 'processing', [ 'enhanced_roi' => [] ] );
                        do_action( 'rtbcb_workflow_step_completed', 'intelligent_recommendations' );
                        RTBCB_Background_Job::update_status( $job_id, 'processing', [ 'category' => 'cat' ] );
                        do_action( 'rtbcb_workflow_step_completed', 'hybrid_rag_analysis' );
                        RTBCB_Background_Job::update_status( $job_id, 'processing', [ 'analysis' => [] ] );
                        do_action( 'rtbcb_workflow_step_completed', 'data_structuring' );
                        RTBCB_Background_Job::update_status( $job_id, 'processing', [ 'report_data' => [] ] );
                        if ( 'throw' === self::$mode ) {
                                throw new Exception( 'Explosion' );
                        }
                        if ( 'error' === self::$mode ) {
                                return new WP_Error( 'failed', 'Processing failed.' );
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
assert_true( 'queued' === get_transient( $job_id )['state'], 'Job not queued' );

RTBCB_Background_Job::process_job( $job_id, $user_inputs );

global $transient_log;
$statuses = array_column( $transient_log[ $job_id ], 'state' );
assert_true( $statuses[0] === 'queued' && end( $statuses ) === 'completed', 'Status flow incorrect: ' . json_encode( $statuses ) );
assert_true( $transient_log[ $job_id ][2]['payload']['step'] === 'basic_roi_calculation', 'First step missing' );
$final = RTBCB_Background_Job::get_status( $job_id );
assert_true( isset( $final['basic_roi'] ), 'basic_roi missing' );
assert_true( isset( $final['category'] ), 'category missing' );
assert_true( 'completed' === get_transient( $job_id )['state'], 'Job not completed' );
global $sent_report_email;
assert_true( isset( $final['download_url'] ), 'download_url missing' );
assert_true( ! empty( $sent_report_email['path'] ), 'Report email not sent' );

// Error job flow.
RTBCB_Ajax::$mode = 'error';
$job_id2          = RTBCB_Background_Job::enqueue( $user_inputs );
RTBCB_Background_Job::process_job( $job_id2, $user_inputs );
$status   = RTBCB_Background_Job::get_status( $job_id2 );
$statuses = array_column( $transient_log[ $job_id2 ], 'state' );
assert_true( $statuses[0] === 'queued' && end( $statuses ) === 'error', 'Error status flow incorrect: ' . json_encode( $statuses ) );
assert_true( 'error' === $status['state'], 'Job did not error' );
assert_true( 'Processing failed.' === $status['message'], 'Error message missing' );

// Exception job flow.
RTBCB_Ajax::$mode = 'throw';
$job_id6          = RTBCB_Background_Job::enqueue( $user_inputs );
RTBCB_Background_Job::process_job( $job_id6, $user_inputs );
$status = RTBCB_Background_Job::get_status( $job_id6 );
assert_true( 'error' === $status['state'], 'Exception not handled' );
assert_true( 'Explosion' === $status['message'], 'Exception message missing' );

// Cleanup test.
$job_id3 = RTBCB_Background_Job::enqueue( $user_inputs );
RTBCB_Background_Job::cleanup();
assert_true( get_transient( $job_id ) !== false, 'Completed job cleaned too early' );
assert_true( false === get_transient( $job_id2 ), 'Errored job not cleaned' );
assert_true( get_transient( $job_id3 ) !== false, 'Queued job incorrectly cleaned' );

// Old job removal.
$job_id4                 = RTBCB_Background_Job::enqueue( $user_inputs );
$transients[ $job_id4 ]['created'] = time() - DAY_IN_SECONDS - 1;
RTBCB_Background_Job::cleanup();
assert_true( false === get_transient( $job_id4 ), 'Old job not cleaned' );

// Cleanup on status retrieval.
$job_id5 = RTBCB_Background_Job::enqueue( $user_inputs );
RTBCB_Background_Job::update_status( $job_id5, 'error' );
RTBCB_Background_Job::get_status( $job_id3 );
assert_true( false === get_transient( $job_id5 ), 'Errored job not cleaned during status retrieval' );

echo "background-job.test.php passed\n";
