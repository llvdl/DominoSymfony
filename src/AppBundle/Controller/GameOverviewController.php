<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\CreateGameForm;
use AppBundle\Form\Type\CreateGameFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\FormInterface;
use Llvdl\Domino\GameService;

class GameOverviewController extends \AppBundle\Controller\BaseController
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
     * GET /game, POST /game.
     * 
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $formObject = new CreateGameForm();
        $form = $this->createForm(CreateGameFormType::class, $formObject);

        return $this->handleCreateGameForm($request, $form, $formObject)
            ?: $this->getIndexResponse($form);
    }

    /**
     * @param Request       $request
     * @param FormInterface $form
     *
     * @return Response|null
     */
    private function handleCreateGameForm(Request $request, FormInterface $form, CreateGameForm $formObject)
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $gameId = $this->gameService->createGame($formObject->getName());

            return $this->redirectToRoute(self::ROUTE_GAME_DETAIL, ['gameId' => $gameId]);
        }

        return;
    }

    /**
     * @return Response
     */
    private function getIndexResponse(FormInterface $form)
    {
        $games = $this->gameService->getRecentGames();

        return $this->render('game/game-index.html.twig', [
            'form' => $form->createView(),
            'games' => $games,
        ]);
    }
}
