<?php
/**
 * Report test admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Report Test', 'rtbcb' ); ?></h1>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Generate Full Report', 'rtbcb' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="rtbcb-report-industry"><?php esc_html_e( 'Industry', 'rtbcb' ); ?></label></th>
                <td><input type="text" id="rtbcb-report-industry" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. manufacturing', 'rtbcb' ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="rtbcb-report-company"><?php esc_html_e( 'Company Name', 'rtbcb' ); ?></label></th>
                <td><input type="text" id="rtbcb-report-company" class="regular-text" placeholder="<?php esc_attr_e( 'Enter company name...', 'rtbcb' ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="rtbcb-report-focus"><?php esc_html_e( 'Focus Areas', 'rtbcb' ); ?></label></th>
                <td><input type="text" id="rtbcb-report-focus" class="regular-text" placeholder="<?php esc_attr_e( 'Comma separated', 'rtbcb' ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="rtbcb-report-complexity"><?php esc_html_e( 'Complexity', 'rtbcb' ); ?></label></th>
                <td><input type="text" id="rtbcb-report-complexity" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. mid', 'rtbcb' ); ?>" /></td>
            </tr>
        </table>
        <p class="submit">
            <button type="button" id="rtbcb-generate-report" class="button button-primary"><?php esc_html_e( 'Generate Report', 'rtbcb' ); ?></button>
        </p>
    </div>

    <div id="rtbcb-report-output" style="display:none;">
        <div class="rtbcb-report-summary">
            <p><strong><?php esc_html_e( 'Word Count:', 'rtbcb' ); ?></strong> <span id="rtbcb-report-word-count"></span></p>
            <p><strong><?php esc_html_e( 'Generated:', 'rtbcb' ); ?></strong> <span id="rtbcb-report-generated"></span></p>
            <p>
                <button type="button" id="rtbcb-export-html" class="button"><?php esc_html_e( 'Export HTML', 'rtbcb' ); ?></button>
                <button type="button" id="rtbcb-export-pdf" class="button"><?php esc_html_e( 'Export PDF', 'rtbcb' ); ?></button>
            </p>
        </div>
        <div class="rtbcb-report-section" id="rtbcb-section-commentary">
            <h2><?php esc_html_e( 'Industry Commentary', 'rtbcb' ); ?> <button type="button" class="button rtbcb-regenerate" data-section="commentary"><?php esc_html_e( 'Regenerate', 'rtbcb' ); ?></button></h2>
            <div class="rtbcb-section-content"></div>
        </div>
        <div class="rtbcb-report-section" id="rtbcb-section-company">
            <h2><?php esc_html_e( 'Company Overview', 'rtbcb' ); ?> <button type="button" class="button rtbcb-regenerate" data-section="company"><?php esc_html_e( 'Regenerate', 'rtbcb' ); ?></button></h2>
            <div class="rtbcb-section-content"></div>
        </div>
        <div class="rtbcb-report-section" id="rtbcb-section-tech">
            <h2><?php esc_html_e( 'Treasury Tech Overview', 'rtbcb' ); ?> <button type="button" class="button rtbcb-regenerate" data-section="tech"><?php esc_html_e( 'Regenerate', 'rtbcb' ); ?></button></h2>
            <div class="rtbcb-section-content"></div>
        </div>
    </div>
</div>
<script>
<?php if ( ! isset( $GLOBALS['ajaxurl'] ) || empty( $GLOBALS['ajaxurl'] ) ) : ?>
var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
<?php endif; ?>
</script>
