<?php
/**
 * ROI calculation utilities.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_Calculator.
 */
class RTBCB_Calculator {
    /**
     * Calculate ROI scenarios for given inputs.
     *
     * @param array $user_inputs User provided inputs.
     * @return array
     */
    public static function calculate_roi( $user_inputs ) {
        $settings = RTBCB_Settings::get_all();

        // Calculate scenarios.
        $scenarios = [];
        foreach ( [ 'low', 'base', 'high' ] as $scenario ) {
            $scenarios[ $scenario ] = self::calculate_scenario( $user_inputs, $settings, $scenario );
        }

        return $scenarios;
    }

    /**
     * Calculate ROI for a scenario.
     *
     * @param array  $inputs        User inputs.
     * @param array  $settings      Plugin settings.
     * @param string $scenario_type Scenario type.
     * @return array
     */
    private static function calculate_scenario( $inputs, $settings, $scenario_type ) {
        $multipliers = [
            'low'  => 0.8,
            'base' => 1.0,
            'high' => 1.2,
        ];
        $multiplier = $multipliers[ $scenario_type ];

        // Labor cost savings.
        $labor_savings = self::calculate_labor_savings( $inputs, $settings, $multiplier );

        // Bank fee reduction.
        $fee_savings = self::calculate_fee_savings( $inputs, $settings, $multiplier );

        // Error reduction benefits.
        $error_reduction = self::calculate_error_reduction( $inputs, $settings, $multiplier );

        $total_annual_benefit = $labor_savings + $fee_savings + $error_reduction;

        return [
            'labor_savings'        => $labor_savings,
            'fee_savings'          => $fee_savings,
            'error_reduction'      => $error_reduction,
            'total_annual_benefit' => $total_annual_benefit,
            'assumptions'          => self::get_scenario_assumptions( $scenario_type, $multiplier ),
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
        $cost_map = [
            '<$50M'       => 25000,
            '$50M-$500M'  => 75000,
            '$500M-$2B'   => 200000,
            '>$2B'        => 500000,
        ];
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
     *
     * @return array Assumptions for the scenario.
     */
    private static function get_scenario_assumptions( $scenario_type, $multiplier ) {
        return [
            'name'                  => ucfirst( $scenario_type ),
            'efficiency_improvement'=> 0.30 * $multiplier,
            'error_reduction'       => 0.25 * $multiplier,
            'fee_reduction'         => 0.08 * $multiplier,
        ];
    }
}
