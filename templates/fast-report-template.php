<?php
defined( 'ABSPATH' ) || exit;

/**
	* Fast ROI report template.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

$company_name = isset( $form_data['company_name'] ) ? sanitize_text_field( $form_data['company_name'] ) : '';
$roi_low  = isset( $calculations['conservative']['roi_percentage'] ) ? floatval( $calculations['conservative']['roi_percentage'] ) : 0;
$roi_base = isset( $calculations['base']['roi_percentage'] ) ? floatval( $calculations['base']['roi_percentage'] ) : 0;
$roi_high = isset( $calculations['optimistic']['roi_percentage'] ) ? floatval( $calculations['optimistic']['roi_percentage'] ) : 0;
?>
<div class="rtbcb-fast-report">
	<h2><?php echo esc_html( $company_name ); ?></h2>
	<p>
		<?php
		printf(
			esc_html__( 'Based on your inputs, the projected ROI for %1$s ranges from %2$s%% to %3$s%%.', 'rtbcb' ),
			esc_html( $company_name ),
			esc_html( number_format_i18n( $roi_low, 2 ) ),
			esc_html( number_format_i18n( $roi_high, 2 ) )
		);
		?>
	</p>
	<h3><?php esc_html_e( 'ROI Summary', 'rtbcb' ); ?></h3>
	<ul>
		<li><?php printf( esc_html__( 'Conservative ROI: %s%%', 'rtbcb' ), esc_html( number_format_i18n( $roi_low, 2 ) ) ); ?></li>
		<li><?php printf( esc_html__( 'Base ROI: %s%%', 'rtbcb' ), esc_html( number_format_i18n( $roi_base, 2 ) ) ); ?></li>
		<li><?php printf( esc_html__( 'Optimistic ROI: %s%%', 'rtbcb' ), esc_html( number_format_i18n( $roi_high, 2 ) ) ); ?></li>
	</ul>
</div>
