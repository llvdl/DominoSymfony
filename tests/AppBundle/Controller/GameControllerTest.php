<?php

namespace Tests\AppBundle\Controller;

use Llvdl\Domino\Game;
use Llvdl\Domino\Dto\GameDetailDto;
use Llvdl\Domino\Dto\GameDetailDtoBuilder;
use Llvdl\Domino\Exception\DominoException;

class GameControllerTest extends MockeryWebTestCase
{
    public function testIndexNoGamesAreAvailableIsShown()
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getRecentGames')
            ->once()
            ->andReturn([]);

        $crawler = $this->getClient()->request('GET', '/game');

        $this->assertEquals(200, $this->getClient()->getResponse()->getStatusCode());
        $this->assertContains('Games', $crawler->filter('#container h1')->text());
	$this->assertContains('No games are available.', $crawler->filter('#container .game-list')->text(), 'message is shown that there are no games available');
    }

    public function testIndexOneGameIsShown()
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getRecentGames')
            ->once()
            ->andReturn([$this->createGame(123, 'My Game')]);

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

        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getRecentGames')
            ->once()
            ->andReturn($games);

        $crawler = $this->getClient()->request('GET', '/game');

        $this->assertEquals(200, $this->getClient()->getResponse()->getStatusCode());
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
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getGameById')->with(1)
            ->once()
            ->andReturn(null);

        $crawler = $this->getClient()->request('GET', '/game/1');
        $this->assertEquals(404, $this->getClient()->getResponse()->getStatusCode());
    }

    public function testGameDetailShowsDetails()
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getGameById')->with(1)
            ->once()
            ->andReturn((new GameDetailDtoBuilder())
                ->id(1)
                ->stateReady()
                ->name('My Game')
                ->get()
                );

        $crawler = $this->getClient()->request('GET', '/game/1');
        $this->assertEquals(200, $this->getClient()->getResponse()->getStatusCode());

        $this->assertContains('My Game', $crawler->filter('#container h1')->text());
        $this->assertContains('ready', $crawler->filter('#container .game-state')->text());
    }

    public function testGameCanBeCreatedAndReturnsId()
    {
        $gameName = 'My new game ' . uniqid();

        // open game page, which has the create game form
        $crawler = $this->getClient()->request('GET', '/game');

        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('createGame')->with($gameName)
            ->andReturn(42);

        // fill in form and submit
        $form = $crawler->selectButton('create-game')->form();
        $form['create_game_form[name]'] = $gameName;
        $crawler = $this->getClient()->submit($form);

        // check for redirect
        $this->assertEquals(302, $this->getClient()->getResponse()->getStatusCode(), 'A redirect is expected so refreshing will not resubmit the form');

        // follow redirect to game detail page
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getGameById')->with(42)
            ->andReturn((new GameDetailDtoBuilder())
                ->id(42)
                ->stateReady()
                ->name($gameName)
                ->get());

        $crawler = $this->getClient()->followRedirect();
        $this->assertEquals(200, $this->getClient()->getResponse()->getStatusCode());
        $this->assertContains($gameName, $crawler->filter('#container h1')->text());
    }

    public function testGameCanBeDealt()
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getGameById')->with(1)
            ->once()
            ->andReturn((new GameDetailDtoBuilder())
                ->id(1)
                ->stateReady()
                ->get()
            );

        $crawler = $this->getClient()->request('GET', '/game/1');
        $button = $crawler->selectButton('deal-game');
        $this->assertEquals(1, count($button));
        $form = $button->form();

        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('deal')->with(1)
            ->once();

        $crawler = $this->getClient()->submit($form);

        // check for redirect
        $this->assertEquals(302, $this->getClient()->getResponse()->getStatusCode(), 'A redirect is expected so refreshing will not resubmit the form');

        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getGameById')->with(1)
            ->andReturn((new GameDetailDtoBuilder())
                ->id(1)
                ->stateStarted()
                ->get()
            );

        $crawler = $this->getClient()->followRedirect();
        $this->assertEquals(200, $this->getClient()->getResponse()->getStatusCode());
        $button = $crawler->selectButton('deal-game');
        $this->assertEquals(0, count($button), 'deal button not shown');
    }

    public function testGameDetailDealButtonIsNotShownIfAlreadyDealt()
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getGameById')->with(1)
            ->once()
            ->andReturn((new GameDetailDtoBuilder())
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
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getGameById')->with(1)
            ->andReturn((new GameDetailDtoBuilder())->id(1)->stateReady()->get());

        $crawler = $this->getClient()->request('GET', '/game/1');
        $button = $crawler->selectButton('deal-game');

        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('deal')->with(1)
            ->once()
            ->andThrow(new DominoException('cannot start, already started'));

        $form = $button->form();
        $crawler = $this->getClient()->submit($form);
        $this->assertEquals(400, $this->getClient()->getResponse()->getStatusCode());
    }

    public function testGameDealButtonMustBeSubmitted()
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getGameById')->with(1)
            ->andReturn((new GameDetailDtoBuilder())->id(1)->stateReady()->get());

        $crawler = $this->getClient()->request('GET', '/game/1');
        $button = $crawler->selectButton('deal-game');
        $this->assertEquals(1, count($button));
        $form = $button->form();

        // POST the form, but do not submit the submit button
        $this->assertTrue(isset($form['deal-game']));
        unset($form['deal-game']);
        $crawler = $this->getClient()->submit($form);
        $this->assertEquals(400, $this->getClient()->getResponse()->getStatusCode());
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
}
