<?php
defined( 'ABSPATH' ) || exit;

/**
 * Treasury tool category recommendation engine.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_Category_Recommender.
 */
class RTBCB_Category_Recommender {
    /**
     * Category definitions with descriptions and criteria.
     *
     * @var array
     */
    public const CATEGORIES = [
        'cash_tools' => [
            'name'        => 'Cash Management Tools',
            'description' => 'Basic cash visibility and forecasting solutions for simple treasury operations.',
            'features'    => [
                'Real-time cash positioning',
                'Basic cash forecasting',
                'Bank balance aggregation',
                'Simple reporting',
                'Excel-based workflows',
            ],
            'ideal_for'   => 'Small to mid-market companies with straightforward cash management needs',
            'roi_range'   => [ 15000, 75000 ],
        ],
        'tms_lite'    => [
            'name'        => 'Treasury Management System (Lite)',
            'description' => 'Mid-tier treasury platform with automation and enhanced analytics.',
            'features'    => [
                'Automated bank reconciliation',
                'Advanced cash forecasting',
                'Payment processing',
                'Risk management basics',
                'Multi-entity support',
                'API integrations',
            ],
            'ideal_for'   => 'Mid-market to large companies with moderate complexity',
            'roi_range'   => [ 50000, 200000 ],
        ],
        'trms'        => [
            'name'        => 'Treasury & Risk Management System',
            'description' => 'Comprehensive enterprise treasury platform with full automation and risk management.',
            'features'    => [
                'Full treasury automation',
                'Sophisticated risk analytics',
                'Multi-currency support',
                'Complex derivatives handling',
                'Enterprise integrations',
                'Regulatory compliance tools',
                'Advanced forecasting models',
            ],
            'ideal_for'   => 'Large enterprises with complex, global treasury operations',
            'roi_range'   => [ 150000, 500000 ],
        ],
    ];

    /**
     * Recommend the most appropriate category based on user inputs.
     *
     * @param array $user_inputs User form data.
     * @return array Recommendation with scoring details.
     */
    public static function recommend_category( $user_inputs ) {
        $scores = [];

        foreach ( array_keys( self::CATEGORIES ) as $category ) {
            $scores[ $category ] = self::calculate_category_score( $category, $user_inputs );
        }

        arsort( $scores );
        $recommended = array_key_first( $scores );

        $category_info = self::translate_category_info( self::CATEGORIES[ $recommended ] );

        return [
            'recommended'   => $recommended,
            'category_info' => $category_info,
            'scores'        => $scores,
            'confidence'    => self::calculate_confidence( $scores ),
            'reasoning'     => self::generate_reasoning( $recommended, $user_inputs ),
            'alternatives'  => self::get_alternatives( $scores, $recommended ),
        ];
    }

    /**
     * Translate category info strings.
     *
     * @param array $category Category data.
     * @return array
     */
    private static function translate_category_info( $category ) {
        $category['name']        = __( $category['name'], 'rtbcb' );
        $category['description'] = __( $category['description'], 'rtbcb' );
        $category['features']    = array_map(
            function( $feature ) {
                return __( $feature, 'rtbcb' );
            },
            $category['features']
        );
        $category['ideal_for']   = __( $category['ideal_for'], 'rtbcb' );

        return $category;
    }

    /**
     * Calculate score for a specific category.
     *
     * @param string $category Category key.
     * @param array  $inputs   User inputs.
     * @return float Score (0-100).
     */
    private static function calculate_category_score( $category, $inputs ) {
        $score     = 0;
        $max_score = 0;

        $size_score = self::score_company_size( $category, $inputs['company_size'] ?? '' );
        $score     += $size_score * 0.4;
        $max_score += 40;

        $complexity_score = self::score_complexity( $category, $inputs );
        $score            += $complexity_score * 0.3;
        $max_score        += 30;

        $pain_score = self::score_pain_points( $category, $inputs['pain_points'] ?? [] );
        $score     += $pain_score * 0.2;
        $max_score += 20;

        $volume_score = self::score_volume( $category, $inputs );
        $score       += $volume_score * 0.1;
        $max_score   += 10;

        return ( $score / $max_score ) * 100;
    }

