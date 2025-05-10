<?php
class WooAffiliate_Discount_Calculator {

    public static function init() {
        // Add discount to cart
        add_action('woocommerce_cart_calculate_fees', [__CLASS__, 'add_affiliate_discount']);
    }

    public static function add_affiliate_discount($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        // Check if referral cookie exists
        if (!isset($_COOKIE['wooaffiliate_referral'])) {
            return;
        }

        // Get discount percentage
        $discount_percentage = WooAffiliate_Discount_Display::get_applied_discount_percentage();

        if ($discount_percentage > 0) {
            $discount_amount = $cart->get_subtotal() * ($discount_percentage / 100) * -1; // Negative amount for discount
            $cart->add_fee(__('Affiliate Discount', 'wooaffiliate'), $discount_amount, true);
        }
    }
}

// Initialize the discount calculator
WooAffiliate_Discount_Calculator::init();
