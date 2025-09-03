<?php
defined( 'ABSPATH' ) || exit;

/**
 * API transport helper for LLM.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
require_once __DIR__ . '/helpers.php';

class RTBCB_LLM_Transport {
/**
 * API key.
 *
 * @var string
 */
private $api_key;

/**
 * GPT-5 configuration.
 *
 * @var array
 */
private $gpt5_config;

/**
 * Last request body.
 *
 * @var array|null
 */
private $last_request;

/**
 * Last response from API.
 *
 * @var array|WP_Error|null
 */
private $last_response;

/**
 * Constructor.
 *
 * @param RTBCB_LLM_Config $config Configuration instance.
 */
public function __construct( RTBCB_LLM_Config $config ) {
$this->api_key     = $config->get_api_key();
$this->gpt5_config = $config->get_gpt5_config();
}

/**
 * Get last request body.
 *
 * @return array|null
 */
public function get_last_request() {
return $this->last_request;
}

/**
 * Get last response.
 *
 * @return array|WP_Error|null
 */
public function get_last_response() {
return $this->last_response;
}

/**
 * Save API interaction to log table when available.
 *
 * @param array $request  Request data.
 * @param array $response Response data.
 * @return void
 */
private function maybe_log_interaction( $request, $response ) {
if ( ! class_exists( 'RTBCB_API_Log' ) ) {
return;
}

$user_id      = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;
$user_email   = $request['email'] ?? '';
$company_name = $request['company_name'] ?? '';

RTBCB_API_Log::save_log( $request, $response, $user_id, $user_email, $company_name );
}

/**
	* Call OpenAI Responses API with retries and optional streaming.
	*
	* @param string        $model             Model name.
	* @param array|string  $prompt            Prompt data.
	* @param int|null      $max_output_tokens Optional max output tokens.
	* @param int|null      $max_retries       Optional retries.
	* @param callable|null $chunk_handler     Optional streaming handler.
	* @return array|WP_Error Response array or WP_Error.
	*/
