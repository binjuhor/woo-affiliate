<?php
class WooAffiliate_Commission {

    public static function init() {
        add_action('woocommerce_account_menu_items', [__CLASS__, 'add_commission_menu'], 10);
        add_action('init', [__CLASS__, 'add_commission_endpoint']);
        add_action('woocommerce_account_commission_endpoint', [__CLASS__, 'commission_page']);

        add_filter('query_vars', [__CLASS__, 'add_commission_query_var'], 0);
        add_filter('woocommerce_get_query_vars', [__CLASS__, 'add_commission_wc_query_var']);
    }

    public static function add_commission_menu($items) {
        $logout_position = array_search('customer-logout', array_keys($items));

        if ($logout_position !== false) {
            $items_before_logout = array_slice($items, 0, $logout_position);
            $items_after_logout = array_slice($items, $logout_position);

            $items = $items_before_logout + ['commission' => __('My Commissions', 'wooaffiliate')] + $items_after_logout;
        } else {
            $items['commission'] = __('My Commissions', 'wooaffiliate');
        }

        return $items;
    }

    public static function add_commission_endpoint() {
        add_rewrite_endpoint('commission', EP_ROOT | EP_PAGES);
    }

    public static function add_commission_query_var($vars) {
        $vars[] = 'commission';
        return $vars;
    }

    public static function add_commission_wc_query_var($query_vars) {
        $query_vars['commission'] = 'commission';
        return $query_vars;
    }

    public static function commission_page() {
        $user_id = get_current_user_id();
        $commission = get_user_meta($user_id, 'wooaffiliate_commission', true);
        $commission = $commission ? $commission : 0;

        $has_pending_withdrawal = self::has_pending_withdrawal($user_id);

        echo '<h2>' . __('My Commissions', 'wooaffiliate') . '</h2>';
        echo '<p>' . sprintf(__('Your current commission balance is: %s', 'wooaffiliate'), wc_price($commission)) . '</p>';

        if ($commission > 0) {
            if (!$has_pending_withdrawal) {
                echo '<form method="post">';
                echo '<input type="hidden" name="wooaffiliate_action" value="convert_to_discount">';
                wp_nonce_field('wooaffiliate_action', 'wooaffiliate_nonce');
                echo '<button type="submit" class="button">' . __('Convert to Discount Code', 'wooaffiliate') . '</button>';
                echo '</form>';
            } else {
                echo '<p class="wooaffiliate-notice">' . __('You cannot convert to discount code while a withdrawal request is pending.', 'wooaffiliate') . '</p>';
            }

            if (!$has_pending_withdrawal) {
                echo '<form method="post" style="margin-top: 10px;">';
                echo '<input type="hidden" name="wooaffiliate_action" value="request_withdrawal">';
                wp_nonce_field('wooaffiliate_action', 'wooaffiliate_nonce');
                echo '<button type="submit" class="button">' . __('Request Withdrawal', 'wooaffiliate') . '</button>';
                echo '</form>';
            } else {
                echo '<p class="wooaffiliate-notice">' . __('You already have a pending withdrawal request.', 'wooaffiliate') . '</p>';
            }
        } else {
            echo '<p>' . __('You do not have any commission available for withdrawal or discount conversion.', 'wooaffiliate') . '</p>';
        }

        self::display_user_withdrawal_requests($user_id);
        self::handle_post_actions($user_id, $commission);
    }

    public static function has_pending_withdrawal($user_id) {
        $withdrawals = get_option('wooaffiliate_withdrawal_requests', array());

        if (isset($withdrawals[$user_id])) {
            foreach ($withdrawals[$user_id] as $request) {
                if ($request['status'] === 'pending') {
                    return true;
                }
            }
        }

        return false;
    }

    public static function display_user_withdrawal_requests($user_id) {
        $withdrawals = get_option('wooaffiliate_withdrawal_requests', array());

        if (!isset($withdrawals[$user_id]) || empty($withdrawals[$user_id])) {
            return;
        }

        echo '<h3>' . __('Your Withdrawal Requests', 'wooaffiliate') . '</h3>';
        echo '<table class="woocommerce-orders-table shop_table shop_table_responsive">';
        echo '<thead><tr>';
        echo '<th>' . __('Request ID', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Amount', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Date', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Status', 'wooaffiliate') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($withdrawals[$user_id] as $request_id => $request) {
            echo '<tr>';
            echo '<td>' . $request_id . '</td>';
            echo '<td>' . wc_price($request['amount']) . '</td>';
            echo '<td>' . date_i18n(get_option('date_format'), $request['date']) . '</td>';
            echo '<td>' . ($request['status'] === 'completed' ?
                '<span class="wooaffiliate-status-completed">' . __('Completed', 'wooaffiliate') . '</span>' :
                '<span class="wooaffiliate-status-pending">' . __('Pending', 'wooaffiliate') . '</span>'
            ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
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

            $coupon = new WC_Coupon();
            $coupon->set_code($discount_code);
            $coupon->set_amount($amount);
            $coupon->set_discount_type('fixed_cart');
            $coupon->set_individual_use(true);

            $coupon->set_usage_limit(1);
            $coupon->set_usage_limit_per_user(1);

            $coupon->set_date_expires(strtotime('+30 days'));

            $coupon->save();

            update_user_meta($user_id, 'wooaffiliate_commission', 0);

            echo '<p>' . sprintf(__('Discount code %s created for %s. This code can only be used once and will expire in 30 days.', 'wooaffiliate'), '<strong>' . $discount_code . '</strong>', wc_price($amount)) . '</p>';
        } elseif ($action === 'request_withdrawal' && $commission > 0) {
            $request_id = 'WITHDRAW-' . strtoupper(wp_generate_password(6, false));

            $withdrawals = get_option('wooaffiliate_withdrawal_requests', array());

            if (!isset($withdrawals[$user_id])) {
                $withdrawals[$user_id] = array();
            }

            $withdrawals[$user_id][$request_id] = array(
                'amount' => $commission,
                'date' => time(),
                'status' => 'pending'
            );

            update_option('wooaffiliate_withdrawal_requests', $withdrawals);

            $admin_email = get_option('admin_email');
            wp_mail(
                $admin_email,
                __('Withdrawal Request', 'wooaffiliate'),
                sprintf(
                    __('User %d requested a withdrawal of %s. Request ID: %s. You can process this request in the WooAffiliate admin panel.', 'wooaffiliate'),
                    $user_id,
                    wc_price($commission),
                    $request_id
                )
            );

            echo '<p>' . __('Withdrawal request sent to admin. You will be notified when your withdrawal is processed.', 'wooaffiliate') . '</p>';
        }
    }
}

WooAffiliate_Commission::init();
