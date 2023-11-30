<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Factory;

use Setono\SyliusFeedPlugin\Model\ViolationInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;

class ViolationFactory implements ViolationFactoryInterface
{
    private FactoryInterface $decoratedFactory;

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
     * @param mixed|null $data
     */
    public function createFromConstraintViolation(
        ConstraintViolationInterface $constraintViolation,
        ChannelInterface $channel,
        LocaleInterface $locale,
        $data = null
    ): ViolationInterface {
        $violation = $this->createNew();

        $violation->setChannel($channel);
        $violation->setLocale($locale);
        $violation->setMessage(
            $constraintViolation->getPropertyPath() . ': ' . sprintf((string) $constraintViolation->getMessage(), $constraintViolation->getInvalidValue())
        );
        $violation->setData($data);

        if ($constraintViolation instanceof ConstraintViolation) {
            $constraint = $constraintViolation->getConstraint();
            if (null !== $constraint && isset($constraint->payload['severity'])) {
                $violation->setSeverity($constraint->payload['severity']);
            }
        }

        return $violation;
    }
}
