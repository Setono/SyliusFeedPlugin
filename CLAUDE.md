# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Sylius plugin for generating product feeds (e.g., Google Shopping feeds). It processes feed items in batches using Symfony Messenger for async processing. Feeds have a workflow state machine (unprocessed -> processing -> ready/error).

## Code Standards

Follow clean code principles and SOLID design patterns when working with this codebase:
- Write clean, readable, and maintainable code
- Apply SOLID principles (Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion)
- Use meaningful variable and method names
- Keep methods and classes focused on a single responsibility
- Favor composition over inheritance
- Write code that is easy to test and extend

### Testing Requirements
- **Every feature must be tested.** A feature is not "done" until it ships with tests that exercise it. Write **unit tests for all new classes/behaviour**, and add **functional tests whenever the code crosses a Sylius/Doctrine/container/HTTP boundary** — DI/service wiring, Doctrine mappings, the workflow, value resolvers/data sources against real entities, writers' streamed output, public routes, and admin actions. Use a functional test when a unit test cannot meaningfully prove the behaviour.
- **The test suite is split into two PHPUnit suites** (see `phpunit.xml.dist`):
  - `tests/Unit/` (suite `unit`) — fast, isolated tests with no kernel/container/database; mirror the `src/` namespace structure.
  - `tests/Functional/` (suite `functional`) — boot the test-application kernel by extending `Setono\SyliusFeedPlugin\Tests\Functional\FunctionalTestCase`. May use the **database**: `dama/doctrine-test-bundle` wraps each test in a transaction that is rolled back (requires `doctrine.dbal.use_savepoints: true`), so persist freely and the DB stays clean. The schema must exist first (`doctrine:schema:create`).
  - Run one suite via `composer phpunit-unit` / `composer phpunit-functional`; `composer phpunit` runs both. The **unit** suite needs no database; the **functional** suite does — in CI it runs in the `integration-tests` job (and the coverage job), not `unit-tests`.
- Follow the BDD-style naming convention for test methods (e.g., `it_should_do_something_when_condition_is_met`)
- **MUST use Prophecy for mocking** - Use the `ProphecyTrait` and `$this->prophesize()` for all mocks, NOT PHPUnit's `$this->createMock()`
- **Form testing** - Use Symfony's best practices for form testing as documented at https://symfony.com/doc/current/form/unit_testing.html
  - Extend `Symfony\Component\Form\Test\TypeTestCase` for form type tests
  - Use `$this->factory->create()` to create form instances
  - Test form submission, validation, and data transformation
- Ensure tests are isolated and don't depend on external state
- Test both happy path and edge cases
- **Every UI feature must be verified in a real browser with Playwright (MCP).** Automated
  PHPUnit tests are not enough for admin/shop UI: after building or changing anything users
  interact with (grids, forms, menu items, admin actions, public pages), drive it end-to-end with
  the Playwright MCP tools against the running test app and confirm it actually works — pages load
  (no 500s), forms submit and persist, grid row actions run, and generated output is reachable.
  - Run the test app over HTTPS via `symfony server:start -d` in `tests/Application` (note the
    port it reports — it is **not** necessarily `:8000` if another project already holds that port).
    Log in at `/admin/login` with `sylius` / `sylius` (load fixtures first:
    `bin/console sylius:fixtures:load -n --env=dev`). Build assets with `yarn build`. The test app
    **inlines `@sylius-ui/frontend`'s build dependencies** directly in `package.json` (instead of the
    meta-package) so versions are controllable, with two deliberate pins:
    - `sass` is pinned to **`1.77.8`** (dart-sass; the latest requires Node ≥20.19, which would force
      Node 20+) so the build runs on Node 18.
    - a `resolutions` entry dedupes **`jquery` to `3.7.1`**. Without it, `jquery.dirtyforms` (dep
      `"jquery": ">=1.4.2"`) and `semantic-ui-css` each pull their own `jquery@4`, so the plugin
      attaches `.dirtyForms` to a different jQuery instance than Sylius's admin entry uses → the
      admin JS throws `dirtyForms is not a function` and **silently breaks every admin form
      interaction** (collection add/remove, etc.). One jQuery fixes it.

    `tests/Application/.nvmrc` pins **Node 18**. With Node 18 active (`nvm use`), a clean
    `yarn install && yarn build` works on Apple Silicon (`node-sass` is not used). If you see a
    `node-sass`/arm64 error, it's a stale `node_modules` — delete it and reinstall.
  - The plugin's messages must be routed to an async transport in the app (see the test app's
    `config/packages/dev/setono_sylius_feed.yaml`) so "Generate now" queues a run; process it with
    `bin/console messenger:consume main`. (The functional test suite keeps them synchronous.)
  - Check the browser console for errors as part of every verification, and prefer
    `browser_snapshot` over screenshots for assertions.

