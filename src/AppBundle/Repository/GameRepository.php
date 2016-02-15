<?php

namespace AppBundle\Repository;

use Llvdl\Domino\Domain\Game;
use Doctrine\ORM\EntityManager;

class GameRepository implements \Llvdl\Domino\Domain\GameRepository
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /** @see Llvdl\Domino\GameRepository::getRecentGames */
    public function getRecentGames()
    {
        $qb = $this->createBaseQueryBuilder();

        return $qb->getQuery()->getResult();
    }

    /** @see Llvdl\Domino\GameRepository::findById */
    public function findById($id)
    {
        $qb = $this->createBaseQueryBuilder();
        $qb
            ->where('g.id = :id')
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
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
        return $this->entityManager;
    }

    private function createBaseQueryBuilder()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('g')
            ->from(Game::class, 'g')
            ->leftJoin('g.players', 'p')
            ->leftJoin('p.stones', 's')
            ->leftJoin('g.state', 'gs')
            ->leftJoin('g.currentTurn', 't')
            ->addSelect('p')
            ->addSelect('s')
            ->addSelect('gs')
            ->addSelect('t');

        return $qb;
    }
}
