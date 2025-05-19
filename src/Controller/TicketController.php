<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class TicketController extends AbstractController
{
    #[Route('/my-tickets', name: 'ticket_list')]
    public function list(TicketRepository $ticketRepository, Security $security): Response
    {
        /** @var \App\Entity\User  $user*/
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $tickets = $ticketRepository->findBy(['user' => $user]);

        return $this->render('ticket/list.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/checkout', name: 'ticket_checkout')]
    public function checkout(RequestStack $requestStack, OfferRepository $offerRepository, EntityManagerInterface $em, Security $security): Response {
        $session = $requestStack->getSession();
        $cart = $session->get('cart', []);

        /** @var \App\Entity\User $user */
        $user = $security->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $tickets = [];

        foreach ($cart as $offerId => $quantity) {
            $offer = $offerRepository->find($offerId);
            if (!$offer) continue;

            for ($i = 0; $i < $quantity; $i++) {
                $purchaseKey = bin2hex(random_bytes(16));
                $finalKey = hash('sha256', $user->getPrivateKey() . $purchaseKey);

                $ticket = new Ticket();
                $ticket->setUser($user);
                $ticket->setOffer($offer);
                $ticket->setPurchaseKey($purchaseKey);
                $ticket->setFinalKey($finalKey);
                $ticket->setCreatedAt(new \DateTimeImmutable());

                $em->persist($ticket);
                $tickets[] = $ticket;
            }
        }

        $em->flush();
        $session->remove('cart'); // vider le panier après achat

        return $this->render('ticket/confirmation.html.twig', [
            'tickets' => $tickets,
        ]);
    }
}