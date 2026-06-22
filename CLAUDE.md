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
    `bin/console sylius:fixtures:load -n --env=dev`). Build assets with `yarn build` — the test app
    uses **dart-sass** (via `@sylius-ui/frontend`), not the abandoned `node-sass`, so a clean
    `yarn install && yarn build` works on Apple Silicon. Use **Node ≥20.19** (dart-sass's floor);
    `tests/Application/.nvmrc` pins Node 22. If you see a `node-sass`/arm64 error, it's a stale
    `node_modules` — delete it and reinstall.
  - The plugin's messages must be routed to an async transport in the app (see the test app's
    `config/packages/dev/setono_sylius_feed.yaml`) so "Generate now" queues a run; process it with
    `bin/console messenger:consume main`. (The functional test suite keeps them synchronous.)
  - Check the browser console for errors as part of every verification, and prefer
    `browser_snapshot` over screenshots for assertions.

### Service Definitions
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
- **Dependency analysis** (`shipmonk/composer-dependency-analyser`, config in `composer-dependency-analyser.php`) checks that every used package is a direct dependency.

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

### Feed Processing Flow

The pipeline is a fan-out of async messages, and the *lifecycle* is driven by Symfony Workflow transition events — not by the handlers calling each other directly.

1. `ProcessFeedsCommand` (`setono:sylius-feed:process`) calls `FeedProcessor::process()`, which dispatches one `ProcessFeed` per enabled feed.
2. `ProcessFeedHandler` validates the feed type's template, applies the `process` transition, and dispatches one `GenerateFeed` per channel/locale combination.
3. `GenerateFeedHandler` asks the feed type's `DataProvider` for batches (`getBatches()`) and dispatches one `GenerateBatch` per batch.
4. `GenerateBatchHandler` resolves the batch's items, runs each through the item context, validates every context, renders the Twig `item` block, writes a **partial file** per channel/locale, then dispatches `BatchGeneratedEvent`.
5. `FinishGenerationHandler` concatenates the partials into the final feed (wrapping them with the feed start/end rendered from `@SetonoSyliusFeedPlugin/Feed/feed.txt.twig`, split on the `<!-- ITEM_BOUNDARY -->` marker), deletes the partials, and applies the `processed` transition.

**Completion detection is counter-based, not "last handler wins".** The total batch count is set on the feed when the `process` transition fires (`StartProcessingSubscriber` → `Feed::setBatches()`). On each `BatchGeneratedEvent`, `IncrementFinishedBatchesSubscriber` (priority 100) increments the counter, then `SendFinishGenerationCommandSubscriber` dispatches `FinishGeneration` only once `FeedRepository::batchesGenerated()` is true. This is what makes the flow safe under out-of-order async batch processing.

**Workflow-transition subscribers** (`workflow.setono_sylius_feed.feed.transition.*`) handle side effects so handlers stay focused:
- `process` → `StartProcessingSubscriber` resets and sets the batch count.
- `processed` → `MoveGeneratedFeedSubscriber` moves the feed from the temporary filesystem to its final (public) location.
- `errored` → `DeleteGeneratedFilesSubscriber` cleans up generated files.

**Validation/violation behavior:** each context is validated with the feed type's validation groups. A violation with severity `error` causes that item to be **skipped** (not written to the feed); other severities are recorded as `Violation`s on the feed but the item is still written. Any thrown error transitions the feed to `error`.

### Key Components

- **FeedType** (`FeedTypeInterface`): Defines a feed format. Contains data provider, templates, feed context, and item context. Register with tag `setono_sylius_feed.feed_type`.
- **DataProvider** (`DataProviderInterface`): Provides items to be included in the feed (e.g., products)
- **FeedContext/ItemContext**: Transform raw data into context for Twig templates
- **Workflow** (`FeedGraph`): States: unprocessed, processing, ready, error. Transitions: process, processed, errored

### Message Commands

All commands implement `CommandInterface` and can be routed to async transport:
- `ProcessFeed` - Start processing a feed
- `GenerateFeed` - Generate feed for a specific channel/locale
- `GenerateBatch` - Process a batch of items
- `FinishGeneration` - Finalize feed after all batches complete

### Feed Templates

Templates in `src/Resources/views/Feed/` must define an `item` block. Example structure:
```twig
{% block item %}
{# Render single feed item #}
{% endblock %}
```

### Extension Points

- Implement `FeedTypeInterface` for custom feed formats
- Use event listeners on `QueryBuilderEvent` to filter data
- Filter listeners in `EventListener/Filter/` (channel, enabled, in-stock filters)
- Subscribe to `GenerateBatchItemEvent` and `GenerateBatchViolationEvent` for item processing hooks

### Model Interfaces

Product models can implement optional interfaces for feed data:
- `BrandAwareInterface`, `GtinAwareInterface`, `MpnAwareInterface`
- `ColorAwareInterface`, `SizeAwareInterface`, `ConditionAwareInterface`
- Localized variants: `LocalizedBrandAwareInterface`, etc.

### Translations

The plugin provides multilingual support through translation files in `src/Resources/translations/`:

- **Translation Files**: Available in 10 languages (en, da, de, es, fr, it, nl, no, pl, sv)
- **Translation Domains**:
  - `messages.*` - UI labels and general translations
  - `flashes.*` - Flash message translations (success/error messages)
  - `validators.*` - Validation error messages

Key translation keys:
- `setono_sylius_feed.ui.*` - UI labels (feeds, violations, states)
- `setono_sylius_feed.form.*` - Form field labels
- `setono_sylius_feed.feed_type.*` - Feed type names
