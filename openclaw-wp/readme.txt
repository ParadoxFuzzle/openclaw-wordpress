=== OpenClaw for WordPress ===
Contributors: openclaw
Tags: openclaw, ai, rest-api, content-generation, seo, automation
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete OpenClaw integration for WordPress. REST API management, AI content generation, and SEO optimization.

== Description ==

OpenClaw for WordPress gives your OpenClaw AI assistant full programmatic control over your WordPress site via a comprehensive REST API, plus built-in AI content generation and SEO analysis.

**Features:**

* **Full REST API** — Manage posts, pages, plugins, themes, users, media, categories, and tags
* **AI Content Generation** — Generate blog posts, pages, and SEO metadata using any OpenAI-compatible endpoint (LiteLLM, OpenAI, Groq, etc.)
* **SEO Analysis** — Analyze posts and pages for SEO health with actionable recommendations and scoring
* **Dual Authentication** — Supports WordPress Application Passwords and custom API keys
* **Role-Based Access** — Admins get full access; editors and authors get content creation permissions
* **Admin Dashboard** — Settings page with AI configuration, SEO settings, and API documentation

**AI Integration:**

Connect to any OpenAI-compatible API endpoint:
* OpenClaw LiteLLM proxy
* OpenAI API
* Groq
* Cerebras
* Mistral
* Any OpenAI-compatible endpoint

**REST API Endpoints:**

* `GET/POST /openclaw/v1/posts` — List/create posts
* `GET/PUT/DELETE /openclaw/v1/posts/{id}` — Read/update/delete posts
* `GET/POST /openclaw/v1/pages` — List/create pages
* `GET/PUT/DELETE /openclaw/v1/pages/{id}` — Read/update/delete pages
* `GET /openclaw/v1/plugins` — List plugins
* `PUT /openclaw/v1/plugins/{file}` — Activate/deactivate plugins
* `GET /openclaw/v1/themes` — List themes
* `PUT /openclaw/v1/themes/{stylesheet}` — Switch theme
* `GET/POST /openclaw/v1/users` — List/create users
* `GET/PUT/DELETE /openclaw/v1/users/{id}` — Read/update/delete users
* `POST /openclaw/v1/media` — Upload media
* `GET/POST /openclaw/v1/categories` — List/create categories
* `GET/POST /openclaw/v1/tags` — List/create tags
* `POST /openclaw/v1/ai/generate-post` — AI generate a blog post
* `POST /openclaw/v1/ai/generate-page` — AI generate a page
* `POST /openclaw/v1/ai/generate-seo` — AI generate SEO metadata
* `GET /openclaw/v1/seo/analyze/{id}` — Analyze post SEO
* `GET/POST/DELETE /openclaw/v1/keys` — Manage API keys
* `GET /openclaw/v1/site` — Get site information

== Installation ==

1. Upload the `openclaw-wp` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'OpenClaw' in the admin menu
4. Configure your AI endpoint and API key under AI Settings
5. Create an Application Password in your WordPress user profile
6. Start using the REST API endpoints

== Authentication ==

All API requests require authentication via one of:

**Application Passwords (recommended):**
1. Go to your WordPress user profile
2. Scroll to "Application Passwords"
3. Enter a name and click "Add New Application Password"
4. Use HTTP Basic Auth: `Authorization: Basic base64(username:password)`

**API Keys:**
1. Use the REST API to create a key: `POST /openclaw/v1/keys`
2. Send with requests: `X-OpenClaw-API-Key: your-key`

== Frequently Asked Questions ==

= What AI endpoints are supported? =

Any OpenAI-compatible API endpoint. This includes OpenAI, LiteLLM, Groq, Cerebras, Mistral, and others.

= Can editors and authors use the API? =

Yes. Editors get content management and AI generation permissions. Authors get post creation and AI generation permissions. Only admins can manage plugins, themes, users, and API keys.

= Does this work with the native WordPress REST API? =

This plugin adds its own namespace (`/openclaw/v1/`) alongside the native WordPress REST API. It does not modify or interfere with the native API.

== Changelog ==

= 1.0.0 =
* Initial release
* Full REST API for site management
* AI content generation (posts, pages, SEO)
* SEO analysis with scoring and recommendations
* Application Password and API key authentication
* Role-based access control
* Admin settings dashboard
