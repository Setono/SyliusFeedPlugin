<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * One place a generated feed context is pushed to after it has been promoted to canonical storage
 * (§12). A target names the transport to use (e.g. `local`, `ftp`, `sftp`, `s3`), its (env-resolved)
 * connection config, a path template for the remote file(s) and an optional match that limits it to
 * a subset of the feed's contexts (by channel/locale/currency).
 */
interface DeliveryTargetInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getFeed(): ?FeedInterface;

    public function setFeed(?FeedInterface $feed): void;

    public function getPosition(): ?int;

    public function setPosition(?int $position): void;

    /**
     * The transport type, matched against {@see \Setono\SyliusFeedPlugin\Delivery\DeliveryTransportInterface::getType()}.
     */
    public function getTransport(): ?string;

    public function setTransport(?string $transport): void;

    /**
     * The transport connection config (host, credentials, root, …). Secrets are expected to be
     * env-resolved by the application, never stored in clear text here.
     *
     * @return array<string, mixed>
     */
    public function getTransportConfig(): array;

    /**
     * @param array<string, mixed> $transportConfig
     */
    public function setTransportConfig(array $transportConfig): void;

    /**
     * The remote path template with placeholders {channel}, {locale}, {currency}, {contextKey},
     * {ext} and {part}.
     */
    public function getPathTemplate(): ?string;

    public function setPathTemplate(?string $pathTemplate): void;

    /**
     * The match limiting this target to a subset of contexts, shape {channel?, locale?, currency?}.
     * An omitted dimension is a wildcard; an empty match delivers every context.
     *
     * @return array<string, mixed>
     */
    public function getMatch(): array;

    /**
     * @param array<string, mixed> $match
     */
    public function setMatch(array $match): void;
}