### Service Definitions

**No autoconfiguration, no autowiring — ever. This is a distributable plugin, not an
application.** A reusable Sylius/Symfony plugin must wire itself **deterministically and
explicitly** so it behaves identically regardless of how the host application is configured (an
app may have autowiring/autoconfiguration off, or different conventions, entirely). Therefore the
plugin's own services rely **exclusively on explicit mechanisms**: service **tags**, hand-written
**service definitions** (every constructor argument wired with `<argument>`), **compiler passes**,
and **registries built from `tagged_iterator`**. Never lean on `autowire`, `autoconfigure`,
attribute-based auto-registration, or convention/directory-based discovery for anything the plugin
ships. The specific rules below spell out how this applies.

- **Use the FQCN as the service id.** Register a service under its fully-qualified class name
  (e.g. `<service id="Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistry">`), not a custom dotted
  id like `setono_sylius_feed.registry.feed_type`.
- **Every single-implementation service gets an interface + an FQCN alias.** Define an interface,
  implement it, and register `<service id="…\FooInterface" alias="…\Foo"/>`; consumers depend on
  the interface, never the concrete class. The only services without an alias are the tagged
  extension-point services (collected through a registry) and framework entry points (console
  commands, message handlers, event subscribers, controllers).
- **Do NOT use `autowire` or `autoconfigure` on the plugin's own services.** This applies to *all*
  services in the plugin. Wire every constructor argument explicitly with `<argument>`, define each
  tagged service explicitly (no `<prototype>`), and apply every tag explicitly with `<tag>` —
  `messenger.message_handler`, `kernel.event_subscriber`, `console.command`,
  `controller.service_arguments`, and the `setono_sylius_feed.*` registry tags. Do not rely on
  attributes for *registration*: message handlers use a `messenger.message_handler` tag (not
  `#[AsMessageHandler]`), and `#[AsCommand]` may remain only as name/description metadata next to an
  explicit `console.command` tag.
- Event subscribers live in `src/EventSubscriber` and are registered in their own
  `services/event_subscriber.xml`.
- The extension still calls `registerForAutoconfiguration()` for each extension-point interface —
  but **only as a developer-experience aid for applications** that add their own implementations.
  The plugin must never depend on it for its own wiring.

### Doctrine Access
- **Never inject `EntityManagerInterface` (or a repository) directly into a service.** Inject
  `Doctrine\Persistence\ManagerRegistry` and use the `Setono\Doctrine\ORMTrait` (from
  `setono/doctrine-orm-trait`): add `use ORMTrait;`, assign `$this->managerRegistry = $managerRegistry;`
  in the constructor, then call `$this->getManager($class)` / `$this->getRepository($class)`. The
  trait resolves the correct manager per entity class and transparently re-opens a closed manager —
  important for long-running/batch feed generation where one failure would otherwise close the EM
  for the rest of the run. Test classes may still fetch the manager from the container directly.

## Development Commands

### Code Quality & Testing
```bash
# Run all tests (unit + functional)
composer phpunit

# Run only one suite
composer phpunit-unit
composer phpunit-functional

# Run a single test file
vendor/bin/phpunit tests/Unit/Context/FeedContextTest.php

# Static analysis (PHPStan at max level)
composer analyse

# Check coding standards (ECS with Sylius coding standard)
composer check-style

# Fix coding standards
composer fix-style

# Run Rector (dry-run)
vendor/bin/rector process --dry-run

# Mutation testing (currently non-blocking in CI — see CI Gates below)
vendor/bin/infection

# Lint Symfony container (requires test application)
(cd tests/Application && bin/console lint:container)

# Lint YAML/Twig files
(cd tests/Application && bin/console lint:yaml ../../src/Resources)
(cd tests/Application && bin/console lint:twig ../../src/Resources)
```

