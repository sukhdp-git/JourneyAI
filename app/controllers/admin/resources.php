<?php
/**
 * Control Panel resource definitions for the generic CRUD engine (ResourceController).
 * Each entry: table, labels, permission, list columns, search/filter/sort options and form fields.
 * Field types: text, email, link, textarea, richtext, slug, image, select, checkbox, number, date, datetime,
 *              color, icon, repeater, tags, password, permissions.
 */

use App\Core\Auth;
use App\Core\Database;
use App\Controllers\PageController;

$status = ['published' => 'Published', 'draft' => 'Draft'];
$seo = [
    ['name' => 'meta_title', 'label' => 'Meta title', 'type' => 'text', 'rules' => 'max:200', 'help' => 'Leave empty to use the title. Aim for 50–60 characters.', 'tab' => 'SEO', 'counter' => 60],
    ['name' => 'meta_description', 'label' => 'Meta description', 'type' => 'textarea', 'rules' => 'max:320', 'help' => 'Shown in search results. Aim for 140–160 characters.', 'tab' => 'SEO', 'counter' => 160, 'rows' => 3],
    ['name' => 'og_image', 'label' => 'Social share image (OG)', 'type' => 'image', 'help' => 'Recommended 1200 × 630 px. Falls back to the main image, then the global default.', 'tab' => 'SEO'],
];
$navTargets = ['_self' => 'Same tab', '_blank' => 'New tab'];
$bg = ['default' => 'Default', 'muted' => 'Muted', 'dark' => 'Dark', 'brand' => 'Brand gradient'];

