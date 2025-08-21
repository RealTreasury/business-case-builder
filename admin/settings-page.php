<?php
/**
 * Settings admin page for Real Treasury Business Case Builder plugin.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$api_key         = get_option( 'rtbcb_openai_api_key', '' );
$mini_model      = get_option( 'rtbcb_mini_model', '' );
$premium_model   = get_option( 'rtbcb_premium_model', '' );
$embedding_model = get_option( 'rtbcb_embedding_model', '' );
$labor_cost      = get_option( 'rtbcb_labor_cost_per_hour', '' );
$bank_fee        = get_option( 'rtbcb_bank_fee_baseline', '' );
$pdf_enabled     = (bool) get_option( 'rtbcb_pdf_enabled', true );
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Business Case Builder Settings', 'rtbcb' ); ?></h1>
    <form action="options.php" method="post">
        <?php settings_fields( 'rtbcb_settings' ); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="rtbcb_openai_api_key"><?php echo esc_html__( 'OpenAI API Key', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="text" id="rtbcb_openai_api_key" name="rtbcb_openai_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb_mini_model"><?php echo esc_html__( 'Mini Model', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="text" id="rtbcb_mini_model" name="rtbcb_mini_model" value="<?php echo esc_attr( $mini_model ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb_premium_model"><?php echo esc_html__( 'Premium Model', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="text" id="rtbcb_premium_model" name="rtbcb_premium_model" value="<?php echo esc_attr( $premium_model ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb_embedding_model"><?php echo esc_html__( 'Embedding Model', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="text" id="rtbcb_embedding_model" name="rtbcb_embedding_model" value="<?php echo esc_attr( $embedding_model ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb_labor_cost_per_hour"><?php echo esc_html__( 'Labor Cost Per Hour', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="rtbcb_labor_cost_per_hour" name="rtbcb_labor_cost_per_hour" value="<?php echo esc_attr( $labor_cost ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb_bank_fee_baseline"><?php echo esc_html__( 'Bank Fee Baseline', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="rtbcb_bank_fee_baseline" name="rtbcb_bank_fee_baseline" value="<?php echo esc_attr( $bank_fee ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb_pdf_enabled"><?php echo esc_html__( 'Enable PDF Generation', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="rtbcb_pdf_enabled" name="rtbcb_pdf_enabled" value="1" <?php checked( $pdf_enabled ); ?> />
                        <?php esc_html_e( 'Generate downloadable PDF reports', 'rtbcb' ); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>


