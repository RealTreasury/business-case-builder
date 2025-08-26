<?php
if ( ! function_exists( 'wp_remote_get' ) ) {
    function wp_remote_get( $url, $args = [] ) {
        $headers = '';
        if ( ! empty( $args['headers'] ) && is_array( $args['headers'] ) ) {
            foreach ( $args['headers'] as $key => $value ) {
                $headers .= $key . ': ' . $value . "\r\n";
            }
        }
        $context = stream_context_create(
            [
                'http' => [
                    'method'        => 'GET',
                    'header'        => $headers,
                    'timeout'       => $args['timeout'] ?? 5,
                    'ignore_errors' => true,
                ],
            ]
        );
        $body = @file_get_contents( $url, false, $context );
        $response_headers = [];
        $status_code      = 0;
        if ( isset( $http_response_header ) ) {
            foreach ( $http_response_header as $header_line ) {
                if ( preg_match( '#^HTTP/\\d+\.\\d+\\s+(\\d+)#', $header_line, $matches ) ) {
                    $status_code = intval( $matches[1] );
                } elseif ( strpos( $header_line, ':' ) !== false ) {
                    list( $name, $value ) = explode( ':', $header_line, 2 );
                    $response_headers[ strtolower( trim( $name ) ) ] = trim( $value );
                }
            }
        }
        return [
            'body'     => $body,
            'response' => [ 'code' => $status_code ],
            'headers'  => $response_headers,
        ];
    }
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
    function wp_remote_retrieve_response_code( $response ) {
        return $response['response']['code'] ?? 0;
    }
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
    function wp_remote_retrieve_body( $response ) {
        return $response['body'] ?? '';
    }
}

if ( ! function_exists( 'wp_remote_retrieve_headers' ) ) {
    function wp_remote_retrieve_headers( $response ) {
        return $response['headers'] ?? [];
    }
}
