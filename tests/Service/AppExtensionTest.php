<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Offer;
use App\Service\AppExtension;
use App\Repository\OfferRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests unitaires pour la classe AppExtension.
 *
 * - getGlobals() doit retourner la clé `cart_count`
 *   contenant la somme des quantités du panier
 *   mais seulement pour les offres réellement trouvées
 *   par l’OfferRepository.
 */
final class AppExtensionTest extends TestCase
{

    public function testGetGlobalsReturnsZeroWhenCartIsEmpty(): void
    {
        $extension = $this->createExtensionWithCart([], []);
        $globals   = $extension->getGlobals();

        $this->assertArrayHasKey('cart_count', $globals);
        $this->assertSame(0, $globals['cart_count']);
    }

    public function testGetGlobalsReturnsQuantitySumForValidOffers(): void
    {
        $cart = [1 => 2, 2 => 3];

        $existingIds = [1, 2];

        $extension = $this->createExtensionWithCart($cart, $existingIds);
        $globals   = $extension->getGlobals();

        $this->assertSame(5, $globals['cart_count']);
    }


    public function testInvalidOfferIdsAreIgnored(): void
    {
        $cart         = [1 => 1, 2 => 4]; 
        $existingIds  = [1];              

        $extension = $this->createExtensionWithCart($cart, $existingIds);
        $globals   = $extension->getGlobals();

        $this->assertSame(1, $globals['cart_count']);
    }


    private function createExtensionWithCart(array $cart, array $idsPresent): AppExtension
    {
        /* ---- Session contenant le panier ---- */
        $session = new Session(new MockArraySessionStorage());
        $session->set('cart', $cart);

        $request = new Request();
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        /* ---- Mock d’OfferRepository ---- */
        $repo = $this->createMock(OfferRepository::class);
        $repo->method('find')
            ->willReturnCallback(static function ($id) use ($idsPresent) {
                return in_array($id, $idsPresent, true) ? new Offer() : null;
            });

        return new AppExtension($requestStack, $repo);
    }
}