    /**
     * Score based on company size.
     *
     * @param string $category     Category key.
     * @param string $company_size Company size.
     * @return float Score (0-100).
     */
    private static function score_company_size( $category, $company_size ) {
        $size_scores = [
            'cash_tools' => [
                '<$50M'      => 100,
                '$50M-$500M' => 80,
                '$500M-$2B'  => 40,
                '>$2B'       => 20,
            ],
            'tms_lite'   => [
                '<$50M'      => 60,
                '$50M-$500M' => 100,
                '$500M-$2B'  => 90,
                '>$2B'       => 70,
            ],
            'trms'       => [
                '<$50M'      => 20,
                '$50M-$500M' => 50,
                '$500M-$2B'  => 90,
                '>$2B'       => 100,
            ],
        ];

        return $size_scores[ $category ][ $company_size ] ?? 50;
    }

    /**
     * Score based on operational complexity.
     *
     * @param string $category Category key.
     * @param array  $inputs   User inputs.
     * @return float Score (0-100).
     */
    private static function score_complexity( $category, $inputs ) {
        $num_banks   = intval( $inputs['num_banks'] ?? 0 );
        $ftes        = floatval( $inputs['ftes'] ?? 0 );
        $total_hours = floatval( $inputs['hours_reconciliation'] ?? 0 ) + floatval( $inputs['hours_cash_positioning'] ?? 0 );

        $complexity_index = 0;

        if ( $num_banks <= 3 ) {
            $complexity_index += 1;
        } elseif ( $num_banks <= 8 ) {
            $complexity_index += 2;
        } else {
            $complexity_index += 3;
        }

        if ( $ftes <= 2 ) {
            $complexity_index += 1;
        } elseif ( $ftes <= 5 ) {
            $complexity_index += 2;
        } else {
            $complexity_index += 3;
        }

        if ( $total_hours <= 10 ) {
            $complexity_index += 1;
        } elseif ( $total_hours <= 25 ) {
            $complexity_index += 2;
        } else {
            $complexity_index += 3;
        }

        $complexity_scores = [
            'cash_tools' => [ 1 => 100, 2 => 90, 3 => 80, 4 => 70, 5 => 60, 6 => 50, 7 => 40, 8 => 30, 9 => 20 ],
            'tms_lite'   => [ 1 => 60, 2 => 70, 3 => 80, 4 => 90, 5 => 100, 6 => 90, 7 => 80, 8 => 70, 9 => 60 ],
            'trms'       => [ 1 => 20, 2 => 30, 3 => 40, 4 => 50, 5 => 60, 6 => 70, 7 => 80, 8 => 90, 9 => 100 ],
        ];

        return $complexity_scores[ $category ][ $complexity_index ] ?? 50;
    }

    /**
     * Score based on pain points alignment.
     *
     * @param string $category    Category key.
     * @param array  $pain_points Selected pain points.
     * @return float Score (0-100).
     */
    private static function score_pain_points( $category, $pain_points ) {
        if ( empty( $pain_points ) ) {
            return 50;
        }

        $pain_point_mapping = [
            'cash_tools' => [
                'poor_visibility'    => 100,
                'manual_processes'   => 90,
                'forecast_accuracy'  => 80,
                'integration_issues' => 40,
                'compliance_risk'    => 50,
                'bank_fees'          => 60,
            ],
            'tms_lite'   => [
                'manual_processes'   => 100,
                'forecast_accuracy'  => 90,
                'poor_visibility'    => 85,
                'integration_issues' => 80,
                'bank_fees'          => 75,
                'compliance_risk'    => 70,
            ],
            'trms'       => [
                'compliance_risk'    => 100,
                'integration_issues' => 95,
                'forecast_accuracy'  => 90,
                'manual_processes'   => 85,
                'bank_fees'          => 80,
                'poor_visibility'    => 75,
            ],
        ];

        $total_score = 0;
        $count       = 0;

        foreach ( $pain_points as $pain_point ) {
            if ( isset( $pain_point_mapping[ $category ][ $pain_point ] ) ) {
                $total_score += $pain_point_mapping[ $category ][ $pain_point ];
                $count++;
            }
        }

        return $count > 0 ? ( $total_score / $count ) : 50;
    }

