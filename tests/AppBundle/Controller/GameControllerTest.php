<?php

namespace Tests\AppBundle\Controller;

use Llvdl\Domino\Game;
use Llvdl\Domino\Dto\StoneDto;
use Llvdl\Domino\Dto\PlayDto;
use Llvdl\Domino\Dto\GameDetailDto;
use Llvdl\Domino\Dto\GameDetailDtoBuilder;
use Llvdl\Domino\Exception\DominoException;

use Symfony\Component\DomCrawler\Crawler;

class GameControllerTest extends MockeryWebTestCase
{
    const STATUS_CODE_OK = 200;
    const STATUS_CODE_TEMPORARY_REDIRECT = 302;
    const STATUS_CODE_CLIENT_ERROR = 400;
    const STATUS_CODE_NOT_FOUND = 404;

    const DEAL_BUTTON_NAME = 'game_detail_form[dealGame]';
    const CREATE_GAME_BUTTON_NAME = 'create_game_form_create';
    const PLAY_BUTTON_NAME = 'player_form[play]';

    public function testIndexNoGamesAreAvailableIsShown()
    {
        $this->expectForRecentGames([]);

        $crawler = $this->openGameIndexPage();

        $this->assertTitleContains('Games', $crawler);
        $this->assertContains('No games are available.', $crawler->filter('#container .game-list')->text(), 'message is shown that there are no games available');
    }

    public function testIndexOneGameIsShown()
    {
        $this->expectForRecentGames([$this->createGame(123, 'My Game')]);

        $crawler = $this->openGameIndexPage();

        $this->assertTitleContains('Games', $crawler);
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

        $crawler = $this->openGameIndexPage();

        $this->assertTitleContains('Games', $crawler);
        $this->assertEquals(count($games), $crawler->filter('#container .game-list ul li')->count(), 'all games available');

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
        $this->expectForGameById(1, $game, null);

        $crawler = $this->openGameDetailPage(1);

        $this->assertTitleContains('My Game', $crawler);
        $this->assertContains('ready', $crawler->filter('#container .game-state')->text());
        $this->assertNotEmpty($crawler->filter('#container h2'), 'header "Players" is shown');
        $this->assertContains('Players', $crawler->filter('#container h2')->text());
        $this->assertCount(4, $crawler->filter('#container .players .player'));
    }

    public function testGameCanBeCreatedAndReturnsId()
    {
        $gameName = 'My new game ' . uniqid();
        $gameBeforeCreated = null;
        $gameAfterCreated = (new GameDetailDtoBuilder())->id(42)->stateReady()->name($gameName)->get();
        $gameCreated = false;

        $this->expectForGameById(42, function() use(&$gameCreated, $gameBeforeCreated, $gameAfterCreated) {
            return $gameCreated ? $gameAfterCreated : $gameBeforeCreated;
        }, null);
        $this->expectCreateGame($gameName, function() use (&$gameCreated) {
                $gameCreated = true;
                return 42;
        });
        $this->expectForRecentGames([]);

        // open game page, which has the create game form
        $crawler = $this->openGameIndexPage();
        $this->clickCreateGameButton($crawler, $gameName);
        $this->assertContains($gameName, $crawler->filter('#container h1')->text(), 'game name is shown in title');
    }

    public function testGameCanBeDealt()
    {
        $gameBeforeDeal = (new GameDetailDtoBuilder())->id(1)->stateReady()->get();
        $gameAfterDeal = (new GameDetailDtoBuilder())->id(1)->stateStarted()->get();
        $gameDealt = false;
        $this->expectForGameById(1,function() use (&$gameDealt, $gameBeforeDeal, $gameAfterDeal) {
            return $gameDealt ? $gameAfterDeal : $gameBeforeDeal;
        }, null);
        $this->expectForDeal(1, function() use (&$gameDealt) { 
            $gameDealt = true;
        });

        $crawler = $this->openGameDetailPage(1);
        $this->clickDealButton($crawler);

        $button = $crawler->selectButton(self::DEAL_BUTTON_NAME);
        $this->assertEquals(0, count($button), 'deal button not shown after game is started');
    }

    public function testGameDetailDealButtonIsNotShownIfAlreadyStarted()
    {
        $game = (new GameDetailDtoBuilder())->id(1)->stateStarted()->get();
        $this->expectForGameById(1, $game, null);

        $crawler = $this->openGameDetailPage(1);

        $button = $crawler->selectButton(self::DEAL_BUTTON_NAME);
        $this->assertEquals(0, count($button), 'deal button is not shown');
    }

