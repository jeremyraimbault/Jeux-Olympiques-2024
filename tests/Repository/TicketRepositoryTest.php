<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Offer;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test d’intégration du repository TicketRepository.
 *
 * Objectif : vérifier que countSalesByOffer() retourne bien
 *            le nombre de ventes par offre ainsi que la capacité restante.
 */
final class TicketRepositoryTest extends KernelTestCase
{
    private \Doctrine\ORM\EntityManagerInterface $em;
    private TicketRepository $ticketRepo;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em         = self::getContainer()->get('doctrine')->getManager();

        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $conn->executeQuery('TRUNCATE TABLE ticket');
        $conn->executeQuery('TRUNCATE TABLE offer');
        $conn->executeQuery('TRUNCATE TABLE user');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        
        /** @var TicketRepository $repo */
        $repo             = $this->em->getRepository(Ticket::class);
        $this->ticketRepo = $repo;
    }

    public function testCountSalesByOffer(): void
    {
        $this->assertSame([], $this->ticketRepo->countSalesByOffer());

        $user = (new User())
            ->setEmail('repo@test.com')
            ->setUsername('repouser')
            ->setPassword('hash')
            ->setPrivateKey(bin2hex(random_bytes(16)));
        $this->em->persist($user);

        $offerA = (new Offer())
            ->setName('Offre A')
            ->setDescription('Desc A')
            ->setPrice(50)
            ->setCapacity(10)
            ->setRemainingCapacity(7);
        $this->em->persist($offerA);

        $offerB = (new Offer())
            ->setName('Offre B')
            ->setDescription('Desc B')
            ->setPrice(30)
            ->setCapacity(5)
            ->setRemainingCapacity(4);
        $this->em->persist($offerB);

        for ($i = 0; $i < 3; ++$i) {
            $this->em->persist(
                (new Ticket())
                    ->setUser($user)
                    ->setOffer($offerA)
                    ->setPurchaseKey("pkA{$i}")
                    ->setFinalKey(hash('sha256', $user->getPrivateKey() . "pkA{$i}"))
                    ->setCreatedAt(new \DateTimeImmutable())
            );
        }
        $this->em->persist(
            (new Ticket())
                ->setUser($user)
                ->setOffer($offerB)
                ->setPurchaseKey('pkB')
                ->setFinalKey(hash('sha256', $user->getPrivateKey() . 'pkB'))
                ->setCreatedAt(new \DateTimeImmutable())
        );

        $this->em->flush();
        $this->em->clear(); 
        $results = $this->ticketRepo->countSalesByOffer();

        $this->assertCount(2, $results);

        $map = [];
        foreach ($results as $row) {
            $map[$row['offerName']] = $row;
        }

        $this->assertSame(3, (int) $map['Offre A']['sales']);
        $this->assertSame(1, (int) $map['Offre B']['sales']);
        $this->assertSame(7, (int) $map['Offre A']['remainingCapacity']);
        $this->assertSame(4, (int) $map['Offre B']['remainingCapacity']);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Ticket t')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Offer  o')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\User   u')->execute();

        $this->em->close();
        parent::tearDown();
    }
}
