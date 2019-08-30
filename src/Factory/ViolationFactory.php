<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Factory;

use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Model\ViolationInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;

final class ViolationFactory implements ViolationFactoryInterface
{
    /** @var FactoryInterface */
    private $decoratedFactory;

    public function __construct(FactoryInterface $decoratedFactory)
    {
        $this->decoratedFactory = $decoratedFactory;
    }

    public function createNew(): ViolationInterface
    {
        /** @var ViolationInterface $violation */
        $violation = $this->decoratedFactory->createNew();

        return $violation;
    }

    /**
     * @throws StringsException
     */
    public function createFromConstraintViolation(
        ConstraintViolationInterface $constraintViolation,
        ChannelInterface $channel,
        LocaleInterface $locale
    ): ViolationInterface {
        $violation = $this->createNew();

        $violation->setChannel($channel);
        $violation->setLocale($locale);
        $violation->setMessage($constraintViolation->getPropertyPath() . ': ' . sprintf($constraintViolation->getMessage(),
                $constraintViolation->getInvalidValue()));

        if ($constraintViolation instanceof ConstraintViolation) {
            $constraint = $constraintViolation->getConstraint();
            if (null !== $constraint && isset($constraint->payload['severity'])) {
                $violation->setSeverity($constraint->payload['severity']);
            }
        }

        return $violation;
    }
}
