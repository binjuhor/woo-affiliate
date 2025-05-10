<?php
class WooAffiliate_Discount_Display {

    public static function init() {
        // Display affiliate discount info in cart
        add_action('woocommerce_cart_totals_before_order_total', [__CLASS__, 'display_affiliate_discount_cart']);

        // Display affiliate discount info in checkout
        add_action('woocommerce_review_order_before_order_total', [__CLASS__, 'display_affiliate_discount_checkout']);

        // Add discount notice on cart page
        add_action('woocommerce_before_cart', [__CLASS__, 'display_discount_notice']);

        // Add discount notice on checkout page
        add_action('woocommerce_before_checkout_form', [__CLASS__, 'display_discount_notice']);
    }

    public static function display_affiliate_discount_cart() {
        if (!isset($_COOKIE['wooaffiliate_referral'])) {
            return;
        }

        $discount_percentage = self::get_applied_discount_percentage();

        if ($discount_percentage > 0) {
            echo '<tr class="wooaffiliate-discount-row">
                <th>' . __('Affiliate Discount', 'wooaffiliate') . ' (' . $discount_percentage . '%)</th>
                <td data-title="' . esc_attr__('Affiliate Discount', 'wooaffiliate') . '">' . wc_price(WC()->cart->get_subtotal() * ($discount_percentage / 100)) . '</td>
            </tr>';
        }
    }

    public static function display_affiliate_discount_checkout() {
        if (!isset($_COOKIE['wooaffiliate_referral'])) {
            return;
        }

        $discount_percentage = self::get_applied_discount_percentage();

        if ($discount_percentage > 0) {
            echo '<tr class="wooaffiliate-discount-row">
                <th>' . __('Affiliate Discount', 'wooaffiliate') . ' (' . $discount_percentage . '%)</th>
                <td>' . wc_price(WC()->cart->get_subtotal() * ($discount_percentage / 100)) . '</td>
            </tr>';
        }
    }

    public static function display_discount_notice() {
        if (!isset($_COOKIE['wooaffiliate_referral'])) {
            return;
        }

        $discount_percentage = self::get_applied_discount_percentage();

        if ($discount_percentage > 0) {
            $referrer_id = intval($_COOKIE['wooaffiliate_referral']);
            $referrer = get_user_by('id', $referrer_id);
            $referrer_name = $referrer ? $referrer->display_name : __('a friend', 'wooaffiliate');

            wc_print_notice(
                sprintf(__('You are receiving a %s%% discount through %s\'s affiliate link!', 'wooaffiliate'),
                $discount_percentage, $referrer_name),
                'notice'
            );
        }
    }

    public static function get_applied_discount_percentage() {
        // Default discount from plugin settings
        $default_discount = get_option('wooaffiliate_discount_percentage', 5);
        $discount_percentage = $default_discount;

        // Check cart items for category-specific discounts
        if (!is_null(WC()->cart)) {
            foreach (WC()->cart->get_cart() as $cart_item) {
                $product_id = $cart_item['product_id'];
                $product = wc_get_product($product_id);
                $categories = $product->get_category_ids();

                foreach ($categories as $category_id) {
                    $category_discount = get_term_meta($category_id, 'wooaffiliate_discount_percentage', true);
                    if (!empty($category_discount) && $category_discount > $discount_percentage) {
                        $discount_percentage = $category_discount;
                    }
                }
            }
        }

        return $discount_percentage;
    }
}

// Initialize the discount display functionality
WooAffiliate_Discount_Display::init();
