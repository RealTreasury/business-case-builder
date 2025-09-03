<?php
defined( 'ABSPATH' ) || exit;

/**
	* Handles background job processing for case generation.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

class RTBCB_Background_Job {
/**
	* Update job status data and accumulate payload.
	*
	* @param string $job_id  Job identifier.
	* @param string $state   New job state.
	* @param array  $payload Additional fields such as step, percent, or partial results.
	* @return void
	*/
	public static function update_status( $job_id, $state, $payload = [] ) {
$current = get_transient( $job_id );
if ( ! is_array( $current ) ) {
$current = [
'payload' => [],
];
}
if ( ! isset( $current['created'] ) ) {
$current['created'] = time();
}
$existing_payload   = isset( $current['payload'] ) && is_array( $current['payload'] ) ? $current['payload'] : [];
$current['payload'] = array_merge( $existing_payload, $payload );
$current['state']   = $state;
$current['updated'] = time();
set_transient( $job_id, $current, HOUR_IN_SECONDS );
}
	/**
	* Enqueue a case generation job.
	*
	* @param array $user_inputs Sanitized user inputs.
	* @return string Job ID.
	*/
public static function enqueue( $user_inputs ) {
	$job_id = uniqid( 'rtbcb_job_', true );
	
	self::update_status( $job_id, 'queued' );
	
	$cron_disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
	
	$scheduled = false;
	if ( ! $cron_disabled ) {
		$scheduled = wp_schedule_single_event(
		time(),
		'rtbcb_process_job',
		[ $job_id, $user_inputs ]
		);
	}
	
	if ( function_exists( 'spawn_cron' ) && ( ! function_exists( 'wp_doing_cron' ) || ! wp_doing_cron() ) && ! $cron_disabled ) {
	spawn_cron();
	}
	
	if ( ! $scheduled || $cron_disabled ) {
	self::process_job( $job_id, $user_inputs );
	}
	
	return $job_id;
}

	/**
	* Process a queued job.
	*
	* @param string $job_id      Job identifier.
	* @param array  $user_inputs User inputs.
	* @return void
	*/
	public static function process_job( $job_id, $user_inputs ) {
	self::update_status( $job_id, 'processing' );
	
	try {
	$basic_roi = RTBCB_Ajax::process_basic_roi_step( $user_inputs );
	
	self::update_status(
	$job_id,
	'processing',
	[
	'step'      => 'basic_roi_calculation',
	'percent'   => 10,
	'basic_roi' => $basic_roi,
	],
	);
	
	add_action(
	'rtbcb_workflow_step_completed',
	function ( $step ) use ( $job_id ) {
	$map = [
	'ai_enrichment'             => 30,
	'enhanced_roi_calculation'  => 50,
	'intelligent_recommendations' => 70,
	'hybrid_rag_analysis'       => 85,
	'data_structuring'          => 95,
	];
	if ( isset( $map[ $step ] ) ) {
	self::update_status(
	$job_id,
	'processing',
	[
	'step'    => $step,
	'percent' => $map[ $step ],
	],
	);
	}
	},
	10,
	1
	);
	
	$result = RTBCB_Ajax::process_comprehensive_case( $user_inputs, $job_id );
	
	if ( is_wp_error( $result ) ) {
	self::update_status(
	$job_id,
	'error',
	[
	'message' => $result->get_error_message(),
	'percent' => 100,
	],
	);
	} else {
	$report_html = '';
	if ( isset( $result['report_data'] ) && function_exists( 'wp_kses' ) ) {
	$report_data = $result['report_data'];
	ob_start();
	include RTBCB_DIR . 'templates/comprehensive-report-template.php';
	$report_html = ob_get_clean();
$report_html = rtbcb_sanitize_report_html( $report_html );
	} else {
	$report_html = '<html></html>';
	}
	
	$upload_dir  = function_exists( 'wp_upload_dir' ) ? wp_upload_dir() : [
	'basedir' => sys_get_temp_dir(),
	'baseurl' => 'http://example.com/uploads',
	];
	$base_dir    = isset( $upload_dir['basedir'] ) ? $upload_dir['basedir'] : sys_get_temp_dir();
	$base_url    = isset( $upload_dir['baseurl'] ) ? $upload_dir['baseurl'] : '';
	$reports_dir = rtrim( $base_dir, '/\\' ) . '/rtbcb-reports';
	if ( ! file_exists( $reports_dir ) ) {
	if ( function_exists( 'wp_mkdir_p' ) ) {
	wp_mkdir_p( $reports_dir );
	} else {
	mkdir( $reports_dir, 0777, true );
	}
	}
        $html_path  = rtrim( $reports_dir, '/\\' ) . '/' . $job_id . '.html';
        file_put_contents( $html_path, $report_html );

        $report_url = rtrim( $base_url, '/\\' ) . '/rtbcb-reports/' . $job_id . '.html';

        if ( function_exists( 'rtbcb_send_report_email' ) ) {
        rtbcb_send_report_email( $user_inputs, $report_url );
        }

        self::update_status(
        $job_id,
        'completed',
        [
        'percent'     => 100,
        'report_html' => $report_html,
        'report_data' => $result['report_data'],
        'report_url'  => $report_url,
        'result'      => $result,
        ],
        );
	}
	} catch ( \Throwable $e ) {
	self::update_status(
	$job_id,
	'error',
	[
	'message' => $e->getMessage(),
	'percent' => 100,
	],
	);
	if ( function_exists( 'rtbcb_log_error' ) ) {
	rtbcb_log_error( 'Job processing error: ' . $e->getMessage(), $e->getTraceAsString() );
	}
	}
	}

