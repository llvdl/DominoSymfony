<?php

namespace Tests\AppBundle\Controller;

use Llvdl\Domino\Service\Dto;
use Llvdl\Domino\Domain\Exception\DominoException;
use Tests\AppBundle\Controller\Http\StatusCode;
use Symfony\Component\DomCrawler\Crawler;

class GameOverviewControllerTest extends MockeryWebTestCase
{
    use Traits\GameServiceExpectationTrait;
    use Traits\StatusCodeAsserterTrait;

    const CREATE_GAME_BUTTON_NAME = 'create_game_form_create';

    public function testIndexNoGamesAreAvailableIsShown()
    {
        $this->expectForRecentGames([]);

        $crawler = $this->openGameIndexPage();

        $this->assertTitleContains('Games', $crawler);
        $this->assertGameOverListIsEmpty($crawler);
    }

    public function testIndexOneGameIsShown()
    {
        $this->expectForRecentGames([$this->createGame(123, 'My Game')]);

        $crawler = $this->openGameIndexPage();

        $this->assertTitleContains('Games', $crawler);
        $this->assertEquals(1, $crawler->filter('#container .game-list ul li')->count(), 'one game available');
        $this->assertGameIsInOverviewList(123, 'My Game', $crawler);
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
        foreach($games as $game) {
            $this->assertGameIsInOverviewList($game->getId(), $game->getName(), $crawler);
        }
    }

    public function testGameCanBeCreatedAndReturnsId()
    {
        $gameName = 'My new game ' . uniqid();
        $gameBeforeCreated = null;
        $gameAfterCreated = (new Dto\GameDetailBuilder())->id(42)->stateReady()->name($gameName)->get();
        $gameCreated = false;

        $this->expectForGameById(42, function() use(&$gameCreated, $gameBeforeCreated, $gameAfterCreated) {
            return $gameCreated ? $gameAfterCreated : $gameBeforeCreated;
        }, null);
        $this->expectCreateGame($gameName, function() use (&$gameCreated) {
                $gameCreated = true;
                return 42;
        });
        $this->expectForRecentGames([]);

        $crawler = $this->openGameIndexPage();
        $this->assertGameIsNotInOverviewList(42, $gameName, $crawler);
        $this->clickCreateGameButton($crawler, $gameName);
        $this->assertEquals('/game/42', $this->getClient()->getRequest()->getPathInfo(), 'we have been redirected to game detail page');
    }

    /**
     * @param int $id
     * @param string $name
     * @return Dto\GameDetail
     */
    private function createGame($id, $name)
    {
        $GameDetail = new Dto\GameDetail($id, $name, null, [], []);
        return $GameDetail;
    }

    /** @return Crawler */
    private function openGameIndexPage()
    {
        $crawler = $this->getClient()->request('GET', '/game');
        $this->assertEquals(200, $this->getClient()->getResponse()->getStatusCode());

        return $crawler;
    }

    private function clickCreateGameButton(Crawler &$crawler, $gameName)
    {
        $form = $crawler->selectButton(self::CREATE_GAME_BUTTON_NAME)->form();
        $form['create_game_form[name]'] = $gameName;
        $crawler = $this->getClient()->submit($form);

        $this->assertStatusCode(StatusCode::MOVED_TEMPORARILY);
        $crawler = $this->getClient()->followRedirect();
        $this->assertStatusCode(StatusCode::OK);
    }

    private function assertTitleContains($text, Crawler $crawler)
    {
        $this->assertContains($text, $crawler->filter('#container h1')->text());
    }

    private function assertGameOverListIsEmpty(Crawler $crawler)
    {
        $this->assertEquals(0, $crawler->filter('#container .game-list ul li')->count(), 'game overview list is empty');
        $this->assertContains('No games are available.', $crawler->filter('#container .game-list')->text(), 'message is shown that there are no games available');
    }

    private function assertGameIsInOverviewList($gameId, $gameName, Crawler $crawler)
    {
        $message = 'game with id '.$gameId . ' and name "' . $gameName . '" is in the game overview list';
        $this->assertTrue($this->isInOverViewList($gameId, $gameName, $crawler), $message);
    }

    private function assertGameIsNotInOverviewList($gameId, $gameName, Crawler $crawler)
    {
        $message = 'game with id '.$gameId . ' and name "' . $gameName . '" is not in the game overview list';
        $this->assertFalse($this->isInOverViewList($gameId, $gameName, $crawler));
    }

    /**
     * @param int $gameId
     * @param string $gameName
     * @param Crawler $crawler
     * @return bool 
     */
    private function isInOverViewList($gameId, $gameName, Crawler $crawler) {
        $gameWithIdNode = $crawler->filter('#container .game-list ul li a[href = "/game/'.$gameId.'"]');
        return count($gameWithIdNode) === 0 ? false : strstr($gameName, $gameWithIdNode->text()) !== false;
    }
}
