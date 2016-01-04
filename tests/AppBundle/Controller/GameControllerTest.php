<?php

namespace Tests\AppBundle\Controller;

use Llvdl\Domino\Game;
use Llvdl\Domino\Dto\GameDetailDto;
use Llvdl\Domino\Dto\GameDetailDtoBuilder;
use Llvdl\Domino\Exception\DominoException;

class GameControllerTest extends MockeryWebTestCase
{
    const STATUS_CODE_OK = 200;
    const STATUS_CODE_TEMPORARY_REDIRECT = 302;
    const STATUS_CODE_CLIENT_ERROR = 400;
    const STATUS_CODE_NOT_FOUND = 404;

    public function testIndexNoGamesAreAvailableIsShown()
    {
        $this->expectForRecentGames([]);

        $crawler = $this->getClient()->request('GET', '/game');

        $this->assertEquals(200, $this->getClient()->getResponse()->getStatusCode());
        $this->assertContains('Games', $crawler->filter('#container h1')->text());
        $this->assertContains('No games are available.', $crawler->filter('#container .game-list')->text(), 'message is shown that there are no games available');
    }

    public function testIndexOneGameIsShown()
    {
        $this->expectForRecentGames([$this->createGame(123, 'My Game')]);

        $crawler = $this->getClient()->request('GET', '/game');

        $this->assertEquals(200, $this->getClient()->getResponse()->getStatusCode());
        $this->assertContains('Games', $crawler->filter('#container h1')->text());
        $this->assertEquals(1, $crawler->filter('#container .game-list ul li')->count(), 'one game available');
        $this->assertContains('My Game', $crawler->filter('#container .game-list ul li a')->text());
        $this->assertContains('/game/123', $crawler->filter('#container .game-list ul li a')->attr('href'));
    }


    public function testIndexManyGamesAreShown()
    {
        $games = [
                $this->createGame(123, 'My Game 1'),
                $this->createGame(345, 'My Game 2'),
                $this->createGame(567, 'My Game 3'),
                $this->createGame(789, 'My Game 4')
        ];

        $this->expectForRecentGames($games);

        $crawler = $this->getClient()->request('GET', '/game');

        $this->assertStatusCode(self::STATUS_CODE_OK);
        $this->assertContains('Games', $crawler->filter('#container h1')->text());
        $this->assertEquals(count($games), $crawler->filter('#container .game-list ul li')->count(), 'one game available');

        for($i = 0; $i < count($games); ++$i) {
            $link = $crawler->filter('#container .game-list ul li')->eq($i)->filter('a');
            $this->assertContains($games[$i]->getName(), $link->text());
            $this->assertContains('game/'.$games[$i]->getId(), $link->attr('href'));
        }
    }

    public function testGameDetailReturns404IfNotFound()
    {
        $this->expectForGameById(1, null);

        $crawler = $this->getClient()->request('GET', '/game/1');
        $this->assertStatusCode(self::STATUS_CODE_NOT_FOUND);
    }

    public function testGameDetailShowsDetails()
    {
        $game = (new GameDetailDtoBuilder())
            ->id(1)
            ->stateReady()
            ->name('My Game')
            ->addPlayer(1, [])
            ->addPlayer(2, [])
            ->addPlayer(3, [])
            ->addPlayer(4, [])
            ->get();
        $this->expectForGameById(1, $game);

        $crawler = $this->getClient()->request('GET', '/game/1');
        $this->assertStatusCode(self::STATUS_CODE_OK);

        $this->assertContains('My Game', $crawler->filter('#container h1')->text());
        $this->assertContains('ready', $crawler->filter('#container .game-state')->text());
        $this->assertNotEmpty($crawler->filter('#container h2'), 'header "Players" is shown');
        $this->assertContains('Players', $crawler->filter('#container h2')->text());
        $this->assertCount(4, $crawler->filter('#container .players .player'));
    }

    public function testGameCanBeCreatedAndReturnsId()
    {
        $gameName = 'My new game ' . uniqid();

        // open game page, which has the create game form
        $crawler = $this->getClient()->request('GET', '/game');

        $this->expectCreateGame($gameName, 42);

        // fill in form and submit
        $form = $crawler->selectButton('create-game')->form();
        $form['create_game_form[name]'] = $gameName;
        $crawler = $this->getClient()->submit($form);

        $this->assertStatusCode(self::STATUS_CODE_TEMPORARY_REDIRECT);

        // follow redirect to game detail page
        $this->expectForGameById(42,
            (new GameDetailDtoBuilder())->id(42)->stateReady()->name($gameName)->get()
        );

        $crawler = $this->getClient()->followRedirect();
        $this->assertStatusCode(self::STATUS_CODE_OK);
        $this->assertContains($gameName, $crawler->filter('#container h1')->text());
    }

