<?php
/**
 * SEO analysis and optimization
 */

if (!defined('ABSPATH')) {
    exit;
}

class OpenClaw_SEO {

    /**
     * Analyze a post/page for SEO
     */
    public function analyze_post(WP_Post $post): array {
        $content = wp_strip_all_tags($post->post_content);
        $title = $post->post_title;
        $excerpt = $post->post_excerpt;

        $word_count = str_word_count($content);
        $reading_time = ceil($word_count / 200);

        // Keyword density
        $focus_keyword = get_post_meta($post->ID, '_openclaw_focus_keyword', true);
        $keyword_density = 0;
        if ($focus_keyword) {
            $keyword_count = substr_count(strtolower($content), strtolower($focus_keyword));
            $keyword_density = $word_count > 0 ? round(($keyword_count / $word_count) * 100, 2) : 0;
        }

        // Heading structure
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $post->post_content, $heading_matches);
        $headings = [];
        foreach ($heading_matches[0] as $i => $heading) {
            $headings[] = [
                'level' => (int) $heading_matches[1][$i],
                'text' => wp_strip_all_tags($heading_matches[2][$i]),
            ];
        }

        // Image analysis
        preg_match_all('/<img[^>]+>/i', $post->post_content, $img_matches);
        $total_images = count($img_matches[0]);
        $images_without_alt = 0;
        foreach ($img_matches[0] as $img) {
            if (!preg_match('/alt=["\'][^"\']+["\']/i', $img)) {
                $images_without_alt++;
            }
        }

