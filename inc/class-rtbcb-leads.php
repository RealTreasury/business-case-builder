<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enhanced leads management for tracking form submissions.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_Leads - Enhanced version with full tracking capabilities.
 */
class RTBCB_Leads {
    /**
     * Database table name.
     *
     * @var string
     */
    private static $table_name;

    /**
     * Create the leads table with improved error handling.
     *
     * @return bool True on success, false on failure.
     */
    private static function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        self::$table_name = $wpdb->prefix . 'rtbcb_leads';

        $sql = "CREATE TABLE " . self::$table_name . " (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            company_name varchar(255) DEFAULT '',
            company_size varchar(50) DEFAULT '',
            industry varchar(50) DEFAULT '',
            hours_reconciliation decimal(5,2) DEFAULT 0,
            hours_cash_positioning decimal(5,2) DEFAULT 0,
            num_banks int(3) DEFAULT 0,
            ftes decimal(4,1) DEFAULT 0,
            pain_points longtext DEFAULT '',
            recommended_category varchar(50) DEFAULT '',
            roi_low decimal(12,2) DEFAULT 0,
            roi_base decimal(12,2) DEFAULT 0,
            roi_high decimal(12,2) DEFAULT 0,
            report_html longtext DEFAULT '',
            ip_address varchar(45) DEFAULT '',
            user_agent text DEFAULT '',
            utm_source varchar(100) DEFAULT '',
            utm_medium varchar(100) DEFAULT '',
            utm_campaign varchar(100) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email_unique (email),
            KEY created_at_index (created_at),
            KEY company_size_index (company_size),
            KEY recommended_category_index (recommended_category),
            KEY email_created (email, created_at),
            KEY roi_base_index (roi_base)
        ) $charset_collate;";

        try {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            $result = dbDelta( $sql );

            // Check if table was actually created
            $table_exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                self::$table_name
            ) );

            if ( ! $table_exists ) {
                error_log( 'RTBCB: Failed to create table ' . self::$table_name );

                // Try to create table with simpler structure as fallback
                $simple_sql = "CREATE TABLE IF NOT EXISTS " . self::$table_name . " (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    email varchar(255) NOT NULL,
                    company_name varchar(255) DEFAULT '',
                    company_size varchar(50) DEFAULT '',
                    industry varchar(50) DEFAULT '',
                    hours_reconciliation decimal(5,2) DEFAULT 0,
                    hours_cash_positioning decimal(5,2) DEFAULT 0,
                    num_banks int(3) DEFAULT 0,
                    ftes decimal(4,1) DEFAULT 0,
                    pain_points text DEFAULT '',
                    recommended_category varchar(50) DEFAULT '',
                    roi_low decimal(12,2) DEFAULT 0,
                    roi_base decimal(12,2) DEFAULT 0,
                    roi_high decimal(12,2) DEFAULT 0,
                    report_html text DEFAULT '',
                    ip_address varchar(45) DEFAULT '',
                    user_agent text DEFAULT '',
                    utm_source varchar(100) DEFAULT '',
                    utm_medium varchar(100) DEFAULT '',
                    utm_campaign varchar(100) DEFAULT '',
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY email_unique (email)
                ) $charset_collate;";

                $wpdb->query( $simple_sql );

                // Check again
                $table_exists = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                    DB_NAME,
                    self::$table_name
                ) );

                if ( ! $table_exists ) {
                    error_log( 'RTBCB: Failed to create table even with simple structure' );
                    return false;
                }
            }

            error_log( 'RTBCB: Successfully created/updated table ' . self::$table_name );
            return true;

        } catch ( Exception $e ) {
            error_log( 'RTBCB: Exception creating table: ' . $e->getMessage() );
            return false;
        } catch ( Error $e ) {
            error_log( 'RTBCB: Fatal error creating table: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Initialize the class with better error handling.
     */
    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'rtbcb_leads';

        // Try to create table and log results
        $table_created = self::create_table();

        if ( ! $table_created ) {
            error_log( 'RTBCB: Warning - leads table creation failed, plugin may not function correctly' );

            // Add admin notice for database issues
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__( 'Real Treasury Business Case Builder: Database table creation failed. Please check your database permissions.', 'rtbcb' );
                echo '</p></div>';
            } );
        }
    }
	/**
	 * Add missing indexes to the leads table.
	 *
	 * Ensures unique and secondary indexes exist for commonly queried fields.
	 *
	 * @return void
	 */
        public static function add_missing_indexes() {
                global $wpdb;

		self::$table_name = $wpdb->prefix . 'rtbcb_leads';

		$indexes     = $wpdb->get_results( 'SHOW INDEX FROM ' . self::$table_name, ARRAY_A );
		$index_names = wp_list_pluck( $indexes, 'Key_name' );

		if ( ! in_array( 'email_unique', $index_names, true ) ) {
			$wpdb->query( 'ALTER TABLE ' . self::$table_name . ' ADD UNIQUE KEY email_unique (email)' );
		}

		if ( ! in_array( 'created_at_index', $index_names, true ) ) {
			$wpdb->query( 'ALTER TABLE ' . self::$table_name . ' ADD KEY created_at_index (created_at)' );
		}

                if ( ! in_array( 'recommended_category_index', $index_names, true ) ) {
                        $wpdb->query( 'ALTER TABLE ' . self::$table_name . ' ADD KEY recommended_category_index (recommended_category)' );
                }
        }

    /**
     * Compress and encode existing report_html entries.
     *
     * @return void
     */
    public static function compress_existing_report_html() {
        global $wpdb;

        self::$table_name = $wpdb->prefix . 'rtbcb_leads';

        $leads = $wpdb->get_results(
            "SELECT id, report_html FROM " . self::$table_name . " WHERE report_html != ''",
            ARRAY_A
        );

        foreach ( $leads as $lead ) {
            $data    = $lead['report_html'];
            $decoded = base64_decode( $data, true );
            $decoded = false !== $decoded ? $decoded : $data;

            if ( false !== @gzuncompress( $decoded ) ) {
                continue;
            }

            $compressed = base64_encode( gzcompress( $data ) );
            $wpdb->update(
                self::$table_name,
                [ 'report_html' => $compressed ],
                [ 'id' => $lead['id'] ],
                [ '%s' ],
                [ '%d' ]
            );
        }
    }


    /**
     * Save a lead to the database.
     *
     * @param array $lead_data Lead information.
     * @return int|false Lead ID or false on failure.
     */
    public static function save_lead( $lead_data ) {
        global $wpdb;

        // Validate required fields
        if ( empty( $lead_data['email'] ) || ! is_email( $lead_data['email'] ) ) {
            error_log( 'RTBCB: Invalid email provided to save_lead' );
            return false;
        }

        // Sanitize data with proper validation
        $sanitized_data = [
            'email'                   => sanitize_email( $lead_data['email'] ),
            'company_name'            => sanitize_text_field( $lead_data['company_name'] ?? '' ),
            'company_size'            => sanitize_text_field( $lead_data['company_size'] ?? '' ),
            'industry'                => sanitize_text_field( $lead_data['industry'] ?? '' ),
            'hours_reconciliation'    => floatval( $lead_data['hours_reconciliation'] ?? 0 ),
            'hours_cash_positioning'  => floatval( $lead_data['hours_cash_positioning'] ?? 0 ),
            'num_banks'               => intval( $lead_data['num_banks'] ?? 0 ),
            'ftes'                    => floatval( $lead_data['ftes'] ?? 0 ),
            'pain_points'             => maybe_serialize( $lead_data['pain_points'] ?? [] ),
            'recommended_category'    => sanitize_text_field( $lead_data['recommended_category'] ?? '' ),
            'roi_low'                 => floatval( $lead_data['roi_low'] ?? 0 ),
            'roi_base'                => floatval( $lead_data['roi_base'] ?? 0 ),
            'roi_high'                => floatval( $lead_data['roi_high'] ?? 0 ),
            'report_html'             => wp_kses_post( $lead_data['report_html'] ?? '' ),
            'ip_address'              => self::get_client_ip(),
            'user_agent'              => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
            'utm_source'              => sanitize_text_field( $_GET['utm_source'] ?? '' ),
            'utm_medium'              => sanitize_text_field( $_GET['utm_medium'] ?? '' ),
            'utm_campaign'            => sanitize_text_field( $_GET['utm_campaign'] ?? '' ),
        ];

        if ( ! empty( $sanitized_data['report_html'] ) ) {
            $sanitized_data['report_html'] = base64_encode( gzcompress( $sanitized_data['report_html'] ) );
        }

        // Prepare format array to match the sanitized data
        $formats = [
            '%s', // email
            '%s', // company_name
            '%s', // company_size
            '%s', // industry
            '%f', // hours_reconciliation
            '%f', // hours_cash_positioning
            '%d', // num_banks
            '%f', // ftes
            '%s', // pain_points (serialized)
            '%s', // recommended_category
            '%f', // roi_low
            '%f', // roi_base
            '%f', // roi_high
            '%s', // report_html
            '%s', // ip_address
            '%s', // user_agent
            '%s', // utm_source
            '%s', // utm_medium
            '%s', // utm_campaign
        ];

        // Check if lead exists
        try {
            $existing_lead = self::get_lead_by_email( $sanitized_data['email'] );

            if ( $existing_lead ) {
                // Update existing lead
                $result = $wpdb->update(
                    self::$table_name,
                    $sanitized_data,
                    [ 'email' => $sanitized_data['email'] ],
                    $formats,
                    [ '%s' ]
                );

                if ( false === $result ) {
                    error_log( 'RTBCB: Database update failed: ' . $wpdb->last_error );
                    return false;
                }

                return intval( $existing_lead['id'] );
            } else {
                // Insert new lead
                $result = $wpdb->insert(
                    self::$table_name,
                    $sanitized_data,
                    $formats
                );

                if ( false === $result ) {
                    error_log( 'RTBCB: Database insert failed: ' . $wpdb->last_error );
                    return false;
                }

                return $wpdb->insert_id;
            }
        } catch ( Exception $e ) {
            error_log( 'RTBCB: Exception in save_lead: ' . $e->getMessage() );
            return false;
        } catch ( Error $e ) {
            error_log( 'RTBCB: Fatal error in save_lead: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Get a lead by email address.
     *
     * @param string $email Email address.
     * @return array|null Lead data or null if not found.
     */
    public static function get_lead_by_email( $email ) {
        global $wpdb;

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::$table_name . " WHERE email = %s",
                sanitize_email( $email )
            ),
            ARRAY_A
        );

        if ( $result ) {
            $result['pain_points'] = maybe_unserialize( $result['pain_points'] );

            if ( ! empty( $result['report_html'] ) ) {
                $data    = $result['report_html'];
                $decoded = base64_decode( $data, true );
                $decoded = false !== $decoded ? $decoded : $data;

                $uncompressed = @gzuncompress( $decoded );
                if ( false !== $uncompressed ) {
                    $result['report_html'] = $uncompressed;
                }
            }
        }

        return $result;
    }

    /**
     * Get all leads with pagination and filtering.
     *
     * @param array $args Query arguments.
     * @return array Leads data with pagination info.
     */
    public static function get_all_leads( $args = [] ) {
        global $wpdb;

        $defaults = [
            'per_page'    => 20,
            'page'        => 1,
            'orderby'     => 'created_at',
            'order'       => 'DESC',
            'search'      => '',
            'category'    => '',
            'date_from'   => '',
            'date_to'     => '',
        ];

        $args = function_exists( 'wp_parse_args' )
            ? wp_parse_args( $args, $defaults )
            : array_merge( $defaults, $args );

        // Build WHERE clause
        $where_conditions = [ '1=1' ];
        $prepare_values = [];

        if ( ! empty( $args['search'] ) ) {
            $where_conditions[] = 'email LIKE %s';
            $prepare_values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
        }

        if ( ! empty( $args['category'] ) ) {
            $where_conditions[] = 'recommended_category = %s';
            $prepare_values[] = $args['category'];
        }

        if ( ! empty( $args['date_from'] ) ) {
            $where_conditions[] = 'created_at >= %s';
            $prepare_values[] = $args['date_from'] . ' 00:00:00';
        }

        if ( ! empty( $args['date_to'] ) ) {
            $where_conditions[] = 'created_at <= %s';
            $prepare_values[] = $args['date_to'] . ' 23:59:59';
        }

        $where_clause = implode( ' AND ', $where_conditions );

        // Get total count
        $count_sql = "SELECT COUNT(*) FROM " . self::$table_name . " WHERE " . $where_clause;
        if ( ! empty( $prepare_values ) ) {
            $total_leads = $wpdb->get_var( $wpdb->prepare( $count_sql, $prepare_values ) );
        } else {
            $total_leads = $wpdb->get_var( $count_sql );
        }

        // Get leads
        $offset = ( $args['page'] - 1 ) * $args['per_page'];
        $orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );

        $sql = "SELECT * FROM " . self::$table_name . " WHERE " . $where_clause . " ORDER BY " . $orderby . " LIMIT %d OFFSET %d";
        $prepare_values[] = $args['per_page'];
        $prepare_values[] = $offset;

        $leads = $wpdb->get_results( $wpdb->prepare( $sql, $prepare_values ), ARRAY_A );

        // Unserialize pain points and decompress report HTML.
        foreach ( $leads as &$lead ) {
            $lead['pain_points'] = maybe_unserialize( $lead['pain_points'] );

            if ( ! empty( $lead['report_html'] ) ) {
                $data    = $lead['report_html'];
                $decoded = base64_decode( $data, true );
                $decoded = false !== $decoded ? $decoded : $data;

                $uncompressed = @gzuncompress( $decoded );
                if ( false !== $uncompressed ) {
                    $lead['report_html'] = $uncompressed;
                }
            }
        }

        return [
            'leads'       => $leads,
            'total'       => intval( $total_leads ),
            'per_page'    => $args['per_page'],
            'current_page'=> $args['page'],
            'total_pages' => ceil( $total_leads / $args['per_page'] ),
        ];
    }

    /**
     * Get lead statistics.
     *
     * @return array Statistics data.
     */
    public static function get_statistics() {
        global $wpdb;

        $stats = [];

        // Total leads
        $stats['total_leads'] = $wpdb->get_var( "SELECT COUNT(*) FROM " . self::$table_name );

        // Leads by category
        $category_stats = $wpdb->get_results(
            "SELECT recommended_category, COUNT(*) as count FROM " . self::$table_name . " 
             WHERE recommended_category != '' 
             GROUP BY recommended_category",
            ARRAY_A
        );
        $stats['by_category'] = $category_stats;

        // Leads by company size
        $size_stats = $wpdb->get_results(
            "SELECT company_size, COUNT(*) as count FROM " . self::$table_name . " 
             WHERE company_size != '' 
             GROUP BY company_size",
            ARRAY_A
        );
        $stats['by_company_size'] = $size_stats;

        // Average ROI
        $roi_stats = $wpdb->get_row(
            "SELECT AVG(roi_low) as avg_low, AVG(roi_base) as avg_base, AVG(roi_high) as avg_high 
             FROM " . self::$table_name . " 
             WHERE roi_base > 0",
            ARRAY_A
        );
        $stats['average_roi'] = $roi_stats;

        // Recent activity (last 30 days)
        $stats['recent_leads'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . self::$table_name . " 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        return $stats;
    }

    /**
     * Export leads to CSV.
     *
     * @param array $args Export arguments.
     * @return string CSV content.
     */
    public static function export_to_csv( $args = [] ) {
        $leads_data = self::get_all_leads( array_merge( $args, [ 'per_page' => -1 ] ) );
        $leads = $leads_data['leads'];

        $csv_content = '';

        // Headers
        $headers = [
            'Email', 'Company Size', 'Industry', 'Hours Reconciliation',
            'Hours Cash Positioning', 'Number of Banks', 'FTEs',
            'Pain Points', 'Recommended Category', 'ROI Low', 'ROI Base',
            'ROI High', 'Created At', 'UTM Source', 'UTM Medium', 'UTM Campaign'
        ];
        $csv_content .= implode( ',', $headers ) . "\n";

        // Data rows
        foreach ( $leads as $lead ) {
            $row = [
                '"' . str_replace( '"', '""', $lead['email'] ) . '"',
                '"' . str_replace( '"', '""', $lead['company_size'] ) . '"',
                '"' . str_replace( '"', '""', $lead['industry'] ) . '"',
                $lead['hours_reconciliation'],
                $lead['hours_cash_positioning'],
                $lead['num_banks'],
                $lead['ftes'],
                '"' . str_replace( '"', '""', implode( '; ', (array) $lead['pain_points'] ) ) . '"',
                '"' . str_replace( '"', '""', $lead['recommended_category'] ) . '"',
                $lead['roi_low'],
                $lead['roi_base'],
                $lead['roi_high'],
                $lead['created_at'],
                '"' . str_replace( '"', '""', $lead['utm_source'] ) . '"',
                '"' . str_replace( '"', '""', $lead['utm_medium'] ) . '"',
                '"' . str_replace( '"', '""', $lead['utm_campaign'] ) . '"',
            ];
            $csv_content .= implode( ',', $row ) . "\n";
        }

        return $csv_content;
    }

    /**
     * Get client IP address.
     *
     * @return string IP address.
     */
    private static function get_client_ip() {
        $ip_keys = [ 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ];

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = $_SERVER[ $key ];
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
}

// Initialize the class
add_action( 'init', [ 'RTBCB_Leads', 'init' ] );
