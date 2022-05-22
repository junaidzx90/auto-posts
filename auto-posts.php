<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.fiverr.com/junaidzx90
 * @since             1.0.0
 * @package           Auto_posts
 *
 * @wordpress-plugin
 * Plugin Name:       Auto Posts
 * Plugin URI:        https://www.fiverr.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Developer Junayed
 * Author URI:        https://www.fiverr.com/junaidzx90
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       auto-posts
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'AUTO_POSTS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 */
function activate_auto_posts() {
	
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_auto_posts() {
	
}

register_activation_hook( __FILE__, 'activate_auto_posts' );
register_deactivation_hook( __FILE__, 'deactivate_auto_posts' );

require_once plugin_dir_path( __FILE__ )."inc/class-auto-posts.php";

function run_auto_posts() {

	$plugin = new Auto_Posts();
	$plugin->run();

}
run_auto_posts();