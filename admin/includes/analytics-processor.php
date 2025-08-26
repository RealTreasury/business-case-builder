<?php
/**
 * Analytics Processor for Real Treasury Business Case Builder
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class RTBCB_Analytics_Processor
 * Processes analytics data for charts and reports
 */
class RTBCB_Analytics_Processor {
    
    /**
     * Get chart data based on type and date range.
     *
     * @param string $chart_type Type of chart (leads_over_time, roi_distribution, etc.).
     * @param string $date_range Date range in days (7, 30, 90, 365).
     * @return array Chart data formatted for Chart.js.
     */
    public function get_chart_data( $chart_type, $date_range = '30' ) {
        $days = intval( $date_range );
        
        switch ( $chart_type ) {
            case 'leads_over_time':
                return $this->get_leads_over_time_data( $days );
            case 'roi_distribution':
                return $this->get_roi_distribution_data( $days );
            case 'industry_breakdown':
                return $this->get_industry_breakdown_data( $days );
            case 'company_size_breakdown':
                return $this->get_company_size_breakdown_data( $days );
            case 'status_breakdown':
                return $this->get_status_breakdown_data( $days );
            case 'conversion_funnel':
                return $this->get_conversion_funnel_data( $days );
            case 'utm_sources':
                return $this->get_utm_sources_data( $days );
            default:
                return $this->get_default_chart_data();
        }
    }

