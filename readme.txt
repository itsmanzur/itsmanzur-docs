=== Nirdeshio – Documentation, Knowledge Base & Help Center for WordPress ===
Contributors: itsmanzur
Tags: documentation, knowledge base, docs, help center, product docs
Requires at least: 6.5
Tested up to: 7.1
Stable tag: 1.2.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Product documentation, knowledge base, and help center for WordPress — fast, theme-independent, and easy to set up in 5 minutes.

== Description ==

**Nirdeshio** is a product documentation and knowledge base plugin built for WordPress. It helps software companies, plugin/theme developers, SaaS founders, WooCommerce sellers, and agencies publish beautiful, fast-loading documentation without slowing down their sites.

= Why Nirdeshio? =

Most documentation plugins load heavy JavaScript, conflict with popular themes, or require dozens of settings before your first article appears. Nirdeshio is different:

* **5-minute setup** — A built-in wizard creates your first documentation portal and publishes it before your coffee gets cold.
* **Zero theme conflict** — Every frontend element is scoped under `.itsdz-docs`, so Nirdeshio never leaks styles into your theme.
* **Ultra-light frontend** — Less than 30 KB of JavaScript (gzipped). The search modal loads on-demand only when the user opens it.
* **No jQuery** — Built with modern Vanilla TypeScript and React for the admin.
* **Performance-first** — Views are buffered and written to the database in batches every 5 minutes, not on every page load.

= Free Features =

* Documentation projects (knowledge bases) — no cap, create as many as you need
* Unlimited sections (3 levels deep), unlimited articles
* **Sections manager** — a dedicated screen to create, rename, re-nest, icon, and delete sections without touching individual articles
* Drag-and-drop article ordering
* Gutenberg block editor support + shortcodes
* **Instant full-text search** (MySQL FULLTEXT — no `LIKE %keyword%`)
* Auto-generated table of contents, breadcrumbs, Previous / Next navigation
* Related articles
* Light / dark mode (respects system preference, user-toggleable)
* Print-friendly layout
* Mobile slide-out navigation
* "Was this helpful?" feedback system
* Reading time estimate, author byline, and last-updated date
* Content freshness tracking — log a review date per article and spot stale docs at a glance
* **WordPress dashboard widget** — see draft, stale, and unsectioned article counts without leaving your Dashboard
* Basic view counter (buffered, performance-safe)
* Two layout modes: **canvas** (full-page, no theme header/footer) and **theme** (integrates with your active theme)
* Three template styles: **Clean**, **Modern**, **Compact**
* Custom brand color per knowledge base
* **Project logo and landing intro** — upload a logo and write a custom intro line for each knowledge base's homepage
* **Custom navigation styles** — choose Accordion, Rail, or Tree for how the sidebar organizes sections and articles
* **Header links** — add up to 4 custom links (e.g. "Contact support", "Changelog") to the documentation header
* **Custom CSS per project** — fine-tune the look of a specific knowledge base without a child theme
* Import / export (JSON)
* **Markdown import** — drop in a `.md` file and it lands as a draft article, ready to review and publish
* One-click sample content — publishes a complete demo knowledge base so you can see the result before writing anything, and removes it just as easily
* **Glossary** — define your product's terminology once; the first mention of each term in an article is highlighted with a tooltip definition, and the full list can be embedded anywhere
* **WooCommerce product docs** — link articles to products; linked products get a "Documentation" tab
* Translation-ready, RTL support
* SEO plugin compatible (Yoast SEO, Rank Math, AIOSEO)

= AI-Ready Documentation =

Assistants like ChatGPT, Claude and Perplexity increasingly read documentation on behalf of your users. Nirdeshio makes that work properly instead of leaving them to guess from rendered HTML.

