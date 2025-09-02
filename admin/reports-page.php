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

       <form method="post" class="rtbcb-delete-all-reports">
               <?php wp_nonce_field( 'rtbcb_delete_all_reports' ); ?>
               <?php submit_button( __( 'Delete All Reports', 'rtbcb' ), 'delete', 'rtbcb_delete_all_reports', false ); ?>
       </form>


       <?php if ( empty( $report_files ) ) : ?>
		<p><?php esc_html_e( 'No reports found.', 'rtbcb' ); ?></p>
	<?php else : ?>
		<table class="widefat fixed">
			<thead>
				<tr>
					<th><?php esc_html_e( 'File', 'rtbcb' ); ?></th>
					<th><?php esc_html_e( 'Size', 'rtbcb' ); ?></th>
					<th><?php esc_html_e( 'Modified', 'rtbcb' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'rtbcb' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $report_files as $file ) : ?>
				<tr>
					<td><a href="<?php echo esc_url( $file['url'] ); ?>" target="_blank"><?php echo esc_html( $file['name'] ); ?></a></td>
					<td><?php echo esc_html( $file['size'] ); ?></td>
					<td><?php echo esc_html( $file['modified'] ); ?></td>
					<td><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rtbcb-reports&delete=' . urlencode( $file['name'] ) ), 'rtbcb_delete_report_' . $file['name'] ) ); ?>" class="submitdelete"><?php esc_html_e( 'Delete', 'rtbcb' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

