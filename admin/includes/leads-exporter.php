<?php
/**
 * Leads Exporter for Real Treasury Business Case Builder
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class RTBCB_Leads_Exporter
 * Handles exporting leads data to various formats
 */
class RTBCB_Leads_Exporter {
    
    /**
     * Export leads to CSV format.
     *
     * @param array $args Export arguments.
     * @return void
     */
    public function export_to_csv( $args = [] ) {
        // Get all leads with filters applied
        $leads_data = RTBCB_Leads::get_all_leads( array_merge( $args, [ 'per_page' => -1 ] ) );
        $leads = $leads_data['leads'] ?? [];

        if ( empty( $leads ) ) {
            wp_die( esc_html__( 'No leads found to export.', 'rtbcb' ) );
        }

        // Set headers for CSV download
        $filename = 'rtbcb-leads-' . date( 'Y-m-d-H-i-s' ) . '.csv';
        
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        // Create file pointer for output
        $output = fopen( 'php://output', 'w' );

        // Add BOM for UTF-8
        fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );

        // CSV Headers
        $headers = [
            __( 'ID', 'rtbcb' ),
            __( 'Email', 'rtbcb' ),
            __( 'Company Size', 'rtbcb' ),
            __( 'Industry', 'rtbcb' ),
            __( 'Hours Reconciliation', 'rtbcb' ),
            __( 'Hours Cash Positioning', 'rtbcb' ),
            __( 'Number of Banks', 'rtbcb' ),
            __( 'FTEs', 'rtbcb' ),
            __( 'Pain Points', 'rtbcb' ),
            __( 'Recommended Category', 'rtbcb' ),
            __( 'ROI Low', 'rtbcb' ),
            __( 'ROI Base', 'rtbcb' ),
            __( 'ROI High', 'rtbcb' ),
            __( 'Status', 'rtbcb' ),
            __( 'Created Date', 'rtbcb' ),
            __( 'UTM Source', 'rtbcb' ),
            __( 'UTM Medium', 'rtbcb' ),
            __( 'UTM Campaign', 'rtbcb' ),
            __( 'IP Address', 'rtbcb' ),
            __( 'User Agent', 'rtbcb' ),
        ];

        fputcsv( $output, $headers );

        // Data rows
        foreach ( $leads as $lead ) {
            $pain_points = '';
            if ( is_array( $lead['pain_points'] ) && ! empty( $lead['pain_points'] ) ) {
                $pain_points = implode( '; ', $lead['pain_points'] );
            }

            $row = [
                $lead['id'],
                $lead['email'],
                $lead['company_size'] ?? '',
                $lead['industry'] ?? '',
                $lead['hours_reconciliation'] ?? 0,
                $lead['hours_cash_positioning'] ?? 0,
                $lead['num_banks'] ?? 0,
                $lead['ftes'] ?? 0,
                $pain_points,
                $lead['recommended_category'] ?? '',
                $lead['roi_low'] ?? 0,
                $lead['roi_base'] ?? 0,
                $lead['roi_high'] ?? 0,
                $lead['status'] ?? 'new',
                $lead['created_at'] ?? '',
                $lead['utm_source'] ?? '',
                $lead['utm_medium'] ?? '',
                $lead['utm_campaign'] ?? '',
                $lead['ip_address'] ?? '',
                $this->truncate_user_agent( $lead['user_agent'] ?? '' ),
            ];

            fputcsv( $output, $row );
        }

