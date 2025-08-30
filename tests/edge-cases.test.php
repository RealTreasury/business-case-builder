<?php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ );
}
defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private $message;
        public function __construct( $code = '', $message = '' ) {
            $this->message = $message;
        }
        public function get_error_message() {
            return $this->message;
        }
    }
}

function is_wp_error( $thing ) {
    return $thing instanceof WP_Error;
}

function wp_verify_nonce( $nonce, $action ) {
    return true;
}

function wp_unslash( $value ) {
    return $value;
}

function sanitize_text_field( $text ) {
    $text = is_scalar( $text ) ? (string) $text : '';
    $text = preg_replace( '/[\r\n\t\0\x0B]/', '', $text );
    return trim( $text );
}

function sanitize_email( $email ) {
    return filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : '';
}

class RTBCB_JSON_Response extends Exception {
    public $response;
    public $status;
    public function __construct( $response, $status ) {
        parent::__construct();
        $this->response = $response;
        $this->status   = $status;
    }
}

function wp_send_json_error( $data = null, $status_code = null ) {
    throw new RTBCB_JSON_Response(
        [
            'success' => false,
            'data'    => $data,
        ],
        $status_code
    );
}

function wp_send_json_success( $data = null, $status_code = null ) {
    throw new RTBCB_JSON_Response(
        [
            'success' => true,
            'data'    => $data,
        ],
        $status_code
    );
}

function __( $text, $domain = null ) {
    return $text;
}

function wp_upload_dir() {
    return [ 'basedir' => sys_get_temp_dir() ];
}

function trailingslashit( $p ) {
    return rtrim( $p, '/\\' ) . '/';
}

function wp_mkdir_p( $dir ) {
    if ( ! is_dir( $dir ) ) {
        mkdir( $dir, 0777, true );
    }
}

function get_bloginfo( $show = '' ) {
    return 'Test Blog';
}

function wp_mail( $to, $subject, $message, $headers = [], $attachments = [] ) {
    return true;
}

function rtbcb_sanitize_form_data( $data ) {
    $sanitized = [];

    if ( isset( $data['email'] ) ) {
        $sanitized['email'] = sanitize_email( $data['email'] );
    }

    $text_fields = [ 'company_size', 'industry', 'current_tech', 'business_objective', 'implementation_timeline', 'budget_range', 'company_name', 'company_description' ];
    foreach ( $text_fields as $field ) {
        if ( isset( $data[ $field ] ) ) {
            $sanitized[ $field ] = sanitize_text_field( $data[ $field ] );
        }
    }

    $numeric_fields = [
        'hours_reconciliation'   => [ 'min' => 0,   'max' => 168 ],
        'hours_cash_positioning' => [ 'min' => 0,   'max' => 168 ],
        'num_banks'              => [ 'min' => 1,   'max' => 50 ],
        'ftes'                   => [ 'min' => 0.5, 'max' => 100 ],
    ];

    foreach ( $numeric_fields as $field => $limits ) {
        if ( isset( $data[ $field ] ) ) {
            $value = floatval( $data[ $field ] );
            $value = max( $limits['min'], min( $limits['max'], $value ) );
            $sanitized[ $field ] = $value;
        }
    }

    if ( isset( $data['pain_points'] ) && is_array( $data['pain_points'] ) ) {
        $valid_pain_points = [
            'manual_processes',
            'poor_visibility',
            'forecast_accuracy',
            'compliance_risk',
            'bank_fees',
            'integration_issues',
        ];
        $sanitized['pain_points'] = array_filter(
            array_map( 'sanitize_text_field', $data['pain_points'] ),
            function ( $point ) use ( $valid_pain_points ) {
                return in_array( $point, $valid_pain_points, true );
            }
        );
    }

    if ( isset( $data['decision_makers'] ) && is_array( $data['decision_makers'] ) ) {
        $sanitized['decision_makers'] = array_map( 'sanitize_text_field', $data['decision_makers'] );
    }

    if ( isset( $data['consent'] ) ) {
        $sanitized['consent'] = sanitize_text_field( $data['consent'] );
    }

    return $sanitized;
}

