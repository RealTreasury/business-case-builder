<?php
defined( 'ABSPATH' ) || exit;

/**
	* API log management class.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

/**
	* Handles storage and retrieval of API interaction logs.
	*/
class RTBCB_API_Log {
	/**
	* Database table name.
	*
	* @var string
	*/
	private static $table_name;

	/**
	* Initialize the table name and create the table.
	*
	* @return void
	*/
	public static function init() {
		global $wpdb;

		self::$table_name = $wpdb->prefix . 'rtbcb_api_logs';
		self::create_table();
	}

	/**
	* Create the API logs table.
	*
	* @return bool True on success, false on failure.
	*/
        private static function create_table() {
                global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
               $sql             = 'CREATE TABLE ' . self::$table_name . " (
                       id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                       user_id bigint(20) unsigned DEFAULT 0,
                       user_email varchar(255) DEFAULT '',
                       lead_id bigint(20) unsigned DEFAULT 0,
                       company_name varchar(255) DEFAULT '',
                       request_json longtext DEFAULT '',
                       response_json longtext DEFAULT '',
                       response_truncated tinyint(1) DEFAULT 0,
                       prompt_tokens int(11) DEFAULT 0,
                       completion_tokens int(11) DEFAULT 0,
                       total_tokens int(11) DEFAULT 0,
                       created_at datetime DEFAULT CURRENT_TIMESTAMP,
                       PRIMARY KEY  (id),
                       KEY user_id (user_id),
                       KEY lead_id (lead_id),
                       KEY created_at (created_at)
               ) $charset_collate;";

