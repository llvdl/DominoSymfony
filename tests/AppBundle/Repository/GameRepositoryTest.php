<?php

namespace Tests\AppBundle\Repository;

use Doctrine\ORM\Tools\SchemaTool;
use Liip\FunctionalTestBundle\Test\WebTestCase;

use AppBundle\Repository\GameRepository;

use Llvdl\Domino\Game;
use Llvdl\Domino\State;

class GameRepositoryTest extends WebTestCase
{
    /** @var GameRepository */
    private $gameRepository;

    public function setUp()
    {
        parent::setUp();

        $em = $this->getContainer()->get('doctrine')->getManager();
        if (!isset($metadatas)) {
            $metadatas = $em->getMetadataFactory()->getAllMetadata();
        }
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        if (!empty($metadatas)) {
            $schemaTool->createSchema($metadatas);
        }
        $this->postFixtureSetup();

        $fixtures = [];
        $this->loadFixtures($fixtures);
        $this->gameRepository = new GameRepository($this->getContainer()->get('doctrine'));
    }

    public function testPersistNewGame()
    {
        $game = new Game('test game #1');
        $this->assertNull($game->getId());

        $this->gameRepository->persistGame($game);
        $this->assertNotNull($game->getId());
        $this->assertEquals('test game #1', $game->getName());
        $this->assertCount(4, $game->getPlayers());
    }

    public function testPersistExistingGame()
    {
        $game = new Game('test game #1');
        $this->assertNull($game->getId());

        $this->gameRepository->persistGame($game);
        $id = $game->getId();

        $game->setName('test game #2');
        $this->gameRepository->persistGame($game);

        $this->assertNotNull($game->getId());
        $this->assertSame($id, $game->getId(), 'game still has same id');
        $this->assertSame('test game #2', $game->getName());
    }

    public function testPersistNewGameState()
    {
        // create and persist game with state "ready"
        $game0 = new Game('test game');
        $this->assertTrue($game0->getState()->isEqual(new State(State::READY)));
        $this->gameRepository->persistGame($game0);

        // create and persist game with state "ready"
        $game1 = new Game('test game');
        $this->assertTrue($game1->getState()->isEqual(new State(State::READY)));
        $this->gameRepository->persistGame($game1);

        // change state to "started" and persist
        $game2 = $this->gameRepository->findById($game1->getId());
        $game2->deal();
        $this->assertTrue($game2->getState()->isEqual(new State(State::STARTED)));
        $this->gameRepository->persistGame($game2);

        // assert that the state is "started" when reloading the game
        $game3 = $this->gameRepository->findById($game1->getId());
        $this->assertTrue($game3->getState()->isEqual(new State(State::STARTED)));

        // assert that unchanged game still has state "ready" when reloading the game
        $game4 = $this->gameRepository->findById($game0->getId());
        $this->assertTrue($game4->getState()->isEqual(new State(State::READY)));
    }

    public function testFindByIdNotFoundReturnsNull()
    {
        $game = $this->gameRepository->findById(123);
        $this->assertNull($game);
    }

    public function testFindById()
    {
        $game = $this->gameRepository->findById(123);
        $this->assertNull($game);

        // persist games, store ids to look up games
        $names = [
            'test game #1-'.uniqid(), 
            'test game #2-'.uniqid(), 
            'test game #3-'.uniqid()
        ];
        $gameNamesById = [];
        foreach($names as $name) {
            $game = new Game($name);
            $this->gameRepository->persistGame($game);
            $this->assertNotNull($game->getId());
            $gameNamesById[$game->getId()] = $name;
        }
        $this->assertCount(count($names), $gameNamesById);

        // find games by id and check content
        foreach($gameNamesById as $id => $name)
        {
            $game = $this->gameRepository->findById($id);
            $this->assertNotNull($game);
            $this->assertSame($id, $game->getId());
            $this->assertSame($name, $game->getName());
            $this->assertCount(4, $game->getPlayers());
        }
    }

    public function testGetRecentGamesEmpty()
    {
        $this->assertSame([], $this->gameRepository->getRecentGames());
    }

    public function testGetRecentGamesSome()
    {
        $names = [
            'test game #1-'.uniqid(), 
            'test game #2-'.uniqid(), 
            'test game #3-'.uniqid()
        ];
        $gameNamesById = [];
        foreach($names as $name) {
            $game = new Game($name);
            $this->gameRepository->persistGame($game);
        }

        $games = $this->gameRepository->getRecentGames();
        $this->assertCount(3, $games);
    }

}