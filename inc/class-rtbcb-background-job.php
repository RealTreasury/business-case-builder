<?php
defined( 'ABSPATH' ) || exit;

/**
 * Handles background job processing for case generation.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

class RTBCB_Background_Job {
/**
 * Update job status data.
 *
 * @param string $job_id Job identifier.
 * @param string $status  New status.
 * @param array  $extra   Extra data such as step, message, percent, or result.
 * @return void
 */
public static function update_status( $job_id, $status, $extra = [] ) {
$current = get_transient( $job_id );
if ( ! is_array( $current ) ) {
$current = [];
}

$new_data = array_merge( $current, $extra, [ 'status' => $status ] );
set_transient( $job_id, $new_data, HOUR_IN_SECONDS );
}
	/**
	 * Enqueue a case generation job.
	 *
	 * @param array $user_inputs Sanitized user inputs.
	 * @return string Job ID.
	 */
	public static function enqueue( $user_inputs ) {
		$job_id = uniqid( 'rtbcb_job_', true );

self::update_status(
$job_id,
'queued',
[
'result' => null,
]
);

                wp_schedule_single_event(
                        time(),
                        'rtbcb_process_job',
                        [ $job_id, $user_inputs ]
                );

		// Trigger cron immediately in a non-blocking way.
		if ( function_exists( 'spawn_cron' ) && ! wp_doing_cron() ) {
			spawn_cron();
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

		$basic_roi = RTBCB_Ajax::process_basic_roi_step( $user_inputs );

		self::update_status(
			$job_id,
			'processing',
			[
				'step'    => 'basic_roi_calculation',
				'percent' => 10,
				'result'  => $basic_roi,
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
]
);
}
},
10,
1
);

$result = RTBCB_Ajax::process_comprehensive_case( $user_inputs );

if ( is_wp_error( $result ) ) {
self::update_status(
$job_id,
'error',
[
'message' => $result->get_error_message(),
'percent' => 100,
]
);
} else {
self::update_status(
$job_id,
'completed',
[
'result'  => $result,
'percent' => 100,
]
);
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

		if ( false === $data ) {
			return new WP_Error( 'not_found', __( 'Job not found.', 'rtbcb' ) );
		}

return $data;
}

/**
 * Cleanup completed or expired job transients.
 *
 * @return void
 */
public static function cleanup() {
global $wpdb;

if ( isset( $wpdb ) ) {
$like         = $wpdb->esc_like( '_transient_rtbcb_job_' ) . '%';
$option_names = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '{$like}' OR option_name LIKE '_transient_timeout_rtbcb_job_%'" );
foreach ( $option_names as $option_name ) {
$job_id = str_replace( [ '_transient_', '_transient_timeout_' ], '', $option_name );
$data   = get_transient( $job_id );
if ( false === $data || in_array( $data['status'] ?? '', [ 'completed', 'error' ], true ) ) {
delete_transient( $job_id );
}
}
} elseif ( isset( $GLOBALS['transients'] ) && is_array( $GLOBALS['transients'] ) ) {
foreach ( array_keys( $GLOBALS['transients'] ) as $job_id ) {
if ( 0 === strpos( $job_id, 'rtbcb_job_' ) ) {
$data = $GLOBALS['transients'][ $job_id ];
if ( ! is_array( $data ) || in_array( $data['status'] ?? '', [ 'completed', 'error' ], true ) ) {
unset( $GLOBALS['transients'][ $job_id ] );
}
}
}
}
}
}

add_action( 'rtbcb_process_job', [ 'RTBCB_Background_Job', 'process_job' ], 10, 2 );
add_action( 'rtbcb_cleanup_jobs', [ 'RTBCB_Background_Job', 'cleanup' ] );