class RTBCB_Calculator {
    public static function calculate_roi( $form_data ) {
        return [
            'roi_low'  => 1000,
            'roi_base' => 2000,
            'roi_high' => 3000,
        ];
    }
}

class RTBCB_RAG {
    public function get_context( $desc ) {
        return [];
    }
}

class RTBCB_LLM {
    public function generate_business_case( $form_data, $calculations, $rag_context, $model ) {
        return [
            'roi_low'  => 1000,
            'roi_base' => 2000,
            'roi_high' => 3000,
        ];
    }
}

class RTBCB_Leads {
    public function save_lead( $form_data, $business_case_data ) {
        return 1;
    }
}

if ( ! defined( 'RTBCB_DIR' ) ) {
    define( 'RTBCB_DIR', __DIR__ . '/../' );
}

require_once RTBCB_DIR . 'inc/class-rtbcb-validator.php';
require_once RTBCB_DIR . 'inc/class-rtbcb-router.php';

class Test_RTBCB_Router extends RTBCB_Router {
    public function route_model( $inputs, $chunks ) {
        return 'test-model';
    }

    public function get_report_html( $business_case_data ) {
        return '<html>report</html>';
    }
}

final class RTBCB_EdgeCasesTest extends TestCase {
    /**
     * @dataProvider edge_case_provider
     */
    public function test_handle_form_submission( $post_data, $expected_success, $expected_message = '' ) {
        $post_data['rtbcb_nonce'] = 'nonce';
        $_POST                   = $post_data;

        $router = new Test_RTBCB_Router();
        $caught = null;
        try {
            $router->handle_form_submission();
        } catch ( RTBCB_JSON_Response $e ) {
            $caught = $e;
        }

        $this->assertNotNull( $caught, 'No JSON response captured.' );

        if ( $expected_success ) {
            $this->assertTrue( $caught->response['success'] );
        } else {
            $this->assertFalse( $caught->response['success'] );
            $this->assertSame( $expected_message, $caught->response['data']['message'] );
        }

        $_POST = [];
    }

    public function edge_case_provider() {
        $base = [
            'company_name'           => 'ACME',
            'company_size'           => '1-10',
            'industry'               => 'Finance',
            'hours_reconciliation'   => 10,
            'hours_cash_positioning' => 5,
            'num_banks'              => 3,
            'ftes'                   => 10,
            'pain_points'            => [ 'manual_processes' ],
            'current_tech'           => 'spreadsheets',
            'business_objective'     => 'growth',
            'implementation_timeline'=> 'Q1',
            'decision_makers'        => [ 'CFO' ],
            'budget_range'           => '$10k-$50k',
            'email'                  => 'user@example.com',
            'consent'                => '1',
            'company_description'    => 'desc',
        ];

        return [
            'extreme_numeric_values' => [
                array_merge( $base, [
                    'hours_reconciliation'   => PHP_INT_MAX,
                    'hours_cash_positioning' => -5,
                    'num_banks'              => 999,
                    'ftes'                   => 1000,
                ] ),
                true,
            ],
            'missing_optional_fields' => [
                [
                    'company_name'           => 'ACME',
                    'company_size'           => '1-10',
                    'industry'               => 'Finance',
                    'hours_reconciliation'   => 1,
                    'hours_cash_positioning' => 1,
                    'num_banks'              => 1,
                    'ftes'                   => 1,
                    'current_tech'           => 'spreadsheets',
                    'business_objective'     => 'growth',
                    'implementation_timeline'=> 'Q1',
                    'budget_range'           => '$10k-$50k',
                    'email'                  => 'user@example.com',
                    'consent'                => '1',
                    'company_description'    => '',
                ],
                true,
            ],
            'unusual_character_sets' => [
                array_merge( $base, [
                    'company_name'       => '测试公司🚀',
                    'business_objective' => 'Expand π',
                ] ),
                true,
            ],
            'missing_required_field' => [
                array_merge( $base, [
                    'company_name' => '',
                ] ),
                false,
                'Company name is required.',
            ],
            'invalid_email' => [
                array_merge( $base, [
                    'email' => 'invalid-email',
                ] ),
                false,
                'Email is required.',
            ],
        ];
    }
}
