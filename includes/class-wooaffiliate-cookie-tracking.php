<?php
class WooAffiliate_Cookie_Tracking {

    public static function init() {
        // Set referral cookie on visit
        add_action('init', [__CLASS__, 'set_referral_cookie']);
    }

    public static function set_referral_cookie() {
        if (isset($_GET['ref']) && is_numeric($_GET['ref'])) {
            $referrer_id = intval($_GET['ref']);
            $cookie_duration = get_option('wooaffiliate_cookie_duration', 30);

            // Set the cookie
            setcookie('wooaffiliate_referral', $referrer_id, time() + (DAY_IN_SECONDS * $cookie_duration), COOKIEPATH, COOKIE_DOMAIN);
        }
    }
}

// Initialize the cookie tracking functionality
WooAffiliate_Cookie_Tracking::init();
