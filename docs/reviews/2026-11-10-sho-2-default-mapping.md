# SHO-2 new mapping defaults

Addresses D1 from the independent editor-switch review. The XML declared Conditional Value before Static Value in all eight feed types. Magento's select component picks the first option for a new mapping, creating an empty conditional rule that cannot pass validation.

## Change

Restore Static Value as the first directive in all eight definitions in `etc/mageos_shopping_feed.xml`. Conditional Value remains available as the second option. Both Columns Map and Replace Empty Values use the same directive source. No validation rules or saved mappings are changed.

## Regression coverage

`Test/Unit/Ui/DataProvider/Feed/Form/MappingDefaultsTest.php` reads the actual feed XML through the real converter, builds the directive option source, and inspects the real form metadata for both mapping sections of every feed type. Database and unrelated option providers are stubbed. It checks that the effective new-row default is Static Value and that Conditional Value remains available.

- Before the fix: all 16 cases failed, expecting Static Value but receiving Conditional Value.
- With the fix: 16 tests and 48 assertions passed.
- Temporarily restoring the original XML: all 16 cases failed again.
- Fixed XML restored: 16 tests and 48 assertions passed.

The test is part of the existing PHPUnit test discovery, so it runs with the normal unit suite.

## Installed-store checks

Tested the existing disposable Mage-OS 4.0.0 store at `http://127.0.0.1:8131`, using PHP 8.4.27 and Chrome. Applied only the directive-order change to the installed XML, preserving its SHO-1 overlay, and cleaned the local configuration cache. The store also includes the existing SHO-3 changes.

Created disabled synthetic feed 145 with no schedules or uploads. In the actual Admin, adding a Columns Map row and a Replace Empty Values row selected Static Value and displayed an empty parameter input. An existing saved Conditional Value mapping still displayed its condition and both branches.

The browser connection repeatedly detached during field input and actions. The new column name appeared in the DOM, but Magento still reported it as required. No successful browser save/reopen is claimed for this run, and this observation does not establish a module defect. A fresh tab did not resolve the automation problem.

Separately, synthetic feed 146 exercised the installed `FeedFormData` encode/decode, `Feed\Converter`, model save/load, and form projection. Both new mappings used the installed configuration's first directive, Static Value, with null parameters. Both persisted and projected correctly for reopening. This check did not submit the Admin controller through a browser.

## Other verification

Commands used the installed platform's PHPUnit and the standards checkout's PHPCS:

```sh
MAGENTO_ROOT=/private/tmp/shopping-feed-functional-20261109/app \
  /opt/homebrew/opt/php@8.4/bin/php -d xdebug.mode=off \
  /private/tmp/shopping-feed-functional-20261109/app/vendor/bin/phpunit \
  --colors=never -c phpunit.xml.dist

/opt/homebrew/opt/php@8.4/bin/php -d xdebug.mode=off \
  /Users/matt/code/module-shopping-feed-standards/vendor/bin/phpcs \
  Test/Unit/Ui/DataProvider/Feed/Form/MappingDefaultsTest.php
```

- Full PHPUnit suite: 1019 tests, 2859 assertions, passed.
- PHPCS for the new test: passed. The tool printed existing deprecated-sniff notices.
- Wiki validator: 44 pages and 44 sidebar targets, passed.
- Consolidation validator on a scratch copy of tracked files: passed, 26 XML files and eight feed types. Used a scratch copy to avoid the previously documented nested-worktree scan issue.
- `git diff --check`: passed.

Evidence and fixture scripts are under `/private/tmp/sho2-defaults-20261110/`, including `before.txt`, `mutation.txt`, `after.txt`, `phpunit.txt`, `admin-before-save.txt`, `before-save.png`, and `server-persisted.json`.

Changes remain uncommitted and unpushed. Unrelated checkout changes and prior reports were preserved. Browser save/reopen remains the outstanding acceptance check for this follow-up.
