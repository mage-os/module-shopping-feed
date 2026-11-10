# SHO-1 functional-test follow-up

Resolved the two non-SHO-2 findings from the combined installed-store test report.

- Removed the Microsoft destination's obsolete `(TSV)` qualifier from config and translations. Updated its guide for the automatic XML preset, filename, and shipping mapping.
- Fixed the pre-existing shared collection provider retaining a preview SKU during subsequent full generation in the same PHP process. Both product selection and total counting now explicitly set the current generator's scope, including null for full output.

Verification on the existing isolated Mage-OS 4 installation:

- New PHPUnit regression cases for `getCollection()` and `getTotalItems()` both failed with one product instead of two before the fix, then passed. Clover records execution of both real methods.
- SHO-1 PHPUnit: 973 tests, 2,735 assertions passed.
- Combined SHO-1/SHO-2 PHPUnit: 990 tests, 2,792 assertions passed.
- Installed generation/persistence and boundary suites: 98 checks passed after removing the harness's manual SKU-filter reset.
- Dedicated preview/full-generation reproduction now exports both products.
- Magento's loaded feed config returns `Microsoft Merchant Center`.
- PHPCS on `Model/Generator.php` and `Test/Unit/Model/GeneratorTest.php` passed, with existing tool deprecation notices only.

Commands used:

```sh
cd /private/tmp/shopping-feed-sho-1
MAGENTO_ROOT=/private/tmp/shopping-feed-functional-20261109/app \
  /opt/homebrew/opt/php@8.4/bin/php -d xdebug.mode=off \
  /private/tmp/shopping-feed-functional-20261109/app/vendor/bin/phpunit -c phpunit.xml.dist

cd /private/tmp/shopping-feed-functional-20261109
/opt/homebrew/opt/php@8.4/bin/php -d xdebug.mode=off reproduce-shared-preview.php
/opt/homebrew/opt/php@8.4/bin/php -d xdebug.mode=off functional.php
/opt/homebrew/opt/php@8.4/bin/php -d xdebug.mode=off boundaries.php
```

Evidence is retained in `/private/tmp/shopping-feed-functional-20261109/evidence/sho-1-fixed-*`, `sho-1-scope-coverage.xml`, `shared-preview-before-fix.json`, and `shared-preview-reproduction.json`.

SHO-2 remains unchanged: switching an existing non-conditional mapping to Conditional Value activates Magento's remote template before editor data exists, causing a null `name` JavaScript error and a blank editor. Saved conditional mappings and newly added default conditional rows work. Its tests need the actual Magento template-engine transition, not only saved-data initialization.

Changes remain local and uncommitted. External provider ingestion is still unverified.
