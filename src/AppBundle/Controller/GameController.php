<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use AppBundle\Form\CreateGameForm;
use AppBundle\Form\Type\CreateGameFormType;
use AppBundle\Form\GameDetailForm;
use AppBundle\Form\Type\GameDetailFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Llvdl\Domino\GameService;
use Llvdl\Domino\Dto\GameDetailDto;
use Llvdl\Domino\Exception\DominoException;

class GameController extends \AppBundle\Controller\BaseController
{
    /** @var GameService */
    private $gameService;

    const ROUTE_GAME_DETAIL = 'app.game_detail';

    public function __construct(EngineInterface $templating, Router $router, FormFactoryInterface $formFactory, GameService $gameService)
    {
        parent::__construct($templating, $router, $formFactory);
        $this->gameService = $gameService;
    }

    /**
     * @Template("game/game-index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $formObject = new CreateGameForm();
        $form = $this->createForm(CreateGameFormType::class, $formObject);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $gameId = $this->gameService->createGame($formObject->getName());

            return $this->redirectToRoute(self::ROUTE_GAME_DETAIL, ['gameId' => $gameId]);
        }

        $games = $this->gameService->getRecentGames();

        return [
            'form' => $form->createView(),
            'games' => $games,
        ];
    }

    /**
     * @Template("game/game-view.html.twig")
     */
    public function viewAction(Request $request, $gameId)
    {
        try {
            $formObject = new GameDetailForm();
            $form = $this->createForm(GameDetailFormType::class, $formObject);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                if ($request->request->get('deal-game')) {
                    $this->gameService->deal($gameId);

                    return $this->redirectToRoute(self::ROUTE_GAME_DETAIL, ['gameId' => $gameId]);
                }

                // no buttons match
                throw new BadRequestHttpException('could not determine the submit action');
            }

            $gameDetail = $this->gameService->getGameById($gameId);
            if ($gameDetail === null) {
                throw new NotFoundHttpException('Game with given id was not found');
            }

            return [
                'form' => $form->createView(),
                'game' => $gameDetail,
                'canDeal' => $gameDetail->getState() === GameDetailDto::STATE_READY,
            ];
        } catch (DominoException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}
