<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/wp-stubs.php';

if ( ! function_exists( 'wp_kses_allowed_html' ) ) {
	function wp_kses_allowed_html( $context = 'post' ) {
	    return [];
	}
}

if ( ! function_exists( 'wp_kses' ) ) {
	function wp_kses( $string, $allowed_html ) {
	    return preg_replace_callback(
	        '#<([a-z0-9]+)([^>]*)>(.*?)</\\1>#is',
	        function ( $matches ) use ( $allowed_html ) {
	            $tag     = strtolower( $matches[1] );
	            $attrs   = $matches[2];
	            $content = $matches[3];

	            if ( ! isset( $allowed_html[ $tag ] ) ) {
	                return '';
	            }

	            $allowed_attrs = $allowed_html[ $tag ];
	            $new_attrs     = '';

	            if ( preg_match_all( '#([a-zA-Z0-9-:]+)="([^"]*)"#', $attrs, $attr_matches, PREG_SET_ORDER ) ) {
	                foreach ( $attr_matches as $attr ) {
	                    $name  = $attr[1];
	                    $value = $attr[2];
	                    if ( isset( $allowed_attrs[ $name ] ) ) {
	                        $new_attrs .= ' ' . $name . '="' . $value . '"';
	                    }
	                }
	            }

	            return '<' . $tag . $new_attrs . '>' . $content . '</' . $tag . '>';
	        },
	        $string
	    );
	}
}

require_once __DIR__ . '/../inc/helpers.php';

final class RTBCB_GetReportAllowedHtmlTest extends TestCase {
        public function test_allows_only_safe_script_types() {
            $allowed = rtbcb_get_report_allowed_html();
            $this->assertArrayHasKey( 'script', $allowed );
            $this->assertSame(
                [
                    'id'   => true,
                    'type' => [
                        'application/json'   => true,
                        'application/ld+json' => true,
                    ],
                ],
                $allowed['script']
            );

            $sanitized = rtbcb_sanitize_report_html( '<script type="application/json" id="init">{}</script>' );
            $this->assertSame( '<script type="application/json" id="init">{}</script>', $sanitized );
        }

        public function test_removes_disallowed_script_types_and_attributes() {
            $this->assertSame( '', rtbcb_sanitize_report_html( '<script type="text/javascript">alert(1)</script>' ) );
            $this->assertSame( '', rtbcb_sanitize_report_html( '<script src="//evil.test/evil.js"></script>' ) );
            $this->assertSame( '', rtbcb_sanitize_report_html( '<script id="no-type">alert(1)</script>' ) );
        }
}
