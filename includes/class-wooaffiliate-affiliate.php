<?php
class WooAffiliate_Affiliate {

    public static function init() {
        add_action('woocommerce_order_status_completed', [__CLASS__, 'track_affiliate_commission']);
        add_action('woocommerce_order_status_processing', [__CLASS__, 'track_affiliate_commission']);
        add_action('woocommerce_payment_complete', [__CLASS__, 'track_affiliate_commission']);

        add_action('woocommerce_account_dashboard', [__CLASS__, 'display_affiliate_link']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }

    public static function track_affiliate_commission($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $commission_awarded = get_post_meta($order_id, '_wooaffiliate_commission_awarded', true);
        if ($commission_awarded) {
            return;
        }

        if (!$order->is_paid()) {
            return;
        }

        if (isset($_COOKIE['wooaffiliate_referral'])) {
            $referrer_id = intval($_COOKIE['wooaffiliate_referral']);

            if ($referrer_id == $order->get_customer_id()) {
                return;
            }

            $total_commission = 0;

            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $product_total = $item->get_total();

                $commission_percentage = WooAffiliate_Category_Discounts::get_category_commission_percentage($product_id);
                $item_commission = ($product_total * $commission_percentage) / 100;
                $total_commission += $item_commission;
            }

            $current_commission = get_user_meta($referrer_id, 'wooaffiliate_commission', true);
            $current_commission = $current_commission ? $current_commission : 0;
            update_user_meta($referrer_id, 'wooaffiliate_commission', $current_commission + $total_commission);

            $commission_data = get_user_meta($referrer_id, 'wooaffiliate_commission_history', true);
            $commission_data = $commission_data ? $commission_data : array();

            $commission_data[] = array(
                'order_id' => $order_id,
                'date' => current_time('timestamp'),
                'amount' => $total_commission,
                'order_total' => $order->get_total()
            );

            update_user_meta($referrer_id, 'wooaffiliate_commission_history', $commission_data);

            update_post_meta($order_id, '_wooaffiliate_commission_awarded', true);
            update_post_meta($order_id, '_wooaffiliate_referrer_id', $referrer_id);
            update_post_meta($order_id, '_wooaffiliate_commission_amount', $total_commission);

            self::notify_affiliate_of_commission($referrer_id, $order_id, $total_commission);
        }
    }

    public static function notify_affiliate_of_commission($affiliate_id, $order_id, $commission_amount) {
        $user = get_user_by('id', $affiliate_id);
        if (!$user) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $subject = sprintf(__('You earned a commission of %s', 'wooaffiliate'), wc_price($commission_amount));

        $message = sprintf(
            __('Hello %s,

Good news! You have earned a commission of %s from an order placed through your affiliate link.

Order Details:
- Order Number: #%s
- Order Date: %s
- Commission Amount: %s

You can view your commission details in your account dashboard.

Thank you for being our affiliate partner!

Regards,
%s', 'wooaffiliate'),
            $user->display_name,
            wc_price($commission_amount),
            $order->get_order_number(),
            $order->get_date_created()->date_i18n(get_option('date_format')),
            wc_price($commission_amount),
            get_bloginfo('name')
        );

        wp_mail($user->user_email, $subject, $message);
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
                    background: #ffffff;
                    border: 1px solid #e5e5e5;
                    border-radius: 4px;
                    padding: 25px;
                    margin: 20px 0;
                    box-shadow: 0 1px 5px rgba(0,0,0,0.05);
                }
                .wooaffiliate-link-container {
                    display: flex;
                    flex-wrap: wrap;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 20px;
                }
                #wooaffiliate-link {
                    background: #fff;
                    padding: 12px 15px;
                    border-radius: 4px;
                    flex: 1;
                    border: 1px solid #ddd;
                    font-size: 14px;
                    box-shadow: inset 0 1px 2px rgba(0,0,0,0.07);
                }
                .wooaffiliate-copy-btn {
                    background: #4e9ce6;
                    color: white;
                    border: none;
                    padding: 12px 20px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.2s ease;
                    text-transform: uppercase;
                    font-size: 13px;
                    letter-spacing: 0.5px;
                }
                .wooaffiliate-copy-btn:hover {
                    background: #3a87d2;
                    transform: translateY(-1px);
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .wooaffiliate-copy-success {
                    display: none;
                    color: #4CAF50;
                    margin-top: 8px;
                    font-weight: bold;
                }
                .wooaffiliate-tabs {
                    margin-bottom: 30px !important;
                }
                .wooaffiliate-tabs ul {
                    display: flex;
                    border-bottom: 1px solid #ddd;
                    padding: 0;
                    margin-bottom: 25px;
                    list-style: none;
                    flex-wrap: wrap;
                }
                .wooaffiliate-tabs li {
                    margin: 0 10px 0 0;
                }
                .wooaffiliate-tabs li a {
                    display: block;
                    padding: 12px 20px;
                    text-decoration: none;
                    color: #555;
                    border: 1px solid transparent;
                    border-bottom: none;
                    border-radius: 4px 4px 0 0;
                    font-weight: normal;
                    transition: all 0.2s ease;
                }
                .wooaffiliate-tabs li:hover a {
                    background: #fafafa;
                    border-color: #ddd;
                    color: #333;
                }
                .wooaffiliate-tabs li.is-active a {
                    background: #fff;
                    color: #333;
                    border-color: #ddd;
                    border-bottom-color: #fff;
                    font-weight: bold;
                    margin-bottom: -1px;
                }
                .wooaffiliate-heading {
                    margin-bottom: 20px;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                    font-size: 18px;
                    color: #333;
                }
            ');
        }
    }
}

WooAffiliate_Affiliate::init();
