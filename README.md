# Sylius Feed Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]

A **resource-agnostic feed-generation engine** for [Sylius](https://sylius.com). It iterates any
Sylius resource — product variants, products, orders, customers, taxons, product reviews, promotions
— maps each entity to named output fields, transforms and filters them, validates against a channel
spec, and streams the result to a format (Google RSS, CSV, generic XML, Partner-ads). Google Shopping
is the richest built-in target, but the same engine ships presets for Meta, Bing, Pinterest, TikTok
and Partner-ads, plus fully custom feeds.

> **The mental model:** *iterate a resource → map each entity to output fields → transform & filter →
> validate → stream to a format → gate → publish & deliver.* Everything in this plugin is one of those
> stages, and every stage is an extension point.

---

## Table of contents

- [Features](#features)
- [How it works](#how-it-works)
- [Installation](#installation)
- [Usage](#usage)
  - [Create a feed](#create-a-feed)
  - [Generate a feed](#generate-a-feed)
  - [Scheduling with cron](#scheduling-with-cron)
  - [The public feed URL](#the-public-feed-url)
- [Core concepts](#core-concepts)
- [The mapping language](#the-mapping-language)
- [Configuration](#configuration)
  - [Storage](#storage)
  - [Split & gzip (`formatConfig`)](#split--gzip-formatconfig)
  - [The publish gate (`publishConfig`)](#the-publish-gate-publishconfig)
  - [Delivery targets](#delivery-targets)
  - [Lookup tables](#lookup-tables)
- [Diagnostics: preview & audit](#diagnostics-preview--audit)
- [Extending & customizing](#extending--customizing)
  - [The extension-point pattern](#the-extension-point-pattern)
  - [Add a value resolver](#add-a-value-resolver)
  - [Add a transformation](#add-a-transformation)
  - [Add an operator](#add-an-operator)
  - [Add a format](#add-a-format)
  - [Add a channel preset](#add-a-channel-preset-mappingpreset)
  - [Add a feed type (a new source resource)](#add-a-feed-type-a-new-source-resource)
  - [Add a lookup source](#add-a-lookup-source)
  - [Add a delivery transport](#add-a-delivery-transport)
  - [Add a publish guardrail](#add-a-publish-guardrail)
  - [Hook into events](#hook-into-events)
  - [Override a model or service](#override-a-model-or-service)
- [Development & testing](#development--testing)
- [License](#license)

---

## Features

**Formats & channels** — Google Shopping (RSS), CSV, generic XML and Partner-ads out of the box, with
ready-made mapping presets for **Google Shopping, Meta, Bing, Pinterest, TikTok and Partner-ads**. A
single catalog can produce a valid Meta CSV *and* a valid Partner-ads XML from two sources in one feed.

**Any resource** — seven built-in feed types (`product_variant`, `product`, `order`, `customer`,
`taxon`, `product_review`, `promotion`) prove the engine is resource-agnostic; adding your own is a
class + a tag.

**Rich mapping** — a target-first admin flow seeds the format and field mappings from a known target;
each field is a source picker + an ordered chain of **transformations** + an optional emit
**condition**, all editable in the admin. Sandboxed Twig and Symfony ExpressionLanguage are available
as transformations for the rare cases the built-ins don't cover.

**Enrichment** — `LookupTable` resources import rows from a CSV upload or a URL (e.g. a published
Google Sheet) with keep-last-good refresh; reference them from a mapping as `lookup:{code}:{column}`
to, say, backfill GTINs.

**Scope fan-out** — feeds fan out over **channel × locale × currency**, so one feed definition yields
a correct file per storefront context.

**Diagnostics** — a dry-run **preview** shows the include funnel and a sample of included/excluded
items (with the exact rule that dropped each one) *without writing a file*; a **feed audit** reports
per-attribute fill rates and soft warnings so you can fix a feed before disapprovals happen.

**Safety** — a **publish gate** compares each freshly generated context against the last good one and
refuses to replace a healthy feed with a broken upstream day (e.g. a 50k → 5k collapse), keeping the
live file and firing an event; a one-click "publish anyway" overrides it.

**Scale** — optional gzip, file splitting with a supplemental manifest, and multi-chunk fan-out for
large catalogs (byte-identical to the single-pass output, and resumable after a mid-run failure).

**Delivery** — every feed is always served from canonical storage at a public URL; on top of that a
feed can push each context's whole file set to one or more **FTP/SFTP/S3** targets, matched by
`{channel, locale, currency}` and named by a path template.

---

## How it works

Generation is an asynchronous fan-out driven by Symfony Messenger; the feed's *lifecycle* is driven by
a Symfony Workflow (`ready → processing → completed | failed`).

```
setono:feed:process
        │  dispatch ProcessFeed (per enabled feed)
        ▼
ProcessFeedHandler ── apply "process" ── ContextFactory fans out one
        │                                 GenerateFeedContext per context
        │                                 (channel × locale × currency)
        ▼
GenerateFeedContextHandler
        │   small feed → generate inline
        │   large feed → partition into id-ranges → GenerateFeedChunk (body-only
        │                partials) → barrier → FinalizeFeedContext (concat)
        ▼
FeedContextFinalizer ── record FeedContextResult ── run the PUBLISH GATE
        │                                            ── on last context: apply "complete"
        ▼
MoveGeneratedFeedSubscriber (on "complete")
        │   promote only PUBLISHED contexts staging → canonical
        │   (blocked contexts keep their prior live file)
        ▼
   deliver each published context to matching DeliveryTargets
```

For every source entity the **per-item pipeline** runs:

```
bind source resolver → pre-filters → apply field mappings → post-filters
→ FeedItemBuiltEvent → validate → write
```

Excluded items are counted and reported with a reason — they never abort the run.

---

## Installation

### Step 1: Download the plugin

```bash
composer require setono/sylius-feed-plugin
```

### Step 2: Enable the plugin

Add the bundles to `config/bundles.php`:

```php
<?php

return [
    // ...
    League\FlysystemBundle\FlysystemBundle::class => ['all' => true],
    Setono\SyliusFeedPlugin\SetonoSyliusFeedPlugin::class => ['all' => true],

    // The plugin must be registered BEFORE the grid bundle
    Sylius\Bundle\GridBundle\SyliusGridBundle::class => ['all' => true],
    // ...
];
```

> **NOTE** the plugin must come before `SyliusGridBundle`, otherwise you'll see
> `You have requested a non-existent parameter "setono_sylius_feed.model.feed.class"`.

### Step 3: Import routing

```yaml
# config/routes/setono_sylius_feed.yaml
setono_sylius_feed:
    resource: "@SetonoSyliusFeedPlugin/Resources/config/routes.yaml"

setono_sylius_feed_admin:
    resource: "@SetonoSyliusFeedPlugin/Resources/config/routes/admin.yaml"
```

`routes.yaml` exposes the public feed at `/feed/{code}/{filename}`; `routes/admin.yaml` adds the admin
screens under `/admin`.

### Step 4: Import configuration

```yaml
# config/packages/setono_sylius_feed.yaml
imports:
    - { resource: "@SetonoSyliusFeedPlugin/Resources/config/app/config.yaml" }
```

### Step 5: Update the database schema

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

### Step 6: Route messages to an async transport (recommended)

Every message implements `Setono\SyliusFeedPlugin\Message\Command\CommandInterface`, so you can route
the whole family in one line:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            'Setono\SyliusFeedPlugin\Message\Command\CommandInterface': async
```

Run a worker to consume it: `bin/console messenger:consume async`. Without an async transport the
commands run synchronously in the process that dispatches them.

---

## Usage

### Create a feed

Go to **`/admin/feeds/new`**. The create flow is **target-first**: pick a target (a channel preset like
*Google Shopping*, or *Custom*) and the feed's format and first source's field mappings are seeded for
you. Then:

- choose the **channels** the feed applies to and fill in the localized **name/slug**;
- refine the **mapping** — each row is an output field, a source, an optional transformation chain and
  an optional condition. Rows the preset couldn't map automatically are flagged **"requires input"**
  and block enabling the feed until you resolve them;
- optionally add **filters**, **delivery targets** and format/publish config;
- **enable** the feed.

Multi-source feeds add more sources (each with its own feed type, mapping and filters); the union of
their fields becomes the output.

### Generate a feed

From the admin grid use the **Generate now** row action, or from the console:

```bash
# every enabled feed (the cron form)
bin/console setono:feed:process --all

# a single feed
bin/console setono:feed:process --feed=google

# dry-run: print the include funnel + sample rows without writing anything
bin/console setono:feed:process --feed=google --preview

# ...and per-attribute fill rates + soft warnings
bin/console setono:feed:process --feed=google --preview --audit
```

If you routed messages to an async transport, `process` only *dispatches* the work — make sure a
worker is consuming the transport.

### Scheduling with cron

There is no bundled scheduler — run the process command from your crontab. To regenerate every enabled
feed hourly:

```cron
0 * * * * cd /path/to/project && bin/console setono:feed:process --all >> var/log/feed.log 2>&1
```

### The public feed URL

Every generated context is written to canonical storage and served at
`/feed/{code}/{filename}` (gzip-aware), regardless of any delivery targets. The per-context results
table in the admin links to each file.

---

## Core concepts

| Concept | What it is |
| --- | --- |
| **Feed** | The configured resource: a code, channels, a `format`, one or more **sources**, and optional format/publish/delivery config. Has a workflow state (`ready → processing → completed \| failed`). |
| **FeedType** | Binds a source resource (e.g. `product_variant`) to its scope dimensions and the source fields available for mapping. |
| **Source** (`FeedSource`) | One feed type + its **field mappings** + its **filters**. A feed can have several. |
| **FeedField** | One output field: an output name, a source (`field` / `literal` / `expression` / `twig`), an ordered transformation chain, and an optional emit condition. |
| **FeedContext** | The `(channel, locale, currency)` a feed is generated for. Feeds fan out over the cartesian product of their feed types' **scope dimensions**. |
| **FeedItem** | The output "bag" for one entity — an ordered map of output field → value that the writer serializes. |
| **Format** | A named structural preset (`google_rss`, `csv`, …) over a **writer** family (`xml` / `csv`). |
| **MappingPreset** | A named target (Google Shopping, Meta, …) that seeds a feed's format + default field mappings. |
| **FeedContextResult** | The recorded outcome of generating one context: item/excluded counts, bytes, per-item exclusion reasons, publish state and delivery results. |

---

## The mapping language

A **source reference** in a field mapping or a filter resolves, in order, to:

1. a **quoted literal** — `'out_of_stock'`;
2. an **earlier output field** — the value already written for another output key;
3. a **source field** — a value resolver's name (`title`, `channel_price`, …), driven by the source's
   feed type;
4. a **lookup** — `lookup:{code}:{column}` reads from a [lookup table](#lookup-tables).

Field types (`FieldType`) are `string`, `integer`, `decimal`, `money`, `date`, `bool`, `url`, `image`,
`collection`.

### Transformations

Chained per field, each `{type, params}`:

| Type | Purpose |
| --- | --- |
| `concat` | join several references with a separator |
| `find_replace` / `regex_replace` | literal / regex substitution |
| `value_map` | map known values to replacements (with a default) |
| `prefix` / `suffix` | wrap the value |
| `change_case` | upper / lower / title case |
| `truncate` | cut to a max length (e.g. title ≤ 150) |
| `strip_tags` | remove HTML |
| `default_if_empty` | fall back when the value is empty |
| `number_format` / `money_format` / `date_format` | format numbers, prices and dates |
| `literal` | a constant value |
| `conditional` | pick a value based on an [operator](#operators) test |
| `expression` | a sandboxed Symfony ExpressionLanguage expression |
| `twig` | a sandboxed Twig template |

### Operators

One vocabulary powers filters, field emit-conditions and the `conditional` transform:

`equals`, `not_equals`, `contains`, `starts_with`, `ends_with`, `matches`, `in`, `not_in`, `gt`,
`gte`, `lt`, `lte`, `between`, `empty`, `not_empty`, `true`, `false`.

### Filters

A filter is `{field, operator, value, action, stage}`. **action** is `include` (keep only matching) or
`exclude` (drop matching); **stage** is `pre` (runs on the raw source value, before mapping) or `post`
(runs on the mapped output value). Example — drop out-of-stock variants:
`{field: availability, operator: equals, value: 'out_of_stock', action: exclude, stage: post}`.

---

## Configuration

### Storage

Storage is [Flysystem](https://github.com/thephpleague/flysystem-bundle). Point the plugin at your own
filesystem services if you don't want the defaults:

```yaml
# config/packages/setono_sylius_feed.yaml
setono_sylius_feed:
    storage:
        feed: 'my.feed_filesystem'       # canonical, publicly served
        feed_tmp: 'my.feed_tmp_filesystem' # staging (candidates + partials)
```

### Split & gzip (`formatConfig`)

Per-feed overrides stored on the feed's `formatConfig`:

```jsonc
{
  "gzip": true,                 // gzip each written part (.gz extension)
  "split": { "maxItems": 50000 } // split a context past this many items (or "maxBytes")
}
```

Google RSS splits into a primary + supplemental set; other formats emit self-describing numbered parts.

### The publish gate (`publishConfig`)

Guardrails compare the candidate against the last successfully published result for the same context.
A tripped `block` guardrail keeps the live file, records the reason, and fires `FeedPublishBlockedEvent`;
`warn` publishes but still fires the event. An empty guardrail list = always publish.

```jsonc
{
  "guardrails": [
    { "type": "non_empty", "severity": "block" },
    { "type": "max_drop_pct", "params": { "pct": 40 }, "severity": "block" },
    { "type": "min_items", "params": { "min": 10 }, "severity": "block" },
    { "type": "max_growth_pct", "params": { "pct": 300 }, "severity": "warn" }
  ]
}
```

Available types: `min_items`, `max_drop_pct`, `non_empty`, `min_bytes`, `max_exclusion_pct`,
`max_growth_pct`. A blocked context surfaces in the admin results table with a **"publish anyway"**
action.

### Delivery targets

A feed can declare zero or more delivery targets. Delivery runs per context at finalize, after the
canonical file is stored and only if the context passed the publish gate; failures are recorded on the
context result and never fail the feed. The `local` transport is always available; `ftp`, `sftp` and
`s3` require the matching Flysystem adapter:

```bash
composer require league/flysystem-ftp        # ftp
composer require league/flysystem-sftp-v3     # sftp
composer require league/flysystem-aws-s3-v3   # s3
```

Each target has a `transport`, its `transportConfig` (host/credentials/root — use env placeholders,
never plaintext), a `pathTemplate` with `{channel} {locale} {currency} {contextKey} {ext} {part}`
placeholders, and a `{channel?, locale?, currency?}` matcher (an omitted dimension is a wildcard):

```
US channel → SFTP  "{locale}/google-{currency}.xml"
DE channel → FTP   "produkter-{locale}.xml"
EUR (any)  → S3    "{contextKey}.xml"     # cross-cuts both
```

### Lookup tables

A separate resource at **`/admin/lookup-tables`**. Create one with a `code`, a source (`csv` upload or
`url`), a `keyColumn` and a `joinField`, then use the **Refresh** row action to (re)import (it keeps
the last good rows if a refresh fails). Reference it from a mapping as `lookup:{code}:{column}`.

---

## Diagnostics: preview & audit

Open **`/admin/feeds/{id}/preview`** (or pass `--preview` on the console). Without writing anything, the
preview runs the real pipeline over the first N sampled items and shows:

- a **funnel** — source → after pre-filters → after mapping/validation → after post-filters → included;
- a sample of **included** items with their final mapped output;
- a sample of **excluded** items, each annotated with *why* (which filter + stage, or which missing
  required field);
- the **feed audit** — per-attribute fill rates (e.g. "32% missing `g:gtin`"), soft warnings (titles >
  150 chars, HTML left in descriptions, price = 0) and value distributions.

---

## Extending & customizing

This is a distributable plugin, so it wires itself **explicitly** — no autowiring or attribute-based
auto-registration of its own services. Every extension point below is the same shape.

### The extension-point pattern

1. Implement the interface.
2. Register the class as a service **tagged** with the extension point's tag.

In a standard Symfony application (autoconfiguration on) the bundle already calls
`registerForAutoconfiguration()` for each interface, so a service with `autoconfigure: true` is tagged
automatically. If you keep autoconfiguration off, add the tag explicitly. Both are shown below.

| Extend | Interface | Tag |
| --- | --- | --- |
| a source field | `ValueResolver\ValueResolverInterface` | `setono_sylius_feed.value_resolver` |
| a value operation | `Transformation\TransformationInterface` | `setono_sylius_feed.transformation` |
| a condition predicate | `Operator\OperatorInterface` | `setono_sylius_feed.operator` |
| an output format | `Format\FormatInterface` | `setono_sylius_feed.format` |
| an output writer family | `Writer\FeedWriterInterface` | `setono_sylius_feed.writer` |
| a channel target | `MappingPreset\MappingPresetInterface` | `setono_sylius_feed.mapping_preset` |
| a source resource | `FeedType\FeedTypeInterface` | `setono_sylius_feed.feed_type` |
| a lookup source | `Lookup\LookupSourceInterface` | `setono_sylius_feed.lookup_source` |
| a delivery transport | `Delivery\DeliveryTransportInterface` | `setono_sylius_feed.delivery_transport` |
| a publish guardrail | `Publish\GuardrailInterface` | `setono_sylius_feed.guardrail` |
| a split manifest | `Writer\SplitManifestInterface` | `setono_sylius_feed.split_manifest` |

### Add a value resolver

A value resolver produces one named source field for a given resource in a given context. Example — a
`shipping_label` field for product variants:

```php
<?php

declare(strict_types=1);

namespace App\Feed\ValueResolver;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class ShippingLabelResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'shipping_label'; // the name shown in the mapping's source picker
    }

    public function getLabel(): string
    {
        return 'app.feed.value_resolver.shipping_label'; // a translation key
    }

    public function getType(): FieldType
    {
        return FieldType::STRING;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, ProductVariantInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        if (!$entity instanceof ProductVariantInterface) {
            return null;
        }

        return $entity->getShippingCategory()?->getCode();
    }
}
```

```yaml
# config/services.yaml
services:
    App\Feed\ValueResolver\ShippingLabelResolver:
        autoconfigure: true   # tags it via registerForAutoconfiguration()
        # ...or, with autoconfiguration off:
        # tags: [ 'setono_sylius_feed.value_resolver' ]
```

`getName()` becomes selectable in the mapping editor for any feed type whose resource the resolver
`supports()`.

### Add a transformation

```php
final class SlugifyTransformation implements \Setono\SyliusFeedPlugin\Transformation\TransformationInterface
{
    public function getType(): string
    {
        return 'slugify';
    }

    /** @param array<string, mixed> $params */
    public function apply(mixed $value, array $params, \Setono\SyliusFeedPlugin\Item\FeedItem $item): mixed
    {
        return is_string($value) ? (new \Cocur\Slugify\Slugify())->slugify($value) : $value;
    }
}
```

Tag it `setono_sylius_feed.transformation`; `getType()` becomes an available transformation in a field's
chain. `$item` gives you the whole in-progress bag (`$item->get('other_field')`) if a transform needs
sibling values.

### Add an operator

```php
final class DivisibleByOperator implements \Setono\SyliusFeedPlugin\Operator\OperatorInterface
{
    public function getName(): string
    {
        return 'divisible_by';
    }

    /** @param array<string, mixed> $params */
    public function matches(mixed $value, array $params): bool
    {
        return is_numeric($value) && is_numeric($params['value'] ?? null) && ((int) $value) % ((int) $params['value']) === 0;
    }
}
```

Tag it `setono_sylius_feed.operator`; it's immediately usable in filters, field conditions and the
`conditional` transform.

### Add a format

A format is a preset over the `xml` or `csv` writer family. Implement `FormatInterface`
(`getCode`, `getWriter`, `getConfig`, `getRequiredFields`, `getItemValidationGroups`,
`getSplitManifest`, `getSplitLimit`) and return the structural `WriterConfigInterface` for your writer.
Look at `src/Format/GoogleRssFormat.php` and `src/Format/CsvFormat.php` as templates, then tag it
`setono_sylius_feed.format`. Only implement a new **writer** (`FeedWriterInterface`, tag
`setono_sylius_feed.writer`) if you need a family beyond XML/CSV (e.g. JSON).

### Add a channel preset (`MappingPreset`)

A preset seeds a feed's format and default `FeedField` mappings from a target. Implement
`MappingPresetInterface` (`getCode`, `getLabel`, `supports(feedType)`, `getFormat`, `getMapping`) and
build the mapping with the `FieldMapping` builders; see `src/MappingPreset/MetaMappingPreset.php`. Tag
it `setono_sylius_feed.mapping_preset` and it appears in the target picker.

### Add a feed type (a new source resource)

To feed from a resource the plugin doesn't ship, implement `FeedTypeInterface` (`getCode`, `getLabel`,
`getDataSource`, `getScopeDimensions`, `getAvailableFields`, `createItem`) plus a `DataSourceInterface`
that streams the resource (extend the Doctrine data source pattern in `src/DataSource/` — it iterates
with `setono/doctrine-orm-batcher` and clears the entity manager per batch so a 50k feed stays within
memory), plus the value resolvers for its fields. See `src/FeedType/OrderFeedType.php` +
`src/DataSource/OrderDataSource.php` for a compact, real example. Tag the feed type
`setono_sylius_feed.feed_type`.

### Add a lookup source

Beyond `csv` and `url` — e.g. pull enrichment rows from an API:

```php
final class ApiLookupSource implements \Setono\SyliusFeedPlugin\Lookup\LookupSourceInterface
{
    public function getType(): string
    {
        return 'api';
    }

    /** @param array<string, mixed> $sourceConfig @return iterable<array<string, scalar|null>> */
    public function fetch(array $sourceConfig): iterable
    {
        // yield one associative row per record
    }
}
```

Tag it `setono_sylius_feed.lookup_source`.

### Add a delivery transport

Any Flysystem adapter works — implement `DeliveryTransportInterface` and build a `FilesystemOperator`
from the target's `transportConfig`:

```php
final class GcsDeliveryTransport implements \Setono\SyliusFeedPlugin\Delivery\DeliveryTransportInterface
{
    public function getType(): string
    {
        return 'gcs';
    }

    /** @param array<string, mixed> $transportConfig */
    public function createFilesystem(array $transportConfig): \League\Flysystem\FilesystemOperator
    {
        // return new \League\Flysystem\Filesystem(new GcsAdapter(...));
    }
}
```

Tag it `setono_sylius_feed.delivery_transport`; `getType()` becomes selectable on a delivery target.

### Add a publish guardrail

```php
final class MaxTitleLengthGuardrail implements \Setono\SyliusFeedPlugin\Publish\GuardrailInterface
{
    public function getType(): string
    {
        return 'max_avg_title_length';
    }

    public function evaluate(
        \Setono\SyliusFeedPlugin\Model\FeedContextResultInterface $candidate,
        ?\Setono\SyliusFeedPlugin\Model\FeedContextResultInterface $baseline,
        array $params,
    ): bool {
        // return true to TRIP the guardrail
        return false;
    }
}
```

Tag it `setono_sylius_feed.guardrail`; reference it by `getType()` in a feed's `publishConfig.guardrails`.

### Hook into events

Register a normal Symfony event subscriber/listener for:

- **`FeedItemBuiltEvent`** — dispatched after a `FeedItem` is mapped, before validation/write. Read or
  mutate the bag, or call `$event->item->skip()` to drop the item.

  ```php
  public function onItemBuilt(FeedItemBuiltEvent $event): void
  {
      $item = $event->item;
      if ($item->get('g:price') === '0.00 USD') {
          $item->skip();
      }
  }
  ```

- **`FeedPublishBlockedEvent`** — dispatched when the publish gate blocks (or warns on) a context; carries
  `feed`, `contextKey`, `reasons` and `blocked`. Wire it to Slack/email to be alerted when a feed
  refuses to publish.

### Override a model or service

Every single-implementation service is registered under its **interface** (aliased to the concrete
class), so you override by re-aliasing:

```yaml
services:
    Setono\SyliusFeedPlugin\Generator\FeedContextFinalizerInterface: '@App\Feed\MyFinalizer'
```

Resource models (Feed, FeedSource, FeedField, FeedFilter, DeliveryTarget, FeedContextResult,
LookupTable) follow the standard Sylius ResourceBundle override — set your class under
`setono_sylius_feed.resources.<resource>.classes.model` and extend the shipped model.

---

## Development & testing

The repo ships a full Sylius 1.14 test application in `tests/Application/`.

```bash
composer install

# static analysis (PHPStan max), coding standards (ECS), Rector
composer analyse
composer check-style
vendor/bin/rector process --dry-run

# tests — unit needs no database; functional boots the test kernel + DB
composer phpunit            # both suites
composer phpunit-unit
composer phpunit-functional
```

To run the test app:

```bash
cd tests/Application
bin/console doctrine:database:create
bin/console doctrine:schema:create
bin/console sylius:fixtures:load
yarn install && yarn build
symfony server:start -d
# admin login: sylius / sylius
```

Contributions are welcome — please keep every feature covered by tests (unit for behaviour, functional
across Sylius/Doctrine/HTTP boundaries) and the CI gate green.

---

## License

This plugin is released under the [MIT License](LICENSE).

[ico-version]: https://poser.pugx.org/setono/sylius-feed-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-feed-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusFeedPlugin/workflows/build/badge.svg

[link-packagist]: https://packagist.org/packages/setono/sylius-feed-plugin
[link-github-actions]: https://github.com/Setono/SyliusFeedPlugin/actions
