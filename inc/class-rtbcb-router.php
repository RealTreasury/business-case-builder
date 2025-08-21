<?php
/**
 * Routes model selection based on request complexity.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_Router.
 */
class RTBCB_Router {
    /**
     * Route to the appropriate LLM model.
     *
     * @param array $inputs User inputs.
     * @param array $chunks Context chunks.
     *
     * @return string Model name.
     */
    public function route_model( $inputs, $chunks ) {
        $complexity = $this->calculate_complexity( $inputs, $chunks );
        $category   = RTBCB_Category_Recommender::recommend_category( $inputs )['recommended'];

        $model = get_option( 'rtbcb_mini_model', 'gpt-4o-mini' );

        if ( $complexity > 0.6 || 'trms' === $category ) {
            $model = get_option( 'rtbcb_premium_model', 'gpt-4o' );
        } elseif ( 'tms_lite' === $category && $complexity > 0.4 ) {
            $model = get_option( 'rtbcb_premium_model', 'gpt-4o' );
        }

        return $model;
    }

    /**
     * Calculate complexity score for model routing.
     *
     * @param array $inputs User inputs.
     * @param array $chunks Context chunks.
     *
     * @return float Complexity score between 0 and 1.
     */
    private function calculate_complexity( $inputs, $chunks ) {
        $score = 0;

        $pain_points = isset( $inputs['pain_points'] ) ? (array) $inputs['pain_points'] : [];
        $score      += count( $pain_points ) * 0.1;
        $score      += count( $chunks ) * 0.2;

        if ( isset( $inputs['company_size'] ) && '>$2B' === $inputs['company_size'] ) {
            $score += 0.3;
        }

        return min( 1.0, $score );
    }
}

