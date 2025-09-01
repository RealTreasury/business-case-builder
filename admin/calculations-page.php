<?php
defined( 'ABSPATH' ) || exit;

/**
 * Calculation info admin page for Real Treasury Business Case Builder plugin.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

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

	<h2><?php echo esc_html__( 'Industry Commentary Test', 'rtbcb' ); ?></h2>
	<p>
		<label for="rtbcb-commentary-industry"><?php esc_html_e( 'Industry', 'rtbcb' ); ?></label>
		<select id="rtbcb-commentary-industry">
			<option value=""><?php esc_html_e( 'Select your industry...', 'rtbcb' ); ?></option>
			<option value="manufacturing"><?php esc_html_e( 'Manufacturing', 'rtbcb' ); ?></option>
			<option value="retail"><?php esc_html_e( 'Retail &amp; E-commerce', 'rtbcb' ); ?></option>
			<option value="healthcare"><?php esc_html_e( 'Healthcare', 'rtbcb' ); ?></option>
			<option value="technology"><?php esc_html_e( 'Technology', 'rtbcb' ); ?></option>
			<option value="financial_services"><?php esc_html_e( 'Financial Services', 'rtbcb' ); ?></option>
			<option value="energy"><?php esc_html_e( 'Energy &amp; Utilities', 'rtbcb' ); ?></option>
			<option value="real_estate"><?php esc_html_e( 'Real Estate', 'rtbcb' ); ?></option>
			<option value="professional_services"><?php esc_html_e( 'Professional Services', 'rtbcb' ); ?></option>
			<option value="transportation"><?php esc_html_e( 'Transportation &amp; Logistics', 'rtbcb' ); ?></option>
			<option value="education"><?php esc_html_e( 'Education', 'rtbcb' ); ?></option>
			<option value="government"><?php esc_html_e( 'Government', 'rtbcb' ); ?></option>
			<option value="other"><?php esc_html_e( 'Other', 'rtbcb' ); ?></option>
		</select>
		<button type="button" id="rtbcb-generate-commentary" class="button" data-nonce="<?php echo esc_attr( wp_create_nonce( 'rtbcb_test_commentary' ) ); ?>">
			<?php esc_html_e( 'Generate Commentary', 'rtbcb' ); ?>
		</button>
	</p>
	<div id="rtbcb-commentary-results"></div>
</div>

