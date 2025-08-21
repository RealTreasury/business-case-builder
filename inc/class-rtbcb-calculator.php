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

    // TODO: Implement the private calculation methods:
    // - calculate_labor_savings( $inputs, $settings, $multiplier )
    // - calculate_fee_savings( $inputs, $settings, $multiplier )
    // - calculate_error_reduction( $inputs, $settings, $multiplier )
    // - get_scenario_assumptions( $scenario_type, $multiplier )
}
