<?php

namespace Tests\Llvdl\Domino\Traits;

trait PrivatePropertySetter
{
    public function setPrivateProperty($obj, $name, $value)
    {
        $property = new \reflectionproperty(get_class($obj), $name);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }
}
