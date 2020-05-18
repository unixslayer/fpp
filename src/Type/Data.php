<?php

/**
 * This file is part of prolic/fpp.
 * (c) 2018-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fpp\Type\Data;

use function Fpp\alphanum;
use function Fpp\assignment;
use function Fpp\char;
use function Fpp\int;
use function Fpp\item;
use function Fpp\letter;
use function Fpp\many;
use Fpp\Parser;
use function Fpp\plus;
use function Fpp\result;
use function Fpp\sepBy1list;
use function Fpp\spaces;
use function Fpp\spaces1;
use function Fpp\string;
use function Fpp\surrounded;
use function Fpp\surroundedWith;
use Fpp\Type as FppType;
use function Fpp\Type\Marker\markers;
use function Fpp\typeName;
use Fpp\TypeTrait;
use Nette\PhpGenerator\ClassType;
use Phunkie\Types\ImmList;
use Phunkie\Types\ImmMap;
use Phunkie\Types\Tuple;

function definition(): Tuple
{
    return \Tuple(parse, build, fromPhpValue, toPhpValue);
}

const parse = 'Fpp\Type\Data\parse';

function parse(): Parser
{
    return for_(
        __($_)->_(spaces()),
        __($_)->_(string('data')),
        __($_)->_(spaces1()),
        __($t)->_(typeName()),
        __($_)->_(spaces()),
        __($ms)->_(
            plus(markers(), result(Nil()))
        ),
        __($_)->_(assignment()),
        __($as)->_(surrounded(
            for_(
                __($_)->_(spaces()),
                __($o)->_(char('{')),
                __($_)->_(spaces())
            )->yields($o),
            sepBy1list(
                for_(
                    __($_)->_(spaces()),
                    __($n)->_(char('?')->or(result(''))),
                    __($at)->_(typeName()->or(result(''))),
                    __($l)->_(string('[]')->or(result(''))),
                    __($_)->_(spaces()),
                    __($_)->_(char('$')),
                    __($x)->_(plus(letter(), char('_'))),
                    __($xs)->_(many(plus(alphanum(), char('_')))),
                    __($_)->_(spaces()),
                    __($e)->_(char('=')->or(result(''))),
                    __($_)->_(spaces()),
                    __($d)->_(
                        many(int())
                            ->or(string('null'))
                            ->or(string('[]'))
                            ->or(surroundedWith(char('\''), many(item()), char('\'')))->or(result(''))
                    ),
                )->call(
                    fn ($at, $x, $xs, $n, $l, $e, $d) => new Argument(
                        $x . $xs,
                        '' === $at ? null : $at,
                        $n === '?',
                        '[]' === $l,
                        '=' === $e ? $d : null
                    ),
                    $at,
                    $x,
                    $xs,
                    $n,
                    $l,
                    $e,
                    $d
                ),
                char(',')
            ),
            for_(
                __($_)->_(spaces()),
                __($c)->_(char('}')),
                __($_)->_(spaces())
            )->yields($c)
        )),
        __($_)->_(spaces()),
        __($_)->_(char(';'))
    )->call(fn ($t, $ms, $as) => new Data($t, $ms, $as), $t, $ms, $as);
}

const build = 'Fpp\Type\Data\build';

function build(Data $data, ImmMap $builders): ClassType
{
    $classname = $data->classname();

    $class = new ClassType($classname);
    $class->setFinal(true);

    $constructor = $class->addMethod('__construct');

    $body = '';
    $data->arguments()->map(function (Argument $a) use ($data, $class, $constructor, &$body) {
        $property = $class->addProperty($a->name())->setPrivate()->setNullable($a->nullable());

        switch ($a->type()) {
            case 'int':
                $defaultValue = (int) $a->defaultValue();
                break;
            case 'float':
                $defaultValue = (float) $a->defaultValue();
                break;
            case 'bool':
                $defaultValue = ('true' === $a->defaultValue());
                break;
            default:
                $defaultValue = $a->defaultValue() === "''" ? '' : ($a->defaultValue() === '[]' ? [] : $a->defaultValue());
                break;
        }

        if ($defaultValue) {
            $param = $constructor->addParameter($a->name(), $defaultValue);
        } else {
            $param = $constructor->addParameter($a->name());
        }

        $param->setNullable($a->nullable());

        $body .= "\$this->{$a->name()} = \${$a->name()};\n";
        $method = $class->addMethod($a->name());
        $method->setBody("return \$this->{$a->name()};");

        if ($a->isList()) {
            $property->setType('array');
            $param->setType('array');

            if ($a->type()) {
                $method->addComment('@return ' . $a->type() . '[]');
            }
            $method->setReturnType('array');
        } else {
            $property->setType($a->type());
            $param->setType($a->type());
            $method->setReturnType($a->type());
            $method->setReturnNullable($a->nullable());
        }

        if (null !== $a->type() && $a->isList()) {
            $property->setType('array');
            $property->addComment('@return ' . $a->type() . '[]');
        }
    });

    $constructor->setBody(\substr($body, 0, -1));

    return $class;
}

const fromPhpValue = 'Fpp\Type\Data\fromPhpValue';

function fromPhpValue(Data $type, bool $value): string
{
    throw new \BadMethodCallException('Not implemented');
}

const toPhpValue = 'Fpp\Type\Data\toPhpValue';

function toPhpValue(Data $type, string $paramName): string
{
    throw new \BadMethodCallException('Not implemented');
}

class Data implements FppType
{
    use TypeTrait;

    /** @var Immlist<Argument> */
    private ImmList $arguments;

    /** @param Immlist<Argument> $arguments */
    public function __construct(string $classname, ImmList $markers, ImmList $arguments)
    {
        $this->classname = $classname;
        $this->markers = $markers;
        $this->arguments = $arguments;
    }

    /**
     * @return ImmList<Argument>
     */
    public function arguments(): ImmList
    {
        return $this->arguments;
    }
}

class Argument
{
    private string $name;
    private ?string $type;
    private bool $nullable;
    private bool $isList;
    /** @var mixed */
    private $defaultValue;

    public function __construct(string $name, ?string $type, bool $nullable, bool $isList, $defaultValue)
    {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->isList = $isList;
        $this->defaultValue = $defaultValue;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): ?string
    {
        return $this->type;
    }

    public function nullable(): bool
    {
        return $this->nullable;
    }

    public function isList(): bool
    {
        return $this->isList;
    }

    public function defaultValue()
    {
        return $this->defaultValue;
    }

    public function isScalarTypeHint(): bool
    {
        return \in_array($this->type, ['string', 'int', 'bool', 'float'], true);
    }
}
