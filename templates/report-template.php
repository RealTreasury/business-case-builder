<?php
defined( 'ABSPATH' ) || exit;

/**
 * Template for generated business case report.
 *
 * @package RealTreasuryBusinessCaseBuilder
 *
 * @var array $business_case_data Business case data from the LLM.
 */

$metadata      = $business_case_data['metadata'] ?? [];
$analysis_type = $metadata['analysis_type'] ?? 'basic';
$rag_context   = $business_case_data['rag_context'] ?? [];
$executive_summary    = $business_case_data['executive_summary'] ?? [];
$operational_analysis = $business_case_data['operational_analysis'] ?? [];
$industry_insights    = $business_case_data['industry_insights'] ?? [];
?>
<div class="rtbcb-report">
        <h2><?php echo esc_html__( 'Business Case Report', 'rtbcb' ); ?></h2>
        <p class="rtbcb-version-tag"><?php printf( esc_html__( 'Version %s', 'rtbcb' ), esc_html( defined( 'RTBCB_VERSION' ) ? RTBCB_VERSION : 'dev' ) ); ?></p>

	<?php if ( ! empty( $executive_summary['strategic_positioning'] ) || ! empty( $executive_summary['business_case_strength'] ) || ! empty( $executive_summary['key_value_drivers'] ) || ! empty( $executive_summary['executive_recommendation'] ) ) : ?>
               <h3><?php echo esc_html__( 'Executive Summary', 'rtbcb' ); ?></h3>
               <?php if ( ! empty( $executive_summary['strategic_positioning'] ) ) : ?>
                       <p><?php echo esc_html( $executive_summary['strategic_positioning'] ); ?></p>
               <?php endif; ?>
	<?php if ( ! empty( $executive_summary['business_case_strength'] ) ) : ?>
<p><?php echo esc_html( $executive_summary['business_case_strength'] ); ?></p>
	<?php endif; ?>
	<?php if ( ! empty( $executive_summary['key_value_drivers'] ) ) : ?>
<h4><?php echo esc_html__( 'Key Value Drivers', 'rtbcb' ); ?></h4>
	<?php if ( is_array( $executive_summary['key_value_drivers'] ) ) : ?>
<ul>
	<?php foreach ( $executive_summary['key_value_drivers'] as $driver ) : ?>
<li><?php echo esc_html( $driver ); ?></li>
	<?php endforeach; ?>
</ul>
	<?php else : ?>
<p><?php echo esc_html( $executive_summary['key_value_drivers'] ); ?></p>
	<?php endif; ?>
	<?php endif; ?>
	<?php if ( ! empty( $executive_summary['executive_recommendation'] ) ) : ?>
<p><?php echo esc_html( $executive_summary['executive_recommendation'] ); ?></p>
	<?php endif; ?>
