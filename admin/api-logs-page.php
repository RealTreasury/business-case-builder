<?php
defined( 'ABSPATH' ) || exit;

/**
 * API logs admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

?>
<div class="wrap">
	<h1><?php echo esc_html__( 'API Logs', 'rtbcb' ); ?></h1>
	<p>
		<button id="rtbcb-clear-logs" class="button" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<?php esc_html_e( 'Clear All Logs', 'rtbcb' ); ?>
		</button>
	</p>
       <p><?php esc_html_e( 'Logs include prompt, completion, and total token usage.', 'rtbcb' ); ?></p>
       <div class="rtbcb-log-table-wrapper">
<table id="rtbcb-api-logs-table" class="widefat fixed striped">
                        <thead>
                                <tr>
                                       <th><?php esc_html_e( 'ID', 'rtbcb' ); ?></th>
                                       <th><?php esc_html_e( 'Lead ID', 'rtbcb' ); ?></th>
                                       <th><?php esc_html_e( 'Email', 'rtbcb' ); ?></th>
                                       <th><?php esc_html_e( 'Company Name', 'rtbcb' ); ?></th>
                                       <th><?php esc_html_e( 'Model', 'rtbcb' ); ?></th>
                                       <th><?php esc_html_e( 'Request', 'rtbcb' ); ?></th>
                                       <th><?php esc_html_e( 'Prompt Tokens', 'rtbcb' ); ?></th>
                                       <th><?php esc_html_e( 'Completion Tokens', 'rtbcb' ); ?></th>
                                       <th><?php esc_html_e( 'Tokens', 'rtbcb' ); ?></th>
                                       <th><?php esc_html_e( 'Status', 'rtbcb' ); ?></th>
                                       <th><?php esc_html_e( 'Timestamp', 'rtbcb' ); ?></th>
                                       <th><?php esc_html_e( 'Actions', 'rtbcb' ); ?></th>
                                </tr>
                        </thead>
                        <tbody>
<?php if ( ! empty( $logs ) ) : ?>
<?php foreach ( $logs as $log ) :
						$request  = json_decode( $log['request_json'], true );
						$response = json_decode( $log['response_json'], true );
						$summary  = '';
						if ( isset( $request['messages'][0]['content'] ) ) {
							$summary = wp_trim_words( $request['messages'][0]['content'], 10, '...' );
						} else {
							$summary = wp_trim_words( $log['request_json'], 10, '...' );
						}
                                               $status = isset( $response['error'] ) ? __( 'Error', 'rtbcb' ) : __( 'OK', 'rtbcb' );
                                               if ( ! empty( $log['corruption_detected'] ) ) {
                                                       $status .= ' (' . __( 'Corrupt', 'rtbcb' ) . ')';
                                               } elseif ( ! empty( $log['is_truncated'] ) ) {
                                                       $status .= ' (' . __( 'Truncated', 'rtbcb' ) . ')';
                                               }
					?>
					<tr data-id="<?php echo esc_attr( $log['id'] ); ?>" data-request="<?php echo esc_attr( $log['request_json'] ); ?>" data-response="<?php echo esc_attr( $log['response_json'] ); ?>">
	                                       <td><?php echo esc_html( $log['id'] ); ?></td>
	                                       <td><?php echo esc_html( $log['lead_id'] ); ?></td>
	                                       <td><?php echo esc_html( $log['user_email'] ); ?></td>
                                               <td><?php echo esc_html( $log['company_name'] ); ?></td>
                                               <td><?php echo esc_html( $log['llm_model'] ); ?></td>
                                               <td><?php echo esc_html( $summary ); ?></td>
                                               <td><?php echo esc_html( $log['prompt_tokens'] ); ?></td>
                                               <td><?php echo esc_html( $log['completion_tokens'] ); ?></td>
                                               <td><?php echo esc_html( $log['total_tokens'] ); ?></td>
                                               <td><?php echo esc_html( $status ); ?></td>
                                               <td><?php echo esc_html( $log['created_at'] ); ?></td>
                                               <td>
							<button class="button rtbcb-view-log">
								<?php esc_html_e( 'View', 'rtbcb' ); ?>
							</button>
							<button class="button rtbcb-delete-log" data-id="<?php echo esc_attr( $log['id'] ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
								<?php esc_html_e( 'Delete', 'rtbcb' ); ?>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
<div id="rtbcb-log-modal" style="display:none;">
        <div class="rtbcb-modal-content">
                <pre id="rtbcb-log-detail"></pre>
                <button class="button" id="rtbcb-copy-log"><?php esc_html_e( 'Copy', 'rtbcb' ); ?></button>
                <button class="button" id="rtbcb-close-log"><?php esc_html_e( 'Close', 'rtbcb' ); ?></button>
        </div>
</div>
<script type="text/javascript">
        jQuery(function($){
        var table = $('#rtbcb-api-logs-table').DataTable({
        pageLength: 20,
        order: [[0, 'desc']],
        scrollX: true,
        autoWidth: false,
        language: {
        emptyTable: '<?php echo esc_js( __( 'No logs found.', 'rtbcb' ) ); ?>'
        },
        columns: [
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        { orderable: false }
        ]
        });
var search = new URLSearchParams(window.location.search).get('search');
if (search) {
table.search(search).draw();
}
	$('#rtbcb-clear-logs').on('click', function(e){
			e.preventDefault();
			if (!confirm('<?php echo esc_js( __( 'Are you sure you want to clear all logs?', 'rtbcb' ) ); ?>')) {
				return;
			}
			$.post(window.rtbcbAdmin.ajax_url, {
				action: 'rtbcb_clear_logs',
				nonce: $(this).data('nonce')
			}, function(resp){
				if (resp.success) {
					location.reload();
				} else {
									alert(resp.data && resp.data.message ? resp.data.message : '<?php echo esc_js( __( 'Error', 'rtbcb' ) ); ?>');
				}
			});
		});
	$('#rtbcb-api-logs-table').on('click', '.rtbcb-delete-log', function(e){
			e.preventDefault();
			if (!confirm('<?php echo esc_js( __( 'Delete this log?', 'rtbcb' ) ); ?>')) {
				return;
			}
			var id = $(this).data('id');
			var nonce = $(this).data('nonce');
			$.post(window.rtbcbAdmin.ajax_url, {
				action: 'rtbcb_delete_log',
				nonce: nonce,
				id: id
			}, function(resp){
	if (resp.success) {
	table.row( $('tr[data-id="' + id + '"]') ).remove().draw();
	} else {
									alert(resp.data && resp.data.message ? resp.data.message : '<?php echo esc_js( __( 'Error', 'rtbcb' ) ); ?>');
				}
			});
		});
	$('#rtbcb-api-logs-table').on('click', '.rtbcb-view-log', function(e){
	e.preventDefault();
	var $row = $(this).closest('tr');
	var req = $row.attr('data-request');
	var res = $row.attr('data-response');
	try { req = JSON.stringify(JSON.parse(req), null, 2); } catch (err) {}
	try { res = JSON.stringify(JSON.parse(res), null, 2); } catch (err) {}
	var content = '<?php echo esc_js( __( 'Request:', 'rtbcb' ) ); ?>\n' + req + '\n\n<?php echo esc_js( __( 'Response:', 'rtbcb' ) ); ?>\n' + res;
	$('#rtbcb-log-detail').text(content);
	$('#rtbcb-log-modal').show();
	});
	$('#rtbcb-copy-log').on('click', function(){
	var text = $('#rtbcb-log-detail').text();
	navigator.clipboard.writeText(text).then(function(){
	alert('<?php echo esc_js( __( 'Copied to clipboard.', 'rtbcb' ) ); ?>');
	}).catch(function(){
	alert('<?php echo esc_js( __( 'Copy failed.', 'rtbcb' ) ); ?>');
	});
	});
	$('#rtbcb-close-log').on('click', function(){
	$('#rtbcb-log-modal').hide();
	});
	});
</script>

