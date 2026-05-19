<?php
/**
 * REST API authentication bridge — enables Application Passwords for the OpenClaw namespace
 */

if (!defined('ABSPATH')) {
    exit;
}

class OpenClaw_REST_Auth {

    public function __construct() {
        add_filter('determine_current_user', [$this, 'determine_current_user'], 20);
        add_filter('rest_authentication_errors', [$this, 'rest_authentication_errors']);
    }

    /**
     * Allow Application Password authentication for OpenClaw endpoints
     */
    public function determine_current_user($user_id) {
        if ($user_id) {
            return $user_id;
        }

        $route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';
        if (strpos($route, '/openclaw/') !== 0) {
            return $user_id;
        }

        // Try Application Password
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($auth_header) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (stripos($auth_header, 'Basic ') === 0) {
            $decoded = base64_decode(substr($auth_header, 6));
            if ($decoded && strpos($decoded, ':') !== false) {
                [$username, $password] = explode(':', $decoded, 2);
                $user = wp_authenticate_application_password(null, $username, $password);
                if ($user instanceof WP_User) {
                    return $user->ID;
                }
            }
        }

        return $user_id;
    }

    /**
     * Don't block our endpoints on missing auth — let the permission callbacks handle it
     */
    public function rest_authentication_errors($result) {
        if (is_wp_error($result)) {
            $route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';
            if (strpos($route, '/openclaw/') === 0) {
                return null; // Let permission callbacks handle auth
            }
        }
        return $result;
    }
}

new OpenClaw_REST_Auth();