    public function testCannotDealGameTwice()
    {
        // a stale form may be submitted to deal the game even if it has already been dealt
        $this->expectForGameById(1, (new GameDetailDtoBuilder())->id(1)->stateReady()->get(), null);

        $crawler = $this->openGameDetailPage(1);

        $this->expectExceptionForGameById(1, new DominoException('cannot start, already started'));

        $button = $crawler->selectButton(self::DEAL_BUTTON_NAME);
        $form = $button->form();
        $crawler = $this->getClient()->submit($form);
        $this->assertStatusCode(self::STATUS_CODE_CLIENT_ERROR);
    }

    public function testGameDealButtonMustBeSubmitted()
    {
        $this->expectForGameById(1, (new GameDetailDtoBuilder())->id(1)->stateReady()->get(), null);

        $crawler = $this->openGameDetailPage(1);

        $button = $crawler->selectButton(self::DEAL_BUTTON_NAME);
        $this->assertEquals(1, count($button));
        $form = $button->form();

        // POST the form, but do not submit the submit button
        $this->assertTrue(isset($form[self::DEAL_BUTTON_NAME]));
        unset($form[self::DEAL_BUTTON_NAME]);
        $crawler = $this->getClient()->submit($form);
        $this->assertStatusCode(self::STATUS_CODE_CLIENT_ERROR);
    }

    public function testAfterGameDealAllPlayersHaveSevenStones()
    {
        $gameBeforeDeal = (new GameDetailDtoBuilder())->id(1)->stateReady()->get();
        $shuffler = new StoneShuffler();
        $gameAfterDeal = (new GameDetailDtoBuilder())
            ->id(1)
            ->stateStarted()
            ->addPlayer(1, $shuffler->getNext(7))
            ->addPlayer(2, $shuffler->getNext(7))
            ->addPlayer(3, $shuffler->getNext(7))
            ->addPlayer(4, $shuffler->getNext(7))
            ->get();

        $gameDealt = false;
        $this->expectForGameById(1, function() use (&$gameDealt, $gameBeforeDeal, $gameAfterDeal) {
            return $gameDealt ? $gameAfterDeal : $gameBeforeDeal;
        }, null);
        $this->expectForDeal(1, function() use (&$gameDealt) { $gameDealt = true; });

        $crawler = $this->openGameDetailPage(1);
        $this->clickDealButton($crawler);

        $this->assertContains('7', $crawler->filter('#container .players .player .stone-count')->eq(0)->text());
        $this->assertContains('7', $crawler->filter('#container .players .player .stone-count')->eq(0)->text());
        $this->assertContains('7', $crawler->filter('#container .players .player .stone-count')->eq(0)->text());
        $this->assertContains('7', $crawler->filter('#container .players .player .stone-count')->eq(0)->text());
    }

    public function testAfterGameDealTurnNumberIsOneAndAPlayerHasTurn()
    {
        $gameDto = (new GameDetailDtoBuilder())
                ->id(1)
                ->stateStarted()
                ->turn(1, 3)
                ->get();
        $this->expectForGameById(1, $gameDto, null);

        $crawler = $this->openGameDetailPage(1);

        $this->assertContains('1', $crawler->filter('#container .current-turn .turn-number')->text());
        $this->assertContains('3', $crawler->filter('#container .current-turn .player-number')->text());
    }

