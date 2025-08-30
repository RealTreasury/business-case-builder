<?php
defined( 'ABSPATH' ) || exit;

/**
 * Handles background job processing for case generation.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
class RTBCB_Background_Job {
	/**
	 * Enqueue a case generation job.
	 *
	 * @param array $user_inputs Sanitized user inputs.
	 * @return string Job ID.
	 */
public static function enqueue( $user_inputs ) {
$job_id = uniqid( 'rtbcb_job_', true );

set_transient(
$job_id,
[
'status' => 'queued',
'result' => null,
],
HOUR_IN_SECONDS
);

wp_schedule_single_event(
time(),
'rtbcb_process_job',
[ $job_id, $user_inputs ]
);

$jobs            = get_option( 'rtbcb_background_jobs', [] );
$jobs[ $job_id ] = time();
update_option( 'rtbcb_background_jobs', $jobs, false );

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
		set_transient(
			$job_id,
			[
				'status' => 'processing',
			],
			HOUR_IN_SECONDS
		);

		$result = RTBCB_Ajax::process_comprehensive_case( $user_inputs );

		if ( is_wp_error( $result ) ) {
			set_transient(
				$job_id,
				[
					'status'  => 'error',
					'message' => $result->get_error_message(),
				],
				HOUR_IN_SECONDS
			);
		} else {
			set_transient(
				$job_id,
				[
					'status' => 'completed',
					'result' => $result,
				],
				HOUR_IN_SECONDS
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

self::cleanup();

if ( false === $data ) {
return new WP_Error( 'not_found', __( 'Job not found.', 'rtbcb' ) );
}

return $data;
}

/**
 * Remove stale or errored jobs.
 *
 * @return void
 */
public static function cleanup() {
$threshold = (int) apply_filters( 'rtbcb_job_cleanup_threshold', DAY_IN_SECONDS );
$jobs      = get_option( 'rtbcb_background_jobs', [] );

foreach ( $jobs as $job_id => $created ) {
$data = get_transient( $job_id );

if ( false === $data || ( isset( $data['status'] ) && 'error' === $data['status'] ) || time() - (int) $created > $threshold ) {
delete_transient( $job_id );
unset( $jobs[ $job_id ] );
}
}

update_option( 'rtbcb_background_jobs', $jobs, false );
}
}

add_action( 'rtbcb_process_job', [ 'RTBCB_Background_Job', 'process_job' ], 10, 2 );
add_action( 'rtbcb_cleanup_jobs', [ 'RTBCB_Background_Job', 'cleanup' ] );