### Static Analysis

#### PHPStan Configuration
PHPStan is configured in `phpstan.neon` with:
- **Analysis Level**: max (strictest)
- **Extensions**: Auto-loaded via `phpstan/extension-installer`
  - `phpstan/phpstan-symfony` - Symfony framework integration
  - `phpstan/phpstan-doctrine` - Doctrine ORM integration
  - `phpstan/phpstan-phpunit` - PHPUnit test integration
  - `jangregor/phpstan-prophecy` - Prophecy mocking integration
- **Symfony Integration**: Uses console application loader (`tests/PHPStan/console_application.php`)
- **Doctrine Integration**: Uses object manager loader (`tests/PHPStan/object_manager.php`)
- **Exclusions**: Test application directory and Configuration.php
- **No baseline**: the rewrite keeps zero PHPStan errors, so there is intentionally no `phpstan-baseline.neon`. Fix issues at the source rather than reintroducing a baseline.

### CI Gates & Compatibility

These are enforced by `.github/workflows/build.yaml` and will fail the build if violated:

- **Mutation testing is currently non-blocking.** `infection.json.dist` still sets `minMsi`/`minCoveredMsi` to `100.00`, but the `mutation-tests` CI job runs with `continue-on-error: true` during the rewrite, so it does not fail the build. Mutation coverage is therefore not enforced right now — rely on the "every feature must be tested" rule above instead.
- **PHP 8.1 is the floor**: the package supports PHP `>=8.1`, and CI runs against 8.1/8.2/8.3 with Symfony `~6.4`. Coding-standards run on **8.1** and Rector targets `LevelSetList::UP_TO_PHP_81`, so do **not** use syntax/features newer than 8.1.
- **`lowest` and `highest` dependencies** are both tested — avoid relying on behavior only present in newer versions of a `^`-constrained dependency.
- **`composer normalize --dry-run`** must pass — keep `composer.json` normalized (run `composer normalize`).
- **Dependency analysis** (`shipmonk/composer-dependency-analyser`, config in `composer-dependency-analyser.php`) must pass: every symbol used in `src/` maps to a declared `require`, and every `require` is used. **Gotcha:** the job runs `composer config --unset require-dev` and resolves **`require`-only**, so it never sees the `sylius/sylius` monorepo (a require-dev dependency) — it pulls the *split* component packages (`sylius/core`, `sylius/order`, …) instead. Two consequences: (a) `require` must directly declare every Sylius component the code uses (the split packages don't `replace` each other); (b) the split `sylius/core` caps `league/flysystem` at `^2.4`, so `require` keeps `league/flysystem: ^2.4 || ^3.0` and the 3.x floor + `league/flysystem-local` live in **require-dev** (the test app and the full install still resolve flysystem 3.15 + flysystem-local 3.15, matched so `ChecksumProvider` is present). Reproduce the exact job locally with: `cp composer.json /tmp/bak && composer config --unset require-dev && composer require --dev --no-install shipmonk/composer-dependency-analyser && composer update --prefer-lowest --ignore-platform-req=php+ && vendor/bin/composer-dependency-analyser` (then `cp /tmp/bak composer.json && composer update`).

### Test Application
The plugin includes a test Symfony application in `tests/Application/` for development and testing:
- Navigate to `tests/Application/` directory
- Run `yarn install && yarn build` to build assets
- Use standard Symfony commands for the test app
- **Sylius Backend Credentials**: Username: `sylius`, Password: `sylius`

Database setup:
```bash
(cd tests/Application && bin/console doctrine:database:create)
(cd tests/Application && bin/console doctrine:schema:create)
```

## Bash Tools Recommendations

Use the right tool for the right job when executing bash commands:

- **Finding FILES?** → Use `fd` (fast file finder)
- **Finding TEXT/strings?** → Use `rg` (ripgrep for text search)
- **Finding CODE STRUCTURE?** → Use `ast-grep` (syntax-aware code search)
- **SELECTING from multiple results?** → Pipe to `fzf` (interactive fuzzy finder)
- **Interacting with JSON?** → Use `jq` (JSON processor)
- **Interacting with YAML or XML?** → Use `yq` (YAML/XML processor)

Examples:
- `fd "*.php" | fzf` - Find PHP files and interactively select one
- `rg "function.*validate" | fzf` - Search for validation functions and select
- `ast-grep --lang php -p 'class $name extends $parent'` - Find class inheritance patterns

## Architecture

The plugin is a **resource-agnostic transformation engine**: *iterate any Sylius resource → map each
entity to named output fields → transform/filter → validate → stream to a format → gate → publish/
deliver*. "Google Shopping product feed" is just the richest `FeedType` plugged into that engine. The
authoritative design lives in `.notes/sylius-feed-plugin-spec.md` (gitignored).

### Feed Processing Flow

Generation is an async Messenger fan-out; the *lifecycle* is driven by Symfony Workflow transitions,
not by handlers calling each other.

1. `ProcessFeedCommand` (`setono:feed:process [--feed=CODE] [--all]`) dispatches one `ProcessFeed` per
   enabled feed.
2. `ProcessFeedHandler` applies the `process` transition and, via `ContextFactory`, fans out one
   `GenerateFeedContext` per **context** — the cartesian product of the feed's scope dimensions
   (channel × locale × currency) — recording the expected context count on the feed.
3. `GenerateFeedContextHandler` generates one context. Small feeds run **inline**
   (`FeedGenerator::generate()`); a large single-source, non-split, non-gzip feed **fans out**:
   `ChunkPartitioner` splits the source into id-ranges, one `GenerateFeedChunk` per range renders a
   **body-only partial**, a per-context `FeedChunk` barrier table tracks completion with an atomic
   idempotent counter, and the last chunk dispatches `FinalizeFeedContext`, which concatenates the
   ordered partials into the context file (**byte-identical** to the inline path, and resumable after
   a mid-run chunk failure).
4. Either path finishes through the shared `FeedContextFinalizer`: record a `FeedContextResult`
   (item/excluded counts, bytes, per-item exclusion reasons), run the **publish gate**, increment the
   completed-context counter, and on the last context apply the `complete` transition.
5. On `complete`, `MoveGeneratedFeedSubscriber` does the **per-context gated promotion** staging→
   canonical (only `published` contexts move; `blocked` ones keep their prior live file), then runs
   **delivery** per published context to any matching `DeliveryTarget`s.

**Per-item pipeline** (`FeedGenerator` and `PreviewService` share it): per source entity — bind the
on-demand source resolver (`SourceResolverBinder`) → `pre`-filters (`FilterEvaluator`) → apply field
mappings (`FieldMappingEvaluator`, resolved by `MappingResolver`: `FeedField` rows first, else the
`MappingPreset`) → `post`-filters → `FeedItemBuiltEvent` → validation (`FeedItemValidator`: typed
Symfony constraints when the format declares validation groups, else the required-field fallback) →
write. Excluded items are counted and reported with a reason; they never abort the run.

**Publish gate (spec §6.6):** generating and *publishing* are separate acts. Per context, before the
canonical swap and delivery, the candidate `FeedContextResult` is compared against the last-good
baseline via the feed's `publishConfig` guardrails (`min_items`, `max_drop_pct`, `non_empty`,
`min_bytes`, `max_exclusion_pct`, `max_growth_pct`). A tripped `block` guardrail sets
`publishState = blocked`, keeps the live file, and dispatches `FeedPublishBlockedEvent`; a `warn`
publishes and still dispatches. A "publish anyway" admin action promotes a retained blocked candidate.

