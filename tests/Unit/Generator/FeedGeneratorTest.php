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
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Acceptance test for the M1 engine: generating a Google Shopping feed for a context produces a
 * valid Google RSS document with the required `g:` fields. Runs the real pipeline (preset, writer,
 * transformations, validation) against a stub data source and an in-memory-style local filesystem,
 * so it needs no database.
 */
final class FeedGeneratorTest extends TestCase
{
    use ProphecyTrait;

    private string $storageDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->storageDir = sys_get_temp_dir() . '/setono-feed-' . bin2hex(random_bytes(6));
        $this->filesystem = new Filesystem(new LocalFilesystemAdapter($this->storageDir));
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory('google');
    }

    /**
     * @test
     */
    public function it_generates_a_valid_google_rss_feed_for_a_context(): void
    {
        $generator = $this->createGenerator();

        $result = $generator->generate($this->feed(), new FeedContext($this->channel(), 'en_US', 'USD'));

        self::assertSame('google/web_en_us_usd.xml', $result->path);
        self::assertSame(2, $result->itemCount);
        self::assertSame(0, $result->excludedCount);

        $xml = $this->filesystem->read($result->path);

        $document = new \DOMDocument();
        self::assertTrue($document->loadXML($xml), 'The generated feed must be well-formed XML');

        self::assertStringContainsString('xmlns:g="http://base.google.com/ns/1.0"', $xml);
        self::assertSame(2, substr_count($xml, '<item>'));
        self::assertStringContainsString('<g:id>SKU-1</g:id>', $xml);
        self::assertStringContainsString('<g:title>Acme Shoe</g:title>', $xml);
        self::assertStringContainsString('<g:description>Very nice</g:description>', $xml);
        self::assertStringContainsString('<g:link>https://example.com/products/acme-shoe</g:link>', $xml);
        self::assertStringContainsString('<g:image_link>https://example.com/img/main.jpg</g:image_link>', $xml);
        self::assertStringContainsString('<g:availability>in_stock</g:availability>', $xml);
        self::assertStringContainsString('<g:price>9.99 USD</g:price>', $xml);
        self::assertStringContainsString('<g:condition>new</g:condition>', $xml);
        // emitted because is_configurable resolves true
        self::assertStringContainsString('<g:item_group_id>PROD-1</g:item_group_id>', $xml);
    }

    /**
     * With no matching preset there are no field mappings, so every item lacks the required output
     * fields and is excluded — the feed is still written, just empty.
     *
     * @test
     */
    public function it_excludes_items_that_are_missing_required_fields(): void
    {
        $generator = $this->createGenerator(presets: []);

        $result = $generator->generate($this->feed(), new FeedContext($this->channel(), 'en_US', 'USD'));

        self::assertSame(0, $result->itemCount);
        self::assertSame(2, $result->excludedCount);
        self::assertStringNotContainsString('<item>', $this->filesystem->read($result->path));
    }

    /**
     * @param list<\Setono\SyliusFeedPlugin\MappingPreset\MappingPresetInterface>|null $presets
     */
    private function createGenerator(?array $presets = null): FeedGenerator
    {
        $feedTypeRegistry = $this->prophesize(FeedTypeRegistryInterface::class);
        $feedTypeRegistry->get('product_variant')->willReturn($this->feedType());

        $presetRegistry = $this->prophesize(MappingPresetRegistryInterface::class);
        $presetRegistry->forFeedType('product_variant')->willReturn($presets ?? [new GoogleShoppingMappingPreset()]);

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
            $this->filesystem,
        );
    }

    private function feedType(): FeedTypeInterface
    {
        $fields = [
            'id' => $this->field('id', FieldType::STRING, 'SKU-1'),
            'item_group_id' => $this->field('item_group_id', FieldType::STRING, 'PROD-1'),
            'title' => $this->field('title', FieldType::STRING, 'Acme Shoe'),
            'description' => $this->field('description', FieldType::STRING, '<p>Very nice</p>'),
            'link' => $this->field('link', FieldType::URL, 'https://example.com/products/acme-shoe'),
            'main_image' => $this->field('main_image', FieldType::IMAGE, 'https://example.com/img/main.jpg'),
            'additional_images' => $this->field('additional_images', FieldType::IMAGE, ['https://example.com/img/1.jpg']),
            'availability' => $this->field('availability', FieldType::STRING, 'in_stock'),
            'channel_price' => $this->field('channel_price', FieldType::MONEY, 999),
            'original_price' => $this->field('original_price', FieldType::MONEY, 1299),
            'is_configurable' => $this->field('is_configurable', FieldType::BOOL, true),
            'on_sale' => $this->field('on_sale', FieldType::BOOL, false),
        ];

        $dataSource = new class() implements DataSourceInterface {
            public function getResourceClass(): string
            {
                return \stdClass::class;
            }

            public function getItems(FeedContext $context, FilterSet $filters): iterable
            {
                yield new \stdClass();
                yield new \stdClass();
            }

            public function count(FeedContext $context, FilterSet $filters): int
            {
                return 2;
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
        $resolver = new FixedValueResolver($name, $type, $value);

        return new FieldDefinition($name, $resolver->getLabel(), $type, $resolver, FieldType::IMAGE === $type && is_array($value));
    }

    private function channel(): ChannelInterface
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getCode()->willReturn('web');
        $channel->getHostname()->willReturn('example.com');

        return $channel->reveal();
    }

    private function feed(): FeedInterface
    {
        $source = $this->prophesize(FeedSourceInterface::class);
        $source->getFeedType()->willReturn('product_variant');
        $source->getPosition()->willReturn(0);
        $source->getFilters()->willReturn(new ArrayCollection());
        $source->getFields()->willReturn(new ArrayCollection());

        $feed = $this->prophesize(FeedInterface::class);
        $feed->getCode()->willReturn('google');
        $feed->getFormat()->willReturn('google_rss');
        $feed->getSources()->willReturn(new ArrayCollection([$source->reveal()]));

        return $feed->reveal();
    }
}
