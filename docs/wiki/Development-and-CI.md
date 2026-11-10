# Development and CI

The repository validates module identity, configuration integrity, PHP behavior, and supported Magento-family platforms. Run focused checks before requesting review.

> Documentation baseline: release 1.2.5 (`v1.2.5`); earlier acceptance is identified by version. Last reviewed: 2026-10-22.

## 1.2.5 product attribute fixes

The release adds the module's first data patch, `Setup/Patch/Data/AddSkipSubmitAttribute`, so `Setup` is now covered by the coding standard, PHPStan, and the consolidation validator's patch allowlist. The candidate passes 951 unit tests on PHP 8.4 and 8.5, 38 integration tests on a fresh Mage-OS 3.6.0 install, four database persistence tests, and 84 JavaScript checks; the PHPStan baseline shrank by one entry. The [PR 24 CI run](https://github.com/mage-os-lab/module-shopping-feed/actions/runs/39718036238) passed all 38 checks at `79b6dbc`; results for the release commit are recorded after publication.

## 1.2.4 standards and security

The workflow now targets Mage-OS 3.5.0 and 3.6.0, uses pinned actions and development tools, runs locked dependency audits, and enforces PHPStan level 8 against a baseline that may only shrink. The released Mage-OS 3.6 candidate passed 939 unit tests and 84 JavaScript/CI-policy checks; integration and transfer evidence is linked from [Release 1.2.4](Release-1-2-4). All 37 [post-merge CI jobs](https://github.com/mage-os-lab/module-shopping-feed/actions/runs/39372608509) passed for release commit `75dd335f930aef78ae693f62c3039e469c2d26f6`. Use the [current workflow](https://github.com/mage-os-lab/module-shopping-feed/actions/workflows/check-extension.yaml) when evaluating later commits.

The PHP 8.1 tool graph uses the existing legacy compatibility policy only for the exact Magento base 2.4.7-p10, framework 103.0.7-p10, and Flysystem 2.5.0 versions. Its known upstream advisory remains visible, and other findings fail. Other profiles retain strict dependency auditing.

## 1.2.3 hardening and compatibility

The CI matrix explicitly targets Mage-OS 3.5.0 alongside supported Magento Open Source profiles. The combined release candidate passes 922 unit tests and 2481 assertions locally on the Mage-OS 3.5 framework. The [hardening candidate CI](https://github.com/mage-os-lab/module-shopping-feed/actions/runs/37792760180) passed 911 unit tests and 27 integration tests per platform, 44 JavaScript checks, and all 25 jobs. The extra tests in the release candidate cover the already-merged package-version display fix. Review [historical 1.2.3 CI](https://github.com/mage-os-lab/module-shopping-feed/actions/workflows/ci.yml?query=branch%3Amain) against the exact release SHA. See [Release 1.2.3](Release-1-2-3) for source and native-theme evidence.

## 1.2.2 patch verification

The #14 description correction adds 17 cases. The core suite passes 863 tests on PHPUnit 9, 10, and 12, and 47 JavaScript checks pass. The merged runtime passed all 25 [post-merge CI jobs](https://github.com/mage-os-lab/module-shopping-feed/actions/runs/37332361658). See [Release 1.2.2](Release-1-2-2) and the dated release preparation record in the repository. Earlier store/browser results below keep their original scope.

## 1.2.0 editor implementation and historical evidence

The UI Component forms introduced in 1.2.0 live in `view/adminhtml/ui_component/mageos_shopping_feed_form.xml` and `mageos_shopping_feed_test_form.xml`, with providers under `Ui/DataProvider/Feed` and controls under `view/adminhtml/web/js/form`. Use `ShoppingFeedFormModifierPool` for custom metadata/data and the repository's `docs/ui-component-editor.md` for the contract. Previous form files remain for transition; their passing tests do not prove the replacement form. The legacy menu and category tree still have live callers, and renderer class names remain active configuration identifiers. Audit those dependencies and validator/test references before removing files.

Candidate `133af71` passes **809 PHP unit tests** on Mage-OS 3.5.0 and Magento Open Source 2.4.8/2.4.9. Assertions total 1,879 on Magento 2.4.8 and 2,158 on the other two frameworks. Both Docker platforms pass **21 official integration tests / 53 assertions** and production compilation. The stock, frontend scope, and currency regressions were observed failing before their corrections. The repository's `docs/reviews/2026-10-03-local-acceptance.md` records that historical deployment, runtime evidence, and remaining checks.

The unchanged JavaScript/XML code retains **29 frontend tests**, **26 XML schema checks**, and five actual-framework date/null-value cases per platform. Nine frontend tests cover the active form; others cover retained legacy behavior and the storefront. Earlier four-test database regressions, six-role browser/route checks, and eight-preset form/output comparisons remain recorded in the linked reports. Do not substitute aggregate unit counts for those runtime checks.

The historical 1.2.0 Docker checks cover scheduler edge cases, interrupted and competing workers, private FTP/SFTP delivery, semantic product modes, native configurable MSI, pricing, store-scoped frontend rendering, 5000-product generation for every preset, and disable/re-enable rehearsals. Two real hourly cron cycles passed with stable output and empty queues; that dated record includes their observation window and final cleanup. External recipient and target-store acceptance remain separate gates.

## Local validation

From the module repository root:

```bash
composer validate --strict --no-check-publish
find . -path './.git' -prune -o -path './vendor' -prune -o -type f \( -name '*.php' -o -name '*.phtml' \) -print0 | xargs -0 -n1 php -l
php dev/tests/validate.php
php dev/tests/validate-wiki.php
node --test dev/tests/frontend/*.test.cjs dev/tests/ci/*.test.cjs
```

The coding standard and static analysis run from the module's own development install. `composer update` installs the pinned tools from `require-dev` (PHPStan 2.3.1, bitexpert/phpstan-magento 0.44.0, magento/magento-coding-standard 41) together with the Magento packages they analyse; `vendor/` and `composer.lock` stay untracked. Use the newest PHP the module supports, as CI does:

```bash
composer update
vendor/bin/phpcs
vendor/bin/phpstan analyse --memory-limit=-1
```

PHPCS warnings fail the run. PHPStan runs at level 8 with `phpstan-baseline.neon`, the reviewed debt carried over from earlier releases. The baseline may only shrink: never regenerate it to absorb new findings. When a change fixes baselined debt, PHPStan reports the now-unmatched pattern; delete that entry by hand. Check a baseline against the base branch with:

```bash
php dev/tests/check-phpstan-baseline.php --git-ref=origin/main phpstan-baseline.neon
```

The baseline is the debt list: every entry names the message, identifier, count and file, and `php dev/tests/check-phpstan-baseline.php phpstan-baseline.neon phpstan-baseline.neon` prints its totals. To see that debt as PHPStan output, comment out the `phpstan-baseline.neon` include in `phpstan.neon.dist` for a local run and restore it afterwards. Lower `--level` values show which levels are already clean.

Run the unit suite with the PHPUnit installation from an existing Magento or Mage-OS checkout:

```bash
MAGENTO_ROOT=/path/to/magento /path/to/magento/vendor/bin/phpunit -c phpunit.xml.dist
```

Run `MAGENTO_ROOT=/path/to/magento node --test dev/tests/magento-ui-form.test.cjs` for promotion-date conversion and directive initialization against the installed platform's actual date, abstract-field, and value-link components. This separate suite needs a framework checkout and covers three Admin locale formats plus preservation of null directive parameters.

Run `MAGENTO_ROOT=/path/to/magento node --test dev/tests/product-selection-editor.test.cjs` for the shared SHO-2/SHO-3 editor using the platform's actual Knockout observables. The SHO-3 installed-store acceptance and browser save/reopen evidence are recorded in `docs/reviews/2026-11-10-sho-3-product-selection.md`.

The consolidated validation checks package and module identity, feed configuration, Magento XML, schema whitelist alignment, isolated runtime identifiers, storefront integration markers, and selected regression-sensitive behaviors. Wiki validation checks navigation, page baselines, and known legacy instructions.

## Disposable database regressions

The persistence tests use the actual Magento resource load/save path with session-local temporary tables and a synthetic encryption key. They do not bootstrap a store or read its database credentials. Start a disposable MariaDB container with database `shopping_feed_test`, an empty test-only root password, and port 3306 mapped to a random **127.0.0.1** port. Run:

```bash
SHOPPING_FEED_TEST_DB_PORT=<mapped-port> MAGENTO_ROOT=/path/to/magento \
  php /path/to/magento/vendor/bin/phpunit --bootstrap Test/Unit/bootstrap.php Test/Database
```

Stop and remove the disposable container afterward. The tests check raw ciphertext after an Admin-style masked save, repeated saves, passwords whose encrypted form exceeds 255 bytes, and same-day interrupted queue recovery. They complement the full Magento integration suite and do not replace an installed-store schema upgrade check.

## CI coverage

The `.github/workflows/check-extension.yaml` workflow runs on pushes to `main` and `feat/**` and on every pull request, whatever its base branch. Releases up to 1.2.3 ran the same checks from the earlier `ci.yml` workflow, whose run history stays under that name. It runs:

* Composer metadata validation
* PHP syntax checks across PHP 8.1 to 8.5
* Consolidated module and wiki validation
* Hyva and Luma storefront auto-selection checks and the CI-policy script tests
* The pinned Magento coding standard, PHPStan level 8 with the reviewed baseline, and a check that the baseline did not grow compared with the base commit
* Graycore's reusable check-extension workflow for the supported Magento Open Source releases and for Mage-OS 3.5.0 and 3.6.0: installation, unit and integration tests, DI compilation, the coding standard and PHPStan (enabled in `.github/check-extension.json`)
* A locked `composer audit` of each normal platform project with the module installed, and of the module's own runtime and development dependencies on PHP 8.1 and 8.5. Any advisory, ignored advisory or filter-list match fails the job; abandoned packages are reported
* The dedicated Magento 2.4.7-p10 profile described below

Dependabot proposes weekly Composer and GitHub Actions updates.

The exact supported PHP constraint remains authoritative in `composer.json`. The current [Status and compatibility](Status-and-Compatibility) page translates that metadata for users.

The earlier Google/custom-feed acceptance profile on Mage-OS 3.5.0 passed 413 unit tests with 955 assertions, 10 application integration tests with 26 assertions, 14 frontend tests, and 33 generation checks. See [Release 1.1.0](Release-1-1-0) for the separate earlier Nebula, no-Nebula, and storefront runs. PHPUnit 9, 10, and 12 use the same data-provider coverage; compatibility fixtures explicitly configure optional arguments and date modification. The follow-up issue review passed 433 unit tests, 19 JavaScript tests, and 42 feed/stock checks and is recorded in the [issue acceptance report](https://github.com/mage-os-lab/module-shopping-feed/blob/v1.1.0/docs/reviews/2026-09-28-github-issues.md). Those historical local results do not establish CI or recipient acceptance for the current release. Use the [Check Extension run history](https://github.com/mage-os-lab/module-shopping-feed/actions/workflows/check-extension.yaml) for the status of an exact commit; releases up to 1.2.3 are in the [earlier CI history](https://github.com/mage-os-lab/module-shopping-feed/actions/workflows/ci.yml).

## Documentation changes

Update documentation in `docs/wiki`, not directly in the public wiki. Follow `docs/WIKI-MAINTENANCE.md` for source precedence, page conventions, drift checks, and the separate publication approval boundary.

When behavior changes, update the implementation, tests, relevant wiki page, and [release acceptance](Release-Acceptance) evidence in the same reviewable change. Do not carry historical instructions forward when current code disagrees.

## Review expectations

Provide:

1. The exact behavior or documentation claim changed
2. The current-code evidence for it
3. Focused validation output
4. Platform or runtime evidence when behavior depends on Magento
5. Any unverified external service behavior or remaining limitation

Passing local checks is not evidence that a wiki was published or that an external feed recipient accepted an output file.

## Magento 2.4.7-p10 test profile

The dedicated PHP 8.3 job tests this exact platform without changing other platform jobs. Its temporary root project permits resolution of one Flysystem advisory while retaining it in `composer audit`; additional advisories fail the job. It also tests the optional security backport in an isolated library copy. See [Status and compatibility](Status-and-Compatibility) for the dependency guidance and acceptance record. The extension package does not distribute a Composer audit exception.

Magento 2.4.7 uses PHPUnit 9. Keep `@dataProvider` annotations alongside `DataProvider` attributes so parameterized tests run on both older and newer frameworks. Run the CI-policy checks with `node --test dev/tests/ci/*.test.cjs`; run actual-framework form checks with `MAGENTO_ROOT=/path/to/magento node --test dev/tests/magento-ui-form.test.cjs`.
