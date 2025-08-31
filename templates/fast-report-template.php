<?php
/**
 * Fast report template with minimal narrative and ROI summary.
 *
 * @package RealTreasuryBusinessCaseBuilder
 * @var array $business_case_data Business case data from the LLM.
 */

defined( 'ABSPATH' ) || exit;

$narrative     = $business_case_data['narrative'] ?? '';
$roi_scenarios = $business_case_data['scenarios'] ?? ( $business_case_data['roi_scenarios'] ?? [] );

if ( empty( $roi_scenarios ) && isset( $business_case_data['roi_base'] ) ) {
	$roi_scenarios = [
	    'base' => [ 'total_annual_benefit' => $business_case_data['roi_base'] ],
	];
}
?>
<div class="rtbcb-fast-report">
	<h2><?php echo esc_html__( 'Business Case Snapshot', 'rtbcb' ); ?></h2>

	<?php if ( $narrative ) : ?>
	    <p><?php echo esc_html( $narrative ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $roi_scenarios ) ) : ?>
	    <h3><?php echo esc_html__( 'ROI Summary', 'rtbcb' ); ?></h3>
	    <ul>
	        <?php foreach ( (array) $roi_scenarios as $label => $scenario ) : ?>
	            <?php
	            $benefit = 0;
	            if ( is_array( $scenario ) && isset( $scenario['total_annual_benefit'] ) ) {
	                $benefit = (float) $scenario['total_annual_benefit'];
	            } elseif ( is_numeric( $scenario ) ) {
	                $benefit = (float) $scenario;
	            }
	            ?>
	            <li><?php echo esc_html( ucfirst( $label ) . ': ' . number_format_i18n( $benefit, 2 ) ); ?></li>
	        <?php endforeach; ?>
	    </ul>
	<?php endif; ?>
</div>
