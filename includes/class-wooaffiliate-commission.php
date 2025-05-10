<?php
class WooAffiliate_Commission {

    public static function init() {
        // Add My Account menu for commission management
        add_action('woocommerce_account_menu_items', [__CLASS__, 'add_commission_menu']);
        add_action('init', [__CLASS__, 'add_commission_endpoint']);
        add_action('woocommerce_account_commission_endpoint', [__CLASS__, 'commission_page']);
    }

    public static function add_commission_menu($items) {
        $items['commission'] = __('My Commissions', 'wooaffiliate');
        return $items;
    }

    public static function add_commission_endpoint() {
        add_rewrite_endpoint('commission', EP_ROOT | EP_PAGES);
    }

    public static function commission_page() {
        $user_id = get_current_user_id();
        $commission = get_user_meta($user_id, 'wooaffiliate_commission', true);
        $commission = $commission ? $commission : 0;

        echo '<h2>' . __('My Commissions', 'wooaffiliate') . '</h2>';
        echo '<p>' . sprintf(__('Your current commission balance is: %s', 'wooaffiliate'), wc_price($commission)) . '</p>';

        echo '<form method="post">';
        echo '<input type="hidden" name="wooaffiliate_action" value="convert_to_discount">';
        wp_nonce_field('wooaffiliate_action', 'wooaffiliate_nonce');
        echo '<button type="submit" class="button">' . __('Convert to Discount Code', 'wooaffiliate') . '</button>';
        echo '</form>';

        echo '<form method="post">';
        echo '<input type="hidden" name="wooaffiliate_action" value="request_withdrawal">';
        wp_nonce_field('wooaffiliate_action', 'wooaffiliate_nonce');
        echo '<button type="submit" class="button">' . __('Request Withdrawal', 'wooaffiliate') . '</button>';
        echo '</form>';

        self::handle_post_actions($user_id, $commission);
    }

    public static function handle_post_actions($user_id, $commission) {
        if (!isset($_POST['wooaffiliate_action']) || !isset($_POST['wooaffiliate_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['wooaffiliate_nonce'], 'wooaffiliate_action')) {
            return;
        }

        $action = sanitize_text_field($_POST['wooaffiliate_action']);

        if ($action === 'convert_to_discount' && $commission > 0) {
            $discount_code = 'AFF' . strtoupper(wp_generate_password(8, false));
            $amount = $commission;

            // Create WooCommerce coupon
            $coupon = new WC_Coupon();
            $coupon->set_code($discount_code);
            $coupon->set_amount($amount);
            $coupon->set_discount_type('fixed_cart');
            $coupon->set_individual_use(true);
            $coupon->save();

            // Reset commission
            update_user_meta($user_id, 'wooaffiliate_commission', 0);

            echo '<p>' . sprintf(__('Discount code %s created for %s', 'wooaffiliate'), $discount_code, wc_price($amount)) . '</p>';
        } elseif ($action === 'request_withdrawal' && $commission > 0) {
            // Notify admin for manual withdrawal
            $admin_email = get_option('admin_email');
            wp_mail($admin_email, __('Withdrawal Request', 'wooaffiliate'), sprintf(__('User %d requested a withdrawal of %s', 'wooaffiliate'), $user_id, wc_price($commission)));

            echo '<p>' . __('Withdrawal request sent to admin.', 'wooaffiliate') . '</p>';
        }
    }
}

// Initialize the commission functionality
WooAffiliate_Commission::init();
