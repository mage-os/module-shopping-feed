<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$failures = [];

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$composer = json_decode((string) file_get_contents($root . '/composer.json'), true);
$assert(is_array($composer), 'composer.json must contain valid JSON');
$assert(($composer['name'] ?? null) === 'mage-os/module-shopping-feed', 'Unexpected Composer package name');
$assert(($composer['type'] ?? null) === 'magento2-module', 'Unexpected Composer package type');
$assert(($composer['autoload']['psr-4']['MageOS\\ShoppingFeed\\'] ?? null) === '', 'Unexpected PSR-4 mapping');
$assert(($composer['license'] ?? null) === 'OSL-3.0', 'Composer license must use the SPDX OSL-3.0 identifier');
$migrationPackage = 'rocketweb/module-shopping-feed-migration-rocketweb';
foreach (['require', 'require-dev', 'replace', 'provide', 'conflict'] as $dependencySection) {
    $assert(
        !isset($composer[$dependencySection][$migrationPackage]),
        'The Rocket Web migration companion must remain an optional Composer suggestion'
    );
}

$ciWorkflow = (string) file_get_contents($root . '/.github/workflows/check-extension.yaml');
$assert(!is_file($root . '/.github/workflows/ci.yml'), 'The CI workflow must use the standard check-extension.yaml name');
$assert(
    preg_match('/^  pull_request:\s*$/m', $ciWorkflow) === 1,
    'Pull request CI must run for every base branch'
);
$checkExtensionConfig = json_decode((string) @file_get_contents($root . '/.github/check-extension.json'), true);
$assert(
    is_array($checkExtensionConfig) && ($checkExtensionConfig['jobs']['phpstan'] ?? false) === true,
    'The check-extension PHPStan job must be enabled'
);
$assert(
    preg_match_all('/check-extension\.yaml@[0-9a-f]{40} # v\d+\.\d+\.\d+/', $ciWorkflow) === 2,
    'Reusable check-extension workflows must be pinned to a commit with a version comment'
);
$assert(
    preg_match('/^\s*-?\s*uses: [^@\s]+@(?![0-9a-f]{40}\b)/m', $ciWorkflow) !== 1,
    'Every action must be pinned to a full commit SHA'
);
$assert(str_contains($ciWorkflow, 'dependency-audit:'), 'Locked platform dependency audit job is missing');
$assert(str_contains($ciWorkflow, 'development-tool-audit:'), 'Locked extension and tool audit job is missing');
$assert(is_file($root . '/.github/dependabot.yml'), 'Dependabot configuration is missing');
$assert(str_contains($ciWorkflow, 'check-magento:'), 'Magento Open Source CI job is missing');
$assert(str_contains($ciWorkflow, 'check-mageos:'), 'Mage-OS CI job is missing');
$assert(
    str_contains($ciWorkflow, 'magento_repository: https://mirror.mage-os.org/'),
    'Magento Open Source CI must install from the public Magento mirror'
);
$assert(
    str_contains($ciWorkflow, 'magento_repository: https://repo.mage-os.org/'),
    'Mage-OS CI must install from the Mage-OS distribution repository'
);

$moduleXml = (string) file_get_contents($root . '/etc/module.xml');
$registration = (string) file_get_contents($root . '/registration.php');
$assert(str_contains($moduleXml, 'name="MageOS_ShoppingFeed"'), 'Magento module name is missing');
$assert(str_contains($registration, "'MageOS_ShoppingFeed'"), 'Registration module name is missing');

$xmlFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    // The local dev install (vendor/) is not module source.
    if (str_contains($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)
        || str_starts_with($path, $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
    ) {
        continue;
    }

    if ($file->getExtension() === 'xml') {
        $xmlFiles[] = $path;
        $document = new DOMDocument();
        $assert($document->load($path, LIBXML_NONET), sprintf('%s is not well-formed XML', substr($path, strlen($root) + 1)));
        $schema = $document->documentElement?->getAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'noNamespaceSchemaLocation'
        );
        $assert($schema !== '', sprintf('%s does not declare a Magento schema', substr($path, strlen($root) + 1)));
    }
}

$feedConfigPath = $root . '/etc/mageos_shopping_feed.xml';
$feedSchemaPath = $root . '/etc/mageos_shopping_feed.xsd';
$feedConfig = new DOMDocument();
$feedConfig->load($feedConfigPath, LIBXML_NONET);
libxml_use_internal_errors(true);
$feedSchemaValid = $feedConfig->schemaValidate($feedSchemaPath);
foreach (libxml_get_errors() as $error) {
    $failures[] = sprintf('Feed configuration schema error on line %d: %s', $error->line, trim($error->message));
}
libxml_clear_errors();
libxml_use_internal_errors(false);
$assert($feedSchemaValid, 'The consolidated feed configuration does not match its XSD');

