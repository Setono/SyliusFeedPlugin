<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

trait GetLocaleTrait
{
    private RepositoryInterface $localeRepository;

    private function getLocale(int $id): LocaleInterface
    {
        /** @var LocaleInterface|null $obj */
        $obj = $this->localeRepository->find($id);

        if (null === $obj) {
            throw new UnrecoverableMessageHandlingException(sprintf('Locale with id %s does not exist', $id));
        }

        return $obj;
    }
}
