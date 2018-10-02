# Sylius Feed Plugin

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-code-quality]][link-code-quality]

## Installation

### Step 1: Download the plugin

Open a command console, enter your project directory and execute the following command to download the latest stable version of this bundle:

```bash
$ composer require setono/sylius-feed-plugin
```

This command requires you to have Composer installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md) of the Composer documentation.


### Step 2: Enable the plugin

Then, enable the plugin by adding it to the list of registered plugins/bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

use Sylius\Bundle\CoreBundle\Application\Kernel;

final class AppKernel extends Kernel
{
    public function registerBundles(): array
    {
        return array_merge(parent::registerBundles(), [
            // ...
            new \Setono\SyliusFeedPlugin\SetonoSyliusFeedPlugin(),
            // ...
        ]);
    }
    
    // ...
}
```

### Step 3: Configure the plugin

```yaml
# app/config/config.yml

imports:
    # ...
    - { resource: "@SetonoSyliusFeedPlugin/Resources/config/config.yml" }

setono_sylius_feed:
    dir: "%kernel.project_dir%/var/feeds"
```

```yaml
# app/config/routing.yml

# ...

setono_sylius_feed:
    resource: "@SetonoSyliusFeedPlugin/Resources/config/routing.yml"
```


### Step 4: Update your database schema
```bash
$ php bin/console doctrine:schema:update --force
```

or use [Doctrine Migrations](https://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html).

## Usage

1. Go to `/admin/feeds/` and create a new feed.
2. Run this command to generate your feed(s): `php bin/console setono:feed:generate`
3. Now you can download your newly generated feed here: `/en_US/feed/test` assuming that your locale is `en_US` and that the slug of the feed is `test`

## TODO
- Select the resource that should be used to create the feed
- Select which attributes should be included in the feed
- Use twig templates instead of hardcoding into command

[ico-version]: https://img.shields.io/packagist/v/setono/sylius-feed-plugin.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/Setono/SyliusFeedPlugin/master.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/Setono/SyliusFeedPlugin.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/setono/sylius-feed-plugin
[link-travis]: https://travis-ci.org/Setono/SyliusFeedPlugin
[link-code-quality]: https://scrutinizer-ci.com/g/Setono/SyliusFeedPlugin