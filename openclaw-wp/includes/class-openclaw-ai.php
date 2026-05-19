<?php
/**
 * AI content generation — integrates with OpenClaw's LiteLLM proxy or any OpenAI-compatible endpoint
 */

if (!defined('ABSPATH')) {
    exit;
}

class OpenClaw_AI {

    private string $endpoint;
    private string $api_key;
    private string $model;

    public function __construct() {
        $this->endpoint = get_option('openclaw_ai_endpoint', '');
        $this->api_key = get_option('openclaw_ai_api_key', '');
        $this->model = get_option('openclaw_ai_model', 'gpt-4o');
    }

    /**
     * Check if AI is configured and enabled
     */
    public function is_available(): bool {
        return !empty($this->endpoint) && !empty($this->api_key) && get_option('openclaw_enable_ai', 1);
    }

    /**
     * Generate a blog post
     */
    public function generate_post(array $params): array|WP_Error {
        if (!$this->is_available()) {
            return new WP_Error('ai_not_configured', __('AI is not configured. Set the endpoint and API key in OpenClaw settings.', 'openclaw-wp'));
        }

        $topic = $params['topic'] ?? '';
        $tone = $params['tone'] ?? 'professional';
        $length = $params['length'] ?? 'medium';
        $keywords = $params['keywords'] ?? [];
        $category = $params['category'] ?? '';

        if (empty($topic)) {
            return new WP_Error('missing_topic', __('Topic is required.', 'openclaw-wp'));
        }

        $length_words = match ($length) {
            'short' => '300-500',
            'medium' => '800-1200',
            'long' => '1500-2500',
            default => '800-1200',
        };

        $system_prompt = "You are an expert content writer for WordPress. Generate a well-structured blog post in HTML format. Use proper headings (h2, h3), paragraphs, and lists. Do not include the title in the content — it will be set separately.";

        $user_prompt = "Write a {$length_words} word blog post about: {$topic}\n\n";
        $user_prompt .= "Tone: {$tone}\n";
        $user_prompt .= "Length: {$length_words} words\n";

        if (!empty($keywords)) {
            $user_prompt .= "Target keywords: " . implode(', ', $keywords) . "\n";
        }
        if ($category) {
            $user_prompt .= "Category: {$category}\n";
        }

        $user_prompt .= "\nReturn a JSON object with this exact structure:\n";
        $user_prompt .= '{"title": "SEO-optimized title", "excerpt": "Compelling 2-3 sentence excerpt", "content": "Full HTML content", "tags": ["tag1", "tag2", "tag3"], "meta_description": "SEO meta description under 160 characters"}';

        $response = $this->call_ai($system_prompt, $user_prompt);

        if (is_wp_error($response)) {
            return $response;
        }

        $parsed = $this->parse_json_response($response);
        if (is_wp_error($parsed)) {
            return $parsed;
        }

        // Auto-create the post if requested
        if (!empty($params['auto_create'])) {
            $parsed['post_id'] = $this->create_post_from_ai($parsed, $params);
        }

        return $parsed;
    }

    /**
     * Generate a page
     */
    public function generate_page(array $params): array|WP_Error {
        if (!$this->is_available()) {
            return new WP_Error('ai_not_configured', __('AI is not configured.', 'openclaw-wp'));
        }

        $topic = $params['topic'] ?? '';
        $template = $params['template'] ?? 'default';

        if (empty($topic)) {
            return new WP_Error('missing_topic', __('Topic is required.', 'openclaw-wp'));
        }

        $system_prompt = "You are an expert WordPress page creator. Generate a well-structured page in HTML format. Use proper headings, sections, and calls-to-action where appropriate.";

        $user_prompt = "Create a WordPress page about: {$topic}\n";
        $user_prompt .= "Template style: {$template}\n\n";
        $user_prompt .= "Return JSON: {\"title\": \"Page title\", \"content\": \"Full HTML content\", \"excerpt\": \"Brief description\"}";

        $response = $this->call_ai($system_prompt, $user_prompt);

        if (is_wp_error($response)) {
            return $response;
        }

        $parsed = $this->parse_json_response($response);
        if (is_wp_error($parsed)) {
            return $parsed;
        }

        if (!empty($params['auto_create'])) {
            $parsed['post_id'] = $this->create_page_from_ai($parsed, $params);
        }

        return $parsed;
    }

