<?php
class RTBCB_GPT5_Integration_Test extends WP_UnitTestCase {
    
    public function test_gpt5_request_format() {
        $llm = new RTBCB_LLM();
        $reflection = new ReflectionClass( $llm );
        $method = $reflection->getMethod( 'call_openai' );
        $method->setAccessible( true );
        
        // Mock wp_remote_post to capture request
        $captured_body = null;
        add_filter( 'pre_http_request', function( $preempt, $args ) use ( &$captured_body ) {
            $captured_body = json_decode( $args['body'], true );
            return [
                'body' => json_encode([
                    'output_text' => 'Test comprehensive business case analysis...',
                    'output' => [
                        [
                            'type' => 'message',
                            'content' => [
                                [ 'type' => 'output_text', 'text' => 'Test comprehensive business case analysis...' ]
                            ]
                        ]
                    ]
                ])
            ];
        }, 10, 2 );
        
        $prompt = [
            'instructions' => 'Create a business case',
            'input' => 'Company: Test Corp, Industry: Manufacturing'
        ];
        
        $method->invoke( $llm, 'gpt-5-mini', $prompt );
        
        // Verify GPT-5 specific parameters
        $this->assertArrayHasKey( 'reasoning', $captured_body );
        $this->assertArrayHasKey( 'text', $captured_body );
        $this->assertEquals( 'medium', $captured_body['reasoning']['effort'] ); // Reduced effort for faster responses
        
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