* **`/llms.txt`** — an automatically generated, always up-to-date map of your documentation following the [llmstxt.org](https://llmstxt.org/) convention. One line per article with a short summary, grouped by project.
* **`/llms-full.txt`** — the same index plus the complete plain-text body of every article, for assistants that can ingest the whole corpus.
* **Copy as Markdown** — a button on every article that copies clean Markdown to the clipboard, ready to paste into an AI chat, an issue, or a pull request.

Only published articles in published projects are ever included. Both routes are cached and can be switched off with a single filter:

`add_filter( 'itsdz_llms_txt_enabled', '__return_false' );`

= Gutenberg Blocks =

* **Nirdeshio Search** — Embed a search box anywhere on your site. Submits to your KB page and auto-opens the search modal with the entered query pre-filled.
* **Nirdeshio Article List** — Display a linked list of articles from any KB. Ideal for sidebars, landing pages, or related-content widgets.
* **Nirdeshio Popular Articles** — Rank articles by view count or publish date. Great for "Top questions" sections on a support landing page.
* **Nirdeshio FAQ** — Build a collapsible question-and-answer list. Automatically outputs FAQPage structured data so your questions can appear directly in search results.
* **Nirdeshio Glossary** — Display every defined term with its definition, sorted alphabetically.
* **Nirdeshio Callout** — A coloured note, tip, warning, or danger box inside an article. Insert it from the block inserter while writing.

= Shortcodes =

* `[nirdeshio_docs]` — published documentation list (optional `kb_id`)
* `[nirdeshio_search kb_id="123"]` — search form for a specific KB
* `[nirdeshio_docs_list kb_id="123" limit="5" show_section="true"]` — article list
* `[nirdeshio_popular_docs kb_id="123" limit="5" order="popular"]` — most viewed (or `order="recent"`) articles
* `[nirdeshio_faq heading="Billing"]` — FAQ list; write one `Question | Answer` pair per line between the opening and closing tags
* `[nirdeshio_glossary heading="Glossary" show_aliases="true"]` — alphabetical list of every glossary term

Glossary auto-highlighting never touches links, headings or code samples, and can be switched off entirely:

`add_filter( 'itsdz_glossary_autolink', '__return_false' );`

= WooCommerce Integration =

Selling a product? Link its documentation to it.

* In the article editor, a **WooCommerce products** panel lets you search and link one or more products to the article.
* Linked products automatically get a **Documentation** tab listing every published article linked to them.
* Only published articles in published knowledge bases are shown, so drafts never leak.
* Nothing loads unless WooCommerce is active.

= Privacy =

Nirdeshio does not send any data to external servers. The "Was this helpful?" feedback and view counts are stored locally in your WordPress database. IP addresses are stored as irreversible hashes (SHA-256) for rate-limiting purposes only.

Optional setup analytics are **off by default**. If you check “Help improve Nirdeshio by sharing anonymous setup analytics” during the onboarding wizard, Nirdeshio stores only a step number and a timestamp in your local database so you can see setup drop-off in Settings. No personal data, IP address, email, or documentation content is recorded, and nothing is sent to Nirdeshio or any other remote server.

When uninstalling, data is deleted only if you opt in to data removal in the plugin settings. By default, your content and settings are preserved.

= Source Code =

Source code and build instructions: https://github.com/itsmanzur/itsmanzur-docs/

== Installation ==

**Automatic installation (recommended)**

1. Log in to your WordPress admin panel and go to **Plugins → Add New**.
2. Search for **Nirdeshio**.
3. Click **Install Now**, then **Activate**.

**Manual installation**

1. Download the plugin ZIP from the WordPress.org plugin directory.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Choose the ZIP file and click **Install Now**, then **Activate**.

**After activation**

1. Go to **Nirdeshio** in your admin sidebar.
2. The setup wizard offers a two-minute guided path. You can skip it at any time — it is never required.

== Frequently Asked Questions ==

= Will Nirdeshio conflict with my theme? =

No. Nirdeshio uses two layout modes. In **canvas mode** (the default), it renders a fully independent page that bypasses your theme entirely. In **theme mode**, it integrates with your theme's header and footer, and all CSS is scoped under `.itsdz-docs` so it cannot affect other parts of your site.

= Does Nirdeshio slow down my site? =

No — in fact, we designed Nirdeshio specifically to avoid the performance problems common in other documentation plugins:

* Assets load **only on documentation pages** (conditional enqueue).
* The search modal JavaScript is loaded **on-demand**, only when the user opens the search.
* Page views are written to the database in **5-minute batches**, never on every request.
* The frontend bundle is under **30 KB gzipped**.

= How is Nirdeshio search different from the built-in WordPress search? =

Nirdeshio uses a dedicated search index table with MySQL **FULLTEXT** indexing. It never runs `LIKE %keyword%` queries against `wp_posts`, which are slow and load your entire database. Searches are also cached in the object cache (Redis / Memcached if available, transients otherwise).

= Can I use Nirdeshio with the Classic Editor? =

Yes. `[nirdeshio_docs]`, `[nirdeshio_search]`, and `[nirdeshio_docs_list]` shortcodes work in any editor, widget, or page builder that supports shortcodes.

= Is Nirdeshio compatible with page caching plugins? =

Yes. The feedback ("Was this helpful?") system uses JavaScript + REST API instead of PHP sessions, so it works correctly behind WP Rocket, LiteSpeed Cache, W3 Total Cache, and similar caching plugins.

= Can I translate Nirdeshio? =

Yes. Nirdeshio is fully internationalized (i18n). All strings use the `itsmanzur-docs` text domain. Translations can be contributed on translate.wordpress.org.

= Does Nirdeshio work with RTL languages? =

Yes. The compiled CSS includes an automatically generated RTL stylesheet (`style-frontend-rtl.css`) loaded by WordPress when an RTL language is active.

= How many knowledge bases can I have in the free version? =

As many as you like — there is no limit in the free version.

= Where is my data stored? =

All data is stored in your WordPress database. Nirdeshio creates five custom tables: `{prefix}itsdz_search_index`, `{prefix}itsdz_search_log`, `{prefix}itsdz_feedback`, `{prefix}itsdz_views`, and `{prefix}itsdz_product_links` (which article is linked to which WooCommerce product). Articles and knowledge bases are stored as standard WordPress custom post types and can be exported like any other post type.

== Screenshots ==

1. Knowledge base landing page — hero section with instant search trigger, section cards, and article list.
2. Single documentation article — sidebar navigation, auto-generated table of contents, breadcrumbs, feedback widget, and related articles.
3. Instant search modal — opens on Ctrl+K or by clicking the search button, results appear as you type.
4. Setup Wizard — 5-step guided wizard for publishing your first documentation portal in under 5 minutes.
5. Documentation Tree Builder — drag-and-drop article ordering, inline status toggles, and auto-save.
6. Dark mode — automatically respects the visitor's system preference with a manual override toggle.

== Changelog ==

= 1.2.0 =
**New: WooCommerce product documentation, now free**

* Link articles to WooCommerce products from a sidebar panel in the article editor; linked products get a "Documentation" tab.
* Declares compatibility with WooCommerce High-Performance Order Storage.
* This feature previously lived in a separate add-on; it is now part of the plugin and there is no Pro version to buy. Existing product links keep working.

= 1.1.1 =
**New: Onboarding wizard**

* Added a skippable first-run setup (welcome, first section, navigation style, shortcode placement). Skip is always available; the wizard never blocks the dashboard.
* Added `[nirdeshio_docs]` to embed a published documentation list, plus an optional “auto-create a page” action during setup.
* Settings includes **Re-run setup wizard**, which resets only the wizard UI — existing sections and articles stay.
* Optional, local-only setup analytics (step number + timestamp) when you explicitly opt in. Nothing is sent off-site.

= 1.1.0 =
**New: Sections manager**

* Added a dedicated Sections screen — create, rename, re-nest, set a Dashicon, and delete sections without opening an article.

**New: Markdown import**

* Import a `.md` file directly as a draft article from the Import / Export screen.

**New: Project appearance & navigation**

* Added Accordion, Rail, and Tree navigation styles — pick how the sidebar organizes sections and articles per project.
* Added per-project logo upload and a customizable landing-page intro line.
* Added optional header links (up to 4) for linking out to support, changelog, or any custom page.
* Added per-project custom CSS.
* Table of contents, feedback, related articles, and print button can each now be toggled on or off per project.

**New: WordPress dashboard widget**

* Added a dashboard widget showing draft, stale (90+ days unreviewed), and unsectioned article counts.

**Shortcode names changed (breaking)**

* All shortcodes were renamed to match the plugin's public name: `[doczur_search]` → `[nirdeshio_search]`, and similarly for `doczur_docs_list`, `doczur_popular_docs`, `doczur_faq`, and `doczur_glossary`. There is no backward-compatible alias — if you used the old shortcode names anywhere, update them after upgrading.

**Fixes**

* The article list REST endpoint now paginates correctly instead of silently truncating at 100 articles.
* Saving a custom URL slug base now reliably flushes rewrite rules so the new slug takes effect immediately.

= 1.0.0 =
**Editor**

* Article editing now happens entirely in the native WordPress block editor (Gutenberg) — no HTML tags are ever shown, and every core block (images, tables, lists) is available.
* Added a "Nirdeshio" panel to the block editor's sidebar for section, version, tags and the "Mark reviewed today" control — no separate metadata form.
* Added a "Nirdeshio Callout" block (Info / Tip / Warning / Danger) for coloured notes inside an article, insertable from the regular block inserter.
* The Documentation screen is now a lightweight tree: search, drag-and-drop ordering, bulk actions, and a "+ New" button that opens straight into the editor.

**AI-ready documentation**

* Added `/llms.txt`, an automatically generated map of your documentation following the llmstxt.org convention.
* Added `/llms-full.txt`, which also includes the full text of every published article.
* Added a "Copy as Markdown" button to every article.
* Both routes are cached, exclude unpublished content, and can be disabled with the `itsdz_llms_txt_enabled` filter.

**Glossary**

* Added a glossary: define a term once and the first mention in each article gains a tooltip definition.
* Alternative spellings are supported, so plurals and abbreviations match too.
* Auto-highlighting never alters links, headings, code samples or HTML attributes, and can be disabled with the `itsdz_glossary_autolink` filter.
* Added the `itsdz/glossary` block and `[nirdeshio_glossary]` shortcode.

**New blocks and shortcodes**

* `itsdz/faq` and `[nirdeshio_faq]` — collapsible question lists that also emit FAQPage structured data.
* `itsdz/popular-docs` and `[nirdeshio_popular_docs]` — rank articles by views or publish date.
* All blocks now ship an editor interface, so their settings can be configured from the block sidebar.

**Content maintenance**

* Articles can record a review date; anything unreviewed for 90 days is flagged in the article list.
* Added one-click sample content that publishes a complete demo knowledge base and removes it just as cleanly.
* Articles now display their author and, when set, the date they were last reviewed.

**UI polish**

* The KB landing page no longer shows a duplicate search box — the top navigation search is now hidden on the landing page in favour of the hero search, and stays visible on article pages.
* Section cards without a custom icon now show a monogram of the section name instead of a generic "§" placeholder.
* Improved field spacing and button contrast on the Glossary admin screen.

**Under the hood**

* Added a WordPress integration test suite alongside the existing unit tests.
* Added a small extension architecture: the `itsdz_services` filter lets an add-on register its own backend services, `itsdz_kb_overview_extra` lets one add data to the Documentation screen's overview panel, and `itsdz_is_pro_active()` is a simple informational check for "is an add-on active" — none of it is used by anything in this free plugin itself.

= 0.1.0 =
* Initial public release.
* Knowledge base and article custom post types with section, tag, and version taxonomies.
* Setup Wizard (5 steps), Documentation Tree Builder, Settings screen, Import/Export UI.
* Gutenberg block editor admin interface (React + TypeScript).
* Instant full-text search with MySQL FULLTEXT index.
* Auto table of contents, breadcrumbs, Previous/Next navigation, related articles.
* "Was this helpful?" feedback system.
* Buffered view counter (5-minute WP-Cron flush, Action Scheduler compatible).
* Dark mode, print layout, mobile slide-out navigation.
* `itsdz/search` and `itsdz/docs-list` Gutenberg blocks.
* `[nirdeshio_search]` and `[nirdeshio_docs_list]` shortcodes.
* Canvas and theme layout modes.
* Clean, Modern, and Compact template styles.
* Translation-ready with RTL support.
* REST API (`itsdz/v1`) for admin and public endpoints.

== Upgrade Notice ==

= 1.2.0 =
Adds free WooCommerce product documentation tabs. Adds one small database table on upgrade.

= 1.1.1 =
Adds a skippable first-run setup wizard, the [nirdeshio_docs] shortcode, and an optional local-only setup analytics toggle. Existing documentation is not changed.

= 1.1.0 =
Shortcodes were renamed ([doczur_search] etc. → [nirdeshio_search] etc.) with no backward-compatible alias. If you used any Nirdeshio shortcode in a post or page, update it after upgrading.

= 0.1.0 =
First release — no upgrade steps required.