    public function testGameCanBeDealt()
    {
        $this->expectForGameById(1,
            (new GameDetailDtoBuilder())
            ->id(1)
            ->stateReady()
            ->get()
        );

        $crawler = $this->getClient()->request('GET', '/game/1');
        $button = $crawler->selectButton('deal-game');
        $this->assertEquals(1, count($button));
        $form = $button->form();

        $this->expectForDeal(1);

        $crawler = $this->getClient()->submit($form);

        // check for redirect
        $this->assertStatusCode(self::STATUS_CODE_TEMPORARY_REDIRECT);

        $this->expectForGameById(1,
            (new GameDetailDtoBuilder())
                ->id(1)
                ->stateStarted()
                ->get()
        );

        $crawler = $this->getClient()->followRedirect();
        $this->assertStatusCode(self::STATUS_CODE_OK);
        $button = $crawler->selectButton('deal-game');
        $this->assertEquals(0, count($button), 'deal button not shown');
    }

    public function testGameDetailDealButtonIsNotShownIfAlreadyDealt()
    {
        $this->expectForGameById(1,
            (new GameDetailDtoBuilder())
                ->id(1)
                ->stateStarted()
                ->get()
        );

        $crawler = $this->getClient()->request('GET', '/game/1');
        $button = $crawler->selectButton('deal-game');
        $this->assertEquals(0, count($button));
    }

    public function testCannotDealGameTwice()
    {
        // a stale form may be submitted to deal the game even if it has already been dealt
        $this->expectForGameById(1, (new GameDetailDtoBuilder())->id(1)->stateReady()->get());

        $crawler = $this->getClient()->request('GET', '/game/1');
        $button = $crawler->selectButton('deal-game');

        $this->expectExceptionForGameById(1, new DominoException('cannot start, already started'));

        $form = $button->form();
        $crawler = $this->getClient()->submit($form);
        $this->assertStatusCode(self::STATUS_CODE_CLIENT_ERROR);
    }

    public function testGameDealButtonMustBeSubmitted()
    {
        $this->expectForGameById(1, (new GameDetailDtoBuilder())->id(1)->stateReady()->get());

        $crawler = $this->getClient()->request('GET', '/game/1');
        $button = $crawler->selectButton('deal-game');
        $this->assertEquals(1, count($button));
        $form = $button->form();

        // POST the form, but do not submit the submit button
        $this->assertTrue(isset($form['deal-game']));
        unset($form['deal-game']);
        $crawler = $this->getClient()->submit($form);
        $this->assertStatusCode(self::STATUS_CODE_CLIENT_ERROR);
    }

    public function testAfterGameDealAllPlayersHaveSevenStones()
    {
        $this->expectForGameById(1, (new GameDetailDtoBuilder())->id(1)->stateReady()->get());

        $crawler = $this->getClient()->request('GET', '/game/1');
        $button = $crawler->selectButton('deal-game');
        $this->assertEquals(1, count($button));

        $this->expectForDeal(1);

        $shuffler = new StoneShuffler();
        $this->expectForGameById(1, (
            new GameDetailDtoBuilder())->id(1)->stateStarted()
            ->addPlayer(1, $shuffler->getNext(7))
            ->addPlayer(1, $shuffler->getNext(7))
            ->addPlayer(1, $shuffler->getNext(7))
            ->addPlayer(1, $shuffler->getNext(7))
            ->get());

        $form = $button->form();
        $crawler = $this->getClient()->submit($form);
        $crawler = $this->getClient()->followRedirect();

        $this->assertStatusCode(self::STATUS_CODE_OK);

        $this->assertContains('7', $crawler->filter('#container .players .player .stone-count')->eq(0)->text());
        $this->assertContains('7', $crawler->filter('#container .players .player .stone-count')->eq(0)->text());
        $this->assertContains('7', $crawler->filter('#container .players .player .stone-count')->eq(0)->text());
        $this->assertContains('7', $crawler->filter('#container .players .player .stone-count')->eq(0)->text());
    }


    private function createGame($id, $name)
    {
        $game = new Game($name);
        $this->setPrivateProperty($game, 'id', $id);
        return $game;
    }

    private function setPrivateProperty($obj, $name, $value)
    {
        $property = new \reflectionproperty(get_class($obj), $name);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }

    /** @param GameDetailDto[] $result */
    private function expectForRecentGames(array $result)
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getRecentGames')
            ->once()
            ->andReturn($result);
    }

    /** @param integer $expectedStatusCode */
    private function assertStatusCode($expectedStatusCode)
    {
        $this->assertEquals($expectedStatusCode, $this->getClient()->getResponse()->getStatusCode());
    }

    /**
     * @param integer $id
     * @param GameDetailDto|NULL $result
     */
    private function expectForGameById($id, $result)
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getGameById')->with($id)
            ->once()
            ->andReturn($result);
    }

    /**
     * @param string $gameName
     * @param integer $result game id result
     */
    private function expectCreateGame($gameName, $result)
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('createGame')->with($gameName)
            ->andReturn($result);
    }

    /** @param integer $gameId */
    private function expectForDeal($gameId)
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('deal')->with($gameId)
            ->once();
    }

    /**
     * @param integer $id gameId
     * @param Exception $e
     */
    private function expectExceptionForGameById($gameId, \Exception $e)
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('deal')->with($gameId)
            ->once()
            ->andThrow($e);
    }
}
