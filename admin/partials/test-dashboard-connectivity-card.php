<?php
/**
 * Connectivity status card for test dashboard.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$company_data = get_option( 'rtbcb_company_data', [] );
$company_name = isset( $company_data['name'] ) ? sanitize_text_field( $company_data['name'] ) : '';
?>
<div class="card">
    <h2 class="title"><?php esc_html_e( 'Connectivity Tests & Status', 'rtbcb' ); ?></h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Check', 'rtbcb' ); ?></th>
                <th><?php esc_html_e( 'Status', 'rtbcb' ); ?></th>
                <th><?php esc_html_e( 'Indexed Items', 'rtbcb' ); ?></th>
                <th><?php esc_html_e( 'Last Updated', 'rtbcb' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th><?php esc_html_e( 'OpenAI API Key', 'rtbcb' ); ?></th>
                <td><?php echo $openai_status ? esc_html__( 'Configured', 'rtbcb' ) : esc_html__( 'Missing', 'rtbcb' ); ?></td>
                <td><?php esc_html_e( 'N/A', 'rtbcb' ); ?></td>
                <td><?php esc_html_e( 'N/A', 'rtbcb' ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Portal Integration', 'rtbcb' ); ?></th>
                <td><?php echo $portal_active ? esc_html__( 'Active', 'rtbcb' ) : esc_html__( 'Inactive', 'rtbcb' ); ?></td>
                <td><?php esc_html_e( 'N/A', 'rtbcb' ); ?></td>
                <td><?php esc_html_e( 'N/A', 'rtbcb' ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'RAG Index', 'rtbcb' ); ?></th>
                <td><?php echo $rag_is_healthy ? esc_html__( 'Healthy', 'rtbcb' ) : esc_html__( 'Needs attention', 'rtbcb' ); ?></td>
                <td><?php echo isset( $rag_health['indexed_items'] ) ? esc_html( number_format_i18n( $rag_health['indexed_items'] ) ) : esc_html( '0' ); ?></td>
                <td>
                    <?php
                    echo ! empty( $rag_health['last_updated'] )
                        ? esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $rag_health['last_updated'] ) )
                        : esc_html__( 'Never', 'rtbcb' );
                    ?>
                </td>
            </tr>
        </tbody>
    </table>
    <p class="submit">
        <button
            type="button"
            id="rtbcb-test-openai"
            class="button"
            data-nonce="<?php echo esc_attr( wp_create_nonce( 'rtbcb_test_api' ) ); ?>"
            data-success="<?php echo esc_attr__( 'Connection successful.', 'rtbcb' ); ?>"
            data-failure="<?php echo esc_attr__( 'Connection failed.', 'rtbcb' ); ?>"
            data-request-failed="<?php echo esc_attr__( 'Request failed.', 'rtbcb' ); ?>"
        ><?php esc_html_e( 'Test OpenAI API', 'rtbcb' ); ?></button>
        <button
            type="button"
            id="rtbcb-test-portal"
            class="button"
            data-nonce="<?php echo esc_attr( wp_create_nonce( 'rtbcb_test_portal' ) ); ?>"
            data-success="<?php echo esc_attr__( 'Portal test successful.', 'rtbcb' ); ?>"
            data-failure="<?php echo esc_attr__( 'Test failed.', 'rtbcb' ); ?>"
            data-request-failed="<?php echo esc_attr__( 'Request failed.', 'rtbcb' ); ?>"
            data-vendor-label="<?php echo esc_attr__( 'Vendor count:', 'rtbcb' ); ?>"
        ><?php esc_html_e( 'Test Portal Connection', 'rtbcb' ); ?></button>
        <button
            type="button"
            id="rtbcb-test-rag"
            class="button"
            data-nonce="<?php echo esc_attr( wp_create_nonce( 'rtbcb_test_rag' ) ); ?>"
            data-success="<?php echo esc_attr__( 'RAG index healthy.', 'rtbcb' ); ?>"
            data-failure="<?php echo esc_attr__( 'Test failed.', 'rtbcb' ); ?>"
            data-request-failed="<?php echo esc_attr__( 'Request failed.', 'rtbcb' ); ?>"
        ><?php esc_html_e( 'Test RAG Index', 'rtbcb' ); ?></button>
    </p>
    <p>
        <label for="rtbcb-company-name"><?php esc_html_e( 'Company Name', 'rtbcb' ); ?></label>
        <input type="text" id="rtbcb-company-name" class="regular-text" value="<?php echo esc_attr( $company_name ); ?>" />
        <button
            type="button"
            id="rtbcb-set-company"
            class="button"
            data-saving="<?php echo esc_attr__( 'Saving...', 'rtbcb' ); ?>"
            data-request-failed="<?php echo esc_attr__( 'Request failed.', 'rtbcb' ); ?>"
            data-no-results="<?php echo esc_attr__( 'No test results found.', 'rtbcb' ); ?>"
        ><?php esc_html_e( 'Set Company', 'rtbcb' ); ?></button>
        <?php wp_nonce_field( 'rtbcb_set_test_company', 'rtbcb_set_test_company_nonce' ); ?>
    </p>
    <p id="rtbcb-connectivity-status"></p>

    <?php include RTBCB_DIR . 'admin/partials/dashboard-test-results.php'; ?>
</div>
