<?php

declare(strict_types=1);

namespace Bagan\Test\Container\Mock;

class C {
    public $prop = 'c';

    public function __construct(A $a, int $int = 2)
    {
        $this->prop = $int;
    }
}
