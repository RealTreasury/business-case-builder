<?php
defined( 'ABSPATH' ) || exit;

/**
	* ROI calculation utilities.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

/**
	* Class RTBCB_Calculator.
	*
	* Provides static ROI calculation helpers.
	*/
class RTBCB_Calculator {
	/**
	* Calculate ROI scenarios for given inputs.
	*
	* @param array $user_inputs User provided inputs.
	* @param array $category    Optional category info.
	* @return array
	*/
	public static function calculate_roi( $user_inputs, $category = [] ) {
               $settings      = class_exists( 'RTBCB_Settings' ) ? RTBCB_Settings::get_all() : [];
               $company       = function_exists( 'rtbcb_get_current_company' ) ? rtbcb_get_current_company() : [];
               $industry_mult = self::get_industry_benchmark( $company['industry'] ?? '' );

		$scenarios = [];
		foreach ( [ 'conservative', 'base', 'optimistic' ] as $scenario ) {
			$scenarios[ $scenario ] = self::calculate_scenario( $user_inputs, $settings, $category, $scenario, $industry_mult );
		}

		return $scenarios;
	}

	/**
	* Recalculate ROI scenarios using recommended category info.
	*
	* @param array $user_inputs User provided inputs.
	* @param array $category    Category info from recommender.
	* @return array
	*/
	public static function calculate_category_refined_roi( $user_inputs, $category ) {
		return self::calculate_roi( $user_inputs, $category );
	}

	/**
	* Calculate ROI for a scenario.
	*
	* @param array  $inputs        User inputs.
	* @param array  $settings      Plugin settings.
	* @param array  $category      Recommended category info.
	* @param string $scenario_type Scenario type.
	* @param float  $industry_mult Industry benchmark multiplier.
	* @return array
	*/
	private static function calculate_scenario( $inputs, $settings, $category, $scenario_type, $industry_mult ) {
		/**
		* Filters the scenario multipliers used to adjust ROI calculations.
		*
		* @param array  $multipliers   Associative array of scenario multipliers.
		* @param array  $inputs        User provided inputs.
		* @param array  $settings      Plugin settings.
		* @param array  $category      Recommended category info.
		* @param string $scenario_type Scenario type.
		* @param float  $industry_mult Industry benchmark multiplier.
		*/
		$multipliers = apply_filters(
			'rtbcb_roi_multipliers',
			[
				'conservative' => 0.8,
				'base'         => 1.0,
				'optimistic'   => 1.2,
			],
			$inputs,
			$settings,
			$category,
			$scenario_type,
			$industry_mult
		);
		$multiplier  = $multipliers[ $scenario_type ];

		$labor_savings   = self::calculate_labor_savings( $inputs, $settings, $multiplier );
		$fee_savings     = self::calculate_fee_savings( $inputs, $settings, $multiplier );
		$error_reduction = self::calculate_error_reduction( $inputs, $settings, $multiplier );

		$total_annual_benefit = ( $labor_savings + $fee_savings + $error_reduction ) * $industry_mult;

		$min_roi = $category['roi_range'][0] ?? 0;
		$max_roi = $category['roi_range'][1] ?? $total_annual_benefit;
		$total_annual_benefit = max( $min_roi, min( $total_annual_benefit, $max_roi ) );

		$avg_cost      = ( $min_roi + $max_roi ) / 2;
		$roi_percentage = $avg_cost > 0 ? ( $total_annual_benefit / $avg_cost ) * 100 : 0;

		return [
			'labor_savings'        => $labor_savings,
			'fee_savings'          => $fee_savings,
			'error_reduction'      => $error_reduction,
			'total_annual_benefit' => $total_annual_benefit,
			'roi_percentage'       => $roi_percentage,
			'assumptions'          => self::get_scenario_assumptions( $scenario_type, $multiplier, $industry_mult ),
		];
	}

