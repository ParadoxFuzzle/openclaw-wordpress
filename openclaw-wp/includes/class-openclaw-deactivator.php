<?php
/**
 * Plugin deactivation handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class OpenClaw_Deactivator {

    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}
