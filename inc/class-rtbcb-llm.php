<?php
/**
 * Handles LLM interactions for the plugin.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_LLM.
 */
class RTBCB_LLM {
    /**
     * OpenAI API key.
     *
     * @var string
     */
    private $api_key;

    /**
     * Available models.
     *
     * @var array
     */
    private $models;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->api_key = get_option( 'rtbcb_openai_api_key' );
        $this->models  = [
            'mini'      => get_option( 'rtbcb_mini_model', 'gpt-4o-mini' ),
            'premium'   => get_option( 'rtbcb_premium_model', 'gpt-4o' ),
            'embedding' => get_option( 'rtbcb_embedding_model', 'text-embedding-3-small' ),
        ];
    }

    /**
     * Generate a business case narrative using the LLM.
     *
     * @param array $user_inputs   User provided inputs.
     * @param array $roi_data      ROI calculation results.
     * @param array $context_chunks Context chunks for RAG.
     *
     * @return array Parsed LLM response.
     */
    public function generate_business_case( $user_inputs, $roi_data, $context_chunks = [] ) {
        $model    = $this->select_model( $user_inputs, $context_chunks );
        $prompt   = $this->build_prompt( $user_inputs, $roi_data, $context_chunks );
        $response = $this->call_openai( $model, $prompt );

        return $this->parse_response( $response );
    }

    /**
     * Select the appropriate model for the request.
     *
     * @param array $inputs User inputs.
     * @param array $chunks Context chunks.
     *
     * @return string Model name.
     */
    private function select_model( $inputs, $chunks ) {
        // TODO: Implement model routing logic via RTBCB_Router class
        $router = new RTBCB_Router();
        return $router->route_model( $inputs, $chunks );
    }

    /**
     * Build the prompt for the LLM.
     *
     * @param array $inputs User inputs.
     * @param array $roi_data ROI data.
     * @param array $chunks Context chunks.
     *
     * @return string Prompt text.
     */
    private function build_prompt( $inputs, $roi_data, $chunks ) {
        $context = $this->format_context_chunks( $chunks );

        $prompt  = "You are a CFO advisor creating a business case for treasury technology.\n\n";
        $prompt .= "CONTEXT from vendor research:\n{$context}\n\n";
        $prompt .= "USER SITUATION:\n";
        $prompt .= "- Company size: {$inputs['company_size']}\n";
        $prompt .= "- Current pain points: " . implode( ', ', $inputs['pain_points'] ) . "\n\n";
        $prompt .= "ROI ANALYSIS (assumption-driven, not vendor pricing):\n";
        $prompt .= "- Base case annual benefit: $" . number_format( $roi_data['base']['total_annual_benefit'] ) . "\n\n";
        $prompt .= "Create a concise business case narrative (≤180 words) with CFO tone.\n";
        $prompt .= "Include short citations for vendor facts using [vendor_id] format.\n\n";
        $prompt .= "Response must be valid JSON:\n";
        $prompt .= json_encode(
            [
                'narrative'            => 'string',
                'risks'                => [ 'string' ],
                'assumptions_explained'=> [ 'string' ],
                'citations'            => [ [ 'ref' => 'string', 'loc' => 'string' ] ],
                'next_actions'         => [ 'string' ],
                'confidence'           => 0.0,
            ],
            JSON_PRETTY_PRINT
        );

        return $prompt;
    }

    /**
     * Format context chunks into a single string.
     *
     * @param array $chunks Context chunks.
     *
     * @return string
     */
    private function format_context_chunks( $chunks ) {
        $formatted = '';
        foreach ( $chunks as $chunk ) {
            if ( empty( $chunk['text'] ) ) {
                continue;
            }
            $ref      = isset( $chunk['ref'] ) ? '[' . $chunk['ref'] . '] ' : '';
            $formatted .= $ref . $chunk['text'] . "\n";
        }
        return trim( $formatted );
    }

    /**
     * Call the OpenAI API with the provided prompt.
     *
     * @param string $model  Model name.
     * @param string $prompt Prompt to send.
     *
     * @return array|WP_Error Response object.
     */
    private function call_openai( $model, $prompt ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'rtbcb_no_api_key', __( 'Missing OpenAI API key.', 'rtbcb' ) );
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $args     = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode(
                [
                    'model'       => $model,
                    'messages'    => [
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.2,
                ]
            ),
            'timeout' => 60,
        ];

        return wp_remote_post( $endpoint, $args );
    }

    /**
     * Parse the OpenAI API response.
     *
     * @param array|WP_Error $response Response from OpenAI.
     *
     * @return array Parsed response or error details.
     */
    private function parse_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data['choices'][0]['message']['content'] ) ) {
            return [ 'error' => __( 'Invalid response from OpenAI API.', 'rtbcb' ) ];
        }

        $content = $data['choices'][0]['message']['content'];
        $decoded = json_decode( trim( $content ), true );

        if ( null === $decoded ) {
            return [ 'error' => __( 'Failed to decode model response.', 'rtbcb' ) ];
        }

        return $decoded;
    }
}

