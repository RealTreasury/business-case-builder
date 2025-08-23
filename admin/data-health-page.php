<?php
/**
 * Data Health admin page for Real Treasury Business Case Builder plugin.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$portal_active  = (bool) ( has_filter( 'rt_portal_get_vendors' ) || has_filter( 'rt_portal_get_vendor_notes' ) );
$last_indexed   = get_option( 'rtbcb_last_indexed', '' );
$last_index_display = $last_indexed ? $last_indexed : __( 'Never', 'rtbcb' );
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Data Health', 'rtbcb' ); ?></h1>
    <p>
        <strong><?php echo esc_html__( 'Portal Integration:', 'rtbcb' ); ?></strong>
        <?php echo $portal_active ? esc_html__( 'Active', 'rtbcb' ) : esc_html__( 'Inactive', 'rtbcb' ); ?>
    </p>
    <p>
        <strong><?php echo esc_html__( 'Last RAG Index:', 'rtbcb' ); ?></strong>
        <?php echo esc_html( $last_index_display ); ?>
    </p>
    <p><?php echo esc_html__( 'Need more details? Run diagnostics from the settings page.', 'rtbcb' ); ?></p>
    <form id="rtbcb-sync-local-form">
        <?php wp_nonce_field( 'rtbcb_sync_local', 'rtbcb_sync_local_nonce' ); ?>
        <button type="button" class="button" id="rtbcb-sync-local">
            <?php echo esc_html__( 'Sync to Local', 'rtbcb' ); ?>
        </button>
    </form>
</div>


