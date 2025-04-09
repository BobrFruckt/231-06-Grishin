<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp_database' );

/** Database username */
define( 'DB_USER', 'wp_user' );

/** Database password */
define( 'DB_PASSWORD', 'J8@q#Pz3!kL0' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '7>;`);WJl} N63(GNNzgzz_tHk#%Cf@YN94 adrxp+za/.[W^xT~+CNv^{3o=sGs' );
define( 'SECURE_AUTH_KEY',  ',jnj-3[Q=Vz,,^J0eO+|D0?Ni#H>|ta-*eUQi0F <o0VfaGz? $Z9qO}N2fS#R2j' );
define( 'LOGGED_IN_KEY',    ':NzBmkez;#|h;p1x;Ys;J1QA44OF#pkF$kTQe>=FR[=dVh0OoKCkw)5?}!+7^3HX' );
define( 'NONCE_KEY',        '&*W:;cTQbaNf~Ou>%*[}&lm>kj+CDm!lW3T!t3J1-+OzXX17#ta-)u/cM<#`g9sp' );
define( 'AUTH_SALT',        '[:%Vk!VnESKQWR8xS6g9/iid9-|OAp(BPa6(*I@`S9eeps^an1?f~,5Sfw=H-zW%' );
define( 'SECURE_AUTH_SALT', 'W,sE#9V,fRwEjWkfqMHs?+6UKY@!33QC/1C};I{gA*#a<NR96DFjP{] sX:2zBQ^' );
define( 'LOGGED_IN_SALT',   'G56@kO+e#yY ez!K!:e;%eo2QOk5D><?Q4aJWT7*h1v&sb|lPl)^/)~neT>[en)g' );
define( 'NONCE_SALT',       'pwB{dz^&INQDK!dr^%PQ]rKG^Ng1Aetb#1%^@tcsJBsk)Nz}x&Z0KY6,^i0;%(.5' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
