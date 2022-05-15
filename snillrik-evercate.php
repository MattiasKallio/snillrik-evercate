<?php
defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );
/**
* Plugin Name: Snillrik Evercate
* Plugin URI: http://www.snillrik.se/
* Description: This is a plugin for adding some Evercate functionallity to WP and WooCommerce.
* Version: 0.7.1
* Author: Mattias Kallio
* Author URI: http://www.snillrik.se
* License: GPL v2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain: snevercate
* Domain Path: /languages
*/

DEFINE("SNILLRIK_EV_PLUGIN_URL", plugin_dir_url(__FILE__));
DEFINE("SNILLRIK_EV_DIR", plugin_dir_path(__FILE__));
DEFINE("SNILLRIK_EV_NAME", "snevercate");

//ToDo: Add to settings page
DEFINE("SNILLRIK_EV_ADD_TITLE", true); //add business title to customer.
DEFINE("SNILLRIK_EV_NO_ADDRESS_ON_VIRTUAL", true); //To remove address fields on virtual products.
DEFINE("SNILLRIK_EV_WOO_HOOK","woocommerce_order_status_completed"); //for when Woo should post to Evercate 

require_once SNILLRIK_EV_DIR . 'classes/settings.php';
require_once SNILLRIK_EV_DIR . 'classes/evercate_api.php';
require_once SNILLRIK_EV_DIR . 'classes/evercate_user.php';
require_once SNILLRIK_EV_DIR . 'classes/woocommerce.php';

/**
 * Adds script for admin
 */
function snillrik_snevercate_add_admin_scripts(){
    wp_enqueue_style('snillrik-snevercate-front', SNILLRIK_EV_PLUGIN_URL . 'css/front.css');
    wp_enqueue_style('snillrik-snevercate-admin', SNILLRIK_EV_PLUGIN_URL . 'css/admin.css');
    wp_enqueue_script('snillrik-snevercate-admin-script', SNILLRIK_EV_PLUGIN_URL . 'js/admin-main.js', array('jquery'));
}
add_action('admin_enqueue_scripts', 'snillrik_snevercate_add_admin_scripts');

function snillrik_plugin_init() {
    load_plugin_textdomain( SNILLRIK_EV_NAME, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'snillrik_plugin_init');
