<?php
/**
 * Complete report test page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Report Test', 'rtbcb' ); ?></h1>
    <?php wp_nonce_field( 'rtbcb_test_generate_complete_report', 'rtbcb_complete_report_nonce' ); ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="rtbcb-company-name"><?php esc_html_e( 'Company Name', 'rtbcb' ); ?></label>
            </th>
            <td><input type="text" id="rtbcb-company-name" class="regular-text" /></td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rtbcb-focus-areas"><?php esc_html_e( 'Focus Areas', 'rtbcb' ); ?></label>
            </th>
            <td>
                <input type="text" id="rtbcb-focus-areas" class="regular-text" placeholder="<?php esc_attr_e( 'cash forecasting, payments', 'rtbcb' ); ?>" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rtbcb-complexity"><?php esc_html_e( 'Complexity', 'rtbcb' ); ?></label>
            </th>
            <td>
                <select id="rtbcb-complexity">
                    <option value=""><?php esc_html_e( 'Select', 'rtbcb' ); ?></option>
                    <option value="low"><?php esc_html_e( 'Low', 'rtbcb' ); ?></option>
                    <option value="medium"><?php esc_html_e( 'Medium', 'rtbcb' ); ?></option>
                    <option value="high"><?php esc_html_e( 'High', 'rtbcb' ); ?></option>
                </select>
            </td>
        </tr>
    </table>
    <p>
        <button type="button" id="rtbcb-generate-report" class="button button-primary">
            <?php esc_html_e( 'Generate Report', 'rtbcb' ); ?>
        </button>
    </p>
    <div id="rtbcb-report-status"></div>

    <div id="rtbcb-report-summary" style="display:none;">
        <p><?php esc_html_e( 'Word Count:', 'rtbcb' ); ?> <span id="rtbcb-summary-word-count"></span></p>
        <p><?php esc_html_e( 'Generated:', 'rtbcb' ); ?> <span id="rtbcb-summary-generated"></span></p>
        <p><?php esc_html_e( 'Elapsed Time:', 'rtbcb' ); ?> <span id="rtbcb-summary-elapsed"></span></p>
    </div>

    <div id="rtbcb-report-actions" style="display:none;">
        <a id="rtbcb-export-html" class="button" href="#" target="_blank"><?php esc_html_e( 'Download HTML', 'rtbcb' ); ?></a>
        <button id="rtbcb-export-pdf" class="button" type="button"><?php esc_html_e( 'Export PDF', 'rtbcb' ); ?></button>
        <button id="rtbcb-copy-report" class="button" type="button"><?php esc_html_e( 'Copy Report', 'rtbcb' ); ?></button>
        <button id="rtbcb-clear-report" class="button" type="button"><?php esc_html_e( 'Clear', 'rtbcb' ); ?></button>
    </div>

    <div id="rtbcb-report-sections" style="display:none;">
        <div id="rtbcb-section-company_overview" class="rtbcb-section">
            <h2>
                <?php esc_html_e( 'Company Overview', 'rtbcb' ); ?>
                <button type="button" class="button rtbcb-regenerate" data-section="company_overview"><?php esc_html_e( 'Regenerate', 'rtbcb' ); ?></button>
                <button type="button" class="button rtbcb-copy" data-section="company_overview"><?php esc_html_e( 'Copy', 'rtbcb' ); ?></button>
            </h2>
            <p class="rtbcb-section-stats">
                <?php esc_html_e( 'Word count:', 'rtbcb' ); ?> <span class="rtbcb-word-count"></span>
                | <?php esc_html_e( 'Generated:', 'rtbcb' ); ?> <span class="rtbcb-generated"></span>
                | <?php esc_html_e( 'Elapsed:', 'rtbcb' ); ?> <span class="rtbcb-elapsed"></span>
            </p>
            <div class="rtbcb-section-content"></div>
        </div>

        <div id="rtbcb-section-treasury_tech_overview" class="rtbcb-section">
            <h2>
                <?php esc_html_e( 'Treasury Tech Overview', 'rtbcb' ); ?>
                <button type="button" class="button rtbcb-regenerate" data-section="treasury_tech_overview"><?php esc_html_e( 'Regenerate', 'rtbcb' ); ?></button>
                <button type="button" class="button rtbcb-copy" data-section="treasury_tech_overview"><?php esc_html_e( 'Copy', 'rtbcb' ); ?></button>
            </h2>
            <p class="rtbcb-section-stats">
                <?php esc_html_e( 'Word count:', 'rtbcb' ); ?> <span class="rtbcb-word-count"></span>
                | <?php esc_html_e( 'Generated:', 'rtbcb' ); ?> <span class="rtbcb-generated"></span>
                | <?php esc_html_e( 'Elapsed:', 'rtbcb' ); ?> <span class="rtbcb-elapsed"></span>
            </p>
            <div class="rtbcb-section-content"></div>
        </div>

        <div id="rtbcb-section-roi" class="rtbcb-section">
            <h2>
                <?php esc_html_e( 'ROI', 'rtbcb' ); ?>
                <button type="button" class="button rtbcb-regenerate" data-section="roi"><?php esc_html_e( 'Regenerate', 'rtbcb' ); ?></button>
                <button type="button" class="button rtbcb-copy" data-section="roi"><?php esc_html_e( 'Copy', 'rtbcb' ); ?></button>
            </h2>
            <p class="rtbcb-section-stats">
                <?php esc_html_e( 'Generated:', 'rtbcb' ); ?> <span class="rtbcb-generated"></span>
                | <?php esc_html_e( 'Elapsed:', 'rtbcb' ); ?> <span class="rtbcb-elapsed"></span>
            </p>
            <div class="rtbcb-section-content"></div>
        </div>
    </div>

    <div id="rtbcb-report-preview" style="display:none;"></div>
</div>

