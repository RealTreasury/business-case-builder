<?php
defined( 'ABSPATH' ) || exit;

/**
 * Implements Retrieval-Augmented Generation logic for the plugin.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_RAG.
 */
class RTBCB_RAG {
    /**
     * Constructor.
     */
    public function __construct() {
        RTBCB_DB::init();
    }

    /**
     * Rebuild the RAG index from portal data.
     *
     * @return void
     */
    public function rebuild_index() {
        // Ensure the index table exists.
        RTBCB_DB::init();

        // Get vendor data from Portal.
        $vendors = apply_filters( 'rt_portal_get_vendors', [] );
        $notes   = apply_filters( 'rt_portal_get_vendor_notes', [] );

        foreach ( $vendors as $vendor ) {
            $this->index_vendor( $vendor );
        }

        foreach ( $notes as $note ) {
            $this->index_note( $note );
        }

        // Seed sample data when no real data is available.
        if ( empty( $vendors ) && empty( $notes ) ) {
            $this->index_note(
                [
                    'id'      => 'sample',
                    'content' => 'Sample RAG data. Use the rebuild button after configuring the portal to refresh.',
                ]
            );
        }

        update_option( 'rtbcb_last_indexed', current_time( 'mysql' ) );
    }

    /**
     * Search the index for similar content.
     *
     * @param string $query Query string.
     * @param int    $top_k Number of results.
     *
     * @return array Matching rows.
     */
    public function search_similar( $query, $top_k = 3 ) {
        $query_embedding = $this->get_embedding( $query );
        return $this->cosine_similarity_search( $query_embedding, $top_k );
    }

    /**
     * Retrieve context metadata for a query.
     *
     * Calls {@see search_similar()} and returns only the metadata portion of
     * the results. If embeddings cannot be generated, an empty array is
     * returned.
     *
     * @param string $query Query string.
     * @param int    $top_k Number of results to retrieve.
     *
     * @return array List of metadata arrays.
     */
    public function get_context( $query, $top_k = 3 ) {
        $embedding = $this->get_embedding( $query );
        if ( empty( $embedding ) ) {
            return [];
        }

        $results = $this->search_similar( $query, $top_k );
        $context = [];

        foreach ( $results as $row ) {
            if ( isset( $row['metadata'] ) ) {
                $context[] = $row['metadata'];
            }
        }

        return $context;
    }

    /**
     * Index a vendor record.
     *
     * @param array $vendor Vendor data.
     *
     * @return void
     */
    private function index_vendor( $vendor ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_rag_index';

        $text = '';
        if ( is_array( $vendor ) ) {
            $text = ( $vendor['description'] ?? '' ) . ' ' . ( $vendor['name'] ?? '' );
        } else {
            $text = (string) $vendor;
        }

        $text_hash = hash( 'sha256', $text );
        $embedding = $this->get_embedding( $text );

        $wpdb->replace(
            $table_name,
            [
                'type'          => 'vendor',
                'ref_id'        => isset( $vendor['id'] ) ? sanitize_text_field( $vendor['id'] ) : '',
                'text_hash'     => $text_hash,
                'embedding'     => maybe_serialize( $embedding ),
                'metadata'      => maybe_serialize( $vendor ),
                'embedding_norm' => $this->calculate_embedding_norm( $embedding ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%f' ]
        );
    }

    /**
     * Index a note record.
     *
     * @param array $note Note data.
     *
     * @return void
     */
    private function index_note( $note ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_rag_index';

        $text = is_array( $note ) ? ( $note['content'] ?? '' ) : (string) $note;
        $text_hash = hash( 'sha256', $text );
        $embedding = $this->get_embedding( $text );

        $wpdb->replace(
            $table_name,
            [
                'type'          => 'note',
                'ref_id'        => isset( $note['id'] ) ? sanitize_text_field( $note['id'] ) : '',
                'text_hash'     => $text_hash,
                'embedding'     => maybe_serialize( $embedding ),
                'metadata'      => maybe_serialize( $note ),
                'embedding_norm' => $this->calculate_embedding_norm( $embedding ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%f' ]
        );
    }

    /**
     * Retrieve embedding vector for text.
     *
     * @param string $text Text to embed.
     *
     * @return array Embedding vector.
     */
    private function get_embedding( $text ) {
        $api_key = get_option( 'rtbcb_openai_api_key' );
        $model   = get_option( 'rtbcb_embedding_model', 'text-embedding-3-small' );

        if ( empty( $api_key ) ) {
            return [];
        }

        $endpoint = 'https://api.openai.com/v1/embeddings';
        $args     = [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode(
                [
                    'model' => $model,
                    'input' => $text,
                ]
            ),
				'timeout' => 600,
        ];

        $response = wp_remote_post( $endpoint, $args );
        if ( is_wp_error( $response ) ) {
            return [];
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        return $data['data'][0]['embedding'] ?? [];
    }

    /**
     * Search embeddings using cosine similarity.
     *
     * @param array $query_embedding Query embedding.
     * @param int   $top_k           Number of results.
     *
     * @return array
     */
    private function cosine_similarity_search( $query_embedding, $top_k ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_rag_index';

        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
        if ( $table_exists !== $table_name ) {
            return [];
        }

        $query_norm = $this->calculate_embedding_norm( $query_embedding );
        $limit      = max( $top_k * 10, $top_k );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT type, ref_id, embedding, metadata FROM {$table_name} ORDER BY ABS( embedding_norm - %f ) ASC LIMIT %d",
                $query_norm,
                $limit
            ),
            ARRAY_A
        );

        $scores = [];
        foreach ( $rows as $row ) {
            $embedding = maybe_unserialize( $row['embedding'] );
            if ( ! is_array( $embedding ) ) {
                continue;
            }
            $score    = $this->cosine_similarity( $query_embedding, $embedding );
            $scores[] = [
                'score'    => $score,
                'type'     => $row['type'],
                'ref_id'   => $row['ref_id'],
                'metadata' => maybe_unserialize( $row['metadata'] ),
            ];
        }

        usort(
            $scores,
            static function ( $a, $b ) {
                if ( $a['score'] === $b['score'] ) {
                    return 0;
                }
                return ( $a['score'] > $b['score'] ) ? -1 : 1;
            }
        );

        return array_slice( $scores, 0, $top_k );
    }

    /**
     * Calculate the Euclidean norm of an embedding vector.
     *
     * @param array $embedding Embedding vector.
     *
     * @return float Vector norm.
     */
    private function calculate_embedding_norm( $embedding ) {
        $norm = 0;
        foreach ( $embedding as $value ) {
            $norm += $value * $value;
        }
        return sqrt( $norm );
    }

    /**
     * Calculate cosine similarity between two vectors.
     *
     * @param array $a Vector A.
     * @param array $b Vector B.
     *
     * @return float Similarity score.
     */
    private function cosine_similarity( $a, $b ) {
        $dot = 0;
        $norm_a = 0;
        $norm_b = 0;
        $length  = min( count( $a ), count( $b ) );
        for ( $i = 0; $i < $length; $i++ ) {
            $dot    += $a[ $i ] * $b[ $i ];
            $norm_a += $a[ $i ] * $a[ $i ];
            $norm_b += $b[ $i ] * $b[ $i ];
        }
        if ( 0 === $norm_a || 0 === $norm_b ) {
            return 0;
        }
        return $dot / ( sqrt( $norm_a ) * sqrt( $norm_b ) );
    }
}

