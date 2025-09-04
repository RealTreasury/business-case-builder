<?php
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-rtbcb-response-parser.php';

/**
	* Helper functions for the Real Treasury Business Case Builder plugin.
	*/

/**
	* Get the timeout for external API requests.
	*
	* Allows filtering via `rtbcb_api_timeout` to adjust how long remote
	* requests may take before failing.
	*
	* @return int Timeout in seconds.
	*/
function rtbcb_get_api_timeout() {
	$timeout = function_exists( 'get_option' ) ? (int) get_option( 'rtbcb_gpt5_timeout', 300 ) : 300;
	$timeout = rtbcb_sanitize_api_timeout( $timeout );

	/**
	* Filter the API request timeout.
	*
	* @param int $timeout Timeout in seconds.
	*/
	if ( function_exists( 'apply_filters' ) ) {
		return (int) apply_filters( 'rtbcb_api_timeout', $timeout );
	}

	return $timeout;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/wp-polyfills.php';

/**
	* Determine the current analysis tier.
	*
	* Uses plugin settings to detect enabled features and maps them to one of
	* the allowed tiers. The value can be filtered via `rtbcb_analysis_type`.
	*
	* @return string Analysis type.
	*/
function rtbcb_get_analysis_type() {
	$analysis_type = 'basic';

	$enable_ai = class_exists( 'RTBCB_Settings' ) ? RTBCB_Settings::get_setting( 'enable_ai_analysis', true ) : true;

	if ( $enable_ai ) {
		$analysis_type = 'enhanced';
	}

	if ( function_exists( 'apply_filters' ) ) {
		$analysis_type = apply_filters( 'rtbcb_analysis_type', $analysis_type );
	}

	if ( ! in_array( $analysis_type, RTBCB_ALLOWED_TIERS, true ) ) {
		$analysis_type = 'basic';
	}

	return $analysis_type;
}

/**
	* Retrieve the OpenAI API key from environment or plugin settings.
	*
	* Checks the `RTBCB_OPENAI_API_KEY` environment variable first and falls back
	* to the value stored in the WordPress options table.
	*
	* @return string Sanitized API key.
	*/
function rtbcb_get_openai_api_key() {
	$api_key = '';
	
	if ( function_exists( 'getenv' ) ) {
		$env_key = getenv( 'RTBCB_OPENAI_API_KEY' );
		if ( false !== $env_key && '' !== $env_key ) {
			$api_key = $env_key;
		}
	}
	
	if ( '' === $api_key && function_exists( 'get_option' ) ) {
		$api_key = get_option( 'rtbcb_openai_api_key', '' );
	}
	
	return function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $api_key ) : $api_key;
}

/**
	* Determine if an OpenAI API key is configured.
	*
	* @return bool True if the API key is present.
	*/
function rtbcb_has_openai_api_key() {
	return ! empty( rtbcb_get_openai_api_key() );
}

/**
	* Check if heavy AI features are disabled.
	*
	* @return bool True if heavy features should be bypassed.
	*/
function rtbcb_heavy_features_disabled() {
$disabled = function_exists( 'get_option' ) ? get_option( 'rtbcb_disable_heavy_features', 0 ) : 0;
$fast	  = function_exists( 'get_option' ) ? get_option( 'rtbcb_fast_mode', 0 ) : 0;
$auto	  = false;

if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
if ( isset( $_SERVER['HTTP_X_JETPACK_SIGNATURE'] ) || isset( $_SERVER['HTTP_JETPACK_SIGNATURE'] ) ) {
$auto = true;
}
} else {
$for		 = isset( $_GET['for'] ) ? sanitize_key( wp_unslash( $_GET['for'] ) ) : '';
$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

if ( 'jetpack' === $for || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
$auto = true;
} elseif ( isset( $_SERVER['HTTP_X_JETPACK_SIGNATURE'] ) || isset( $_SERVER['HTTP_JETPACK_SIGNATURE'] ) ) {
$auto = true;
} elseif ( false !== strpos( $request_uri, '/jetpack/' ) || false !== strpos( $request_uri, '/wp-admin/rest-proxy/' ) ) {
$auto = true;
}
}

	return (bool) ( $disabled || $fast || $auto );
}

/**
	* Detect if the site is hosted on WordPress.com.
	*
	* Checks the `IS_WPCOM` constant and common Jetpack proxy headers used on
	* WordPress.com infrastructure.
	*
	* @return bool True if WordPress.com environment is detected.
	*/
function rtbcb_is_wpcom() {
	if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
	return true;
	}
	
	$headers = [
	'HTTP_X_WPCOM_REQUEST_ID',
	'HTTP_X_WPCOM_PROXIED',
	'HTTP_X_JETPACK_PROXIED_REQUEST',
	];
	
	foreach ( $headers as $header ) {
	if ( isset( $_SERVER[ $header ] ) ) {
	return true;
	}
	}
	
	return false;
}
	
/**
 * Perform a remote POST request with retry logic.
 *
 * Uses exponential backoff with jitter between attempts. Retries are
	* triggered for transport errors, HTTP 429 responses and server errors.
	* Client errors (4xx) other than 429 will return immediately.
	*
	* @param string $url		 Endpoint URL.
	* @param array	$args		 Arguments for {@see wp_remote_post()}.
	* @param int	$max_retries Optional. Number of attempts. Default 3.
	* @return array|WP_Error HTTP response array or WP_Error on failure.
	*/
function rtbcb_wp_remote_post_with_retry( $url, $args = [], $max_retries = 3 ) {
	static $logging = false;
	$url          = function_exists( 'esc_url_raw' ) ? esc_url_raw( $url ) : $url;
	$max_retries  = max( 1, (int) $max_retries );

	$base_timeout   = isset( $args['timeout'] ) ? (int) $args['timeout'] : rtbcb_get_api_timeout();
	$max_retry_time = isset( $args['max_retry_time'] ) ? (int) $args['max_retry_time'] : $base_timeout * $max_retries;
	unset( $args['max_retry_time'] );

	if ( $base_timeout <= 0 || $max_retry_time <= 0 ) {
		$error_code    = 'invalid_timeout';
		$error_message = __( 'Request timeout must be greater than zero.', 'rtbcb' );
		rtbcb_log_error( $error_code . ': ' . $error_message );
		return new WP_Error( $error_code, $error_message );
	}

	$current_timeout = $base_timeout;
	$start_time      = microtime( true );

	for ( $attempt = 1; $attempt <= $max_retries; $attempt++ ) {
		$elapsed = microtime( true ) - $start_time;
		if ( $elapsed >= $max_retry_time ) {
			break;
		}

		$remaining       = $max_retry_time - $elapsed;
		$args['timeout'] = min( $current_timeout, $remaining );

		$response      = wp_remote_post( $url, $args );
		$response_code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
		if ( ! $logging ) {
			$logging = true;
			RTBCB_Logger::log(
				'http_request',
				[
					'url'           => $url,
					'attempt'       => $attempt,
					'response_code' => $response_code,
				],
			);
			$logging = false;
		}

		if ( ! is_wp_error( $response ) ) {
			$status = $response_code;
			if ( $status >= 200 && $status < 300 ) {
				return $response;
			}

			if ( $status >= 400 && $status < 500 && 429 !== $status ) {
				$error_code    = 'http_error';
				$error_message = sprintf( __( 'HTTP error %d', 'rtbcb' ), $status );
				rtbcb_log_error( $error_code . ': ' . $error_message );
				return new WP_Error( $error_code, $error_message, [ 'status' => $status ] );
			}
		}

		if ( $attempt < $max_retries ) {
			$current_timeout = min( $current_timeout + 5, $max_retry_time );

			$elapsed = microtime( true ) - $start_time;
			$delay   = min( pow( 2, $attempt - 1 ), $max_retry_time - $elapsed );
			if ( $delay > 0 ) {
				$jitter = wp_rand( 0, 1000 ) / 1000;
				usleep( (int) ( ( $delay + $jitter ) * 1000000 ) );
			}
		}
	}

	if ( is_wp_error( $response ) ) {
		rtbcb_log_error( $response->get_error_code() . ': ' . $response->get_error_message() );
	}

	return $response;
}

/**
	* Determine if an error indicates an OpenAI configuration issue.
	*
	* Checks for common phrases like a missing API key or invalid model.
	*
	* @param Throwable $e Thrown error or exception.
	* @return bool True if the error appears configuration related.
	*/
function rtbcb_is_openai_configuration_error( $e ) {
	$message = strtolower( $e->getMessage() );

	return false !== strpos( $message, 'api key' ) || false !== strpos( $message, 'model' );
}

/**
	* Retrieve current company data.
	*
	* @return array Current company data.
	*/
function rtbcb_get_current_company() {
	return get_option( 'rtbcb_current_company', [] );
}

/**
	* Clear stored company data and reset test progress.
	*
	* @return void
	*/
function rtbcb_clear_current_company() {
	$sections = rtbcb_get_dashboard_sections( [] );

	$options = [
		'rtbcb_company_overview',
		'rtbcb_treasury_challenges',
		'rtbcb_company_data',
		'rtbcb_test_results',
	];

	foreach ( $sections as $section ) {
		if ( ! empty( $section['option'] ) ) {
			$options[] = $section['option'];
		}
	}

	foreach ( array_unique( $options ) as $option ) {
		delete_option( $option );
	}
}

/**
	* Retrieve model capability configuration.
	*
	* @return array Model capability data.
	*/
function rtbcb_get_model_capabilities() {
	return include RTBCB_DIR . 'inc/model-capabilities.php';
}

/**
	* Determine if a model supports the temperature parameter.
	*
	* Attempts to query the OpenAI models endpoint and caches the result. Falls
	* back to a static list of unsupported models if the request fails.
	*
	* @param string $model Model identifier.
	* @return bool Whether the model supports temperature.
	*/
function rtbcb_model_supports_temperature( $model ) {
		$capabilities = include RTBCB_DIR . 'inc/model-capabilities.php';
		$unsupported = $capabilities['temperature']['unsupported'] ?? [];
		return ! in_array( $model, $unsupported, true );
}

/**
	* Sanitize the API timeout option.
	*
	* Ensures the value stays within the allowed 1-600 second range while
	* preserving legacy zero values by falling back to the default 300 seconds.
	*
	* @param mixed $value Raw option value.
	* @return int Sanitized timeout in seconds.
	*/
function rtbcb_sanitize_api_timeout( $value ) {
	$value = intval( $value );

	if ( $value <= 0 ) {
		return 300;
	}

	return min( 600, $value );
}

/**
	* Sanitize the max output tokens option.
	*
	* Ensures the value stays within the allowed 256-128000 token range.
	*
	* @param mixed $value Raw option value.
	* @return int Sanitized token count.
	*/
function rtbcb_sanitize_max_output_tokens( $value ) {
	$value = intval( $value );

	return min( 128000, max( 256, $value ) );
}

/**
	* Sanitize the min output tokens option.
	*
	* Ensures the value stays within the allowed 1-128000 token range.
	*
	* @param mixed $value Raw option value.
	* @return int Sanitized token count.
	*/
function rtbcb_sanitize_min_output_tokens( $value ) {
	$value = intval( $value );

	return min( 128000, max( 1, $value ) );
}

/**
	* Get testing dashboard sections and their completion state.
	*
	* The returned array is keyed by section ID and contains the section label,
	* related option key, AJAX action, dependencies, and whether the section has
	* been completed.
	*
	* @param array|null $test_results Optional preloaded test results.
	* @return array[] Section data keyed by section ID.
	*/
