<?php
/**
 * Uninstall handler — cleans up all plugin data
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete custom table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}openclaw_api_keys");

// Delete options
$options = [
    'openclaw_ai_endpoint',
    'openclaw_ai_model',
    'openclaw_ai_api_key',
    'openclaw_enable_seo',
    'openclaw_enable_ai',
    'openclaw_seo_auto_generate',
    'openclaw_allowed_post_types',
];

foreach ($options as $option) {
    delete_option($option);
}

// Clean up post meta
$wpdb->delete($wpdb->postmeta, ['meta_key' => '_openclaw_meta_description']);
$wpdb->delete($wpdb->postmeta, ['meta_key' => '_openclaw_focus_keyword']);
$wpdb->delete($wpdb->postmeta, ['meta_key' => '_openclaw_seo_title']);
$wpdb->delete($wpdb->postmeta, ['meta_key' => '_openclaw_og_title']);
$wpdb->delete($wpdb->postmeta, ['meta_key' => '_openclaw_og_description']);
