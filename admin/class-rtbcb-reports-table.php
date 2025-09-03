<?php
/**
 * Reports list table.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table for generated reports.
 */
class RTBCB_Reports_Table extends WP_List_Table {
    /**
     * Reports directory path.
     *
     * @var string
     */
    protected $reports_dir = '';

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            [
                'singular' => 'rtbcb_report',
                'plural'   => 'rtbcb_reports',
                'ajax'     => false,
            ]
        );
    }

    /**
     * Retrieve columns.
     *
     * @return array
     */
    public function get_columns() {
        return [
            'cb'       => '<input type="checkbox" />',
            'file'     => __( 'File', 'rtbcb' ),
            'size'     => __( 'Size', 'rtbcb' ),
            'modified' => __( 'Modified', 'rtbcb' ),
        ];
    }

    /**
     * Define sortable columns.
     *
     * @return array
     */
    protected function get_sortable_columns() {
        return [
            'file'     => [ 'file', true ],
            'size'     => [ 'size', false ],
            'modified' => [ 'modified', false ],
        ];
    }

    /**
     * Checkbox column.
     *
     * @param array $item Row item.
     * @return string
     */
    protected function column_cb( $item ) {
        return '<input type="checkbox" name="files[]" value="' . esc_attr( $item['file'] ) . '" />';
    }

    /**
     * File column.
     *
     * @param array $item Row item.
     * @return string
     */
    protected function column_file( $item ) {
        return '<a href="' . esc_url( $item['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $item['file'] ) . '</a>';
    }

    /**
     * Size column.
     *
     * @param array $item Row item.
     * @return string
     */
    protected function column_size( $item ) {
        return esc_html( size_format( $item['size'], 2 ) );
    }

    /**
     * Modified column.
     *
     * @param array $item Row item.
     * @return string
     */
    protected function column_modified( $item ) {
        return esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item['modified'] ) );
    }

    /**
     * Bulk actions.
     *
     * @return array
     */
    protected function get_bulk_actions() {
        return [
            'delete'     => __( 'Delete', 'rtbcb' ),
            'delete_all' => __( 'Delete All', 'rtbcb' ),
        ];
    }

    /**
     * Display message when no items.
     *
     * @return void
     */
    public function no_items() {
        esc_html_e( 'No reports found.', 'rtbcb' );
    }

    /**
     * Prepare list items.
     *
     * @param string $orderby Column to order by.
     * @param string $order   Sort direction.
     * @return void
     */
    public function prepare_items( $orderby = 'file', $order = 'asc' ) {
        $upload_dir        = wp_upload_dir();
        $this->reports_dir = trailingslashit( $upload_dir['basedir'] ) . 'rtbcb-reports';

        $this->process_bulk_action();

        $files = glob( $this->reports_dir . '/*.{html,pdf}', GLOB_BRACE );
        $files = $files ? $files : [];

        $items = [];
        foreach ( $files as $filepath ) {
            $filename = basename( $filepath );
            $items[]  = [
                'file'     => $filename,
                'size'     => filesize( $filepath ),
                'modified' => filemtime( $filepath ),
                'url'      => trailingslashit( $upload_dir['baseurl'] ) . 'rtbcb-reports/' . $filename,
            ];
        }

        $sortable = $this->get_sortable_columns();
        $orderby  = array_key_exists( $orderby, $sortable ) ? $orderby : 'file';
        $order    = 'desc' === strtolower( $order ) ? 'desc' : 'asc';

        usort(
            $items,
            function ( $a, $b ) use ( $orderby, $order ) {
                if ( $a[ $orderby ] === $b[ $orderby ] ) {
                    return 0;
                }
                $result = ( $a[ $orderby ] < $b[ $orderby ] ) ? -1 : 1;
                return 'asc' === $order ? $result : -$result;
            }
        );

        $per_page     = $this->get_items_per_page( 'rtbcb_reports_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items  = count( $items );

        $this->items = array_slice( $items, ( $current_page - 1 ) * $per_page, $per_page );

        $this->_column_headers = [ $this->get_columns(), [], $sortable ];

        $this->set_pagination_args(
            [
                'total_items' => $total_items,
                'per_page'    => $per_page,
            ]
        );
    }

    /**
     * Handle bulk actions.
     *
     * @return void
     */
    public function process_bulk_action() {
        $action = $this->current_action();

        if ( 'delete' !== $action && 'delete_all' !== $action ) {
            return;
        }

        check_admin_referer( 'rtbcb_reports_action' );

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( 'delete_all' === $action ) {
            $files = glob( trailingslashit( $this->reports_dir ) . '*.{html,pdf}', GLOB_BRACE );
            $files = $files ? $files : [];

            foreach ( $files as $file_path ) {
                if ( file_exists( $file_path ) ) {
                    unlink( $file_path );
                }
            }

            rtbcb_clear_report_cache();
            return;
        }

        $files = isset( $_POST['files'] ) ? (array) wp_unslash( $_POST['files'] ) : [];
        $files = array_map( 'sanitize_file_name', $files );

        foreach ( $files as $file ) {
            $file_path = trailingslashit( $this->reports_dir ) . $file;
            if ( file_exists( $file_path ) ) {
                unlink( $file_path );
            }
        }

        if ( ! empty( $files ) ) {
            rtbcb_clear_report_cache();
        }
    }
}
