<?php

namespace Llvdl\Domino;

interface GameRepository
{
    /** @return Game[] */
    public function getRecentGames();

    /** @return Game|null */
    public function findById($id);

    /** @var Game game */
    public function persistGame(Game $game);
}
