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
?>
<div class="rtbcb-report">
	<h2><?php echo esc_html__( 'Business Case Report', 'rtbcb' ); ?></h2>
	<p class="rtbcb-version-tag"><?php printf( esc_html__( 'Version %s', 'rtbcb' ), esc_html( defined( 'RTBCB_VERSION' ) ? RTBCB_VERSION : 'dev' ) ); ?></p>
	<?php if ( ! empty( $business_case_data['narrative'] ) ) : ?>
		<p><?php echo esc_html( $business_case_data['narrative'] ); ?></p>
	<?php endif; ?>

        <?php if ( ! empty( $business_case_data['risks'] ) ) : ?>
                <h3><?php echo esc_html__( 'Risks', 'rtbcb' ); ?></h3>
		<ul>
			<?php foreach ( (array) $business_case_data['risks'] as $risk ) : ?>
				<li><?php echo esc_html( $risk ); ?></li>
			<?php endforeach; ?>
		</ul>
        <?php endif; ?>

       <?php if ( ! empty( $business_case_data['industry_insights'] ) ) : ?>
               <h3><?php echo esc_html__( 'Industry Insights', 'rtbcb' ); ?></h3>
               <ul>
                       <?php foreach ( (array) $business_case_data['industry_insights'] as $insight ) : ?>
                               <li><?php echo esc_html( $insight ); ?></li>
                       <?php endforeach; ?>
               </ul>
       <?php endif; ?>

	<?php if ( ! empty( $business_case_data['assumptions_explained'] ) ) : ?>
		<h3><?php echo esc_html__( 'Assumptions', 'rtbcb' ); ?></h3>
		<ul>
			<?php foreach ( (array) $business_case_data['assumptions_explained'] as $assumption ) : ?>
				<li><?php echo esc_html( $assumption ); ?></li>
			<?php endforeach; ?>
		</ul>
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
