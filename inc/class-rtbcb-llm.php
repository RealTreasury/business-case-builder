<?php
/**
 * Enhanced LLM integration with comprehensive business analysis
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

class RTBCB_LLM {
    private $api_key;
    private $models;
    private $current_inputs = [];

    public function __construct() {
        $this->api_key = get_option( 'rtbcb_openai_api_key' );
        $this->models  = [
            'mini'      => get_option( 'rtbcb_mini_model', 'gpt-4o-mini' ),
            'premium'   => get_option( 'rtbcb_premium_model', 'gpt-4o' ),
            'advanced'  => get_option( 'rtbcb_advanced_model', 'o1-preview' ),
            'embedding' => get_option( 'rtbcb_embedding_model', 'text-embedding-3-small' ),
        ];
        
        if ( empty( $this->api_key ) ) {
            error_log( 'RTBCB: OpenAI API key not configured - reports will use fallback content' );
        }
    }

    /**
     * Generate a simplified business case analysis.
     *
     * Attempts to call the LLM for a brief analysis. Falls back to an
     * enhanced static analysis when no API key is available. If the LLM call
     * fails, a {@see WP_Error} is returned for the caller to handle.
     *
     * @param array $user_inputs    Sanitized user inputs.
     * @param array $roi_data       ROI calculation data.
     * @param array $context_chunks Optional context strings for the prompt.
     *
     * @return array|WP_Error Simplified analysis array or error object.
     */
    public function generate_business_case( $user_inputs, $roi_data, $context_chunks = [] ) {
        $inputs = [
            'company_name'           => sanitize_text_field( $user_inputs['company_name'] ?? '' ),
            'company_size'           => sanitize_text_field( $user_inputs['company_size'] ?? '' ),
            'industry'               => sanitize_text_field( $user_inputs['industry'] ?? '' ),
            'hours_reconciliation'   => floatval( $user_inputs['hours_reconciliation'] ?? 0 ),
            'hours_cash_positioning' => floatval( $user_inputs['hours_cash_positioning'] ?? 0 ),
            'num_banks'              => intval( $user_inputs['num_banks'] ?? 0 ),
            'ftes'                   => floatval( $user_inputs['ftes'] ?? 0 ),
            'pain_points'            => array_map( 'sanitize_text_field', (array) ( $user_inputs['pain_points'] ?? [] ) ),
        ];

        $this->current_inputs = $inputs;

        if ( empty( $this->api_key ) ) {
            return $this->create_enhanced_fallback( $inputs, $roi_data );
        }

        $model  = $this->models['mini'] ?? 'gpt-4o-mini';
        $prompt = 'Create a concise treasury technology business case in JSON with keys '
            . 'executive_summary (strategic_positioning, business_case_strength, key_value_drivers[], '
            . 'executive_recommendation), operational_analysis (current_state_assessment), '
            . 'industry_insights (sector_trends, competitive_benchmarks, regulatory_considerations).'
            . '\nCompany: ' . $inputs['company_name']
            . '\nIndustry: ' . $inputs['industry']
            . '\nSize: ' . $inputs['company_size']
            . '\nPain Points: ' . implode( ', ', $inputs['pain_points'] );

        if ( ! empty( $context_chunks ) ) {
            $prompt .= '\nContext: ' . implode( '\n', array_map( 'sanitize_text_field', $context_chunks ) );
        }

        $response = $this->call_openai_with_retry( $model, $prompt );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'llm_failure', __( 'Unable to generate analysis at this time.', 'rtbcb' ) );
        }

        $body    = wp_remote_retrieve_body( $response );
        $decoded = json_decode( $body, true );
        $content = $decoded['choices'][0]['message']['content'] ?? '';
        $json    = json_decode( $content, true );

        if ( ! is_array( $json ) ) {
            return new WP_Error( 'llm_parse_error', __( 'Invalid response from language model.', 'rtbcb' ) );
        }

        $analysis = [
            'company_name'       => $inputs['company_name'],
            'analysis_date'      => current_time( 'Y-m-d' ),
            'executive_summary'  => [
                'strategic_positioning'   => sanitize_text_field( $json['executive_summary']['strategic_positioning'] ?? '' ),
                'business_case_strength'  => sanitize_text_field( $json['executive_summary']['business_case_strength'] ?? '' ),
                'key_value_drivers'       => array_map( 'sanitize_text_field', $json['executive_summary']['key_value_drivers'] ?? [] ),
                'executive_recommendation'=> sanitize_text_field( $json['executive_summary']['executive_recommendation'] ?? '' ),
            ],
            'operational_analysis' => [
                'current_state_assessment' => sanitize_text_field( $json['operational_analysis']['current_state_assessment'] ?? '' ),
            ],
            'industry_insights'   => [
                'sector_trends'          => sanitize_text_field( $json['industry_insights']['sector_trends'] ?? '' ),
                'competitive_benchmarks' => sanitize_text_field( $json['industry_insights']['competitive_benchmarks'] ?? '' ),
                'regulatory_considerations' => sanitize_text_field( $json['industry_insights']['regulatory_considerations'] ?? '' ),
            ],
            'financial_analysis' => $this->build_financial_analysis( $roi_data, $inputs ),
        ];

        return $analysis;
    }

    /**
     * Generate comprehensive business case with deep analysis
     */
    public function generate_comprehensive_business_case( $user_inputs, $roi_data, $context_chunks = [] ) {
        $this->current_inputs = $user_inputs;
        
        if ( empty( $this->api_key ) ) {
            error_log( 'RTBCB: No API key - using enhanced fallback' );
            return $this->create_enhanced_fallback( $user_inputs, $roi_data );
        }

        // Enhanced company research
        $company_research = $this->conduct_company_research( $user_inputs );
        
        // Industry analysis
        $industry_analysis = $this->analyze_industry_context( $user_inputs );
        
        // Technology landscape research
        $tech_landscape = $this->research_treasury_solutions( $user_inputs, $context_chunks );
        
        // Generate comprehensive report
        $model = $this->select_optimal_model( $user_inputs, $context_chunks );
        $prompt = $this->build_comprehensive_prompt( 
            $user_inputs, 
            $roi_data, 
            $company_research,
            $industry_analysis,
            $tech_landscape
        );
        
        $response = $this->call_openai_with_retry( $model, $prompt );
        
        if ( is_wp_error( $response ) ) {
            error_log( 'RTBCB: OpenAI call failed: ' . $response->get_error_message() );
            return $this->create_enhanced_fallback( $user_inputs, $roi_data );
        }
        
        $parsed = $this->parse_comprehensive_response( $response );
        
        if ( isset( $parsed['error'] ) ) {
            error_log( 'RTBCB: Response parsing failed: ' . $parsed['error'] );
            return $this->create_enhanced_fallback( $user_inputs, $roi_data );
        }
        
        return $this->enhance_with_research( $parsed, $company_research, $industry_analysis );
    }

    /**
     * Conduct company-specific research
     */
    private function conduct_company_research( $user_inputs ) {
        $company_name = $user_inputs['company_name'] ?? '';
        $industry = $user_inputs['industry'] ?? '';
        $company_size = $user_inputs['company_size'] ?? '';
        
        // Simulate company research (in real implementation, this could query APIs, databases, etc.)
        $research = [
            'company_profile' => $this->build_company_profile( $company_name, $industry, $company_size ),
            'industry_positioning' => $this->analyze_market_position( $industry, $company_size ),
            'treasury_maturity' => $this->assess_treasury_maturity( $user_inputs ),
            'competitive_landscape' => $this->analyze_competitive_context( $industry ),
            'growth_trajectory' => $this->project_growth_path( $company_size, $industry ),
        ];
        
        return $research;
    }

    /**
     * Build detailed company profile
     */
    private function build_company_profile( $company_name, $industry, $company_size ) {
        $size_profiles = [
            '<$50M' => [
                'stage' => 'emerging growth',
                'characteristics' => 'agile decision-making, resource constraints, high growth potential',
                'treasury_focus' => 'cash flow optimization, banking relationship efficiency',
                'typical_challenges' => 'manual processes, limited treasury expertise, cash flow volatility'
            ],
            '$50M-$500M' => [
                'stage' => 'scaling business',
                'characteristics' => 'expanding operations, increasing complexity, professionalization',
                'treasury_focus' => 'process automation, risk management, strategic cash management',
                'typical_challenges' => 'growing complexity, system integration, resource allocation'
            ],
            '$500M-$2B' => [
                'stage' => 'established enterprise',
                'characteristics' => 'mature operations, multiple business units, geographic diversity',
                'treasury_focus' => 'enterprise integration, advanced analytics, risk optimization',
                'typical_challenges' => 'legacy systems, coordination complexity, regulatory compliance'
            ],
            '>$2B' => [
                'stage' => 'large enterprise',
                'characteristics' => 'global operations, sophisticated structure, regulatory oversight',
                'treasury_focus' => 'enterprise-wide optimization, regulatory compliance, strategic finance',
                'typical_challenges' => 'system complexity, governance requirements, scale management'
            ]
        ];
        
        $profile = $size_profiles[$company_size] ?? $size_profiles['$50M-$500M'];
        
        return [
            'company_name' => $company_name,
            'revenue_segment' => $company_size,
            'business_stage' => $profile['stage'],
            'key_characteristics' => $profile['characteristics'],
            'treasury_priorities' => $profile['treasury_focus'],
            'common_challenges' => $profile['typical_challenges'],
            'industry_context' => $this->get_industry_context( $industry ),
        ];
    }

    /**
     * Get industry-specific context
     */
    private function get_industry_context( $industry ) {
        $contexts = [
            'manufacturing' => [
                'cash_flow_pattern' => 'cyclical with seasonal variations',
                'working_capital_intensity' => 'high inventory and receivables',
                'regulatory_environment' => 'environmental and safety regulations',
                'treasury_priorities' => 'supply chain financing, FX risk management'
            ],
            'technology' => [
                'cash_flow_pattern' => 'rapid growth with high volatility',
                'working_capital_intensity' => 'low physical assets, high cash burn',
                'regulatory_environment' => 'data privacy and cybersecurity',
                'treasury_priorities' => 'liquidity management, investment optimization'
            ],
            'retail' => [
                'cash_flow_pattern' => 'highly seasonal and promotional',
                'working_capital_intensity' => 'inventory-heavy with payment timing',
                'regulatory_environment' => 'consumer protection and payment regulations',
                'treasury_priorities' => 'cash forecasting, payment processing optimization'
            ],
            // Add more industries as needed
        ];
        
        return $contexts[$industry] ?? [
            'cash_flow_pattern' => 'varies by business model',
            'working_capital_intensity' => 'moderate',
            'regulatory_environment' => 'standard compliance requirements',
            'treasury_priorities' => 'operational efficiency and risk management'
        ];
    }

    /**
     * Build comprehensive prompt with research context
     */
    private function build_comprehensive_prompt( $user_inputs, $roi_data, $company_research, $industry_analysis, $tech_landscape ) {
        $company_name = $user_inputs['company_name'] ?? 'the company';
        $company_profile = $company_research['company_profile'];
        
        $prompt = "You are a senior treasury technology consultant creating a comprehensive business case for {$company_name}.\n\n";
        
        // Company Context
        $prompt .= "COMPANY PROFILE:\n";
        $prompt .= "Company: {$company_name}\n";
        $prompt .= "Industry: " . ($user_inputs['industry'] ?? 'Not specified') . "\n";
        $prompt .= "Revenue Size: " . ($user_inputs['company_size'] ?? 'Not specified') . "\n";
        $prompt .= "Business Stage: {$company_profile['business_stage']}\n";
        $prompt .= "Key Characteristics: {$company_profile['key_characteristics']}\n";
        $prompt .= "Treasury Priorities: {$company_profile['treasury_priorities']}\n";
        $prompt .= "Common Challenges: {$company_profile['common_challenges']}\n\n";
        
        // Current State Analysis
        $prompt .= "CURRENT TREASURY OPERATIONS:\n";
        $prompt .= "Weekly Reconciliation Hours: " . ($user_inputs['hours_reconciliation'] ?? 0) . "\n";
        $prompt .= "Weekly Cash Positioning Hours: " . ($user_inputs['hours_cash_positioning'] ?? 0) . "\n";
        $prompt .= "Banking Relationships: " . ($user_inputs['num_banks'] ?? 0) . "\n";
        $prompt .= "Treasury Team Size: " . ($user_inputs['ftes'] ?? 0) . " FTEs\n";
        $prompt .= "Key Pain Points: " . implode(', ', $user_inputs['pain_points'] ?? []) . "\n\n";
        
        // ROI Analysis
        $prompt .= "PROJECTED ROI ANALYSIS:\n";
        $prompt .= "Conservative Scenario: $" . number_format($roi_data['conservative']['total_annual_benefit'] ?? 0) . "\n";
        $prompt .= "Base Case Scenario: $" . number_format($roi_data['base']['total_annual_benefit'] ?? 0) . "\n";
        $prompt .= "Optimistic Scenario: $" . number_format($roi_data['optimistic']['total_annual_benefit'] ?? 0) . "\n\n";
        
        // Strategic Context
        if ( !empty($user_inputs['business_objective']) ) {
            $prompt .= "Primary Business Objective: " . $user_inputs['business_objective'] . "\n";
        }
        if ( !empty($user_inputs['implementation_timeline']) ) {
            $prompt .= "Implementation Timeline: " . $user_inputs['implementation_timeline'] . "\n";
        }
        if ( !empty($user_inputs['budget_range']) ) {
            $prompt .= "Budget Range: " . $user_inputs['budget_range'] . "\n";
        }
        
        $prompt .= "\nCreate a comprehensive business case analysis that includes:\n\n";
        
        $prompt .= "REQUIRED JSON STRUCTURE (respond with ONLY this JSON, no other text):\n";
        $prompt .= json_encode([
            'executive_summary' => [
                'strategic_positioning' => "2-3 sentences about {$company_name}'s strategic position and readiness for treasury technology",
                'business_case_strength' => 'Strong|Moderate|Compelling',
                'key_value_drivers' => [
                    "Primary value driver specific to {$company_name}",
                    "Secondary value driver for their industry/size",
                    "Third strategic benefit for their situation"
                ],
                'executive_recommendation' => "Clear recommendation with specific next steps for {$company_name}",
                'confidence_level' => 'decimal between 0.7-0.95'
            ],
            'operational_analysis' => [
                'current_state_assessment' => [
                    'efficiency_rating' => 'Excellent|Good|Fair|Poor',
                    'benchmark_comparison' => "How {$company_name} compares to industry peers",
                    'capacity_utilization' => "Analysis of current team capacity and bottlenecks"
                ],
                'process_inefficiencies' => [
                    [
                        'process' => 'specific process name',
                        'impact' => 'High|Medium|Low',
                        'description' => 'detailed description of inefficiency'
                    ]
                ],
                'automation_opportunities' => [
                    [
                        'area' => 'process area',
                        'complexity' => 'High|Medium|Low',
                        'potential_hours_saved' => 'number'
                    ]
                ]
            ],
            'industry_insights' => [
                'sector_trends' => "Key trends affecting {$company_name}'s industry",
                'competitive_benchmarks' => "How competitors are leveraging treasury technology",
                'regulatory_considerations' => "Relevant compliance and regulatory factors"
            ],
            'technology_recommendations' => [
                'primary_solution' => [
                    'category' => 'recommended category',
                    'rationale' => "Why this fits {$company_name} specifically",
                    'key_features' => ['feature1', 'feature2', 'feature3']
                ],
                'implementation_approach' => [
                    'phase_1' => 'initial implementation focus',
                    'phase_2' => 'expansion phase',
                    'success_metrics' => ['metric1', 'metric2', 'metric3']
                ]
            ],
            'financial_analysis' => [
                'investment_breakdown' => [
                    'software_licensing' => 'estimated cost range',
                    'implementation_services' => 'estimated cost range',
                    'training_change_management' => 'estimated cost range'
                ],
                'payback_analysis' => [
                    'payback_months' => 'number',
                    'roi_3_year' => 'percentage',
                    'npv_analysis' => 'positive value justification'
                ]
            ],
            'risk_mitigation' => [
                'implementation_risks' => [
                    "Risk specific to {$company_name}'s situation",
                    "Industry-specific risk consideration",
                    "Technology adoption risk"
                ],
                'mitigation_strategies' => [
                    'risk_1_mitigation' => 'specific mitigation approach',
                    'risk_2_mitigation' => 'specific mitigation approach'
                ]
            ],
            'next_steps' => [
                "Immediate action for {$company_name} leadership",
                "Vendor evaluation and selection process",
                "Implementation planning and timeline",
                "Change management and training program"
            ]
        ], JSON_PRETTY_PRINT);
        
        return $prompt;
    }

    /**
     * Create enhanced fallback with detailed analysis
     */
    private function create_enhanced_fallback( $user_inputs, $roi_data ) {
        $company_name = $user_inputs['company_name'] ?? 'Your Company';
        $company_research = $this->conduct_company_research( $user_inputs );
        $company_profile = $company_research['company_profile'];
        
        return [
            'company_name' => $company_name,
            'analysis_date' => current_time( 'Y-m-d' ),
            'executive_summary' => [
                'strategic_positioning' => sprintf(
                    "%s, as a %s company in the %s sector, is well-positioned to realize significant operational improvements through treasury technology modernization. The current manual processes and %s pain points indicate clear automation opportunities that align with industry best practices.",
                    $company_name,
                    $company_profile['business_stage'],
                    $user_inputs['industry'] ?? 'business',
                    count($user_inputs['pain_points'] ?? [])
                ),
                'business_case_strength' => 'Strong',
                'key_value_drivers' => [
                    sprintf("Process automation will eliminate %s's current manual bottlenecks, saving %.1f hours weekly", 
                        $company_name, 
                        ($user_inputs['hours_reconciliation'] ?? 0) + ($user_inputs['hours_cash_positioning'] ?? 0)
                    ),
                    sprintf("Real-time cash visibility will improve %s's working capital optimization by 15-25%%", $company_name),
                    sprintf("Reduced error rates will enhance %s's operational reliability and stakeholder confidence", $company_name)
                ],
                'executive_recommendation' => sprintf(
                    "%s should proceed with treasury technology implementation focusing on %s to achieve projected annual benefits of $%s while improving operational efficiency and risk management capabilities.",
                    $company_name,
                    $company_profile['treasury_priorities'],
                    number_format($roi_data['base']['total_annual_benefit'] ?? 0)
                ),
                'confidence_level' => 0.85
            ],
            'operational_analysis' => [
                'current_state_assessment' => [
                    'efficiency_rating' => $this->calculate_efficiency_rating( $user_inputs ),
                    'benchmark_comparison' => sprintf(
                        "%s's current treasury operations show %s automation levels compared to industry benchmarks, with significant opportunity for process improvement.",
                        $company_name,
                        $this->get_automation_level( $user_inputs )
                    ),
                    'capacity_utilization' => sprintf(
                        "The treasury team is operating at high manual capacity with %.1f weekly hours spent on routine tasks that could be automated.",
                        ($user_inputs['hours_reconciliation'] ?? 0) + ($user_inputs['hours_cash_positioning'] ?? 0)
                    )
                ],
                'process_inefficiencies' => $this->analyze_process_inefficiencies( $user_inputs ),
                'automation_opportunities' => $this->identify_automation_opportunities( $user_inputs )
            ],
            'industry_insights' => [
                'sector_trends' => $this->get_industry_trends( $user_inputs['industry'] ?? '' ),
                'competitive_benchmarks' => $this->get_competitive_benchmarks( $user_inputs ),
                'regulatory_considerations' => $this->get_regulatory_considerations( $user_inputs['industry'] ?? '' )
            ],
            'financial_analysis' => $this->build_financial_analysis( $roi_data, $user_inputs ),
            'confidence_level' => 0.85,
            'enhanced_fallback' => true
        ];
    }

    /**
     * Call OpenAI with retry logic
     */
    private function call_openai_with_retry( $model, $prompt, $max_retries = 2 ) {
        for ( $attempt = 1; $attempt <= $max_retries; $attempt++ ) {
            $response = $this->call_openai( $model, $prompt );
            
            if ( !is_wp_error( $response ) ) {
                return $response;
            }
            
            error_log( "RTBCB: OpenAI attempt {$attempt} failed: " . $response->get_error_message() );
            
            if ( $attempt < $max_retries ) {
                sleep( $attempt ); // Progressive backoff
            }
        }
        
        return $response; // Return last error
    }

    /**
     * Enhanced OpenAI call with better error handling
     */
    private function call_openai( $model, $prompt ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', 'OpenAI API key not configured' );
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $body = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a senior treasury technology consultant. Provide detailed, research-driven analysis in the exact JSON format requested. Do not include any text outside the JSON structure.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.4,
            'max_tokens' => 4000,
            'response_format' => ['type' => 'json_object'] // Force JSON response
        ];

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode( $body ),
            'timeout' => 120, // Longer timeout for comprehensive analysis
        ];

        error_log( 'RTBCB: Making OpenAI API call with model: ' . $model );
        
        $response = wp_remote_post( $endpoint, $args );
        
        if ( is_wp_error( $response ) ) {
            error_log( 'RTBCB: HTTP request failed: ' . $response->get_error_message() );
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        
        error_log( 'RTBCB: OpenAI response status: ' . $status_code );
        
        if ( $status_code !== 200 ) {
            $error_data = json_decode( $response_body, true );
            $error_message = $error_data['error']['message'] ?? 'Unknown API error';
            error_log( 'RTBCB: OpenAI API error: ' . $error_message );
            return new WP_Error( 'openai_api_error', $error_message );
        }

        return $response;
    }

    // Helper methods for enhanced analysis
    private function calculate_efficiency_rating( $user_inputs ) {
        $total_hours = ($user_inputs['hours_reconciliation'] ?? 0) + ($user_inputs['hours_cash_positioning'] ?? 0);
        $team_size = $user_inputs['ftes'] ?? 1;
        $hours_per_fte = $team_size > 0 ? $total_hours / $team_size : $total_hours;
        
        if ( $hours_per_fte < 5 ) return 'Good';
        if ( $hours_per_fte < 15 ) return 'Fair'; 
        return 'Poor';
    }

    private function get_automation_level( $user_inputs ) {
        $pain_points = $user_inputs['pain_points'] ?? [];
        if ( in_array( 'manual_processes', $pain_points ) ) return 'low';
        if ( in_array( 'integration_issues', $pain_points ) ) return 'moderate';
        return 'moderate';
    }

    private function analyze_process_inefficiencies( $user_inputs ) {
        $inefficiencies = [];
        $pain_points = $user_inputs['pain_points'] ?? [];
        
        foreach ( $pain_points as $pain_point ) {
            switch ( $pain_point ) {
                case 'manual_processes':
                    $inefficiencies[] = [
                        'process' => 'Bank Reconciliation',
                        'impact' => 'High',
                        'description' => 'Manual data entry and reconciliation processes consume significant time and introduce error risk'
                    ];
                    break;
                case 'poor_visibility':
                    $inefficiencies[] = [
                        'process' => 'Cash Position Reporting',
                        'impact' => 'High', 
                        'description' => 'Lack of real-time visibility delays decision-making and impacts working capital optimization'
                    ];
                    break;
                case 'forecast_accuracy':
                    $inefficiencies[] = [
                        'process' => 'Cash Forecasting',
                        'impact' => 'Medium',
                        'description' => 'Inaccurate forecasting leads to suboptimal cash positioning and increased financing costs'
                    ];
                    break;
            }
        }
        
        return $inefficiencies;
    }

    private function identify_automation_opportunities( $user_inputs ) {
        $opportunities = [];
        
        if ( ($user_inputs['hours_reconciliation'] ?? 0) > 0 ) {
            $opportunities[] = [
                'area' => 'Bank Reconciliation',
                'complexity' => 'Medium',
                'potential_hours_saved' => round( ($user_inputs['hours_reconciliation'] ?? 0) * 0.7, 1 )
            ];
        }
        
        if ( ($user_inputs['hours_cash_positioning'] ?? 0) > 0 ) {
            $opportunities[] = [
                'area' => 'Cash Position Management',
                'complexity' => 'Low',
                'potential_hours_saved' => round( ($user_inputs['hours_cash_positioning'] ?? 0) * 0.5, 1 )
            ];
        }
        
        return $opportunities;
    }

    private function get_industry_trends( $industry ) {
        $trends = [
            'manufacturing' => 'Digital transformation initiatives are driving treasury automation adoption, with focus on supply chain finance integration and sustainability reporting',
            'technology' => 'Rapid growth companies are prioritizing real-time cash management and automated forecasting to support scaling operations',
            'retail' => 'Omnichannel payment processing and seasonal cash flow management are key drivers for treasury technology investment'
        ];
        
        return $trends[$industry] ?? 'Companies across industries are modernizing treasury operations to improve efficiency and risk management';
    }

    private function build_financial_analysis( $roi_data, $user_inputs ) {
        $base_benefit = $roi_data['base']['total_annual_benefit'] ?? 0;
        $estimated_cost = $base_benefit * 0.4; // Rough estimate
        
        return [
            'investment_breakdown' => [
                'software_licensing' => '$' . number_format( $estimated_cost * 0.6 ) . ' - $' . number_format( $estimated_cost * 0.8 ),
                'implementation_services' => '$' . number_format( $estimated_cost * 0.15 ) . ' - $' . number_format( $estimated_cost * 0.25 ),
                'training_change_management' => '$' . number_format( $estimated_cost * 0.05 ) . ' - $' . number_format( $estimated_cost * 0.15 )
            ],
            'payback_analysis' => [
                'payback_months' => $base_benefit > 0 ? round( 12 * $estimated_cost / $base_benefit ) : 24,
                'roi_3_year' => round( ( $base_benefit * 3 - $estimated_cost ) / $estimated_cost * 100 ),
                'npv_analysis' => 'Positive NPV of $' . number_format( $base_benefit * 2.5 - $estimated_cost ) . ' over 3 years at 10% discount rate'
            ]
        ];
    }
}