    /**
     * Score based on transaction volume indicators.
     *
     * @param string $category Category key.
     * @param array  $inputs   User inputs.
     * @return float Score (0-100).
     */
    private static function score_volume( $category, $inputs ) {
        $num_banks = intval( $inputs['num_banks'] ?? 0 );
        $ftes      = floatval( $inputs['ftes'] ?? 0 );

        $volume_indicator = ( $num_banks * 10 ) + ( $ftes * 20 );

        $volume_scores = [
            'cash_tools' => [ 0 => 100, 50 => 90, 100 => 70, 150 => 50, 200 => 30 ],
            'tms_lite'   => [ 0 => 50, 50 => 80, 100 => 100, 150 => 90, 200 => 70 ],
            'trms'       => [ 0 => 20, 50 => 40, 100 => 60, 150 => 80, 200 => 100 ],
        ];

        $closest_volume = 0;
        foreach ( array_keys( $volume_scores[ $category ] ) as $volume ) {
            if ( abs( $volume - $volume_indicator ) < abs( $closest_volume - $volume_indicator ) ) {
                $closest_volume = $volume;
            }
        }

        return $volume_scores[ $category ][ $closest_volume ];
    }

    /**
     * Calculate confidence level in recommendation.
     *
     * @param array $scores Category scores.
     * @return float Confidence (0-1).
     */
    private static function calculate_confidence( $scores ) {
        $score_values = array_values( $scores );
        $top_score    = $score_values[0];
        $second_score = $score_values[1] ?? 0;

        $score_gap  = $top_score - $second_score;
        $confidence = min( 1.0, $score_gap / 30 );

        return max( 0.5, $confidence );
    }

