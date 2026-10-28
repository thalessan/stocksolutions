<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'stocks');

/** MySQL database username */
define('DB_USER', 'stocks');

/** MySQL database password */
define('DB_PASSWORD', 'tR6@u4*I');

/** MySQL hostname */
define('DB_HOST', 'stocks.mysql.uhserver.com');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

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
define('AUTH_KEY',         'y@8payuv9UVxqC)@DgDhyXHLj#Oq@ZwcIw!4@t6&3oPC^nQM*BzxS#&192wmA(66');
define('SECURE_AUTH_KEY',  'UzNBZjf5OuCt%T#Mf)Z%B%@(p#7*xM8JSEkhVZOsU#%1CT#xlYA9&(&)@8Uj3W^S');
define('LOGGED_IN_KEY',    'N0)y2*mVgERxi!v3l6hT!jGr%j24*xhT*&lPdjmAu^SKTyO@id&^PExOd6J!cn%8');
define('NONCE_KEY',        'Y*7!u0o(BLt6L&LOGv!99SFFL(OnWY4c*l#TkogDOk0%TjJgs3oxA4HhWGoxd#fJ');
define('AUTH_SALT',        'G7ric1PkxCZAmW69v87aZ7jKKPuXurti@KZM9led&jpgNZgEA(TwWhZp5^P^tSf%');
define('SECURE_AUTH_SALT', '(vmpxWivkao7nH9Fn2RROzQGKLCJni4lCjnt(j)kPjlX#On0FPmhEY&gqOo7sKJk');
define('LOGGED_IN_SALT',   'Q)hBDqA##1%lgK2M*YSirp5Ysn^L(Dd^)u4G9fG@TZCOP9t1cc%VfXibl6aodjtx');
define('NONCE_SALT',       'U@Px9UnFqT7dfd5%oBpp*Acd)YX5vzcPqOIzLwqtKfrL4udDaKtS4UOvy723M4Id');
/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'apswp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

define( 'WP_ALLOW_MULTISITE', true );

define ('FS_METHOD', 'direct');
