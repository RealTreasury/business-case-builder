<?php
defined( 'ABSPATH' ) || exit;

/**
 * Optimized prompt implementations for RTBCB_LLM class.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
class RTBCB_LLM_Optimized extends RTBCB_LLM {

       /**
        * Build optimized system prompt for enrichment.
        *
        * @return string System prompt.
        */
       protected function build_enrichment_system_prompt() {
               return <<<'SYSTEM'
You are a senior treasury technology consultant with 15+ years of experience conducting comprehensive company and industry research for Fortune 500 clients.

Your expertise includes:
- Treasury operations analysis and digital transformation strategy
- Industry benchmarking and competitive positioning analysis
- Technology vendor evaluation and solution architecture
- Financial modeling and ROI analysis for technology investments
- Risk assessment and change management planning

Your task: Enrich company profiles with strategic insights that inform treasury technology investment decisions.

CRITICAL REQUIREMENTS:
- Respond ONLY with valid JSON matching the exact schema provided
- Base analysis on provided company data and industry best practices
- Provide specific, actionable insights rather than generic advice
- Use realistic estimates when exact data is unavailable
- Maintain professional consulting tone throughout analysis

OUTPUT FORMAT: Return only a single JSON object with no additional text outside the JSON structure.
SYSTEM;
       }

       /**
        * Build structured user prompt with clear sections.
        *
        * @param array $user_inputs User inputs.
        * @return string User prompt.
        */
       protected function build_enrichment_user_prompt( $user_inputs ) {
               $pain_points_formatted = $this->format_pain_points( $user_inputs['pain_points'] ?? [] );

               return <<<PROMPT
## Treasury Technology Analysis Request

### Company Profile
- **Company Name**: {$user_inputs['company_name']}
- **Industry Sector**: {$user_inputs['industry']}
- **Revenue Size**: {$user_inputs['company_size']}
- **Business Objective**: {$user_inputs['business_objective']}
- **Implementation Timeline**: {$user_inputs['implementation_timeline']}
- **Budget Range**: {$user_inputs['budget_range']}

### Current Treasury Operations
- **Team Size**: {$user_inputs['ftes']} FTEs
- **Weekly Reconciliation Hours**: {$user_inputs['hours_reconciliation']}
- **Weekly Cash Positioning Hours**: {$user_inputs['hours_cash_positioning']}
- **Banking Relationships**: {$user_inputs['num_banks']}
- **Key Pain Points**: {$pain_points_formatted}

### Analysis Requirements
Provide deep, actionable insights to support:
1. **ROI Calculation Foundations**: Baseline efficiency metrics and improvement potential
2. **Technology Category Selection**: Solution complexity and feature requirements
3. **Implementation Planning**: Timeline, resource requirements, and risk factors
4. **Strategic Positioning**: Competitive context and business value alignment

Focus on treasury-specific challenges and opportunities within the {$user_inputs['industry']} industry for a {$user_inputs['company_size']} organization.

### Required JSON Output Schema
```json
{
  "company_profile": {
    "enhanced_description": "string - 2-3 sentence business model and market position summary",
    "business_model": "string - primary revenue streams and operational model",
    "market_position": "string - competitive standing and differentiators",
    "maturity_level": "basic|developing|strategic|optimized",
    "treasury_maturity": {
      "current_state": "string - assessment of current treasury processes and capabilities",
      "sophistication_level": "manual|semi_automated|automated|strategic",
      "key_gaps": ["array of 3-4 specific operational gaps"],
      "automation_readiness": "low|medium|high"
    },
    "strategic_context": {
      "primary_challenges": ["array of 3-4 main business challenges"],
      "growth_objectives": ["array of 2-3 strategic growth goals"],
      "competitive_pressures": ["array of 2-3 external market pressures"],
      "regulatory_environment": "string - key regulatory considerations affecting treasury"
    }
  },
  "industry_context": {
    "sector_analysis": {
      "market_dynamics": "string - current market conditions and trends",
      "growth_trends": "string - industry growth patterns and outlook",
      "disruption_factors": ["array of 2-3 disruptive trends or technologies"],
      "technology_adoption": "laggard|follower|mainstream|leader"
    },
    "benchmarking": {
      "typical_treasury_setup": "string - industry standard treasury practices and team sizes",
      "common_pain_points": ["array of 3-4 frequent industry challenges"],
      "technology_penetration": "low|medium|high",
      "investment_patterns": "string - typical technology investment approaches and budgets"
    },
    "regulatory_landscape": {
      "key_regulations": ["array of 2-3 principal regulations"],
      "compliance_complexity": "low|medium|high|very_high",
      "upcoming_changes": ["array of anticipated regulatory updates"]
    }
  },
  "strategic_insights": {
    "technology_readiness": "not_ready|ready|urgent_need",
    "investment_justification": "weak|moderate|strong|compelling",
    "implementation_complexity": "low|medium|high|very_high",
    "expected_benefits": {
      "efficiency_gains": "string - specific efficiency improvements with estimated impact",
      "risk_reduction": "string - risk mitigation benefits and quantified impact",
      "strategic_value": "string - broader business benefits beyond cost savings",
      "competitive_advantage": "string - competitive positioning improvements"
    },
    "critical_success_factors": ["array of 4-5 key implementation success factors"],
    "potential_obstacles": ["array of 4-5 likely implementation challenges"]
  },
  "enrichment_metadata": {
    "confidence_level": "number between 0.7 and 0.95",
    "data_sources": ["array of information sources and analysis methods used"],
    "analysis_depth": "surface|moderate|comprehensive",
    "recommendations_priority": "low|medium|high|urgent"
  }
}
```
PROMPT;
       }

       /**
        * Format pain points for better prompt clarity.
        *
        * @param array|string $pain_points Pain points list.
        * @return string Comma-separated pain points or default message.
        */
       private function format_pain_points( $pain_points ) {
               if ( empty( $pain_points ) ) {
                       return 'None specified';
               }

               $formatted = array_map(
                       static function( $point ) {
                               return str_replace( '_', ' ', ucwords( $point, '_' ) );
                       },
                       (array) $pain_points
               );

               return implode( ', ', $formatted );
       }

       /**
        * Build comprehensive business case prompt.
        *
        * @param array  $user_inputs      User inputs.
        * @param array  $roi_data         ROI calculation data.
        * @param array  $company_research Company research data.
        * @param array  $industry_analysis Industry analysis.
        * @param string $tech_landscape   Technology landscape description.
        * @return string Prompt.
        */
       protected function build_comprehensive_prompt( $user_inputs, $roi_data, $company_research, $industry_analysis, $tech_landscape ) {
               $company_name        = $user_inputs['company_name'] ?? 'the company';
               $company_profile     = $company_research['company_profile'] ?? [];
               $business_stage      = $company_profile['business_stage'] ?? 'Not specified';
               $key_characteristics = $company_profile['key_characteristics'] ?? 'Not specified';
               $treasury_priorities = $company_profile['treasury_priorities'] ?? 'Not specified';

               return <<<PROMPT
# Treasury Technology Business Case Generation

## Executive Brief
Create a strategic business case for {$company_name} that justifies treasury technology investment with:
- **Clear ROI Projections**: Risk-adjusted scenarios with quantified benefits
- **Strategic Value Drivers**: Key business value creation opportunities  
- **Implementation Roadmap**: Practical phases with success metrics and timelines
- **Risk Mitigation Strategy**: Comprehensive risk assessment with mitigation approaches

## Company Intelligence

### Company Profile
- **Name**: {$company_name}
- **Industry**: {$user_inputs['industry']}
- **Revenue Size**: {$user_inputs['company_size']}
- **Business Stage**: {$business_stage}
- **Key Characteristics**: {$key_characteristics}
- **Treasury Priorities**: {$treasury_priorities}

### Current Treasury Operations
- **Team Size**: {$user_inputs['ftes']} FTEs
- **Weekly Reconciliation**: {$user_inputs['hours_reconciliation']} hours
- **Weekly Cash Positioning**: {$user_inputs['hours_cash_positioning']} hours  
- **Banking Relationships**: {$user_inputs['num_banks']}
- **Primary Pain Points**: {$this->format_pain_points( $user_inputs['pain_points'] ?? [] )}

### Strategic Context
- **Business Objective**: {$user_inputs['business_objective']}
- **Implementation Timeline**: {$user_inputs['implementation_timeline']}
- **Budget Range**: {$user_inputs['budget_range']}

## Market Intelligence

### Industry Analysis
{$this->format_industry_analysis( $industry_analysis )}

### Technology Landscape
{$tech_landscape}

## Financial Projections
- **Conservative Scenario**: \\${number_format( $roi_data['conservative']['total_annual_benefit'] ?? 0 )}
- **Base Case Scenario**: \\${number_format( $roi_data['base']['total_annual_benefit'] ?? 0 )}
- **Optimistic Scenario**: \\${number_format( $roi_data['optimistic']['total_annual_benefit'] ?? 0 )}

## Required Comprehensive Analysis

Return a complete JSON business case covering:

1. **Executive Summary**: Strategic positioning, value drivers, and executive recommendation
2. **Company Intelligence**: Enhanced company profile and industry context analysis
3. **Operational Insights**: Current state assessment and improvement opportunities
4. **Financial Analysis**: Detailed ROI scenarios, investment breakdown, and payback analysis
5. **Technology Strategy**: Recommended solutions and implementation roadmap
6. **Risk Analysis**: Implementation risks and comprehensive mitigation strategies
7. **Action Plan**: Immediate steps, short-term milestones, and long-term objectives

### Complete Business Case Schema
```json
{
  "executive_summary": {
    "strategic_positioning": "string - 3-4 sentences on strategic rationale and business context",
    "key_value_drivers": ["array of 4 primary value creation opportunities"],
    "business_case_strength": "weak|moderate|strong|compelling",
    "executive_recommendation": "string - clear next steps with rationale and timeline",
    "confidence_level": "number between 0.80 and 0.95"
  },
  "company_intelligence": {
    "enriched_profile": {
      "name": "{$company_name}",
      "enhanced_description": "string - comprehensive business model and market position",
      "maturity_level": "basic|developing|strategic|optimized",
      "key_challenges": ["array of 3-4 current operational challenges"],
      "strategic_priorities": ["array of 2-3 top strategic priorities"]
    },
    "industry_context": {
      "competitive_pressure": "low|moderate|high",
      "regulatory_environment": "string - regulatory considerations and compliance requirements",
      "sector_trends": "string - key industry trends affecting treasury operations"
    },
    "maturity_assessment": [
      {
        "dimension": "string - specific assessment area",
        "current_level": "string - current capability level",
        "target_level": "string - desired future state",
        "gap_analysis": "string - specific gaps and improvement requirements"
      }
    ]
  },
  "operational_insights": {
    "current_state_assessment": ["array of 3-4 key current state observations"],
    "process_improvements": [
      {
        "process": "string - specific process name",
        "current_state": "string - how process works today",
        "improved_state": "string - how process will work post-implementation",
        "impact": "string - quantified business impact where possible"
      }
    ],
    "automation_opportunities": [
      {
        "opportunity": "string - specific automation opportunity",
        "complexity": "low|medium|high",
        "potential_savings": "string - time and cost savings description",
        "implementation_effort": "string - effort and resources required"
      }
    ]
  },
  "financial_analysis": {
    "roi_scenarios": {
      "conservative": {
        "total_annual_benefit": "number",
        "labor_savings": "number",
        "fee_savings": "number",
        "error_reduction": "number"
      },
      "base": {
        "total_annual_benefit": "number",
        "labor_savings": "number",
        "fee_savings": "number",
        "error_reduction": "number"
      },
      "optimistic": {
        "total_annual_benefit": "number",
        "labor_savings": "number",
        "fee_savings": "number",
        "error_reduction": "number"
      }
    },
    "investment_breakdown": [
      {
        "category": "string - investment category",
        "amount": "number - estimated cost",
        "description": "string - detailed description"
      }
    ],
    "payback_analysis": [
      {
        "scenario": "string - scenario name",
        "payback_months": "number",
        "roi_3_year": "number - percentage",
        "npv": "number - net present value"
      }
    ],
    "sensitivity_analysis": [
      {
        "factor": "string - factor name",
        "impact_percentage": "number - impact on ROI",
        "probability": "number - likelihood between 0-1"
      }
    ]
  },
  "technology_strategy": {
    "recommended_category": "string - solution category",
    "category_details": {
      "name": "string - category name",
      "features": ["array of key features needed"],
      "ideal_for": "string - why this fits the company profile"
    },
    "implementation_roadmap": [
      {
        "phase": "string - phase name",
        "timeline": "string - duration estimate",
        "activities": ["array of key phase activities"],
        "success_criteria": ["array of success measures"]
      }
    ],
    "vendor_considerations": ["array of 4-5 vendor evaluation factors"]
  },
  "industry_insights": {
    "sector_trends": ["array of 3-4 industry trends affecting treasury"],
    "competitive_benchmarks": ["array of 2-3 competitive benchmarking insights"],
    "regulatory_considerations": ["array of 2-3 regulatory factors"]
  },
  "risk_analysis": {
    "implementation_risks": ["array of 5-6 key implementation risks"],
    "mitigation_strategies": ["array of 5-6 specific risk mitigation approaches"],
    "success_factors": ["array of 5-6 critical success factors"]
  },
  "action_plan": {
    "immediate_steps": ["array of immediate actions for next 30 days"],
    "short_term_milestones": ["array of milestones for 3-6 months"], 
    "long_term_objectives": ["array of objectives for 6+ months"]
  }
}
```
PROMPT;
       }

       /**
        * Format industry analysis for better prompt integration.
        *
        * @param array $industry_analysis Industry analysis data.
        * @return string Formatted analysis block.
        */
       private function format_industry_analysis( $industry_analysis ) {
               if ( empty( $industry_analysis ) ) {
                       return 'No specific industry analysis provided.';
               }

               $formatted = '';

               if ( ! empty( $industry_analysis['analysis'] ) ) {
                       $formatted .= "**Market Analysis**: " . $industry_analysis['analysis'] . "\n\n";
               }

               if ( ! empty( $industry_analysis['recommendations'] ) ) {
                       $formatted .= "**Industry Recommendations**:\n";
                       foreach ( $industry_analysis['recommendations'] as $rec ) {
                               $formatted .= '- ' . $rec . "\n";
                       }
                       $formatted .= "\n";
               }

               if ( ! empty( $industry_analysis['references'] ) ) {
                       $formatted .= "**Reference Sources**: " . implode( ', ', $industry_analysis['references'] );
               }

               return trim( $formatted );
       }

       /**
        * Enhanced model selection with complexity scoring.
        *
        * @param array                     $user_inputs    User inputs.
        * @param callable|array|Traversable $context_chunks Context chunks.
        * @param string                    $analysis_type  Analysis type.
        * @return string Selected model.
        */
       protected function select_optimal_model( $user_inputs, $context_chunks, $analysis_type = 'basic' ) {
               $complexity_factors = [
                       'company_size_factor' => $this->get_company_size_complexity( $user_inputs['company_size'] ?? '' ),
                       'pain_points_factor' => count( $user_inputs['pain_points'] ?? [] ) * 0.1,
                       'context_factor' => count( $context_chunks ) * 0.15,
                       'analysis_type_factor' => $this->get_analysis_type_complexity( $analysis_type ),
                       'industry_factor' => $this->get_industry_complexity( $user_inputs['industry'] ?? '' ),
               ];

               $total_complexity = array_sum( $complexity_factors );

if ( class_exists( 'RTBCB_Logger' ) ) {
RTBCB_Logger::log(
'complexity_calculation',
[
'total'   => $total_complexity,
'factors' => $complexity_factors,
]
);
}

               if ( $total_complexity >= 0.8 ) {
                       $selected_model = $this->get_model( 'premium' );
                       $reasoning      = 'High complexity analysis requiring premium model capabilities';
               } elseif ( $total_complexity >= 0.5 ) {
                       $selected_model = $this->get_model( 'advanced' );
                       $reasoning      = 'Moderate complexity requiring advanced model capabilities';
               } else {
                       $selected_model = $this->get_model( 'mini' );
                       $reasoning      = 'Standard complexity suitable for mini model';
               }

if ( class_exists( 'RTBCB_Logger' ) ) {
RTBCB_Logger::log(
'model_selected',
[
'model'      => $selected_model,
'complexity' => $total_complexity,
'reason'     => $reasoning,
]
);
}

               return $selected_model;
       }

       /**
        * Get complexity factor for company size.
        *
        * @param string $size Company size.
        * @return float Complexity factor.
        */
       private function get_company_size_complexity( $size ) {
               $complexity_map = [
                       '<$50M'       => 0.2,
                       '$50M-$500M'  => 0.4,
                       '$500M-$2B'   => 0.6,
                       '>$2B'       => 0.8,
               ];

               return $complexity_map[ $size ] ?? 0.3;
       }

       /**
        * Get complexity factor for analysis type.
        *
        * @param string $type Analysis type.
        * @return float Complexity factor.
        */
       private function get_analysis_type_complexity( $type ) {
               $complexity_map = [
                       'fast'          => 0.1,
                       'basic'         => 0.3,
                       'comprehensive' => 0.7,
                       'strategic'     => 0.5,
               ];

               return $complexity_map[ $type ] ?? 0.3;
       }

       /**
        * Get complexity factor for industry.
        *
        * @param string $industry Industry name.
        * @return float Complexity factor.
        */
       private function get_industry_complexity( $industry ) {
               $complexity_map = [
                       'financial_services' => 0.3,
                       'healthcare'         => 0.25,
                       'manufacturing'      => 0.2,
                       'technology'         => 0.15,
                       'retail'             => 0.15,
               ];

               return $complexity_map[ $industry ] ?? 0.2;
       }
}