function rtbcb_get_dashboard_sections( $test_results = null ) {
	$empty = [];

	if ( null !== $test_results && ! is_array( $test_results ) ) {
		return $empty;
	}

	try {
if ( null === $test_results ) {
$test_results = function_exists( 'get_option' ) ? get_option( 'rtbcb_test_results', [] ) : [];
}

		if ( ! is_array( $test_results ) ) {
			$test_results = [];
		}

		$sections = [
		'rtbcb-test-company-overview'	   => [
			'label'	   => __( 'Company Overview', 'rtbcb' ),
			'requires' => [],
			'phase'	   => 1,
			'action'   => 'rtbcb_test_company_overview',
		],
		'rtbcb-test-data-enrichment'	   => [
			'label'	   => __( 'Data Enrichment', 'rtbcb' ),
			'option'   => 'rtbcb_data_enrichment',
			'requires' => [ 'rtbcb-test-company-overview' ],
			'phase'	   => 1,
			'action'   => 'rtbcb_test_data_enrichment',
		],
		'rtbcb-test-data-storage'		   => [
			'label'	   => __( 'Data Storage', 'rtbcb' ),
			'option'   => 'rtbcb_data_storage',
			'requires' => [ 'rtbcb-test-data-enrichment' ],
			'phase'	   => 1,
			'action'   => 'rtbcb_test_data_storage',
		],
		'rtbcb-test-maturity-model'		   => [
			'label'	   => __( 'Maturity Model', 'rtbcb' ),
			'requires' => [ 'rtbcb-test-data-storage' ],
			'phase'	   => 2,
			'action'   => 'rtbcb_test_maturity_model',
		],
		'rtbcb-test-rag-market-analysis'   => [
			'label'	   => __( 'RAG Market Analysis', 'rtbcb' ),
			'option'   => 'rtbcb_rag_market_analysis',
			'requires' => [ 'rtbcb-test-maturity-model' ],
			'phase'	   => 2,
			'action'   => 'rtbcb_test_rag_market_analysis',
		],
		'rtbcb-test-value-proposition'	   => [
			'label'	   => __( 'Value Proposition', 'rtbcb' ),
			'option'   => 'rtbcb_value_proposition',
			'requires' => [ 'rtbcb-test-rag-market-analysis' ],
			'phase'	   => 2,
			'action'   => 'rtbcb_test_value_proposition',
		],
		'rtbcb-test-industry-overview'		=> [
			'label'	   => __( 'Industry Overview', 'rtbcb' ),
			'requires' => [ 'rtbcb-test-value-proposition' ],
			'phase'	   => 2,
			'action'   => 'rtbcb_test_industry_overview',
		],
		'rtbcb-test-real-treasury-overview' => [
			'label'	   => __( 'Real Treasury Overview', 'rtbcb' ),
			'option'   => 'rtbcb_real_treasury_overview',
			'requires' => [ 'rtbcb-test-industry-overview' ],
			'phase'	   => 2,
			'action'   => 'rtbcb_test_real_treasury_overview',
		],
		'rtbcb-test-roadmap-generator'		=> [
			'label'	   => __( 'Roadmap Generator', 'rtbcb' ),
			'option'   => 'rtbcb_roadmap_plan',
			'requires' => [ 'rtbcb-test-real-treasury-overview' ],
			'phase'	   => 3,
		],
		'rtbcb-test-roi-calculator'			=> [
			'label'	   => __( 'ROI Calculator', 'rtbcb' ),
			'option'   => 'rtbcb_roi_results',
			'requires' => [ 'rtbcb-test-roadmap-generator' ],
			'phase'	   => 3,
			'action'   => 'rtbcb_test_calculate_roi',
		],
		'rtbcb-test-estimated-benefits'		=> [
			'label'	   => __( 'Estimated Benefits', 'rtbcb' ),
			'option'   => 'rtbcb_estimated_benefits',
			'requires' => [ 'rtbcb-test-roi-calculator' ],
			'phase'	   => 3,
			'action'   => 'rtbcb_test_estimated_benefits',
		],
		'rtbcb-test-report-assembly'		=> [
			'label'	   => __( 'Report Assembly & Delivery', 'rtbcb' ),
			'option'   => 'rtbcb_executive_summary',
			'requires' => [ 'rtbcb-test-estimated-benefits' ],
			'phase'	   => 4,
			'action'   => 'rtbcb_test_report_assembly',
		],
		'rtbcb-test-tracking-script'		=> [
			'label'	   => __( 'Tracking Scripts', 'rtbcb' ),
			'option'   => 'rtbcb_tracking_script',
			'requires' => [ 'rtbcb-test-report-assembly' ],
			'phase'	   => 5,
			'action'   => 'rtbcb_test_tracking_script',
		],
		'rtbcb-test-follow-up-email'		=> [
			'label'	   => __( 'Follow-up Emails', 'rtbcb' ),
			'option'   => 'rtbcb_follow_up_queue',
			'requires' => [ 'rtbcb-test-tracking-script' ],
			'phase'	   => 5,
			'action'   => 'rtbcb_test_follow_up_email',
		],
	];

		foreach ( $sections as $id => &$section ) {
				$result				  = rtbcb_get_last_test_result( $id, $test_results );
				$status				  = $result['status'] ?? '';
				$section['completed'] = ( 'success' === $status );
		}

		return $sections;
	} catch ( Exception $e ) {
		rtbcb_log_error( 'Dashboard sections mapping failed: ' . $e->getMessage() );
		return $empty;
	}
}

/**
	* Calculate completion percentages for each phase.
	*
	* @param array $sections Dashboard sections.
	* @param array $phases	 Optional phase numbers to include in the result.
	* @return array Percentages keyed by phase number.
	*/
function rtbcb_calculate_phase_completion( $sections, $phases = [] ) {
	$totals = [];
	$done	= [];

	foreach ( $sections as $section ) {
		$phase = isset( $section['phase'] ) ? (int) $section['phase'] : 0;
		if ( $phase ) {
			if ( ! isset( $totals[ $phase ] ) ) {
				$totals[ $phase ] = 0;
				$done[ $phase ]	  = 0;
			}
			$totals[ $phase ]++;
			if ( ! empty( $section['completed'] ) ) {
				$done[ $phase ]++;
			}
		}
	}

	$phase_keys	 = $phases ? $phases : array_keys( $totals );
	$percentages = array_fill_keys( $phase_keys, 0 );

	foreach ( $totals as $phase => $total ) {
		$percentages[ $phase ] = $total ? round( ( $done[ $phase ] / $total ) * 100 ) : 0;
	}

	ksort( $percentages );

	return $percentages;
}

/**
	* Get the first incomplete dependency for a section.
	*
	* Recursively checks the dependency chain and returns the earliest section
	* that has not been completed.
	*
	* @param string $section_id Section identifier to check.
	* @param array	$sections	All dashboard sections.
	* @return string|null The first incomplete dependency or null if all met.
	*/
function rtbcb_get_first_incomplete_dependency( $section_id, $sections, $visited = [] ) {
	if ( in_array( $section_id, $visited, true ) ) {
		return null;
	}

	$visited[] = $section_id;

	if ( empty( $sections[ $section_id ]['requires'] ) ) {
		return null;
	}

	foreach ( $sections[ $section_id ]['requires'] as $dependency ) {
		if ( empty( $sections[ $dependency ]['completed'] ) ) {
			$deep = rtbcb_get_first_incomplete_dependency( $dependency, $sections, $visited );
			if ( $deep ) {
				return $deep;
			}
			return $dependency;
		}
	}

	return null;
}

/**
	* Ensure required sections are complete before rendering a dashboard section.
	*
	* Outputs a warning linking to the first incomplete section when prerequisites
	* are missing.
	*
	* @param string $current_section Current section ID.
	* @param bool	$display_notice	 Optional. Whether to show an admin notice.
	*								 Defaults to true.
	* @return bool True when allowed, false otherwise.
	*/
function rtbcb_require_completed_steps( $current_section, $display_notice = true ) {
	static $displayed = [];

	$sections	= rtbcb_get_dashboard_sections();
	$dependency = rtbcb_get_first_incomplete_dependency( $current_section, $sections );

	if ( null === $dependency ) {
		return true;
	}

	if ( $display_notice && ! in_array( $dependency, $displayed, true ) ) {
		$phase	= isset( $sections[ $dependency ]['phase'] ) ? (int) $sections[ $dependency ]['phase'] : 0;
		$anchor = $phase ? 'rtbcb-phase' . $phase : $dependency;
		$url	= admin_url( 'admin.php?page=rtbcb-test-dashboard#' . $anchor );
		echo '<div class="notice notice-error"><p>' .
			sprintf(
				esc_html__( 'Please complete %s first.', 'rtbcb' ),
				'<a href="' . esc_url( $url ) . '">' .
				esc_html( $sections[ $dependency ]['label'] ) . '</a>'
			) .
			'</p></div>';
		$displayed[] = $dependency;
	}

	return false;
}

/**
	* Retrieve the most recent test result for a section.
	*
	* @param string		$section_id	  Section identifier.
	* @param array|null $test_results Optional preloaded test results.
	* @return array|null Matching result or null when none found.
	*/
function rtbcb_get_last_test_result( $section_id, $test_results = null ) {
if ( null === $test_results ) {
$test_results = function_exists( 'get_option' ) ? get_option( 'rtbcb_test_results', [] ) : [];
}

	if ( ! is_array( $test_results ) ) {
		return null;
	}

	foreach ( $test_results as $result ) {
		if ( isset( $result['section'] ) && $result['section'] === $section_id ) {
			return $result;
		}
	}

	return null;
}

/**
	* Render a button to start a new company analysis.
	*
	* The button clears existing company data and navigates to the Company
	* Overview section of the testing dashboard so a new analysis can begin.
	*
	* @return void
	*/
function rtbcb_render_start_new_analysis_button() {
	echo '<p>';
	echo '<button type="button" id="rtbcb-start-new-analysis" class="button">' .
		esc_html__( 'Start New Analysis', 'rtbcb' ) . '</button>';
	wp_nonce_field( 'rtbcb_test_company_overview', 'rtbcb_clear_current_company_nonce' );
	echo '</p>';
}

function rtbcb_check_database_health() {
	global $wpdb;

	$tables = [
		'rtbcb_leads'	  => $wpdb->prefix . 'rtbcb_leads',
		'rtbcb_rag_index' => $wpdb->prefix . 'rtbcb_rag_index',
	];

	$status = [];

	foreach ( $tables as $key => $table_name ) {
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		$status[ $key ] = [
			'exists' => ! empty( $exists ),
			'name'	 => $table_name,
		];
	}

	return $status;
}

/**
	* Sanitize form input data
	*
	* @param array $data Raw form data
	* @return array Sanitized data
	*/
function rtbcb_sanitize_form_data( $data ) {
	$sanitized = [];

	// Email
	if ( isset( $data['email'] ) ) {
		$sanitized['email'] = sanitize_email( $data['email'] );
	}

	// Text fields
	$text_fields = [ 'company_size', 'industry' ];
	foreach ( $text_fields as $field ) {
		if ( isset( $data[ $field ] ) ) {
			$sanitized[ $field ] = sanitize_text_field( $data[ $field ] );
		}
	}

	// Numeric fields
	$numeric_fields = [
		'hours_reconciliation'	 => [ 'min' => 0,	'max' => 168 ],
		'hours_cash_positioning' => [ 'min' => 0,	'max' => 168 ],
		'num_banks'				 => [ 'min' => 1,	'max' => 50 ],
		'ftes'					 => [ 'min' => 0.5, 'max' => 100 ],
	];

	foreach ( $numeric_fields as $field => $limits ) {
		if ( isset( $data[ $field ] ) ) {
			$value = floatval( $data[ $field ] );
			$value = max( $limits['min'], min( $limits['max'], $value ) );
			$sanitized[ $field ] = $value;
		}
	}

	// Pain points array
	if ( isset( $data['pain_points'] ) && is_array( $data['pain_points'] ) ) {
		$valid_pain_points = [
			'manual_processes',
			'poor_visibility',
			'forecast_accuracy',
			'compliance_risk',
			'bank_fees',
			'integration_issues',
		];

		$sanitized['pain_points'] = array_filter(
			array_map( 'sanitize_text_field', $data['pain_points'] ),
			function ( $point ) use ( $valid_pain_points ) {
				return in_array( $point, $valid_pain_points, true );
			}
		);
	}

	return $sanitized;
}

