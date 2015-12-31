<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MockeryWebTestCase extends WebTestCase
{
    private $client;

    public static function setUpBeforeClass()
    {
        // disallow mocking of non existent methods, so when an interface changes tests will more likely fail
        \Mockery::getConfiguration()->allowMockingNonExistentMethods(false);
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    public function tearDown()
    {
       foreach ($this->client->getContainer()->getMockedServices() as $id => $service) {
            $this->client->getContainer()->unmock($id);
        }

        \Mockery::close();

        $this->client = null;

        parent::tearDown();
    }

    protected function getClient()
    {
        return $this->client;
    }
}