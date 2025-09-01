<?php
/**
	* Settings admin page for Real Treasury Business Case Builder plugin.
	*/

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$api_key         = function_exists( 'get_option' ) ? get_option( 'rtbcb_openai_api_key', '' ) : '';
$mini_model      = function_exists( 'get_option' ) ? get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) ) : rtbcb_get_default_model( 'mini' );
$premium_model   = function_exists( 'get_option' ) ? get_option( 'rtbcb_premium_model', rtbcb_get_default_model( 'premium' ) ) : rtbcb_get_default_model( 'premium' );
$advanced_model  = function_exists( 'get_option' ) ? get_option( 'rtbcb_advanced_model', rtbcb_get_default_model( 'advanced' ) ) : rtbcb_get_default_model( 'advanced' );
$embedding_model = function_exists( 'get_option' ) ? get_option( 'rtbcb_embedding_model', rtbcb_get_default_model( 'embedding' ) ) : rtbcb_get_default_model( 'embedding' );
$labor_cost      = function_exists( 'get_option' ) ? get_option( 'rtbcb_labor_cost_per_hour', '' ) : '';
$bank_fee        = function_exists( 'get_option' ) ? get_option( 'rtbcb_bank_fee_baseline', '' ) : '';
$gpt5_timeout    = rtbcb_get_api_timeout();
$gpt5_max_output_tokens = function_exists( 'get_option' ) ? get_option( 'rtbcb_gpt5_max_output_tokens', 8000 ) : 8000;
$gpt5_min_output_tokens = function_exists( 'get_option' ) ? get_option( 'rtbcb_gpt5_min_output_tokens', 256 ) : 256;
$fast_mode       = function_exists( 'get_option' ) ? get_option( 'rtbcb_fast_mode', 0 ) : 0;
$feature_defaults = class_exists( 'RTBCB_Settings' ) ? RTBCB_Settings::DEFAULTS : [];
$feature_settings = function_exists( 'get_option' ) ? get_option( 'rtbcb_settings', $feature_defaults ) : $feature_defaults;
$enable_ai_analysis = isset( $feature_settings['enable_ai_analysis'] ) ? (bool) $feature_settings['enable_ai_analysis'] : true;
$enable_charts      = isset( $feature_settings['enable_charts'] ) ? (bool) $feature_settings['enable_charts'] : true;

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

<?php if ( ! rtbcb_has_openai_api_key() ) : ?>
	<div class="notice notice-warning is-dismissible">
		<p><?php echo esc_html__( 'OpenAI API key is missing. Please enter a valid key to enable AI features.', 'rtbcb' ); ?></p>
	</div>
<?php endif; ?>

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
					<input type="number" id="rtbcb_gpt5_timeout" name="rtbcb_gpt5_timeout" value="<?php echo esc_attr( $gpt5_timeout ); ?>" class="small-text" min="1" max="600" />
					<p class="description"><?php echo esc_html__( 'Maximum time to wait for OpenAI responses (default 300, max 600).', 'rtbcb' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="rtbcb_gpt5_min_output_tokens"><?php echo esc_html__( 'Min Output Tokens', 'rtbcb' ); ?></label>
				</th>
				<td>
					<input type="number" id="rtbcb_gpt5_min_output_tokens" name="rtbcb_gpt5_min_output_tokens" value="<?php echo esc_attr( $gpt5_min_output_tokens ); ?>" class="small-text" min="1" max="128000" />
					<p class="description"><?php echo esc_html__( 'Minimum tokens returned by OpenAI.', 'rtbcb' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="rtbcb_gpt5_max_output_tokens"><?php echo esc_html__( 'Max Output Tokens', 'rtbcb' ); ?></label>
				</th>
				<td>
					<input type="number" id="rtbcb_gpt5_max_output_tokens" name="rtbcb_gpt5_max_output_tokens" value="<?php echo esc_attr( $gpt5_max_output_tokens ); ?>" class="small-text" min="256" max="128000" />
					<p class="description"><?php echo esc_html__( 'Maximum tokens returned by OpenAI (max 128000).', 'rtbcb' ); ?></p>
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
			<tr>
				<th scope="row">
					<label for="rtbcb_fast_mode"><?php echo esc_html__( 'Fast Mode', 'rtbcb' ); ?></label>
				</th>
				<td>
					<input type="checkbox" id="rtbcb_fast_mode" name="rtbcb_fast_mode" value="1" <?php checked( 1, $fast_mode ); ?> />
					<p class="description"><?php echo esc_html__( 'Generate a basic ROI-only report without AI processing.', 'rtbcb' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="rtbcb_enable_ai_analysis"><?php echo esc_html__( 'Enable AI Analysis', 'rtbcb' ); ?></label>
				</th>
				<td>
					<input type="checkbox" id="rtbcb_enable_ai_analysis" name="rtbcb_settings[enable_ai_analysis]" value="1" <?php checked( $enable_ai_analysis ); ?> />
					<p class="description"><?php echo esc_html__( 'Use AI services for enhanced insights and recommendations.', 'rtbcb' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="rtbcb_enable_charts"><?php echo esc_html__( 'Enable Charts', 'rtbcb' ); ?></label>
				</th>
				<td>
					<input type="checkbox" id="rtbcb_enable_charts" name="rtbcb_settings[enable_charts]" value="1" <?php checked( $enable_charts ); ?> />
					<p class="description"><?php echo esc_html__( 'Display visual charts in reports and analytics.', 'rtbcb' ); ?></p>
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


