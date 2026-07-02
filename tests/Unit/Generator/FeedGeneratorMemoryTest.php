<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\DataSource\DataSourceInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Setono\SyliusFeedPlugin\Format\FormatRegistryInterface;
use Setono\SyliusFeedPlugin\Format\GoogleRssFormat;
use Setono\SyliusFeedPlugin\Generator\FeedGenerator;
use Setono\SyliusFeedPlugin\Generator\FieldMappingEvaluator;
use Setono\SyliusFeedPlugin\Lookup\InMemoryLookup;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\Mapping\ScopeDimension;
use Setono\SyliusFeedPlugin\MappingPreset\GoogleShoppingMappingPreset;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistryInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;
use Setono\SyliusFeedPlugin\Operator\IsTrue;
use Setono\SyliusFeedPlugin\Operator\OperatorRegistry;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolver;
use Setono\SyliusFeedPlugin\Scripting\ExpressionEvaluator;
use Setono\SyliusFeedPlugin\Scripting\FeedTemplateSecurityPolicy;
use Setono\SyliusFeedPlugin\Scripting\SandboxedTwigRenderer;
use Setono\SyliusFeedPlugin\Transformation\MoneyFormat;
use Setono\SyliusFeedPlugin\Transformation\StripTags;
use Setono\SyliusFeedPlugin\Transformation\TransformationChain;
use Setono\SyliusFeedPlugin\Transformation\TransformationRegistry;
use Setono\SyliusFeedPlugin\Transformation\Truncate;
use Setono\SyliusFeedPlugin\Validator\RequiredFieldsValidator;
use Setono\SyliusFeedPlugin\Writer\FeedWriterRegistryInterface;
use Setono\SyliusFeedPlugin\Writer\XmlWriter;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Performance budget (§6.3, M1 acceptance): a 50,000-item feed must stream to storage without
 * accumulating the result set in memory. Asserts that peak memory growth during generation stays
 * far below the 256 MB budget — proving the writer/generator stream rather than buffer.
 */
final class FeedGeneratorMemoryTest extends TestCase
{
    use ProphecyTrait;

    public const ITEMS = 50000;

    /**
     * @test
     */
    public function it_generates_a_large_feed_within_the_memory_budget(): void
    {
        $filesystem = new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir() . '/setono-feed-' . bin2hex(random_bytes(6))));

        $generator = $this->createGenerator($filesystem);

        $before = memory_get_peak_usage(true);
        $result = $generator->generate($this->feed(), new FeedContext(null, 'en_US', 'USD'));
        $growth = memory_get_peak_usage(true) - $before;

        self::assertSame(self::ITEMS, $result->itemCount);
        // Streaming means peak memory does not scale with item count; allow generous headroom but
        // far under the 256 MB budget.
        self::assertLessThan(64 * 1024 * 1024, $growth);

        $filesystem->deleteDirectory('google');
    }

    private function createGenerator(Filesystem $filesystem): FeedGenerator
    {
        $feedTypeRegistry = $this->prophesize(FeedTypeRegistryInterface::class);
        $feedTypeRegistry->get('product_variant')->willReturn($this->feedType());

        $presetRegistry = $this->prophesize(MappingPresetRegistryInterface::class);
        $presetRegistry->forFeedType('product_variant')->willReturn([new GoogleShoppingMappingPreset()]);

        $formatRegistry = $this->prophesize(FormatRegistryInterface::class);
        $formatRegistry->get('google_rss')->willReturn(new GoogleRssFormat());

        $writerRegistry = $this->prophesize(FeedWriterRegistryInterface::class);
        $writerRegistry->get('xml')->willReturn(new XmlWriter());

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->getContext()->willReturn(new RequestContext());

        return new FeedGenerator(
            $feedTypeRegistry->reveal(),
            $presetRegistry->reveal(),
            $formatRegistry->reveal(),
            $writerRegistry->reveal(),
            new FieldMappingEvaluator(
                new TransformationChain(new TransformationRegistry([new Truncate(), new StripTags(), new MoneyFormat()])),
                new ReferenceResolver(),
                new OperatorRegistry([new IsTrue()]),
                new ExpressionEvaluator(new InMemoryLookup()),
                new SandboxedTwigRenderer(new FeedTemplateSecurityPolicy(), new InMemoryLookup()),
            ),
            new RequiredFieldsValidator(),
            new EventDispatcher(),
            $urlGenerator->reveal(),
            $filesystem,
        );
    }

    private function feedType(): FeedTypeInterface
    {
        $fields = [
            'id' => $this->field('id', FieldType::STRING, 'SKU-1'),
            'title' => $this->field('title', FieldType::STRING, 'Acme Shoe'),
            'description' => $this->field('description', FieldType::STRING, 'A nice shoe'),
            'link' => $this->field('link', FieldType::URL, 'https://example.com/p/1'),
            'main_image' => $this->field('main_image', FieldType::IMAGE, 'https://example.com/i/1.jpg'),
            'availability' => $this->field('availability', FieldType::STRING, 'in_stock'),
            'channel_price' => $this->field('channel_price', FieldType::MONEY, 999),
        ];

        $dataSource = new class() implements DataSourceInterface {
            public function getResourceClass(): string
            {
                return \stdClass::class;
            }

            public function getItems(FeedContext $context, FilterSet $filters): iterable
            {
                for ($i = 0; $i < FeedGeneratorMemoryTest::ITEMS; ++$i) {
                    yield new \stdClass();
                }
            }

            public function count(FeedContext $context, FilterSet $filters): int
            {
                return FeedGeneratorMemoryTest::ITEMS;
            }
        };

        return new class($dataSource, $fields) implements FeedTypeInterface {
            /**
             * @param array<string, FieldDefinition> $fields
             */
            public function __construct(
                private readonly DataSourceInterface $dataSource,
                private readonly array $fields,
            ) {
            }

            public function getCode(): string
            {
                return 'product_variant';
            }

            public function getLabel(): string
            {
                return 'test.product_variant';
            }

            public function getDataSource(): DataSourceInterface
            {
                return $this->dataSource;
            }

            public function getScopeDimensions(): array
            {
                return [ScopeDimension::CHANNEL, ScopeDimension::LOCALE, ScopeDimension::CURRENCY];
            }

            public function getAvailableFields(): array
            {
                return $this->fields;
            }
        };
    }

    private function field(string $name, FieldType $type, mixed $value): FieldDefinition
    {
        return new FieldDefinition($name, 'test.' . $name, $type, new FixedValueResolver($name, $type, $value));
    }

    private function feed(): FeedInterface
    {
        $source = $this->prophesize(FeedSourceInterface::class);
        $source->getFeedType()->willReturn('product_variant');
        $source->getPosition()->willReturn(0);
        $source->getFilters()->willReturn(new ArrayCollection());

        $feed = $this->prophesize(FeedInterface::class);
        $feed->getCode()->willReturn('google');
        $feed->getFormat()->willReturn('google_rss');
        $feed->getSources()->willReturn(new ArrayCollection([$source->reveal()]));

        return $feed->reveal();
    }
}
