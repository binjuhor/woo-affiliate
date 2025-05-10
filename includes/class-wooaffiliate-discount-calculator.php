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

        $total_discount = 0;

        // Calculate discount for each cart item based on its category
        foreach ($cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $product_subtotal = $cart_item['line_subtotal'];

            // Get category-specific discount percentage for this product
            $discount_percentage = WooAffiliate_Category_Discounts::get_category_discount_percentage($product_id);

            // Calculate discount amount for this item
            $item_discount = ($product_subtotal * $discount_percentage) / 100;
            $total_discount += $item_discount;
        }

        if ($total_discount > 0) {
            // Apply negative fee (discount)
            $cart->add_fee(__('Affiliate Discount', 'wooaffiliate'), $total_discount * -1, true);
        }
    }
}

// Initialize the discount calculator
WooAffiliate_Discount_Calculator::init();
