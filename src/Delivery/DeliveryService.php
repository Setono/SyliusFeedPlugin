<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Model\DeliveryTargetInterface;
use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;

/**
 * Pushes a promoted context's canonical file set to its matching delivery targets (§12).
 *
 * Every push is isolated: a failing transport, target or file is recorded as an `error` delivery on
 * the result and never aborts the rest, so one broken destination cannot fail the feed run or starve
 * the other targets/contexts. The method is best-effort and never throws.
 */
final class DeliveryService implements DeliveryServiceInterface
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly DeliveryTransportRegistryInterface $transportRegistry,
        private readonly DeliveryMatcherInterface $matcher,
        private readonly PathTemplateRendererInterface $pathTemplateRenderer,
        private readonly FilesystemOperator $feedFilesystem,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function deliver(FeedInterface $feed, FeedContextResultInterface $result, array $paths): void
    {
        try {
            $recorded = false;
            foreach ($feed->getDeliveryTargets() as $target) {
                $recorded = $this->deliverToTarget($target, $result, $paths) || $recorded;
            }

            if ($recorded) {
                $this->getManager($result)->flush();
            }
        } catch (\Throwable) {
            // Last-resort safety net: delivery must never fail the feed run.
        }
    }

    /**
     * @param list<string> $paths
     *
     * @return bool whether any delivery entry was recorded on the result
     */
    private function deliverToTarget(DeliveryTargetInterface $target, FeedContextResultInterface $result, array $paths): bool
    {
        if (!$this->matcher->matches(
            $target->getMatch(),
            $result->getChannelCode(),
            $result->getLocaleCode(),
            $result->getCurrencyCode(),
        )) {
            return false;
        }

        $label = sprintf('%s/%s', (string) $target->getTransport(), (string) $target->getId());

        try {
            $filesystem = $this->transportRegistry->get((string) $target->getTransport())->createFilesystem($target->getTransportConfig());
        } catch (\Throwable $exception) {
            $result->addDelivery([
                'target' => $label,
                'path' => (string) $target->getPathTemplate(),
                'status' => 'error',
                'error' => $exception->getMessage(),
            ]);

            return true;
        }

        $contextKey = (string) $result->getContextKey();
        foreach ($paths as $path) {
            $this->push($filesystem, $target, $result, $label, $contextKey, $path);
        }

        return true;
    }

    private function push(
        FilesystemOperator $filesystem,
        DeliveryTargetInterface $target,
        FeedContextResultInterface $result,
        string $label,
        string $contextKey,
        string $path,
    ): void {
        [$ext, $part] = $this->describe($path, $contextKey);
        $renderedPath = $this->pathTemplateRenderer->render(
            (string) $target->getPathTemplate(),
            $result->getChannelCode(),
            $result->getLocaleCode(),
            $result->getCurrencyCode(),
            $contextKey,
            $ext,
            $part,
        );

        try {
            $stream = $this->feedFilesystem->readStream($path);
            $filesystem->writeStream($renderedPath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            $result->addDelivery(['target' => $label, 'path' => $renderedPath, 'status' => 'delivered']);
        } catch (\Throwable $exception) {
            $result->addDelivery([
                'target' => $label,
                'path' => $renderedPath,
                'status' => 'error',
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Derives the extension (including a trailing .gz) and the split part number of a canonical path
     * so the destination path template can be rendered per file.
     *
     * @return array{0: string, 1: ?string}
     */
    private function describe(string $path, string $contextKey): array
    {
        $basename = basename($path);

        $suffix = '';
        if (str_ends_with($basename, '.gz')) {
            $suffix = '.gz';
            $basename = substr($basename, 0, -3);
        }

        $ext = pathinfo($basename, \PATHINFO_EXTENSION) . $suffix;
        $filename = pathinfo($basename, \PATHINFO_FILENAME);

        $part = null;
        if ('' !== $contextKey && str_starts_with($filename, $contextKey . '-')) {
            $part = substr($filename, strlen($contextKey) + 1);
        }

        return [$ext, $part];
    }
}
