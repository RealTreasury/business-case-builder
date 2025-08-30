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

		if ( false === $data ) {
			return new WP_Error( 'not_found', __( 'Job not found.', 'rtbcb' ) );
		}

		return $data;
	}
}

add_action( 'rtbcb_process_job', [ 'RTBCB_Background_Job', 'process_job' ], 10, 2 );
