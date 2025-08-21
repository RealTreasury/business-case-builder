<?php
/**
 * Leads admin page for Real Treasury Business Case Builder plugin.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$leads = [];
if ( class_exists( 'RTBCB_Leads' ) && method_exists( 'RTBCB_Leads', 'get_all_leads' ) ) {
    $leads = RTBCB_Leads::get_all_leads();
}
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Leads', 'rtbcb' ); ?></h1>
    <?php if ( ! empty( $leads ) && is_array( $leads ) ) : ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__( 'Name', 'rtbcb' ); ?></th>
                    <th><?php echo esc_html__( 'Email', 'rtbcb' ); ?></th>
                    <th><?php echo esc_html__( 'Company', 'rtbcb' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $leads as $lead ) : ?>
                    <tr>
                        <td><?php echo isset( $lead['name'] ) ? esc_html( $lead['name'] ) : ''; ?></td>
                        <td><?php echo isset( $lead['email'] ) ? esc_html( $lead['email'] ) : ''; ?></td>
                        <td><?php echo isset( $lead['company'] ) ? esc_html( $lead['company'] ) : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php echo esc_html__( 'No leads yet.', 'rtbcb' ); ?></p>
    <?php endif; ?>
</div>


