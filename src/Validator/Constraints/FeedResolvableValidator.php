<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Validator\Constraints;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class FeedResolvableValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof FeedResolvable) {
            throw new UnexpectedTypeException($constraint, FeedResolvable::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof FeedInterface) {
            throw new UnexpectedValueException($value, FeedInterface::class);
        }

        if (!$value->isEnabled()) {
            return;
        }

        foreach ($value->getSources() as $source) {
            foreach ($source->getFields() as $field) {
                if ($field->getRequiresInput()) {
                    $this->context->buildViolation($constraint->message)
                        ->atPath('enabled')
                        ->addViolation();

                    return;
                }
            }
        }
    }
}
