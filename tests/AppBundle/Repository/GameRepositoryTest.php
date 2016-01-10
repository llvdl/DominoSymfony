<?php

namespace Tests\AppBundle\Repository;

use Doctrine\ORM\Tools\SchemaTool;
use Liip\FunctionalTestBundle\Test\WebTestCase;

use AppBundle\Repository\GameRepository;

use Llvdl\Domino\Game;
use Llvdl\Domino\State;
use Llvdl\Domino\Player;
use Llvdl\Domino\Stone;
use Llvdl\Domino\Turn;

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

        $this->clearEntityManager();

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

    public function testPlayerStonesArePersisted()
    {
        $game0 = new Game('test game #1');
        $this->setGameStones($game0, [1 => [[0,0], [1,0]], 2 => [[1,1], [2,1]], 3 => [[2,2], [2,3]], 4 => [[3,3]]]);
        $this->gameRepository->persistGame($game0);

        $this->clearEntityManager();

        $game = $this->gameRepository->findById($game0->getId());

        $this->assertPlayerHasStones($game->getPlayers()[1], [[0,0], [1,0]]);
        $this->assertPlayerHasStones($game->getPlayers()[2], [[1,1], [2,1]]);
        $this->assertPlayerHasStones($game->getPlayers()[3], [[2,2], [2,3]]);
        $this->assertPlayerHasStones($game->getPlayers()[4], [[3,3]]);
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

    public function testPersistCurrentTurn()
    {
        $game = new Game('my game');
        $game->deal();

        $turn = $game->getCurrentTurn();
        $this->assertNotNull($turn);

        $this->gameRepository->persistGame($game);
        $gameId = $game->getId();

        $this->clearEntityManager();

        $persistedGame = $this->gameRepository->findById($gameId);
        $this->assertSameTurn($turn, $persistedGame->getCurrentTurn());
    }

    /**
     * helper function to assign stones to players
     *
     * @param Game $game
     * @param array $playerStones array of stone values (first value is top value, second value
     *      is bottom values). The array keys are player numbers
     */
    private function setGameStones(Game $game, array $playerStones) {
        $players = $game->getPlayers();
        foreach($playerStones as $playerNumber=>$stoneValues)
        {
            $stones = array_map(function(array $values) { return new Stone($values[0], $values[1]); }, $stoneValues);
            $players[$playerNumber]->addStones($stones);
        }
    }

    private function assertPlayerHasStones(Player $player, array $stoneValues)
    {

        $stones = $player->getStones();

        $this->assertEquals(count($stoneValues), count($stones), 'player should have ' . count($stoneValues) . ' stone(s)');

        reset($stoneValues);
        foreach($stones as $stone) {
            $values = current($stoneValues);
            $topValue = $values[0];
            $bottomValue = $values[1];

            $this->assertEquals($topValue, $stone->getTopValue());
            $this->assertEquals($bottomValue, $stone->getBottomValue());
            next($stoneValues);
        }
    }

    private function assertSameTurn(Turn $expected, Turn $turn)
    {
        $this->assertTrue($expected->isEqual($turn));
    }

    /**
     * clears the entity manager to prevent the entity manager from returning cached objects
     */
    private function clearEntityManager()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->clear();
    }

}