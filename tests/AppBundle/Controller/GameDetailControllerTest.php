<?php

namespace Tests\AppBundle\Controller;

use Llvdl\Domino\Game;
use Llvdl\Domino\Dto\StoneDto;
use Llvdl\Domino\Dto\PlayDto;
use Llvdl\Domino\Dto\GameDetailDto;
use Llvdl\Domino\Dto\GameDetailDtoBuilder;
use Llvdl\Domino\Exception\DominoException;
use Tests\AppBundle\Controller\Http\StatusCode;
use Symfony\Component\DomCrawler\Crawler;

class GameDetailControllerTest extends MockeryWebTestCase
{
    use Traits\GameServiceExpectationTrait;
    use Traits\StatusCodeAsserterTrait;

    const DEAL_BUTTON_NAME = 'game_detail_form[dealGame]';

    public function testGameDetailReturns404IfNotFound()
    {
        $this->expectForGameById(1, null);

        $crawler = $this->getClient()->request('GET', '/game/1');
        $this->assertStatusCode(StatusCode::NOT_FOUND);
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

    public function testGameDetailPageHasLinkToPlayerPages()
    {
        $game = (new GameDetailDtoBuilder())
            ->id(312)
            ->stateReady()
            ->name('My Game')
            ->addPlayer(1, [])
            ->addPlayer(2, [])
            ->addPlayer(3, [])
            ->addPlayer(4, [])
            ->get();
        $this->expectForGameById(312, $game, null);
        $crawler = $this->openGameDetailPage(312);

        $this->assertEquals('/game/312/player/1', $crawler->filter('#container .players .player a')->eq(0)->attr('href'));
        $this->assertEquals('/game/312/player/2', $crawler->filter('#container .players .player a')->eq(1)->attr('href'));
        $this->assertEquals('/game/312/player/3', $crawler->filter('#container .players .player a')->eq(2)->attr('href'));
        $this->assertEquals('/game/312/player/4', $crawler->filter('#container .players .player a')->eq(3)->attr('href'));
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
        $this->assertStatusCode(StatusCode::CLIENT_ERROR);
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
        $this->assertStatusCode(StatusCode::CLIENT_ERROR);
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

    private function openGameDetailPage($gameId)
    {
        $url = strtr('/game/{gameId}', ['{gameId}' => $gameId]);
        $crawler = $this->getClient()->request('GET', $url);
        $this->assertStatusCode(StatusCode::OK);

        return $crawler;
    }

    private function clickDealButton(Crawler &$crawler)
    {
        $button = $crawler->selectButton(self::DEAL_BUTTON_NAME);
        $this->assertEquals(1, count($button), 'deal button is on page');
        $form = $button->form();
        $crawler = $this->getClient()->submit($form);
        $this->assertStatusCode(StatusCode::MOVED_TEMPORARILY);
        $crawler = $this->getClient()->followRedirect();
        $this->assertStatusCode(StatusCode::OK);
    }

    private function clickActivePlayerDetailLink(Crawler &$crawler)
    {
        $activePlayerLink = $crawler->filter('#container .players .player.active a.detail-link');
        $this->assertEquals(1, count($activePlayerLink), 'active player detail page link is shown');
        $crawler = $this->getClient()->click($activePlayerLink->link());
        $this->assertStatusCode(StatusCode::OK);
    }

    private function assertTitleContains($text, Crawler $crawler)
    {
        $this->assertContains($text, $crawler->filter('#container h1')->text());
    }
}
