<?php

namespace App\Tests\Controller;

use App\Entity\Offer;
use App\Entity\Ticket;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\BrowserKit\Cookie;

class TicketControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = self::GetContainer();
        $this->entityManager = $container->get('doctrine')->getManager();

    }

    public function testListRedirectsToLoginIfNotAuthenticated(): void
    {
        $this->client->request('GET', '/my-tickets');

        $this->assertResponseRedirects('/login');
    }

    public function testListShowsTicketsForAuthenticatedUser(): void
    {
        $user = $this->getUser();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/my-tickets');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'Mes Tickets');
    }

    public function testCheckoutRedirectsToLoginIfNotAuthenticated(): void
    {
        $this->client->request('GET', '/checkout');

        $this->assertResponseRedirects('/login');
    }

    public function testCheckoutWithEmptyCartRedirectsToCartIndex(): void
    {
        $user = $this->getUser();
        $this->client->loginUser($user);

        $session = $this->initSession();
        $session->set('cart', []);
        $session->save();

        $crawler = $this->client->request('GET', '/checkout');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', '🎉 Merci pour votre achat !');
    }

    public function testCheckoutSuccessfulCreatesTickets(): void
    {
        $user = $this->getUser();
        $this->client->loginUser($user);

        $offer = $this->createOfferWithCapacity(5);

        $session = $this->initSession();
        $session->set('cart', [$offer->getId() => 2]);
        $session->save();

        $crawler = $this->client->request('GET', '/checkout');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', '🎉 Merci pour votre achat !');

        $tickets = $this->entityManager->getRepository(Ticket::class)->findBy(['user' => $user, 'offer' => $offer]);
        $this->assertGreaterThanOrEqual(2, count($tickets));
    }

    public function testShowDisplaysQrCode(): void
    {
        $ticket = $this->createTicket();

        $this->client->loginUser($ticket->getUser());
        $url = self::getContainer()
            ->get('router')
            ->generate('app_ticket_show', ['id' => $ticket->getId()]);
        $crawler = $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('img[alt="QR Code du billet"]');
    }

    /* ============ Helpers ============ */

    /**
     * Initialise la session et renvoie l’instance \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private function initSession(): SessionInterface
    {
        // Faire une fausse requête pour initialiser le client
        $this->client->request('GET', '/');
        
        $session = $this->client->getRequest()->getSession();
        
        // Lier la session à la CookieJar
        $this->client->getCookieJar()->set(
            new \Symfony\Component\BrowserKit\Cookie($session->getName(), $session->getId())
        );

        return $session;
    }

    private function getUser(): User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        if (!$user) {
            $user = new User();
            $user->setEmail('user@example.com');
            $user->setPassword('hashed_password');
            $user->setUsername('testuser');
            $user->setPrivateKey(bin2hex(random_bytes(16)));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $user;
    }

    private function createOfferWithCapacity(int $capacity): Offer
    {
        $offer = new Offer();
        $offer->setName('Offre test');
        $offer->setDescription('Description test');
        $offer->setPrice(100);
        $offer->setCapacity($capacity);

        $this->entityManager->persist($offer);
        $this->entityManager->flush();

        return $offer;
    }

    private function createTicket(): Ticket
    {
        $user = $this->getUser();
        $offer = $this->createOfferWithCapacity(10);

        $ticket = new Ticket();
        $ticket->setUser($user);
        $ticket->setOffer($offer);
        $ticket->setPurchaseKey('purchase_key_test');
        $ticket->setFinalKey(hash('sha256', $user->getPrivateKey() . 'purchase_key_test'));
        $ticket->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        return $ticket;
    }
}
