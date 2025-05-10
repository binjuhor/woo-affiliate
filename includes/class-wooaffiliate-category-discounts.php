<?php
class WooAffiliate_Category_Discounts {

    public static function init() {
        // Add category-specific settings
        add_action('product_cat_add_form_fields', [__CLASS__, 'add_category_fields']);
        add_action('product_cat_edit_form_fields', [__CLASS__, 'edit_category_fields']);
        add_action('edited_product_cat', [__CLASS__, 'save_category_fields']);
        add_action('create_product_cat', [__CLASS__, 'save_category_fields']);

        // Apply category-specific discounts
        add_filter('woocommerce_get_price_html', [__CLASS__, 'apply_category_discount'], 10, 2);
    }

    public static function add_category_fields() {
        // Get default values from settings
        $default_commission = get_option('wooaffiliate_commission_percentage', 10);
        $default_discount = get_option('wooaffiliate_discount_percentage', 5);

        echo '<div class="form-field">
            <h3>' . __('WooAffiliate Settings', 'wooaffiliate') . '</h3>
            <p>' . __('Set specific commission and discount percentages for this category. If left empty, default values will be used.', 'wooaffiliate') . '</p>
        </div>';

        echo '<div class="form-field">
            <label for="wooaffiliate_commission_percentage">' . __('Affiliate Commission Percentage', 'wooaffiliate') . '</label>
            <input type="number" name="wooaffiliate_commission_percentage" id="wooaffiliate_commission_percentage" value="" min="0" max="100" step="0.1">
            <p class="description">' . sprintf(__('Default commission is %s%%. Enter a value to override for this category.', 'wooaffiliate'), $default_commission) . '</p>
        </div>';

        echo '<div class="form-field">
            <label for="wooaffiliate_discount_percentage">' . __('Affiliate Discount Percentage', 'wooaffiliate') . '</label>
            <input type="number" name="wooaffiliate_discount_percentage" id="wooaffiliate_discount_percentage" value="" min="0" max="100" step="0.1">
            <p class="description">' . sprintf(__('Default discount is %s%%. Enter a value to override for this category.', 'wooaffiliate'), $default_discount) . '</p>
        </div>';
    }

    public static function edit_category_fields($term) {
        // Get saved category values
        $commission_value = get_term_meta($term->term_id, 'wooaffiliate_commission_percentage', true);
        $discount_value = get_term_meta($term->term_id, 'wooaffiliate_discount_percentage', true);

        // Get default values from settings
        $default_commission = get_option('wooaffiliate_commission_percentage', 10);
        $default_discount = get_option('wooaffiliate_discount_percentage', 5);

        echo '<tr class="form-field">
            <th scope="row" colspan="2">
                <h3>' . __('WooAffiliate Settings', 'wooaffiliate') . '</h3>
                <p>' . __('Set specific commission and discount percentages for this category. If left empty, default values will be used.', 'wooaffiliate') . '</p>
            </th>
        </tr>';

        echo '<tr class="form-field">
            <th scope="row" valign="top">
                <label for="wooaffiliate_commission_percentage">' . __('Affiliate Commission Percentage', 'wooaffiliate') . '</label>
            </th>
            <td>
                <input type="number" name="wooaffiliate_commission_percentage" id="wooaffiliate_commission_percentage" value="' . esc_attr($commission_value) . '" min="0" max="100" step="0.1">
                <p class="description">' . sprintf(__('Default commission is %s%%. Enter a value to override for this category.', 'wooaffiliate'), $default_commission) . '</p>
            </td>
        </tr>';

        echo '<tr class="form-field">
            <th scope="row" valign="top">
                <label for="wooaffiliate_discount_percentage">' . __('Affiliate Discount Percentage', 'wooaffiliate') . '</label>
            </th>
            <td>
                <input type="number" name="wooaffiliate_discount_percentage" id="wooaffiliate_discount_percentage" value="' . esc_attr($discount_value) . '" min="0" max="100" step="0.1">
                <p class="description">' . sprintf(__('Default discount is %s%%. Enter a value to override for this category.', 'wooaffiliate'), $default_discount) . '</p>
            </td>
        </tr>';
    }

    public static function save_category_fields($term_id) {
        if (isset($_POST['wooaffiliate_commission_percentage'])) {
            update_term_meta($term_id, 'wooaffiliate_commission_percentage', sanitize_text_field($_POST['wooaffiliate_commission_percentage']));
        }

        if (isset($_POST['wooaffiliate_discount_percentage'])) {
            update_term_meta($term_id, 'wooaffiliate_discount_percentage', sanitize_text_field($_POST['wooaffiliate_discount_percentage']));
        }
    }

    public static function apply_category_discount($price, $product) {
        if (isset($_COOKIE['wooaffiliate_referral'])) {
            $categories = $product->get_category_ids();
            $default_discount = get_option('wooaffiliate_discount_percentage', 5);

            foreach ($categories as $category_id) {
                $discount_percentage = get_term_meta($category_id, 'wooaffiliate_discount_percentage', true);
                if ($discount_percentage) {
                    $price = wc_price($product->get_regular_price() * (1 - $discount_percentage / 100));
                    break;
                }
            }

            if (!isset($discount_percentage)) {
                $price = wc_price($product->get_regular_price() * (1 - $default_discount / 100));
            }
        }
        return $price;
    }

    /**
     * Get category-specific commission percentage for a product
     */
    public static function get_category_commission_percentage($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return get_option('wooaffiliate_commission_percentage', 10);
        }

        $categories = $product->get_category_ids();
        $default_commission = get_option('wooaffiliate_commission_percentage', 10);

        foreach ($categories as $category_id) {
            $category_commission = get_term_meta($category_id, 'wooaffiliate_commission_percentage', true);
            if (!empty($category_commission)) {
                return $category_commission;
            }
        }

        return $default_commission;
    }

    /**
     * Get category-specific discount percentage for a product
     */
    public static function get_category_discount_percentage($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return get_option('wooaffiliate_discount_percentage', 5);
        }

        $categories = $product->get_category_ids();
        $default_discount = get_option('wooaffiliate_discount_percentage', 5);

        foreach ($categories as $category_id) {
            $category_discount = get_term_meta($category_id, 'wooaffiliate_discount_percentage', true);
            if (!empty($category_discount)) {
                return $category_discount;
            }
        }

        return $default_discount;
    }
}

// Initialize the category discount functionality
WooAffiliate_Category_Discounts::init();
