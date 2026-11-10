# Admin UI Component editor

Baseline: release 1.2.4 (`v1.2.4`). Reviewed October 20, 2026. The editor was introduced in 1.2.0; historical version 1.1.0 uses the previous editor.

**Historical 1.2.0 acceptance.** The [October 3 acceptance record](reviews/2026-10-03-local-acceptance.md) covers `133af71`, the completed form checks, and extended product, MSI, pricing, operations, and rollback verification. The [earlier deployment report](reviews/2026-10-02-mageos-latest-deployment-acceptance.md) records eight-preset persistence/output and category/promotion-date repair evidence. Magento Open Source 2.4.8 and 2.4.9 retain their [Docker form workflows](reviews/2026-10-02-magento-docker-acceptance.md) and [six-role grid checks](reviews/2026-10-02-grid-permission-acceptance.md). The final suite passes 809 unit tests on all three frameworks and 21 official integration tests on both Docker versions. Native Nebula bridge acceptance remains separate.

The default New/Edit Feed screen and Test Feed screen use Magento UI Component forms. The editor keeps the existing routes, feed types, configuration keys, database schema, generators, and save permissions. General settings, mappings, categories, filters, product options, product relationships, shipping, schedules, uploads, and Google promotions have corresponding form sections, with the existing type-specific differences. See the [operating guide](wiki/Admin-UI-Component-Forms.md) for controls and current limitations.

Version [1.2.3](releases/1.2.3.md) retains this editor contract and includes subsequent inventory, promotion persistence, state-saving, and Nebula filter corrections. The [native-theme acceptance](reviews/2026-10-08-remote-ci-and-native-theme-acceptance.md) covers the Nebula 0.9.0 listing, permissions, and supported Magento backend editor fallback. Native Nebula UI Bridge rendering remains unverified.

Version [1.2.4](releases/1.2.4.md) adds a required **SFTP Host Key Fingerprint** field, encrypted upload model data, and **Generate Feed** authorization for Test Feed. `Upload::getPassword()` returns ciphertext; request `getDecryptedPassword()` only when opening a transport. Cloning preserves ciphertext and the pinned key and rolls back failed copies. Its [Mage-OS 3.6 verification](reviews/2026-10-20-standards-security-mageos36.md) is separate from the older multi-platform browser/theme results.

## Platform and customization compatibility

The [Magento 2.4.7-p10 profile](compatibility/magento-2.4.7-p10.md) uses the same UI Component forms on PHP 8.3. Its [acceptance record](reviews/2026-10-03-magento-247-acceptance.md) is separate from the broader 2.4.8/2.4.9 operational runs. Its upstream Flysystem advisory requires a platform mitigation decision; no editor downgrade or module dependency change is required.

The migration does not raise Composer's platform requirements. Earlier disposable Magento Open Source 2.4.8 and 2.4.9 installations passed the recorded form, output, DI, and static-deployment checks but missed the later defects. The repairs now pass unit, integration, database, framework JavaScript, production compilation, and browser/output checks on both versions. The Docker run used PHP 8.4.26 and the standard Magento/backend Admin. This verifies the recorded workflows and fixtures; it does not establish third-party editor customization, native Nebula bridge, external recipients, or production-catalog capacity. A separate 5,000-product synthetic run is recorded in the current acceptance report.

The customization change affects both the module's own former observers/plugins, now replaced with metadata, and any third-party modules that extended those old editor hooks. A store without those customizations does not need to write its own adapter. Sites with custom observers, plugins, or parameter renderers need the migration described below. Inventory downstream integrations before upgrading. The subsequent stock fix also adds `linkedProductCollectionFactory` before `data` in the `Composite` and `Configurable` adapter constructors. Custom PHP subclasses overriding those constructors must forward the dependency; ordinary module installations require no custom adapter. See the [stock correction report](reviews/2026-10-02-stock-acceptance-fixes.md).

## Form architecture

- `mageos_shopping_feed_form.xml` declares the provider, submit route, and buttons. `Ui/DataProvider/Feed/FormDataProvider` supplies data and applies the `ShoppingFeedFormModifierPool` modifiers.
- `Form/Fields` and `Form/Metadata` define fields, dependencies, and DynamicRows. `Form/Options` reuses the existing option sources. `Form/Parameters` maps the bundled directive renderer identifiers to declarative parameter controls.
- `Model/Adminhtml/FeedFormData` projects only editor fields and masks upload passwords before they reach provider JSON. Initial data uses a protected JSON envelope decoded after UI template initialization, preserving literal `${...}` text and nested array shapes through Magento's metadata sanitizer. The JavaScript provider also sends a JSON envelope so empty collections, nested parameters, zero, false, and literal text survive serialization.
- Mapping rows keep their explicit Order values, including duplicate priorities. New rows default after the largest existing order. Other configuration row lists preserve drag-and-drop positions when saving.
- DynamicRows removes deleted records. The provider remembers the initial schedule/upload IDs and submits explicit deletion markers for removed persisted children. The existing model still checks that each child belongs to the feed.
- The converter applies submitted configuration keys to the loaded feed. Unexposed custom configuration remains stored. Selects preserve unavailable saved options, and unknown parameter renderers preserve their values with a read-only notice.
- Existing feeds with a blank `general_currency` display their effective generation currency. An unchanged save persists that choice instead of substituting a different store default. New feeds still default to the store currency, and explicit saved values remain intact.
- Failed saves use `DataPersistor` keyed to the feed ID, type, and store. New and changed passwords must be entered again; decrypted passwords and typed replacement passwords are not restored into provider JSON.

