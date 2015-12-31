<?php

namespace AppBundle\Repository;

use Llvdl\Domino\Game;
use Symfony\Bridge\Doctrine\RegistryInterface;

class GameRepository implements \Llvdl\Domino\GameRepository
{
    /** @var RegistryInterface */
    private $doctrine;

    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /** @see Llvdl\Domino\GameRepository::getRecentGames */
    public function getRecentGames()
    {
        return $this->getGameRepository()->findAll();
    }

    /** @see Llvdl\Domino\GameRepository::findById */
    public function findById($id)
    {
        return $this->getGameRepository()->find($id);
    }

    /** @see Llvdl\Domino\GameRepository::createGame */
    public function persistGame(Game $game)
    {
        $em = $this->getEntityManager();
        $em->persist($game);
        $em->flush();
    }

    /** @return Doctrine\ORM\EntityManager */
    private function getEntityManager()
    {
        return $this->doctrine->getManager();
    }

    /** @return Doctrine\ORM\EntityRepository */
    private function getGameRepository()
    {
        return $this->doctrine->getRepository('Llvdl\Domino\Game');
    }
}
