<?php
/**
 * Role-based access control
 */

if (!defined('ABSPATH')) {
    exit;
}

class OpenClaw_Roles {

    /**
     * All OpenClaw capabilities
     */
    public static function get_all_capabilities(): array {
        return [
            // Content management
            'manage_posts',
            'manage_pages',
            'manage_media',
            'manage_categories',
            'manage_tags',

            // Site management
            'manage_plugins',
            'manage_themes',
            'manage_users',
            'manage_options',

            // AI features
            'use_ai_generation',
            'use_ai_seo',

            // API key management
            'manage_api_keys',
        ];
    }

    /**
     * Get capabilities by role
     */
    public static function get_role_capabilities(string $role): array {
        $all_caps = self::get_all_capabilities();

        switch ($role) {
            case 'administrator':
                return $all_caps;

            case 'editor':
                return [
                    'manage_posts',
                    'manage_pages',
                    'manage_media',
                    'manage_categories',
                    'manage_tags',
                    'use_ai_generation',
                    'use_ai_seo',
                ];

            case 'author':
                return [
                    'manage_posts',
                    'manage_media',
                    'use_ai_generation',
                    'use_ai_seo',
                ];

            case 'contributor':
                return [
                    'manage_posts',
                ];

            default:
                return [];
        }
    }

    /**
     * Initialize role capabilities on plugin load
     */
    public static function init(): void {
        // Ensure admin has all caps
        $admin = get_role('administrator');
        if ($admin) {
            foreach (self::get_all_capabilities() as $cap) {
                $admin->add_cap('openclaw_' . $cap, true);
            }
        }
    }

    /**
     * Check if the current request user has the required capability
     */
    public static function check_permission(WP_REST_Request $request, string $capability): bool|WP_Error {
        $user = OpenClaw_Auth::authenticate($request);

        if (is_wp_error($user)) {
            return $user;
        }

        if (!OpenClaw_Auth::user_can($user->ID, $capability)) {
            return new WP_Error(
                'openclaw_forbidden',
                __('You do not have permission to perform this action.', 'openclaw-wp'),
                ['status' => 403]
            );
        }

        return true;
    }
}
