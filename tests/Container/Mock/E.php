<?php

declare(strict_types=1);

namespace Bagan\Test\Container\Mock;

class E {
    public $prop = 'e';

    public function __construct(Z $z)
    {
        $this->prop = $z;
    }
}
