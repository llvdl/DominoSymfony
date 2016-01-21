<?php

namespace Tests\AppBundle\Controller\Traits;

trait StatusCodeAsserterTrait
{
    /** @param integer $expectedStatusCode */
    protected function assertStatusCode($expectedStatusCode)
    {
        $this->assertEquals($expectedStatusCode, $this->getClient()->getResponse()->getStatusCode());
    }
}