    /**
     * Get leads over time chart data.
     *
     * @param int $days Number of days to look back.
     * @return array Chart data.
     */
    private function get_leads_over_time_data( $days ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        
        // Get daily lead counts
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM {$table_name} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $days
        ), ARRAY_A );

        // Generate complete date range
        $dates = [];
        $counts = [];
        
        for ( $i = $days - 1; $i >= 0; $i-- ) {
            $date = date( 'Y-m-d', strtotime( "-{$i} days" ) );
            $dates[] = date( 'M j', strtotime( $date ) );
            
            // Find count for this date
            $count = 0;
            foreach ( $results as $result ) {
                if ( $result['date'] === $date ) {
                    $count = intval( $result['count'] );
                    break;
                }
            }
            $counts[] = $count;
        }

        return [
            'type' => 'line',
            'data' => [
                'labels' => $dates,
                'datasets' => [
                    [
                        'label' => __( 'New Leads', 'rtbcb' ),
                        'data' => $counts,
                        'borderColor' => '#3b82f6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => true,
                        'tension' => 0.4,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'stepSize' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get ROI distribution chart data.
     *
     * @param int $days Number of days to look back.
     * @return array Chart data.
     */
    private function get_roi_distribution_data( $days ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT roi_base FROM {$table_name} 
             WHERE roi_base > 0 
             AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ), ARRAY_A );

        // Create ROI ranges
        $ranges = [
            '0-10K' => 0,
            '10K-50K' => 0,
            '50K-100K' => 0,
            '100K-500K' => 0,
            '500K+' => 0,
        ];

        foreach ( $results as $result ) {
            $roi = floatval( $result['roi_base'] );
            
            if ( $roi < 10000 ) {
                $ranges['0-10K']++;
            } elseif ( $roi < 50000 ) {
                $ranges['10K-50K']++;
            } elseif ( $roi < 100000 ) {
                $ranges['50K-100K']++;
            } elseif ( $roi < 500000 ) {
                $ranges['100K-500K']++;
            } else {
                $ranges['500K+']++;
            }
        }

        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => array_keys( $ranges ),
                'datasets' => [
                    [
                        'data' => array_values( $ranges ),
                        'backgroundColor' => [
                            '#ef4444',
                            '#f59e0b',
                            '#10b981',
                            '#3b82f6',
                            '#8b5cf6',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get industry breakdown chart data.
     *
     * @param int $days Number of days to look back.
     * @return array Chart data.
     */
    private function get_industry_breakdown_data( $days ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT industry, COUNT(*) as count 
             FROM {$table_name} 
             WHERE industry != '' 
             AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY industry 
             ORDER BY count DESC",
            $days
        ), ARRAY_A );

        $labels = [];
        $data = [];
        $colors = [ '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16' ];
        
        foreach ( $results as $index => $result ) {
            $labels[] = $result['industry'];
            $data[] = intval( $result['count'] );
        }

        return [
            'type' => 'pie',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'data' => $data,
                        'backgroundColor' => array_slice( $colors, 0, count( $data ) ),
                    ],
                ],
            ],
        ];
    }

    /**
     * Get company size breakdown chart data.
     *
     * @param int $days Number of days to look back.
     * @return array Chart data.
     */
    private function get_company_size_breakdown_data( $days ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT company_size, COUNT(*) as count 
             FROM {$table_name} 
             WHERE company_size != '' 
             AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY company_size 
             ORDER BY FIELD(company_size, 'Small (1-50)', 'Medium (51-200)', 'Large (201-1000)', 'Enterprise (1000+)')",
            $days
        ), ARRAY_A );

        $labels = [];
        $data = [];
        
        foreach ( $results as $result ) {
            $labels[] = $result['company_size'];
            $data[] = intval( $result['count'] );
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => __( 'Leads by Company Size', 'rtbcb' ),
                        'data' => $data,
                        'backgroundColor' => '#3b82f6',
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'stepSize' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get status breakdown chart data.
     *
     * @param int $days Number of days to look back.
     * @return array Chart data.
     */
    private function get_status_breakdown_data( $days ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT COALESCE(status, 'new') as status, COUNT(*) as count 
             FROM {$table_name} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY status 
             ORDER BY FIELD(status, 'new', 'contacted', 'qualified', 'converted', 'lost')",
            $days
        ), ARRAY_A );

        $labels = [];
        $data = [];
        $colors = [
            'new' => '#3b82f6',
            'contacted' => '#f59e0b',
            'qualified' => '#10b981',
            'converted' => '#059669',
            'lost' => '#ef4444',
        ];
        $background_colors = [];
        
        foreach ( $results as $result ) {
            $status = $result['status'];
            $labels[] = ucfirst( $status );
            $data[] = intval( $result['count'] );
            $background_colors[] = $colors[ $status ] ?? '#6b7280';
        }

        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'data' => $data,
                        'backgroundColor' => $background_colors,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get conversion funnel chart data.
     *
     * @param int $days Number of days to look back.
     * @return array Chart data.
     */
    private function get_conversion_funnel_data( $days ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        
        $totals = $wpdb->get_row( $wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('contacted', 'qualified', 'converted') THEN 1 ELSE 0 END) as contacted,
                SUM(CASE WHEN status IN ('qualified', 'converted') THEN 1 ELSE 0 END) as qualified,
                SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted
             FROM {$table_name} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ), ARRAY_A );

        $data = [
            intval( $totals['total'] ?? 0 ),
            intval( $totals['contacted'] ?? 0 ),
            intval( $totals['qualified'] ?? 0 ),
            intval( $totals['converted'] ?? 0 ),
        ];

        return [
            'type' => 'bar',
            'data' => [
                'labels' => [
                    __( 'Total Leads', 'rtbcb' ),
                    __( 'Contacted', 'rtbcb' ),
                    __( 'Qualified', 'rtbcb' ),
                    __( 'Converted', 'rtbcb' ),
                ],
                'datasets' => [
                    [
                        'label' => __( 'Conversion Funnel', 'rtbcb' ),
                        'data' => $data,
                        'backgroundColor' => [
                            '#3b82f6',
                            '#f59e0b',
                            '#10b981',
                            '#059669',
                        ],
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'stepSize' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get UTM sources chart data.
     *
     * @param int $days Number of days to look back.
     * @return array Chart data.
     */
    private function get_utm_sources_data( $days ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                COALESCE(NULLIF(utm_source, ''), 'Direct') as source, 
                COUNT(*) as count 
             FROM {$table_name} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY source 
             ORDER BY count DESC 
             LIMIT 10",
            $days
        ), ARRAY_A );

        $labels = [];
        $data = [];
        $colors = [ '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1' ];
        
        foreach ( $results as $index => $result ) {
            $labels[] = $result['source'];
            $data[] = intval( $result['count'] );
        }

        return [
            'type' => 'horizontalBar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => __( 'Leads by Source', 'rtbcb' ),
                        'data' => $data,
                        'backgroundColor' => array_slice( $colors, 0, count( $data ) ),
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'scales' => [
                    'x' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'stepSize' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get default chart data when no specific type is requested.
     *
     * @return array Default chart data.
     */
    private function get_default_chart_data() {
        return [
            'type' => 'line',
            'data' => [
                'labels' => [],
                'datasets' => [],
            ],
            'options' => [
                'responsive' => true,
            ],
        ];
    }

    /**
     * Get analytics summary data.
     *
     * @param int $days Number of days to look back.
     * @return array Summary statistics.
     */
    public function get_analytics_summary( $days = 30 ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        
        $summary = $wpdb->get_row( $wpdb->prepare(
            "SELECT 
                COUNT(*) as total_leads,
                AVG(roi_base) as avg_roi,
                MIN(roi_base) as min_roi,
                MAX(roi_base) as max_roi,
                SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_leads,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as todays_leads
             FROM {$table_name} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ), ARRAY_A );

        $total_leads = intval( $summary['total_leads'] ?? 0 );
        $converted_leads = intval( $summary['converted_leads'] ?? 0 );
        
        return [
            'total_leads' => $total_leads,
            'converted_leads' => $converted_leads,
            'conversion_rate' => $total_leads > 0 ? round( ( $converted_leads / $total_leads ) * 100, 2 ) : 0,
            'avg_roi' => round( floatval( $summary['avg_roi'] ?? 0 ), 2 ),
            'min_roi' => floatval( $summary['min_roi'] ?? 0 ),
            'max_roi' => floatval( $summary['max_roi'] ?? 0 ),
            'todays_leads' => intval( $summary['todays_leads'] ?? 0 ),
        ];
    }
}