<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}
defined( 'ABSPATH' ) || exit;
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}
if (!function_exists('add_filter')) {
	function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {}
}
if (!function_exists('apply_filters')) {
	function apply_filters($tag, $value) {
		return $value;
	}
}
require_once __DIR__ . '/../inc/helpers.php';
if (!function_exists('esc_html__')) {
	function esc_html__($text, $domain = null) {
		return $text;
	}
}
if (!function_exists('esc_html')) {
	function esc_html($text) {
		return $text;
	}
}
if (!function_exists('esc_attr')) {
function esc_attr($text) {
		return $text;
}
}
if (!function_exists('wp_convert_hr_to_bytes')) {
	function wp_convert_hr_to_bytes($size) {
		$size = trim($size);
		$unit = strtolower(substr($size, -1));
		$bytes = (int) $size;
		switch ($unit) {
			case 'g':
				$bytes *= 1024 * 1024 * 1024;
				break;
			case 'm':
				$bytes *= 1024 * 1024;
				break;
			case 'k':
				$bytes *= 1024;
				break;
		}
		return $bytes;
	}
}
$business_case_data = [
       'executive_summary'    => [
               'strategic_positioning' => 'Sample strategic positioning.',
       ],
       'business_case_strength'    => 'Sample case strength.',
       'key_value_drivers'         => [ 'Driver1' ],
       'executive_recommendation'  => 'Sample recommendation.',
       'operational_analysis'      => [
               'current_state_assessment' => 'Sample current state assessment.',
       ],
       'industry_insights'         => [
               'sector_trends'           => [ 'Trend1' ],
               'competitive_benchmarks'  => [ 'Benchmark1' ],
               'regulatory_considerations' => [ 'Reg1' ],
       ],
];
ob_start();
include __DIR__ . '/../templates/report-template.php';
ob_end_clean();
$status = rtbcb_get_memory_status();
$threshold = getenv('RTBCB_MEMORY_LIMIT');
$threshold = $threshold ? wp_convert_hr_to_bytes($threshold) : 50 * 1024 * 1024;
if ($status['current'] > $threshold) {
	echo 'Memory usage exceeded threshold: ' . $status['current'] . ' > ' . $threshold . "\n";
	exit(1);
}
echo 'Report generation memory usage within threshold: ' . $status['current'] . " bytes\n";

