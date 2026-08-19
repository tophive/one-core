<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

foreach ([
    'inc/licensing',
    'sdk',
    't/inc/updater',
] as $relative) {
    if (file_exists($root . '/' . $relative)) {
        $failures[] = "One Core must not own shared licensing runtime: {$relative}";
    }
}

$bridge = file_get_contents($root . '/inc/class-entitlement-bridge.php') ?: '';
foreach (['tophive_one_license_status', 'tophive_one_has_entitlement', 'tophive_one_license_features'] as $needle) {
    if (strpos($bridge, $needle) === false) {
        $failures[] = "One Core entitlement bridge is missing: {$needle}";
    }
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $relative = ltrim(str_replace($root, '', $file->getPathname()), '/');
    foreach (['tests/', 'release/', 'temp/', 'node_modules/', '.git/'] as $excluded) {
        if (str_starts_with($relative, $excluded)) {
            continue 2;
        }
    }
    $source = file_get_contents($file->getPathname()) ?: '';
    foreach (['Tophive\\LicensingClient', 'LicenseApiClient', 'EntitlementVerifier', 'wp_remote_post(', 'pre_set_site_transient_update_plugins'] as $needle) {
        if (strpos($source, $needle) !== false) {
            $failures[] = "Theme-owned licensing runtime leaked into One Core: {$needle} in {$relative}";
        }
    }
}

if ($failures !== []) {
    fwrite(STDERR, "One Core shared SDK boundary contract failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "One Core shared SDK boundary contract: PASS\n";
