<?php
/**
 * Template for generated business case report.
 *
 * @package RealTreasuryBusinessCaseBuilder
 *
 * @var array $business_case_data Business case data from the LLM.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="rtbcb-report">
    <h2><?php echo esc_html__( 'Business Case Report', 'rtbcb' ); ?></h2>
    <?php if ( ! empty( $business_case_data['narrative'] ) ) : ?>
        <p><?php echo esc_html( $business_case_data['narrative'] ); ?></p>
    <?php endif; ?>

    <?php
    $risks = (array) ( $business_case_data['risks'] ?? [] );
    if ( empty( $risks ) ) {
	$risks[] = __( 'No data provided', 'rtbcb' );
    }
    if ( ! empty( $risks ) ) :
    ?>
        <h3><?php echo esc_html__( 'Risks', 'rtbcb' ); ?></h3>
        <ul>
            <?php foreach ( $risks as $risk ) : ?>
                <li><?php echo esc_html( $risk ); ?></li>
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
                        $url  = esc_url( $citation['url'] );
                        $text = ! empty( $citation['text'] ) ? esc_html( $citation['text'] ) : $url;
                        echo '<a href="' . $url . '">' . $text . '</a>';
                    } else {
                        echo esc_html( is_array( $citation ) ? wp_json_encode( $citation ) : $citation );
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
</div>