        fclose( $output );
        exit;
    }

    /**
     * Export leads to Excel format (XLSX).
     *
     * @param array $args Export arguments.
     * @return void
     */
    public function export_to_excel( $args = [] ) {
        // For now, fallback to CSV since Excel requires additional libraries
        // TODO: Implement XLSX export using PhpSpreadsheet if needed
        $this->export_to_csv( $args );
    }

    /**
     * Generate leads report in JSON format.
     *
     * @param array $args Export arguments.
     * @return string JSON data.
     */
    public function export_to_json( $args = [] ) {
        $leads_data = RTBCB_Leads::get_all_leads( array_merge( $args, [ 'per_page' => -1 ] ) );
        $leads = $leads_data['leads'] ?? [];

        $export_data = [
            'export_date' => current_time( 'mysql' ),
            'total_leads' => count( $leads ),
            'filters_applied' => $this->get_applied_filters( $args ),
            'leads' => $leads,
        ];

        return wp_json_encode( $export_data, JSON_PRETTY_PRINT );
    }

    /**
     * Get summary of applied filters.
     *
     * @param array $args Filter arguments.
     * @return array Filter summary.
     */
    private function get_applied_filters( $args ) {
        $filters = [];

        if ( ! empty( $args['search'] ) ) {
            $filters['search'] = $args['search'];
        }

        if ( ! empty( $args['status'] ) ) {
            $filters['status'] = $args['status'];
        }

        if ( ! empty( $args['industry'] ) ) {
            $filters['industry'] = $args['industry'];
        }

        if ( ! empty( $args['company_size'] ) ) {
            $filters['company_size'] = $args['company_size'];
        }

        if ( ! empty( $args['date_from'] ) ) {
            $filters['date_from'] = $args['date_from'];
        }

        if ( ! empty( $args['date_to'] ) ) {
            $filters['date_to'] = $args['date_to'];
        }

        return $filters;
    }

    /**
     * Truncate user agent string for better CSV readability.
     *
     * @param string $user_agent User agent string.
     * @return string Truncated user agent.
     */
    private function truncate_user_agent( $user_agent ) {
        if ( strlen( $user_agent ) > 100 ) {
            return substr( $user_agent, 0, 97 ) . '...';
        }
        return $user_agent;
    }

    /**
     * Generate export statistics.
     *
     * @param array $leads Leads data.
     * @return array Export statistics.
     */
    public function get_export_stats( $leads ) {
        $stats = [
            'total_leads' => count( $leads ),
            'by_status' => [],
            'by_industry' => [],
            'by_company_size' => [],
            'roi_stats' => [
                'average' => 0,
                'min' => 0,
                'max' => 0,
            ],
            'date_range' => [
                'earliest' => null,
                'latest' => null,
            ],
        ];

        if ( empty( $leads ) ) {
            return $stats;
        }

        $roi_values = [];
        $dates = [];

        foreach ( $leads as $lead ) {
            // Status stats
            $status = $lead['status'] ?? 'new';
            $stats['by_status'][ $status ] = ( $stats['by_status'][ $status ] ?? 0 ) + 1;

            // Industry stats
            $industry = $lead['industry'] ?? 'Unknown';
            $stats['by_industry'][ $industry ] = ( $stats['by_industry'][ $industry ] ?? 0 ) + 1;

            // Company size stats
            $size = $lead['company_size'] ?? 'Unknown';
            $stats['by_company_size'][ $size ] = ( $stats['by_company_size'][ $size ] ?? 0 ) + 1;

            // ROI stats
            if ( ! empty( $lead['roi_base'] ) && is_numeric( $lead['roi_base'] ) ) {
                $roi_values[] = floatval( $lead['roi_base'] );
            }

            // Date stats
            if ( ! empty( $lead['created_at'] ) ) {
                $dates[] = $lead['created_at'];
            }
        }

        // Calculate ROI statistics
        if ( ! empty( $roi_values ) ) {
            $stats['roi_stats']['average'] = round( array_sum( $roi_values ) / count( $roi_values ), 2 );
            $stats['roi_stats']['min'] = min( $roi_values );
            $stats['roi_stats']['max'] = max( $roi_values );
        }

        // Calculate date range
        if ( ! empty( $dates ) ) {
            sort( $dates );
            $stats['date_range']['earliest'] = reset( $dates );
            $stats['date_range']['latest'] = end( $dates );
        }

        return $stats;
    }
}