<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Form\PlayerForm;
use AppBundle\Form\Type\PlayerFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Llvdl\Domino\Service\GameService;
use AppBundle\Form\Move;
use Llvdl\Domino\Service\Dto\GameDetail;
use Llvdl\Domino\Service\Dto\Player;
use Llvdl\Domino\Service\Dto\Play;
use Llvdl\Domino\Service\Dto\Stone;

class PlayerController extends \AppBundle\Controller\BaseController
{
    /** @var GameService */
    private $gameService;

    const ROUTE_PLAYER_DETAIL = 'app.player_detail';

    public function __construct(EngineInterface $templating, Router $router, FormFactoryInterface $formFactory, GameService $gameService)
    {
        parent::__construct($templating, $router, $formFactory);
        $this->gameService = $gameService;
    }

    public function viewAction(Request $request, $gameId, $playerNumber)
    {
        $game = $this->gameService->getGameById($gameId);
        $player = $game !== null ? $game->getPlayerByNumber($playerNumber) : null;
        if ($game === null || $player === null) {
            throw new NotFoundHttpException('Game and/or player cannot be found');
        }
        $moves = $this->getMoves($player);

        $turnNumber = $game->getCurrentTurn()->getNumber() ?: null;
        $formObject = new PlayerForm($game->getId(), $player->getNumber(), $turnNumber, $moves);
        $form = $this->createForm(PlayerFormType::class, $formObject);

        return $this->handlePlayerForm($request, $form, $game, $player)
            ?: $this->getViewResponse($form, $game, $player);
    }

    /**
     * @param Request       $request
     * @param FormInterface $form
     * @param GameDetailDto $game
     * @param PlayerDto     $player
     *
     * @return Response|null
     */
    private function handlePlayerForm(Request $request, FormInterface $form, GameDetail $game, Player $player)
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('play')->isClicked()) {
                $turnNumber = $form->getData()->getTurnNumber();
                $move = $form->getData()->getMove();
                if ($move && $move->isPlay()) {
                    $play = new Play(
                        $turnNumber,
                        new Stone($move->getStoneTopValue(), $move->getStoneBottomValue()),
                        $move->getSide() === 'left' ? Play::SIDE_LEFT : Play::SIDE_RIGHT
                    );
                    $this->gameService->play($game->getId(), $player->getNumber(), $play);

                    return $this->redirectToRoute(self::ROUTE_PLAYER_DETAIL, [
                        'gameId' => $form->getData()->getGameId(),
                        'playerNumber' => $form->getData()->getPlayerNumber(),
                    ]);
                }
            }
        }

        return;
    }

    /**
     * @param FormInterface $form
     * @param GameDetailDto $game
     * @param PlayerDto     $player
     *
     * @return Response
     */
    private function getViewResponse(FormInterface $form, GameDetail $game, Player $player)
    {
        return $this->render('player/player-view.html.twig', [
            'form' => $form->createView(),
            'game' => $game,
            'player' => $player,
        ]);
    }

    /**
     * @param Player $player
     *
     * @return Move[]
     */
    private function getMoves(Player $player)
    {
        $moves = [Move::pass()];
        foreach ($player->getStones() as $stone) {
            $moves[] = Move::play($stone->getTopValue(), $stone->getBottomValue(), 'left');
            $moves[] = Move::play($stone->getTopValue(), $stone->getBottomValue(), 'right');
        }

        return $moves;
    }
}
