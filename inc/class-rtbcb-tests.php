<?php
/**
 * Simple test utilities for Real Treasury Business Case Builder.
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
     * Test basic RAG search functionality.
     *
     * @return bool Whether search returned results.
     */
    public function test_rag_search() {
        if ( ! class_exists( 'RTBCB_RAG' ) ) {
            return false;
        }

        try {
            $rag     = new RTBCB_RAG();
            $results = $rag->search_similar( 'test', 1 );
            return ! empty( $results );
        } catch ( Exception $e ) {
            return false;
        }
    }
}
