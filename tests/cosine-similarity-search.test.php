<?php
require_once __DIR__ . '/../inc/class-rtbcb-rag.php';

if ( ! defined( 'ARRAY_A' ) ) {
    define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! function_exists( 'maybe_unserialize' ) ) {
    function maybe_unserialize( $data ) {
        return $data;
    }
}

class WPDB_Stub {
    public $prefix = '';
    public $last_query = '';

    public function prepare( $query, ...$args ) {
        $this->last_query = vsprintf( $query, $args );
        return $this->last_query;
    }

    public function get_var( $sql ) {
        $this->last_query = $sql;
        return 'rtbcb_rag_index';
    }

    public function get_results( $sql, $output ) {
        $this->last_query = $sql;
        return [
            [ 'type' => 'vendor', 'ref_id' => '1', 'embedding' => [ 1, 0 ], 'metadata' => [] ],
            [ 'type' => 'vendor', 'ref_id' => '2', 'embedding' => [ 0.8, 0.2 ], 'metadata' => [] ],
            [ 'type' => 'vendor', 'ref_id' => '3', 'embedding' => [ 0, 1 ], 'metadata' => [] ],
        ];
    }
}

global $wpdb;
$wpdb = new WPDB_Stub();

class RTBCB_RAG_Test extends RTBCB_RAG {
    public function __construct() {}
    public function run_search( $query_embedding, $top_k ) {
        $method = new ReflectionMethod( RTBCB_RAG::class, 'cosine_similarity_search' );
        $method->setAccessible( true );
        return $method->invoke( $this, $query_embedding, $top_k );
    }
}

$rag     = new RTBCB_RAG_Test();
$results = $rag->run_search( [ 1, 0 ], 2 );

if ( 2 !== count( $results ) ) {
    echo "Expected 2 results, got " . count( $results ) . "\n";
    exit( 1 );
}

if ( $results[0]['score'] < $results[1]['score'] ) {
    echo "Results are not sorted by score\n";
    exit( 1 );
}

if ( false === stripos( $wpdb->last_query, 'limit' ) ) {
    echo "LIMIT clause missing from query\n";
    exit( 1 );
}

echo "cosine-similarity-search.test.php passed\n";
