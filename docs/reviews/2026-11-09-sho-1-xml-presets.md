# SHO-1 destination XML presets

Follow-up to the generic XML writer in `4aa30bc`, on `feature/SHO-1-xml-output`.

## Implemented behavior

- Google Shopping, Google Local Inventory, Microsoft Merchant Center, Pinterest, TikTok, and Meta use canonical RSS presets from `mageos_shopping_feed.xml` when XML output is selected.
- Channel metadata uses the feed name and selected store URL, escaped as XML. Fields receive the Google namespace prefix, empty values are omitted, and zero values are retained.
- TikTok maps `sku_id` to `g:id` after existing row validation. Its image and shipping strings retain their destination-specific packed representation.
- Google Shopping, Microsoft, Pinterest, and Meta expand configured shipping and tax groups into nested elements and additional images into repeated elements. Local Inventory retains its store-code and quantity fields.
- Canonical presets take precedence over saved generic wrappers. Manual structure fields remain on generic feeds only. OpenAI offers delimited output only and rejects saved XML selections.
- Existing delimited defaults and column mappings remain intact. Merchants still select an `.xml` filename.

## Verification

Commands used the same external tool dependencies and Magento unit bootstrap documented in the initial XML report.

- Full PHPUnit suite: 971 tests and 2,731 assertions passed, with `--coverage-filter Model --coverage-clover /private/tmp/sho-presets-coverage.xml`. Coverage executed the real RSS serializer and image mapper; the RSS serializer covered 37 of 38 statements and the image mapper covered all 30.
- Tests parse all six configured outputs using DOM and XPath; verify namespaces, field aliases, empty documents, metadata escaping, repeated fields and nested shipping/tax; exercise the generator with saved generic wrappers; check admin field visibility and OpenAI rejection.
- The image-separator regression was run against the old mapper and failed because XML received pipe-separated image URLs. Restoring the fix makes it pass while preserving delimited behavior.
- `vendor/bin/phpcs`: no source errors or warnings. The installed standard emits deprecation notices.
- `vendor/bin/phpstan analyse --debug --memory-limit=1G --no-progress`: no errors. The baseline remains unchanged at 1,714 entries and 2,403 existing errors.
- Repository validation, Magento XML schema validation, wiki validation, Composer validation, and changed-file PHP syntax checks pass.
- Frontend/CI tests: 79 passed. Magento form-contract tests: five passed.

## Provider evidence and limits

- [Google RSS example](https://support.google.com/merchants/answer/14987622?hl=en) and [shipping structure](https://support.google.com/merchants/answer/6324484?hl=en).
- [Google Local Inventory XML example](https://support.google.com/merchants/answer/14684743?hl=en-GB).
- [Microsoft accepts Google-formatted XML](https://help.ads.microsoft.com/apex/index/3/en/60048).
- [Pinterest formats and fields](https://help.pinterest.com/en/business/article/before-you-get-started-with-catalogs).
- [TikTok supported formats and template download instructions](https://ads.tiktok.com/resources/help/article/create-manage-catalogs?lang=en). Direct confirmation against the account's downloadable XML template remains pending.
- Meta's current developer documentation returned HTTP 429 and its business help required sign-in. The implemented RSS convention still requires direct confirmation against the current provider sample.

No live catalog uploads, browser/database acceptance, deployment, or publication were performed. Local serialization tests do not establish provider ingestion acceptance. See the destination-preset scenarios in `ACCEPTANCE-TEST-PLAN.md` for those remaining checks.
