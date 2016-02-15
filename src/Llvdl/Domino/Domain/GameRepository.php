<?php

namespace Llvdl\Domino\Domain;

interface GameRepository
{
    /** @return Game[] */
    public function getRecentGames();

    /** @return Game|null */
    public function findById($id);

    /** @var Game game */
    public function persistGame(Game $game);
}
