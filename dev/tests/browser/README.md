# Conditional Value browser regression

This test requires a disposable installed Magento or Mage-OS store with this module and deployed Admin static assets. It uses the platform's actual remote template engine, Knockout bindings, UI field, and form provider. It does not substitute a local template engine or mock the parameter component.

Copy `conditional-value-switch.html` and `conditional-value-switch.js` into the store's `pub/static/shopping-feed-tests/` directory. Open:

```text
http://YOUR-LOCAL-STORE/static/shopping-feed-tests/conditional-value-switch.html?static=/static/adminhtml/Vendor/theme/en_US/
```

Replace the origin, theme, and locale with the installed store's values. The static directory must end in `/`. Ensure deployed module templates reflect the checkout being tested, then reload with browser caching disabled when comparing revisions. Remove these test assets from the disposable store when finished; do not deploy them to a live store.

Expected result: **PASS: 8 checks**. The test binds an existing Static Value mapping before changing its directive, configures a price condition and both output branches through DOM events, verifies the actual form provider, destroys and reopens the component from its saved value, then switches away and back with cached templates. Browser errors fail the run.

Without the `conditionalEditor()` rendering guard in `view/adminhtml/web/template/form/parameter.html`, the same page fails in Magento's `RemoteTemplateEngine.makeTemplateSource` with `Cannot read properties of null (reading 'name')`. The isolated Node editor tests do not exercise this template lifecycle.

## Installed Admin persistence and generation acceptance

The self-running page checks the provider round trip; it does not submit an Admin form or run PHP generation. Complete the following separate installed-store check for changes to this lifecycle:

1. Use two enabled, salable synthetic products assigned to the feed's website: `sho-match` priced at 100 and `sho-fallback` priced at 40. Ensure feed filters include both.
2. Create a disposable Generic feed with no schedules or uploads. Use delimited output and map `sku` to SKU and `switch_result` to Static Value with `original static`. Save and reopen it so the row is an existing mapping.
3. In **Columns Map**, change `switch_result` to **Conditional Value**. Confirm condition and both output controls appear without a browser exception.
4. Add **Price**, **greater than**, `75`. Set **If matching** to fixed text `switch-match` and **Otherwise** to fixed text `switch-fallback`.
5. Save, leave the form, and reopen the feed. Verify the directive, condition, operator, value, and both outputs survived the server round trip.
6. Generate this feed using the installed module's normal generation path. Inspect the generated file, not only a success message. Require these exact rows:

   ```text
   sku          switch_result
   sho-match    switch-match
   sho-fallback switch-fallback
   ```

Record platform/version, feed ID, browser errors, reopened form evidence, and actual output. The automated browser page is a separately invoked regression, not part of the Node test suite or current CI.
