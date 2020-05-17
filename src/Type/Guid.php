<?php

/**
 * This file is part of prolic/fpp.
 * (c) 2018-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fpp\Type\Guid;

use function Fpp\char;
use Fpp\Namespace_;
use Fpp\Parser;
use function Fpp\plus;
use function Fpp\result;
use function Fpp\spaces;
use function Fpp\spaces1;
use function Fpp\string;
use Fpp\Type as FppType;
use function Fpp\Type\Marker\markers;
use function Fpp\typeName;
use Fpp\TypeTrait;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Type;
use Phunkie\Types\ImmMap;
use Phunkie\Types\Tuple;

function definition(): Tuple
{
    return \Tuple(parse, build, fromPhpValue, toPhpValue);
}

const parse = 'Fpp\Type\Guid\parse';

function parse(): Parser
{
    return for_(
        __($_)->_(spaces()),
        __($_)->_(string('guid')),
        __($_)->_(spaces1()),
        __($t)->_(typeName()),
        __($_)->_(spaces()),
        __($ms)->_(
            plus(markers(), result(Nil()))
        ),
        __($_)->_(spaces()),
        __($_)->_(char(';'))
    )->call(fn ($t, $ms) => new Guid($t, $ms), $t, $ms);
}

const build = 'Fpp\Type\Guid\build';

function build(Guid $type, ImmMap $builders): ClassType
{
    $class = new ClassType($type->classname());
    $class->setFinal(true);
    $class->setImplements($type->markers()->toArray());

    $class->addProperty('uuid')->setType('UuidInterface')->setPrivate();
    $class->addProperty('factory')->setType('UuidFactory')->setNullable()->setStatic()->setPrivate();

    $constructor = $class->addMethod('__construct');
    $constructor->addParameter('uuid')->setType('UuidInterface');
    $constructor->setBody('$this->uuid = $uuid;');
    $constructor->setPrivate();

    $generate = $class->addMethod('generate')->setReturnType('self');
    $generate->setBody('return new self(self::factory()->uuid4());');
    $generate->setStatic();

    $fromString = $class->addMethod('fromString')->setReturnType('self');
    $fromString->addParameter('uuid')->setType('string');
    $fromString->setBody('return new self(self::factory()->fromString($uuid));');
    $fromString->setStatic();

    $fromBinary = $class->addMethod('fromBinary')->setReturnType('self');
    $fromBinary->addParameter('bytes')->setType('string');
    $fromBinary->setBody('return new self(self::factory()->fromBytes($bytes));');
    $fromBinary->setStatic();

    $toString = $class->addMethod('toString')->setReturnType(Type::STRING);
    $toString->setBody('return $this->uuid->toString();');

    $__toString = $class->addMethod('__toString')->setReturnType(Type::STRING);
    $__toString->setBody('return $this->uuid->toString();');

    $toBinary = $class->addMethod('toBinary')->setReturnType(Type::STRING);
    $toBinary->setBody('return $this->uuid->getBytes();');

    $equals = $class->addMethod('equals')->setReturnType(Type::BOOL);
    $equals->addParameter('other')->setType('self');
    $equals->setBody('return $this->uuid->equals($other->uuid);');

    $factory = $class->addMethod('factory')->setReturnType('UuidFactory');
    $factory->setPrivate()->setStatic();
    $factory->setBody(<<<CODE
if (null === self::\$factory) {
    self::\$factory = new UuidFactory(new FeatureSet(true));
}

return self::\$factory;
CODE
);

    return $class;
}

const fromPhpValue = 'Fpp\Type\Guid\fromPhpValue';

function fromPhpValue(Guid $type, bool $value): string
{
    return $type->classname() . '::fromString(' . $value . ')';
}

const toPhpValue = 'Fpp\Type\Guid\toPhpValue';

function toPhpValue(Guid $type, string $paramName): string
{
    return $paramName . '->toString()';
}

class Guid implements FppType
{
    use TypeTrait;

    public function setNamespace(Namespace_ $namespace): void
    {
        $namespace->addImports(ImmList(
            Pair('Ramsey\Uuid\FeatureSet', null),
            Pair('Ramsey\Uuid\Uuid', null),
            Pair('Ramsey\Uuid\UuidFactory', null),
            Pair('Ramsey\Uuid\UuidInterface', null),
        ));

        $this->namespace = $namespace;
    }
}
