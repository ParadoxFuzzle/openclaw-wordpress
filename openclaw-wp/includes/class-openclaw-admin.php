<?php
/**
 * Admin settings page
 */

if (!defined('ABSPATH')) {
    exit;
}

class OpenClaw_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_menu_page(): void {
        add_menu_page(
            __('OpenClaw', 'openclaw-wp'),
            __('OpenClaw', 'openclaw-wp'),
            'manage_options',
            'openclaw-wp',
            [$this, 'render_page'],
            'dashicons-rest-api',
            80
        );
    }

    public function register_settings(): void {
        // AI Settings
        register_setting('openclaw_ai', 'openclaw_enable_ai');
        register_setting('openclaw_ai', 'openclaw_ai_endpoint');
        register_setting('openclaw_ai', 'openclaw_ai_api_key');
        register_setting('openclaw_ai', 'openclaw_ai_model');

        // SEO Settings
        register_setting('openclaw_seo', 'openclaw_enable_seo');
        register_setting('openclaw_seo', 'openclaw_seo_auto_generate');

        // General Settings
        register_setting('openclaw_general', 'openclaw_allowed_post_types');

        // Add sections
        add_settings_section('openclaw_ai_section', __('AI Configuration', 'openclaw-wp'), null, 'openclaw-wp-ai');
        add_settings_section('openclaw_seo_section', __('SEO Configuration', 'openclaw-wp'), null, 'openclaw-wp-seo');
        add_settings_section('openclaw_general_section', __('General Settings', 'openclaw-wp'), null, 'openclaw-wp-general');
        add_settings_section('openclaw_api_section', __('API Documentation', 'openclaw-wp'), null, 'openclaw-wp-api');

        // AI fields
        add_settings_field('openclaw_enable_ai', __('Enable AI', 'openclaw-wp'), [$this, 'render_checkbox'], 'openclaw-wp-ai', 'openclaw_ai_section', [
            'option' => 'openclaw_enable_ai',
            'description' => __('Enable AI content generation features.', 'openclaw-wp'),
        ]);

        add_settings_field('openclaw_ai_endpoint', __('AI Endpoint', 'openclaw-wp'), [$this, 'render_text'], 'openclaw-wp-ai', 'openclaw_ai_section', [
            'option' => 'openclaw_ai_endpoint',
            'description' => __('OpenAI-compatible API endpoint. Example: http://your-server:8000/v1', 'openclaw-wp'),
            'placeholder' => 'http://127.0.0.1:8000/v1',
        ]);

        add_settings_field('openclaw_ai_api_key', __('AI API Key', 'openclaw-wp'), [$this, 'render_password'], 'openclaw-wp-ai', 'openclaw_ai_section', [
            'option' => 'openclaw_ai_api_key',
            'description' => __('API key for your AI endpoint.', 'openclaw-wp'),
        ]);

        add_settings_field('openclaw_ai_model', __('AI Model', 'openclaw-wp'), [$this, 'render_text'], 'openclaw-wp-ai', 'openclaw_ai_section', [
            'option' => 'openclaw_ai_model',
            'description' => __('Model ID to use for generation. Example: gpt-4o, claude-sonnet-4', 'openclaw-wp'),
            'placeholder' => 'gpt-4o',
        ]);

        // SEO fields
        add_settings_field('openclaw_enable_seo', __('Enable SEO', 'openclaw-wp'), [$this, 'render_checkbox'], 'openclaw-wp-seo', 'openclaw_seo_section', [
            'option' => 'openclaw_enable_seo',
            'description' => __('Enable SEO analysis and optimization features.', 'openclaw-wp'),
        ]);

        add_settings_field('openclaw_seo_auto_generate', __('Auto-Generate SEO', 'openclaw-wp'), [$this, 'render_checkbox'], 'openclaw-wp-seo', 'openclaw_seo_section', [
            'option' => 'openclaw_seo_auto_generate',
            'description' => __('Automatically generate SEO metadata when AI creates content.', 'openclaw-wp'),
        ]);
    }

    public function enqueue_assets(string $hook): void {
        if ($hook !== 'toplevel_page_openclaw-wp') {
            return;
        }
        wp_enqueue_style('openclaw-admin', OPENCLAW_WP_PLUGIN_URL . 'assets/css/admin.css', [], OPENCLAW_WP_VERSION);
    }

    public function render_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $active_tab = sanitize_text_field($_GET['tab'] ?? 'dashboard');
        ?>
        <div class="wrap openclaw-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=openclaw-wp&tab=dashboard" class="nav-tab <?php echo $active_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>"><?php _e('Dashboard', 'openclaw-wp'); ?></a>
                <a href="?page=openclaw-wp&tab=ai" class="nav-tab <?php echo $active_tab === 'ai' ? 'nav-tab-active' : ''; ?>"><?php _e('AI Settings', 'openclaw-wp'); ?></a>
                <a href="?page=openclaw-wp&tab=seo" class="nav-tab <?php echo $active_tab === 'seo' ? 'nav-tab-active' : ''; ?>"><?php _e('SEO Settings', 'openclaw-wp'); ?></a>
                <a href="?page=openclaw-wp&tab=api" class="nav-tab <?php echo $active_tab === 'api' ? 'nav-tab-active' : ''; ?>"><?php _e('API Docs', 'openclaw-wp'); ?></a>
            </nav>

            <?php
            switch ($active_tab) {
                case 'dashboard':
                    $this->render_dashboard();
                    break;
                case 'ai':
                    $this->render_ai_settings();
                    break;
                case 'seo':
                    $this->render_seo_settings();
                    break;
                case 'api':
                    $this->render_api_docs();
                    break;
            }
            ?>
        </div>
        <?php
    }

    private function render_dashboard(): void {
        $ai = new OpenClaw_AI();
        $site_info = [
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'openclaw_version' => OPENCLAW_WP_VERSION,
            'ai_configured' => $ai->is_available(),
            'ai_endpoint' => get_option('openclaw_ai_endpoint', ''),
            'ai_model' => get_option('openclaw_ai_model', 'gpt-4o'),
            'seo_enabled' => get_option('openclaw_enable_seo', 1),
            'active_theme' => wp_get_theme()->get('Name'),
            'active_plugins' => count(get_option('active_plugins', [])),
            'total_posts' => wp_count_posts()->publish,
            'total_pages' => wp_count_posts('page')->publish,
        ];
        ?>
        <div class="openclaw-dashboard">
            <div class="openclaw-cards">
                <div class="openclaw-card">
                    <h3><?php _e('Plugin Version', 'openclaw-wp'); ?></h3>
                    <p class="openclaw-big"><?php echo esc_html($site_info['openclaw_version']); ?></p>
                </div>
                <div class="openclaw-card">
                    <h3><?php _e('AI Status', 'openclaw-wp'); ?></h3>
                    <p class="openclaw-big <?php echo $site_info['ai_configured'] ? 'openclaw-status-ok' : 'openclaw-status-warn'; ?>">
                        <?php echo $site_info['ai_configured'] ? '✅ ' . __('Configured', 'openclaw-wp') : '⚠️ ' . __('Not Configured', 'openclaw-wp'); ?>
                    </p>
                </div>
                <div class="openclaw-card">
                    <h3><?php _e('SEO', 'openclaw-wp'); ?></h3>
                    <p class="openclaw-big <?php echo $site_info['seo_enabled'] ? 'openclaw-status-ok' : 'openclaw-status-warn'; ?>">
                        <?php echo $site_info['seo_enabled'] ? '✅ ' . __('Enabled', 'openclaw-wp') : '❌ ' . __('Disabled', 'openclaw-wp'); ?>
                    </p>
                </div>
                <div class="openclaw-card">
                    <h3><?php _e('Content', 'openclaw-wp'); ?></h3>
                    <p><?php printf(__('%d posts, %d pages', 'openclaw-wp'), $site_info['total_posts'], $site_info['total_pages']); ?></p>
                </div>
            </div>

            <div class="openclaw-card openclaw-card-full">
                <h3><?php _e('Quick Start', 'openclaw-wp'); ?></h3>
                <ol>
                    <li><?php _e('Go to AI Settings and configure your AI endpoint (e.g., LiteLLM proxy)', 'openclaw-wp'); ?></li>
                    <li><?php _e('Create an Application Password in your WordPress user profile', 'openclaw-wp'); ?></li>
                    <li><?php _e('Use the REST API endpoints to manage your site programmatically', 'openclaw-wp'); ?></li>
                    <li><?php _e('Check the API Docs tab for full endpoint documentation', 'openclaw-wp'); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }

    private function render_ai_settings(): void {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('openclaw_ai');
            do_settings_sections('openclaw-wp-ai');
            submit_button();
            ?>
        </form>
        <?php
    }

    private function render_seo_settings(): void {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('openclaw_seo');
            do_settings_sections('openclaw-wp-seo');
            submit_button();
            ?>
        </form>
        <?php
    }

    private function render_api_docs(): void {
        $rest_url = rest_url('openclaw/v1');
        ?>
        <div class="openclaw-api-docs">
            <h2><?php _e('REST API Endpoints', 'openclaw-wp'); ?></h2>
            <p><?php printf(__('Base URL: <code>%s</code>', 'openclaw-wp'), esc_url($rest_url)); ?></p>

            <h3><?php _e('Authentication', 'openclaw-wp'); ?></h3>
            <p><?php _e('All endpoints require authentication via one of:', 'openclaw-wp'); ?></p>
            <ul>
                <li><strong><?php _e('Application Password:', 'openclaw-wp'); ?></strong> <?php _e('Use HTTP Basic Auth with your WordPress username and Application Password.', 'openclaw-wp'); ?></li>
                <li><strong><?php _e('API Key:', 'openclaw-wp'); ?></strong> <?php _e('Send header <code>X-OpenClaw-API-Key: your-key</code>', 'openclaw-wp'); ?></li>
            </ul>

            <h3><?php _e('Endpoints', 'openclaw-wp'); ?></h3>

            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Method', 'openclaw-wp'); ?></th>
                        <th><?php _e('Endpoint', 'openclaw-wp'); ?></th>
                        <th><?php _e('Description', 'openclaw-wp'); ?></th>
                        <th><?php _e('Permission', 'openclaw-wp'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><code>GET</code></td><td><code>/openclaw/v1/site</code></td><td><?php _e('Get site information', 'openclaw-wp'); ?></td><td>manage_options</td></tr>
                    <tr><td><code>GET</code></td><td><code>/openclaw/v1/posts</code></td><td><?php _e('List posts', 'openclaw-wp'); ?></td><td>manage_posts</td></tr>
                    <tr><td><code>GET</code></td><td><code>/openclaw/v1/posts/{id}</code></td><td><?php _e('Get single post', 'openclaw-wp'); ?></td><td>manage_posts</td></tr>
                    <tr><td><code>POST</code></td><td><code>/openclaw/v1/posts</code></td><td><?php _e('Create post', 'openclaw-wp'); ?></td><td>manage_posts</td></tr>
                    <tr><td><code>PUT</code></td><td><code>/openclaw/v1/posts/{id}</code></td><td><?php _e('Update post', 'openclaw-wp'); ?></td><td>manage_posts</td></tr>
                    <tr><td><code>DELETE</code></td><td><code>/openclaw/v1/posts/{id}</code></td><td><?php _e('Delete post', 'openclaw-wp'); ?></td><td>manage_posts</td></tr>
                    <tr><td><code>GET</code></td><td><code>/openclaw/v1/pages</code></td><td><?php _e('List pages', 'openclaw-wp'); ?></td><td>manage_pages</td></tr>
                    <tr><td><code>POST</code></td><td><code>/openclaw/v1/pages</code></td><td><?php _e('Create page', 'openclaw-wp'); ?></td><td>manage_pages</td></tr>
                    <tr><td><code>PUT</code></td><td><code>/openclaw/v1/pages/{id}</code></td><td><?php _e('Update page', 'openclaw-wp'); ?></td><td>manage_pages</td></tr>
                    <tr><td><code>DELETE</code></td><td><code>/openclaw/v1/pages/{id}</code></td><td><?php _e('Delete page', 'openclaw-wp'); ?></td><td>manage_pages</td></tr>
                    <tr><td><code>GET</code></td><td><code>/openclaw/v1/plugins</code></td><td><?php _e('List plugins', 'openclaw-wp'); ?></td><td>manage_plugins</td></tr>
                    <tr><td><code>PUT</code></td><td><code>/openclaw/v1/plugins/{file}</code></td><td><?php _e('Activate/deactivate plugin', 'openclaw-wp'); ?></td><td>manage_plugins</td></tr>
                    <tr><td><code>GET</code></td><td><code>/openclaw/v1/themes</code></td><td><?php _e('List themes', 'openclaw-wp'); ?></td><td>manage_themes</td></tr>
                    <tr><td><code>PUT</code></td><td><code>/openclaw/v1/themes/{stylesheet}</code></td><td><?php _e('Switch theme', 'openclaw-wp'); ?></td><td>manage_themes</td></tr>
                    <tr><td><code>GET</code></td><td><code>/openclaw/v1/users</code></td><td><?php _e('List users', 'openclaw-wp'); ?></td><td>manage_users</td></tr>
                    <tr><td><code>POST</code></td><td><code>/openclaw/v1/users</code></td><td><?php _e('Create user', 'openclaw-wp'); ?></td><td>manage_users</td></tr>
                    <tr><td><code>PUT</code></td><td><code>/openclaw/v1/users/{id}</code></td><td><?php _e('Update user', 'openclaw-wp'); ?></td><td>manage_users</td></tr>
                    <tr><td><code>DELETE</code></td><td><code>/openclaw/v1/users/{id}</code></td><td><?php _e('Delete user', 'openclaw-wp'); ?></td><td>manage_users</td></tr>
                    <tr><td><code>POST</code></td><td><code>/openclaw/v1/media</code></td><td><?php _e('Upload media', 'openclaw-wp'); ?></td><td>manage_media</td></tr>
                    <tr><td><code>GET</code></td><td><code>/openclaw/v1/categories</code></td><td><?php _e('List categories', 'openclaw-wp'); ?></td><td>manage_categories</td></tr>
                    <tr><td><code>POST</code></td><td><code>/openclaw/v1/categories</code></td><td><?php _e('Create category', 'openclaw-wp'); ?></td><td>manage_categories</td></tr>
                    <tr><td><code>GET</code></td><td><code>/openclaw/v1/tags</code></td><td><?php _e('List tags', 'openclaw-wp'); ?></td><td>manage_tags</td></tr>
                    <tr><td><code>POST</code></td><td><code>/openclaw/v1/tags</code></td><td><?php _e('Create tag', 'openclaw-wp'); ?></td><td>manage_tags</td></tr>
                    <tr><td><code>POST</code></td><td><code>/openclaw/v1/ai/generate-post</code></td><td><?php _e('AI generate post', 'openclaw-wp'); ?></td><td>use_ai_generation</td></tr>
                    <tr><td><code>POST</code></td><td><code>/openclaw/v1/ai/generate-page</code></td><td><?php _e('AI generate page', 'openclaw-wp'); ?></td><td>use_ai_generation</td></tr>
                    <tr><td><code>POST</code></td><td><code>/openclaw/v1/ai/generate-seo</code></td><td><?php _e('AI generate SEO metadata', 'openclaw-wp'); ?></td><td>use_ai_seo</td></tr>
                    <tr><td><code>GET</code></td><td><code>/openclaw/v1/seo/analyze/{id}</code></td><td><?php _e('Analyze post SEO', 'openclaw-wp'); ?></td><td>use_ai_seo</td></tr>
                    <tr><td><code>GET</code></td><td><code>/openclaw/v1/keys</code></td><td><?php _e('List API keys', 'openclaw-wp'); ?></td><td>manage_api_keys</td></tr>
                    <tr><td><code>POST</code></td><td><code>/openclaw/v1/keys</code></td><td><?php _e('Create API key', 'openclaw-wp'); ?></td><td>manage_api_keys</td></tr>
                    <tr><td><code>DELETE</code></td><td><code>/openclaw/v1/keys/{id}</code></td><td><?php _e('Revoke API key', 'openclaw-wp'); ?></td><td>manage_api_keys</td></tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    // ========================================
    // FIELD RENDERERS
    // ========================================

    public function render_checkbox(array $args): void {
        $value = get_option($args['option'], 1);
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr($args['option']); ?>" value="1" <?php checked(1, $value); ?> />
            <?php echo esc_html($args['description']); ?>
        </label>
        <?php
    }

    public function render_text(array $args): void {
        $value = get_option($args['option'], '');
        ?>
        <input type="text" name="<?php echo esc_attr($args['option']); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="<?php echo esc_attr($args['placeholder'] ?? ''); ?>" />
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }

    public function render_password(array $args): void {
        $value = get_option($args['option'], '');
        ?>
        <input type="password" name="<?php echo esc_attr($args['option']); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }
}
