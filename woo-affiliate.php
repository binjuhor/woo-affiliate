<?php
/*
Plugin Name: Woo Affiliate
Description: Affiliate plugin for WooCommerce.
Version: 1.0.1
Author: Binjuhor
Author URI: https://binjuhor.com
Text Domain: wooaffiliate
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WOOAFFILIATE_PLUGIN_DIR')) {
    define('WOOAFFILIATE_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WOOAFFILIATE_PLUGIN_URL')) {
    define('WOOAFFILIATE_PLUGIN_URL', plugin_dir_url(__FILE__));
}

function wooaffiliate_load_textdomain() {
    load_plugin_textdomain('wooaffiliate', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'wooaffiliate_load_textdomain', 0);

require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-init.php';
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-affiliate.php';
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-commission.php';
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-category-discounts.php';
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-cookie-tracking.php';
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-discount-display.php';
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-discount-calculator.php';
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-admin.php';

register_activation_hook(__FILE__, 'wooaffiliate_activate');

function wooaffiliate_activate() {
    WooAffiliate_Commission::add_commission_endpoint();
    flush_rewrite_rules();
}

add_action('plugins_loaded', ['WooAffiliate_Init', 'init']);
