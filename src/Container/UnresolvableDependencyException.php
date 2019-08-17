<?php

namespace Bagan\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class UnresolvableDependencyException extends Exception implements ContainerExceptionInterface
{

}