/**
	* Validate email domain
	*
	* @param string $email Email address
	* @return bool True if valid business email
	*/
function rtbcb_is_business_email( $email ) {
	$consumer_domains = [
		'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com',
		'aol.com', 'icloud.com', 'mail.com', 'protonmail.com',
	];

	$domain = substr( strrchr( $email, '@' ), 1 );
	return ! in_array( strtolower( $domain ), $consumer_domains, true );
}

/**
	* Validate OpenAI API key format.
	*
	* Accepts standard and project-scoped keys which start with "sk-" and may
	* include letters, numbers, hyphens, colons, and underscores.
	*
	* @param string $api_key API key.
	* @return bool Whether the format is valid.
	*/
function rtbcb_is_valid_openai_api_key( $api_key ) {
	return preg_match( '/^sk-[A-Za-z0-9_:-]{48,}$/', $api_key );
}

/**
	* Normalize a model name by stripping date suffixes.
	*
	* @param string $model Raw model identifier.
	* @return string Model name without version date.
	*/
function rtbcb_normalize_model_name( $model ) {
	$model = sanitize_text_field( $model );
	return preg_replace( '/^(gpt-[^\s]+?)(?:-\d{4}-\d{2}-\d{2})$/', '$1', $model );
}

/**
	* Send the generated report to the user via email.
	*
	* @param array  $form_data Submitted form data.
	* @param string $report_url URL to the generated report.
	* @param callable $mailer     Optional mailer function for testing.
	*
	* @return void
	*/
function rtbcb_send_report_email( $form_data, $report_url, $mailer = 'wp_mail' ) {
		$email = isset( $form_data['email'] ) ? sanitize_email( $form_data['email'] ) : '';

		if ( empty( $email ) || empty( $report_url ) ) {
				return;
		}

		$subject = __( 'Your Business Case Report', 'rtbcb' );
		$message = sprintf( __( 'View your business case report: %s', 'rtbcb' ), esc_url( $report_url ) );

		if ( is_callable( $mailer ) ) {
				call_user_func( $mailer, $email, $subject, $message );
		}
}

/**
	* Get client information for analytics
	*
	* @return array Client data
	*/
function rtbcb_get_client_info() {
	return [
		'ip'		  => rtbcb_get_client_ip(),
		'user_agent'  => isset( $_SERVER['HTTP_USER_AGENT'] ) ?
			sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
		'referrer'	  => isset( $_SERVER['HTTP_REFERER'] ) ?
			esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
		'utm_source'  => isset( $_GET['utm_source'] ) ?
			sanitize_text_field( wp_unslash( $_GET['utm_source'] ) ) : '',
		'utm_medium'  => isset( $_GET['utm_medium'] ) ?
			sanitize_text_field( wp_unslash( $_GET['utm_medium'] ) ) : '',
		'utm_campaign'=> isset( $_GET['utm_campaign'] ) ?
			sanitize_text_field( wp_unslash( $_GET['utm_campaign'] ) ) : '',
	];
}

/**
	* Get client IP address
	*
	* @return string IP address
	*/
function rtbcb_get_client_ip() {
	$ip_keys = [ 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ];

	foreach ( $ip_keys as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) ) {
			$ip = wp_unslash( $_SERVER[ $key ] );

			// Handle comma-separated IPs
			if ( strpos( $ip, ',' ) !== false ) {
				$ip = trim( explode( ',', $ip )[0] );
			}

			// Validate IP
			if ( filter_var(
				$ip,
				FILTER_VALIDATE_IP,
				FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
			) ) {
				return $ip;
			}
		}
	}

	return isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
}

/**
	* Recursively sanitize values using sanitize_text_field.
	*
	* @param mixed $data Data to sanitize.
	* @return mixed Sanitized data.
	*/
function rtbcb_recursive_sanitize_text_field( $data ) {
	if ( is_array( $data ) ) {
		foreach ( $data as $key => $value ) {
			$data[ $key ] = rtbcb_recursive_sanitize_text_field( $value );
		}

		return $data;
	}

	return sanitize_text_field( (string) $data );
}

/**
	* Set the current lead context for logging.
	*
	* @param int	$lead_id	Lead ID.
	* @param string $lead_email Lead email address.
	* @return void
	*/
function rtbcb_set_current_lead( $lead_id, $lead_email = '' ) {
		$GLOBALS['rtbcb_current_lead'] = [
	'id'	=> intval( $lead_id ),
	'email' => sanitize_email( $lead_email ),
	];
}

/**
	* Retrieve the current lead context.
	*
	* @return array|null Array with 'id' and 'email' or null if not set.
	*/
function rtbcb_get_current_lead() {
				return isset( $GLOBALS['rtbcb_current_lead'] ) ? $GLOBALS['rtbcb_current_lead'] : null;
}

/**
	* Clean JSON response from API calls.
	*
	* Attempts to decode the response as JSON and returns the decoded array on
	* success. If decoding fails or the input is not a JSON string, the original
	* response is returned.
	*
	* @param mixed $response Raw response string or data.
	* @return mixed Decoded array or original response.
*/
function rtbcb_clean_json_response( $response ) {
		if ( is_string( $response ) ) {
				$decoded = json_decode( $response, true );

				if ( json_last_error() === JSON_ERROR_NONE ) {
				        return $decoded;
				}
		}

		return $response;
}

/**
	* Extract final JSON payload from an OpenAI response body.
	*
	* Decodes the outer response structure and, when present, parses any nested
	* JSON found in message content. If no nested JSON is detected, the decoded
	* outer array is returned.
	*
	* @param string $raw_body Raw response body from the OpenAI Responses API.
	* @return array|false Decoded JSON array on success, false on failure.
	*/
function rtbcb_extract_json_from_openai_response( $raw_body ) {
	$outer = json_decode( $raw_body, true );
	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $outer ) ) {
		return false;
	}

	$text = '';
	if ( isset( $outer['output_text'] ) && is_string( $outer['output_text'] ) ) {
		$text = trim( $outer['output_text'] );
	} elseif ( isset( $outer['output'] ) && is_array( $outer['output'] ) ) {
		foreach ( $outer['output'] as $chunk ) {
			if ( 'message' === ( $chunk['type'] ?? '' ) && isset( $chunk['content'] ) && is_array( $chunk['content'] ) ) {
				foreach ( $chunk['content'] as $piece ) {
					if ( isset( $piece['text'] ) && '' !== trim( $piece['text'] ) ) {
						$text .= $piece['text'];
					}
				}
			}
		}
	}

	if ( '' === $text ) {
		return $outer;
	}
	$inner = json_decode( $text, true );
	if ( JSON_ERROR_NONE === json_last_error() ) {
		return $inner;
	}

	return $outer;
}
/**
	* Safe JSON encode without escaping issues.
	*
	* Uses wp_json_encode with flags to prevent unnecessary escaping.
	*
	* @param mixed $data Data to encode.
	* @return string|false JSON encoded string or false on failure.
	*/
function rtbcb_safe_json_encode( $data ) {
	return wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

/**
	* Clean logging function.
	*
	* Logs messages only when WP_DEBUG is enabled and safely encodes any provided
	* data to avoid escaping issues.
	*
	* @param string $message Log message.
	* @param mixed  $data    Optional data to encode.
	* @return void
	*/
function rtbcb_log_clean( $message, $data = null ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$log_message = $message;
		if ( null !== $data ) {
			$log_message .= ': ' . rtbcb_safe_json_encode( $data );
		}
		error_log( $log_message );
	}
}

/**
		* Log API debug messages.
		*
		* @param string $message Log message.
	* @param mixed	$data	 Optional data.
	* @return void
	*/
function rtbcb_log_api_debug( $message, $data = null ) {
		$lead = rtbcb_get_current_lead();
	if ( $lead ) {
	$message .= ' [Lead ID: ' . $lead['id'] . ' Email: ' . $lead['email'] . ']';
}
$log_message = 'RTBCB API Debug: ' . $message;
if ( $data ) {
$log_message .= ' - ' . wp_json_encode( $data );
}
error_log( $log_message );
}

function rtbcb_log_error( $message, $context = null ) {
$lead = rtbcb_get_current_lead();
if ( $lead ) {
$message .= ' [Lead ID: ' . $lead['id'] . ' Email: ' . $lead['email'] . ']';
}
$log_message = 'RTBCB Error: ' . $message;
if ( $context ) {
$log_message .= ' - Context: ' . wp_json_encode( $context );
}

if ( class_exists( 'RTBCB_Logger' ) ) {
$logger_context = [ 'message' => $log_message ];
if ( null !== $context ) {
$logger_context['context'] = $context;
}
RTBCB_Logger::log( 'error', $logger_context );
}

error_log( $log_message );
}

function rtbcb_setup_ajax_logging() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_reporting( E_ALL );
				ini_set( 'display_errors', 1 );
				ini_set( 'log_errors', 1 );
		}
}

/**
 * Convert shorthand byte values to an integer number of bytes.
 *
 * Acts as a fallback for wp_convert_hr_to_bytes when WordPress is not loaded.
 *
 * @param string $value Shorthand byte value (e.g. "256M").
 * @return int Number of bytes.
 */
function rtbcb_convert_hr_to_bytes( $value ) {
	   $value = strtolower( trim( $value ) );

	   if ( is_numeric( $value ) ) {
			   return (int) $value;
	   }

	   $number = (int) $value;
	   $unit   = substr( $value, -1 );

	   switch ( $unit ) {
			   case 'g':
					   $number *= 1024;
					   // No break.
			   case 'm':
					   $number *= 1024;
					   // No break.
			   case 'k':
					   $number *= 1024;
					   break;
	   }

	   return $number;
}

function rtbcb_increase_memory_limit() {
	   $current = ini_get( 'memory_limit' );
	   $current_bytes = function_exists( 'wp_convert_hr_to_bytes' ) ? wp_convert_hr_to_bytes( $current ) : rtbcb_convert_hr_to_bytes( $current );
	   $required_bytes = 256 * 1024 * 1024;

	if ( $current_bytes < $required_bytes ) {
		ini_set( 'memory_limit', '256M' );
	}
}

/**
	* Log current and peak memory usage.
	*
	* @param string $checkpoint Identifier for log position.
	* @return void
	*/
function rtbcb_log_memory_usage( $checkpoint ) {
	$usage = memory_get_usage( true );
	$peak  = memory_get_peak_usage( true );

	error_log( 'RTBCB Memory [' . $checkpoint . ']: Current=' . size_format( $usage ) . ', Peak=' . size_format( $peak ) );
}

function rtbcb_get_memory_status() {
		return [
				'current' => memory_get_usage( true ),
				'peak'	  => memory_get_peak_usage( true ),
				'limit'	  => wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) ),
		];
}

/**
	* Determine if provided inputs qualify for synchronous processing.
	*
	* Simple cases with minimal data can be handled without background jobs.
	* A case is considered "simple" when all of the following are under their
	* respective thresholds:
	* - Number of banks involved.
	* - Full-time equivalents (FTEs) required.
	* - Combined weekly hours spent on reconciliation and cash positioning.
	*
	* These thresholds are intentionally conservative to keep synchronous
	* processing fast. Adjust the values below or use the `rtbcb_is_simple_case`
	* filter to customize the behavior.
	*
	* @param array $user_inputs Sanitized user inputs.
	* @return bool True when synchronous execution is allowed.
	*/
function rtbcb_is_simple_case( $user_inputs ) {
		$user_inputs = is_array( $user_inputs ) ? $user_inputs : [];

		// Thresholds for determining a simple case.
		$max_banks = 2;	 // No more than two banking relationships.
		$max_ftes  = 2;	 // Teams of two FTEs or fewer.
		$max_hours = 20; // Twenty or fewer total manual hours per week.

		$num_banks = absint( $user_inputs['num_banks'] ?? 0 );
		$ftes	   = absint( $user_inputs['ftes'] ?? 0 );
		$hours	   = absint( $user_inputs['hours_reconciliation'] ?? 0 ) + absint( $user_inputs['hours_cash_positioning'] ?? 0 );

		$is_simple = ( $num_banks <= $max_banks && $ftes <= $max_ftes && $hours <= $max_hours );

		/**
		* Filter whether a case is simple enough for synchronous execution.
		*
		* @param bool  $is_simple	Whether case is considered simple.
		* @param array $user_inputs User input data.
		*/
		return (bool) apply_filters( 'rtbcb_is_simple_case', $is_simple, $user_inputs );
}

