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
	<?php
	$executive_summary       = $business_case_data['executive_summary'] ?? [];
	$strategic_positioning   = sanitize_text_field( $executive_summary['strategic_positioning'] ?? '' );
	$business_case_strength  = sanitize_text_field( $executive_summary['business_case_strength'] ?? '' );
	$key_value_drivers       = array_map( 'sanitize_text_field', (array) ( $executive_summary['key_value_drivers'] ?? [] ) );
	$executive_recommendation = sanitize_text_field( $executive_summary['executive_recommendation'] ?? '' );

	if ( $strategic_positioning || $business_case_strength || ! empty( $key_value_drivers ) || $executive_recommendation ) :
	?>
	<h3><?php echo esc_html__( 'Executive Summary', 'rtbcb' ); ?></h3>
	<?php if ( $strategic_positioning ) : ?>
	<p><?php echo esc_html( $strategic_positioning ); ?></p>
	<?php endif; ?>
	<?php if ( $business_case_strength ) : ?>
	<p><?php echo esc_html( $business_case_strength ); ?></p>
	<?php endif; ?>
	<?php if ( ! empty( $key_value_drivers ) ) : ?>
	<ul>
	<?php foreach ( $key_value_drivers as $driver ) : ?>
	<li><?php echo esc_html( $driver ); ?></li>
	<?php endforeach; ?>
	</ul>
	<?php endif; ?>
	<?php if ( $executive_recommendation ) : ?>
	<p><?php echo esc_html( $executive_recommendation ); ?></p>
	<?php endif; ?>
	<?php endif; ?>

	<?php
	$operational_insights_raw = $business_case_data['operational_insights'] ?? [];
	if ( isset( $operational_insights_raw['current_state_assessment'] ) ) {
		$operational_insights_raw = $operational_insights_raw['current_state_assessment'];
	}
	$operational_insights = array_map( 'sanitize_text_field', (array) $operational_insights_raw );
	if ( ! empty( $operational_insights ) ) :
	?>
	<h3><?php echo esc_html__( 'Operational Analysis', 'rtbcb' ); ?></h3>
	<ul>
	<?php foreach ( $operational_insights as $assessment ) : ?>
	<li><?php echo esc_html( $assessment ); ?></li>
	<?php endforeach; ?>
	</ul>
	<?php endif; ?>

	<?php
$industry_context   = $business_case_data['company_intelligence']['industry_context'] ?? [];
$sector_analysis    = $industry_context['sector_analysis'] ?? [];
$benchmarking       = $industry_context['benchmarking'] ?? [];
$regulatory_land    = $industry_context['regulatory_landscape'] ?? [];

$market_dynamics    = sanitize_text_field( $sector_analysis['market_dynamics'] ?? '' );
$growth_trends      = sanitize_text_field( $sector_analysis['growth_trends'] ?? '' );
$disruption_factors = array_map( 'sanitize_text_field', (array) ( $sector_analysis['disruption_factors'] ?? [] ) );
$technology_adoption= sanitize_text_field( $sector_analysis['technology_adoption'] ?? '' );
$typical_setup      = sanitize_text_field( $benchmarking['typical_treasury_setup'] ?? '' );
$common_pains       = array_map( 'sanitize_text_field', (array) ( $benchmarking['common_pain_points'] ?? [] ) );
$investment_patterns= sanitize_text_field( $benchmarking['investment_patterns'] ?? '' );
$key_regulations    = array_map( 'sanitize_text_field', (array) ( $regulatory_land['key_regulations'] ?? [] ) );
$compliance_complexity = sanitize_text_field( $regulatory_land['compliance_complexity'] ?? '' );
$upcoming_changes   = array_map( 'sanitize_text_field', (array) ( $regulatory_land['upcoming_changes'] ?? [] ) );

if ( $market_dynamics || $growth_trends || $disruption_factors || $technology_adoption || $typical_setup || $common_pains || $investment_patterns || $key_regulations || $compliance_complexity || $upcoming_changes ) :
?>
<h3><?php echo esc_html__( 'Industry Insights', 'rtbcb' ); ?></h3>
<?php if ( $market_dynamics ) : ?>
<h4><?php echo esc_html__( 'Market Dynamics', 'rtbcb' ); ?></h4>
<p><?php echo esc_html( $market_dynamics ); ?></p>
<?php endif; ?>
<?php if ( $growth_trends ) : ?>
<h4><?php echo esc_html__( 'Growth Trends', 'rtbcb' ); ?></h4>
<p><?php echo esc_html( $growth_trends ); ?></p>
<?php endif; ?>
<?php if ( $disruption_factors ) : ?>
<h4><?php echo esc_html__( 'Disruption Factors', 'rtbcb' ); ?></h4>
<ul>
<?php foreach ( $disruption_factors as $factor ) : ?>
<li><?php echo esc_html( $factor ); ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<?php if ( $technology_adoption ) : ?>
<h4><?php echo esc_html__( 'Technology Adoption', 'rtbcb' ); ?></h4>
<p><?php echo esc_html( $technology_adoption ); ?></p>
<?php endif; ?>
<?php if ( $typical_setup ) : ?>
<h4><?php echo esc_html__( 'Typical Treasury Setup', 'rtbcb' ); ?></h4>
<p><?php echo esc_html( $typical_setup ); ?></p>
<?php endif; ?>
<?php if ( $common_pains ) : ?>
<h4><?php echo esc_html__( 'Common Pain Points', 'rtbcb' ); ?></h4>
<ul>
<?php foreach ( $common_pains as $pain ) : ?>
<li><?php echo esc_html( $pain ); ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<?php if ( $investment_patterns ) : ?>
<h4><?php echo esc_html__( 'Investment Patterns', 'rtbcb' ); ?></h4>
<p><?php echo esc_html( $investment_patterns ); ?></p>
<?php endif; ?>
<?php if ( $key_regulations ) : ?>
<h4><?php echo esc_html__( 'Key Regulations', 'rtbcb' ); ?></h4>
<ul>
<?php foreach ( $key_regulations as $reg ) : ?>
<li><?php echo esc_html( $reg ); ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<?php if ( $compliance_complexity ) : ?>
<h4><?php echo esc_html__( 'Compliance Complexity', 'rtbcb' ); ?></h4>
<p><?php echo esc_html( $compliance_complexity ); ?></p>
<?php endif; ?>
<?php if ( $upcoming_changes ) : ?>
<h4><?php echo esc_html__( 'Upcoming Changes', 'rtbcb' ); ?></h4>
<ul>
<?php foreach ( $upcoming_changes as $change ) : ?>
<li><?php echo esc_html( $change ); ?></li>
<?php endforeach; ?>
</ul>
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
