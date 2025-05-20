<?php

namespace App\Service;

use App\Repository\OfferRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    private RequestStack $requestStack;
    private OfferRepository $offerRepository;

    public function __construct(RequestStack $requestStack, OfferRepository $offerRepository)
    {
        $this->requestStack = $requestStack;
        $this->offerRepository = $offerRepository;
    }

    public function getGlobals(): array
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $offer = $this->offerRepository->find($id);
            if ($offer) {
                $total += $quantity;
            }
        }

        return [
            'cart_count' => $total,
        ];
    }
}