    /**
     * Generate human-readable reasoning for the recommendation.
     *
     * @param string $recommended Recommended category.
     * @param array  $inputs      User inputs.
     * @return string Reasoning text.
     */
    private static function generate_reasoning( $recommended, $inputs ) {
        $company_size = $inputs['company_size'] ?? '';
        $num_banks    = intval( $inputs['num_banks'] ?? 0 );
        $ftes         = floatval( $inputs['ftes'] ?? 0 );
        $pain_points  = $inputs['pain_points'] ?? [];

        $reasoning_parts = [];

        $size_reasoning = [
            'cash_tools' => [
                '<$50M'      => __( 'your company size aligns perfectly with cash management tools', 'rtbcb' ),
                '$50M-$500M' => __( 'cash tools can effectively serve mid-market companies like yours', 'rtbcb' ),
                '$500M-$2B'  => __( 'while larger, your company could benefit from focused cash tools', 'rtbcb' ),
                '>$2B'       => __( 'cash tools may be sufficient for specific business units', 'rtbcb' ),
            ],
            'tms_lite'   => [
                '<$50M'      => __( 'TMS-Lite provides room for growth as your company scales', 'rtbcb' ),
                '$50M-$500M' => __( 'your company size is ideal for a mid-tier TMS solution', 'rtbcb' ),
                '$500M-$2B'  => __( 'TMS-Lite offers the right balance of features for your scale', 'rtbcb' ),
                '>$2B'       => __( 'TMS-Lite can serve specific regions or business units effectively', 'rtbcb' ),
            ],
            'trms'       => [
                '<$50M'      => __( 'TRMS provides enterprise capabilities for future growth', 'rtbcb' ),
                '$50M-$500M' => __( 'TRMS offers comprehensive features as you scale operations', 'rtbcb' ),
                '$500M-$2B'  => __( 'your company scale requires enterprise-grade treasury management', 'rtbcb' ),
                '>$2B'       => __( 'enterprise-scale operations demand comprehensive TRMS capabilities', 'rtbcb' ),
            ],
        ];

        if ( isset( $size_reasoning[ $recommended ][ $company_size ] ) ) {
            $reasoning_parts[] = $size_reasoning[ $recommended ][ $company_size ];
        }

        if ( $num_banks > 5 || $ftes > 3 ) {
            $complexity_reasons = [
                'cash_tools' => __( 'streamlined cash tools can simplify your multi-bank operations', 'rtbcb' ),
                'tms_lite'   => __( 'automation features will significantly reduce your operational complexity', 'rtbcb' ),
                'trms'       => __( 'comprehensive automation is essential for your complex operations', 'rtbcb' ),
            ];
            $reasoning_parts[] = $complexity_reasons[ $recommended ];
        }

        if ( ! empty( $pain_points ) ) {
            $pain_reasons = [
                'cash_tools' => [
                    'poor_visibility'  => __( 'cash tools excel at providing real-time visibility', 'rtbcb' ),
                    'manual_processes' => __( 'basic automation will address your manual workflow challenges', 'rtbcb' ),
                ],
                'tms_lite'   => [
                    'manual_processes'   => __( 'mid-tier TMS automation will eliminate most manual processes', 'rtbcb' ),
                    'forecast_accuracy'  => __( 'advanced forecasting capabilities will improve accuracy', 'rtbcb' ),
                    'integration_issues' => __( 'API integrations will solve your system connectivity needs', 'rtbcb' ),
                ],
                'trms'       => [
                    'compliance_risk'   => __( 'enterprise compliance tools are crucial for your risk management', 'rtbcb' ),
                    'integration_issues'=> __( 'comprehensive integration capabilities will unify your systems', 'rtbcb' ),
                ],
            ];

            foreach ( $pain_points as $pain ) {
                if ( isset( $pain_reasons[ $recommended ][ $pain ] ) ) {
                    $reasoning_parts[] = $pain_reasons[ $recommended ][ $pain ];
                    break;
                }
            }
        }

        return sprintf(
            /* translators: %s: reasoning text */
            __( 'Based on your profile, %s.', 'rtbcb' ),
            implode( ', and ', $reasoning_parts )
        );
    }

    /**
     * Get alternative recommendations.
     *
     * @param array  $scores       All category scores.
     * @param string $recommended  Primary recommendation.
     * @return array Alternative categories.
     */
    private static function get_alternatives( $scores, $recommended ) {
        $alternatives = [];

        foreach ( $scores as $category => $score ) {
            if ( $category !== $recommended && $score > 60 ) {
                $alternatives[] = [
                    'category' => $category,
                    'info'     => self::translate_category_info( self::CATEGORIES[ $category ] ),
                    'score'    => $score,
                ];
            }
        }

        return array_slice( $alternatives, 0, 2 );
    }

    /**
     * Get category information by key.
     *
     * @param string $category_key Category key.
     * @return array|null Category information.
     */
    public static function get_category_info( $category_key ) {
        if ( ! isset( self::CATEGORIES[ $category_key ] ) ) {
            return null;
        }
        return self::translate_category_info( self::CATEGORIES[ $category_key ] );
    }

    /**
     * Get all available categories.
     *
     * @return array All categories.
     */
    public static function get_all_categories() {
        $translated = [];
        foreach ( self::CATEGORIES as $key => $category ) {
            $translated[ $key ] = self::translate_category_info( $category );
        }
        return $translated;
    }
}
