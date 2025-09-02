<?php
/**
 * Reports management page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Generated Reports', 'rtbcb' ); ?></h1>
    <form method="post" class="rtbcb-delete-old-reports">
        <?php wp_nonce_field( 'rtbcb_delete_old_reports' ); ?>
        <label for="rtbcb-delete-days"><?php esc_html_e( 'Delete reports older than (days):', 'rtbcb' ); ?></label>
        <input type="number" name="rtbcb_delete_days" id="rtbcb-delete-days" value="30" min="1" />
        <?php submit_button( __( 'Delete Old Reports', 'rtbcb' ), 'secondary', 'rtbcb_delete_old_reports', false ); ?>
    </form>

    <form method="post">
        <?php wp_nonce_field( 'rtbcb_reports_action' ); ?>
        <input type="hidden" name="page" value="rtbcb-reports" />
        <?php $table->display(); ?>
    </form>
</div>
