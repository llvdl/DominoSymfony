<?php

namespace Llvdl\Domino\Service;

use Llvdl\Domino\Domain\Exception\DominoException;
use Llvdl\Domino\Domain;

class GameService
{
    /** @var Domain\GameRepository */
    private $gameRepository;

    /**
     * @param Domain\GameRepository $gameRepository
     */
    public function __construct(Domain\GameRepository $gameRepository)
    {
        $this->gameRepository = $gameRepository;
    }

    /**
     * @param int $id
     * 
     * @return Dto\GameDetail|null
     */
    public function getGameById($id)
    {
        $game = $this->gameRepository->findById($id);

        return $game === null ? null : $this->mapGameToGameDetailDto($game);
    }

    /** @return Dto\GameDetail[] */
    public function getRecentGames()
    {
        return array_map(function (Domain\Game $game) {
            return $this->mapGameToGameDetailDto($game);
        }, $this->gameRepository->getRecentGames());
    }

    /** 
     * @param string $name name
     *
     * @return int game id
     */
    public function createGame($name)
    {
        $game = new Domain\Game($name);
        $this->gameRepository->persistGame($game);

        return $game->getId();
    }

    /** 
     * @param int $gameId game id 
     *
     * @throws Domain\DominoException if the game could not be loaded or the game has already been dealt
     * @throws Domain\LogicException  if the game was already started
     */
    public function deal($gameId)
    {
        $game = $this->loadGame($gameId);
        $game->deal();
        $this->gameRepository->persistGame($game);
    }

    /**
     * @param int      $gameId
     * @param int      $playerNumber
     * @param Dto\Play $playDto      play move
     */
    public function play($gameId, $playerNumber, Dto\Play $playDto)
    {
        $game = $this->loadGame($gameId);
        $player = $game->getPlayerByPlayerNumber($playerNumber);
        $play = $this->mapPlayDtoToPlay($playDto);
        $player->play($play);
        $this->gameRepository->persistGame($game);
    }

    /**
     * @param Domain\Game $game
     *
     * @return Dto\GameDetail
     */
    private function mapGameToGameDetailDto(Domain\Game $game)
    {
        $builder = (new Dto\GameDetailBuilder())
            ->id($game->getId())
            ->name($game->getName())
            ->state($game->getState()->getName());

        foreach ($game->getPlayers() as $player) {
            $builder->addPlayer($player->getNumber(), $player->getStones());
        }

        if ($game->getCurrentTurn() !== null) {
            $builder->turn($game->getCurrentTurn()->getNumber(), $game->getCurrentTurn()->getPlayerNumber());
        }

        return $builder->get();
    }

    /**
     * @param PlayDto $playDto
     *
     * @return Domain\Play
     */
    private function mapPlayDtoToPlay(Dto\Play $playDto)
    {
        $stone = new Domain\Stone($playDto->getStone()->getTopValue(), $playDto->getStone()->getBottomValue());

        return new Domain\Play(
            $playDto->getTurnNumber(),
            $stone,
            $playDto->getSide()
        );
    }

    /**
     * @param int $gameId
     *
     * @return Domain\Game
     *
     * @throws Domain\DominoException
     */
    private function loadGame($gameId)
    {
        $game = $this->gameRepository->findById($gameId);
        if ($game === null) {
            throw new DominoException('could not find game with id '.$gameId);
        }

        return $game;
    }
}