public function call_openai_with_retry( $model, $prompt, $max_output_tokens = null, $max_retries = null, $chunk_handler = null ) {
$request_data          = is_array( $prompt ) ? $prompt : [ 'input' => $prompt ];
$request_data['model'] = $model;

if ( rtbcb_heavy_features_disabled() ) {
$error = new WP_Error( 'heavy_features_disabled', __( 'AI features temporarily disabled.', 'rtbcb' ) );
$this->maybe_log_interaction( $request_data, [ 'error' => $error->get_error_message() ] );
return $error;
}

if ( empty( $this->api_key ) ) {
$error = new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
$this->maybe_log_interaction( $request_data, [ 'error' => $error->get_error_message() ] );
return $error;
}

$input = is_array( $prompt ) ? ( $prompt['input'] ?? '' ) : $prompt;
if ( '' === trim( (string) $input ) ) {
$error = new WP_Error( 'empty_prompt', __( 'Prompt cannot be empty.', 'rtbcb' ) );
$this->maybe_log_interaction( $request_data, [ 'error' => $error->get_error_message() ] );
return $error;
}

		$max_retries     = min( 3, $max_retries ?? intval( $this->gpt5_config['max_retries'] ?? 3 ) );
		$base_timeout    = intval( $this->gpt5_config['timeout'] ?? 300 );
		$current_timeout = $base_timeout;
		$current_tokens  = $max_output_tokens;
		$max_retry_time  = max( $base_timeout, intval( $this->gpt5_config['max_retry_time'] ?? $base_timeout ) );
		$start_time      = microtime( true );

		for ( $attempt = 1; $attempt <= $max_retries; $attempt++ ) {
			$elapsed = microtime( true ) - $start_time;
			if ( $elapsed >= $max_retry_time ) {
				break;
			}

			$remaining                    = $max_retry_time - $elapsed;
			$this->gpt5_config['timeout'] = min( $current_timeout, $remaining );

$response = $this->call_openai( $model, $prompt, $current_tokens, $chunk_handler );

if ( ! is_wp_error( $response ) ) {
$this->gpt5_config['timeout'] = $base_timeout;
$response_body = $response['body'] ?? '';
$decoded       = json_decode( $response_body, true );
if ( null === $decoded ) {
$decoded = [];
}
$this->maybe_log_interaction( $this->last_request ?? $request_data, $decoded );
return $response;
}

			$error_code = $response->get_error_code();
			if ( 'llm_http_status' === $error_code ) {
				$data   = $response->get_error_data();
				$status = isset( $data['status'] ) ? intval( $data['status'] ) : 0;
				if ( $status >= 400 && $status < 500 && 429 !== $status ) {
					break;
				}
			}

			if ( ! in_array( $error_code, [ 'llm_timeout', 'llm_http_error', 'llm_http_status' ], true ) ) {
				break;
			}

			if ( $attempt < $max_retries ) {
				if ( null !== $current_tokens ) {
					$min_tokens    = intval( $this->gpt5_config['min_output_tokens'] ?? 1 );
					$current_tokens = max( $min_tokens, (int) ( $current_tokens * 0.9 ) );
				}

				$current_timeout = min( $current_timeout + 5, $max_retry_time );

				$delay = min( 5, pow( 2, $attempt - 1 ) );
				usleep( (int) ( $delay * 1000000 ) );
			}
		}

$this->gpt5_config['timeout'] = $base_timeout;
$this->maybe_log_interaction( $this->last_request ?? $request_data, [ 'error' => $response->get_error_message() ] );

return $response; // Return last error.
	}

	/**
	 * Perform the actual OpenAI call.
	 *
	 * @param string        $model          Model name.
	 * @param array|string  $prompt         Prompt data.
	 * @param int|null      $max_tokens     Optional max output tokens.
	 * @param callable|null $chunk_handler  Optional streaming handler.
	 * @return array|WP_Error HTTP-like response or WP_Error.
	 */
	protected function call_openai( $model, $prompt, $max_tokens = null, $chunk_handler = null ) {
		if ( ! function_exists( 'curl_init' ) ) {
			return new WP_Error( 'missing_curl', __( 'The cURL PHP extension is required.', 'rtbcb' ) );
		}

		$endpoint   = 'https://api.openai.com/v1/responses';
		$model_name = sanitize_text_field( $model ?: 'gpt-5-mini' );
		$body       = is_array( $prompt ) ? $prompt : [ 'input' => sanitize_textarea_field( (string) $prompt ) ];
		$body['model'] = $model_name;
		if ( $max_tokens ) {
			$body['max_output_tokens'] = intval( $max_tokens );
		}
		if ( is_callable( $chunk_handler ) ) {
			$body['stream'] = true;
		}

		$timeout = intval( $this->gpt5_config['timeout'] ?? 300 );
		$payload = wp_json_encode( $body );
		$stream  = '';

		$ch = curl_init( $endpoint );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, [
			'Authorization: Bearer ' . $this->api_key,
			'Content-Type: application/json',
		] );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_WRITEFUNCTION, function ( $curl, $data ) use ( &$stream, $chunk_handler ) {
			if ( is_callable( $chunk_handler ) ) {
				try {
					call_user_func( $chunk_handler, $data );
				} catch ( Exception $e ) {
					error_log( 'RTBCB: Chunk handler error: ' . $e->getMessage() );
				}
			}
			$stream .= $data;
			return strlen( $data );
		} );

		$this->last_request = $body;

		$ok        = curl_exec( $ch );
		$error     = curl_error( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( false === $ok ) {
			if ( false !== strpos( strtolower( $error ), 'timed out' ) ) {
				return new WP_Error(
					'llm_timeout',
					__( 'The request took longer than our 5-minute limit. Try Fast Mode or request email delivery.', 'rtbcb' )
				);
			}

			return new WP_Error(
				'llm_http_error',
				sprintf( __( 'Language model request failed: %s', 'rtbcb' ), sanitize_text_field( $error ) )
			);
		}

		$final_response = $this->process_streaming_response( $stream );
		if ( null === $final_response ) {
			return new WP_Error(
				'llm_response_format',
				__( 'Invalid response format received from language model.', 'rtbcb' )
			);
		}

		$response_body = wp_json_encode( $final_response );
		$response      = [
			'body'     => $response_body,
			'response' => [ 'code' => $http_code, 'message' => '' ],
			'headers'  => [],
		];

		$this->last_response = $response;

		if ( $http_code >= 400 ) {
			if ( isset( $final_response['error']['message'] ) ) {
				$message = $final_response['error']['message'];
			} elseif ( isset( $final_response['message'] ) ) {
				$message = $final_response['message'];
			} else {
				$message = wp_json_encode( $final_response );
			}

			$message = sanitize_text_field( $message );

			return new WP_Error( 'llm_http_status', $message, [ 'status' => $http_code ] );
		}

		return $response;
	}

	/**
	 * Process streaming response chunks from the OpenAI API.
	 *
	 * Splits the raw stream into events and assembles the final response
	 * structure, handling both modern and legacy formats.
	 *
	 * @param string $stream Raw streaming data from cURL.
	 * @return array|null Structured response array or null on failure.
	 */
	protected function process_streaming_response( $stream ) {
		if ( empty( $stream ) ) {
			return null;
		}

		$events        = [];
		$lines         = preg_split( "/\r?\n/", $stream );
		$current_event = [];

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( empty( $line ) ) {
				if ( ! empty( $current_event ) ) {
					$events[]      = $current_event;
					$current_event = [];
				}
				continue;
			}

			if ( strpos( $line, ':' ) !== false ) {
				list( $field, $value ) = explode( ':', $line, 2 );
				$field = trim( $field );
				$value = trim( $value );

				if ( 'data' === $field && '[DONE]' !== $value ) {
					$current_event['data'] = $value;
				} elseif ( 'event' === $field ) {
					$current_event['event'] = $value;
				}
			}
		}

		if ( ! empty( $current_event ) ) {
			$events[] = $current_event;
		}

		$final_response      = null;
		$accumulated_content = '';

		foreach ( $events as $event ) {
			if ( ! isset( $event['data'] ) ) {
				continue;
			}

			$event_data = json_decode( $event['data'], true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				continue;
			}

			if ( isset( $event_data['type'] ) ) {
				switch ( $event_data['type'] ) {
					case 'response.done':
					case 'response.content_part.done':
						if ( isset( $event_data['response'] ) ) {
							$final_response = $event_data['response'];
						}
						break;
					case 'response.content_part.delta':
						if ( isset( $event_data['delta']['text'] ) ) {
							$accumulated_content .= $event_data['delta']['text'];
						}
						break;
				}
			} else {
				if ( isset( $event_data['choices'][0]['delta']['content'] ) ) {
					$accumulated_content .= $event_data['choices'][0]['delta']['content'];
				} elseif ( isset( $event_data['choices'][0]['message'] ) ) {
					$final_response = $event_data;
				}
			}
		}

		if ( $final_response ) {
			return $final_response;
		}

		if ( ! empty( $accumulated_content ) ) {
			return [
				'choices' => [
					[
						'message' => [
							'content' => $accumulated_content,
						],
					],
				],
			];
		}

		$decoded = json_decode( $stream, true );
		if ( JSON_ERROR_NONE === json_last_error() ) {
			return $decoded;
		}

		return null;
	}
}