<?php endif; ?>

       <?php if ( ! empty( $operational_analysis['current_state_assessment'] ) ) : ?>
               <h3><?php echo esc_html__( 'Operational Analysis', 'rtbcb' ); ?></h3>
               <p><?php echo esc_html( $operational_analysis['current_state_assessment'] ); ?></p>
       <?php endif; ?>

       <?php if ( ! empty( $industry_insights['sector_trends'] ) || ! empty( $industry_insights['competitive_benchmarks'] ) || ! empty( $industry_insights['regulatory_considerations'] ) ) : ?>
               <h3><?php echo esc_html__( 'Industry Insights', 'rtbcb' ); ?></h3>
               <?php if ( ! empty( $industry_insights['sector_trends'] ) ) : ?>
                       <h4><?php echo esc_html__( 'Sector Trends', 'rtbcb' ); ?></h4>
                       <?php if ( is_array( $industry_insights['sector_trends'] ) ) : ?>
                               <ul>
                                       <?php foreach ( $industry_insights['sector_trends'] as $trend ) : ?>
                                               <li><?php echo esc_html( $trend ); ?></li>
                                       <?php endforeach; ?>
                               </ul>
                       <?php else : ?>
                               <p><?php echo esc_html( $industry_insights['sector_trends'] ); ?></p>
                       <?php endif; ?>
               <?php endif; ?>
               <?php if ( ! empty( $industry_insights['competitive_benchmarks'] ) ) : ?>
                       <h4><?php echo esc_html__( 'Competitive Benchmarks', 'rtbcb' ); ?></h4>
                       <?php if ( is_array( $industry_insights['competitive_benchmarks'] ) ) : ?>
                               <ul>
                                       <?php foreach ( $industry_insights['competitive_benchmarks'] as $benchmark ) : ?>
                                               <li><?php echo esc_html( $benchmark ); ?></li>
                                       <?php endforeach; ?>
                               </ul>
                       <?php else : ?>
                               <p><?php echo esc_html( $industry_insights['competitive_benchmarks'] ); ?></p>
                       <?php endif; ?>
               <?php endif; ?>
               <?php if ( ! empty( $industry_insights['regulatory_considerations'] ) ) : ?>
                       <h4><?php echo esc_html__( 'Regulatory Considerations', 'rtbcb' ); ?></h4>
                       <?php if ( is_array( $industry_insights['regulatory_considerations'] ) ) : ?>
                               <ul>
                                       <?php foreach ( $industry_insights['regulatory_considerations'] as $reg ) : ?>
                                               <li><?php echo esc_html( $reg ); ?></li>
                                       <?php endforeach; ?>
                               </ul>
                       <?php else : ?>
                               <p><?php echo esc_html( $industry_insights['regulatory_considerations'] ); ?></p>
                       <?php endif; ?>
               <?php endif; ?>
       <?php endif; ?>

	<?php if ( ! empty( $business_case_data['citations'] ) ) : ?>
		<h3><?php echo esc_html__( 'Citations', 'rtbcb' ); ?></h3>
		<ol>
			<?php foreach ( (array) $business_case_data['citations'] as $citation ) : ?>
				<li>
					<?php
					if ( is_array( $citation ) && ! empty( $citation['url'] ) ) {
						$url  = $citation['url'];
						$text = ! empty( $citation['text'] ) ? $citation['text'] : $url;
						printf(
							'<a href="%s">%s</a>',
							esc_url( $url ),
							esc_html( $text )
						);
					} else {
						$encoded_citation = is_array( $citation ) ? ( function_exists( 'wp_json_encode' ) ? wp_json_encode( $citation ) : json_encode( $citation ) ) : $citation;
						echo esc_html( $encoded_citation );
					}
					?>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>

	<?php if ( ! empty( $business_case_data['next_actions'] ) ) : ?>
		<h3><?php echo esc_html__( 'Next Actions', 'rtbcb' ); ?></h3>
		<ul>
			<?php foreach ( (array) $business_case_data['next_actions'] as $action ) : ?>
				<li><?php echo esc_html( $action ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( isset( $business_case_data['confidence'] ) ) : ?>
		<p><?php printf( esc_html__( 'Confidence: %s%%', 'rtbcb' ), esc_html( round( (float) $business_case_data['confidence'] * 100 ) ) ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $business_case_data['recommended_category'] ) ) : ?>
		<p><?php echo esc_html__( 'Recommended Category:', 'rtbcb' ) . ' ' . esc_html( $business_case_data['recommended_category'] ); ?></p>
	<?php endif; ?>

	<?php if ( 'basic' !== $analysis_type ) : ?>
		<h3><?php echo esc_html__( 'Context', 'rtbcb' ); ?></h3>
		<?php if ( ! empty( $rag_context ) ) : ?>
				<ul>
						<?php foreach ( (array) $rag_context as $context_item ) : ?>
								<li><?php echo esc_html( $context_item ); ?></li>
						<?php endforeach; ?>
				</ul>
		<?php else : ?>
				<p><?php echo esc_html__( 'No additional context available.', 'rtbcb' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
</div>
