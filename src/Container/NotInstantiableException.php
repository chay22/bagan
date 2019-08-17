<?php

namespace Bagan\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class NotInstantiableException extends Exception implements ContainerExceptionInterface
{

}
