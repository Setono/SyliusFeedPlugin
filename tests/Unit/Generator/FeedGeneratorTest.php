<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use League\Csv\Reader;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\DataSource\DataSourceInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Setono\SyliusFeedPlugin\Format\CsvFormat;
use Setono\SyliusFeedPlugin\Format\FormatRegistryInterface;
use Setono\SyliusFeedPlugin\Format\GoogleRssFormat;
use Setono\SyliusFeedPlugin\Format\PartnerAdsFormat;
use Setono\SyliusFeedPlugin\Generator\FeedGenerator;
use Setono\SyliusFeedPlugin\Generator\FieldMappingEvaluator;
use Setono\SyliusFeedPlugin\Lookup\InMemoryLookup;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\Mapping\ScopeDimension;
use Setono\SyliusFeedPlugin\MappingPreset\GoogleShoppingMappingPreset;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistryInterface;
use Setono\SyliusFeedPlugin\MappingPreset\MetaMappingPreset;
use Setono\SyliusFeedPlugin\MappingPreset\PartnerAdsMappingPreset;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Setono\SyliusFeedPlugin\Model\FeedFieldInterface;
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
use Setono\SyliusFeedPlugin\Transformation\ValueMap;
use Setono\SyliusFeedPlugin\Validator\RequiredFieldsValidator;
use Setono\SyliusFeedPlugin\Writer\CsvWriter;
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
     * A CSV feed whose mapping comes from admin-configured FeedField rows: one header row that is
     * the union of the output fields (in row order) followed by one row per item, cells keyed by
     * the header.
     *
     * @test
     */
    public function it_generates_a_csv_feed_with_a_union_header(): void
    {
        $source = $this->prophesize(FeedSourceInterface::class);
        $source->getFeedType()->willReturn('product_variant');
        $source->getPosition()->willReturn(0);
        $source->getFilters()->willReturn(new ArrayCollection());
        $source->getFields()->willReturn(new ArrayCollection([
            $this->feedField('id', 'id', 0),
            $this->feedField('title', 'title', 1),
            $this->feedField('availability', 'availability', 2),
        ]));

        $feed = $this->prophesize(FeedInterface::class);
        $feed->getCode()->willReturn('google');
        $feed->getFormat()->willReturn('csv');
        $feed->getSources()->willReturn(new ArrayCollection([$source->reveal()]));

        $result = $this->createGenerator()->generate($feed->reveal(), new FeedContext($this->channel(), 'en_US', 'USD'));

        self::assertSame('google/web_en_us_usd.csv', $result->path);
        self::assertSame(2, $result->itemCount);

        $csv = $this->filesystem->read($result->path);
        $rows = array_values(iterator_to_array(Reader::createFromString($csv)->getRecords()));

        self::assertCount(3, $rows, 'header + two item rows');
        self::assertSame(['id', 'title', 'availability'], $rows[0]);
        self::assertSame(['SKU-1', 'Acme Shoe', 'in_stock'], $rows[1]);
    }

    /**
     * Acceptance: the same catalog renders as a valid Meta CSV (with Meta's space-separated
     * availability) purely by choosing the Meta preset — no code changes.
     *
     * @test
     */
    public function it_generates_a_valid_meta_csv_from_the_catalog(): void
    {
        $result = $this->createGenerator([new MetaMappingPreset()])->generate($this->feed('csv'), new FeedContext($this->channel(), 'en_US', 'USD'));

        $reader = Reader::createFromString($this->filesystem->read($result->path));
        $reader->setHeaderOffset(0);
        self::assertContains('availability', $reader->getHeader());
        self::assertContains('price', $reader->getHeader());

        $records = $this->csvRecords($reader);
        $row = $records[0];
        self::assertSame('SKU-1', $row['id']);
        self::assertSame('in stock', $row['availability']);
        self::assertSame('9.99 USD', $row['price']);
    }

    /**
     * Normalises league/csv records to arrays through a mixed boundary — older league/csv versions
     * type `getRecords()` loosely (mixed), newer ones precisely, so this stays valid on both.
     *
     * @return list<array<int|string, mixed>>
     */
    private function csvRecords(Reader $reader): array
    {
        return array_values(array_filter(iterator_to_array($reader->getRecords()), is_array(...)));
    }

    /**
     * Acceptance: the same catalog renders as a valid Partner-ads XML (Danish element names, its
     * own root/item) purely by choosing the Partner-ads preset — no code changes.
     *
     * @test
     */
    public function it_generates_a_valid_partner_ads_xml_from_the_catalog(): void
    {
        $result = $this->createGenerator([new PartnerAdsMappingPreset()])->generate($this->feed('partner_ads'), new FeedContext($this->channel(), 'en_US', 'USD'));

        $xml = $this->filesystem->read($result->path);
        self::assertTrue((new \DOMDocument())->loadXML($xml), 'The Partner-ads feed must be well-formed XML');
        self::assertStringContainsString('<produkter>', $xml);
        self::assertSame(2, substr_count($xml, '<produkt>'));
        self::assertStringContainsString('<produktid>SKU-1</produktid>', $xml);
        self::assertStringContainsString('<produktnavn>Acme Shoe</produktnavn>', $xml);
        self::assertStringContainsString('<nypris>9.99 USD</nypris>', $xml);
        self::assertStringContainsString('<VareURL>https://example.com/products/acme-shoe</VareURL>', $xml);
    }

    private function feedField(string $outputField, string $sourceField, int $position): FeedFieldInterface
    {
        $field = new FeedField();
        $field->setOutputField($outputField);
        $field->setSourceType('field');
        $field->setSourceValue($sourceField);
        $field->setPosition($position);

        return $field;
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
        $formatRegistry->get('csv')->willReturn(new CsvFormat());
        $formatRegistry->get('partner_ads')->willReturn(new PartnerAdsFormat());

        $writerRegistry = $this->prophesize(FeedWriterRegistryInterface::class);
        $writerRegistry->get('xml')->willReturn(new XmlWriter());
        $writerRegistry->get('csv')->willReturn(new CsvWriter());

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->getContext()->willReturn(new RequestContext());

        return new FeedGenerator(
            $feedTypeRegistry->reveal(),
            $presetRegistry->reveal(),
            $formatRegistry->reveal(),
            $writerRegistry->reveal(),
            new FieldMappingEvaluator(
                new TransformationChain(new TransformationRegistry([new Truncate(), new StripTags(), new MoneyFormat(), new ValueMap()])),
                new ReferenceResolver(),
                new OperatorRegistry([new IsTrue()]),
                new ExpressionEvaluator(new InMemoryLookup()),
                new SandboxedTwigRenderer(new FeedTemplateSecurityPolicy(), new InMemoryLookup()),
                new NullLookupReferenceResolver(),
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

    private function feed(string $format = 'google_rss'): FeedInterface
    {
        $source = $this->prophesize(FeedSourceInterface::class);
        $source->getFeedType()->willReturn('product_variant');
        $source->getPosition()->willReturn(0);
        $source->getFilters()->willReturn(new ArrayCollection());
        $source->getFields()->willReturn(new ArrayCollection());

        $feed = $this->prophesize(FeedInterface::class);
        $feed->getCode()->willReturn('google');
        $feed->getFormat()->willReturn($format);
        $feed->getSources()->willReturn(new ArrayCollection([$source->reveal()]));

        return $feed->reveal();
    }
}
