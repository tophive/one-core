<?php
$root = dirname(__DIR__);
$php = file_get_contents($root . '/inc/admin/demo-import.php');
$ui = file_get_contents($root . '/inc/admin/demo-import-ui.js');
$css = file_get_contents($root . '/inc/admin/demo-import.css');
$checks = [
    [strpos($php, "'pages' => one_demo_page_library()") !== false, 'Server page library is not localized.'],
    [strpos($php, 'function one_demo_page_library()') !== false, 'Server page inventory is missing.'],
    [strpos($php, 'function bp_demo_import_pages(array $page_keys = [], bool $reimport = false)') !== false, 'Selective page importer is missing.'],
    [strpos($php, "get_post_meta(\$existing->ID, '_one_demo_page', true)") !== false, 'Demo ownership marker check is missing.'],
    [strpos($php, 'if (!$reimport)') !== false, 'Imported pages are not protected from implicit updates.'],
    [strpos($ui, "setTab('pages')") !== false, 'Pages tab is missing.'],
    [strpos($ui, "'Starter Pages'") === false, 'Starter Pages still appears in Setup options.'],
    [strpos($ui, "p.status==='conflict'") !== false, 'Existing customer-page conflict state is missing.'],
    [strpos($ui, "imported?'Re-import':'Import'") !== false, 'Explicit Re-import action is missing.'],
    [strpos($ui, 'if(!BPDemoSteps.setup_imported)') !== false, 'Repeated setup can still append widgets/homepage work.'],
    [strpos($css, '.one-demo-tabs') !== false && strpos($css, '.one-demo-page-list') !== false, 'Pages tab UI styles are missing.'],
];
foreach ($checks as [$ok, $message]) { if (!$ok) { fwrite(STDERR, $message . "\n"); exit(1); } }
echo "Demo page library contract PASS\n";
