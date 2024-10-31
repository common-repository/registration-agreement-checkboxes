<?php
/**
 * Plugin Name: Registration Agreement Checkboxes
 * Version: 1.0.2
 * Author: Greg Bialowas / Young-Pros.com
 * Author URI: http://www.young-pros.com
 * Description: Displays checkboxes to be ticked when registering a new user. It also gives an option to turn on double validation of the password.
 * Text Domain: yp-agreement-checkboxes
 * Domain Path: /lang
 *
 * @package yp-agreement-checkboxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'REGISTER_AGREEMENT_CHBXS__MAIN_FILE', __FILE__ );
define( 'REGISTER_AGREEMENT_CHBXS__VERSION', '1.0.2' );

/**
 * Load functions
 */
require_once dirname( REGISTER_AGREEMENT_CHBXS__MAIN_FILE ) . '/assets/inc/functions.php';

/**
 * Load classes
 */
require_once dirname( REGISTER_AGREEMENT_CHBXS__MAIN_FILE ) . '/classes/class-gbyp-registration-checkboxes.php';

/**
 * Load translations.
 */
function yp_chcbx_reg__lang_load_translation() {
	load_plugin_textdomain( 'yp-agreement-checkboxes', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
}
add_action( 'plugins_loaded', 'yp_chcbx_reg__lang_load_translation' );
