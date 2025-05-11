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
        $commission_history = get_user_meta($user_id, 'wooaffiliate_commission_history', true);
        $commission_history = $commission_history ? $commission_history : array();

        $has_pending_withdrawal = self::has_pending_withdrawal($user_id);

        $tab = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'summary';

        echo '<h2>' . __('My Commissions', 'wooaffiliate') . '</h2>';

        echo '<nav class="woocommerce-MyAccount-navigation wooaffiliate-tabs">';
        echo '<ul>';
        echo '<li class="' . ($tab === 'summary' ? 'is-active' : '') . '"><a href="' . wc_get_account_endpoint_url('commission') . '">' . __('Summary', 'wooaffiliate') . '</a></li>';
        echo '<li class="' . ($tab === 'history' ? 'is-active' : '') . '"><a href="' . wc_get_account_endpoint_url('commission') . '?view=history">' . __('Commission History', 'wooaffiliate') . '</a></li>';
        echo '</ul>';
        echo '</nav>';

        if ($tab === 'history') {
            self::display_commission_history($user_id, $commission_history);
        } else {
            echo '<div class="wooaffiliate-commission-summary">';
            echo '<div class="wooaffiliate-balance-card">';
            echo '<h3>' . __('Current Balance', 'wooaffiliate') . '</h3>';
            echo '<div class="wooaffiliate-balance-amount">' . wc_price($commission) . '</div>';

            $total_earned = 0;
            $order_count = count($commission_history);

            foreach($commission_history as $entry) {
                $total_earned += $entry['amount'];
            }

            echo '<div class="wooaffiliate-stats">';
            echo '<div class="wooaffiliate-stat"><span>' . __('Total Earned', 'wooaffiliate') . ':</span> ' . wc_price($total_earned) . '</div>';
            echo '<div class="wooaffiliate-stat"><span>' . __('Orders', 'wooaffiliate') . ':</span> ' . $order_count . '</div>';
            echo '</div>';
            echo '</div>';

            if ($commission > 0) {
                echo '<div class="wooaffiliate-actions">';
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
                echo '</div>';
            } else {
                echo '<p>' . __('You do not have any commission available for withdrawal or discount conversion.', 'wooaffiliate') . '</p>';
            }

            self::display_user_withdrawal_requests($user_id);
            echo '</div>';
        }

        self::handle_post_actions($user_id, $commission);

        echo '<style>
            .wooaffiliate-tabs ul {
                display: flex;
                margin: 0 0 20px;
                padding: 0;
                border-bottom: 1px solid #ddd;
                flex-wrap: wrap;
            }
            .wooaffiliate-tabs li {
                list-style: none;
                margin: 0 4px 4px 0;
            }
            .wooaffiliate-tabs li a {
                display: block;
                padding: 12px 20px;
                text-decoration: none;
                border: 1px solid #ddd;
                background: #f8f8f8;
                color: #6d6d6d;
                border-radius: 5px;
                transition: all 0.3s ease;
                font-weight: 500;
            }
            .wooaffiliate-tabs li a:hover {
                background: #f0f0f0;
                color: #333;
            }
            .wooaffiliate-tabs li.is-active a {
                background: #fff;
                border-color: #2e8b57;
                color: #2e8b57;
                font-weight: 600;
            }
            .wooaffiliate-balance-card {
                background: #ffffff;
                border: 1px solid #e5e5e5;
                border-radius: 8px;
                padding: 25px;
                margin-bottom: 30px;
                text-align: center;
                box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            }
            .wooaffiliate-balance-amount {
                font-size: 36px;
                font-weight: bold;
                margin: 20px 0;
                color: #2e8b57;
            }
            .wooaffiliate-stats {
                display: flex;
                justify-content: space-around;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #eee;
                flex-wrap: wrap;
            }
            .wooaffiliate-stat {
                padding: 10px;
                margin: 5px;
                flex: 1;
                min-width: 120px;
            }
            .wooaffiliate-stat span {
                font-weight: bold;
                display: block;
                margin-bottom: 5px;
                color: #555;
            }
            .wooaffiliate-actions {
                display: flex;
                gap: 15px;
                flex-wrap: wrap;
                margin: 25px 0;
                justify-content: center;
            }
            .wooaffiliate-actions form {
                margin: 0;
            }
            .wooaffiliate-actions .button {
                padding: 12px 20px;
                height: auto;
                line-height: 1.5;
                font-weight: 500;
                background-color: #2e8b57;
                color: #fff;
                border-color: #2e8b57;
                transition: all 0.3s ease;
            }
            .wooaffiliate-actions .button:hover {
                background-color: #267349;
                color: #fff;
                border-color: #267349;
            }
            .wooaffiliate-notice {
                padding: 15px;
                background: #fff8e5;
                border-left: 4px solid #ffb900;
                margin: 15px 0;
                border-radius: 4px;
            }
            .wooaffiliate-commission-table {
                margin-bottom: 30px;
                border-collapse: collapse;
                width: 100%;
            }
            .wooaffiliate-commission-table th,
            .wooaffiliate-commission-table td {
                border: 1px solid #ddd;
                padding: 12px 15px;
                text-align: left;
            }
            .wooaffiliate-commission-table th {
                background-color: #f8f8f8;
                font-weight: 600;
            }
            .wooaffiliate-commission-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .wooaffiliate-status-completed {
                color: #2e8b57;
                font-weight: 600;
            }
            .wooaffiliate-status-pending {
                color: #e67e22;
                font-weight: 600;
            }
            /* Coupon styling */
            .wooaffiliate-copy-coupon {
                display: inline-block;
                background: #4e9ce6;
                color: white;
                border: none;
                padding: 4px 10px;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
                margin-left: 10px;
                transition: all 0.2s ease;
            }
            .wooaffiliate-copy-coupon:hover {
                background: #3a87d2;
            }
            .wooaffiliate-copy-success {
                color: #2e8b57;
                margin-left: 10px;
                font-size: 12px;
                font-weight: 500;
            }
            .coupon-code {
                display: flex;
                align-items: center;
            }
            .wooaffiliate-status-active {
                color: #2e8b57;
                font-weight: 600;
            }
            .wooaffiliate-status-used {
                color: #7e7e7e;
                font-weight: 600;
            }
            .wooaffiliate-status-expired {
                color: #c0392b;
                font-weight: 600;
            }
            @media (max-width: 768px) {
                .wooaffiliate-stats {
                    flex-direction: column;
                }
                .wooaffiliate-tabs ul {
                    flex-direction: column;
                    width: 100%;
                }
                .wooaffiliate-tabs li {
                    margin: 0 0 5px 0;
                    width: 100%;
                }
                .wooaffiliate-tabs li a {
                    text-align: center;
                }
                .coupon-code {
                    flex-wrap: wrap;
                }
                .wooaffiliate-copy-coupon {
                    margin-top: 5px;
                    margin-left: 0;
                }
            }
        </style>';
    }

    public static function display_commission_history($user_id, $commission_history) {
        echo '<h3>' . __('Lịch sử hoa hồng', 'wooaffiliate') . '</h3>';

        if (empty($commission_history)) {
            echo '<p>' . __('Bạn chưa kiếm được hoa hồng nào.', 'wooaffiliate') . '</p>';
            return;
        }

        usort($commission_history, function($a, $b) {
            return $b['date'] - $a['date'];
        });

        echo '<div class="wooaffiliate-commission-table">';
        echo '<table class="woocommerce-orders-table shop_table shop_table_responsive">';
        echo '<thead><tr>';
        echo '<th>' . __('Ngày', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Đơn hàng', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Tổng đơn hàng', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Số tiền hoa hồng', 'wooaffiliate') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($commission_history as $entry) {
            $order_id = isset($entry['order_id']) ? $order_id : 0;
            $order = wc_get_order($order_id);
            $order_url = $order ? $order->get_view_order_url() : '';
            $order_number = $order ? $order->get_order_number() : __('Không xác định', 'wooaffiliate');
            $order_total = isset($entry['order_total']) ? $entry['order_total'] : 0;

            echo '<tr>';
            echo '<td data-title="' . __('Ngày', 'wooaffiliate') . '">' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $entry['date']) . '</td>';
            echo '<td data-title="' . __('Đơn hàng', 'wooaffiliate') . '">' .
                ($order_url ? '<a href="' . esc_url($order_url) . '">#' . esc_html($order_number) . '</a>' : esc_html($order_number)) .
                '</td>';
            echo '<td data-title="' . __('Tổng đơn hàng', 'wooaffiliate') . '">' . wc_price($order_total) . '</td>';
            echo '<td data-title="' . __('Số tiền hoa hồng', 'wooaffiliate') . '">' . wc_price($entry['amount']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
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
            echo '<td data-title="' . __('Request ID', 'wooaffiliate') . '">' . $request_id . '</td>';
            echo '<td data-title="' . __('Amount', 'wooaffiliate') . '">' . wc_price($request['amount']) . '</td>';
            echo '<td data-title="' . __('Date', 'wooaffiliate') . '">' . date_i18n(get_option('date_format'), $request['date']) . '</td>';
            echo '<td data-title="' . __('Status', 'wooaffiliate') . '">' . ($request['status'] === 'completed' ?
                '<span class="wooaffiliate-status-completed">' . __('Completed', 'wooaffiliate') . '</span>' :
                '<span class="wooaffiliate-status-pending">' . __('Pending', 'wooaffiliate') . '</span>'
            ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        // Display user's coupons
        self::display_user_coupons($user_id);
    }

    public static function display_user_coupons($user_id) {
        $user_coupons = get_user_meta($user_id, 'wooaffiliate_user_coupons', true);

        if (!$user_coupons || empty($user_coupons)) {
            return;
        }

        echo '<h3>' . __('Your Discount Codes', 'wooaffiliate') . '</h3>';
        echo '<table class="woocommerce-orders-table shop_table shop_table_responsive">';
        echo '<thead><tr>';
        echo '<th>' . __('Coupon Code', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Amount', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Created', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Expires', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Status', 'wooaffiliate') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($user_coupons as $coupon) {
            $now = time();
            $is_expired = $coupon['expires'] < $now;

            // Check if the coupon has been used
            $is_used = false;
            if (isset($coupon['code'])) {
                $coupon_obj = new WC_Coupon($coupon['code']);
                $usage_count = $coupon_obj->get_usage_count();
                $is_used = $usage_count > 0;
            }

            echo '<tr>';
            echo '<td data-title="' . __('Coupon Code', 'wooaffiliate') . '" class="coupon-code"><strong>' . $coupon['code'] . '</strong>';

            // Add copy button
            echo ' <button class="wooaffiliate-copy-coupon" data-coupon="' . esc_attr($coupon['code']) . '">' . __('Copy', 'wooaffiliate') . '</button>';
            echo '<span class="wooaffiliate-copy-success" style="display:none;">' . __('Copied!', 'wooaffiliate') . '</span>';
            echo '</td>';

            echo '<td data-title="' . __('Amount', 'wooaffiliate') . '">' . wc_price($coupon['amount']) . '</td>';
            echo '<td data-title="' . __('Created', 'wooaffiliate') . '">' . date_i18n(get_option('date_format'), $coupon['created']) . '</td>';
            echo '<td data-title="' . __('Expires', 'wooaffiliate') . '">' . date_i18n(get_option('date_format'), $coupon['expires']) . '</td>';
            echo '<td data-title="' . __('Status', 'wooaffiliate') . '">';

            if ($is_used) {
                echo '<span class="wooaffiliate-status-used">' . __('Used', 'wooaffiliate') . '</span>';
            } elseif ($is_expired) {
                echo '<span class="wooaffiliate-status-expired">' . __('Expired', 'wooaffiliate') . '</span>';
            } else {
                echo '<span class="wooaffiliate-status-active">' . __('Active', 'wooaffiliate') . '</span>';
            }

            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        // Add inline JavaScript for copy functionality
        echo '<script>
            jQuery(document).ready(function($) {
                $(".wooaffiliate-copy-coupon").on("click", function() {
                    var $this = $(this);
                    var couponCode = $this.data("coupon");
                    var $successMsg = $this.next(".wooaffiliate-copy-success");

                    navigator.clipboard.writeText(couponCode).then(function() {
                        $successMsg.fadeIn().delay(1500).fadeOut();
                    });
                });
            });
        </script>';
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

            $conversion_history = get_user_meta($user_id, 'wooaffiliate_conversion_history', true);
            $conversion_history = $conversion_history ? $conversion_history : array();

            $conversion_history[] = array(
                'coupon_code' => $discount_code,
                'amount' => $amount,
                'date' => current_time('timestamp'),
                'expiry' => strtotime('+30 days')
            );

            update_user_meta($user_id, 'wooaffiliate_conversion_history', $conversion_history);

            // Store the user's active coupons for display in dashboard
            $user_coupons = get_user_meta($user_id, 'wooaffiliate_user_coupons', true);
            $user_coupons = $user_coupons ? $user_coupons : array();

            $user_coupons[] = array(
                'code' => $discount_code,
                'amount' => $amount,
                'created' => current_time('timestamp'),
                'expires' => strtotime('+30 days')
            );

            update_user_meta($user_id, 'wooaffiliate_user_coupons', $user_coupons);

            echo '<div class="woocommerce-message" role="alert">' .
                sprintf(__('Discount code %s created for %s. This code can only be used once and will expire in 30 days.', 'wooaffiliate'),
                '<strong>' . $discount_code . '</strong>', wc_price($amount)) .
                '</div>';
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
            $user_info = get_userdata($user_id);

            wp_mail(
                $admin_email,
                __('Withdrawal Request', 'wooaffiliate'),
                sprintf(
                    __('User %s (ID: %d) requested a withdrawal of %s. Request ID: %s. You can process this request in the WooAffiliate admin panel.', 'wooaffiliate'),
                    $user_info->display_name,
                    $user_id,
                    wc_price($commission),
                    $request_id
                )
            );

            echo '<div class="woocommerce-message" role="alert">' .
                __('Withdrawal request sent to admin. You will be notified when your withdrawal is processed.', 'wooaffiliate') .
                '</div>';
        }
    }
}

WooAffiliate_Commission::init();
