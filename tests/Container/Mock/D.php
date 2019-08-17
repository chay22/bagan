<?php

declare(strict_types=1);

namespace Bagan\Test\Container\Mock;

class D {
    public $prop = 'd';

    public function __construct($int)
    {
        $this->prop = $int;
    }
}
