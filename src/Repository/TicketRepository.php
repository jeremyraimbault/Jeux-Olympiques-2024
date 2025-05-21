<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * Retourne les ventes par types d'offre.
     *
     * @return array [ ['offer' => Offer, 'sales' => int], ... ]
     */
    public function countSalesByOffer(): array 
    {
        return $this->createQueryBuilder('t')
            ->select('o.name AS offerName, COUNT(t.id) AS sales, o.remainingCapacity AS remainingCapacity')
            ->join('t.offer', 'o')
            ->groupBy('o.id')
            ->getQuery()
            ->getResult();
    }
}