$feedXpath = new DOMXPath($feedConfig);
$expectedFeeds = [
    'generic' => ['directives' => 25, 'columns' => 17],
    'google_shopping' => ['directives' => 33, 'columns' => 31],
    'google_local_inventory' => ['directives' => 12, 'columns' => 7],
    'meta_catalog' => ['directives' => 26, 'columns' => 23],
    'openai_google_compatible' => ['directives' => 26, 'columns' => 29],
    'pinterest_catalog' => ['directives' => 26, 'columns' => 23],
    'tiktok_catalog' => ['directives' => 26, 'columns' => 24],
    'microsoft_merchant_center' => ['directives' => 27, 'columns' => 24],
];
foreach ($expectedFeeds as $feedName => $expected) {
    $feed = $feedXpath->query(sprintf('/config/feed[@name="%s"]', $feedName))->item(0);
    $assert($feed instanceof DOMElement, sprintf('Missing %s feed configuration', $feedName));
    if (!$feed instanceof DOMElement) {
        continue;
    }

    $directives = $feedXpath->query('directives/directive', $feed);
    $columns = $feedXpath->query('default_product_columns/column', $feed);
    $assert($directives->length === $expected['directives'], sprintf('%s directive count changed', $feedName));
    $assert($columns->length === $expected['columns'], sprintf('%s default-column count changed', $feedName));

    $directiveNames = [];
    foreach ($directives as $directive) {
        $directiveNames[] = $directive->attributes->getNamedItem('name')?->nodeValue;
    }
    $assert(count($directiveNames) === count(array_unique($directiveNames)), sprintf('%s has duplicate directives', $feedName));

    $columnNames = [];
    foreach ($columns as $column) {
        $columnNames[] = $feedXpath->query('column', $column)->item(0)?->nodeValue;
    }
    $assert(count($columnNames) === count(array_unique($columnNames)), sprintf('%s has duplicate default columns', $feedName));
}

$assert(
    $feedXpath->query('/config/feed[@name="google_shopping"]/directives/directive[@name="directive_promotions_id"]')->length === 1,
    'Google Promotions was not merged into Google Shopping'
);
$assert(
    $feedXpath->query('/config/feed[@name="google_shopping"]/default_product_columns/column[@attribute="directive_identifier_attribute"]/column[text()="mpn" or text()="gtin"]')->length === 2,
    'Google identifiers must require a real attribute mapping instead of defaulting to SKU'
);
$assert(
    $feedXpath->query('/config/feed[@name="google_shopping"]/default_product_columns/column/column[text()="availability_date"]')->length === 1,
    'Google Shopping needs an availability_date mapping for backorders and preorders'
);
$assert(
    $feedXpath->query('/config/feed[@name="google_shopping"]/default_product_columns/column/column[text()="promotion_id"]')->length === 1,
    'Google Shopping is missing the promotion_id column'
);
$assert(
    $feedXpath->query('/config/feed[@name="google_shopping"]/default_product_columns/column/column[text()="promotions_id"]')->length === 0,
    'Google Shopping still uses the invalid promotions_id column'
);
foreach (['item_group_title', 'variant_option', 'color', 'size', 'material', 'pattern', 'gender', 'age_group'] as $column) {
    $assert(
        $feedXpath->query(sprintf('/config/feed[@name="google_shopping"]/default_product_columns/column/column[text()="%s"]', $column))->length === 1,
        sprintf('Google Shopping is missing the %s variant column', $column)
    );
}
$assert(
    $feedXpath->query('/config/feed[@name="google_local_inventory"]//directive[@name="directive_inventory_source"]')->length === 1,
    'Google Local Inventory is missing its inventory-source directive'
);
$assert(
    $feedXpath->query('/config/feed[@name="google_local_inventory"]/default_feed_config/categories/inventory_source_map')->length === 1,
    'Google Local Inventory is missing its inventory-source mapping config'
);
$assert(
    $feedXpath->query('/config/feed/default_feed_config/general/feed_dir[text()="pub/media/mageos-shopping-feed"]')->length === count($expectedFeeds),
    'One or more feed output directories are not isolated'
);
$allowedPatches = [$root . '/Setup/Patch/Data/AddSkipSubmitAttribute.php'];
$patches = glob($root . '/Setup/Patch/*/*.php');
$assert(is_array($patches), 'Setup patch discovery failed, so the patch allowlist could not be checked');
$assert(
    is_array($patches) && array_diff($patches, $allowedPatches) === [],
    'Historical paid-package data patches must not run under the new module identity'
);