### Workflow (`FeedGraph`, graph `setono_sylius_feed_feed`)

States `ready → processing → completed | failed`. Transitions: `process` (ready→processing),
`complete` (processing→completed), `fail` (processing→failed), `reset` (completed|failed→ready).
Marking store: `Feed::state`.

### Extension points (tagged registries — no autowiring)

Each is an interface collected into an FQCN registry via a tag (the extension calls
`registerForAutoconfiguration` only as a DX aid for apps that add their own):

- `FeedTypeInterface` — `setono_sylius_feed.feed_type` (product_variant, product, order, customer, taxon, product_review, promotion)
- `ValueResolverInterface` — `setono_sylius_feed.value_resolver`
- `TransformationInterface` — `setono_sylius_feed.transformation`
- `OperatorInterface` — `setono_sylius_feed.operator` (shared by filters, `FeedField.condition`, and the `conditional` transform)
- `MappingPresetInterface` — `setono_sylius_feed.mapping_preset` (Google Shopping, Meta, Bing, Pinterest, TikTok, Partner-ads)
- `FormatInterface` — `setono_sylius_feed.format` (google_rss, csv, generic_xml, partner_ads)
- `FeedWriterInterface` — `setono_sylius_feed.writer` (XmlWriter, CsvWriter)
- `SplitManifestInterface` — `setono_sylius_feed.split_manifest` (none, supplemental)
- `LookupSourceInterface` — `setono_sylius_feed.lookup_source` (csv, url) — powers `LookupTable` enrichment referenced as `lookup:{code}:{column}`
- `DeliveryTransportInterface` — `setono_sylius_feed.delivery_transport` (local always available; ftp/sftp/s3 gated on `class_exists`, their Flysystem adapters are optional `suggest` installs)
- `GuardrailInterface` — `setono_sylius_feed.guardrail`

