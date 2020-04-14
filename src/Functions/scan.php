<?php

/**
 * This file is part of prolic/fpp.
 * (c) 2018-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fpp;

use FilterIterator;
use function ImmList;
use function Nil;
use Phunkie\Types;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

const scan = '\Fpp\scan';

/**
 * @param string $directoryOrFile
 *
 * @return Types\ImmList<string>
 */
function scan(string $directoryOrFile): Types\ImmList
{
    if (! \is_readable($directoryOrFile)) {
        return Nil();
    }

    if (\is_file($directoryOrFile)) {
        return ImmList($directoryOrFile);
    }

    $iterator = new class(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directoryOrFile))) extends FilterIterator {
        public function __construct($directoryOrFile)
        {
            parent::__construct($directoryOrFile);
        }

        public function accept(): bool
        {
            $file = $this->getInnerIterator()->current();

            if (! $file->isFile()) {
                return false;
            }

            if (! $file->isReadable()) {
                return false;
            }

            return $file->getExtension() === 'fpp';
        }
    };

    $files = [];

    foreach ($iterator as $f => $i) {
        $files[] = $f;
    }

    return ImmList(...$files);
}
