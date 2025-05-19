<?php

namespace App\Controller;

use App\Repository\OfferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CartController extends AbstractController
{
    #[Route('/cart', name: 'cart_index')]
    public function index(SessionInterface $session, OfferRepository $offerRepository): Response
    {
        $cart = $session->get('cart', []);
        $cartData = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $offer = $offerRepository->find($id);
            if ($offer) {
                $cartData[] = [
                    'offer' => $offer,
                    'quantity' => $quantity,
                    'subtotal' => $offer->getPrice() * $quantity
                ];
                $total += $offer->getPrice() * $quantity;
            }
        }

        return $this->render('cart/index.html.twig', [
            'items' => $cartData,
            'total' => $total,
        ]);
    }

    #[Route('/cart/add/{id}', name: 'cart_add')]
    public function add($id, Request $request, SessionInterface $session, OfferRepository $offerRepository): Response
    {
        $quantity = (int) $request->request->get('quantity', 1);
        $allowedQuantites = [1, 2, 4];

        if (!in_array($quantity, $allowedQuantites)) {
            $this->addFlash('danger', 'Quantité invalide.');
            return $this->redirectToRoute('app_offer_index');
        }

        if($quantity < 1){
            $this->addFlash('danger', 'Quantité invalide.');
            return $this->redirectToRoute('app_offer_index');
        }

        $offer = $offerRepository->find($id);
        if (!$offer) {
            $this->addFlash('danger', 'Offre non trouvée.');
            return $this->redirectToRoute('app_offer_index');
        }

        if($quantity > $offer->getCapacity()){
            $this->addFlash('warning', 'La quantité dépasse la capacité maximale de cette offre.');
            return $this->redirectToRoute('app_offer_index');
        }

        $cart = $session->get('cart', []);
        $cart[$id] = ($cart[$id] ?? 0) + $quantity;
        $session->set('cart', $cart);

        $this->addFlash('success', 'Offre ajoutée au panier !');
        return $this->redirectToRoute('app_offer_index');
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove')]
    public function remove($id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        if (isset($cart[$id])) {
            unset($cart[$id]);
        }
        $session->set('cart', $cart);

        $this->addFlash('info', 'Offre retirée du panier.');
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/cart/clear', name: 'cart_clear')]
    public function clear(SessionInterface $session): Response
    {
        $session->remove('cart');
        $this->addFlash('warning', 'Panier vidé.');
        return $this->redirectToRoute('cart_index');
    }

    
}
