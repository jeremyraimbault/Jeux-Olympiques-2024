<?php

namespace App\Controller;

use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminStatsController extends AbstractController
{
    #[Route('/admin/stats', name: 'admin_stats')]
    public function index(TicketRepository $ticketRepository): Response
    {
        $stats = $ticketRepository->countSalesByOffer();

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
        ]);
    }
}