	/**
	* Calculate labor cost savings.
	*
	* @param array $inputs     User inputs.
	* @param array $settings   Plugin settings.
	* @param float $multiplier Scenario multiplier.
	*
	* @return float Annual labor savings.
	*/
	private static function calculate_labor_savings( $inputs, $settings, $multiplier ) {
		$hourly_cost = isset( $settings['labor_cost_per_hour'] ) ? floatval( $settings['labor_cost_per_hour'] ) : 100;
		$weekly_hours = ( isset( $inputs['hours_reconciliation'] ) ? floatval( $inputs['hours_reconciliation'] ) : 0 )
			+ ( isset( $inputs['hours_cash_positioning'] ) ? floatval( $inputs['hours_cash_positioning'] ) : 0 );
		$efficiency   = 0.30 * $multiplier;
		$hours_saved  = $weekly_hours * $efficiency;

		return $hours_saved * 52 * $hourly_cost;
	}

	/**
	* Calculate bank fee savings.
	*
	* @param array $inputs     User inputs.
	* @param array $settings   Plugin settings.
	* @param float $multiplier Scenario multiplier.
	*
	* @return float Annual fee savings.
	*/
	private static function calculate_fee_savings( $inputs, $settings, $multiplier ) {
		$num_banks = isset( $inputs['num_banks'] ) ? intval( $inputs['num_banks'] ) : 0;
		$baseline  = isset( $settings['bank_fee_baseline'] ) ? floatval( $settings['bank_fee_baseline'] ) : 15000;
		$rate      = 0.08 * $multiplier;

		return $num_banks * $baseline * $rate;
	}

	/**
	* Calculate savings from error reduction.
	*
	* @param array $inputs     User inputs.
	* @param array $settings   Plugin settings.
	* @param float $multiplier Scenario multiplier.
	*
	* @return float Annual error reduction benefit.
	*/
	private static function calculate_error_reduction( $inputs, $settings, $multiplier ) {
		/**
		* Filters the base error cost map used for calculating error reduction savings.
		*
		* @param array $cost_map   Map of company sizes to base error costs.
		* @param array $inputs     User provided inputs.
		* @param array $settings   Plugin settings.
		* @param float $multiplier Scenario multiplier.
		*/
		$cost_map = apply_filters(
			'rtbcb_error_cost_map',
			[
				'<$50M'       => 25000,
				'$50M-$500M'  => 75000,
				'$500M-$2B'   => 200000,
				'>$2B'        => 500000,
			],
			$inputs,
			$settings,
			$multiplier
		);
		$company_size = isset( $inputs['company_size'] ) ? $inputs['company_size'] : '';
		$base_cost    = isset( $cost_map[ $company_size ] ) ? $cost_map[ $company_size ] : 50000;
		$reduction    = 0.25 * $multiplier;

		return $base_cost * $reduction;
	}

	/**
	* Get scenario assumptions.
	*
	* @param string $scenario_type Scenario key.
	* @param float  $multiplier    Scenario multiplier.
	* @param float  $industry_mult Industry multiplier.
	*
	* @return array Assumptions for the scenario.
	*/
	private static function get_scenario_assumptions( $scenario_type, $multiplier, $industry_mult ) {
		return [
			'name'                  => ucfirst( $scenario_type ),
			'efficiency_improvement'=> 0.30 * $multiplier,
			'error_reduction'       => 0.25 * $multiplier,
			'fee_reduction'         => 0.08 * $multiplier,
			'industry_benchmark'    => $industry_mult,
		];
	}

	/**
	* Retrieve industry benchmark multiplier.
	*
	* @param string $industry Industry identifier.
	* @return float Multiplier.
	*/
	private static function get_industry_benchmark( $industry ) {
		$benchmarks = [
			'manufacturing' => 0.9,
			'technology'    => 1.1,
			'finance'       => 1.05,
			'retail'        => 0.95,
		];

		$industry = strtolower( $industry );

		return $benchmarks[ $industry ] ?? 1.0;
	}
}