$diConfig = (string) file_get_contents($root . '/etc/di.xml');
$assert(str_contains($diConfig, 'name="mageos_shopping_feed_generate"'), 'The generate command DI key is not isolated');
$assert(str_contains($diConfig, 'name="mageos_shopping_feed_generate_schedule"'), 'The schedule command DI key is not isolated');
$assert(str_contains($diConfig, '>mageos_shopping_feed_types_config<'), 'The feed configuration cache ID is not isolated');
$assert(!str_contains($diConfig, 'name="generate"'), 'The shared legacy generate command DI key remains');

$taxonomyProvider = (string) file_get_contents($root . '/Model/Taxonomy/ProviderAbstract.php');
$assert(
    str_contains($taxonomyProvider, "const CACHE_KEY = 'MAGEOS_SHOPPING_FEED_TAXONOMY'"),
    'The taxonomy cache key is not isolated'
);
$assert(
    str_contains($taxonomyProvider, "const CACHE_TAG = 'MAGEOS_SHOPPING_FEED'"),
    'The taxonomy cache tag is not isolated'
);

$adminRequireJs = (string) file_get_contents($root . '/view/adminhtml/requirejs-config.js');
$assert(str_contains($adminRequireJs, 'mageosShoppingFeedCategoryTaxonomy'), 'The taxonomy RequireJS alias is not isolated');
$assert(str_contains($adminRequireJs, 'mageosShoppingFeedForm'), 'The feed form RequireJS alias is not isolated');
$assert(!str_contains($adminRequireJs, '"categoryTaxonomy"'), 'The shared taxonomy RequireJS alias remains');
$assert(!str_contains($adminRequireJs, '"feedForm"'), 'The shared feed-form RequireJS alias remains');

$saveController = (string) file_get_contents($root . '/Controller/Adminhtml/Feed/Save.php');
$builderController = (string) file_get_contents($root . '/Controller/Adminhtml/Feed/Builder.php');
$assert(str_contains($saveController, 'setMageosShoppingFeedData'), 'The Admin session write key is not isolated');
$assert(str_contains($builderController, 'getMageosShoppingFeedData'), 'The Admin session read key is not isolated');

$eventsConfig = new DOMDocument();
$eventsConfig->load($root . '/etc/events.xml', LIBXML_NONET);
$eventsXpath = new DOMXPath($eventsConfig);
foreach ($eventsXpath->query('/config/event') as $event) {
    if (!$event instanceof DOMElement) {
        continue;
    }
    $name = $event->getAttribute('name');
    if (str_starts_with($name, 'adminhtml_feed_edit_tab_')) {
        $failures[] = sprintf('Custom Admin event %s is not isolated', $name);
    }
}
foreach ($eventsXpath->query('/config/event/observer') as $observer) {
    if (!$observer instanceof DOMElement) {
        continue;
    }
    $assert(
        str_starts_with($observer->getAttribute('name'), 'mageos_shopping_feed_'),
        sprintf('Observer %s is not isolated', $observer->getAttribute('name'))
    );
}
$assert(
    $eventsXpath->query('/config/event[@name="mageos_shopping_feed_upload_after"]')->length === 1,
    'The generic post-upload event is missing'
);
$assert(
    $eventsXpath->query('/config/event[@name="mageos_shopping_feed_ftp_upload_after"]')->length === 0,
    'The FTP-specific post-upload event name remains'
);

$configurableSelection = (string) file_get_contents(
    $root . '/view/frontend/web/js/configurable/selection.js'
);
$assert(str_contains($configurableSelection, 'window.location.hash'), 'Configurable deep links do not read URL fragments');
$assert(str_contains($configurableSelection, "window.gtag('event', 'view_item'"), 'Current Google tag view_item event is missing');
$assert(!str_contains($configurableSelection, 'google_tag_params'), 'Legacy Google remarketing globals remain');
$assert(!str_contains($configurableSelection, 'doubleclick.net'), 'Legacy DoubleClick tracking endpoint remains');

$promotionGenerator = (string) file_get_contents($root . '/Observer/Promotions/Generator.php');
$promotionMap = (string) file_get_contents($root . '/Model/Promotions/Provider/Map.php');
$assert(substr_count($promotionGenerator, "'promotion_destination'") === 2, 'Promotions need two destination columns');
$assert(str_contains($promotionGenerator, "'shopping_ads'"), 'Shopping ads promotion destination is missing');
$assert(str_contains($promotionGenerator, "'free_listings'"), 'Free listings promotion destination is missing');
$assert(str_contains($promotionMap, "'all_products'"), 'Current Promotions applicability value is missing');
$assert(str_contains($promotionMap, "'generic_code'"), 'Current Promotions offer type is missing');

