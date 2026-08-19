<?php
$root = dirname(__DIR__);
$pages = $root . '/inc/admin/demo-data/pages.json';
$bootstrap = $root . '/one-core.php';
$importer = $root . '/inc/admin/demo-import.php';
$ui = $root . '/inc/admin/demo-import-ui.js';
$worker = $root . '/inc/admin/demo-import.js';
foreach ([$pages, $bootstrap, $importer, $ui, $worker] as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "Missing required About demo file: {$file}\n");
        exit(1);
    }
}
if (is_file($root . '/assets/css/demo-about.css')) {
    fwrite(STDERR, "About page must not depend on demo-about.css.\n");
    exit(1);
}
$data = json_decode(file_get_contents($pages), true);
if (!is_array($data) || count($data) < 1) {
    fwrite(STDERR, "pages.json is invalid or empty.\n");
    exit(1);
}
$about = null;
foreach ($data as $page) {
    if (($page['post_name'] ?? '') === 'about') {
        $about = $page;
        break;
    }
}
if (!$about) {
    fwrite(STDERR, "About page missing from pages.json.\n");
    exit(1);
}
if (($about['meta']['_one_demo_page'] ?? '') !== 'about') {
    fwrite(STDERR, "About page importer marker missing.\n");
    exit(1);
}
if (!empty($about['content'])) {
    fwrite(STDERR, "Elementor About page must not ship a parallel HTML fallback body.\n");
    exit(1);
}
$elementor = $about['meta']['_elementor_data'] ?? null;
if (!is_array($elementor) || count($elementor) !== 6) {
    fwrite(STDERR, "About page must contain six Elementor sections.\n");
    exit(1);
}
$widgetTypes = [];
$settingsFound = [];
$hasPortableImage = false;
$stack = $elementor;
while ($stack) {
    $node = array_pop($stack);
    if (($node['widgetType'] ?? '') === 'html') {
        fwrite(STDERR, "About page must not use Elementor HTML widgets.\n");
        exit(1);
    }
    if (!empty($node['widgetType'])) {
        $widgetTypes[$node['widgetType']] = true;
    }
    foreach (($node['settings'] ?? []) as $key => $value) {
        $settingsFound[$key] = true;
        if ($key === 'image' && is_array($value) && strpos((string)($value['url'] ?? ''), '{{ONE_CORE_URL}}assets/images/demo/about/') === 0) {
            $hasPortableImage = true;
        }
    }
    foreach (($node['elements'] ?? []) as $child) {
        $stack[] = $child;
    }
}
foreach (['heading','text-editor','button','image'] as $type) {
    if (empty($widgetTypes[$type])) {
        fwrite(STDERR, "Missing native Elementor widget type: {$type}\n");
        exit(1);
    }
}
if (!$hasPortableImage) {
    fwrite(STDERR, "Portable One Core image token missing.\n");
    exit(1);
}
foreach (['flex_direction','padding','background_color','typography_font_size','width','height','object-fit','border_radius'] as $setting) {
    if (empty($settingsFound[$setting])) {
        fwrite(STDERR, "Missing self-contained Elementor style setting: {$setting}\n");
        exit(1);
    }
}
foreach (['about-hero.jpg','about-story.jpg','team-rasel.jpg','team-mariam.jpg','team-nahid.jpg','team-sadia.jpg'] as $image) {
    if (!is_file($root . '/assets/images/demo/about/' . $image)) {
        fwrite(STDERR, "Missing About image: {$image}\n");
        exit(1);
    }
}
$bootstrapSource = file_get_contents($bootstrap);
foreach (['demo-about.css','enqueue_about_demo_style','demo_page_body_class'] as $legacy) {
    if (strpos($bootstrapSource, $legacy) !== false) {
        fwrite(STDERR, "Legacy About CSS dependency remains: {$legacy}\n");
        exit(1);
    }
}
$importerSource = file_get_contents($importer);
if (strpos($importerSource, 'import_post($page, \'page\', (int) $post_id)') === false) {
    fwrite(STDERR, "Page importer is not passing the inserted post ID.\n");
    exit(1);
}
if (strpos(file_get_contents($ui), "step:'import_pages'") === false || strpos($importerSource, 'one_demo_page_library()') === false) {
    fwrite(STDERR, "Pages tab isolated import step missing.\n");
    exit(1);
}
echo "About demo page contract PASS\n";