return [
    'pages' => [
        'table' => 'pages', 'label' => 'Pages', 'singular' => 'Page', 'perm' => 'pages', 'icon' => 'file',
        'order' => 'sort_order ASC, id ASC', 'reorder' => true,
        'toggle' => ['field' => 'status', 'on' => 'published', 'off' => 'draft'],
        'search' => ['title', 'slug', 'content'],
        'filters' => ['status' => ['label' => 'Status', 'options' => $status], 'template' => ['label' => 'Template', 'options' => ['default' => 'Default', 'about' => 'About', 'legal' => 'Legal', 'landing' => 'Landing']]],
        'sortable' => ['title' => 'Title', 'updated_at' => 'Last updated', 'sort_order' => 'Manual order'],
        'columns' => [['title', 'Title', 'title'], ['slug', 'URL', 'path'], ['template', 'Template', 'tag'], ['status', 'Status', 'status'], ['updated_at', 'Updated', 'date']],
        'view' => fn ($r) => $r['status'] === 'published' ? '/' . $r['slug'] : null,
        'protect' => fn ($r) => (int) $r['is_system'] ? 'System pages (About, Privacy Policy, Terms) cannot be deleted. Unpublish them instead.' : null,
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:200', 'tab' => 'Content'],
            ['name' => 'slug', 'label' => 'URL slug', 'type' => 'slug', 'source' => 'title', 'prefix' => '/', 'rules' => 'required|slug|max:150', 'unique' => true, 'reserved' => PageController::RESERVED, 'tab' => 'Content', 'lock_if' => 'is_system'],
            ['name' => 'template', 'label' => 'Template', 'type' => 'select', 'options' => ['default' => 'Default page', 'about' => 'About (hero + blocks)', 'legal' => 'Legal document', 'landing' => 'Landing (blocks only)'], 'rules' => 'required', 'tab' => 'Content', 'width' => 'half'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $status, 'rules' => 'required', 'tab' => 'Content', 'width' => 'half'],
            ['name' => 'hero_eyebrow', 'label' => 'Hero eyebrow', 'type' => 'text', 'rules' => 'max:120', 'tab' => 'Content', 'width' => 'half'],
            ['name' => 'hero_title', 'label' => 'Hero heading', 'type' => 'text', 'rules' => 'max:255', 'help' => 'Defaults to the page title.', 'tab' => 'Content', 'width' => 'half'],
            ['name' => 'hero_subtitle', 'label' => 'Hero intro', 'type' => 'textarea', 'rules' => 'max:600', 'tab' => 'Content', 'rows' => 3],
            ['name' => 'featured_image', 'label' => 'Hero / featured image', 'type' => 'image', 'tab' => 'Content'],
            ['name' => 'content', 'label' => 'Page content', 'type' => 'richtext', 'tab' => 'Content'],
            ['name' => 'blocks', 'label' => 'Content blocks', 'type' => 'repeater', 'tab' => 'Blocks', 'add_label' => 'Add block',
                'help' => 'Blocks appear below the page content in this order. For cards and stats, enter one item per line as: Title | Text | icon',
                'subfields' => [
                    ['name' => 'type', 'label' => 'Block type', 'type' => 'select', 'options' => ['rich' => 'Text', 'split' => 'Text + image', 'cards' => 'Cards / values', 'stats' => 'Statistics', 'process' => 'Process steps', 'faq' => 'FAQs', 'services' => 'Services grid', 'testimonials' => 'Testimonials', 'cta' => 'Call to action'], 'width' => 'half'],
                    ['name' => 'background', 'label' => 'Background', 'type' => 'select', 'options' => $bg, 'width' => 'half'],
                    ['name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'width' => 'half'],
                    ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'width' => 'half'],
                    ['name' => 'body', 'label' => 'Text', 'type' => 'richtext'],
                    ['name' => 'image', 'label' => 'Image (Text + image blocks)', 'type' => 'image', 'width' => 'half'],
                    ['name' => 'image_side', 'label' => 'Image side', 'type' => 'select', 'options' => ['right' => 'Right', 'left' => 'Left'], 'width' => 'half'],
                    ['name' => 'items', 'label' => 'Items (one per line: Title | Text | icon)', 'type' => 'textarea'],
                    ['name' => 'cta_label', 'label' => 'Button label', 'type' => 'text', 'width' => 'half'],
                    ['name' => 'cta_url', 'label' => 'Button link', 'type' => 'link', 'width' => 'half'],
                    ['name' => 'hidden', 'label' => 'Hide this block', 'type' => 'checkbox'],
                ]],
            ['name' => 'in_sitemap', 'label' => 'Include in sitemap.xml', 'type' => 'checkbox', 'tab' => 'SEO', 'default' => 1],
            ['name' => 'noindex', 'label' => 'Hide from search engines (noindex)', 'type' => 'checkbox', 'tab' => 'SEO'],
            ['name' => 'canonical_url', 'label' => 'Canonical URL override', 'type' => 'link', 'rules' => 'max:500', 'help' => 'Only needed if this content is published elsewhere first.', 'tab' => 'SEO'],
            ...$seo,
        ],
    ],

    'services' => [
        'table' => 'services', 'label' => 'Services', 'singular' => 'Service', 'perm' => 'services', 'icon' => 'layers',
        'order' => 'sort_order ASC, id ASC', 'reorder' => true,
        'toggle' => ['field' => 'status', 'on' => 'published', 'off' => 'draft'],
        'search' => ['title', 'slug', 'short_description'],
        'filters' => ['status' => ['label' => 'Status', 'options' => $status]],
        'sortable' => ['title' => 'Title', 'updated_at' => 'Last updated', 'sort_order' => 'Manual order'],
        'columns' => [['title', 'Title', 'title'], ['slug', 'URL', 'path:/services/'], ['is_featured', 'Homepage', 'bool'], ['status', 'Status', 'status'], ['updated_at', 'Updated', 'date']],
        'view' => fn ($r) => $r['status'] === 'published' ? '/services/' . $r['slug'] : null,
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:200', 'tab' => 'Content'],
            ['name' => 'slug', 'label' => 'URL slug', 'type' => 'slug', 'source' => 'title', 'prefix' => '/services/', 'rules' => 'required|slug|max:150', 'unique' => true, 'tab' => 'Content'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $status, 'rules' => 'required', 'tab' => 'Content', 'width' => 'third'],
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'tab' => 'Content', 'width' => 'third'],
            ['name' => 'is_featured', 'label' => 'Show on homepage', 'type' => 'checkbox', 'tab' => 'Content', 'width' => 'third', 'default' => 1],
            ['name' => 'short_description', 'label' => 'Short description', 'type' => 'textarea', 'rules' => 'required|max:500', 'tab' => 'Content', 'rows' => 3, 'counter' => 200],
            ['name' => 'full_description', 'label' => 'Full description', 'type' => 'richtext', 'tab' => 'Content'],
            ['name' => 'thumbnail', 'label' => 'Thumbnail', 'type' => 'image', 'tab' => 'Content', 'width' => 'half'],
            ['name' => 'hero_image', 'label' => 'Hero image', 'type' => 'image', 'tab' => 'Content', 'width' => 'half'],
            ['name' => 'cta_label', 'label' => 'CTA text', 'type' => 'text', 'rules' => 'max:80', 'tab' => 'Content', 'width' => 'half'],
            ['name' => 'cta_url', 'label' => 'CTA link', 'type' => 'link', 'rules' => 'max:500', 'tab' => 'Content', 'width' => 'half'],
            ['name' => 'benefits', 'label' => 'Benefits', 'type' => 'repeater', 'tab' => 'Benefits & process', 'add_label' => 'Add benefit', 'subfields' => [['name' => 'title', 'label' => 'Benefit', 'type' => 'text'], ['name' => 'text', 'label' => 'Description', 'type' => 'textarea']]],
            ['name' => 'process', 'label' => 'Process steps', 'type' => 'repeater', 'tab' => 'Benefits & process', 'add_label' => 'Add step', 'subfields' => [['name' => 'title', 'label' => 'Step', 'type' => 'text'], ['name' => 'text', 'label' => 'Description', 'type' => 'textarea']]],
            ['name' => 'faqs', 'label' => 'FAQs', 'type' => 'repeater', 'tab' => 'FAQ', 'add_label' => 'Add question', 'subfields' => [['name' => 'question', 'label' => 'Question', 'type' => 'text'], ['name' => 'answer', 'label' => 'Answer', 'type' => 'textarea']]],
            ...$seo,
        ],
    ],

    'blog/categories' => [
        'table' => 'blog_categories', 'label' => 'Blog categories', 'singular' => 'Category', 'perm' => 'blog', 'icon' => 'list', 'parent' => ['Blog', 'blog'],
        'order' => 'sort_order ASC, name ASC', 'reorder' => true, 'search' => ['name', 'slug'],
        'columns' => [['name', 'Name', 'title'], ['slug', 'URL', 'path:/blog/category/'], ['posts', 'Posts', 'count']],
        'select_extra' => '(SELECT COUNT(*) FROM blog_posts p WHERE p.category_id = t.id) AS posts',
        'view' => fn ($r) => '/blog/category/' . $r['slug'],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => 'required|max:120'],
            ['name' => 'slug', 'label' => 'URL slug', 'type' => 'slug', 'source' => 'name', 'prefix' => '/blog/category/', 'rules' => 'required|slug|max:150', 'unique' => true],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => 'max:500', 'rows' => 3],
        ],
    ],

    'blog/tags' => [
        'table' => 'blog_tags', 'label' => 'Blog tags', 'singular' => 'Tag', 'perm' => 'blog', 'icon' => 'flag', 'parent' => ['Blog', 'blog'],
        'order' => 'name ASC', 'search' => ['name', 'slug'],
        'columns' => [['name', 'Name', 'title'], ['slug', 'URL', 'path:/blog/tag/'], ['posts', 'Posts', 'count']],
        'select_extra' => '(SELECT COUNT(*) FROM blog_post_tags pt WHERE pt.tag_id = t.id) AS posts',
        'view' => fn ($r) => '/blog/tag/' . $r['slug'],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => 'required|max:80'],
            ['name' => 'slug', 'label' => 'URL slug', 'type' => 'slug', 'source' => 'name', 'prefix' => '/blog/tag/', 'rules' => 'required|slug|max:100', 'unique' => true],
        ],
    ],

    'blog' => [
        'table' => 'blog_posts', 'label' => 'Blog posts', 'singular' => 'Post', 'perm' => 'blog', 'icon' => 'book',
        'order' => 'COALESCE(published_at, created_at) DESC, id DESC',
        'search' => ['title', 'slug', 'excerpt', 'content'],
        'filters' => [
            'status' => ['label' => 'Status', 'options' => ['published' => 'Published', 'scheduled' => 'Scheduled', 'draft' => 'Draft']],
            'category_id' => ['label' => 'Category', 'options' => fn () => array_column(Database::all('SELECT id, name FROM blog_categories ORDER BY name'), 'name', 'id')],
        ],
        'sortable' => ['title' => 'Title', 'published_at' => 'Publish date', 'views' => 'Views', 'updated_at' => 'Last updated'],
        'select_extra' => '(SELECT name FROM blog_categories c WHERE c.id = t.category_id) AS category_name',
        'columns' => [['title', 'Title', 'title'], ['category_name', 'Category', 'text'], ['status', 'Status', 'status'], ['published_at', 'Publish date', 'datetime'], ['views', 'Views', 'count']],
        'toggle' => ['field' => 'status', 'on' => 'published', 'off' => 'draft'],
        'view' => fn ($r) => in_array($r['status'], ['published', 'scheduled'], true) && $r['published_at'] && strtotime($r['published_at'] . ' UTC') <= time() ? '/blog/' . $r['slug'] : null,
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:255', 'tab' => 'Content'],
            ['name' => 'slug', 'label' => 'URL slug', 'type' => 'slug', 'source' => 'title', 'prefix' => '/blog/', 'rules' => 'required|slug|max:190', 'unique' => true, 'reserved' => ['page', 'category', 'tag'], 'tab' => 'Content'],
            ['name' => 'excerpt', 'label' => 'Excerpt', 'type' => 'textarea', 'rules' => 'max:600', 'rows' => 3, 'tab' => 'Content', 'counter' => 200],
            ['name' => 'content', 'label' => 'Content', 'type' => 'richtext', 'rules' => 'required', 'tab' => 'Content'],
            ['name' => 'featured_image', 'label' => 'Featured image', 'type' => 'image', 'tab' => 'Content'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled'], 'rules' => 'required', 'tab' => 'Publishing', 'width' => 'half', 'help' => 'Scheduled posts go live automatically at the publish date.'],
            ['name' => 'published_at', 'label' => 'Publish date & time', 'type' => 'datetime', 'tab' => 'Publishing', 'width' => 'half', 'help' => 'In the site timezone. Empty = now when published.'],
            ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => fn () => ['' => '— None —'] + array_column(Database::all('SELECT id, name FROM blog_categories ORDER BY name'), 'name', 'id'), 'tab' => 'Publishing', 'width' => 'half', 'nullable' => true],
            ['name' => 'tags', 'label' => 'Tags', 'type' => 'tags', 'tab' => 'Publishing', 'width' => 'half', 'help' => 'Comma-separated. New tags are created automatically.'],
            ['name' => 'author_name', 'label' => 'Author name', 'type' => 'text', 'rules' => 'max:120', 'tab' => 'Publishing', 'width' => 'half', 'help' => 'Defaults to the admin who created the post.'],
            ['name' => 'is_featured', 'label' => 'Featured post', 'type' => 'checkbox', 'tab' => 'Publishing', 'width' => 'half'],
            ...$seo,
        ],
        'before_save' => function (array $d, ?array $row): array {
            if ($d['status'] !== 'draft' && empty($d['published_at'])) {
                $d['published_at'] = gmdate('Y-m-d H:i:s');
            }
            if ($d['status'] === 'scheduled' && $d['published_at'] <= gmdate('Y-m-d H:i:s')) {
                $d['status'] = 'published';
            }
            if (!$row) {
                $d['author_id'] = Auth::id();
            }
            return $d;
        },
        'after_save' => function (int $id, array $input): void {
            $names = array_unique(array_filter(array_map(fn ($t) => mb_substr(trim($t), 0, 80), explode(',', (string) ($input['tags'] ?? '')))));
            Database::delete('blog_post_tags', 'post_id = :p', ['p' => $id]);
            foreach (array_slice($names, 0, 20) as $name) {
                $slug = slugify($name, 100);
                $tagId = (int) Database::value('SELECT id FROM blog_tags WHERE slug = :s', ['s' => $slug]);
                if (!$tagId) {
                    $tagId = Database::insert('blog_tags', ['name' => $name, 'slug' => $slug]);
                }
                Database::query('INSERT IGNORE INTO blog_post_tags (post_id, tag_id) VALUES (:p, :t)', ['p' => $id, 't' => $tagId]);
            }
        },
        'load' => function (array $row): array {
            $row['tags'] = implode(', ', array_column(Database::all('SELECT t.name FROM blog_tags t JOIN blog_post_tags pt ON pt.tag_id = t.id WHERE pt.post_id = :p ORDER BY t.name', ['p' => $row['id']]), 'name'));
            return $row;
        },
    ],

    'testimonials' => [
        'table' => 'testimonials', 'label' => 'Testimonials', 'singular' => 'Testimonial', 'perm' => 'testimonials', 'icon' => 'quote',
        'order' => 'sort_order ASC, id ASC', 'reorder' => true, 'toggle' => ['field' => 'status', 'on' => 'published', 'off' => 'draft'],
        'search' => ['name', 'company', 'message'], 'filters' => ['status' => ['label' => 'Status', 'options' => $status], 'is_demo' => ['label' => 'Type', 'options' => ['1' => 'Demo content', '0' => 'Genuine']]],
        'columns' => [['name', 'Name', 'title'], ['message', 'Message', 'excerpt'], ['is_demo', 'Demo', 'demo'], ['status', 'Status', 'status']],
        'notice' => 'Only publish genuine reviews from real customers, with their permission. Items marked “Demo content” show a visible Demo label on the website.',
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => 'required|max:120', 'width' => 'half'],
            ['name' => 'designation', 'label' => 'Designation', 'type' => 'text', 'rules' => 'max:120', 'width' => 'half'],
            ['name' => 'company', 'label' => 'Company / location', 'type' => 'text', 'rules' => 'max:120', 'width' => 'half'],
            ['name' => 'rating', 'label' => 'Rating', 'type' => 'select', 'options' => ['' => 'No rating', '5' => '5 stars', '4' => '4 stars', '3' => '3 stars', '2' => '2 stars', '1' => '1 star'], 'width' => 'half', 'nullable' => true],
            ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'rules' => 'required|max:2000', 'rows' => 5],
            ['name' => 'photo', 'label' => 'Photo', 'type' => 'image'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $status, 'rules' => 'required', 'width' => 'half'],
            ['name' => 'is_demo', 'label' => 'Demo content (shows a “Demo” label publicly)', 'type' => 'checkbox', 'width' => 'half'],
        ],
    ],

    'faqs' => [
        'table' => 'faqs', 'label' => 'FAQs', 'singular' => 'FAQ', 'perm' => 'faqs', 'icon' => 'help',
        'order' => 'category ASC, sort_order ASC, id ASC', 'reorder' => true, 'toggle' => ['field' => 'status', 'on' => 'published', 'off' => 'draft'],
        'search' => ['question', 'answer'], 'filters' => ['status' => ['label' => 'Status', 'options' => $status], 'category' => ['label' => 'Category', 'options' => fn () => array_column(Database::all('SELECT DISTINCT category FROM faqs ORDER BY category'), 'category', 'category')]],
        'columns' => [['question', 'Question', 'title'], ['category', 'Category', 'tag'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'question', 'label' => 'Question', 'type' => 'text', 'rules' => 'required|max:255'],
            ['name' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'rules' => 'required|max:4000', 'rows' => 6],
            ['name' => 'category', 'label' => 'Category', 'type' => 'text', 'rules' => 'required|max:60', 'default' => 'general', 'width' => 'half', 'help' => 'The homepage FAQ section shows the category set in Homepage → FAQ.'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $status, 'rules' => 'required', 'width' => 'half'],
        ],
    ],

    'process-steps' => [
        'table' => 'process_steps', 'label' => 'Process steps', 'singular' => 'Step', 'perm' => 'process', 'icon' => 'activity',
        'order' => 'sort_order ASC, id ASC', 'reorder' => true, 'toggle' => ['field' => 'status', 'on' => 'published', 'off' => 'draft'],
        'search' => ['title', 'description'],
        'columns' => [['step_number', 'No.', 'text'], ['title', 'Title', 'title'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'step_number', 'label' => 'Step number', 'type' => 'text', 'rules' => 'max:10', 'width' => 'third', 'help' => 'e.g. 01. Empty = automatic.'],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:150', 'width' => 'third'],
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'width' => 'third'],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => 'max:1000', 'rows' => 4],
            ['name' => 'image', 'label' => 'Image (replaces the icon)', 'type' => 'image'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $status, 'rules' => 'required', 'width' => 'half'],
        ],
    ],

    'sections' => [
        'table' => 'custom_sections', 'label' => 'Custom sections', 'singular' => 'Section', 'perm' => 'sections', 'icon' => 'section',
        'order' => 'placement ASC, sort_order ASC, id ASC', 'reorder' => true, 'toggle' => ['field' => 'status', 'on' => 'published', 'off' => 'draft'],
        'search' => ['name', 'heading', 'content'], 'filters' => ['placement' => ['label' => 'Placement', 'options' => ['home' => 'Homepage', 'about' => 'About page', 'services' => 'Services page', 'contact' => 'Contact page']]],
        'columns' => [['name', 'Name', 'title'], ['placement', 'Placement', 'tag'], ['layout', 'Layout', 'tag'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'name', 'label' => 'Internal name', 'type' => 'text', 'rules' => 'required|max:120', 'width' => 'half'],
            ['name' => 'placement', 'label' => 'Show on', 'type' => 'select', 'options' => ['home' => 'Homepage (before the call to action)', 'about' => 'About page (end)', 'services' => 'Services page (end)', 'contact' => 'Contact page (end)'], 'rules' => 'required', 'width' => 'half'],
            ['name' => 'layout', 'label' => 'Layout', 'type' => 'select', 'options' => ['split' => 'Text + image', 'text' => 'Centered text', 'banner' => 'Banner'], 'rules' => 'required', 'width' => 'half'],
            ['name' => 'background', 'label' => 'Background', 'type' => 'select', 'options' => $bg, 'rules' => 'required', 'width' => 'half'],
            ['name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'rules' => 'max:120', 'width' => 'half'],
            ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'rules' => 'max:255', 'width' => 'half'],
            ['name' => 'content', 'label' => 'Content', 'type' => 'richtext'],
            ['name' => 'image', 'label' => 'Image', 'type' => 'image'],
            ['name' => 'cta_label', 'label' => 'Button label', 'type' => 'text', 'rules' => 'max:80', 'width' => 'half'],
            ['name' => 'cta_url', 'label' => 'Button link', 'type' => 'link', 'rules' => 'max:500', 'width' => 'half'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $status, 'rules' => 'required', 'width' => 'half'],
        ],
    ],

    'navigation' => [
        'table' => 'navigation', 'label' => 'Navigation', 'singular' => 'Menu item', 'perm' => 'navigation', 'icon' => 'navigation',
        'order' => 'location ASC, COALESCE((SELECT p.sort_order FROM navigation p WHERE p.id = t.parent_id), sort_order) ASC, parent_id IS NOT NULL, sort_order ASC, id ASC',
        'reorder' => true, 'toggle' => ['field' => 'is_enabled', 'on' => 1, 'off' => 0], 'default_filter' => ['location' => 'header'],
        'search' => ['label', 'url'], 'filters' => ['location' => ['label' => 'Menu', 'options' => App\Models\Navigation::LOCATIONS, 'required' => true]],
        'columns' => [['label', 'Label', 'nav'], ['url', 'Link', 'mono'], ['target', 'Opens', 'tag'], ['is_enabled', 'Enabled', 'enabled']],
        'notice' => 'Footer column 1 is replaced by the services list while “Show services in footer” is on (Website → Footer). Drag rows to reorder; child items stay under their parent.',
        'fields' => [
            ['name' => 'label', 'label' => 'Label', 'type' => 'text', 'rules' => 'required|max:100', 'width' => 'half'],
            ['name' => 'url', 'label' => 'Link', 'type' => 'link', 'rules' => 'required|link|max:500', 'width' => 'half', 'help' => 'Internal: /about · External: https://…'],
            ['name' => 'location', 'label' => 'Menu', 'type' => 'select', 'options' => App\Models\Navigation::LOCATIONS, 'rules' => 'required', 'width' => 'half'],
            ['name' => 'parent_id', 'label' => 'Parent (dropdown)', 'type' => 'select', 'nullable' => true, 'width' => 'half', 'options' => fn () => ['' => '— Top level —'] + array_column(Database::all("SELECT id, CONCAT(label, ' (', location, ')') AS l FROM navigation WHERE parent_id IS NULL ORDER BY location, sort_order"), 'l', 'id'), 'help' => 'Only header menus show dropdowns.'],
            ['name' => 'target', 'label' => 'Open in', 'type' => 'select', 'options' => $navTargets, 'rules' => 'required', 'width' => 'half'],
            ['name' => 'is_enabled', 'label' => 'Enabled', 'type' => 'checkbox', 'width' => 'half', 'default' => 1],
        ],
        'validate' => function (array $d, ?array $row): array {
            $e = [];
            if (!empty($d['parent_id'])) {
                $p = Database::one('SELECT id, parent_id, location FROM navigation WHERE id = :id', ['id' => (int) $d['parent_id']]);
                if (!$p || $p['parent_id'] || ($row && (int) $p['id'] === (int) $row['id'])) {
                    $e['parent_id'] = 'Choose a top-level item as the parent.';
                } elseif ($p['location'] !== $d['location']) {
                    $e['parent_id'] = 'The parent must be in the same menu.';
                } elseif ($row && Database::value('SELECT COUNT(*) FROM navigation WHERE parent_id = :id', ['id' => $row['id']])) {
                    $e['parent_id'] = 'This item has its own sub-items, so it cannot become a child.';
                }
            }
            return $e;
        },
    ],

    'homepage' => [
        'table' => 'homepage_sections', 'label' => 'Homepage sections', 'singular' => 'Section', 'perm' => 'homepage', 'icon' => 'home',
        'order' => 'sort_order ASC, id ASC', 'reorder' => true, 'can_create' => false, 'can_delete' => false, 'per_page' => 100,
        'toggle' => ['field' => 'is_enabled', 'on' => 1, 'off' => 0],
        'columns' => [['label', 'Section', 'title'], ['heading', 'Heading', 'excerpt'], ['background', 'Background', 'tag'], ['is_enabled', 'Visible', 'enabled']],
        'notice' => 'Drag sections to change their order on the homepage. Disabled sections are hidden. Services, process steps, posts, testimonials and FAQs are managed in their own modules.',
        'view' => fn ($r) => '/',
        'fields' => function (?array $row) use ($bg) {
            $key = $row['section_key'] ?? '';
            $items = ['hero' => 'Highlight points', 'intro' => 'Pillars', 'benefits' => 'Benefit cards', 'stats' => 'Statistics (Title = the figure, Text = the label)'];
            $f = [
                ['name' => 'is_enabled', 'label' => 'Show this section', 'type' => 'checkbox', 'tab' => 'Content'],
                ['name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'rules' => 'max:120', 'tab' => 'Content'],
                ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'rules' => 'max:255', 'tab' => 'Content'],
                ['name' => 'subheading', 'label' => $key === 'hero' ? 'Subheading / description' : 'Subheading', 'type' => 'textarea', 'rules' => 'max:1000', 'rows' => 3, 'tab' => 'Content'],
            ];
            if (in_array($key, ['about', 'intro', 'benefits', 'generic'], true) || !isset($items[$key]) && !in_array($key, ['hero', 'services', 'process', 'featured', 'testimonials', 'faq', 'cta', 'contact', 'stats'], true)) {
                $f[] = ['name' => 'body', 'label' => 'Body text', 'type' => 'richtext', 'tab' => 'Content'];
            }
            if (in_array($key, ['hero', 'about'], true)) {
                $f[] = ['name' => 'image', 'label' => $key === 'hero' ? 'Background image (desktop) / video poster' : 'Image', 'type' => 'image', 'tab' => 'Media'];
            }
            if ($key === 'hero') {
                $f[] = ['name' => 'opt_media_type', 'label' => 'Hero style', 'type' => 'select', 'options' => ['visual' => 'Built-in product visual', 'image' => 'Background image', 'video' => 'Background video', 'none' => 'Text only'], 'tab' => 'Media', 'width' => 'half'];
                $f[] = ['name' => 'opt_alignment', 'label' => 'Text alignment', 'type' => 'select', 'options' => ['left' => 'Left', 'center' => 'Centered'], 'tab' => 'Media', 'width' => 'half'];
                $f[] = ['name' => 'opt_mobile_image', 'label' => 'Mobile background image', 'type' => 'image', 'tab' => 'Media'];
                $f[] = ['name' => 'opt_video_url', 'label' => 'Background video URL (.mp4 or .webm)', 'type' => 'text', 'rules' => 'max:500', 'tab' => 'Media', 'help' => 'An https:// link to an MP4/WebM file. Keep it short and small (under ~5 MB).'];
                $f[] = ['name' => 'opt_overlay_color', 'label' => 'Overlay colour', 'type' => 'color', 'tab' => 'Media', 'width' => 'half'];
                $f[] = ['name' => 'opt_overlay_opacity', 'label' => 'Overlay opacity (0–95 %)', 'type' => 'number', 'rules' => 'between:0,95', 'tab' => 'Media', 'width' => 'half'];
                $f[] = ['name' => 'opt_animate', 'label' => 'Entrance animation', 'type' => 'checkbox', 'tab' => 'Media'];
            }
            if (isset($items[$key])) {
                $f[] = ['name' => 'items', 'label' => $items[$key], 'type' => 'repeater', 'tab' => 'Items', 'add_label' => 'Add item', 'subfields' => array_values(array_filter([
                    ['name' => 'title', 'label' => $key === 'stats' ? 'Figure' : 'Title', 'type' => 'text', 'width' => 'half'],
                    $key === 'hero' ? null : ['name' => 'text', 'label' => $key === 'stats' ? 'Label' : 'Text', 'type' => 'textarea'],
                    $key === 'stats' ? null : ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'width' => 'half'],
                ]))];
            }
            if (in_array($key, ['services', 'featured', 'testimonials', 'faq'], true)) {
                $f[] = ['name' => 'opt_limit', 'label' => 'Number of items to show', 'type' => 'number', 'rules' => 'between:1,24', 'tab' => 'Content', 'width' => 'half'];
            }
            if ($key === 'faq') {
                $f[] = ['name' => 'opt_category', 'label' => 'FAQ category to show', 'type' => 'text', 'rules' => 'max:60', 'tab' => 'Content', 'width' => 'half', 'help' => 'Empty = all categories.'];
            }
            if ($key !== 'hero') {
                $f[] = ['name' => 'background', 'label' => 'Background', 'type' => 'select', 'options' => $bg + ['image' => 'Image'], 'rules' => 'required', 'tab' => 'Style', 'width' => 'half'];
                $f[] = ['name' => 'background_image', 'label' => 'Background image (when Background = Image)', 'type' => 'image', 'tab' => 'Style'];
            }
            $f[] = ['name' => 'cta_label', 'label' => 'Primary button text', 'type' => 'text', 'rules' => 'max:80', 'tab' => 'Buttons', 'width' => 'half'];
            $f[] = ['name' => 'cta_url', 'label' => 'Primary button link', 'type' => 'link', 'rules' => 'max:500', 'tab' => 'Buttons', 'width' => 'half'];
            if (in_array($key, ['hero', 'cta'], true)) {
                $f[] = ['name' => 'cta2_label', 'label' => 'Secondary button text', 'type' => 'text', 'rules' => 'max:80', 'tab' => 'Buttons', 'width' => 'half'];
                $f[] = ['name' => 'cta2_url', 'label' => 'Secondary button link', 'type' => 'link', 'rules' => 'max:500', 'tab' => 'Buttons', 'width' => 'half'];
            }
            return $f;
        },
    ],

    'email/templates' => [
        'table' => 'email_templates', 'label' => 'Email templates', 'singular' => 'Template', 'perm' => 'email.templates', 'icon' => 'mail', 'parent' => ['Email', null],
        'order' => 'id ASC', 'can_create' => false, 'can_delete' => false, 'toggle' => ['field' => 'is_enabled', 'on' => 1, 'off' => 0],
        'columns' => [['name', 'Template', 'title'], ['subject', 'Subject', 'excerpt'], ['is_enabled', 'Enabled', 'enabled'], ['updated_at', 'Updated', 'date']],
        'notice' => 'Variables: {name} {email} {phone} {service} {message} {date} {subject} {preferred_date} {preferred_time} {site_name} {site_url}. Values are inserted safely (HTML-escaped).',
        'fields' => [
            ['name' => 'is_enabled', 'label' => 'Enabled', 'type' => 'checkbox'],
            ['name' => 'subject', 'label' => 'Subject', 'type' => 'text', 'rules' => 'required|max:255'],
            ['name' => 'body', 'label' => 'Body', 'type' => 'richtext', 'rules' => 'required'],
        ],
    ],

    'admin-users' => [
        'table' => 'admins', 'label' => 'Admin users', 'singular' => 'Admin user', 'perm' => 'users', 'icon' => 'users',
        'order' => 'name ASC', 'search' => ['name', 'email'],
        'filters' => ['role_id' => ['label' => 'Role', 'options' => fn () => array_column(Database::all('SELECT id, name FROM roles ORDER BY id'), 'name', 'id')], 'status' => ['label' => 'Status', 'options' => ['active' => 'Active', 'disabled' => 'Disabled']]],
        'select_extra' => '(SELECT name FROM roles r WHERE r.id = t.role_id) AS role_name',
        'columns' => [['name', 'Name', 'title'], ['email', 'Email', 'text'], ['role_name', 'Role', 'tag'], ['status', 'Status', 'status'], ['last_login_at', 'Last sign-in', 'datetime']],
        'hidden_columns' => ['password_hash'],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => 'required|max:120', 'width' => 'half'],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'rules' => 'required|email|max:190', 'unique' => true, 'width' => 'half'],
            ['name' => 'role_id', 'label' => 'Role', 'type' => 'select', 'options' => fn () => array_column(Database::all('SELECT id, name FROM roles ORDER BY id'), 'name', 'id'), 'rules' => 'required', 'width' => 'half'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'disabled' => 'Disabled'], 'rules' => 'required', 'width' => 'half'],
            ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'width' => 'half', 'help' => 'At least 10 characters with letters and numbers. Leave empty to keep the current password.'],
            ['name' => 'password_confirmation', 'label' => 'Confirm password', 'type' => 'password', 'width' => 'half', 'virtual' => true],
        ],
        'validate' => function (array $d, ?array $row): array {
            $e = [];
            $pw = (string) ($_POST['password'] ?? '');
            if (!$row && $pw === '') {
                $e['password'] = 'Set a password for the new user.';
            } elseif ($pw !== '' && (strlen($pw) < 10 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw))) {
                $e['password'] = 'Use at least 10 characters including letters and numbers.';
            } elseif ($pw !== '' && $pw !== (string) ($_POST['password_confirmation'] ?? '')) {
                $e['password_confirmation'] = 'Passwords do not match.';
            }
            if ($row && (int) $row['id'] === Auth::id() && ($d['status'] !== 'active' || (int) $d['role_id'] !== (int) $row['role_id'])) {
                $e['status'] = 'You cannot disable yourself or change your own role.';
            }
            if ($row && admin_is_last_super((int) $row['id']) && ($d['status'] !== 'active' || !role_is_super((int) $d['role_id']))) {
                $e['role_id'] = 'At least one active Super Admin is required.';
            }
            if (!$row || (int) $d['role_id'] !== (int) ($row['role_id'] ?? 0)) {
                if (role_is_super((int) $d['role_id']) && !Auth::isSuper()) {
                    $e['role_id'] = 'Only a Super Admin can grant the Super Admin role.';
                }
            }
            return $e;
        },
        'before_save' => function (array $d, ?array $row): array {
            $pw = (string) ($_POST['password'] ?? '');
            unset($d['password'], $d['password_confirmation']);
            if ($pw !== '') {
                $d['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
            }
            $d['email'] = strtolower($d['email']);
            return $d;
        },
        'protect' => fn ($r) => (int) $r['id'] === Auth::id() ? 'You cannot delete your own account.' : (admin_is_last_super((int) $r['id']) ? 'At least one active Super Admin is required.' : null),
        'protect_toggle' => true,
    ],

    'roles' => [
        'table' => 'roles', 'label' => 'Roles', 'singular' => 'Role', 'perm' => 'roles', 'icon' => 'key',
        'order' => 'id ASC', 'search' => ['name', 'description'],
        'select_extra' => '(SELECT COUNT(*) FROM admins a WHERE a.role_id = t.id) AS users',
        'columns' => [['name', 'Role', 'title'], ['description', 'Description', 'excerpt'], ['users', 'Users', 'count']],
        'fields' => [
            ['name' => 'name', 'label' => 'Role name', 'type' => 'text', 'rules' => 'required|max:80', 'width' => 'half'],
            ['name' => 'slug', 'label' => 'Key', 'type' => 'slug', 'source' => 'name', 'rules' => 'required|slug|max:80', 'unique' => true, 'width' => 'half', 'lock_if' => 'is_system'],
            ['name' => 'description', 'label' => 'Description', 'type' => 'text', 'rules' => 'max:255'],
            ['name' => 'permissions', 'label' => 'Permissions', 'type' => 'permissions'],
        ],
        'validate' => function (array $d, ?array $row): array {
            if ($row && $row['slug'] === 'super-admin') {
                return ['permissions' => 'The Super Admin role always has full access and cannot be changed.'];
            }
            return [];
        },
        'protect' => fn ($r) => (int) $r['is_system'] ? 'Built-in roles cannot be deleted.' : ((int) Database::value('SELECT COUNT(*) FROM admins WHERE role_id = :id', ['id' => $r['id']]) ? 'Move the users in this role to another role first.' : null),
    ],
];
