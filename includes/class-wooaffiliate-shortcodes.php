<?php
class WooAffiliate_Shortcodes {

    public static function init() {
        add_shortcode('wooaffiliate_link', [__CLASS__, 'affiliate_link_shortcode']);
        add_shortcode('wooaffiliate_stats', [__CLASS__, 'affiliate_stats_shortcode']);
    }

    public static function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => 0,
            'text' => __('Buy Now', 'wooaffiliate'),
            'class' => 'button',
        ), $atts, 'wooaffiliate_link');

        $product_id = absint($atts['product_id']);

        if (!$product_id) {
            return '<span class="wooaffiliate-error">' . __('Product ID is required', 'wooaffiliate') . '</span>';
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            return '<span class="wooaffiliate-error">' . __('Product not found', 'wooaffiliate') . '</span>';
        }

        $affiliate_id = get_current_user_id();

        if (!WooAffiliate_Affiliate::is_affiliate($affiliate_id)) {
            return '<a href="' . esc_url($product->get_permalink()) . '" class="' . esc_attr($atts['class']) . '">' . esc_html($atts['text']) . '</a>';
        }

        $affiliate_code = get_user_meta($affiliate_id, 'wooaffiliate_referral_code', true);

        if (!$affiliate_code) {
            $affiliate_code = WooAffiliate_Affiliate::generate_referral_code($affiliate_id);
        }

        $product_url = add_query_arg('ref', $affiliate_code, $product->get_permalink());

        return '<a href="' . esc_url($product_url) . '" class="' . esc_attr($atts['class']) . '">' . esc_html($atts['text']) . '</a>';
    }

    public static function affiliate_stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_balance' => 'yes',
            'show_total' => 'yes',
            'show_count' => 'yes',
        ), $atts, 'wooaffiliate_stats');

        $user_id = get_current_user_id();

        if (!$user_id || !WooAffiliate_Affiliate::is_affiliate($user_id)) {
            return '<p>' . __('You must be logged in as an affiliate to view stats.', 'wooaffiliate') . '</p>';
        }

        $commission = get_user_meta($user_id, 'wooaffiliate_commission', true);
        $commission = $commission ? $commission : 0;

        $commission_history = get_user_meta($user_id, 'wooaffiliate_commission_history', true);
        $commission_history = $commission_history ? $commission_history : array();

        $total_earned = 0;
        $order_count = count($commission_history);

        foreach($commission_history as $entry) {
            $total_earned += $entry['amount'];
        }

        ob_start();

        echo '<div class="wooaffiliate-stats-shortcode">';

        if ($atts['show_balance'] === 'yes') {
            echo '<div class="wooaffiliate-stat-item">';
            echo '<span class="wooaffiliate-stat-label">' . __('Current Balance', 'wooaffiliate') . ':</span> ';
            echo '<span class="wooaffiliate-stat-value">' . wc_price($commission) . '</span>';
            echo '</div>';
        }

        if ($atts['show_total'] === 'yes') {
            echo '<div class="wooaffiliate-stat-item">';
            echo '<span class="wooaffiliate-stat-label">' . __('Total Earned', 'wooaffiliate') . ':</span> ';
            echo '<span class="wooaffiliate-stat-value">' . wc_price($total_earned) . '</span>';
            echo '</div>';
        }

        if ($atts['show_count'] === 'yes') {
            echo '<div class="wooaffiliate-stat-item">';
            echo '<span class="wooaffiliate-stat-label">' . __('Referred Orders', 'wooaffiliate') . ':</span> ';
            echo '<span class="wooaffiliate-stat-value">' . $order_count . '</span>';
            echo '</div>';
        }

        echo '</div>';

        echo '<style>
            .wooaffiliate-stats-shortcode {
                margin-bottom: 20px;
            }
            .wooaffiliate-stat-item {
                margin-bottom: 10px;
            }
            .wooaffiliate-stat-label {
                font-weight: bold;
            }
        </style>';

        return ob_get_clean();
    }
}

WooAffiliate_Shortcodes::init();