/**
	* Retrieve sample user inputs for testing purposes.
	*
	* @return array Sample user inputs.
	*/
function rtbcb_get_sample_inputs() {
	return [
		'company_name'			 => 'Acme Manufacturing Corp',
		'company_size'			 => '$500M-$2B',
		'industry'				 => 'Manufacturing',
		'hours_reconciliation'	 => 15,
		'hours_cash_positioning' => 10,
		'num_banks'				 => 5,
		'ftes'					 => 3,
		'pain_points'			 => [
			'manual_processes',
			'poor_visibility',
			'forecast_accuracy'
		],
		'business_objective'	 => 'reduce_costs',
		'implementation_timeline'=> '6_months',
		'budget_range'			 => '100k_500k',
	];
}

/**
	* Retrieve predefined sample report scenarios.
	*
	* @return array Map of scenario keys to labels and input data.
	*/
function rtbcb_get_sample_report_forms() {
	return [
		'enterprise_manufacturer' => [
			'label' => __( 'Enterprise Manufacturer', 'rtbcb' ),
			'data'	=> [
				'company_name'	=> 'Acme Manufacturing',
				'company_size'	=> '1000-5000',
				'industry'		=> 'Manufacturing',
				'location'		=> 'USA',
				'analysis_date' => current_time( 'Y-m-d' ),
			],
		],
		'tech_startup'			 => [
			'label' => __( 'Tech Startup', 'rtbcb' ),
			'data'	=> [
				'company_name'	=> 'Innovatech',
				'company_size'	=> '1-50',
				'industry'		=> 'Technology',
				'location'		=> 'UK',
				'analysis_date' => current_time( 'Y-m-d' ),
			],
		],
	];
}

/**
	* Map scenario keys to sample report inputs.
	*
	* @param array	$inputs		  Default inputs.
	* @param string $scenario_key Scenario identifier.
	* @return array Filtered inputs.
	*/
function rtbcb_map_sample_report_inputs( $inputs, $scenario_key ) {
	$empty = [];

	if ( ! is_array( $inputs ) ) {
		rtbcb_log_error( 'Sample report inputs must be an array.' );
		return $empty;
	}

	try {
		$forms = rtbcb_get_sample_report_forms();
		if ( $scenario_key && isset( $forms[ $scenario_key ] ) ) {
		return $forms[ $scenario_key ]['data'];
		}

		return $inputs;
	} catch ( Exception $e ) {
		rtbcb_log_error( 'Sample report input mapping failed: ' . $e->getMessage() );
		return $empty;
	}
}
if ( function_exists( 'add_filter' ) ) {
	add_filter( 'rtbcb_sample_report_inputs', 'rtbcb_map_sample_report_inputs', 10, 2 );
}

/**
	* Generate a category recommendation with enriched context for testing.
	*
	* Sanitizes requirement inputs, runs the category recommender, augments the
	* response with human readable names, reasoning, alternative categories,
	* confidence and scoring data, and optional implementation guidance.
	*
	* @param array $requirements User provided requirement data.
	* @return array Structured recommendation data.
	*/
function rtbcb_test_generate_category_recommendation( $analysis ) {
	$analysis = is_array( $analysis ) ? $analysis : [];

	$payload = [
		'company_overview'	  => sanitize_textarea_field( $analysis['company_overview'] ?? '' ),
		'industry_insights'	  => sanitize_textarea_field( $analysis['industry_insights'] ?? '' ),
		'maturity_model'	  => sanitize_textarea_field( $analysis['maturity_model'] ?? '' ),
		'treasury_challenges' => sanitize_textarea_field( $analysis['treasury_challenges'] ?? '' ),
		'extra_requirements'  => sanitize_textarea_field( $analysis['extra_requirements'] ?? '' ),
	];

	try {
		$api_key = rtbcb_get_openai_api_key();
		if ( ! rtbcb_has_openai_api_key() ) {
			return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
		}

		$model_option = function_exists( 'get_option' ) ? get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) ) : rtbcb_get_default_model( 'mini' );
		$model		  = function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $model_option ) : $model_option;

		$system_prompt = 'You are a treasury technology advisor. Based on the company overview, industry insights, technology overview, and treasury challenges provided, recommend the most suitable solution category (cash_tools, tms_lite, trms). Return JSON with keys "recommended", "reasoning", and "alternatives" (array of objects with "category" and "reasoning").';

		$input = "Company Overview: {$payload['company_overview']}";
		$input .= "\nIndustry Insights: {$payload['industry_insights']}";
		$input .= "\nMaturity Assessment: {$payload['maturity_model']}";
		$input .= "\nTreasury Challenges: {$payload['treasury_challenges']}";
		if ( ! empty( $payload['extra_requirements'] ) ) {
			$input .= "\nExtra Requirements: {$payload['extra_requirements']}";
		}

				$response = rtbcb_wp_remote_post_with_retry(
						'https://api.openai.com/v1/responses',
						[
								'headers' => [
										'Content-Type'	=> 'application/json',
										'Authorization' => 'Bearer ' . $api_key,
								],
								'body'	  => wp_json_encode(
										[
												'model'		   => $model,
												'instructions' => $system_prompt,
												'input'		   => $input,
										]
								),
								'timeout' => rtbcb_get_api_timeout(),
						]
				);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'llm_failure', __( 'Unable to generate recommendation at this time.', 'rtbcb' ) );
		}

		$body	 = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );
		$content = '';

		if ( isset( $decoded['output_text'] ) ) {
			$content = is_array( $decoded['output_text'] ) ? implode( ' ', (array) $decoded['output_text'] ) : $decoded['output_text'];
		} elseif ( ! empty( $decoded['output'] ) && is_array( $decoded['output'] ) ) {
			foreach ( $decoded['output'] as $message ) {
				if ( empty( $message['content'] ) || ! is_array( $message['content'] ) ) {
					continue;
				}

				foreach ( $message['content'] as $chunk ) {
					if ( isset( $chunk['text'] ) && '' !== $chunk['text'] ) {
						$content = $chunk['text'];
						break 2;
					}
				}
			}
		}

		$content = sanitize_textarea_field( $content );

		if ( '' === $content ) {
			return new WP_Error( 'llm_empty_response', __( 'No recommendation returned.', 'rtbcb' ) );
		}

		$json = json_decode( $content, true );
		if ( ! is_array( $json ) ) {
			return new WP_Error( 'invalid_response', __( 'Invalid recommendation format.', 'rtbcb' ) );
		}

		$recommended_key = sanitize_key( $json['recommended'] ?? '' );
		$category_info	 = RTBCB_Category_Recommender::get_category_info( $recommended_key );

		$alternatives = [];
		if ( ! empty( $json['alternatives'] ) && is_array( $json['alternatives'] ) ) {
			foreach ( $json['alternatives'] as $alt ) {
				$alt_key  = sanitize_key( $alt['category'] ?? '' );
				$alt_info = RTBCB_Category_Recommender::get_category_info( $alt_key );
				if ( $alt_key && $alt_info ) {
					$alternatives[] = [
						'key'		=> $alt_key,
						'name'		=> $alt_info['name'] ?? '',
						'reasoning' => sanitize_text_field( $alt['reasoning'] ?? '' ),
					];
				}
			}
		}

		return [
			'recommended' => [
				'key'		  => $recommended_key,
				'name'		  => $category_info['name'] ?? '',
				'description' => $category_info['description'] ?? '',
				'features'	  => $category_info['features'] ?? [],
				'ideal_for'	  => $category_info['ideal_for'] ?? '',
			],
			'reasoning'	   => sanitize_textarea_field( $json['reasoning'] ?? '' ),
			'alternatives' => $alternatives,
		];
	} catch ( \Throwable $e ) {
		return new WP_Error( 'llm_exception', __( 'Unable to generate recommendation at this time.', 'rtbcb' ) );
	}
}

/**
	* Test generating industry commentary using the LLM.
	*
	* @param string $industry Industry slug.
	* @return string|WP_Error Commentary text or error object.
	*/
function rtbcb_test_generate_industry_commentary( $industry ) {
	$industry = sanitize_text_field( $industry );

	try {
		$llm		= new RTBCB_LLM_Optimized();
		$commentary = $llm->generate_industry_commentary( $industry );
	} catch ( \Throwable $e ) {
		return new WP_Error( 'llm_exception', __( 'Unable to generate commentary at this time.', 'rtbcb' ) );
	}

	return $commentary;
}

/**
	* Test generating a company overview using the LLM.
	*
	* @param string $company_name Company name.
	* @return array|WP_Error Structured overview array or error object.
	*/
function rtbcb_test_generate_company_overview( $company_name ) {
	if ( ! class_exists( 'RTBCB_LLM' ) ) {
		return new WP_Error( 'missing_class', __( 'LLM class not available', 'rtbcb' ) );
	}

	$company_name = sanitize_text_field( $company_name );

	try {
		$llm	  = new RTBCB_LLM_Optimized();
		$overview = $llm->generate_company_overview( $company_name );
	} catch ( \Throwable $e ) {
		return new WP_Error( 'llm_exception', $e->getMessage() );
	}

	return $overview;
}

/**
	* Test generating a treasury tech overview using the LLM.
	*
	* @param array $company_data Company data including focus areas and complexity.
	* @return string|WP_Error Overview text or error object.
	*/
/**
	* Test assessing treasury maturity.
	*
	* @param array $company_data Company information.
	* @return array|WP_Error Assessment data or error.
	*/
function rtbcb_test_generate_maturity_model( $company_data ) {
	if ( ! class_exists( 'RTBCB_Maturity_Model' ) ) {
		return new WP_Error( 'missing_class', __( 'Maturity model class not available', 'rtbcb' ) );
	}

	$model		 = new RTBCB_Maturity_Model();
	$company_data = is_array( $company_data ) ? $company_data : [];
	return $model->assess( $company_data );
}

/**
	* Test running RAG market analysis.
	*
	* @param string $query Search query.
	* @return array|WP_Error Vendor shortlist or error.
	*/
function rtbcb_test_rag_market_analysis( $query ) {
	if ( ! class_exists( 'RTBCB_RAG' ) ) {
		return new WP_Error( 'missing_class', __( 'RAG class not available', 'rtbcb' ) );
	}

	$rag	= new RTBCB_RAG();
	$query	= sanitize_text_field( $query );
	$result = $rag->get_context( $query, 3 );

	$vendors = [];
	foreach ( $result as $meta ) {
		if ( isset( $meta['name'] ) ) {
			$vendors[] = sanitize_text_field( $meta['name'] );
		}
	}

	return $vendors;
}

/**
	* Test generating a value proposition.
	*
	* @param array $company_data Company information.
	* @return string|WP_Error Opening paragraph or error.
	*/
function rtbcb_test_generate_value_proposition( $company_data ) {
	$company_data = is_array( $company_data ) ? $company_data : [];
	$company_name = isset( $company_data['name'] ) ? sanitize_text_field( $company_data['name'] ) : '';

	if ( class_exists( 'RTBCB_Maturity_Model' ) ) {
		$model		= new RTBCB_Maturity_Model();
		$assessment = $model->assess( $company_data );
		$level		= $assessment['level'];
	} else {
		$level = __( 'basic', 'rtbcb' );
	}

	$business_case_data = [
		'company_name'		=> $company_name,
		'executive_summary' => [
			'strategic_positioning' => sprintf(
				__( 'Real Treasury helps %1$s advance from %2$s maturity toward optimized performance.', 'rtbcb' ),
				$company_name,
				strtolower( $level )
			),
		],
	];

	ob_start();
	include RTBCB_DIR . 'templates/comprehensive-report-template.php';
	$output = ob_get_clean();

	if ( preg_match( '/<div class="rtbcb-strategic-positioning">\s*<h3>.*?<\/h3>\s*<p>(.*?)<\/p>/s', $output, $matches ) ) {
		return sanitize_text_field( wp_strip_all_tags( $matches[1] ) );
	}

	return new WP_Error( 'no_paragraph', __( 'Unable to generate value proposition.', 'rtbcb' ) );
}