    public function testDoubleSixCanBePlayedAsFirstMove()
    {
        $shuffler = new StoneShuffler();
        $shuffler->setStoneAtPosition([6,6], 1);
        $game = (new GameDetailDtoBuilder())
            ->id(1)
            ->stateStarted()
            ->addPlayer(1, $shuffler->getNext(7))
            ->addPlayer(2, $shuffler->getNext(7))
            ->addPlayer(3, $shuffler->getNext(7))
            ->addPlayer(4, $shuffler->getNext(7))
            ->turn(1, 1)
            ->get();

        $this->expectForGameById(1, $game, null);
        $this->expectForPlay($game->getId(), 1, new PlayDto(1, new StoneDto(6,6), PlayDto::SIDE_LEFT));

        $crawler = $this->openGameDetailPage(1);

        $this->clickActivePlayerDetailLink($crawler);
        $this->clickPlayButton($crawler, '6_6-left');

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
    private function expectForRecentGames(array $result, $count = 1)
    {
        $expectation = $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getRecentGames')
            ->andReturn($result);

        if($count !== null) {
            $expectation->times($count);
        }
    }

    /** @param integer $expectedStatusCode */
    private function assertStatusCode($expectedStatusCode)
    {
        $this->assertEquals($expectedStatusCode, $this->getClient()->getResponse()->getStatusCode());
    }

    private function assertTitleContains($text, Crawler $crawler)
    {
        $this->assertContains($text, $crawler->filter('#container h1')->text());
    }

    /**
     * @param integer $id
     * @param GameDetailDto|callback|NULL $result
     * @param bool $isOnce TRUE if call is expected exactly once
     */
    private function expectForGameById($id, $result, $count = 1)
    {
        $expectation = $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('getGameById')->with($id);

        if(is_callable($result)) {
            $expectation->andReturnUsing($result);
        } else {
            $expectation->andReturn($result);
        }

        if($count !== null) {
            $expectation->times($count);
        }
    }

    /**
     * @param string $gameName
     * @param integer $result game id result
     */
    private function expectCreateGame($gameName, $result)
    {
        $expectation = $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('createGame')->with($gameName);

        if(is_callable($result)) {
            $expectation->andReturnUsing($result);
        } else {
            $expectation->andReturn($result);
        }
    }

    /** @param integer $gameId */
    private function expectForDeal($gameId, $callback = null)
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('deal')
            ->with($gameId)
            ->once()
            ->andReturnUsing(function() use($callback) {
                if($callback) {
                    $callback();
                }
                return;
            });
    }

    /**
     * @param integer $id gameId
     * @param Exception $e
     */
    private function expectExceptionForGameById($gameId, \Exception $e)
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('deal')
            ->with($gameId)
            ->once()
            ->andThrow($e);
    }

    private function expectForPlay($gameId, $playerId, PlayDto $play, callable $callback = null)
    {
        $this->getClient()->getContainer()->mock('app.game_service', 'Llvdl\Domino\GameService')
            ->shouldReceive('play')
            ->with($gameId, $playerId, \Mockery::on(function($p) use($play) {
                return $p instanceof PlayDto
                    && $p->isEqual($play);
            }))
            ->once()
            ->andReturnUsing(function() use($callback) {
                if($callback) {
                    $callback();
                }
                return;
            });
    }

    /** @return Crawler */
    private function openGameIndexPage()
    {
        $crawler = $this->getClient()->request('GET', '/game');
        $this->assertEquals(200, $this->getClient()->getResponse()->getStatusCode());

        return $crawler;
    }

    private function openGameDetailPage($gameId)
    {
        $url = strtr('/game/{gameId}', ['{gameId}' => $gameId]);
        $crawler = $this->getClient()->request('GET', $url);
        $this->assertStatusCode(self::STATUS_CODE_OK);

        return $crawler;
    }

    private function clickCreateGameButton(Crawler &$crawler, $gameName)
    {
        $form = $crawler->selectButton(self::CREATE_GAME_BUTTON_NAME)->form();
        $form['create_game_form[name]'] = $gameName;
        $crawler = $this->getClient()->submit($form);

        $this->assertStatusCode(self::STATUS_CODE_TEMPORARY_REDIRECT);
        $crawler = $this->getClient()->followRedirect();
        $this->assertStatusCode(self::STATUS_CODE_OK);
    }

    private function clickDealButton(Crawler &$crawler)
    {
        $button = $crawler->selectButton(self::DEAL_BUTTON_NAME);
        $this->assertEquals(1, count($button), 'deal button is on page');
        $form = $button->form();
        $crawler = $this->getClient()->submit($form);
        $this->assertStatusCode(self::STATUS_CODE_TEMPORARY_REDIRECT);
        $crawler = $this->getClient()->followRedirect();
        $this->assertStatusCode(self::STATUS_CODE_OK);
    }

    private function clickActivePlayerDetailLink(Crawler &$crawler)
    {
        $activePlayerLink = $crawler->filter('#container .players .player.active a.detail-link');
        $this->assertEquals(1, count($activePlayerLink), 'active player detail page link is shown');
        $crawler = $this->getClient()->click($activePlayerLink->link());
        $this->assertStatusCode(self::STATUS_CODE_OK);
    }

    private function clickPlayButton(Crawler &$crawler, $moveValue)
    {
        $button = $crawler->selectButton(self::PLAY_BUTTON_NAME);
        $this->assertEquals(1, count($button), 'play button is shown');
        $form = $button->form(['player_form[move]' => $moveValue]);
        $crawler= $this->getClient()->submit($form);

        $this->assertStatusCode(self::STATUS_CODE_TEMPORARY_REDIRECT);
        $this->getClient()->followRedirect();
        $this->assertStatusCode(self::STATUS_CODE_OK);
    }
}
