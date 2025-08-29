<?php
/**
 * Fallback category recommendation engine.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provides simple category recommendations.
 */
class RTBCB_Category_Recommender {
    /**
     * Get all available categories.
     *
     * @return array Category map with names and descriptions.
     */
    public static function get_all_categories() {
        return [
            'cash_tools' => [
                'name'        => __( 'Cash Management Tools', 'rtbcb' ),
                'description' => __( 'Basic cash visibility and forecasting tools', 'rtbcb' ),
            ],
            'tms_lite'   => [
                'name'        => __( 'Treasury Management System (Lite)', 'rtbcb' ),
                'description' => __( 'Mid-tier treasury platform', 'rtbcb' ),
            ],
            'trms'       => [
                'name'        => __( 'Treasury & Risk Management System', 'rtbcb' ),
                'description' => __( 'Enterprise treasury platform', 'rtbcb' ),
            ],
        ];
    }

    /**
     * Recommend a category based on user inputs.
     *
     * @param array $user_inputs User input data.
     * @return array Recommendation details.
     */
    public static function recommend_category( $user_inputs ) {
        $categories = self::get_all_categories();

        $company_size = isset( $user_inputs['company_size'] ) ? sanitize_text_field( $user_inputs['company_size'] ) : '';
        $recommended  = 'tms_lite';

        if ( false !== strpos( $company_size, '<$50M' ) ) {
            $recommended = 'cash_tools';
        } elseif ( false !== strpos( $company_size, '>$2B' ) ) {
            $recommended = 'trms';
        }

        return [
            'recommended'   => $recommended,
            'category_info' => $categories[ $recommended ],
            'confidence'    => 0.75,
            'reasoning'     => sprintf(
                /* translators: %s: company size */
                __( 'Based on company size: %s', 'rtbcb' ),
                $company_size
            ),
        ];
    }

    /**
     * Retrieve information about a category.
     *
     * @param string $category Category key.
     * @return array|null Category data or null if not found.
     */
    public static function get_category_info( $category ) {
        $categories = self::get_all_categories();

        $key = sanitize_key( $category );
        return $categories[ $key ] ?? null;
    }
}