		try {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			$table_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
					DB_NAME,
					self::$table_name
				)
			);

			if ( ! $table_exists ) {
				error_log( 'RTBCB: Failed to create table ' . self::$table_name );

                               $simple_sql = 'CREATE TABLE IF NOT EXISTS ' . self::$table_name . " (
                                       id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       user_id bigint(20) unsigned DEFAULT 0,
                                       user_email varchar(255) DEFAULT '',
                                       lead_id bigint(20) unsigned DEFAULT 0,
                                       company_name varchar(255) DEFAULT '',
                                       request_json longtext DEFAULT '',
                                       response_json longtext DEFAULT '',
                                       response_truncated tinyint(1) DEFAULT 0,
                                       prompt_tokens int(11) DEFAULT 0,
                                       completion_tokens int(11) DEFAULT 0,
                                       total_tokens int(11) DEFAULT 0,
                                       created_at datetime DEFAULT CURRENT_TIMESTAMP,
                                       PRIMARY KEY (id)
                               ) $charset_collate;";

				$wpdb->query( $simple_sql );

				$table_exists = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
						DB_NAME,
						self::$table_name
					)
				);

				if ( ! $table_exists ) {
					error_log( 'RTBCB: Failed to create API log table even with simple structure' );
					return false;
                }
        }

                       return true;
               } catch ( Exception $e ) {
                       error_log( 'RTBCB: Exception creating API log table: ' . $e->getMessage() );
                       return false;
               } catch ( Error $e ) {
                       error_log( 'RTBCB: Fatal error creating API log table: ' . $e->getMessage() );
                       return false;
               }
       }

       /**
        * Encode data as JSON with size limit while preserving validity.
        *
        * @param mixed $data       Data to encode.
        * @param int   $max_bytes  Maximum allowed bytes.
        * @param bool  $truncated  Whether truncation occurred (passed by reference).
        * @return string Encoded JSON string.
        */
       private static function encode_json_limited( $data, $max_bytes, &$truncated ) {
               $json      = wp_json_encode( $data );
               $truncated = false;

               if ( strlen( $json ) <= $max_bytes ) {
                       return $json;
               }

               $truncated = true;
               $json      = substr( $json, 0, $max_bytes );

               while ( strlen( $json ) && null === json_decode( $json, true ) ) {
                       $json = substr( $json, 0, -1 );
               }

               return $json;
       }

	/**
	* Save a log entry.
	*
	* @param array  $request      Request data.
	* @param array  $response     Response data.
	* @param int    $user_id      User ID.
	* @param string $user_email   User email.
	* @param string $company_name Company name.
	* @return void
	*/
       public static function save_log( $request, $response, $user_id, $user_email = '', $company_name = '', $lead_id = 0 ) {
               global $wpdb;

               if ( empty( self::$table_name ) ) {
                       self::init();
               }

               if ( empty( $user_email ) && ! empty( $request['email'] ) ) {
                       $user_email = $request['email'];
               }

               if ( empty( $company_name ) && ! empty( $request['company_name'] ) ) {
                       $company_name = $request['company_name'];
               }

               if ( ! $lead_id && function_exists( 'rtbcb_get_current_lead' ) ) {
                       $lead = rtbcb_get_current_lead();
                       if ( $lead ) {
                               $lead_id    = intval( $lead['id'] );
                               if ( empty( $user_email ) && ! empty( $lead['email'] ) ) {
                                       $user_email = $lead['email'];
                               }
                       }
               }

               $request_truncated  = false;
               $response_truncated = false;
               $request_json       = self::encode_json_limited( $request, 20000, $request_truncated );
               $response_json      = self::encode_json_limited( $response, 1024 * 1024, $response_truncated );
                $usage             = $response['usage'] ?? [];
		$prompt_tokens     = intval( $usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0 );
		$completion_tokens = intval( $usage['completion_tokens'] ?? $usage['output_tokens'] ?? 0 );
		$total_tokens      = intval( $usage['total_tokens'] ?? 0 );
		if ( 0 === $total_tokens && ( $prompt_tokens || $completion_tokens ) ) {
			$total_tokens = $prompt_tokens + $completion_tokens;
		}

               $user_email   = sanitize_email( $user_email );
               $company_name = sanitize_text_field( $company_name );

               $wpdb->insert(
                       self::$table_name,
                       [
                               'user_id'           => intval( $user_id ),
                               'user_email'        => $user_email,
                               'lead_id'           => intval( $lead_id ),
                               'company_name'      => $company_name,
                               'request_json'      => $request_json,
                               'response_json'     => $response_json,
                               'response_truncated'=> $response_truncated ? 1 : 0,
                               'prompt_tokens'     => $prompt_tokens,
                               'completion_tokens' => $completion_tokens,
                               'total_tokens'      => $total_tokens,
                               'created_at'        => current_time( 'mysql' ),
                       ],
                       [ '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s' ]
               );
       }

       /**
        * Associate existing logs with a lead ID.
        *
        * @param int    $lead_id    Lead ID.
        * @param string $user_email Lead email address.
        * @return void
        */
       public static function associate_lead( $lead_id, $user_email ) {
               global $wpdb;

               if ( empty( self::$table_name ) ) {
                       self::init();
               }

               $lead_id    = intval( $lead_id );
               $user_email = sanitize_email( $user_email );

               if ( $lead_id <= 0 || empty( $user_email ) ) {
                       return;
               }

               $wpdb->update(
                       self::$table_name,
                       [ 'lead_id' => $lead_id ],
                       [ 'user_email' => $user_email ],
                       [ '%d' ],
                       [ '%s' ]
               );
       }

	/**
	* Retrieve recent logs.
	*
	* @param int $limit Number of logs to retrieve.
	* @return array Log rows.
	*/
	public static function get_logs( $limit = 50 ) {
		global $wpdb;

		if ( empty( self::$table_name ) ) {
			self::init();
		}

		$limit = max( 1, intval( $limit ) );

               $query = $wpdb->prepare(
                       'SELECT id, user_id, user_email, lead_id, company_name, request_json, response_json, response_truncated, prompt_tokens, completion_tokens, total_tokens, created_at FROM ' . self::$table_name . ' ORDER BY created_at DESC LIMIT %d',
                       $limit
               );

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Get log IDs matching a lead ID or email.
	 *
	 * @param int    $lead_id Lead ID.
	 * @param string $email   Lead email address.
	 * @return array List of matching log IDs.
	 */
	public static function get_log_ids_by_lead( $lead_id = 0, $email = '' ) {
		global $wpdb;

		if ( empty( self::$table_name ) ) {
			self::init();
		}

		$lead_id = intval( $lead_id );
		$email   = sanitize_email( $email );

		$where  = [];
		$params = [];

		if ( $lead_id > 0 ) {
			$where[]  = 'lead_id = %d';
			$params[] = $lead_id;
		}

		if ( ! empty( $email ) ) {
			$where[]  = 'user_email = %s';
			$params[] = $email;
		}

		if ( empty( $where ) ) {
			return [];
		}

		$query = 'SELECT id FROM ' . self::$table_name . ' WHERE ' . implode( ' OR ', $where );

		return $wpdb->get_col( $wpdb->prepare( $query, $params ) );
	}
	/**
	* Purge old logs.
	*
	* @param int $days Number of days to retain.
	* @return int Rows deleted.
	*/
	public static function purge_logs( $days = 30 ) {
		global $wpdb;

		if ( empty( self::$table_name ) ) {
			self::init();
		}

		$days = intval( $days );
		if ( $days <= 0 ) {
			return 0;
		}

		$threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

                return $wpdb->query(
                        $wpdb->prepare(
                                'DELETE FROM ' . self::$table_name . ' WHERE created_at < %s',
                                $threshold
                        )
                );
        }

	/**
	 * Purge logs older than 72 hours.
	 *
	 * @return int Rows deleted.
	 */
	public static function purge_old_logs() {
		return self::purge_logs( 3 );
	}

	/**
	* Delete a single log entry.
	*
	* @param int $id Log ID.
	* @return int Rows deleted.
	*/
	public static function delete_log( $id ) {
		global $wpdb;

		if ( empty( self::$table_name ) ) {
			self::init();
		}

		$id = intval( $id );
		if ( $id <= 0 ) {
			return 0;
		}

		return $wpdb->delete( self::$table_name, [ 'id' => $id ], [ '%d' ] );
	}

	/**
	* Clear all log entries.
	*
	* @return int Rows deleted.
	*/
	public static function clear_logs() {
		global $wpdb;

		if ( empty( self::$table_name ) ) {
			self::init();
		}

		return $wpdb->query( 'TRUNCATE TABLE ' . self::$table_name );
	}

	/**
	* Retrieve logs with pagination support.
	*
	* @param int $paged    Current page number.
	* @param int $per_page Items per page.
	* @return array{
	*     logs: array,
	*     total: int
	* }
	*/
	public static function get_logs_paginated( $paged = 1, $per_page = 20 ) {
		global $wpdb;

		if ( empty( self::$table_name ) ) {
			self::init();
		}

		$paged    = max( 1, intval( $paged ) );
		$per_page = max( 1, intval( $per_page ) );
		$offset   = ( $paged - 1 ) * $per_page;

               $logs = $wpdb->get_results(
                       $wpdb->prepare(
                               'SELECT id, user_id, user_email, lead_id, company_name, request_json, response_json, response_truncated, prompt_tokens, completion_tokens, total_tokens, created_at FROM ' . self::$table_name . ' ORDER BY created_at DESC LIMIT %d OFFSET %d',
                               $per_page,
                               $offset
                       ),
                       ARRAY_A
               );

		$total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::$table_name );

		return [
			'logs'  => $logs,
			'total' => $total,
		];
	}

/**
 * Retrieve all logs.
 *
 * @return array
 */
	public static function get_all_logs() {
		global $wpdb;

		if ( empty( self::$table_name ) ) {
			self::init();
		}

		return $wpdb->get_results(
                       'SELECT id, user_id, user_email, lead_id, company_name, request_json, response_json, response_truncated, prompt_tokens, completion_tokens, total_tokens, created_at FROM ' . self::$table_name . ' ORDER BY created_at DESC',
               ARRAY_A
               );
	}
	}