/**
	* Test generating an industry overview using company data.
	*
	* @param array $company_data Company information including industry, size,
	*							 geography, and business model.
	* @return string|WP_Error Overview text or error object.
	*/
function rtbcb_test_generate_industry_overview( $company_data ) {
	if ( ! class_exists( 'RTBCB_LLM' ) ) {
		return new WP_Error( 'missing_class', __( 'LLM class not available', 'rtbcb' ) );
	}

	$company_data = is_array( $company_data ) ? $company_data : [];
	$industry = isset( $company_data['industry'] ) ? sanitize_text_field( $company_data['industry'] ) : '';
	$size	  = isset( $company_data['size'] ) ? sanitize_text_field( $company_data['size'] ) : '';

	if ( empty( $industry ) || empty( $size ) ) {
		return new WP_Error( 'missing_data', __( 'Industry and company size required', 'rtbcb' ) );
	}

	$llm = new RTBCB_LLM_Optimized();
	return $llm->generate_industry_overview( $industry, $size );
}

/**
	* Test generating a Real Treasury overview using the LLM.
	*
	* @param array $company_data {
	*	  Company context data.
	*
	*	  @type bool   $include_portal Include portal integration details.
	*	  @type string $company_size   Company size description.
	*	  @type string $industry	   Company industry.
	*	  @type array  $challenges	   List of identified challenges.
	*	  @type array  $categories	   Optional vendor categories to highlight.
	* }
	* @return string|WP_Error Overview text or error object.
	*/
function rtbcb_test_generate_real_treasury_overview( $include_portal = false, $categories = [] ) {
	if ( ! class_exists( 'RTBCB_LLM' ) ) {
		return new WP_Error( 'missing_class', __( 'LLM class not available', 'rtbcb' ) );
	}

	$company = rtbcb_get_current_company();
	if ( empty( $company ) ) {
		return new WP_Error( 'no_company', __( 'No company data available', 'rtbcb' ) );
	}

	$company_data = [
		'include_portal' => (bool) $include_portal,
		'company_size'	 => sanitize_text_field( $company['size'] ?? '' ),
		'industry'		 => sanitize_text_field( $company['industry'] ?? '' ),
		'challenges'	 => array_map( 'sanitize_text_field', $company['challenges'] ?? [] ),
		'categories'	 => array_map( 'sanitize_text_field', (array) $categories ),
	];

	$llm = new RTBCB_LLM_Optimized();
	return $llm->generate_real_treasury_overview( $company_data );
}

/**
	* Test generating a benefits estimate using the LLM.
	*
	* @param array	$company_data		 Company context including revenue, staff count and efficiency.
	* @param string $recommended_category Solution category.
	* @return array|WP_Error Structured estimate array or error object.
	*/
function rtbcb_test_generate_benefits_estimate( $company_data, $recommended_category ) {
	$company_data = is_array( $company_data ) ? $company_data : [];
	$revenue	  = isset( $company_data['revenue'] ) ? floatval( $company_data['revenue'] ) : 0;
	$staff_count  = isset( $company_data['staff_count'] ) ? intval( $company_data['staff_count'] ) : 0;
	$efficiency	  = isset( $company_data['efficiency'] ) ? floatval( $company_data['efficiency'] ) : 0;
	$recommended_category = sanitize_text_field( $recommended_category );

	try {
		$llm	  = new RTBCB_LLM_Optimized();
		$estimate = $llm->generate_benefits_estimate( $revenue, $staff_count, $efficiency, $recommended_category );
	} catch ( \Throwable $e ) {
		return new WP_Error( 'llm_exception', __( 'Unable to estimate benefits at this time.', 'rtbcb' ) );
	}

	return $estimate;
}

/**
	* Test generating executive summary via the LLM.
	*
	* @return array|WP_Error Summary data or error object.
	*/
function rtbcb_test_generate_executive_summary() {
	if ( ! class_exists( 'RTBCB_LLM' ) ) {
		return new WP_Error( 'missing_class', __( 'LLM class not available', 'rtbcb' ) );
	}

	$company = rtbcb_get_current_company();
$roi	 = function_exists( 'get_option' ) ? get_option( 'rtbcb_roi_results', [] ) : [];

	$llm	= new RTBCB_LLM_Optimized();
	$result = $llm->generate_comprehensive_business_case( $company, $roi, [], null );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$summary = isset( $result['executive_summary'] ) ? $result['executive_summary'] : [];

	$summary = [
		'strategic_positioning'	  => sanitize_text_field( $summary['strategic_positioning'] ?? '' ),
		'business_case_strength'  => sanitize_text_field( $summary['business_case_strength'] ?? '' ),
		'key_value_drivers'		  => array_map( 'sanitize_text_field', $summary['key_value_drivers'] ?? [] ),
		'executive_recommendation'=> sanitize_text_field( $summary['executive_recommendation'] ?? '' ),
	];

	update_option( 'rtbcb_executive_summary', $summary );

	return $summary;
}

/**
 * Parse GPT-5 Responses API output with quality validation.
 *
 * @deprecated Use RTBCB_Response_Parser::parse_business_case() directly.
 *
 * @param array $response Response data from GPT-5 API.
 * @return array|WP_Error Parsed response.
 */
function rtbcb_parse_gpt5_business_case_response( $response ) {
	$parser = new RTBCB_Response_Parser();
	return $parser->parse_business_case( $response );
}

/**
 * Parse a generic GPT-5 response.
 *
 * @deprecated Use RTBCB_Response_Parser::parse() directly.
 *
 * @param array $response Response data from GPT-5 API.
 * @param bool  $store_raw Optional. Include decoded response body.
 * @return array|WP_Error Parsed response.
 */
function rtbcb_parse_gpt5_response( $response, $store_raw = false ) {
	$parser = new RTBCB_Response_Parser();
	return $parser->parse( $response, $store_raw );
}

/**
	* Proxy requests to the OpenAI Responses API.
	*
	* Reads the API key from options and forwards the provided request body to
	* the OpenAI endpoint.
	*
	* @return void
	*/

function rtbcb_proxy_openai_responses() {
        // Only handle our specific AJAX action; bail early for others.
        if ( ! isset( $_REQUEST['action'] ) || 'rtbcb_openai_responses' !== sanitize_key( wp_unslash( $_REQUEST['action'] ) ) ) {
                // Jetpack also routes requests through admin-ajax.php; avoid sending
                // streaming headers for unrelated actions.
                return;
        }

	$api_key = rtbcb_get_openai_api_key();
	if ( ! rtbcb_has_openai_api_key() ) {
		wp_send_json_error( [ 'message' => __( 'OpenAI API key not configured.', 'rtbcb' ) ], 500 );
	}
	if ( ! function_exists( 'curl_init' ) ) {
		wp_send_json_error( [ 'message' => __( 'The cURL PHP extension is required.', 'rtbcb' ) ], 500 );
	}

	if ( isset( $_POST['nonce'] ) ) {
		check_ajax_referer( 'rtbcb_openai_responses', 'nonce' );
	}

	$body = isset( $_POST['body'] ) ? wp_unslash( $_POST['body'] ) : '';
	if ( '' === $body ) {
		wp_send_json_error( [ 'message' => __( 'Missing request body.', 'rtbcb' ) ], 400 );
	}

	$body_array = json_decode( $body, true );
	if ( JSON_ERROR_NONE !== json_last_error() ) {
		wp_send_json_error( [ 'message' => __( 'Invalid JSON body.', 'rtbcb' ) ], 400 );
	}

	$company          = rtbcb_get_current_company();
	$user_email       = isset( $company['email'] ) ? sanitize_email( $company['email'] ) : '';
	$company_name = isset( $company['name'] ) ? sanitize_text_field( $company['name'] ) : '';

	$config                    = rtbcb_get_gpt5_config();
	$max_output_tokens = intval( $body_array['max_output_tokens'] ?? $config['max_output_tokens'] );
	$min_tokens                = intval( $config['min_output_tokens'] );
	$max_output_tokens = min( 128000, max( $min_tokens, $max_output_tokens ) );
	$body_array['max_output_tokens'] = $max_output_tokens;
	$body_array['stream']            = true;
	$payload                         = wp_json_encode( $body_array );

	if ( function_exists( 'rtbcb_is_wpcom' ) && rtbcb_is_wpcom() ) {
		wp_send_json_error( [ 'code' => 'streaming_unsupported', 'message' => __( 'Streaming is not supported on this hosting environment.', 'rtbcb' ) ], 400 );
		return;
	}

	nocache_headers();
	header( 'Content-Type: text/event-stream' );
	header( 'Cache-Control: no-cache' );
	header( 'Connection: keep-alive' );

	$timeout = intval( function_exists( 'get_option' ) ? get_option( 'rtbcb_responses_timeout', 120 ) : 120 );
	if ( $timeout <= 0 ) {
		$timeout = 120;
	}

	$ch = curl_init( 'https://api.openai.com/v1/responses' );
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Authorization: Bearer ' . $api_key,
	] );
	curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
	curl_setopt( $ch, CURLOPT_WRITEFUNCTION, function ( $curl, $data ) {
		echo $data;
		if ( function_exists( 'flush' ) ) {
			flush();
		}
		return strlen( $data );
	} );

	$ok        = curl_exec( $ch );
	$error = curl_error( $ch );
	curl_close( $ch );

	if ( false === $ok && '' !== $error ) {
		$msg = sanitize_text_field( $error );
		echo 'data: ' . wp_json_encode( [ 'error' => $msg ] ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	wp_die();
}
/**
	* Placeholder handler for professional report generation.
	*
	* @return void
	*/
function rtbcb_generate_report() {
	if ( empty( $_POST['rtbcb_nonce'] ) ) {
		wp_send_json_error( [ 'message' => __( 'Missing security token.', 'rtbcb' ) ], 400 );
	}
	
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rtbcb_nonce'] ) ), 'rtbcb_generate' ) ) {
		wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
	}
	
	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	echo wp_kses_post( '<p>' . __( 'Report generation endpoint not yet implemented.', 'rtbcb' ) . '</p>' );
	wp_die();
}

/**
	* Background handler for OpenAI response jobs.
	*
	* @param string $job_id	 Job identifier.
	* @param int	$user_id User identifier.
	* @return void
	*/
