<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, and ABSPATH. You can find more information by visiting
 * {@link https://codex.wordpress.org/Editing_wp-config.php Editing wp-config.php}
 * Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'mgb');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'b_<=U|N)P-5B w*G+y@?{%8)N+Bk+Yc_s<mk-M?b]H~ Cdh&k^`%~I4A%wy]E*M%');
define('SECURE_AUTH_KEY',  'qn|7ZsT&py.}%<|v=7Lx_0_#E4(2m Y&e%Z2jyzSz`LC%i:JmOnp![!r~X3%-TZ-');
define('LOGGED_IN_KEY',    'o(AM!wnYFL!kU+`@<Iv6V DWn:*GB:XhI+B=#]{E(@jA+,1gf^T?knhVX|xx;7D=');
define('NONCE_KEY',        '63b6}q!(k?9@Y2y;(htN}3R^!;K.hg9)|5+8;N-lH#E)5eteGCs]+PjP-J<J&r.)');
define('AUTH_SALT',        'On+/vzWSH]lj5:{dSiVE.`;4B{y|+i#yVQ`2P6:W}j]|umORN3s+ #=(S1rjU}hR');
define('SECURE_AUTH_SALT', '^fyQd?3tZS&[n08{fzaI_U][tQ7<+h}LwBHwd3hO|h38`~SkZS;U;MMe26A7_DRo');
define('LOGGED_IN_SALT',   'Y+JVRSLWgR-eG|8Vz+e-%SOc8!-GOHV|5lvKIido@`nI<_X(d_^3|+nO*!lFT]ka');
define('NONCE_SALT',       '`D-IT||(`QJ/{4>(W9SNZ3p8?Kd8z@`.ti}Q$}E`?|&]HCJ|2<2tm65XC^R IWkc');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'mg_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
