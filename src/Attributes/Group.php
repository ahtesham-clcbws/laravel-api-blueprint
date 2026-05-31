<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Group
{
    public function __construct(
        public string $name,
    ) {}
}