function rtbcb_handle_openai_responses_job( $job_id, $user_id ) {
	$job_id	 = sanitize_key( $job_id );
	$user_id = intval( $user_id );

	$api_key = rtbcb_get_openai_api_key();
	if ( ! rtbcb_has_openai_api_key() ) {
		set_transient(
			'rtbcb_openai_job_' . $job_id,
			[
				'status'  => 'error',
				'message' => __( 'OpenAI API key not configured.', 'rtbcb' ),
			],
			HOUR_IN_SECONDS
		);
		return;
	}

	$body = get_transient( 'rtbcb_openai_job_' . $job_id . '_body' );
	if ( false === $body ) {
		set_transient(
			'rtbcb_openai_job_' . $job_id,
			[
				'status'  => 'error',
				'message' => __( 'Job request not found.', 'rtbcb' ),
			],
			HOUR_IN_SECONDS
		);
		return;
	}
	delete_transient( 'rtbcb_openai_job_' . $job_id . '_body' );

	$body_array = json_decode( $body, true );
	if ( ! is_array( $body_array ) ) {
		$body_array = [];
	}
	
	$user_email	  = isset( $body_array['email'] ) ? sanitize_email( $body_array['email'] ) : '';
	$company_name = isset( $body_array['company_name'] ) ? sanitize_text_field( $body_array['company_name'] ) : '';
	
	$timeout = intval( function_exists( 'get_option' ) ? get_option( 'rtbcb_responses_timeout', 120 ) : 120 );
	if ( $timeout <= 0 ) {
		$timeout = 120;
	}

		$response = rtbcb_wp_remote_post_with_retry(
				'https://api.openai.com/v1/responses',
				[
						'headers' => [
								'Content-Type'	=> 'application/json',
								'Authorization' => 'Bearer ' . $api_key,
						],
						'body'	  => $body,
						'timeout' => $timeout,
				]
		);

	if ( is_wp_error( $response ) ) {
				if ( class_exists( 'RTBCB_API_Log' ) ) {
                                        $model = $body_array['model'] ?? '';
                                        RTBCB_API_Log::save_log( $body_array, [ 'error' => $response->get_error_message() ], $user_id, $user_email, $company_name, 0, $model );
				}
		set_transient(
			'rtbcb_openai_job_' . $job_id,
			[
				'status'  => 'error',
				'message' => $response->get_error_message(),
			],
			HOUR_IN_SECONDS
		);
		return;
	}

	$code	   = wp_remote_retrieve_response_code( $response );
	$resp_body = wp_remote_retrieve_body( $response );
	$decoded   = rtbcb_extract_json_from_openai_response( $resp_body );
	if ( false === $decoded ) {
		$decoded = [];
	}

		if ( class_exists( 'RTBCB_API_Log' ) ) {
                                $model = $body_array['model'] ?? '';
                                RTBCB_API_Log::save_log( $body_array, $decoded, $user_id, $user_email, $company_name, 0, $model );
		}

	set_transient(
		'rtbcb_openai_job_' . $job_id,
		[
			'status'   => 'complete',
			'code'	   => $code,
			'response' => $decoded,
		],
		HOUR_IN_SECONDS
	);
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'rtbcb_run_openai_responses_job', 'rtbcb_handle_openai_responses_job', 10, 2 );
}

/**
	* Retrieve the status or result of an OpenAI response job.
	*
	* @return void
	*/
