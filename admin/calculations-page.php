<?php
/**
 * Calculation info admin page for Real Treasury Business Case Builder plugin.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$labor_cost = get_option( 'rtbcb_labor_cost_per_hour', 0 );
$bank_fee   = get_option( 'rtbcb_bank_fee_baseline', 0 );
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Calculation Info', 'rtbcb' ); ?></h1>

    <h2><?php echo esc_html__( 'Current Settings', 'rtbcb' ); ?></h2>
    <ul>
        <li><?php printf( esc_html__( 'Labor Cost Per Hour: %s', 'rtbcb' ), esc_html( number_format_i18n( $labor_cost, 2 ) ) ); ?></li>
        <li><?php printf( esc_html__( 'Bank Fee Baseline: %s', 'rtbcb' ), esc_html( number_format_i18n( $bank_fee, 2 ) ) ); ?></li>
    </ul>

    <h2><?php echo esc_html__( 'Formulas', 'rtbcb' ); ?></h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'Calculation', 'rtbcb' ); ?></th>
                <th><?php echo esc_html__( 'Formula', 'rtbcb' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo esc_html__( 'Labor Savings', 'rtbcb' ); ?></td>
                <td><?php echo esc_html__( '(Hours Reconciliation + Hours Cash Positioning) * 52 * Labor Cost Per Hour * 0.30 * Scenario Multiplier', 'rtbcb' ); ?></td>
            </tr>
            <tr>
                <td><?php echo esc_html__( 'Bank Fee Savings', 'rtbcb' ); ?></td>
                <td><?php echo esc_html__( 'Number of Banks * Bank Fee Baseline * 0.08 * Scenario Multiplier', 'rtbcb' ); ?></td>
            </tr>
            <tr>
                <td><?php echo esc_html__( 'Error Reduction', 'rtbcb' ); ?></td>
                <td><?php echo esc_html__( 'Base Error Cost * 0.25 * Scenario Multiplier', 'rtbcb' ); ?></td>
            </tr>
        </tbody>
    </table>
</div>