$generator = (string) file_get_contents($root . '/Model/Generator.php');
$assert(str_contains($generator, '->getGzip()'), 'Upload gzip setting is not applied');
$assert(str_contains($generator, '$this->gzipCompressor->compress($file)'), 'Feed gzip compression is not connected');

$schema = new DOMDocument();
$schema->load($root . '/etc/db_schema.xml', LIBXML_NONET);
$expectedWhitelist = [];
foreach ($schema->getElementsByTagName('table') as $table) {
    $tableData = ['column' => [], 'index' => [], 'constraint' => []];
    foreach ($table->childNodes as $child) {
        if (!$child instanceof DOMElement) {
            continue;
        }
        if ($child->tagName === 'column') {
            $tableData['column'][$child->getAttribute('name')] = true;
        } elseif ($child->tagName === 'index') {
            $tableData['index'][$child->getAttribute('referenceId')] = true;
        } elseif ($child->tagName === 'constraint') {
            $tableData['constraint'][$child->getAttribute('referenceId')] = true;
        }
    }
    foreach ($tableData as $key => $values) {
        if ($values === []) {
            unset($tableData[$key]);
        }
    }
    $expectedWhitelist[$table->getAttribute('name')] = $tableData;
}
$actualWhitelist = json_decode((string) file_get_contents($root . '/etc/db_schema_whitelist.json'), true);
$assert($actualWhitelist === $expectedWhitelist, 'db_schema_whitelist.json is out of sync with db_schema.xml');

$codeDirectories = ['Block', 'Console', 'Controller', 'Cron', 'Model', 'Observer', 'Plugin', 'Setup', 'Ui'];
foreach ($codeDirectories as $directory) {
    $directoryPath = $root . '/' . $directory;
    if (!is_dir($directoryPath)) {
        continue;
    }
    $codeIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directoryPath, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($codeIterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $contents = (string) file_get_contents($file->getPathname());
        preg_match('/namespace\s+([^;]+);/', $contents, $namespaceMatch);
        $relativeClass = substr($file->getPathname(), strlen($root) + 1, -4);
        $expectedClass = 'MageOS\\ShoppingFeed\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relativeClass);
        $actualClass = isset($namespaceMatch[1])
            ? trim($namespaceMatch[1]) . '\\' . $file->getBasename('.php')
            : '';
        $assert($actualClass === $expectedClass, sprintf('PSR-4 mismatch for %s', $relativeClass));
    }
}

$forbiddenRuntimeIdentifiers = [
    'RocketWeb\\ShoppingFeeds',
    'Rocketweb\\ShoppingFeeds',
    'RocketWeb_ShoppingFeeds',
    'rocketweb/module-shopping',
    'rw_shoppingfeeds',
    'shoppingfeeds',
    'rocketshoppingfeed',
    'rw_feeds',
    'rocket_shopping_feeds',
    "'feed_prepare_save'",
    "'adminhtml_feed_edit_tab_",
    'rsf_',
    '/cache/promotions.cache',
];
foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    if (str_contains($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)
        || str_starts_with($path, $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
        || $path === __FILE__
        || in_array($file->getExtension(), ['md', 'png'], true)
        || $file->getBasename() === 'LICENSE.txt'
    ) {
        continue;
    }
    $contents = (string) file_get_contents($path);
    if (str_contains($contents, "\0")) {
        continue;
    }
    // Discovery may name the original module/table, but must never load its PHP classes.
    // Allow only complete discovery literals and the optional Composer suggestion key.
    $discoveryLiterals = [
        'composer.json' => ['"' . $migrationPackage . '"'],
        'Block/Adminhtml/Feed/MigrationNotice.php' => [
            "'RocketWeb_ShoppingFeeds'",
            "'rw_shoppingfeeds_feed'",
            "'https://github.com/rocketweb/module-shopping-feed-migration-rocketweb#installation'",
        ],
        'Test/Unit/Block/Adminhtml/MigrationNoticeTest.php' => [
            "'RocketWeb_ShoppingFeeds'",
            "'rw_shoppingfeeds_feed'",
            "'store_prefix_rw_shoppingfeeds_feed'",
        ],
    ];
    $relativePath = substr($path, strlen($root) + 1);
    $contents = str_replace($discoveryLiterals[$relativePath] ?? [], '', $contents);
    foreach ($forbiddenRuntimeIdentifiers as $identifier) {
        $assert(
            !str_contains($contents, $identifier),
            sprintf('Legacy runtime identifier %s remains in %s', $identifier, substr($path, strlen($root) + 1))
        );
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Consolidation validation failed:\n- " . implode("\n- ", array_unique($failures)) . "\n");
    exit(1);
}

printf(
    "Consolidation validation passed: %d XML files, %d feed types, and isolated Mage-OS runtime identifiers.\n",
    count($xmlFiles),
    count($expectedFeeds)
);
