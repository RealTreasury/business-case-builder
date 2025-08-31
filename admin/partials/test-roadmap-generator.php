<?php
/**
	* Partial for Test Roadmap Generator section.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

defined( 'ABSPATH' ) || exit;

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-roadmap-generator', false ) ) {
	echo '<div class="notice notice-warning inline"><p>' .
		esc_html__( 'Please complete previous steps before accessing this section.', 'rtbcb' ) .
		'</p></div>';
	return;
}

$company = rtbcb_get_current_company();
if ( empty( $company ) ) {
	$overview_url = admin_url( 'admin.php?page=rtbcb-test-dashboard#rtbcb-phase1' );
	echo '<div class="notice notice-error"><p>' . sprintf(
		esc_html__( 'No company data found. Please run the %s first.', 'rtbcb' ),
		'<a href="' . esc_url( $overview_url ) . '">' . esc_html__( 'Company Overview', 'rtbcb' ) . '</a>'
	) . '</p></div>';
	return;
}

$roadmap = [];

if ( isset( $_POST['rtbcb_generate_roadmap'] ) && check_admin_referer( 'rtbcb_generate_roadmap', 'rtbcb_generate_roadmap_nonce' ) ) {
	$rec = RTBCB_Category_Recommender::recommend_category( $company );
	update_option( 'rtbcb_last_recommended_category', $rec['recommended'] );
	$features = $rec['category_info']['features'] ?? [];
	$chunks   = array_chunk( $features, ceil( count( $features ) / 3 ) );
	foreach ( $chunks as $index => $items ) {
		$roadmap[ $index + 1 ] = array_map( 'sanitize_text_field', $items );
	}
	update_option( 'rtbcb_roadmap_plan', $roadmap );
}

$roadmap = ! empty( $roadmap ) ? $roadmap : get_option( 'rtbcb_roadmap_plan', [] );
?>
<h2><?php esc_html_e( 'Test Roadmap Generator', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Build a multi-phase implementation roadmap based on the recommended category.', 'rtbcb' ); ?></p>
<p class="rtbcb-data-source">
	<span class="rtbcb-data-status rtbcb-status-implementation-roadmap">⚪ <?php esc_html_e( 'Generate new', 'rtbcb' ); ?></span>
	<a href="#rtbcb-comprehensive-analysis" class="rtbcb-view-source" style="display:none;">
		<?php esc_html_e( 'View Source Data', 'rtbcb' ); ?>
	</a>
</p>
<form method="post">
	<?php wp_nonce_field( 'rtbcb_generate_roadmap', 'rtbcb_generate_roadmap_nonce' ); ?>
	<p class="submit">
		<button type="submit" name="rtbcb_generate_roadmap" class="button button-primary"><?php esc_html_e( 'Generate Roadmap', 'rtbcb' ); ?></button>
	</p>
</form>
<?php if ( ! empty( $roadmap ) ) : ?>
	<div class="card">
		<?php foreach ( $roadmap as $phase => $items ) : ?>
			<h3><?php printf( esc_html__( 'Phase %d', 'rtbcb' ), intval( $phase ) ); ?></h3>
			<ul>
				<?php foreach ( $items as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
