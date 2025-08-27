<?php
/**
 * Dashboard recent test results.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$max_results  = 10;
$recent_results = [];
if ( ! empty( $test_results ) && is_array( $test_results ) ) {
    $recent_results = array_slice( $test_results, 0, $max_results );
}

$sections     = rtbcb_get_dashboard_sections();
?>

<div id="rtbcb-tests-complete-toast" class="notice notice-success is-dismissible" style="display:none;">
    <p><?php esc_html_e( 'All tests completed.', 'rtbcb' ); ?></p>
</div>

<h2 class="title"><?php esc_html_e( 'Recent Test Results', 'rtbcb' ); ?></h2>
<table class="widefat striped" id="rtbcb-test-results-summary">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Section', 'rtbcb' ); ?></th>
            <th><?php esc_html_e( 'Status', 'rtbcb' ); ?></th>
            <th><?php esc_html_e( 'Message', 'rtbcb' ); ?></th>
            <th><?php esc_html_e( 'Timestamp', 'rtbcb' ); ?></th>
            <th><?php esc_html_e( 'Actions', 'rtbcb' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if ( ! empty( $recent_results ) ) : ?>
        <?php foreach ( $recent_results as $result ) : ?>
            <?php
            $section_id    = isset( $result['section'] ) ? sanitize_text_field( $result['section'] ) : '';
            $section_label = isset( $sections[ $section_id ]['label'] ) ? $sections[ $section_id ]['label'] : $section_id;
            ?>
            <tr>
                <td><?php echo esc_html( $section_label ); ?></td>
                <td><?php echo esc_html( $result['status'] ); ?></td>
                <td><?php echo esc_html( $result['message'] ); ?></td>
                <td><?php echo esc_html( $result['timestamp'] ); ?></td>
                <td>
                    <?php if ( $section_id ) : ?>
                        <a href="#<?php echo esc_attr( $section_id ); ?>" class="rtbcb-jump-tab"><?php esc_html_e( 'View', 'rtbcb' ); ?></a>
                        |
                        <a href="#" class="rtbcb-rerun-test" data-section="<?php echo esc_attr( $section_id ); ?>"><?php esc_html_e( 'Re-run', 'rtbcb' ); ?></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else : ?>
        <tr>
            <td colspan="5"><?php esc_html_e( 'No test results found.', 'rtbcb' ); ?></td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
<script>
(function($){
    $('#rtbcb-test-results-summary').on('click', '.rtbcb-jump-tab', function(e){
        e.preventDefault();
        var target = $(this).attr('href');
        $('#rtbcb-test-tabs a[href="' + target + '"]').trigger('click');
    });
    $('#rtbcb-test-results-summary').on('click', '.rtbcb-rerun-test', function(e){
        e.preventDefault();
        var section = $(this).data('section');
        $('#rtbcb-test-tabs a[href="#' + section + '"]').trigger('click');
        $('#' + section).find('form').first().trigger('submit');
    });
    $(document).on('rtbcb-tests-complete', function(){
        var toast = $('#rtbcb-tests-complete-toast');
        toast.fadeIn();
        setTimeout(function(){
            toast.fadeOut();
        }, 5000);
    });
})(jQuery);
</script>
