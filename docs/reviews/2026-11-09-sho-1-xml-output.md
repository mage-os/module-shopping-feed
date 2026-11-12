# SHO-1 XML output verification

Base: `mage-os/module-shopping-feed` main at `286b938045039bbbda04dc4713eb149666d50f80`.
Branch: `feature/SHO-1-xml-output`. Local changes only.

## Results

- PHPUnit: 965 tests, 2,649 assertions passed on PHP 8.5.11. Used `MAGENTO_ROOT=/private/tmp/mageos-4-unit-audit-20261106/install84` and that installation's `vendor/bin/phpunit -c phpunit.xml.dist`, with `XDEBUG_MODE=coverage --coverage-filter Model --coverage-clover /private/tmp/sho-1-coverage.xml`.
- Coverage executed `Generator::run`, `writeFeed`, and `getXmlWriter`, plus the XML writer's header, row, footer, and document validation methods. The writer covered 40 of 42 executable statements. It was not replaced by a mock.
- The wrapper-comment regression test failed before the fix: `<products><!--` and `--></products>` incorrectly accepted items hidden inside a comment. It passes after checking that the wrappers produce real item elements.
- `vendor/bin/phpstan analyse --debug --memory-limit=1G --no-progress`: no errors. Debug mode avoids a sandbox restriction on the worker listener.
- `php dev/tests/check-phpstan-baseline.php --git-ref=HEAD phpstan-baseline.neon`: unchanged baseline, 1,714 entries and 2,403 existing errors.
- PHPCS: full repository scan, followed by a clean check of the final changed files. No source errors or warnings remain; the installed standard emits its own deprecation notices.
- `composer validate --strict --no-check-publish`: passed.
- `php dev/tests/validate.php`: passed, 26 XML files and eight feed types.
- `php dev/tests/validate-wiki.php`: passed, 45 pages and sidebar targets.
- `php dev/tests/validate-magento-xml.php "$MAGENTO_ROOT"`: passed, 26 files.
- `node --test dev/tests/frontend/*.test.cjs dev/tests/ci/*.test.cjs`: 79 passed.
- `MAGENTO_ROOT=... node --test dev/tests/magento-ui-form.test.cjs`: five passed.
- PHP/PHTML syntax checks: 435 files passed. `git diff --check`: passed.

Static tools used the installed dependencies in `/Users/matt/code/module-shopping-feed-standards/vendor` through a temporary local symlink. PHPUnit used the Magento installation's own dependencies to avoid duplicate framework registration.

## Remaining acceptance

Live browser save/reload, database persistence, and generation against an installed product catalog have not been tested. The exact scenarios are in `ACCEPTANCE-TEST-PLAN.md`, under SHO-1. The admin form transport test proves that XML markup and settings survive provider encoding and save decoding; it does not establish database or browser behavior.

This provides flat item fields and repeated elements, with namespaces declared in the document header. Nested per-item structures, XML attributes, destination-specific presets, and channel certification are outside this implementation.
