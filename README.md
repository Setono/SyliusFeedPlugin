# Sylius Feed Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]

A plugin for creating all kinds of feeds to any given service. Do you want to create product feeds for
your Google Merchant center? Then this is the right plugin for you.

## Installation

### Step 1: Download the plugin

Open a command console, enter your project directory and execute the following command to download the latest stable version of this plugin:

```bash
composer require setono/sylius-feed-plugin
```

This command requires you to have Composer installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md) of the Composer documentation.

### Step 2: Enable the plugin

Then, enable the plugin by adding the following to the list of registered plugins/bundles
in the `config/bundles.php` file of your project:

```php
<?php

return [
    // ...
    
    League\FlysystemBundle\FlysystemBundle::class => ['all' => true],
    Setono\SyliusFeedPlugin\SetonoSyliusFeedPlugin::class => ['all' => true],
    Setono\DoctrineORMBatcherBundle\SetonoDoctrineORMBatcherBundle::class => ['all' => true],
    
    // It is important to add plugin before the grid bundle
    Sylius\Bundle\GridBundle\SyliusGridBundle::class => ['all' => true],
        
    // ...
];
```

**NOTE** that you must instantiate the plugin before the grid bundle, else you will see an exception like
`You have requested a non-existent parameter "setono_sylius_feed.model.feed.class".`

### Step 3: Import routing

```yaml
# config/routes/setono_sylius_feed.yaml
setono_sylius_feed:
    resource: "@SetonoSyliusFeedPlugin/Resources/config/routing.yaml"
```

If you don't use localized URLs, use this routing file instead: `@SetonoSyliusFeedPlugin/Resources/config/routing_non_localized.yaml`

### Step 4: Configure plugin

```yaml
# config/packages/setono_sylius_feed.yaml
imports:
    - { resource: "@SetonoSyliusFeedPlugin/Resources/config/app/config.yaml" }
```

### Step 5: Update database schema

Use Doctrine migrations to create a migration file and update the database.

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

### Step 6: Using asynchronous transport (optional, but recommended)

All commands in this plugin will extend the [CommandInterface](src/Message/Command/CommandInterface.php).
Therefore, you can route all commands easily by adding this to your [Messenger config](https://symfony.com/doc/current/messenger.html#routing-messages-to-a-transport):

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            # Route all command messages to the async transport
            # This presumes that you have already set up an 'async' transport
            # See docs on how to set up a transport like that: https://symfony.com/doc/current/messenger.html#transports-async-queued-messages
            'Setono\SyliusFeedPlugin\Message\Command\CommandInterface': async
```

## Usage
After setup, you want to create a feed. Go to `/admin/feeds/new` and create a new feed. Remember to enable it and select
one or more channels.

After that go to your console and run this command:

```bash
$ php bin/console setono:sylius-feed:process
```

If you haven't changed any configuration, there should be a feed with your products inside the `/var/storage/setono_sylius_feed/feed` directory.


[ico-version]: https://poser.pugx.org/setono/sylius-feed-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-feed-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusFeedPlugin/workflows/build/badge.svg

[link-packagist]: https://packagist.org/packages/setono/sylius-feed-plugin
[link-github-actions]: https://github.com/Setono/SyliusFeedPlugin/actions