        // Link analysis
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $post->post_content, $link_matches);
        $total_links = count($link_matches[1]);
        $internal_links = 0;
        $external_links = 0;
        $site_url = parse_url(home_url(), PHP_URL_HOST);
        foreach ($link_matches[1] as $url) {
            $link_host = parse_url($url, PHP_URL_HOST);
            if ($link_host === $site_url || empty($link_host)) {
                $internal_links++;
            } else {
                $external_links++;
            }
        }

        // Meta description
        $meta_description = get_post_meta($post->ID, '_openclaw_meta_description', true);
        $seo_title = get_post_meta($post->ID, '_openclaw_seo_title', true);

        // Score calculation
        $score = $this->calculate_seo_score([
            'word_count' => $word_count,
            'has_focus_keyword' => !empty($focus_keyword),
            'keyword_density' => $keyword_density,
            'has_meta_description' => !empty($meta_description),
            'meta_description_length' => strlen($meta_description),
            'has_seo_title' => !empty($seo_title),
            'title_length' => strlen($title),
            'has_headings' => count($headings) > 0,
            'has_images' => $total_images > 0,
            'images_without_alt' => $images_without_alt,
            'has_internal_links' => $internal_links > 0,
            'has_excerpt' => !empty($excerpt),
        ]);

        // Recommendations
        $recommendations = $this->get_recommendations([
            'word_count' => $word_count,
            'has_focus_keyword' => !empty($focus_keyword),
            'keyword_density' => $keyword_density,
            'has_meta_description' => !empty($meta_description),
            'meta_description_length' => strlen($meta_description),
            'title_length' => strlen($title),
            'has_headings' => count($headings) > 0,
            'images_without_alt' => $images_without_alt,
            'has_internal_links' => $internal_links > 0,
        ]);

        return [
            'post_id' => (int) $post->ID,
            'title' => $title,
            'score' => $score,
            'max_score' => 100,
            'grade' => $this->score_to_grade($score),
            'analysis' => [
                'word_count' => $word_count,
                'reading_time_minutes' => $reading_time,
                'focus_keyword' => $focus_keyword ?: null,
                'keyword_density_percent' => $keyword_density,
                'headings' => $headings,
                'heading_count' => count($headings),
                'images' => [
                    'total' => $total_images,
                    'without_alt' => $images_without_alt,
                ],
                'links' => [
                    'total' => $total_links,
                    'internal' => $internal_links,
                    'external' => $external_links,
                ],
                'seo_title' => $seo_title ?: null,
                'meta_description' => $meta_description ?: null,
                'has_excerpt' => !empty($excerpt),
            ],
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Calculate SEO score (0-100)
     */
    private function calculate_seo_score(array $data): int {
        $score = 0;

        // Word count (max 15)
        if ($data['word_count'] >= 1500) {
            $score += 15;
        } elseif ($data['word_count'] >= 800) {
            $score += 10;
        } elseif ($data['word_count'] >= 300) {
            $score += 5;
        }

        // Focus keyword (max 15)
        if ($data['has_focus_keyword']) {
            $score += 10;
            if ($data['keyword_density'] >= 0.5 && $data['keyword_density'] <= 2.5) {
                $score += 5; // Optimal density
            } elseif ($data['keyword_density'] > 0) {
                $score += 2;
            }
        }

        // Meta description (max 15)
        if ($data['has_meta_description']) {
            $score += 10;
            if ($data['meta_description_length'] >= 120 && $data['meta_description_length'] <= 160) {
                $score += 5;
            }
        }

        // SEO title (max 10)
        if ($data['has_seo_title']) {
            $score += 10;
        } elseif ($data['title_length'] >= 30 && $data['title_length'] <= 60) {
            $score += 5;
        }

        // Headings (max 10)
        if ($data['has_headings']) {
            $score += 10;
        }

        // Images (max 10)
        if ($data['images_without_alt'] === 0) {
            $score += 10;
        }

        // Internal links (max 10)
        if ($data['has_internal_links']) {
            $score += 10;
        }

        // Excerpt (max 5)
        if ($data['has_excerpt']) {
            $score += 5;
        }

        return min(100, $score);
    }

    /**
     * Get SEO recommendations
     */
    private function get_recommendations(array $data): array {
        $recommendations = [];

        if ($data['word_count'] < 300) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => __('Content is too short. Aim for at least 300 words, ideally 800-1500.', 'openclaw-wp'),
            ];
        } elseif ($data['word_count'] < 800) {
            $recommendations[] = [
                'type' => 'info',
                'message' => __('Consider expanding content to 800+ words for better SEO.', 'openclaw-wp'),
            ];
        }

        if (!$data['has_focus_keyword']) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => __('No focus keyword set. Add a target keyword for better ranking.', 'openclaw-wp'),
            ];
        } elseif ($data['keyword_density'] > 3) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => __('Keyword density is too high. Reduce to avoid keyword stuffing.', 'openclaw-wp'),
            ];
        } elseif ($data['keyword_density'] < 0.5 && $data['has_focus_keyword']) {
            $recommendations[] = [
                'type' => 'info',
                'message' => __('Use your focus keyword more naturally throughout the content.', 'openclaw-wp'),
            ];
        }

        if (!$data['has_meta_description']) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => __('No meta description. Add one to improve click-through rates.', 'openclaw-wp'),
            ];
        } elseif ($data['meta_description_length'] > 160) {
            $recommendations[] = [
                'type' => 'info',
                'message' => __('Meta description is too long. Keep it under 160 characters.', 'openclaw-wp'),
            ];
        }

        if ($data['title_length'] > 60) {
            $recommendations[] = [
                'type' => 'info',
                'message' => __('Title is too long. Keep it under 60 characters for search results.', 'openclaw-wp'),
            ];
        } elseif ($data['title_length'] < 20) {
            $recommendations[] = [
                'type' => 'info',
                'message' => __('Title is too short. Aim for 30-60 characters.', 'openclaw-wp'),
            ];
        }

        if (!$data['has_headings']) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => __('No headings found. Use H2 and H3 tags to structure your content.', 'openclaw-wp'),
            ];
        }

        if ($data['images_without_alt'] > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => sprintf(
                    _n(
                        '%d image missing alt text. Add descriptive alt tags for accessibility and SEO.',
                        '%d images missing alt text. Add descriptive alt tags for accessibility and SEO.',
                        $data['images_without_alt'],
                        'openclaw-wp'
                    ),
                    $data['images_without_alt']
                ),
            ];
        }

        if (!$data['has_internal_links']) {
            $recommendations[] = [
                'type' => 'info',
                'message' => __('Add internal links to related content to improve site structure.', 'openclaw-wp'),
            ];
        }

        return $recommendations;
    }

    /**
     * Convert numeric score to letter grade
     */
    private function score_to_grade(int $score): string {
        return match (true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default => 'F',
        };
    }
}
