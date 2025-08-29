<?php
/**
 * Settings admin page for Real Treasury Business Case Builder plugin.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$api_key         = get_option( 'rtbcb_openai_api_key', '' );
$mini_model      = get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) );
$premium_model   = get_option( 'rtbcb_premium_model', rtbcb_get_default_model( 'premium' ) );
$advanced_model  = get_option( 'rtbcb_advanced_model', rtbcb_get_default_model( 'advanced' ) );
$embedding_model = get_option( 'rtbcb_embedding_model', rtbcb_get_default_model( 'embedding' ) );
$labor_cost      = get_option( 'rtbcb_labor_cost_per_hour', '' );
$bank_fee        = get_option( 'rtbcb_bank_fee_baseline', '' );
$gpt5_timeout    = get_option( 'rtbcb_gpt5_timeout', 180 );

$chat_models = [
    'gpt-5'             => 'gpt-5',
    'gpt-5-mini'        => 'gpt-5-mini',
    'gpt-5-nano'        => 'gpt-5-nano',
    'gpt-5-chat-latest' => 'gpt-5-chat-latest',
    'gpt-4o-mini'       => 'gpt-4o-mini',
    'gpt-4o'            => 'gpt-4o',
    'o1-mini'           => 'o1-mini',
    'o1-preview'        => 'o1-preview',
];

$embedding_models = [
    'text-embedding-3-small' => 'text-embedding-3-small',
    'text-embedding-3-large' => 'text-embedding-3-large',
];
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
                    <input type="password" id="rtbcb_openai_api_key" name="rtbcb_openai_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
                    <button type="button" class="button" id="rtbcb-toggle-api-key"><?php echo esc_html__( 'Show', 'rtbcb' ); ?></button>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php echo esc_html__( 'Diagnostics', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <button type="button" class="button" id="rtbcb-run-tests" data-nonce="<?php echo esc_attr( wp_create_nonce( 'rtbcb_nonce' ) ); ?>"><?php echo esc_html__( 'Run Diagnostics', 'rtbcb' ); ?></button>
                    <p class="description"><?php echo esc_html__( 'Verify integration and system health. Tests may take up to two minutes.', 'rtbcb' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb_mini_model"><?php echo esc_html__( 'Mini Model', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb_mini_model" name="rtbcb_mini_model">
                        <?php foreach ( $chat_models as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $mini_model, $value ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb_premium_model"><?php echo esc_html__( 'Premium Model', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb_premium_model" name="rtbcb_premium_model">
                        <?php foreach ( $chat_models as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $premium_model, $value ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb_advanced_model"><?php echo esc_html__( 'Advanced Model', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb_advanced_model" name="rtbcb_advanced_model">
                        <?php foreach ( $chat_models as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $advanced_model, $value ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb_gpt5_timeout"><?php echo esc_html__( 'API Request Timeout (seconds)', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="number" id="rtbcb_gpt5_timeout" name="rtbcb_gpt5_timeout" value="<?php echo esc_attr( $gpt5_timeout ); ?>" class="small-text" />
                    <p class="description"><?php echo esc_html__( 'Maximum time to wait for OpenAI responses.', 'rtbcb' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb_embedding_model"><?php echo esc_html__( 'Embedding Model', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb_embedding_model" name="rtbcb_embedding_model">
                        <?php foreach ( $embedding_models as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $embedding_model, $value ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb_labor_cost_per_hour"><?php echo esc_html__( 'Labor Cost Per Hour', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="rtbcb_labor_cost_per_hour" name="rtbcb_labor_cost_per_hour" value="<?php echo esc_attr( $labor_cost ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb_bank_fee_baseline"><?php echo esc_html__( 'Bank Fee Baseline', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="rtbcb_bank_fee_baseline" name="rtbcb_bank_fee_baseline" value="<?php echo esc_attr( $bank_fee ); ?>" class="regular-text" />
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>

<script type="text/javascript">
( function() {
    var toggleButton = document.getElementById( 'rtbcb-toggle-api-key' );
    var apiInput = document.getElementById( 'rtbcb_openai_api_key' );
    if ( toggleButton && apiInput ) {
        toggleButton.addEventListener( 'click', function() {
            if ( 'password' === apiInput.type ) {
                apiInput.type = 'text';
                toggleButton.textContent = '<?php echo esc_js( __( 'Hide', 'rtbcb' ) ); ?>';
            } else {
                apiInput.type = 'password';
                toggleButton.textContent = '<?php echo esc_js( __( 'Show', 'rtbcb' ) ); ?>';
            }
        } );
    }
} )();
</script>