### Output: field mappings + writers (not Twig templates)

A feed's output is defined by its sources' `FeedField` rows (output field, source picker,
transformation sub-collection, condition), evaluated into a `FeedItem` bag and streamed by the
format's `FeedWriterInterface`. Sandboxed Twig and expression scripting exist as *transformations*
(`FeedTemplateSecurityPolicy`, `ScriptingVariables`), not as a per-feed render template.

### Message Commands

All implement `CommandInterface` and can be routed to an async transport: `ProcessFeed`,
`GenerateFeedContext`, `GenerateFeedChunk`, `FinalizeFeedContext`.

### Diagnostics

- **Preview** (`PreviewService`; admin `/feeds/{id}/preview` + `--preview[=N]`): runs the full pipeline
  over the first N sampled items **without writing** — a funnel (source → after-pre → after-mapping/
  validation → after-post → included), included/excluded samples annotated with reasons, and a
  single-item tester.
- **Audit** (`FeedAuditService`; `--audit`): per-field fill rates, soft warnings (title > 150,
  HTML-in-description, price = 0) and value distributions — advisory only, excludes nothing.

### Delivery, splitting, gzip

Canonical Flysystem storage is always written and served at the public route. On top, a feed's
`DeliveryTarget`s push the whole file set (parts + manifest) to the contexts they match
(`{channel?,locale?,currency?}` matcher + a `pathTemplate`), best-effort and isolated. A context past
its format's split limit is split into numbered parts plus a `SplitManifest` (`supplemental` for
google_rss); `formatConfig.gzip` gzips each part.

### Enrichment (`LookupTable`)

A separate Sylius resource (admin `/admin/lookup-tables`) importing rows from a `csv`/`url`
`LookupSource` with keep-last-good refresh; feed mappings reference it by code as
`lookup:{code}:{column}` (e.g. GTIN backfill).

### Translations

Translation files live in `src/Resources/translations/`. The rewrite ships **English only** so far
(`messages.en.yaml`, `flashes.en.yaml`, `validators.en.yaml`) — other locales are welcome additions.

- **Translation Domains**:
  - `messages.*` - UI labels and general translations
  - `flashes.*` - Flash message translations (success/error messages)
  - `validators.*` - Validation error messages

Key translation keys:
- `setono_sylius_feed.ui.*` - UI labels (feeds, preview/funnel/audit, states)
- `setono_sylius_feed.form.*` - Form field labels (feed, feed_source, feed_field, feed_filter, delivery_target, lookup_table)
- `setono_sylius_feed.feed_type.*` - Feed type names
- `setono_sylius_feed.mapping_preset.*` / `setono_sylius_feed.value_resolver.*` - preset + resolver labels
