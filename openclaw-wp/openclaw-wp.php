<?php
/**
 * Plugin Name: OpenClaw for WordPress
 * Plugin URI: https://github.com/openclaw/openclaw-wp
 * Description: Complete OpenClaw integration for WordPress. REST API management, AI content generation, and SEO optimization.
 * Version: 1.0.0
 * Author: OpenClaw
 * Author URI: https://openclaw.ai
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: openclaw-wp
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OPENCLAW_WP_VERSION', '1.0.0');
define('OPENCLAW_WP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OPENCLAW_WP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OPENCLAW_WP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoload
require_once OPENCLAW_WP_PLUGIN_DIR . 'includes/class-openclaw-activator.php';
require_once OPENCLAW_WP_PLUGIN_DIR . 'includes/class-openclaw-deactivator.php';
require_once OPENCLAW_WP_PLUGIN_DIR . 'includes/class-openclaw-auth.php';
require_once OPENCLAW_WP_PLUGIN_DIR . 'includes/class-openclaw-roles.php';
require_once OPENCLAW_WP_PLUGIN_DIR . 'includes/class-openclaw-rest-api.php';
require_once OPENCLAW_WP_PLUGIN_DIR . 'includes/class-openclaw-ai.php';
require_once OPENCLAW_WP_PLUGIN_DIR . 'includes/class-openclaw-seo.php';
require_once OPENCLAW_WP_PLUGIN_DIR . 'includes/class-openclaw-admin.php';
require_once OPENCLAW_WP_PLUGIN_DIR . 'includes/class-openclaw-rest-auth.php';

/**
 * Main plugin class
 */
class OpenClaw_WP {

    private static $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init(): void {
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, ['OpenClaw_Activator', 'activate']);
        register_deactivation_hook(__FILE__, ['OpenClaw_Deactivator', 'deactivate']);

        // Initialize components
        add_action('plugins_loaded', [$this, 'load_components']);

        // Register REST API routes
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Admin menu
        if (is_admin()) {
            new OpenClaw_Admin();
        }
    }

    public function load_components(): void {
        // Initialize role capabilities
        OpenClaw_Roles::init();
    }

    public function register_rest_routes(): void {
        $rest_api = new OpenClaw_REST_API();
        $rest_api->register_routes();
    }
}

// Boot
OpenClaw_WP::get_instance();