The old column widget required stripping newlines and coercing all mapping values to strings before saving. That workaround is removed. The new form preserves literal text, structured parameters, and null parameters. Its parameter component starts with a null value and preserves it during initial-value calculation because Magento skips null imports while exporting non-null defaults. This keeps generator defaults distinct from an explicitly empty parameter. Model save now rejects control characters in column names, preventing unenclosed header corruption without changing parameter text. Product-value cleaning and CSV serialization still apply during generation.

Category conversion validates both decoded envelopes and legacy JSON maps, preserves valid custom row fields, and supplies embedded IDs before pruning defaults. Generation also recovers missing IDs from existing map keys before sorting. Preview validates lookup inputs before product loading. The `promotion-date` component keeps localized display separate from canonical storage. New Google Shopping feeds retain the microdata default of one; all other new presets default to zero, and existing selections are preserved. See the [reconciled review](reviews/2026-10-01-ui-component-editor-review.md) for the original failure mechanisms.

## Extending the editor

PHP form-block `prepare_form_*` events and plugins on the legacy tab blocks no longer customize the default editor. Their six bundled observers and two tab-visibility plugins have been replaced by metadata behavior. The previous form classes/templates remain in the package for a transition period. Some related classes still have live callers: `Block/Adminhtml/Feed/Edit/Menu` supports the Nebula fallback, and `Edit/Tab/Options/Category/Tree` supplies category suggestions. Legacy renderer class names also remain configuration identifiers resolved by `Form/Parameters`. Audit these dependencies, validator references, and tests before removing retained files.

Use a Magento UI data-provider modifier implementing `Magento\Ui\DataProvider\Modifier\ModifierInterface`. Register it in your module's `etc/adminhtml/di.xml`, with a module sequence after `MageOS_ShoppingFeed`:

```xml
<virtualType name="ShoppingFeedFormModifierPool" type="Magento\Ui\DataProvider\Modifier\Pool">
    <arguments>
        <argument name="modifiers" xsi:type="array">
            <item name="vendor_feed_fields" xsi:type="array">
                <item name="class" xsi:type="string">Vendor\Module\Ui\FeedModifier</item>
                <item name="sortOrder" xsi:type="number">100</item>
            </item>
        </argument>
    </arguments>
</virtualType>
```

`modifyMeta()` receives fieldsets named `general`, `columns`, `categories`, `filters`, `options`, `configurable`, `grouped`, `bundle`, `shipping`, `schedule`, `uploads`, and, for Google Shopping, `promotions`. Google Local Inventory feeds have no `options` or `shipping` fieldset. Metadata uses `arguments/data/config`. `modifyData()` receives `[$feedId => $fields]`, or `['' => $fields]` for a new feed. Configurable settings use a `config.<existing_key>` data scope. A modifier adding a stored setting must supply its current value in `modifyData()` as well as its field metadata. The current feed is registered as `feed` in Magento's registry.

For a custom directive's parameter editor, add its existing PHP renderer class identifier to the `renderers` array argument of `MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form\Parameters`. Supported definitions are `input`, `textarea`, `select`, `multiselect`, and `none`; use `label`, `notice`, and `options`, or an option `source` registered with `Form\Options`. The directive's configured `param` supplies the default when the administrator changes directives. More complex controls can replace the mapping parameter component through metadata. Arbitrary PHP/PHTML renderer output is not executed by the new form.

The `mageos_shopping_feed_feed_prepare_save` event remains available and receives decoded form parameters. Existing non-UI save requests remain supported. Do not rely on browser validation for authorization or data ownership.

Modifier authors must mask secrets before returning data. Upload model data retain ciphertext in 1.2.4. The built-in projection is redacted before `modifyData()` runs, so a modifier must not add decrypted upload passwords or other private model fields back into the response. Preserve unknown configuration and child ownership when adding fields; cover failed-save recovery as well as successful saves. Custom `config` keys are an intentional extension contract and may be consumed by downstream directives or modules. Validate known fields without replacing that contract with a built-in-only key allowlist. Custom controls must enforce their own permission and escaping requirements.

