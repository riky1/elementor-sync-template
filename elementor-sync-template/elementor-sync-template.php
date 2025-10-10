<?php
/**
 * Plugin Name:   Elementor Sync Template
 * Description:   Crea template riutilizzabili e sincronizzati con Elementor, con la possibilità di personalizzare i contenuti per ogni pagina.
 * Plugin URI:    https://github.com/riky1/elementor-sync-template.git
 * Version:       1.2.1
 * Author:        riky1
 * Author URI:    https://example.com/
 * License:       GPL-2.0+
 * License URI:   http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:   elementor-sync-template
 * Domain Path:   /languages
 *
 * Requires PHP: 7.0
 * Requires Plugins: elementor
 * Elementor tested up to: 3.25.0
 * Elementor Pro tested up to: 3.25.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Main plugin file path constant – used by activation/deactivation hooks.
if ( ! defined( 'EST_PLUGIN_FILE' ) ) {
    define( 'EST_PLUGIN_FILE', __FILE__ );
}

define( 'EST_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'EST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize the plugin.
 * 
 * @since 1.0.0
 * @access public
 * @return void
 */
// Ensure main plugin code (classes and activation callbacks) is available
// at load time so activation/deactivation callbacks can be called by WP.
require_once __DIR__ . '/includes/plugin.php';

// Define version constant from the main plugin class
define( 'EST_VERSION', \Elementor_Sync_Template\Plugin::VERSION );

/**
 * Initialize the plugin.
 */
function elementor_sync_template() {

	// Run the plugin (the main plugin class is defined in includes/plugin.php)
	\Elementor_Sync_Template\Plugin::instance();

}

add_action( 'plugins_loaded', 'elementor_sync_template' );

// Register activation/deactivation hooks pointing to the functions defined in includes/plugin.php
if ( defined( 'EST_PLUGIN_FILE' ) ) {

	register_activation_hook( EST_PLUGIN_FILE, 'Elementor_Sync_Template\\est_plugin_activation' );
	register_deactivation_hook( EST_PLUGIN_FILE, 'Elementor_Sync_Template\\est_plugin_deactivation' );

} else {

	register_activation_hook( __FILE__, 'Elementor_Sync_Template\\est_plugin_activation' );
	register_deactivation_hook( __FILE__, 'Elementor_Sync_Template\\est_plugin_deactivation' );
	
}