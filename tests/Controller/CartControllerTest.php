<?php

namespace App\Tests\Controller;

use App\Entity\Offer;
use App\Repository\OfferRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManagerInterface;


class CartControllerTest extends WebTestCase
{
    private $client;
    private $offerRepository;
    private $session;
    private EntityManagerInterface $entitymanager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->client->request('GET', '/');

        $container = self::GetContainer();
        $this->entitymanager = self::GetContainer()->get(EntityManagerInterface::class);

        $this->offerRepository = $container->get(OfferRepository::class);

        $this->session = $this->client->getRequest()->getSession();

        if ($this->session === null) {
            throw new \RuntimeException('Session is not available.');
        }

        $this->session->set('cart', [1 => 1]);
        $this->session->save();
    }

    public function testIndexWithEmptyCart(): void
    {
        $session = $this->client->getContainer()->get('session.factory')->createSession();
        $session->set('cart', []);
        $session->save();

        $this->client->request('GET', '/panier');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testIndexWithCartItems(): void
    {
        $offer = $this->offerRepository->findOneBy([]);
        if (!$offer) {
            $offer = new Offer();
            $offer->setName('CART Sample Offer');
            $offer->setDescription('CART Description');
            $offer->setPrice(10.0);
            $offer->setCapacity(20);
            $offer->setRemainingCapacity(5);
            $this->entitymanager->persist($offer);
            $this->entitymanager->flush();
        }

        $session = $this->client->getContainer()->get('session.factory')->createSession();
        $session->set('cart', [
            $offer->getId() => 2
        ]);
        $session->save();

        $this->client->request('GET', '/panier');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testAddInvalidQuantity(): void
    {
        $this->client->request('POST', '/panier/add/1', ['quantity' => 3]);

        $this->assertResponseRedirects('/boutique');

        $this->client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
    }

    public function testAddOfferNotFound(): void
    {
        $this->client->request('POST', '/panier/add/999999', ['quantity' => 1]);

        $this->assertResponseRedirects('/boutique');
        $this->client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAddQuantityExceedsCapacity(): void
    {
        $offer = $this->offerRepository->findOneBy([]);
        if (!$offer) {
            $this->markTestSkipped('No offer found in database.');
        }
        $capacity = 1;
        $reflection = new \ReflectionClass($offer);
        $property = $reflection->getProperty('capacity');
        $property->setAccessible(true);
        $property->setValue($offer, $capacity);
        self::getContainer()->get('doctrine')->getManager()->flush();

        $this->client->request('POST', '/panier/add/'.$offer->getId(), ['quantity' => 4]);

        $this->assertResponseRedirects('/boutique');
        $this->client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAddValidQuantity(): void
    {
        $offer = $this->offerRepository->findOneBy([]);
        if (!$offer) {
            $this->markTestSkipped('No offer found in database.');
        }

        $this->client->request('POST', '/panier/add/'.$offer->getId(), ['quantity' => 1]);

        $this->assertResponseRedirects('/');

        $this->client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
    }

    public function testRemoveFromCart(): void
    {
        $offer = $this->offerRepository->findOneBy([]);
        if (!$offer) {
            $this->markTestSkipped('No offer found in database.');
        }

        $session = $this->client->getContainer()->get('session.factory')->createSession();
        $session->set('cart', [$offer->getId() => 2]);
        $session->save();

        $this->client->getCookieJar()->set(
            new \Symfony\Component\BrowserKit\Cookie($session->getName(), $session->getId())
        );

        $this->client->request('GET', '/cart/remove/'.$offer->getId());

        $this->assertResponseRedirects('/panier');

        $this->client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
    }

    public function testClearCart(): void
    {
        $this->client->request('GET', '/cart/clear');

        $this->assertResponseRedirects('/panier');

        $this->client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
    }

}