## Unreleased SHO-1 XML controls

The General fieldset exposes `config.output_params_format` with `delimited` as the default and `xml` for Generic and the six supported channel feeds. OpenAI remains delimited only. Generic XML controls use `config.output_params_xml_header`, `config.output_params_xml_item`, `config.output_params_xml_footer`, `config.output_params_xml_optional_columns`, and `config.output_params_xml_cdata_columns`. Header and footer use multiline textareas. Show these fields only for Generic XML; delimiter/enclosure/escape/empty-cell controls apply only to delimited output. A filename extension does not select the serializer.

Channel RSS presets resolve their canonical structure from `mageos_shopping_feed.xml`, including aliases, compound fields, lists, and unprefixed fields. Saved generic wrappers do not override a destination's preset. Preserve this behavior in modifiers rather than exposing misleading editable structure fields on channel feeds. The [XML output guide](wiki/XML-Output.md#preset-configuration) documents preset configuration and user-facing behavior.

Save/import validation checks XML names, wrappers, and declared namespaces. Generation validates product values; row data failures are logged and skipped, while configuration and I/O failures stop generation. Custom mappers should preserve structured shipping and image values through the supported XML path instead of flattening separators prematurely. Delimited consumers retain their existing representation.

## Grid permissions and extensions

The standard grid checks `MageOS_ShoppingFeed::save` for Create New Feed, Configure, Enable, Disable, and Clone; `MageOS_ShoppingFeed::generate` for Run Now and the Test Feed controller/editor buttons; and `MageOS_ShoppingFeed::delete` for bulk Delete. View Log and Export remain available with the grid permission. Both standard and Nebula grids can still display Test Feed to a read-only user, but the controller denies the request. Do not treat link visibility as permission to run a preview. The bulk-action dropdown is omitted when no permitted actions remain. Controller ACL and POST/form-key checks still enforce requests independently of visibility.

The grid uses `Ui/Component/Listing/MassAction`, which filters each action's `config/aclResource` after Magento collects the actions. Custom actions without that setting retain their existing visibility; mutation actions added by other modules should declare their own ACL resource and enforce it in their controller. Explicitly disabled actions remain disabled.

`Ui/Component/Listing/Column/FeedActions` now receives `Magento\Framework\AuthorizationInterface` after `$urlBuilder` and before the optional arrays. Magento DI supplies it. A downstream subclass that calls this constructor explicitly must pass the added dependency; a plugin that adds row actions must check its own permissions.

## Nebula support

This is one shared Magento form implementation and has no required Nebula dependency. It removes the default editor's reliance on the old PHP form renderer, Prototype-era row widgets, global editor scripts, and the custom PHP dependency element. Future theme support can use the same form metadata and persistence contract.

The existing `FeedEditorTheme` fallback and menu cache handling remain. The inspected Nebula 0.9.0 installation does not include UI Bridge, and translating the custom parameter/category controls through a bridge has not been accepted. Nebula installations therefore continue using Magento/backend for the editor, preview, and log routes. The separate Nebula grid integration is unchanged. The scope review counted approximately 575 integration lines, roughly 495 of them for the grid. These counts include comments and whitespace. The migration removes the active editor's need for legacy dependency/rendering workarounds; it does not delete most of that integration. Retiring the remaining editor fallback requires separate native Nebula acceptance. See the [original scope and counts](plans/2026-10-01-ui-component-editor-scope.md#reduction-in-nebula-specific-customization).

The [October 8 native theme acceptance](reviews/2026-10-08-remote-ci-and-native-theme-acceptance.md) rechecks the Nebula 0.9.0 grid and this supported fallback on Mage-OS 3.5.0. It covers create/save/reopen, literal mapper parameters, the twelve Google sections, preview, logs, native bulk actions, queued generation, and a read-only role. Native UI Bridge form rendering remains unverified.

Existing users with custom observers, tab plugins, or parameter renderers should migrate those editor integrations before upgrading. This is an extension customization API change, independent of Magento platform compatibility.

## Rollback

The 1.2.0 editor migration itself has no schema change or editor switch. The later 1.2.4 SFTP fingerprint column does require `setup:upgrade`; retain a matching database/code/lock/configuration backup when crossing that release. Changing the Admin theme does not restore the old editor. Reverting the package code restores it after the normal DI compilation, static asset deployment, and cache refresh for that installation.

Code rollback does not repair promotion dates already erased by a save or category mappings already written without IDs. Retain the pre-upgrade configuration backup and compare affected feeds before restoring individual values. Test site-specific structured parameters against the older editor, which normalizes mapping values more aggressively. The current repair deployment's scoped backup and rollback are documented in the [Mage-OS deployment record](reviews/2026-10-02-mageos-latest-deployment-acceptance.md#deployment-and-rollback). The earlier migration backup remains separate.
