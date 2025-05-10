<?php
class WooAffiliate_Admin {

    public static function init() {
        // Add submenu page to WooAffiliate menu
        add_action('admin_menu', [__CLASS__, 'register_admin_submenu'], 11);

        // Handle admin actions
        add_action('admin_init', [__CLASS__, 'handle_admin_actions']);
    }

    public static function register_admin_submenu() {
        add_submenu_page(
            'wooaffiliate',
            __('Affiliate Commissions', 'wooaffiliate'),
            __('Commissions', 'wooaffiliate'),
            'manage_options',
            'wooaffiliate-commissions',
            [__CLASS__, 'commissions_page']
        );
    }

    public static function commissions_page() {
        // Check if a withdrawal status update is requested
        if (isset($_GET['action']) && $_GET['action'] == 'complete_withdrawal' && isset($_GET['user_id']) && isset($_GET['request_id'])) {
            $user_id = intval($_GET['user_id']);
            $request_id = sanitize_text_field($_GET['request_id']);

            // Mark the withdrawal as completed
            self::mark_withdrawal_completed($user_id, $request_id);

            // Show success notice
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Withdrawal marked as completed.', 'wooaffiliate') . '</p></div>';
        }

        echo '<div class="wrap">';
        echo '<h1>' . __('Woo Affiliate Commissions', 'wooaffiliate') . '</h1>';

        // Tabs for different views
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'commissions';
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=wooaffiliate-commissions&tab=commissions" class="nav-tab ' . ($current_tab == 'commissions' ? 'nav-tab-active' : '') . '">' . __('Commissions', 'wooaffiliate') . '</a>';
        echo '<a href="?page=wooaffiliate-commissions&tab=withdrawals" class="nav-tab ' . ($current_tab == 'withdrawals' ? 'nav-tab-active' : '') . '">' . __('Withdrawal Requests', 'wooaffiliate') . '</a>';
        echo '</h2>';

        if ($current_tab == 'commissions') {
            self::display_commissions_table();
        } else {
            self::display_withdrawals_table();
        }

        echo '</div>';
    }

    public static function display_commissions_table() {
        // Get all users with commission data
        $users_with_commissions = self::get_users_with_commissions();

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('User ID', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Username', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Email', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Commission Balance', 'wooaffiliate') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        if (empty($users_with_commissions)) {
            echo '<tr><td colspan="4">' . __('No commission data found.', 'wooaffiliate') . '</td></tr>';
        } else {
            foreach ($users_with_commissions as $user) {
                echo '<tr>';
                echo '<td>' . $user->ID . '</td>';
                echo '<td>' . $user->display_name . '</td>';
                echo '<td>' . $user->user_email . '</td>';
                echo '<td>' . wc_price($user->commission_amount) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody>';
        echo '</table>';
    }

    public static function display_withdrawals_table() {
        // Get all withdrawal requests
        $withdrawal_requests = self::get_withdrawal_requests();

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Request ID', 'wooaffiliate') . '</th>';
        echo '<th>' . __('User', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Amount', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Date', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Status', 'wooaffiliate') . '</th>';
        echo '<th>' . __('Actions', 'wooaffiliate') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        if (empty($withdrawal_requests)) {
            echo '<tr><td colspan="6">' . __('No withdrawal requests found.', 'wooaffiliate') . '</td></tr>';
        } else {
            foreach ($withdrawal_requests as $request) {
                echo '<tr>';
                echo '<td>' . $request->request_id . '</td>';
                echo '<td>' . $request->user_name . ' (#' . $request->user_id . ')</td>';
                echo '<td>' . wc_price($request->amount) . '</td>';
                echo '<td>' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $request->date) . '</td>';
                echo '<td>' . ($request->status == 'completed' ? '<span style="color:green;">✓ ' . __('Completed', 'wooaffiliate') . '</span>' : '<span style="color:orange;">⌛ ' . __('Pending', 'wooaffiliate') . '</span>') . '</td>';
                echo '<td>';
                if ($request->status != 'completed') {
                    echo '<a href="?page=wooaffiliate-commissions&tab=withdrawals&action=complete_withdrawal&user_id=' . $request->user_id . '&request_id=' . $request->request_id . '" class="button">' . __('Mark as Completed', 'wooaffiliate') . '</a>';
                }
                echo '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody>';
        echo '</table>';
    }

    public static function get_users_with_commissions() {
        global $wpdb;

        $users_with_commissions = $wpdb->get_results(
            "SELECT u.ID, u.user_login, u.display_name, u.user_email, um.meta_value as commission_amount
            FROM {$wpdb->users} u
            JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = 'wooaffiliate_commission'
            AND um.meta_value > 0"
        );

        return $users_with_commissions;
    }

    public static function get_withdrawal_requests() {
        // Placeholder - in a real implementation, this would fetch from a withdrawals table
        // For now, we'll use a mock implementation
        $withdrawals = get_option('wooaffiliate_withdrawal_requests', array());

        if (empty($withdrawals)) {
            return array();
        }

        $formatted_withdrawals = array();
        foreach ($withdrawals as $user_id => $user_withdrawals) {
            foreach ($user_withdrawals as $request_id => $withdrawal) {
                $user = get_user_by('id', $user_id);
                if ($user) {
                    $withdrawal['user_id'] = $user_id;
                    $withdrawal['user_name'] = $user->display_name;
                    $withdrawal['request_id'] = $request_id;
                    $formatted_withdrawals[] = (object) $withdrawal;
                }
            }
        }

        return $formatted_withdrawals;
    }

    public static function mark_withdrawal_completed($user_id, $request_id) {
        $withdrawals = get_option('wooaffiliate_withdrawal_requests', array());

        if (isset($withdrawals[$user_id][$request_id])) {
            $withdrawals[$user_id][$request_id]['status'] = 'completed';
            update_option('wooaffiliate_withdrawal_requests', $withdrawals);
            return true;
        }

        return false;
    }

    public static function handle_admin_actions() {
        // Add more admin actions here if needed
    }
}

// Initialize the admin functionality
WooAffiliate_Admin::init();