function rtbcb_get_openai_responses_status() {
	$job_id = isset( $_REQUEST['job_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['job_id'] ) ) : '';
	if ( '' === $job_id ) {
		wp_send_json_error( [ 'message' => __( 'Missing job ID.', 'rtbcb' ) ], 400 );
	}

	$result = get_transient( 'rtbcb_openai_job_' . $job_id );
	if ( false === $result ) {
		wp_send_json_error( [ 'message' => __( 'Job not found or expired.', 'rtbcb' ) ], 404 );
	}

	if ( isset( $result['status'] ) && 'pending' === $result['status'] ) {
		wp_send_json_success( $result );
	}

	delete_transient( 'rtbcb_openai_job_' . $job_id );
	wp_send_json_success( $result );
}

/**
	* Queue comprehensive analysis generation in the background.
	*
	* @param string $company_name Company name.
	* @return string|WP_Error Job identifier on success or WP_Error on failure.
	*/
function rtbcb_queue_comprehensive_analysis( $company_name ) {
	$company_name = sanitize_text_field( $company_name );
	if ( '' === $company_name ) {
		return new WP_Error( 'invalid_company', __( 'Company name is required.', 'rtbcb' ) );
	}

	$job_id = wp_generate_uuid4();
	wp_schedule_single_event(
		time(),
		'rtbcb_process_comprehensive_analysis',
		[ $company_name, $job_id ]
	);

	return $job_id;
}

/**
	* Background handler for comprehensive analysis jobs.
	*
	* @param string $company_name Company name.
	* @param string $job_id		  Job identifier.
	* @return void
	*/
function rtbcb_handle_comprehensive_analysis( $company_name, $job_id ) {
	$company_name = sanitize_text_field( $company_name );
	$job_id		  = sanitize_key( $job_id );

	$rag_context = [];
	$vendor_list = [];

	if ( function_exists( 'rtbcb_test_rag_market_analysis' ) && function_exists( 'rtbcb_get_current_company' ) ) {
		$company = rtbcb_get_current_company();
		$terms	 = [];

		if ( ! empty( $company['industry'] ) ) {
			$terms[] = sanitize_text_field( $company['industry'] );
		}

		if ( ! empty( $company['focus_areas'] ) && is_array( $company['focus_areas'] ) ) {
			$terms = array_merge( $terms, array_map( 'sanitize_text_field', $company['focus_areas'] ) );
		}

		if ( empty( $terms ) && ! empty( $company['summary'] ) ) {
			$terms[] = sanitize_text_field( wp_trim_words( $company['summary'], 5, '' ) );
		}

		if ( empty( $terms ) ) {
			$terms[] = $company_name;
		}

		$query		 = sanitize_text_field( implode( ' ', $terms ) );
		$vendor_list = rtbcb_test_rag_market_analysis( $query );

		if ( is_wp_error( $vendor_list ) ) {
			$vendor_list = [];
		}

		$rag_context = array_map( 'sanitize_text_field', $vendor_list );
	}

	$llm	  = new RTBCB_LLM_Optimized();
	$analysis = $llm->generate_comprehensive_business_case( [ 'company_name' => $company_name ], [], $rag_context, null );

	if ( is_wp_error( $analysis ) ) {
		update_option(
			'rtbcb_analysis_job_' . $job_id,
			[
				'success'	 => false,
				'message'	 => $analysis->get_error_message(),
				'error_code' => $analysis->get_error_code(),
			]
		);
		return;
	}

	$timestamp = current_time( 'mysql' );

	update_option( 'rtbcb_rag_market_analysis', $vendor_list );
	update_option( 'rtbcb_roadmap_plan', $analysis['implementation_roadmap'] );
	update_option( 'rtbcb_value_proposition', $analysis['executive_summary']['executive_recommendation'] ?? '' );
	update_option( 'rtbcb_estimated_benefits', $analysis['financial_analysis'] );
	update_option( 'rtbcb_executive_summary', $analysis['executive_summary'] );

$results = [
'market_analysis' => [
'summary'	=> $vendor_list,
'stored_in' => 'rtbcb_rag_market_analysis',
                          ],
'implementation_roadmap' => [
'summary'	=> $analysis['implementation_roadmap'],
'stored_in' => 'rtbcb_roadmap_plan',
                          ],
'value_proposition' => [
'summary'	=> $analysis['executive_summary'],
'stored_in' => 'rtbcb_value_proposition',
                          ],
'financial_analysis' => [
'summary'	=> $analysis['financial_analysis'],
'stored_in' => 'rtbcb_estimated_benefits',
                          ],
'executive_summary' => [
'summary'	=> $analysis['executive_summary'],
'stored_in' => 'rtbcb_executive_summary',
                          ],
	];

	$usage_map = [
		[ 'component' => __( 'Market Analysis & Vendors', 'rtbcb' ), 'used_in' => __( 'RAG Market Analysis Test', 'rtbcb' ), 'option' => 'rtbcb_rag_market_analysis' ],
		[ 'component' => __( 'Value Proposition Paragraph', 'rtbcb' ), 'used_in' => __( 'Value Proposition Test', 'rtbcb' ), 'option' => 'rtbcb_value_proposition' ],
		[ 'component' => __( 'Financial Benefits Breakdown', 'rtbcb' ), 'used_in' => __( 'Estimated Benefits Test', 'rtbcb' ), 'option' => 'rtbcb_estimated_benefits' ],
		[ 'component' => __( 'Executive Summary', 'rtbcb' ), 'used_in' => __( 'Report Assembly Test', 'rtbcb' ), 'option' => 'rtbcb_executive_summary' ],
		[ 'component' => __( 'Implementation Roadmap', 'rtbcb' ), 'used_in' => __( 'Roadmap Generator Test', 'rtbcb' ), 'option' => 'rtbcb_roadmap_plan' ],
	];

	update_option(
		'rtbcb_analysis_job_' . $job_id,
		[
			'success'			   => true,
			'timestamp'			   => $timestamp,
			'results'			   => $results,
			'usage_map'			   => $usage_map,
'components_generated' => 5,
		]
	);
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'rtbcb_process_comprehensive_analysis', 'rtbcb_handle_comprehensive_analysis', 10, 2 );
}

/**
	* Get the result of a queued analysis job.
	*
	* @param string $job_id Job identifier.
	* @return mixed|null Stored result array or null if pending.
	*/
function rtbcb_get_analysis_job_result( $job_id ) {
$job_id = sanitize_key( $job_id );
	return function_exists( 'get_option' ) ? get_option( 'rtbcb_analysis_job_' . $job_id, null ) : null;
}

/**
	* Build a cache key for LLM research results.
	*
	* @param string $company  Company name.
	* @param string $industry Industry name.
	* @param string $type	  Cache segment identifier.
	*
	* @return string Cache key.
	*/
function rtbcb_get_research_cache_key( $company, $industry, $type ) {
	$company  = sanitize_title( $company );
	$industry = sanitize_title( $industry );
	$type	  = sanitize_key( $type );

	return 'rtbcb_' . $type . '_' . md5( $company . '_' . $industry );
}

/**
	* Retrieve cached LLM research data.
	*
	* @param string $company  Company name.
	* @param string $industry Industry name.
	* @param string $type	  Cache segment identifier.
	*
	* @return mixed Cached data or false when not found.
	*/
function rtbcb_get_research_cache( $company, $industry, $type ) {
	$key = rtbcb_get_research_cache_key( $company, $industry, $type );
	return get_transient( $key );
}

/**
	* Store LLM research data in cache.
	*
	* @param string $company  Company name.
	* @param string $industry Industry name.
	* @param string $type	  Cache segment identifier.
	* @param mixed	$data	  Data to cache.
	* @param int	$ttl	  Optional TTL in seconds.
	*/
function rtbcb_set_research_cache( $company, $industry, $type, $data, $ttl = 0 ) {
	$key = rtbcb_get_research_cache_key( $company, $industry, $type );
	$ttl = (int) $ttl;
	if ( 0 === $ttl ) {
		$ttl = DAY_IN_SECONDS;
	}

	/**
	* Filter the research cache TTL.
	*
	* @param int $ttl Cache duration in seconds.
	* @param string $type Cache segment identifier.
	* @param string $company Sanitized company name.
	* @param string $industry Sanitized industry.
	*/
	$ttl = apply_filters( 'rtbcb_research_cache_ttl', $ttl, $type, $company, $industry );

	set_transient( $key, $data, $ttl );
}

/**
	* Delete cached LLM research data.
	*
	* @param string $company  Company name.
	* @param string $industry Industry name.
	* @param string $type	  Cache segment identifier.
	*/
function rtbcb_delete_research_cache( $company, $industry, $type ) {
$key = rtbcb_get_research_cache_key( $company, $industry, $type );
delete_transient( $key );
}

/**
	* Get allowed HTML tags and attributes for report templates.
	*
	* Adds canvas and button elements and permits data-* attributes.
	*
	* @return array Allowed HTML tags and attributes.
	*/
function rtbcb_get_report_allowed_html() {
$allowed = wp_kses_allowed_html( 'post' );

	$allowed['canvas'] = [
		'class'	 => true,
		'id'	 => true,
		'width'	 => true,
		'height' => true,
		'style'	 => true,
		'data-*' => true,
	];

	$allowed['button'] = [
		'class'	 => true,
		'id'	 => true,
		'type'	 => true,
		'name'	 => true,
		'value'	 => true,
		'style'	 => true,
		'data-*' => true,
	];
	$allowed['script'] = [
		'type'	 => true,
		'id'	 => true,
		'class'	 => true,
	];

foreach ( $allowed as $tag => $attrs ) {
$allowed[ $tag ]['data-*'] = true;
}

$allowed['script'] = [
'id'   => true,
'type' => [
'application/json'	 => true,
'application/ld+json' => true,
                          ],
];

return $allowed;
}

/**
 * Sanitize report HTML and remove disallowed script tags.
 *
 * Allows only non-executable script types like application/json and
 * application/ld+json. Any script tag missing these types is stripped.
 *
 * @param string $html HTML to sanitize.
 * @return string Sanitized HTML.
 */
function rtbcb_sanitize_report_html( $html ) {
$allowed = rtbcb_get_report_allowed_html();

$html = wp_kses( $html, $allowed );

return preg_replace_callback(
'#<script\\b([^>]*)>(.*?)</script>#is',
function ( $matches ) {
$attrs = $matches[1];
if ( preg_match( "#/\\btype=('|\")(.*?)\\1#i", $attrs, $type_match ) ) {
$type		   = strtolower( $type_match[2] );
$allowed_types = [ 'application/json', 'application/ld+json' ];
if ( in_array( $type, $allowed_types, true ) ) {
return $matches[0];
}
}
return '';
},
$html
);
}

/**
	* Increment the RAG search cache version to invalidate cached results.
	*
	* @return void
	*/
function rtbcb_invalidate_rag_cache() {
		if ( function_exists( 'get_option' ) && function_exists( 'update_option' ) ) {
				$version = (int) get_option( 'rtbcb_rag_cache_version', 1 );
				update_option( 'rtbcb_rag_cache_version', $version + 1 );
		}
}

/**
	* Clear cached report HTML entries.
	*
	* Retrieves the current report cache version for versioned cache keys.
	*
	* @return int
	*/
function rtbcb_get_report_cache_version() {
	$version = wp_cache_get( 'report_cache_version', 'rtbcb_reports' );

	if ( false === $version ) {
		$version = 1;
		wp_cache_set( 'report_cache_version', $version, 'rtbcb_reports' );
	}

	return (int) $version;
}

/**
	* Clear cached report HTML entries.
	*
	* Bumps the report cache version so previously cached templates are ignored.
	*
	* @return void
	*/
function rtbcb_clear_report_cache() {
	$version = rtbcb_get_report_cache_version();
	wp_cache_set( 'report_cache_version', $version + 1, 'rtbcb_reports' );
}


/**
	* Enable persistent database connections when supported.
	*
	* Reconnects using a host prefixed with `p:` if the current connection is
	* not already persistent and persistent connections are allowed. Behavior can
	* be filtered with `rtbcb_enable_persistent_connection`.
	*
	* @return void
	*/
function rtbcb_enable_persistent_connection() {
	global $wpdb;

	if ( strpos( DB_HOST, 'p:' ) === 0 ) {
		return;
	}

	if ( ! ini_get( 'mysqli.allow_persistent' ) ) {
		return;
	}

	$enable_persistent = apply_filters( 'rtbcb_enable_persistent_connection', true );
	if ( ! $enable_persistent ) {
		return;
	}

	$wpdb->dbhost = 'p:' . DB_HOST;
	if ( method_exists( $wpdb, 'close' ) ) {
		$wpdb->close();
	}
	$wpdb->db_connect();
}

if ( function_exists( 'add_action' ) ) {
		add_action( 'plugins_loaded', 'rtbcb_enable_persistent_connection', 1 );
}

function rtbcb_transform_data_for_template( $business_case_data ) {
	$defaults = [
			'company_name'           => '',
			'base_roi'               => 0,
			'roi_base'               => 0,
			'recommended_category'   => '',
			'category_info'          => [],
			'executive_summary'      => [],
			'narrative'              => '',
			'executive_recommendation' => '',
			'recommendation'         => '',
			'payback_months'         => 'N/A',
			'sensitivity_analysis'   => [],
			'company_analysis'       => '',
			'maturity_level'         => 'intermediate',
			'current_state_analysis' => '',
			'market_analysis'        => '',
			'tech_adoption_level'    => 'medium',
                       'operational_insights'   => [],
                       'industry_insights'      => [],
                       'action_plan'            => [],
                       'risk_analysis'          => [],
                       'risks'                  => [],
                       'company_intelligence'   => [],
                       'confidence'             => 0.85,
			'processing_time'        => 0,
	];
	$business_case_data = wp_parse_args( (array) $business_case_data, $defaults );

	// Normalize executive summary structure without overwriting existing fields.
	if ( is_array( $business_case_data['executive_summary'] ) ) {
		$exec_summary = wp_parse_args(
			$business_case_data['executive_summary'],
			[
				'strategic_positioning'	    => '',
				'executive_recommendation' => '',
				'key_value_drivers'	   => [],
			]
		);

		if ( empty( $business_case_data['executive_recommendation'] ) && ! empty( $exec_summary['executive_recommendation'] ) ) {
			$business_case_data['executive_recommendation'] = $exec_summary['executive_recommendation'];
		}

		if ( empty( $business_case_data['value_drivers'] ) && ! empty( $exec_summary['key_value_drivers'] ) ) {
			$business_case_data['value_drivers'] = $exec_summary['key_value_drivers'];
		}

		$business_case_data['executive_summary'] = $exec_summary;
	} else {
		$business_case_data['executive_summary'] = [
			'strategic_positioning'	    => (string) $business_case_data['executive_summary'],
			'executive_recommendation' => (string) ( $business_case_data['executive_recommendation'] ?? '' ),
			'key_value_drivers'	   => (array) ( $business_case_data['value_drivers'] ?? [] ),
		];
	}
	// Get current company data.
	$company      = rtbcb_get_current_company();
	$company_name = sanitize_text_field( $business_case_data['company_name'] ?: ( $company['name'] ?? __( 'Your Company', 'rtbcb' ) ) );
	$base_roi     = floatval( $business_case_data['base_roi'] ?: $business_case_data['roi_base'] );
	$business_case_data['roi_base'] = $base_roi;

	// Derive recommended category and details from recommendation if not provided.
        $recommended_category = sanitize_text_field( $business_case_data['recommended_category'] ?: ( $business_case_data['recommendation']['recommended'] ?? 'treasury_management_system' ) );
        $category_details     = $business_case_data['category_info'] ?: ( $business_case_data['recommendation']['category_info'] ?? [] );
        $category_details     = rtbcb_sanitize_recursive( $category_details );

	$roi_scenarios    = rtbcb_format_roi_scenarios( $business_case_data );
	$conservative_roi = floatval( $roi_scenarios['conservative']['total_annual_benefit'] ?? 0 );
	$base_roi         = floatval( $roi_scenarios['base']['total_annual_benefit'] ?? $base_roi );
	$optimistic_roi   = floatval( $roi_scenarios['optimistic']['total_annual_benefit'] ?? 0 );

	// Prepare operational insights.
       if ( ! empty( $business_case_data['operational_insights'] ) ) {
                       $operational_insights = [
                               'current_state_assessment' => array_map( 'sanitize_text_field', (array) ( $business_case_data['operational_insights']['current_state_assessment'] ?? [] ) ),
                               'process_improvements'     => rtbcb_sanitize_recursive( $business_case_data['operational_insights']['process_improvements'] ?? [] ),
                               'automation_opportunities' => rtbcb_sanitize_recursive( $business_case_data['operational_insights']['automation_opportunities'] ?? [] ),
                       ];
       } else {
                       $operational_insights = rtbcb_generate_operational_fallbacks( $business_case_data );
       }
	// Prepare industry insights.
	if ( ! empty( $business_case_data['industry_insights'] ) ) {
		$industry_insights = rtbcb_sanitize_recursive( $business_case_data['industry_insights'] );
		if ( empty( $industry_insights ) ) {
			$industry_insights = rtbcb_generate_industry_insights_fallbacks( $business_case_data );
		}
	} else {
		$industry_insights = rtbcb_generate_industry_insights_fallbacks( $business_case_data );
	}

        // Prepare action plan.
	if ( ! empty( $business_case_data['action_plan'] ) ) {
			$action_plan = rtbcb_sanitize_recursive( $business_case_data['action_plan'] );
	} else {
			$action_plan = rtbcb_generate_action_plan_fallbacks( $business_case_data );
        }

        // Prepare additional contextual sections.
        $financial_benchmarks = ! empty( $business_case_data['financial_benchmarks'] ) ? rtbcb_sanitize_recursive( $business_case_data['financial_benchmarks'] ) : [];
        $rag_context          = ! empty( $business_case_data['rag_context'] ) ? rtbcb_sanitize_recursive( $business_case_data['rag_context'] ) : [];

	// Prepare company intelligence.
	if ( ! empty( $business_case_data['company_intelligence'] ) ) {
			$company_intelligence = rtbcb_sanitize_recursive( $business_case_data['company_intelligence'] );
	} else {
			$company_intelligence = [
				    'enriched_profile' => [
				            'enhanced_description' => wp_kses_post( $business_case_data['company_analysis'] ),
				            'maturity_level'       => sanitize_text_field( $business_case_data['maturity_level'] ),
				            'treasury_maturity'    => [
				                    'current_state' => wp_kses_post( $business_case_data['current_state_analysis'] ),
				            ],
				    ],
				    'industry_context' => [
				            'sector_analysis' => [
				                    'market_dynamics' => wp_kses_post( $business_case_data['market_analysis'] ),
				            ],
				            'benchmarking'   => [
				                    'technology_penetration' => sanitize_text_field( $business_case_data['tech_adoption_level'] ),
				            ],
				    ],
			];
	}

// Prepare risk analysis.
$risk_matrix = [];
if ( ! empty( $business_case_data['risk_analysis']['risk_matrix'] ) ) {
$risk_matrix = array_map(
function ( $risk ) {
return [
'risk'       => sanitize_text_field( $risk['risk'] ?? '' ),
'likelihood' => sanitize_text_field( $risk['likelihood'] ?? '' ),
'impact'     => sanitize_text_field( $risk['impact'] ?? '' ),
];
},
(array) $business_case_data['risk_analysis']['risk_matrix']
);
}

if ( ! empty( $business_case_data['risk_analysis']['implementation_risks'] ) ) {
$implementation_risks = array_map( 'sanitize_text_field', (array) $business_case_data['risk_analysis']['implementation_risks'] );
} elseif ( ! empty( $business_case_data['risks'] ) ) {
$implementation_risks = array_map( 'sanitize_text_field', (array) $business_case_data['risks'] );
} else {
$implementation_risks = [
__( 'Integration complexity with existing systems', 'rtbcb' ),
__( 'Change management and user adoption challenges', 'rtbcb' ),
];
}

$mitigation_strategies = [];
if ( ! empty( $business_case_data['risk_analysis']['mitigation_strategies'] ) ) {
$mitigation_strategies = array_map( 'sanitize_text_field', (array) $business_case_data['risk_analysis']['mitigation_strategies'] );
}

$success_factors = [];
if ( ! empty( $business_case_data['risk_analysis']['success_factors'] ) ) {
$success_factors = array_map( 'sanitize_text_field', (array) $business_case_data['risk_analysis']['success_factors'] );
}

	// Create structured data format expected by template.
	$report_data = [
			'metadata'           => [
				    'company_name'     => $company_name,
				    'analysis_date'    => current_time( 'Y-m-d' ),
				    'analysis_type'    => rtbcb_get_analysis_type(),
				    'confidence_level' => floatval( $business_case_data['confidence'] ),
				    'processing_time'  => intval( $business_case_data['processing_time'] ),
			],
			'executive_summary'  => [
			'strategic_positioning'    => wp_kses_post( $business_case_data['executive_summary']['strategic_positioning'] ?? $business_case_data['narrative'] ),
				    'key_value_drivers'       => rtbcb_extract_value_drivers( $business_case_data ),
			'executive_recommendation' => wp_kses_post( $business_case_data['executive_summary']['executive_recommendation'] ?? $business_case_data['executive_recommendation'] ?? $business_case_data['recommendation'] ),
				    'business_case_strength'  => rtbcb_determine_business_case_strength( $business_case_data ),
			],
				    'financial_analysis' => [
				    'roi_scenarios'      => $roi_scenarios,
				    'payback_analysis'   => [
				            'payback_months' => sanitize_text_field( $business_case_data['payback_months'] ),
				    ],
				    'sensitivity_analysis' => $business_case_data['sensitivity_analysis'],
				    'chart_data'          => [
				            'labels'   => [
				                    __( 'Conservative', 'rtbcb' ),
				                    __( 'Base', 'rtbcb' ),
				                    __( 'Optimistic', 'rtbcb' ),
				            ],
				            'datasets' => [
				                    [
				                            'data'            => [ $conservative_roi, $base_roi, $optimistic_roi ],
				                            'backgroundColor' => [ '#ff6384', '#36a2eb', '#4bc0c0' ],
				                    ],
				            ],
				    ],
			],
			'company_intelligence' => $company_intelligence,
			'industry_insights'    => $industry_insights,
                        'technology_strategy' => [
                                'recommended_category'  => $recommended_category,
                                'category_details'      => $category_details,
                                'implementation_roadmap' => rtbcb_sanitize_recursive( $business_case_data['technology_strategy']['implementation_roadmap'] ?? $business_case_data['implementation_roadmap'] ?? [] ),
                                'vendor_considerations' => rtbcb_sanitize_recursive( $business_case_data['technology_strategy']['vendor_considerations'] ?? $business_case_data['vendor_considerations'] ?? [] ),
                        ],
                        'operational_insights' => $operational_insights,
                          'risk_analysis'        => [
                                'risk_matrix'          => $risk_matrix,
                                'implementation_risks' => $implementation_risks,
                                'mitigation_strategies' => $mitigation_strategies,
                                'success_factors'      => $success_factors,
                          ],
                        'action_plan'          => $action_plan,
                        'financial_benchmarks' => $financial_benchmarks,
                        'rag_context'          => $rag_context,
        ];

	return $report_data;
	}

	/**
	* Extract value drivers from business case data.
	*
	* @param array $data Business case data.
	*
	* @return array
	*/
	function rtbcb_extract_value_drivers( $data ) {
	$drivers = [];

	// Extract from various possible sources.
	if ( ! empty( $data['executive_summary']['key_value_drivers'] ) ) {
		$drivers = (array) $data['executive_summary']['key_value_drivers'];
	} elseif ( ! empty( $data['value_drivers'] ) ) {
		$drivers = (array) $data['value_drivers'];
	} elseif ( ! empty( $data['key_benefits'] ) ) {
		$drivers = (array) $data['key_benefits'];
	} else {
		// Default value drivers.
		$drivers = [
			__( 'Automated cash management processes', 'rtbcb' ),
			__( 'Enhanced financial visibility and reporting', 'rtbcb' ),
			__( 'Reduced operational risk and errors', 'rtbcb' ),
			__( 'Improved regulatory compliance', 'rtbcb' ),
		];
	}
	return array_slice( $drivers, 0, 4 );
	}

	/**
	* Format ROI scenarios for template.
	*
	* @param array $data Business case data.
	*
	* @return array
	*/
function rtbcb_format_roi_scenarios( $data ) {
	   $allowed = array( 'conservative', 'base', 'optimistic' );

	   // Try to get ROI data from various possible locations.
	   if ( ! empty( $data['scenarios'] ) && is_array( $data['scenarios'] ) ) {
		   return array_intersect_key( $data['scenarios'], array_flip( $allowed ) );
	   }

	   if ( ! empty( $data['roi_scenarios'] ) && is_array( $data['roi_scenarios'] ) ) {
		   return array_intersect_key( $data['roi_scenarios'], array_flip( $allowed ) );
	   }

	   // Fallback to default structure.
	   $scenarios = [
		   'conservative' => [
				   'total_annual_benefit' => $data['roi_low'] ?? 0,
				   'labor_savings'        => ( $data['roi_low'] ?? 0 ) * 0.6,
				   'fee_savings'          => ( $data['roi_low'] ?? 0 ) * 0.3,
				   'error_reduction'      => ( $data['roi_low'] ?? 0 ) * 0.1,
		   ],
		   'base' => [
				   'total_annual_benefit' => $data['roi_base'] ?? 0,
				   'labor_savings'        => ( $data['roi_base'] ?? 0 ) * 0.6,
				   'fee_savings'          => ( $data['roi_base'] ?? 0 ) * 0.3,
				   'error_reduction'      => ( $data['roi_base'] ?? 0 ) * 0.1,
		   ],
		   'optimistic' => [
				   'total_annual_benefit' => $data['roi_high'] ?? 0,
				   'labor_savings'        => ( $data['roi_high'] ?? 0 ) * 0.6,
				   'fee_savings'          => ( $data['roi_high'] ?? 0 ) * 0.3,
				   'error_reduction'      => ( $data['roi_high'] ?? 0 ) * 0.1,
		   ],
	   ];

	   return $scenarios;
	   }

	   /**
	   * Determine business case strength based on ROI.
	   *
	   * @param array $data Business case data.
	   *
	   * @return string
	   */
function rtbcb_determine_business_case_strength( $data ) {
		   $base_roi = $data['roi_base'] ?? $data['scenarios']['base']['total_annual_benefit'] ?? 0;

		   if ( $base_roi > 500000 ) {
				   return 'Compelling';
		   } elseif ( $base_roi > 200000 ) {
				   return 'Strong';
		   } elseif ( $base_roi > 50000 ) {
				   return 'Moderate';
		   } else {
				   return 'Developing';
		   }
	   }

	   /**
	   * Recursively sanitize array data using sanitize_text_field.
	   *
	   * @param mixed $data Data to sanitize.
	   *
	   * @return mixed
	   */
function rtbcb_sanitize_recursive( $data ) {
		   if ( is_array( $data ) ) {
				   foreach ( $data as $key => $value ) {
				           $data[ $key ] = rtbcb_sanitize_recursive( $value );
				   }
				   return $data;
		   }

		   return is_scalar( $data ) ? sanitize_text_field( $data ) : $data;
	   }

	   /**
	   * Generate fallback operational insights when LLM data is missing.
	   *
	   * @param array $data Business case data.
	   *
	   * @return array
	   */
function rtbcb_generate_operational_fallbacks( $data ) {
		   return [
				   'current_state_assessment' => [
				           __( 'Manual processes dominate cash and liquidity management', 'rtbcb' ),
				           __( 'Limited integration between treasury and ERP systems', 'rtbcb' ),
				   ],
				   'process_improvements'     => [
				           __( 'Centralize cash visibility across subsidiaries', 'rtbcb' ),
				           __( 'Standardize payment workflows to reduce errors', 'rtbcb' ),
				   ],
				   'automation_opportunities' => [
				           __( 'Automate bank reconciliation tasks', 'rtbcb' ),
				           __( 'Implement API connections for real-time balances', 'rtbcb' ),
				   ],
		   ];
	   }

	   /**
	   * Generate fallback action plan when LLM data is missing.
	   *
	   * @param array $data Business case data.
	   *
	   * @return array
	   */
function rtbcb_generate_action_plan_fallbacks( $data ) {
		   return [
				   'immediate_steps'       => rtbcb_extract_immediate_steps( $data ),
				   'short_term_milestones' => rtbcb_extract_short_term_steps( $data ),
				   'long_term_objectives'  => rtbcb_extract_long_term_steps( $data ),
		   ];
	   }

	   /**
	   * Generate fallback industry insights when LLM data is missing.
	   *
	   * @param array $data Business case data.
	   *
	   * @return array
	   */
function rtbcb_generate_industry_insights_fallbacks( $data ) {
		   return [
				   'sector_trends'           => [
				           __( 'Increasing focus on real-time liquidity management', 'rtbcb' ),
				           __( 'Growing adoption of API-based bank connectivity', 'rtbcb' ),
				   ],
				   'competitive_benchmarks'  => [
				           __( 'Leading firms leverage AI for cash forecasting', 'rtbcb' ),
				   ],
				   'regulatory_considerations' => [
				           __( 'Heightened emphasis on KYC and AML compliance', 'rtbcb' ),
				   ],
		   ];
	   }

	   /**
	   * Extract action steps from business case data.
	   *
	   * @param array $data Business case data.
	   *
	   * @return array
	   */
function rtbcb_extract_immediate_steps( $data ) {
		   if ( ! empty( $data['next_actions'] ) ) {
				   $all_actions = (array) $data['next_actions'];
				   return array_slice( $all_actions, 0, 3 );
		   }

		   return [
				   __( 'Secure executive sponsorship and budget approval', 'rtbcb' ),
				   __( 'Form project steering committee', 'rtbcb' ),
				   __( 'Conduct detailed requirements gathering', 'rtbcb' ),
		   ];
	   }

	   /**
	   * Extract short term action steps.
	   *
	   * @param array $data Business case data.
	   *
	   * @return array
	   */
function rtbcb_extract_short_term_steps( $data ) {
		   if ( ! empty( $data['implementation_steps'] ) ) {
				   $steps = (array) $data['implementation_steps'];
				   return array_slice( $steps, 0, 4 );
		   }

		   return [
				   __( 'Issue RFP to qualified vendors', 'rtbcb' ),
				   __( 'Conduct vendor demonstrations and evaluations', 'rtbcb' ),
				   __( 'Negotiate contracts and terms', 'rtbcb' ),
				   __( 'Begin system implementation planning', 'rtbcb' ),
		   ];
	   }

	   /**
	   * Extract long term action steps.
	   *
	   * @param array $data Business case data.
	   *
	   * @return array
	   */
function rtbcb_extract_long_term_steps( $data ) {
return [
__( 'Complete system implementation and testing', 'rtbcb' ),
__( 'Conduct user training and change management', 'rtbcb' ),
__( 'Measure and optimize system performance', 'rtbcb' ),
__( 'Expand functionality and integration capabilities', 'rtbcb' ),
];
}

/**
 * Parse a raw API response containing nested and escaped JSON.
 *
 * The remote service returns a JSON object where the `raw_body` field holds
 * another JSON string. Inside that string the `output` array contains a
 * `text` field with an escaped JSON payload. This helper decodes each layer
 * and returns the final associative array.
 *
 * @param string $raw_response Raw response string from the API.
 * @return array|WP_Error      Parsed data array on success or WP_Error on failure.
 */
function rtbcb_parse_api_response( $raw_response ) {
	if ( '' === $raw_response || null === $raw_response ) {
		return new WP_Error(
			'api_empty_response',
			__( 'Empty API response.', 'rtbcb' )
		);
	}
	
	$outer = json_decode( $raw_response, true );
	if ( null === $outer && JSON_ERROR_NONE !== json_last_error() ) {
		return new WP_Error(
			'api_invalid_json',
			sprintf( __( 'Unable to decode API response: %s', 'rtbcb' ), json_last_error_msg() )
		);
	}
	
	if ( empty( $outer['raw_body'] ) ) {
		return new WP_Error(
			'api_missing_body',
			__( 'API response missing raw_body field.', 'rtbcb' )
		);
	}
	
	$inner = json_decode( $outer['raw_body'], true );
	if ( null === $inner && JSON_ERROR_NONE !== json_last_error() ) {
		return new WP_Error(
			'api_invalid_inner_json',
			sprintf( __( 'Unable to decode raw_body JSON: %s', 'rtbcb' ), json_last_error_msg() )
		);
	}
	
	if ( empty( $inner['output'] ) || ! is_array( $inner['output'] ) ) {
		return new WP_Error(
			'api_missing_output',
			__( 'raw_body does not contain an output array.', 'rtbcb' )
		);
	}
	
	$escaped_json = null;
	foreach ( $inner['output'] as $item ) {
		if ( isset( $item['type'] ) && 'message' === $item['type'] && ! empty( $item['content'] ) ) {
			foreach ( $item['content'] as $content ) {
				if ( isset( $content['text'] ) ) {
					$escaped_json = $content['text'];
					break 2;
			}
			}
}
}

	if ( null === $escaped_json ) {
		return new WP_Error(
			'api_missing_text',
			__( 'Unable to locate text field in API response.', 'rtbcb' )
		);
	}
	
	$data = json_decode( $escaped_json, true );
	if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
		return new WP_Error(
				'api_invalid_text_json',
				sprintf( __( 'Unable to decode text JSON: %s', 'rtbcb' ), json_last_error_msg() )
		);
	}

	return $data;
}


