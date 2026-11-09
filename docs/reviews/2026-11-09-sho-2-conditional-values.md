# SHO-2 Conditional Value local verification

Date: 2026-11-09. Scope: uncommitted changes in the local module checkout based on `15ec484`.

The feature adds one All/Any group of 1 to 50 Magento product conditions to each Conditional Value mapping. Matching and nonmatching branches support fixed text, store-view attribute labels, and percentage-adjusted raw numeric attributes. All eight feed presets expose the directive. Save validation covers both Columns Map and Replace Empty Values; generation validates imported parameters too.

The editor uses the existing UI Component parameter and JSON-envelope contracts. No schema change or migration is required. The feature declares its direct dependency on Magento_Rule. Existing feed configuration is unchanged unless a merchant selects the new directive.

## Verification

- Full PHP suite: **968 tests, 2671 assertions passed** on PHP 8.4.27, using `MAGENTO_ROOT=/Users/matt/code/mageos-latest php /Users/matt/code/mageos-latest/vendor/bin/phpunit -c phpunit.xml.dist`.
- New conditional-value suite: **17 tests, 57 assertions passed**. Cases cover All/Any, match and fallback, raw multiselect IDs, missing values without product reload, store-view attribute mapping, positive/negative/zero percentage outputs, nonnumeric rejection, dates without product mutation, malformed configurations, both save locations, all feed registrations, and form-envelope round trips.
- Xdebug Clover coverage confirms execution of `Condition::validate`, `Configuration::validate`, `ConditionalValue::map`, and its output resolver. The missing-attribute check first failed because the inherited Magento validation attempted to reload the product; the feed-specific evaluator avoids that reload.
- Frontend/CI/framework JavaScript suites: **87 tests passed**, using `MAGENTO_ROOT=/Users/matt/code/mageos-latest node --test dev/tests/frontend/*.test.cjs dev/tests/ci/*.test.cjs dev/tests/magento-ui-form.test.cjs`. New cases use Magento's actual Knockout library to verify both outputs, literal text, zero values, isolation between columns, and late provider imports without rebuilding controls during typing.
- Chrome fixture: add an attribute-set condition, choose Clothing, return Manufacturer with `0` fallback, save and reload. Then switch the output to Price with `-10%`, select Any condition, and reload again. All controls retained their values. No browser errors or warnings were captured.
- Magento XSD validation: **52 XML files passed**. Composer validation and wiki validation passed.
- Consolidation validation: **26 XML files and eight feed types passed** in a clean source snapshot. Running directly in this checkout also scans an unrelated ignored `.claude/worktrees` tree and reports legacy identifiers there. That tree was left untouched.
- Scoped Magento coding-standard and PHPStan level 8 checks cover the changed PHP files. No baseline entries were added.

Temporary evidence is under `/private/tmp/sho-2/`, including `coverage.xml`, `node-tests.txt`, and `conditional-value-editor.png`. These files are local evidence, not release artifacts.

## Acceptance limits

The local browser fixture uses Magento's real Knockout library and the module's actual parameter templates, with synthetic options. It does not establish installed Magento Admin layout, database persistence, DI compilation, cron generation, or recipient acceptance. An installed-store save/reopen/Test Feed check remains necessary before release. No store was modified, and no issue status, comment, commit, push, deployment, tag, or release was made.

Nested groups, multiple ordered rules, arbitrary formulas, computed-price directives, and cross-column references are outside this implementation. Percentage calculations use raw attributes, round to two decimals, and do not add tax, convert currency, or attach a currency code. Missing source values remain empty. Existing inheritance, text cleaning, and empty-value replacement rules remain active.
