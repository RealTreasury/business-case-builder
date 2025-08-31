<?php
defined( 'ABSPATH' ) || exit;

/**
	* Settings management for default ROI assumptions.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

/**
	* Class RTBCB_Settings.
	*/
class RTBCB_Settings {
	/**
	 * Default settings.
	 *
	 * @var array
	 */
	const DEFAULTS = [
		'labor_cost_per_hour'       => 100,
		'efficiency_ranges'         => [
			'cash_positioning'       => [ 'min' => 70, 'max' => 80 ],
			'reconciliation'         => [ 'min' => 60, 'max' => 75 ],
			'payments'               => [ 'min' => 40, 'max' => 60 ],
			'forecast_error_reduction' => [ 'min' => 20, 'max' => 30 ],
		],
		'bank_fee_reduction'        => [ 'min' => 5, 'max' => 10 ],
		'baseline_bank_fee_multiplier' => 15000,
		'enable_ai_analysis'        => true,
		'enable_charts'            => true,
	];

	/**
	 * Retrieve a setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get_setting( $key, $default = null ) {
		$settings = get_option( 'rtbcb_settings', self::DEFAULTS );
		return $settings[ $key ] ?? $default;
	}

	/**
	 * Retrieve all settings.
	 *
	 * @return array
	 */
	public static function get_all() {
		return get_option( 'rtbcb_settings', self::DEFAULTS );
	}
}
