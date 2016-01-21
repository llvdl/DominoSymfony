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

class PlayerControllerTest extends MockeryWebTestCase
{
    use Traits\GameServiceExpectationTrait;
    use Traits\StatusCodeAsserterTrait;

    const PLAY_BUTTON_NAME = 'player_form[play]';

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

        $crawler = $this->openPlayerPage(1, 1);
        $this->clickPlayButton($crawler, '6_6-left');
    }

    /**
     * @param int $gameId
     * @param int $playerNumber
     * @return Crawler
     */
    private function openPlayerPage($gameId, $playerNumber)
    {
        $url = strtr(
            '/game/{gameId}/player/{playerNumber}', 
            ['{gameId}'=>$gameId, '{playerNumber}'=>$playerNumber]
        );
        $crawler = $this->getClient()->request('GET', $url);
        $this->assertStatusCode(StatusCode::OK);
        $this->assertEquals(200, $this->getClient()->getResponse()->getStatusCode());

        return $crawler;
    }

    /**
     * @param Crawler $crawler
     * @param string $moveValue
     */
    private function clickPlayButton(Crawler &$crawler, $moveValue)
    {
        $button = $crawler->selectButton(self::PLAY_BUTTON_NAME);
        $this->assertEquals(1, count($button), 'play button is shown');
        $form = $button->form(['player_form[move]' => $moveValue]);
        $crawler= $this->getClient()->submit($form);

        $this->assertStatusCode(StatusCode::MOVED_TEMPORARILY);
        $this->getClient()->followRedirect();
        $this->assertStatusCode(StatusCode::OK);
    }
}