/**
	* Get job status data.
	*
	* @param string $job_id Job identifier.
	* @return array|WP_Error Job data or error.
	*/
public static function get_status( $job_id ) {
$data = get_transient( $job_id );
self::cleanup();
if ( false === $data ) {
return new WP_Error( 'not_found', __( 'Job not found.', 'rtbcb' ) );
}
$payload = isset( $data['payload'] ) && is_array( $data['payload'] ) ? $data['payload'] : [];
unset( $data['payload'] );
return array_merge( $data, $payload );
}

/**
	* Cleanup expired, errored, or stale job transients.
	*
	* @return void
	*/
public static function cleanup() {
global $wpdb;

$default_threshold = defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400;
$threshold         = function_exists( 'apply_filters' ) ? (int) apply_filters( 'rtbcb_job_cleanup_threshold', $default_threshold ) : $default_threshold;

if ( isset( $wpdb ) ) {
$like         = $wpdb->esc_like( '_transient_rtbcb_job_' ) . '%';
$option_names = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '{$like}' OR option_name LIKE '_transient_timeout_rtbcb_job_%'" );
foreach ( $option_names as $option_name ) {
$job_id = str_replace( [ '_transient_', '_transient_timeout_' ], '', $option_name );
$data   = get_transient( $job_id );
$created = is_array( $data ) ? ( $data['created'] ?? 0 ) : 0;
if ( false === $data || 'error' === ( $data['state'] ?? '' ) || ( $created && time() - $created > $threshold ) ) {
delete_transient( $job_id );
}
}
} elseif ( isset( $GLOBALS['transients'] ) && is_array( $GLOBALS['transients'] ) ) {
foreach ( array_keys( $GLOBALS['transients'] ) as $job_id ) {
if ( 0 === strpos( $job_id, 'rtbcb_job_' ) ) {
$data    = $GLOBALS['transients'][ $job_id ];
$created = is_array( $data ) ? ( $data['created'] ?? 0 ) : 0;
if ( ! is_array( $data ) || 'error' === ( $data['state'] ?? '' ) || ( $created && time() - $created > $threshold ) ) {
unset( $GLOBALS['transients'][ $job_id ] );
}
}
}
}
}
}

add_action( 'rtbcb_process_job', [ 'RTBCB_Background_Job', 'process_job' ], 10, 2 );
add_action( 'rtbcb_cleanup_jobs', [ 'RTBCB_Background_Job', 'cleanup' ] );
