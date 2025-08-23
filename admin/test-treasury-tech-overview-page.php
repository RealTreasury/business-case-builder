<?php
/**
 * Test Treasury Technology Overview admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_step = 'treasury_tech_overview';
$steps        = rtbcb_get_test_steps();

if ( ! rtbcb_previous_steps_complete( $current_step ) ) {
    $first = reset( $steps );
    echo '<div class="wrap rtbcb-admin-page"><div class="notice notice-warning"><p>' . esc_html__( 'Please complete previous steps before proceeding.', 'rtbcb' ) . '</p><p><a href="' . esc_url( admin_url( 'admin.php?page=' . $first['page'] ) ) . '">' . esc_html__( 'Return to start', 'rtbcb' ) . '</a></p></div></div>';
    return;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Treasury Technology Overview', 'rtbcb' ); ?></h1>

    <?php rtbcb_render_test_progress( $current_step ); ?>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Generate Overview', 'rtbcb' ); ?></h2>
        <p><?php esc_html_e( 'Select focus areas and company complexity to generate an overview.', 'rtbcb' ); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Focus Areas', 'rtbcb' ); ?></th>
                <td>
                    <?php
                    $areas = [
                        'cash'      => __( 'Cash Management', 'rtbcb' ),
                        'payments'  => __( 'Payments', 'rtbcb' ),
                        'risk'      => __( 'Risk Management', 'rtbcb' ),
                        'liquidity' => __( 'Liquidity', 'rtbcb' ),
                        'analytics' => __( 'Analytics', 'rtbcb' ),
                    ];
                    foreach ( $areas as $value => $label ) :
                        ?>
                        <label>
                            <input type="checkbox" name="rtbcb_focus_areas[]" value="<?php echo esc_attr( $value ); ?>" />
                            <?php echo esc_html( $label ); ?>
                        </label><br />
                        <?php
                    endforeach;
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb-company-complexity"><?php esc_html_e( 'Company Complexity', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb-company-complexity">
                        <option value="simple"><?php esc_html_e( 'Simple', 'rtbcb' ); ?></option>
                        <option value="moderate"><?php esc_html_e( 'Moderate', 'rtbcb' ); ?></option>
                        <option value="complex"><?php esc_html_e( 'Complex', 'rtbcb' ); ?></option>
                    </select>
                    <?php wp_nonce_field( 'rtbcb_test_treasury_tech_overview', 'rtbcb_test_treasury_tech_overview_nonce' ); ?>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="button" id="rtbcb-generate-treasury-tech-overview" class="button button-primary">
                <?php esc_html_e( 'Generate Overview', 'rtbcb' ); ?>
            </button>
            <button type="button" id="rtbcb-clear-treasury-tech-overview" class="button">
                <?php esc_html_e( 'Clear', 'rtbcb' ); ?>
            </button>
        </p>
    </div>

    <div id="rtbcb-treasury-tech-overview-results"></div>

    <?php rtbcb_render_test_navigation( $current_step ); ?>
</div>

<style>
#rtbcb-treasury-tech-overview-results {
    margin-top: 20px;
}
#rtbcb-treasury-tech-overview-results .notice {
    margin: 5px 0;
}
#rtbcb-treasury-tech-overview-results div[style*="background"] {
    white-space: pre-wrap;
    line-height: 1.6;
}
</style>

<script>
// Ensure ajaxurl is available
<?php if ( ! isset( $GLOBALS['ajaxurl'] ) || empty( $GLOBALS['ajaxurl'] ) ) : ?>
var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
<?php endif; ?>
</script>
