<?php

namespace Llvdl\Domino;

use Llvdl\Domino\Dto\GameDetailDto;
use Llvdl\Domino\Dto\GameDetailDtoBuilder;
use Llvdl\Domino\Dto\PlayDto;
use Llvdl\Domino\Exception\DominoException;

class GameService
{
    /** @var GameRepository */
    private $gameRepository;

    public function __construct(GameRepository $gameRepository)
    {
        $this->gameRepository = $gameRepository;
    }

    /** @return GameDetailDto|null */
    public function getGameById($id)
    {
        $game = $this->gameRepository->findById($id);

        return $game === null ? null : $this->mapGameToGameDetailDto($game);
    }

    /** @return GameDetailDto[] */
    public function getRecentGames()
    {
        return array_map(function (Game $game) {
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
        $game = new Game($name);
        $this->gameRepository->persistGame($game);

        return $game->getId();
    }

    /** 
     * @param int $gameId game id 
     *
     * @throws DominoException if the game could not be loaded or the game has already been dealt
     * @throws LogicException  if the game was already started
     */
    public function deal($gameId)
    {
        $game = $this->loadGame($gameId);
        $game->deal();
        $this->gameRepository->persistGame($game);
    }

    /**
     * @param int     $gameId
     * @param int     $playerNumber
     * @param PlayDto $playDto      play move
     */
    public function play($gameId, $playerNumber, PlayDto $playDto)
    {
        $game = $this->loadGame($gameId);
        $player = $game->getPlayerByPlayerNumber($playerNumber);
        $play = $this->mapPlayDtoToPlay($playDto);
        $player->play($play);
        $this->gameRepository->persistGame($game);
    }

    /**
     * @param Game $game
     *
     * @return GameDetailDto
     */
    private function mapGameToGameDetailDto(Game $game)
    {
        $builder = (new GameDetailDtoBuilder())
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
     * @return Play
     */
    private function mapPlayDtoToPlay(PlayDto $playDto)
    {
        $stone = new Stone($playDto->getStone()->getTopValue(), $playDto->getStone()->getBottomValue());

        return new Play($playDto->getTurnNumber(), $stone, $playDto->getSide());
    }

    /**
     * @param int $gameId
     *
     * @return Game
     *
     * @throws DominoException
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
