<?php

declare(strict_types=1);

namespace Bagan\Test\Container\Mock;

class B {
    public $prop = 'b';

    public function __construct($int, A $a)
    {
        $this->prop = $a;
    }
}
