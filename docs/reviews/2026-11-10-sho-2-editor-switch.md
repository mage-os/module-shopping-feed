# SHO-2 existing mapping switch regression

Verified locally on 2026-11-10 against Mage-OS 4.0.0 with the m137 Admin theme, Chrome, and PHP 8.4. The disposable installed store also contains the prior SHO-1 functional-test changes; this fix changes only the module's parameter template.

## Fix and regression

`view/adminhtml/web/template/form/parameter.html` now requires both conditional kind and a non-null `conditionalEditor()` before creating the remote template binding. Changing `kind` can therefore no longer instantiate Magento's template engine with null data while the editor is being initialized.

The self-running regression in `dev/tests/browser/conditional-value-switch.html` loads the installed platform's actual remote template engine, Knockout bootstrap, UI field, provider, and module templates. It binds an existing Static Value mapping before switching it, configures both branches, round-trips provider data into a fresh component, checks the restored condition and outputs, and repeats the transition with cached templates.

Observed results with the same test:

- Original template: fails with `Cannot read properties of null (reading 'name')` in `RemoteTemplateEngine.makeTemplateSource`.
- Guarded template: **8 checks pass**.
- Temporarily removing the guard: original exception reproduced again.
- Restoring the guard: **8 checks pass**.

This is a real browser regression, separately invoked from the Node suite. Its provider round trip does not replace the installed Admin persistence check below. Reproduction instructions are in `dev/tests/browser/README.md`.

## Installed Admin acceptance

Created disabled synthetic Generic feed ID 40, `SHO-2 switch regression 2026-11-10`, with no schedules or uploads. Existing test feeds were preserved.

1. Opened an existing `switch_result` Static Value mapping containing `original static`.
2. Changed it to Conditional Value through the Columns Map directive select. The condition editor and both output branches rendered.
3. Configured **Price greater than 75**, matching fixed text `switch-match`, and fallback fixed text `switch-fallback`.
4. Saved through Magento Admin and observed its saved confirmation.
5. Left and reopened the form. Verified the condition, operator, value, and both branch values in the rendered controls.
6. Loaded the saved feed through Magento's model and independently asserted the persisted mapping before invoking the real generator. No PHP script supplied or changed the condition data.
7. Generation exported two products and produced:

   ```text
   sku          switch_result
   sho-match    switch-match
   sho-fallback switch-fallback
   ```

Local evidence is under `/private/tmp/sho-2-switch-20261110/`: `remote-template-before.txt`, `remote-template-without-fix.txt`, `remote-template-after.txt`, `installed-configured.txt`, `installed-reopened.txt`, `installed-reopened.png`, `persisted-mapping.json`, `sho2_switch_regression_40.tsv`, and `verify-generation.php`.

## Other checks and boundaries

- `MAGENTO_ROOT=/private/tmp/shopping-feed-functional-20261109/app node --test dev/tests/frontend/*.test.cjs dev/tests/ci/*.test.cjs dev/tests/magento-ui-form.test.cjs`: **87 passed**, no failures or skips.
- `node --check dev/tests/browser/conditional-value-switch.js`: passed.
- `php dev/tests/validate-wiki.php`: passed, 44 pages and 44 sidebar targets.
- `php dev/tests/validate.php`: the main checkout scan includes an unrelated nested `.claude/worktrees/affectionate-moser-4dc809` checkout and reports its legacy identifiers. A clean scratch copy of the current tracked files plus the new browser test passes: 26 XML files, eight feed types, isolated runtime identifiers. The nested worktree was left untouched.
- `git diff --check`: passed.

No PHP implementation changed; the full PHP unit suite was not rerun for this template fix. Browser regression is not wired into CI. This evidence establishes local behavior on the stated platform, not a deployment or other-platform acceptance. Changes remain uncommitted and unpushed.
