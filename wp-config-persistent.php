<?php
defined( 'ABSPATH' ) || exit;

/**
 * Sample configuration enabling persistent MySQL connections.
 */

define( 'DB_NAME', 'database_name_here' );
define( 'DB_USER', 'username_here' );
define( 'DB_PASSWORD', 'password_here' );

define( 'DB_HOST', 'p:localhost' ); // Persistent connection

$table_prefix = 'wp_';

require_once ABSPATH . 'wp-settings.php';
