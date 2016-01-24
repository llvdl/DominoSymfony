<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use AppBundle\Form\GameDetailForm;
use AppBundle\Form\Type\GameDetailFormType;
use Llvdl\Domino\GameService;
use Llvdl\Domino\Dto\GameDetailDto;
use Llvdl\Domino\Exception\DominoException;

class GameDetailController extends \AppBundle\Controller\BaseController
{
    const ROUTE_GAME_DETAIL = 'app.game_detail';

    /** @var GameService */
    private $gameService;

    public function __construct(EngineInterface $templating, Router $router, FormFactoryInterface $formFactory, GameService $gameService)
    {
        parent::__construct($templating, $router, $formFactory);
        $this->gameService = $gameService;
    }

    /**
     * GET /game/{gameId}, POST /game/{gameId}.
     *
     * @return Response
     */
    public function viewAction(Request $request, $gameId)
    {
        try {
            $game = $this->getGameById($gameId);
            $formObject = new GameDetailForm($gameId);
            $formObject->setCanDeal($game->getState() == GameDetailDto::STATE_READY);
            $form = $this->createForm(GameDetailFormType::class, $formObject);

            return $this->handleGameDetailForm($request, $form, $formObject)
                ?: $this->getViewResponse($gameId, $form);
        } catch (DominoException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * @return Response|null
     */
    private function handleGameDetailForm(Request $request, FormInterface $form, GameDetailForm $formObject)
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('dealGame')->isClicked()) {
                $this->gameService->deal($formObject->getGameId());

                return $this->redirectToRoute(self::ROUTE_GAME_DETAIL, ['gameId' => $formObject->getGameId()]);
            }

            throw new BadRequestHttpException('invalid or missing submit action');
        }

        return;
    }

    /**
     * @param int           $gameId
     * @param FormInterface $form
     *
     * @return Response
     */
    private function getViewResponse($gameId, FormInterface $form)
    {
        $gameDetail = $this->getGameById($gameId);

        return $this->render('game/game-view.html.twig', [
            'form' => $form->createView(),
            'game' => $gameDetail,
            'canDeal' => $gameDetail->getState() === GameDetailDto::STATE_READY,
        ]);
    }

    /** 
     * @return GameDetailDto
     *
     * @throws NotFoundHttpException if game was not found
     */
    private function getGameById($gameId)
    {
        $gameDetail = $this->gameService->getGameById($gameId);
        if ($gameDetail === null) {
            throw new NotFoundHttpException('Game cannot be found');
        }

        return $gameDetail;
    }
}
