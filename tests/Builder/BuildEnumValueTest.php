<?php

declare(strict_types=1);

namespace FppTest\Builder;

use Fpp\Constructor;
use Fpp\Definition;
use Fpp\DefinitionCollection;
use Fpp\Deriving;
use PHPUnit\Framework\TestCase;
use function Fpp\Builder\buildEnumValue;

class BuildEnumValueTest extends TestCase
{
    /**
     * @test
     */
    public function it_builds_enum_options(): void
    {
        $constructor1 = new Constructor('My\Red');
        $constructor2 = new Constructor('My\Blue');

        $definition = new Definition(
            'My',
            'Color',
            [$constructor1, $constructor2],
            [new Deriving\Enum()]
        );

        $this->assertSame('Red', buildEnumValue($definition, $constructor1, new DefinitionCollection($definition), ''));
    }
}
