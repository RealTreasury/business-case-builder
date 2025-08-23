<?php
/**
 * Report test page with complete report generation.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Report Test', 'rtbcb' ); ?></h1>
    <form id="rtbcb-report-test-form" method="post">
        <?php wp_nonce_field( 'rtbcb_generate_complete_report', 'nonce' ); ?>
        <table class="form-table">
            <tr>
                <th><label for="rtbcb-company-name"><?php esc_html_e( 'Company Name', 'rtbcb' ); ?></label></th>
                <td><input type="text" id="rtbcb-company-name" name="company_name" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="rtbcb-focus-areas"><?php esc_html_e( 'Focus Areas (comma separated)', 'rtbcb' ); ?></label></th>
                <td><input type="text" id="rtbcb-focus-areas" name="focus_areas" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="rtbcb-complexity"><?php esc_html_e( 'Complexity', 'rtbcb' ); ?></label></th>
                <td>
                    <select id="rtbcb-complexity" name="complexity">
                        <option value=""></option>
                        <option value="basic"><?php esc_html_e( 'Basic', 'rtbcb' ); ?></option>
                        <option value="advanced"><?php esc_html_e( 'Advanced', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="rtbcb-roi-inputs"><?php esc_html_e( 'ROI Inputs (JSON)', 'rtbcb' ); ?></label></th>
                <td><textarea id="rtbcb-roi-inputs" name="roi_inputs" rows="4" class="large-text"></textarea></td>
            </tr>
        </table>
        <p>
            <button type="submit" class="button button-primary" id="rtbcb-generate-report">
                <?php esc_html_e( 'Generate Full Report', 'rtbcb' ); ?>
            </button>
        </p>
    </form>
    <div id="rtbcb-report-error" class="notice notice-error" style="display:none;"></div>
    <div id="rtbcb-report-meta"></div>
    <p class="rtbcb-report-actions">
        <a href="#" id="rtbcb-export-html" class="button" style="display:none;" target="_blank">
            <?php esc_html_e( 'Download HTML', 'rtbcb' ); ?>
        </a>
        <button type="button" id="rtbcb-export-pdf" class="button" style="display:none;">
            <?php esc_html_e( 'Download PDF', 'rtbcb' ); ?>
        </button>
    </p>
    <div id="rtbcb-report-sections"></div>
    <div id="rtbcb-report-container">
        <iframe id="rtbcb-test-report-frame"></iframe>
    </div>
</div>
