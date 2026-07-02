<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Preview;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Audit\AuditReport;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Preview\PreviewFunnel;
use Setono\SyliusFeedPlugin\Preview\PreviewResult;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Renders the real preview admin template with strict_variables on — the same mode dev + the test
 * app use. The included-sample table iterates the UNION of every item's output keys, but item bags
 * are heterogeneous (e.g. `g:item_group_id` only exists for multi-variant products), so a cell must
 * tolerate a column its row does not carry rather than throwing "Key ... does not exist".
 */
final class PreviewTemplateTest extends TestCase
{
    private function twig(): Environment
    {
        // the template lives under the @SetonoSyliusFeedPlugin namespace and extends the Sylius admin
        // layout, which we stub so we can render it without booting the whole admin
        $filesystem = new FilesystemLoader();
        $filesystem->addPath(__DIR__ . '/../../../src/Resources/views', 'SetonoSyliusFeedPlugin');

        $twig = new Environment(new ChainLoader([
            new ArrayLoader(['@SyliusAdmin/layout.html.twig' => '{% block content %}{% endblock %}']),
            $filesystem,
        ]), ['strict_variables' => true]);

        // the template only depends on the |trans filter and the path() function from the framework
        $twig->addFilter(new TwigFilter('trans', static fn (string $key): string => $key));
        $twig->addFunction(new TwigFunction('path', static fn (string $route): string => '/' . $route));

        return $twig;
    }

    /**
     * @test
     */
    public function it_renders_included_items_whose_bags_do_not_all_carry_every_column(): void
    {
        $preview = new PreviewResult(
            new PreviewFunnel(3, 3, 2, 2, 2),
            [
                ['g:id' => 'VARIANT-1', 'g:item_group_id' => 'PRODUCT-1', 'g:title' => 'Configurable'],
                // this bag deliberately omits g:item_group_id — a simple (single-variant) product
                ['g:id' => 'VARIANT-2', 'g:title' => 'Simple'],
            ],
            [
                ['item' => 'VARIANT-3', 'reason' => 'validation:g:price'],
            ],
        );

        $audit = new AuditReport(
            ['g:id' => 1.0, 'g:item_group_id' => 0.5],
            [['type' => 'title_too_long', 'field' => 'g:title', 'count' => 1]],
            ['g:availability' => ['in_stock' => 2]],
        );

        // the union of every included bag's keys, exactly as the controller builds it
        $columns = ['g:id', 'g:item_group_id', 'g:title'];

        $html = $this->twig()->render('@SetonoSyliusFeedPlugin/admin/feed/preview.html.twig', [
            'feed' => (object) ['code' => 'test-feed'],
            'context' => new FeedContext(),
            'preview' => $preview,
            'audit' => $audit,
            'columns' => $columns,
        ]);

        self::assertStringContainsString('VARIANT-1', $html);
        self::assertStringContainsString('PRODUCT-1', $html);
        self::assertStringContainsString('VARIANT-2', $html);
        self::assertStringContainsString('VARIANT-3', $html);
    }
}
