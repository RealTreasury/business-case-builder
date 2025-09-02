<?php
defined( 'ABSPATH' ) || exit;

/**
	* Structured logging utilities.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/
class RTBCB_Logger {

	/**
	 * Send a structured log record.
	 *
	 * @param string $event   Event name.
	 * @param array  $context Context data.
	 * @return void
	 */
       public static function log( $event, $context = [] ) {
               if ( function_exists( 'rtbcb_get_current_lead' ) ) {
                       $lead = rtbcb_get_current_lead();
                       if ( $lead ) {
                               $context = array_merge(
                                       [
                                               'lead_id'    => intval( $lead['id'] ),
                                               'lead_email' => $lead['email'],
                                       ],
                                       $context
                               );
                       }
               }

               $record = [
                       'timestamp' => gmdate( 'c' ),
                       'event'     => $event,
                       'context'   => $context,
               ];

		error_log( 'RTBCB_LOG: ' . wp_json_encode( $record ) );

$endpoint = function_exists( 'get_option' ) ? get_option( 'rtbcb_log_endpoint', '' ) : '';
$endpoint = function_exists( 'esc_url_raw' ) ? esc_url_raw( $endpoint ) : $endpoint;

		if ( $endpoint ) {
			rtbcb_wp_remote_post_with_retry(
				$endpoint,
				[
					'headers' => [ 'Content-Type' => 'application/json' ],
					'body'    => wp_json_encode( $record ),
					'timeout' => 2,
				]
			);
		}
	}

	/**
	 * Log request details on shutdown.
	 *
	 * @param float $start_time Request start time.
	 * @param array $payload    Sanitized request payload.
	 * @return void
	 */
	public static function log_shutdown( $start_time, $payload ) {
		$duration = ( microtime( true ) - $start_time ) * 1000;
		$code     = http_response_code();

		$log = [
			'payload'          => $payload,
			'response_time_ms' => round( $duration ),
			'status_code'      => $code,
		];

		$error = error_get_last();
		if ( $error ) {
			$log['error'] = $error['message'];
		}

		if ( 504 === $code || ( isset( $log['error'] ) && false !== stripos( $log['error'], 'timeout' ) ) ) {
			self::record_timeout();
		}

		self::log( 'generate_case', $log );
	}

	/**
	 * Increment timeout counter and trigger alert if threshold exceeded.
	 *
	 * @return void
	 */
	public static function record_timeout() {
		$count = (int) get_transient( 'rtbcb_timeout_count' );
		$count++;
		set_transient( 'rtbcb_timeout_count', $count, 300 );

		if ( $count >= 3 ) {
			self::send_timeout_alert( $count );
			delete_transient( 'rtbcb_timeout_count' );
		}
	}

	/**
	 * Send timeout alert email.
	 *
	 * @param int $count Timeout count.
	 * @return void
	 */
	private static function send_timeout_alert( $count ) {
		$admin_email = function_exists( 'get_option' ) ? get_option( 'admin_email' ) : '';
		$subject     = __( 'Business Case Builder timeout alert', 'rtbcb' );
		$message     = sprintf(
			__( 'The Business Case Builder API timed out %d times in the last five minutes.', 'rtbcb' ),
			$count
		);
		wp_mail( $admin_email, $subject, $message );
	}
}
