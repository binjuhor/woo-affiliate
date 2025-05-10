<?php
/*
Plugin Name: Woo Affiliate
Description: Affiliate plugin for WooCommerce.
Version: 1.0.0
Author: Binjuhor
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
if (!defined('WOOAFFILIATE_PLUGIN_DIR')) {
    define('WOOAFFILIATE_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WOOAFFILIATE_PLUGIN_URL')) {
    define('WOOAFFILIATE_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Include necessary files
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-init.php';
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-affiliate.php';
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-commission.php';
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-category-discounts.php';
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-cookie-tracking.php';
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-discount-display.php';
require_once WOOAFFILIATE_PLUGIN_DIR . 'includes/class-wooaffiliate-discount-calculator.php';

// Initialize the plugin
add_action('plugins_loaded', ['WooAffiliate_Init', 'init']);
