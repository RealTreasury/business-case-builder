<?php
class RTBCB_GPT5_Integration_Test extends WP_UnitTestCase {
    
    public function test_gpt5_request_format() {
        $llm       = new RTBCB_LLM();
        $reflection = new ReflectionClass( $llm );
        $method     = $reflection->getMethod( 'call_openai' );
        $method->setAccessible( true );

        add_filter(
            'pre_http_request',
            function( $preempt, $args, $url ) {
                return [
                    'body'     => json_encode(
                        [
                            'output_text' => 'Test comprehensive business case analysis...',
                            'output'      => [
                                [
                                    'type'    => 'message',
                                    'content' => [
                                        [ 'type' => 'output_text', 'text' => 'Test comprehensive business case analysis...' ],
                                    ],
                                ],
                            ],
                        ]
                    ),
                    'response' => [
                        'code'    => 200,
                        'message' => 'OK',
                    ],
                ];
            },
            10,
            3
        );

        $prompt = [
            'instructions' => 'Create a business case',
            'input'        => 'Company: Test Corp, Industry: Manufacturing',
        ];

        $method->invoke( $llm, 'gpt-5-mini', $prompt );

        $request  = $llm->get_last_request();
        $response = $llm->get_last_response();

        $this->assertArrayHasKey( 'reasoning', $request );
        $this->assertArrayHasKey( 'text', $request );
        $this->assertEquals( 'high', $request['reasoning']['effort'] );
        $this->assertEquals( 200, wp_remote_retrieve_response_code( $response ) );
        $this->assertEquals( 'OK', wp_remote_retrieve_response_message( $response ) );

        remove_all_filters( 'pre_http_request' );
    }

    public function test_gpt5_error_storage() {
        $llm       = new RTBCB_LLM();
        $reflection = new ReflectionClass( $llm );
        $method     = $reflection->getMethod( 'call_openai' );
        $method->setAccessible( true );

        add_filter(
            'pre_http_request',
            function( $preempt, $args, $url ) {
                return new WP_Error( 'http_error', 'Bad Request' );
            },
            10,
            3
        );

        $prompt = [
            'instructions' => 'Create a business case',
            'input'        => 'Company: Test Corp, Industry: Manufacturing',
        ];

        $method->invoke( $llm, 'gpt-5-mini', $prompt );

        $response = $llm->get_last_response();

        $this->assertTrue( is_wp_error( $response ) );
        $this->assertEquals( 'Bad Request', $response->get_error_message() );

        remove_all_filters( 'pre_http_request' );
    }
    
    public function test_quality_validation() {
        $mock_response = [
            'body' => json_encode([
                'output_text' => 'pong — how can I help you today?',
            ])
        ];
        
        $result = rtbcb_parse_gpt5_business_case_response( $mock_response );
        
        $this->assertContains( 'HEALTH_CHECK_RESPONSE', $result['alerts'] );
        $this->assertLessThan( 3, $result['quality_score'] );
    }
    
    public function test_comprehensive_business_case_quality() {
        $mock_response = [
            'body' => json_encode([
                'output_text' => json_encode([
                    'executive_summary' => [
                        'strategic_positioning' => 'Test Corp is well-positioned for treasury technology adoption...',
                        'business_case_strength' => 'Strong',
                        'key_value_drivers' => ['Operational efficiency', 'Risk reduction', 'Cost savings'],
                        'executive_recommendation' => 'Proceed with TMS implementation...'
                    ],
                    // ... more sections
                ])
            ])
        ];
        
        $result = rtbcb_parse_gpt5_business_case_response( $mock_response );
        
        $this->assertGreaterThan( 5, $result['quality_score'] );
        $this->assertEmpty( $result['alerts'] );
    }
}
