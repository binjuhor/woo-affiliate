<?php
class WooAffiliate_Affiliate {

    public static function init() {
        // Hook into WooCommerce order completion
        add_action('woocommerce_thankyou', [__CLASS__, 'track_affiliate_commission']);

        // Add affiliate link to My Account page
        add_action('woocommerce_account_dashboard', [__CLASS__, 'display_affiliate_link']);

        // Add scripts and styles for affiliate functionality
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }

    public static function track_affiliate_commission($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Check for referral cookie
        if (isset($_COOKIE['wooaffiliate_referral'])) {
            $referrer_id = intval($_COOKIE['wooaffiliate_referral']);

            // Calculate commission
            $commission_percentage = get_option('wooaffiliate_commission_percentage', 10);
            $commission = ($order->get_total() * $commission_percentage) / 100;

            // Save commission data (example: store in user meta)
            $current_commission = get_user_meta($referrer_id, 'wooaffiliate_commission', true);
            update_user_meta($referrer_id, 'wooaffiliate_commission', $current_commission + $commission);
        }
    }

    public static function display_affiliate_link() {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return;
        }

        $affiliate_link = add_query_arg('ref', $user_id, home_url());

        echo '<div class="wooaffiliate-box">';
        echo '<h3>' . __('Your Affiliate Link', 'wooaffiliate') . '</h3>';
        echo '<p>' . __('Share this link with friends. When they make a purchase, you\'ll earn commission and they\'ll get a discount!', 'wooaffiliate') . '</p>';
        echo '<div class="wooaffiliate-link-container">';
        echo '<input type="text" id="wooaffiliate-link" value="' . esc_url($affiliate_link) . '" readonly style="width: 100%; margin-bottom: 10px;">';
        echo '<button type="button" class="button wooaffiliate-copy-btn" data-clipboard-target="#wooaffiliate-link">' . __('Copy to Clipboard', 'wooaffiliate') . '</button>';
        echo '<span class="wooaffiliate-copy-success" style="display:none; margin-left: 10px; color: green;">' . __('Copied!', 'wooaffiliate') . '</span>';
        echo '</div>';
        echo '</div>';
    }

    public static function enqueue_scripts() {
        if (is_account_page()) {
            wp_enqueue_script('clipboard-js', 'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js', array(), '2.0.8', true);
            wp_add_inline_script('clipboard-js', '
                jQuery(document).ready(function($) {
                    var clipboard = new ClipboardJS(".wooaffiliate-copy-btn");
                    clipboard.on("success", function(e) {
                        $(".wooaffiliate-copy-success").fadeIn().delay(2000).fadeOut();
                        e.clearSelection();
                    });
                });
            ');

            wp_add_inline_style('woocommerce-inline', '
                .wooaffiliate-box {
                    background: #f8f8f8;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 20px;
                    margin: 20px 0;
                }
                .wooaffiliate-link-container {
                    display: flex;
                    flex-wrap: wrap;
                    align-items: center;
                }
                #wooaffiliate-link {
                    background: #fff;
                    padding: 10px;
                    border: 1px solid #ddd;
                }
            ');
        }
    }
}

// Initialize the affiliate functionality
WooAffiliate_Affiliate::init();
