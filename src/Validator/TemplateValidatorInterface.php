<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Validator;

interface TemplateValidatorInterface
{
    public function validate(string $template): void;
}
