<?php
/**
 * REST API controller — full site management endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class OpenClaw_REST_API {

    const NAMESPACE = 'openclaw/v1';
    const VERSION = '1';

    public function register_routes(): void {
        // ========================================
        // POSTS
        // ========================================
        register_rest_route(self::NAMESPACE, '/posts', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_posts'],
            'permission_callback' => [$this, 'check_manage_posts'],
            'args' => $this->get_posts_args(),
        ]);

        register_rest_route(self::NAMESPACE, '/posts/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_post'],
            'permission_callback' => [$this, 'check_manage_posts'],
        ]);

        register_rest_route(self::NAMESPACE, '/posts', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_post'],
            'permission_callback' => [$this, 'check_manage_posts'],
            'args' => $this->get_post_create_args(),
        ]);

        register_rest_route(self::NAMESPACE, '/posts/(?P<id>\d+)', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'update_post'],
            'permission_callback' => [$this, 'check_manage_posts'],
            'args' => $this->get_post_create_args(),
        ]);

        register_rest_route(self::NAMESPACE, '/posts/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_post'],
            'permission_callback' => [$this, 'check_manage_posts'],
        ]);

        // ========================================
        // PAGES
        // ========================================
        register_rest_route(self::NAMESPACE, '/pages', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_pages'],
            'permission_callback' => [$this, 'check_manage_pages'],
            'args' => $this->get_posts_args(),
        ]);

        register_rest_route(self::NAMESPACE, '/pages/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_page'],
            'permission_callback' => [$this, 'check_manage_pages'],
        ]);

        register_rest_route(self::NAMESPACE, '/pages', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_page'],
            'permission_callback' => [$this, 'check_manage_pages'],
            'args' => $this->get_post_create_args(),
        ]);

        register_rest_route(self::NAMESPACE, '/pages/(?P<id>\d+)', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'update_page'],
            'permission_callback' => [$this, 'check_manage_pages'],
            'args' => $this->get_post_create_args(),
        ]);

        register_rest_route(self::NAMESPACE, '/pages/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_page'],
            'permission_callback' => [$this, 'check_manage_pages'],
        ]);

        // ========================================
        // PLUGINS
        // ========================================
        register_rest_route(self::NAMESPACE, '/plugins', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_plugins'],
            'permission_callback' => [$this, 'check_manage_plugins'],
        ]);

        register_rest_route(self::NAMESPACE, '/plugins/(?P<plugin>[a-zA-Z0-9\-_\.\/]+)', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'toggle_plugin'],
            'permission_callback' => [$this, 'check_manage_plugins'],
            'args' => [
                'action' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['activate', 'deactivate'],
                ],
            ],
        ]);

        // ========================================
        // THEMES
        // ========================================
        register_rest_route(self::NAMESPACE, '/themes', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_themes'],
            'permission_callback' => [$this, 'check_manage_themes'],
        ]);

        register_rest_route(self::NAMESPACE, '/themes/(?P<stylesheet>[a-zA-Z0-9\-_]+)', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'switch_theme'],
            'permission_callback' => [$this, 'check_manage_themes'],
        ]);

        // ========================================
        // USERS
        // ========================================
        register_rest_route(self::NAMESPACE, '/users', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_users'],
            'permission_callback' => [$this, 'check_manage_users'],
            'args' => [
                'role' => ['type' => 'string', 'default' => ''],
                'per_page' => ['type' => 'integer', 'default' => 20],
                'page' => ['type' => 'integer', 'default' => 1],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/users/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_user'],
            'permission_callback' => [$this, 'check_manage_users'],
        ]);

        register_rest_route(self::NAMESPACE, '/users', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_user'],
            'permission_callback' => [$this, 'check_manage_users'],
        ]);

        register_rest_route(self::NAMESPACE, '/users/(?P<id>\d+)', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'update_user'],
            'permission_callback' => [$this, 'check_manage_users'],
        ]);

        register_rest_route(self::NAMESPACE, '/users/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_user'],
            'permission_callback' => [$this, 'check_manage_users'],
        ]);

        // ========================================
        // MEDIA
        // ========================================
        register_rest_route(self::NAMESPACE, '/media', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'upload_media'],
            'permission_callback' => [$this, 'check_manage_media'],
        ]);

        // ========================================
        // CATEGORIES & TAGS
        // ========================================
        register_rest_route(self::NAMESPACE, '/categories', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_categories'],
            'permission_callback' => [$this, 'check_manage_categories'],
        ]);

        register_rest_route(self::NAMESPACE, '/categories', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_category'],
            'permission_callback' => [$this, 'check_manage_categories'],
        ]);

        register_rest_route(self::NAMESPACE, '/tags', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_tags'],
            'permission_callback' => [$this, 'check_manage_tags'],
        ]);

        register_rest_route(self::NAMESPACE, '/tags', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_tag'],
            'permission_callback' => [$this, 'check_manage_tags'],
        ]);

        // ========================================
        // AI GENERATION
        // ========================================
        register_rest_route(self::NAMESPACE, '/ai/generate-post', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'ai_generate_post'],
            'permission_callback' => [$this, 'check_use_ai'],
        ]);

        register_rest_route(self::NAMESPACE, '/ai/generate-page', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'ai_generate_page'],
            'permission_callback' => [$this, 'check_use_ai'],
        ]);

        register_rest_route(self::NAMESPACE, '/ai/generate-seo', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'ai_generate_seo'],
            'permission_callback' => [$this, 'check_use_ai_seo'],
        ]);

        // ========================================
        // SEO
        // ========================================
        register_rest_route(self::NAMESPACE, '/seo/analyze/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'seo_analyze'],
            'permission_callback' => [$this, 'check_use_ai_seo'],
        ]);

        // ========================================
        // API KEYS
        // ========================================
        register_rest_route(self::NAMESPACE, '/keys', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_api_keys'],
            'permission_callback' => [$this, 'check_manage_api_keys'],
        ]);

        register_rest_route(self::NAMESPACE, '/keys', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_api_key'],
            'permission_callback' => [$this, 'check_manage_api_keys'],
        ]);

        register_rest_route(self::NAMESPACE, '/keys/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'revoke_api_key'],
            'permission_callback' => [$this, 'check_manage_api_keys'],
        ]);

        // ========================================
        // SITE INFO
        // ========================================
        register_rest_route(self::NAMESPACE, '/site', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_site_info'],
            'permission_callback' => [$this, 'check_manage_options'],
        ]);
    }

    // ========================================
    // PERMISSION CALLBACKS
    // ========================================

    private function get_authenticated_user(WP_REST_Request $request) {
        return OpenClaw_Auth::authenticate($request);
    }

    public function check_manage_posts(WP_REST_Request $request) {
        return $this->check_cap($request, 'manage_posts');
    }

    public function check_manage_pages(WP_REST_Request $request) {
        return $this->check_cap($request, 'manage_pages');
    }

    public function check_manage_plugins(WP_REST_Request $request) {
        return $this->check_cap($request, 'manage_plugins');
    }

    public function check_manage_themes(WP_REST_Request $request) {
        return $this->check_cap($request, 'manage_themes');
    }

    public function check_manage_users(WP_REST_Request $request) {
        return $this->check_cap($request, 'manage_users');
    }

    public function check_manage_media(WP_REST_Request $request) {
        return $this->check_cap($request, 'manage_media');
    }

    public function check_manage_categories(WP_REST_Request $request) {
        return $this->check_cap($request, 'manage_categories');
    }

    public function check_manage_tags(WP_REST_Request $request) {
        return $this->check_cap($request, 'manage_tags');
    }

    public function check_manage_options(WP_REST_Request $request) {
        return $this->check_cap($request, 'manage_options');
    }

    public function check_use_ai(WP_REST_Request $request) {
        return $this->check_cap($request, 'use_ai_generation');
    }

    public function check_use_ai_seo(WP_REST_Request $request) {
        return $this->check_cap($request, 'use_ai_seo');
    }

    public function check_manage_api_keys(WP_REST_Request $request) {
        return $this->check_cap($request, 'manage_api_keys');
    }

    private function check_cap(WP_REST_Request $request, string $cap): bool|WP_Error {
        $user = $this->get_authenticated_user($request);
        if (is_wp_error($user)) {
            return $user;
        }
        if (!OpenClaw_Auth::user_can($user->ID, $cap)) {
            return new WP_Error('openclaw_forbidden', __('Insufficient permissions.', 'openclaw-wp'), ['status' => 403]);
        }
        return true;
    }

    // ========================================
    // POSTS
    // ========================================

    public function get_posts(WP_REST_Request $request): WP_REST_Response {
        $args = [
            'post_type' => 'post',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged' => $request->get_param('page') ?: 1,
            'post_status' => $request->get_param('status') ?: 'any',
            's' => $request->get_param('search') ?: '',
            'orderby' => $request->get_param('orderby') ?: 'date',
            'order' => $request->get_param('order') ?: 'DESC',
        ];

        $query = new WP_Query($args);
        $posts = [];

        foreach ($query->posts as $post) {
            $posts[] = $this->format_post($post);
        }

        return new WP_REST_Response([
            'posts' => $posts,
            'total' => (int) $query->found_posts,
            'pages' => (int) $query->max_num_pages,
        ], 200);
    }

    public function get_post(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $post = get_post($request->get_param('id'));
        if (!$post || $post->post_type !== 'post') {
            return new WP_Error('not_found', __('Post not found.', 'openclaw-wp'), ['status' => 404]);
        }
        return new WP_REST_Response($this->format_post($post, true), 200);
    }

    public function create_post(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $data = $this->get_post_data($request);
        $data['post_type'] = 'post';

        $post_id = wp_insert_post($data, true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }

        $this->set_post_meta($request, $post_id);
        $this->set_post_terms($request, $post_id);

        return new WP_REST_Response($this->format_post(get_post($post_id), true), 201);
    }

    public function update_post(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'post') {
            return new WP_Error('not_found', __('Post not found.', 'openclaw-wp'), ['status' => 404]);
        }

        $data = $this->get_post_data($request);
        $data['ID'] = $post_id;

        $result = wp_update_post($data, true);
        if (is_wp_error($result)) {
            return $result;
        }

        $this->set_post_meta($request, $post_id);
        $this->set_post_terms($request, $post_id);

        return new WP_REST_Response($this->format_post(get_post($post_id), true), 200);
    }

    public function delete_post(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $post_id = $request->get_param('id');
        $force = (bool) $request->get_param('force');

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'post') {
            return new WP_Error('not_found', __('Post not found.', 'openclaw-wp'), ['status' => 404]);
        }

        $result = $force ? wp_delete_post($post_id, true) : wp_trash_post($post_id);

        if (!$result) {
            return new WP_Error('delete_failed', __('Failed to delete post.', 'openclaw-wp'), ['status' => 500]);
        }

        return new WP_REST_Response(['deleted' => true, 'force' => $force], 200);
    }

    // ========================================
    // PAGES
    // ========================================

    public function get_pages(WP_REST_Request $request): WP_REST_Response {
        $args = [
            'post_type' => 'page',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged' => $request->get_param('page') ?: 1,
            'post_status' => $request->get_param('status') ?: 'any',
            's' => $request->get_param('search') ?: '',
        ];

        $query = new WP_Query($args);
        $pages = [];

        foreach ($query->posts as $post) {
            $pages[] = $this->format_post($post);
        }

        return new WP_REST_Response([
            'pages' => $pages,
            'total' => (int) $query->found_posts,
            'pages_count' => (int) $query->max_num_pages,
        ], 200);
    }

    public function get_page(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $post = get_post($request->get_param('id'));
        if (!$post || $post->post_type !== 'page') {
            return new WP_Error('not_found', __('Page not found.', 'openclaw-wp'), ['status' => 404]);
        }
        return new WP_REST_Response($this->format_post($post, true), 200);
    }

    public function create_page(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $data = $this->get_post_data($request);
        $data['post_type'] = 'page';

        $post_id = wp_insert_post($data, true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }

        $this->set_post_meta($request, $post_id);

        return new WP_REST_Response($this->format_post(get_post($post_id), true), 201);
    }

    public function update_page(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'page') {
            return new WP_Error('not_found', __('Page not found.', 'openclaw-wp'), ['status' => 404]);
        }

        $data = $this->get_post_data($request);
        $data['ID'] = $post_id;

        $result = wp_update_post($data, true);
        if (is_wp_error($result)) {
            return $result;
        }

        $this->set_post_meta($request, $post_id);

        return new WP_REST_Response($this->format_post(get_post($post_id), true), 200);
    }

    public function delete_page(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $post_id = $request->get_param('id');
        $force = (bool) $request->get_param('force');

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'page') {
            return new WP_Error('not_found', __('Page not found.', 'openclaw-wp'), ['status' => 404]);
        }

        $result = $force ? wp_delete_post($post_id, true) : wp_trash_post($post_id);

        if (!$result) {
            return new WP_Error('delete_failed', __('Failed to delete page.', 'openclaw-wp'), ['status' => 500]);
        }

        return new WP_REST_Response(['deleted' => true, 'force' => $force], 200);
    }

    // ========================================
    // PLUGINS
    // ========================================

    public function get_plugins(WP_REST_Request $request): WP_REST_Response {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        $active = get_option('active_plugins', []);
        $result = [];

        foreach ($plugins as $file => $info) {
            $result[] = [
                'file' => $file,
                'name' => $info['Name'],
                'version' => $info['Version'],
                'description' => $info['Description'],
                'author' => $info['Author'],
                'active' => in_array($file, $active, true),
            ];
        }

        return new WP_REST_Response(['plugins' => $result], 200);
    }

    public function toggle_plugin(WP_REST_Request $request): WP_REST_Response|WP_Error {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin = $request->get_param('plugin');
        $action = $request->get_param('action');

        if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
            return new WP_Error('not_found', __('Plugin not found.', 'openclaw-wp'), ['status' => 404]);
        }

        if ($action === 'activate') {
            $result = activate_plugin($plugin);
        } else {
            deactivate_plugins($plugin);
            $result = true;
        }

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(['success' => true, 'action' => $action], 200);
    }

    // ========================================
    // THEMES
    // ========================================

    public function get_themes(WP_REST_Request $request): WP_REST_Response {
        $themes = wp_get_themes();
        $current = wp_get_theme();
        $result = [];

        foreach ($themes as $stylesheet => $theme) {
            $result[] = [
                'stylesheet' => $stylesheet,
                'name' => $theme->get('Name'),
                'version' => $theme->get('Version'),
                'description' => $theme->get('Description'),
                'author' => $theme->get('Author'),
                'active' => ($stylesheet === $current->get_stylesheet()),
            ];
        }

        return new WP_REST_Response(['themes' => $result, 'current' => $current->get('Name')], 200);
    }

    public function switch_theme(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $stylesheet = $request->get_param('stylesheet');
        $theme = wp_get_theme($stylesheet);

        if (!$theme->exists()) {
            return new WP_Error('not_found', __('Theme not found.', 'openclaw-wp'), ['status' => 404]);
        }

        switch_theme($stylesheet);

        return new WP_REST_Response(['success' => true, 'theme' => $theme->get('Name')], 200);
    }

    // ========================================
    // USERS
    // ========================================

    public function get_users(WP_REST_Request $request): WP_REST_Response {
        $args = [
            'number' => $request->get_param('per_page'),
            'offset' => ($request->get_param('page') - 1) * $request->get_param('per_page'),
        ];

        $role = $request->get_param('role');
        if ($role) {
            $args['role'] = $role;
        }

        $query = new WP_User_Query($args);
        $users = [];

        foreach ($query->get_results() as $user) {
            $users[] = $this->format_user($user);
        }

        return new WP_REST_Response([
            'users' => $users,
            'total' => (int) $query->get_total(),
        ], 200);
    }

    public function get_user(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user = get_user_by('id', $request->get_param('id'));
        if (!$user) {
            return new WP_Error('not_found', __('User not found.', 'openclaw-wp'), ['status' => 404]);
        }
        return new WP_REST_Response($this->format_user($user, true), 200);
    }

    public function create_user(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $data = [
            'user_login' => $request->get_param('username'),
            'user_email' => $request->get_param('email'),
            'user_pass' => $request->get_param('password'),
            'role' => $request->get_param('role') ?: 'subscriber',
            'first_name' => $request->get_param('first_name') ?: '',
            'last_name' => $request->get_param('last_name') ?: '',
            'display_name' => $request->get_param('display_name') ?: '',
        ];

        $user_id = wp_insert_user($data);
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        return new WP_REST_Response($this->format_user(get_user_by('id', $user_id), true), 201);
    }

    public function update_user(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = $request->get_param('id');
        $data = ['ID' => $user_id];

        foreach (['email', 'first_name', 'last_name', 'display_name', 'role'] as $field) {
            $value = $request->get_param($field);
            if ($value) {
                $data['user_' . $field] = $value;
            }
        }

        $password = $request->get_param('password');
        if ($password) {
            $data['user_pass'] = $password;
        }

        $result = wp_update_user($data);
        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response($this->format_user(get_user_by('id', $user_id), true), 200);
    }

    public function delete_user(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = $request->get_param('id');
        $reassign = $request->get_param('reassign');

        if (!get_user_by('id', $user_id)) {
            return new WP_Error('not_found', __('User not found.', 'openclaw-wp'), ['status' => 404]);
        }

        $result = wp_delete_user($user_id, $reassign ?: null);

        if (!$result) {
            return new WP_Error('delete_failed', __('Failed to delete user.', 'openclaw-wp'), ['status' => 500]);
        }

        return new WP_REST_Response(['deleted' => true], 200);
    }

    // ========================================
    // MEDIA
    // ========================================

    public function upload_media(WP_REST_Request $request): WP_REST_Response|WP_Error {
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $file = $request->get_file_params();
        if (empty($file['file'])) {
            return new WP_Error('no_file', __('No file provided.', 'openclaw-wp'), ['status' => 400]);
        }

        $uploaded = wp_handle_upload($file['file'], ['test_form' => false]);

        if (isset($uploaded['error'])) {
            return new WP_Error('upload_failed', $uploaded['error'], ['status' => 500]);
        }

        $attachment = [
            'post_mime_type' => $uploaded['type'],
            'post_title' => sanitize_file_name($file['file']['name']),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $uploaded['file']);

        if (is_wp_error($attach_id)) {
            return $attach_id;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata($attach_id, $uploaded['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return new WP_REST_Response([
            'id' => $attach_id,
            'url' => $uploaded['url'],
            'type' => $uploaded['type'],
            'title' => $attachment['post_title'],
        ], 201);
    }

    // ========================================
    // CATEGORIES & TAGS
    // ========================================

    public function get_categories(WP_REST_Request $request): WP_REST_Response {
        $terms = get_terms(['taxonomy' => 'category', 'hide_empty' => false]);
        return new WP_REST_Response(['categories' => $this->format_terms($terms)], 200);
    }

    public function create_category(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $name = $request->get_param('name');
        if (!$name) {
            return new WP_Error('missing_name', __('Category name is required.', 'openclaw-wp'), ['status' => 400]);
        }

        $result = wp_insert_term($name, 'category', [
            'slug' => $request->get_param('slug') ?: '',
            'description' => $request->get_param('description') ?: '',
        ]);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response([
            'id' => $result['term_id'],
            'name' => $name,
        ], 201);
    }

    public function get_tags(WP_REST_Request $request): WP_REST_Response {
        $terms = get_terms(['taxonomy' => 'post_tag', 'hide_empty' => false]);
        return new WP_REST_Response(['tags' => $this->format_terms($terms)], 200);
    }

    public function create_tag(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $name = $request->get_param('name');
        if (!$name) {
            return new WP_Error('missing_name', __('Tag name is required.', 'openclaw-wp'), ['status' => 400]);
        }

        $result = wp_insert_term($name, 'post_tag', [
            'slug' => $request->get_param('slug') ?: '',
            'description' => $request->get_param('description') ?: '',
        ]);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response([
            'id' => $result['term_id'],
            'name' => $name,
        ], 201);
    }

    // ========================================
    // AI GENERATION
    // ========================================

    public function ai_generate_post(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $ai = new OpenClaw_AI();
        $result = $ai->generate_post($request->get_params());

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response($result, 200);
    }

    public function ai_generate_page(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $ai = new OpenClaw_AI();
        $result = $ai->generate_page($request->get_params());

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response($result, 200);
    }

    public function ai_generate_seo(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $ai = new OpenClaw_AI();
        $result = $ai->generate_seo($request->get_params());

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response($result, 200);
    }

    // ========================================
    // SEO
    // ========================================

    public function seo_analyze(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        if (!$post) {
            return new WP_Error('not_found', __('Post not found.', 'openclaw-wp'), ['status' => 404]);
        }

        $seo = new OpenClaw_SEO();
        $analysis = $seo->analyze_post($post);

        return new WP_REST_Response($analysis, 200);
    }

    // ========================================
    // API KEYS
    // ========================================

    public function get_api_keys(WP_REST_Request $request): WP_REST_Response {
        $user = $this->get_authenticated_user($request);
        if (is_wp_error($user)) {
            return new WP_REST_Response(['error' => $user->get_error_message()], 401);
        }

        $keys = OpenClaw_Auth::get_user_api_keys($user->ID);

        return new WP_REST_Response(['keys' => $keys], 200);
    }

    public function create_api_key(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user = $this->get_authenticated_user($request);
        if (is_wp_error($user)) {
            return new WP_REST_Response(['error' => $user->get_error_message()], 401);
        }

        $result = OpenClaw_Auth::generate_api_key(
            $user->ID,
            $request->get_param('name') ?: 'OpenClaw Key',
            $request->get_param('permissions') ?: [],
            $request->get_param('expires_at') ?: null
        );

        if (!$result['success']) {
            return new WP_Error('key_failed', $result['error'], ['status' => 500]);
        }

        return new WP_REST_Response([
            'api_key' => $result['api_key'],
            'id' => $result['id'],
            'message' => __('Save this key — it will not be shown again.', 'openclaw-wp'),
        ], 201);
    }

    public function revoke_api_key(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user = $this->get_authenticated_user($request);
        if (is_wp_error($user)) {
            return new WP_REST_Response(['error' => $user->get_error_message()], 401);
        }

        $key_id = $request->get_param('id');
        OpenClaw_Auth::revoke_api_key($key_id, $user->ID);

        return new WP_REST_Response(['revoked' => true], 200);
    }

    // ========================================
    // SITE INFO
    // ========================================

    public function get_site_info(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response([
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => get_bloginfo('url'),
            'home_url' => get_home_url(),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'openclaw_version' => OPENCLAW_WP_VERSION,
            'active_theme' => wp_get_theme()->get('Name'),
            'active_plugins' => count(get_option('active_plugins', [])),
            'total_posts' => wp_count_posts()->publish,
            'total_pages' => wp_count_posts('page')->publish,
            'total_users' => count_users()['total_users'],
            'timezone' => wp_timezone_string(),
            'language' => get_locale(),
        ], 200);
    }

    // ========================================
    // HELPERS
    // ========================================

    private function get_posts_args(): array {
        return [
            'per_page' => ['type' => 'integer', 'default' => 20],
            'page' => ['type' => 'integer', 'default' => 1],
            'status' => ['type' => 'string', 'default' => 'any'],
            'search' => ['type' => 'string', 'default' => ''],
            'orderby' => ['type' => 'string', 'default' => 'date'],
            'order' => ['type' => 'string', 'default' => 'DESC'],
        ];
    }

    private function get_post_create_args(): array {
        return [
            'title' => ['type' => 'string'],
            'content' => ['type' => 'string'],
            'excerpt' => ['type' => 'string'],
            'status' => ['type' => 'string', 'default' => 'draft'],
            'slug' => ['type' => 'string'],
            'categories' => ['type' => 'array', 'default' => []],
            'tags' => ['type' => 'array', 'default' => []],
            'featured_image' => ['type' => 'integer'],
            'meta' => ['type' => 'object', 'default' => []],
        ];
    }

    private function get_post_data(WP_REST_Request $request): array {
        $data = [];

        if ($request->get_param('title')) {
            $data['post_title'] = sanitize_text_field($request->get_param('title'));
        }
        if ($request->get_param('content')) {
            $data['post_content'] = wp_kses_post($request->get_param('content'));
        }
        if ($request->get_param('excerpt')) {
            $data['post_excerpt'] = sanitize_text_field($request->get_param('excerpt'));
        }
        if ($request->get_param('status')) {
            $data['post_status'] = sanitize_text_field($request->get_param('status'));
        }
        if ($request->get_param('slug')) {
            $data['post_name'] = sanitize_title($request->get_param('slug'));
        }

        return $data;
    }

    private function set_post_meta(WP_REST_Request $request, int $post_id): void {
        $meta = $request->get_param('meta');
        if (is_array($meta)) {
            foreach ($meta as $key => $value) {
                update_post_meta($post_id, sanitize_key($key), sanitize_text_field($value));
            }
        }

        $featured_image = $request->get_param('featured_image');
        if ($featured_image) {
            set_post_thumbnail($post_id, $featured_image);
        }
    }

    private function set_post_terms(WP_REST_Request $request, int $post_id): void {
        $categories = $request->get_param('categories');
        if (is_array($categories)) {
            wp_set_post_categories($post_id, array_map('intval', $categories));
        }

        $tags = $request->get_param('tags');
        if (is_array($tags)) {
            wp_set_post_tags($post_id, array_map('intval', $tags));
        }
    }

    private function format_post(WP_Post $post, bool $full = false): array {
        $data = [
            'id' => (int) $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'type' => $post->post_type,
            'date' => $post->post_date,
            'modified' => $post->post_modified,
            'author' => (int) $post->post_author,
            'author_name' => get_the_author_meta('display_name', $post->post_author),
            'link' => get_permalink($post->ID),
        ];

        if ($full) {
            $data['content'] = $post->post_content;
            $data['excerpt'] = $post->post_excerpt;
            $data['categories'] = wp_get_post_categories($post->ID, ['fields' => 'all']);
            $data['tags'] = wp_get_post_tags($post->ID, ['fields' => 'all']);
            $data['featured_image'] = get_the_post_thumbnail_url($post->ID, 'full');
            $data['meta'] = get_post_meta($post->ID);
        }

        return $data;
    }

    private function format_user(WP_User $user, bool $full = false): array {
        $data = [
            'id' => (int) $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'role' => $user->roles[0] ?? '',
            'registered' => $user->user_registered,
        ];

        if ($full) {
            $data['first_name'] = $user->first_name;
            $data['last_name'] = $user->last_name;
            $data['roles'] = $user->roles;
            $data['caps'] = array_keys(array_filter($user->allcaps));
        }

        return $data;
    }

    private function format_terms(array $terms): array {
        $result = [];
        foreach ($terms as $term) {
            $result[] = [
                'id' => (int) $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'count' => (int) $term->count,
            ];
        }
        return $result;
    }
}
