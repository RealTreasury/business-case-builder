<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

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


if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $text ) {
		$text = is_scalar( $text ) ? (string) $text : '';
		$text = preg_replace( '/[\r\n\t\0\x0B]/', '', $text );
		return trim( $text );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ) {
		$title = strtolower( $title );
		$title = preg_replace( '/[^a-z0-9]+/', '-', $title );
		return trim( $title, '-' );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$key = strtolower( $key );
		return preg_replace( '/[^a-z0-9_\-]/', '', $key );
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $name, $value, $expiration ) {
		global $transients;
		$transients[ $name ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $name ) {
		global $transients;
		return $transients[ $name ] ?? false;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = '' ) {
		if ( 'rtbcb_openai_api_key' === $name ) {
			return 'test-key';
		}
		return $default;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

$llm = new class extends RTBCB_LLM {
        public $calls = 0;


        protected function conduct_company_research( $user_inputs ) {
                $this->calls++;
                return [ 'company_profile' => [ 'size' => $user_inputs['company_size'] ] ];
        }

        protected function analyze_industry_context( $user_inputs ) {
                return [];
        }

        protected function research_treasury_solutions( $user_inputs, $context_chunks ) {
                return [];
        }

        protected function select_optimal_model( $user_inputs, $context_chunks ) {
                return '';
        }

        protected function build_comprehensive_prompt( $user_inputs, $roi_data, $company_research, $industry_analysis, $tech_landscape ) {
                return '';
        }

        protected function build_context_for_responses( $history ) {
                return [];
        }

        protected function tokens_for_report( $report ) {
                return 0;
        }

        protected function call_openai_with_retry( $model, $context, $tokens ) {
                return [];
        }

        protected function parse_comprehensive_response( $response ) {
                return [
                        'executive_summary'         => [],
                        'operational_analysis'      => [],
                        'industry_insights'         => [],
                        'technology_recommendations'=> [],
                        'financial_analysis'        => [],
                        'risk_mitigation'           => [],
                        'next_steps'               => [],
                ];
        }

        protected function enhance_with_research( $parsed, $company_research, $industry_analysis, $tech_landscape ) {
                return [
                        'executive_summary' => [],
                        'research'          => [ 'company' => $company_research, 'technology' => $tech_landscape ],
                        'industry_insights' => [],
                        'technology_recommendations' => [],
                        'financial_analysis' => [],
                        'risk_mitigation' => [],
                        'next_steps' => [],
                ];
        }
};

$inputs_small = [
        'company_name' => 'Cache Co',
        'industry'     => 'finance',
        'company_size' => '$50M-$500M',
];
$result_small = $llm->generate_comprehensive_business_case( $inputs_small, [], [] );
if ( '$50M-$500M' !== ( $result_small['company_profile']['size'] ?? '' ) ) {
        echo "Small research not generated\n";
        exit( 1 );
}
if ( 1 !== $llm->calls ) {
        echo "Research method not called for small size\n";
        exit( 1 );
}
$cached_small = rtbcb_get_research_cache( 'Cache Co', 'finance', 'company', '$50M-$500M' );
if ( false === $cached_small ) {
        echo "Small company research not cached\n";
        exit( 1 );
}

$inputs_large = [
        'company_name' => 'Cache Co',
        'industry'     => 'finance',
        'company_size' => '>$2B',
];
$result_large = $llm->generate_comprehensive_business_case( $inputs_large, [], [] );
if ( '>$2B' !== ( $result_large['company_profile']['size'] ?? '' ) ) {
        echo "Cache reused for different size\n";
        exit( 1 );
}
if ( 2 !== $llm->calls ) {
        echo "Research method not called for large size\n";
        exit( 1 );
}
$cached_large = rtbcb_get_research_cache( 'Cache Co', 'finance', 'company', '>$2B' );
if ( false === $cached_large ) {
        echo "Large company research not cached\n";
        exit( 1 );
}

echo "company-research-cache.test.php passed\n";
