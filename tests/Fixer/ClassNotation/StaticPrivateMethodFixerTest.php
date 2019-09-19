<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Fixer\ClassNotation;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\ClassNotation\StaticPrivateMethodFixer
 */
final class StaticPrivateMethodFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        return [
            'main-use-case' => [
                '<?php
class Foo
{
    public $baz;

    public function bar()
    {
        $var = $this->baz;
        $var = self::baz();
        if (true) {
            $var = self::baz();
        }
    }

    private static function baz()
    {
        return 1;
    }
}
',
                '<?php
class Foo
{
    public $baz;

    public function bar()
    {
        $var = $this->baz;
        $var = $this->baz();
        if (true) {
            $var = $this->baz();
        }
    }

    private function baz()
    {
        return 1;
    }
}
',
            ],
            'handle-multiple-classes' => [
                '<?php
class Foo
{
    private static function baz() { return 1; }

    public function xyz()
    {
        return new class() extends Wut {
            public function anonym_xyz()
            {
                return $this->baz();
            }
        };
    }
}
class Bar
{
    public function baz() { return 1; }

    abstract protected function xyz1();
    protected abstract function xyz2();
    abstract function xyz3();
}
',
                '<?php
class Foo
{
    private function baz() { return 1; }

    public function xyz()
    {
        return new class() extends Wut {
            public function anonym_xyz()
            {
                return $this->baz();
            }
        };
    }
}
class Bar
{
    public function baz() { return 1; }

    abstract protected function xyz1();
    protected abstract function xyz2();
    abstract function xyz3();
}
',
            ],
            'inverse-order-keywords-already-ok' => [
                '<?php
class Foo
{
    static private function inverseOrder() { return 1; }
}
',
            ],
            'skip-methods-containing-closures' => [
                '<?php
class Foo
{
    private function bar()
    {
        return function() {};
    }

    private function baz()
    {
        return static function() {};
    }
}
',
            ],
            'skip-instance-references' => [
                '<?php
class Foo
{
    private function bar()
    {
        return $this;
    }
}
',
            ],
            'skip-debug_backtrace' => [
                '<?php
class Foo
{
    private function bar()
    {
        return debug_backtrace()[1][\'object\'];
    }
}
',
            ],
            'fix-references-inside-non-static-closures' => [
                '<?php
class Foo
{
    public $baz;

    public function bar()
    {
        $var = function() {
            $var = $this->baz;
            $var = self::baz();
            $var = new class() {
                public function foo()
                {
                    return $this->baz();
                }
            };
        };
        // Non valid in runtime, but valid syntax
        $var = static function() {
            $var = $this->baz();
        };
    }

    private static function baz()
    {
        return 1;
    }
}
',
                '<?php
class Foo
{
    public $baz;

    public function bar()
    {
        $var = function() {
            $var = $this->baz;
            $var = $this->baz();
            $var = new class() {
                public function foo()
                {
                    return $this->baz();
                }
            };
        };
        // Non valid in runtime, but valid syntax
        $var = static function() {
            $var = $this->baz();
        };
    }

    private function baz()
    {
        return 1;
    }
}
',
            ],
            'skip-magic-methods' => [
                '<?php
class Foo
{
    private function __call() {}
    private function __callstatic() {}
    private function __clone() {}
    private function __construct() {}
    private function __debuginfo() {}
    private function __destruct() {}
    private function __get() {}
    private function __invoke() {}
    private function __isset() {}
    private function __serialize() {}
    private function __set() {}
    private function __set_state() {}
    private function __sleep() {}
    private function __tostring() {}
    private function __unserialize() {}
    private function __unset() {}
    private function __wakeup() {}
}
',
            ],
            'bug-multiple-methods' => [
                self::generate50Samples(true),
                self::generate50Samples(false),
            ],
        ];
    }

    /**
     * @param bool $fixed
     *
     * @return string
     */
    private static function generate50Samples($fixed)
    {
        $template = '<?php
class Foo
{
    public function userMethodStart()
    {
%s
    }
%s
}
';
        $usage = '';
        $signature = '';
        for ($inc = 0; $inc < 50; ++$inc) {
            $usage .= sprintf('$var = %sbar%02s();%s', $fixed ? 'self::' : '$this->', $inc, PHP_EOL);
            $signature .= sprintf('private %sfunction bar%02s() {}%s', $fixed ? 'static ' : '', $inc, PHP_EOL);
        }

        return sprintf($template, $usage, $signature);
    }
}
