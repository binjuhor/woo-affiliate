<?php
class WooAffiliate_Init {

    public static function init() {
        load_plugin_textdomain('wooaffiliate', false, dirname(plugin_basename(__FILE__)) . '/languages');
        add_action('admin_menu', [__CLASS__, 'register_admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function register_admin_menu() {
        add_menu_page(
            __('Woo Affiliate', 'wooaffiliate'),
            __('Woo Affiliate', 'wooaffiliate'),
            'manage_options',
            'wooaffiliate',
            [__CLASS__, 'admin_page'],
            'dashicons-networking'
        );
    }

    public static function admin_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Woo Affiliate Settings', 'wooaffiliate') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('wooaffiliate_settings');
        do_settings_sections('wooaffiliate');
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public static function register_settings() {
        register_setting('wooaffiliate_settings', 'wooaffiliate_commission_percentage');
        register_setting('wooaffiliate_settings', 'wooaffiliate_discount_percentage');
        register_setting('wooaffiliate_settings', 'wooaffiliate_cookie_duration');
        register_setting('wooaffiliate_settings', 'wooaffiliate_discount_categories');

        add_settings_section(
            'wooaffiliate_general_settings',
            __('General Settings', 'wooaffiliate'),
            null,
            'wooaffiliate'
        );

        add_settings_field(
            'wooaffiliate_commission_percentage',
            __('Commission Percentage', 'wooaffiliate'),
            [__CLASS__, 'commission_percentage_field'],
            'wooaffiliate',
            'wooaffiliate_general_settings'
        );

        add_settings_field(
            'wooaffiliate_discount_percentage',
            __('Discount Percentage', 'wooaffiliate'),
            [__CLASS__, 'discount_percentage_field'],
            'wooaffiliate',
            'wooaffiliate_general_settings'
        );

        add_settings_field(
            'wooaffiliate_cookie_duration',
            __('Cookie Duration (days)', 'wooaffiliate'),
            [__CLASS__, 'cookie_duration_field'],
            'wooaffiliate',
            'wooaffiliate_general_settings'
        );

        add_settings_field(
            'wooaffiliate_discount_categories',
            __('Discount Categories', 'wooaffiliate'),
            function () {
                $selected_categories = get_option('wooaffiliate_discount_categories', []);
                $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

                echo '<select name="wooaffiliate_discount_categories[]" multiple style="width: 100%;">';
                foreach ($categories as $category) {
                    $selected = in_array($category->term_id, (array) $selected_categories) ? 'selected' : '';
                    echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                }
                echo '</select>';
                echo '<p class="description">' . __('Select categories where affiliate discounts apply.', 'wooaffiliate') . '</p>';
            },
            'wooaffiliate',
            'wooaffiliate_general_settings'
        );
    }

    public static function commission_percentage_field() {
        $value = get_option('wooaffiliate_commission_percentage', '10');
        echo '<input type="number" name="wooaffiliate_commission_percentage" value="' . esc_attr($value) . '" min="0" max="100">';
    }

    public static function discount_percentage_field() {
        $value = get_option('wooaffiliate_discount_percentage', '5');
        echo '<input type="number" name="wooaffiliate_discount_percentage" value="' . esc_attr($value) . '" min="0" max="100">';
    }

    public static function cookie_duration_field() {
        $value = get_option('wooaffiliate_cookie_duration', '30');
        echo '<input type="number" name="wooaffiliate_cookie_duration" value="' . esc_attr($value) . '" min="1">';
    }
}
