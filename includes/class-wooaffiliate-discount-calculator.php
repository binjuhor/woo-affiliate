<?php
class WooAffiliate_Discount_Calculator {

    public static function init() {
        add_action('woocommerce_cart_calculate_fees', [__CLASS__, 'add_affiliate_discount']);
    }

    public static function add_affiliate_discount($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        if (!isset($_COOKIE['wooaffiliate_referral'])) {
            return;
        }

        $total_discount = 0;

        foreach ($cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $product_subtotal = $cart_item['line_subtotal'];

            $discount_percentage = WooAffiliate_Category_Discounts::get_category_discount_percentage($product_id);

            $item_discount = ($product_subtotal * $discount_percentage) / 100;
            $total_discount += $item_discount;
        }

        if ($total_discount > 0) {
            $cart->add_fee(__('Affiliate Discount', 'wooaffiliate'), $total_discount * -1, true);
        }
    }
}

WooAffiliate_Discount_Calculator::init();
