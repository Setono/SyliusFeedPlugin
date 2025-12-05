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
- Write unit tests for all new functionality (if it makes sense)
- Follow the BDD-style naming convention for test methods (e.g., `it_should_do_something_when_condition_is_met`)
- **MUST use Prophecy for mocking** - Use the `ProphecyTrait` and `$this->prophesize()` for all mocks, NOT PHPUnit's `$this->createMock()`
- **Form testing** - Use Symfony's best practices for form testing as documented at https://symfony.com/doc/current/form/unit_testing.html
  - Extend `Symfony\Component\Form\Test\TypeTestCase` for form type tests
  - Use `$this->factory->create()` to create form instances
  - Test form submission, validation, and data transformation
- Ensure tests are isolated and don't depend on external state
- Test both happy path and edge cases

## Development Commands

### Code Quality & Testing
```bash
# Run tests
composer phpunit

# Run a single test
vendor/bin/phpunit tests/Resolver/FeedExtensionResolverTest.php

# Static analysis (PHPStan at max level)
composer analyse

# Check coding standards (ECS with Sylius coding standard)
composer check-style

# Fix coding standards
composer fix-style

# Run Rector (dry-run)
vendor/bin/rector process --dry-run

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
- **Baseline**: Generate with `composer analyse -- --generate-baseline` to track improvements

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

1. `ProcessFeedsCommand` triggers `FeedProcessor::process()` which dispatches `ProcessFeed` messages for each enabled feed
2. `ProcessFeedHandler` creates batches and dispatches `GenerateBatch` messages for each channel/locale combination
3. `GenerateBatchHandler` processes items using the feed type's data provider, validates items, renders Twig templates, and writes to filesystem
4. `FinishGenerationHandler` finalizes the feed when all batches complete

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
