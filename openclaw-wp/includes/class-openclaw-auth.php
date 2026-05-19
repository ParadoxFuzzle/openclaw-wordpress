<?php
/**
 * Authentication handler — supports WordPress Application Passwords and custom API Keys
 */

if (!defined('ABSPATH')) {
    exit;
}

class OpenClaw_Auth {

    /**
     * Authenticate a REST API request.
     * Returns WP_User on success, WP_Error on failure.
     */
    public static function authenticate(WP_REST_Request $request) {
        // Try Application Password first (WordPress built-in)
        $user = self::try_application_password($request);
        if ($user instanceof WP_User && !is_wp_error($user)) {
            return $user;
        }

        // Try custom API Key
        $user = self::try_api_key($request);
        if ($user instanceof WP_User && !is_wp_error($user)) {
            return $user;
        }

        return new WP_Error(
            'openclaw_auth_failed',
            __('Authentication required. Provide a valid Application Password or API Key.', 'openclaw-wp'),
            ['status' => 401]
        );
    }

    /**
     * Try WordPress Application Password authentication
     */
    private static function try_application_password(WP_REST_Request $request) {
        // WordPress handles Application Passwords via the Authorization header
        // Format: Basic base64(username:application_password)
        $auth_header = $request->get_header('authorization');

        if (empty($auth_header) || stripos($auth_header, 'Basic ') !== 0) {
            return null;
        }

        // Let WordPress handle it via the standard mechanism
        $user = wp_validate_application_password(null);

        if (!$user) {
            // Try manual extraction
            $decoded = base64_decode(substr($auth_header, 6));
            if (!$decoded || strpos($decoded, ':') === false) {
                return null;
            }

            [$username, $password] = explode(':', $decoded, 2);
            $user = wp_authenticate_application_password(null, $username, $password);
        }

        return $user;
    }

    /**
     * Try custom API Key authentication
     */
    private static function try_api_key(WP_REST_Request $request) {
        // Check header: X-OpenClaw-API-Key
        $api_key = $request->get_header('x_openclaw_api_key');

        // Fallback: query parameter
        if (empty($api_key)) {
            $api_key = sanitize_text_field($request->get_param('openclaw_api_key'));
        }

        if (empty($api_key)) {
            return null;
        }

        return self::validate_api_key($api_key);
    }

    /**
     * Validate an API key against the database
     */
    public static function validate_api_key(string $api_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'openclaw_api_keys';

        $key_hash = hash('sha256', $api_key);

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE api_key_hash = %s AND is_active = 1",
            $key_hash
        ));

        if (!$row) {
            return new WP_Error(
                'openclaw_invalid_key',
                __('Invalid API key.', 'openclaw-wp'),
                ['status' => 401]
            );
        }

        // Check expiration
        if ($row->expires_at && strtotime($row->expires_at) < time()) {
            return new WP_Error(
                'openclaw_expired_key',
                __('API key has expired.', 'openclaw-wp'),
                ['status' => 401]
            );
        }

        // Update last used
        $wpdb->update(
            $table,
            ['last_used_at' => current_time('mysql')],
            ['id' => $row->id]
        );

        $user = get_user_by('id', $row->user_id);
        if (!$user) {
            return new WP_Error(
                'openclaw_user_not_found',
                __('Associated user not found.', 'openclaw-wp'),
                ['status' => 401]
            );
        }

        return $user;
    }

    /**
     * Generate a new API key
     */
    public static function generate_api_key(int $user_id, string $name = '', array $permissions = [], ?string $expires_at = null): array {
        global $wpdb;
        $table = $wpdb->prefix . 'openclaw_api_keys';

        // Generate a secure random key
        $api_key = 'oc_' . wp_generate_password(48, false, false);
        $key_hash = hash('sha256', $api_key);

        $wpdb->insert($table, [
            'user_id' => $user_id,
            'api_key' => '', // Don't store plaintext
            'api_key_hash' => $key_hash,
            'name' => sanitize_text_field($name),
            'permissions' => !empty($permissions) ? wp_json_encode($permissions) : null,
            'expires_at' => $expires_at,
        ]);

        if ($wpdb->last_error) {
            return ['success' => false, 'error' => $wpdb->last_error];
        }

        return [
            'success' => true,
            'api_key' => $api_key, // Only returned once at creation
            'id' => $wpdb->insert_id,
        ];
    }

    /**
     * Revoke an API key
     */
    public static function revoke_api_key(int $key_id, int $user_id): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'openclaw_api_keys';

        return (bool) $wpdb->update(
            $table,
            ['is_active' => 0],
            ['id' => $key_id, 'user_id' => $user_id],
            ['%d'],
            ['%d', '%d']
        );
    }

    /**
     * Get all API keys for a user
     */
    public static function get_user_api_keys(int $user_id): array {
        global $wpdb;
        $table = $wpdb->prefix . 'openclaw_api_keys';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, permissions, last_used_at, created_at, expires_at, is_active
             FROM $table WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
    }

    /**
     * Check if a user has a specific OpenClaw permission
     */
    public static function user_can(int $user_id, string $capability): bool {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        // Admins can do everything
        if (in_array('administrator', $user->roles, true)) {
            return true;
        }

        return $user->has_cap('openclaw_' . $capability);
    }
}
