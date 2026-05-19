<?php
/**
 * Plugin activation handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class OpenClaw_Activator {

    public static function activate(): void {
        self::create_tables();
        self::add_capabilities();
        self::set_default_options();
        flush_rewrite_rules();
    }

    private static function create_tables(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'openclaw_api_keys';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            api_key varchar(255) NOT NULL,
            api_key_hash varchar(255) NOT NULL,
            name varchar(255) DEFAULT '',
            permissions longtext DEFAULT '',
            last_used_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY api_key_hash (api_key_hash),
            KEY user_id (user_id),
            KEY is_active (is_active)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    private static function add_capabilities(): void {
        $role = get_role('administrator');
        if ($role) {
            $caps = OpenClaw_Roles::get_all_capabilities();
            foreach ($caps as $cap) {
                $role->add_cap($cap, true);
            }
        }
    }

    private static function set_default_options(): void {
        $defaults = [
            'openclaw_ai_endpoint' => '',
            'openclaw_ai_model' => 'gpt-4o',
            'openclaw_ai_api_key' => '',
            'openclaw_enable_seo' => 1,
            'openclaw_enable_ai' => 1,
            'openclaw_seo_auto_generate' => 1,
            'openclaw_allowed_post_types' => ['post', 'page'],
        ];

        foreach ($defaults as $key => $value) {
            if (false === get_option($key)) {
                add_option($key, $value);
            }
        }
    }
}
