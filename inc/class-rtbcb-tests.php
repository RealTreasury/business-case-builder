<?php
/**
 * Test utilities for plugin health checks.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class RTBCB_Tests.
 */
class RTBCB_Tests {
    /**
     * Basic RAG search test.
     *
     * @return bool True if RAG search returns results, false otherwise.
     */
    public static function test_rag_search() {
        if ( ! class_exists( 'RTBCB_RAG' ) ) {
            return false;
        }

        try {
            $rag     = new RTBCB_RAG();
            $results = $rag->search_similar( 'test', 1 );
            return ! empty( $results );
        } catch ( Exception $e ) {
            error_log( 'RTBCB RAG Test Error: ' . $e->getMessage() );
            return false;
        }
    }
}

