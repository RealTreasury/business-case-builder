<?php
defined( 'ABSPATH' ) || exit;

/**
* Response integrity utilities for OpenAI API interactions.
*
* @package RealTreasuryBusinessCaseBuilder
*/
class RTBCB_Response_Integrity {
	/**
	* Validate JSON structure integrity.
	*
	* @param string $response JSON response string.
	* @return bool True when valid JSON, false otherwise.
	*/
	public static function validateResponse( $response ) {
		$response = function_exists( 'wp_unslash' ) ? wp_unslash( $response ) : $response;
		json_decode( $response );
		$valid = JSON_ERROR_NONE === json_last_error();
		
		if ( class_exists( 'RTBCB_Logger' ) ) {
			RTBCB_Logger::log(
			'response_validate',
			[
			'valid'  => $valid,
			'length' => strlen( $response ),
			]
			);
		}
		
		return $valid;
	}
	
	/**
	* Detect corruption patterns between stored and original responses.
	*
	* @param string $storedResponse   Stored response string.
	* @param string $originalResponse Original response string.
	* @return array{corrupted:bool,issues:array}
	*/
	public static function detectCorruption( $storedResponse, $originalResponse ) {
		$issues = [];
		
		json_decode( $storedResponse );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$issues[] = 'invalid_json';
		}
		
		if ( $storedResponse !== $originalResponse ) {
			$issues[] = 'mismatch';
		}
		
		$result = [
		'corrupted' => ! empty( $issues ),
		'issues'    => $issues,
		];
		
		if ( class_exists( 'RTBCB_Logger' ) ) {
			RTBCB_Logger::log( 'response_detect_corruption', $result );
		}
		
		return $result;
	}
	
	/**
	* Attempt automatic repair of minor JSON issues.
	*
	* @param string $corruptedResponse Possibly corrupted JSON.
	* @return string Repaired JSON string or original when unrepaired.
	*/
	public static function repairResponse( $corruptedResponse ) {
		$attempts = [
		$corruptedResponse,
		str_replace( ',}', '}', $corruptedResponse ),
		str_replace( ',]', ']', $corruptedResponse ),
		];
		
		foreach ( $attempts as $attempt ) {
			json_decode( $attempt );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				if ( class_exists( 'RTBCB_Logger' ) ) {
					RTBCB_Logger::log(
					'response_repair_success',
					[
					'original_length' => strlen( $corruptedResponse ),
					'repaired_length' => strlen( $attempt ),
					]
					);
				}
				return wp_json_encode( json_decode( $attempt, true ) );
			}
		}
		
		if ( class_exists( 'RTBCB_Logger' ) ) {
			RTBCB_Logger::log( 'response_repair_failed', [ 'length' => strlen( $corruptedResponse ) ] );
		}
		
		return $corruptedResponse;
	}
	
	/**
	* Generate integrity report for a log entry.
	*
	* @param int $logId Log identifier.
	* @return array Report details.
	*/
	public static function generateIntegrityReport( $logId ) {
		$logId = intval( $logId );
		if ( $logId <= 0 || ! class_exists( 'RTBCB_API_Log' ) ) {
			return [];
		}
		
		$logs  = RTBCB_API_Log::get_logs( 200 );
		$entry = null;
		foreach ( $logs as $log ) {
			if ( intval( $log['id'] ) === $logId ) {
				$entry = $log;
				break;
			}
		}
		
		if ( ! $entry ) {
			return [];
		}
		
		$result                = self::detectCorruption( $entry['response_json'], $entry['response_json'] );
		$result['log_id']    = $logId;
		$result['valid_json'] = self::validateResponse( $entry['response_json'] );
		
		if ( class_exists( 'RTBCB_Logger' ) ) {
			RTBCB_Logger::log( 'response_integrity_report', $result );
		}
		
		return $result;
	}
	
	/**
	* Re-process corrupted historical data.
	*
	* @param callable $callback Callback invoked with (log, repaired_json).
	* @param int      $limit    Number of logs to inspect.
	* @return int Number of logs reprocessed.
	*/
	public static function reprocessHistoricalData( callable $callback, $limit = 100 ) {
		if ( ! class_exists( 'RTBCB_API_Log' ) ) {
			return 0;
		}
		
		$logs  = RTBCB_API_Log::get_logs( $limit );
		$count = 0;
		
		foreach ( $logs as $log ) {
			$check = self::detectCorruption( $log['response_json'], $log['response_json'] );
			if ( $check['corrupted'] ) {
				$repaired = self::repairResponse( $log['response_json'] );
				call_user_func( $callback, $log, $repaired );
				$count++;
			}
		}
		
		if ( class_exists( 'RTBCB_Logger' ) ) {
			RTBCB_Logger::log(
			'response_reprocess',
			[
			'processed' => $count,
			'limit'     => $limit,
			]
			);
		}
		
		return $count;
	}
}