    /**
     * Generate SEO metadata for existing or new content
     */
    public function generate_seo(array $params): array|WP_Error {
        if (!$this->is_available()) {
            return new WP_Error('ai_not_configured', __('AI is not configured.', 'openclaw-wp'));
        }

        $content = $params['content'] ?? '';
        $title = $params['title'] ?? '';
        $keywords = $params['keywords'] ?? [];
        $post_id = $params['post_id'] ?? 0;

        // If post_id provided, fetch content
        if ($post_id) {
            $post = get_post($post_id);
            if ($post) {
                $content = $content ?: wp_strip_all_tags($post->post_content);
                $title = $title ?: $post->post_title;
            }
        }

        if (empty($content) && empty($title)) {
            return new WP_Error('missing_content', __('Content or post_id is required.', 'openclaw-wp'));
        }

        $system_prompt = "You are an SEO expert. Analyze the content and generate optimized SEO metadata. Return ONLY valid JSON.";

        $user_prompt = "Generate SEO metadata for this content:\n\n";
        if ($title) {
            $user_prompt .= "Title: {$title}\n";
        }
        $user_prompt .= "Content: " . wp_trim_words($content, 100) . "\n";
        if (!empty($keywords)) {
            $user_prompt .= "Target keywords: " . implode(', ', $keywords) . "\n";
        }

        $user_prompt .= "\nReturn JSON: {\"seo_title\": \"Optimized title (50-60 chars)\", \"meta_description\": \"Compelling description (150-160 chars)\", \"focus_keyword\": \"Primary keyword\", \"secondary_keywords\": [\"kw1\", \"kw2\"], \"slug_suggestion\": \"url-friendly-slug\", \"og_title\": \"Social media title\", \"og_description\": \"Social media description\"}";

        $response = $this->call_ai($system_prompt, $user_prompt);

        if (is_wp_error($response)) {
            return $response;
        }

        $parsed = $this->parse_json_response($response);
        if (is_wp_error($parsed)) {
            return $parsed;
        }

        // Auto-apply to post if requested
        if ($post_id && !empty($params['auto_apply'])) {
            $this->apply_seo_to_post($post_id, $parsed);
            $parsed['applied'] = true;
            $parsed['post_id'] = $post_id;
        }

        return $parsed;
    }

    /**
     * Call the AI endpoint (OpenAI-compatible)
     */
    private function call_ai(string $system_prompt, string $user_prompt): string|WP_Error {
        $body = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $user_prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 4096,
            'response_format' => ['type' => 'json_object'],
        ];

        $response = wp_remote_post($this->endpoint . '/chat/completions', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body' => wp_json_encode($body),
            'timeout' => 120,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('ai_request_failed', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = wp_remote_retrieve_body($response);
            return new WP_Error('ai_api_error', "AI API returned HTTP {$code}: {$body}");
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('ai_parse_error', __('Failed to parse AI response.', 'openclaw-wp'));
        }

        return $data['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Parse JSON from AI response
     */
    private function parse_json_response(string $response): array|WP_Error {
        // Try to extract JSON if wrapped in markdown code blocks
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $response, $matches)) {
            $response = $matches[1];
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('ai_json_error', __('AI returned invalid JSON.', 'openclaw-wp'));
        }

        return $data;
    }

    /**
     * Create a WordPress post from AI-generated data
     */
    private function create_post_from_ai(array $data, array $params): int|WP_Error {
        $post_data = [
            'post_title' => sanitize_text_field($data['title'] ?? 'Untitled'),
            'post_content' => wp_kses_post($data['content'] ?? ''),
            'post_excerpt' => sanitize_text_field($data['excerpt'] ?? ''),
            'post_status' => $params['post_status'] ?? 'draft',
            'post_type' => 'post',
        ];

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Set tags
        if (!empty($data['tags']) && is_array($data['tags'])) {
            wp_set_post_tags($post_id, array_map('sanitize_text_field', $data['tags']));
        }

        // Set category
        if (!empty($params['category_id'])) {
            wp_set_post_categories($post_id, [intval($params['category_id'])]);
        }

        // Store meta description
        if (!empty($data['meta_description'])) {
            update_post_meta($post_id, '_openclaw_meta_description', sanitize_text_field($data['meta_description']));
        }

        return $post_id;
    }

    /**
     * Create a WordPress page from AI-generated data
     */
    private function create_page_from_ai(array $data, array $params): int|WP_Error {
        $post_data = [
            'post_title' => sanitize_text_field($data['title'] ?? 'Untitled'),
            'post_content' => wp_kses_post($data['content'] ?? ''),
            'post_excerpt' => sanitize_text_field($data['excerpt'] ?? ''),
            'post_status' => $params['post_status'] ?? 'draft',
            'post_type' => 'page',
        ];

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        return $post_id;
    }

    /**
     * Apply SEO metadata to a post
     */
    private function apply_seo_to_post(int $post_id, array $seo): void {
        if (!empty($seo['meta_description'])) {
            update_post_meta($post_id, '_openclaw_meta_description', sanitize_text_field($seo['meta_description']));
        }
        if (!empty($seo['focus_keyword'])) {
            update_post_meta($post_id, '_openclaw_focus_keyword', sanitize_text_field($seo['focus_keyword']));
        }
        if (!empty($seo['seo_title'])) {
            update_post_meta($post_id, '_openclaw_seo_title', sanitize_text_field($seo['seo_title']));
        }
        if (!empty($seo['og_title'])) {
            update_post_meta($post_id, '_openclaw_og_title', sanitize_text_field($seo['og_title']));
        }
        if (!empty($seo['og_description'])) {
            update_post_meta($post_id, '_openclaw_og_description', sanitize_text_field($seo['og_description']));
        }
    }
}
