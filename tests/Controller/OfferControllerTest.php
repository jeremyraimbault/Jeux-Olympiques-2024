<?php

namespace App\Tests\Controller;

use App\Entity\Offer;
use App\Repository\OfferRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class OfferControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $offerRepository;
    private $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = self::GetContainer();
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->offerRepository = $container->get(OfferRepository::class);
        $this->userRepository = $container->get(UserRepository::class);

        $adminUser = $this->userRepository->findOneBy(['email' => 'admin@test.com']);
        if (!$adminUser) {
            $adminUser = new \App\Entity\User();
            $adminUser->setEmail('admin@test.com');
            $adminUser->setRoles(['ROLE_ADMIN']);
            $adminUser->setPassword(
                password_hash('adminpassword', PASSWORD_BCRYPT)
            );
            $adminUser->setusername('admin');
            $this->entityManager->persist($adminUser);
            $this->entityManager->flush();
        }

        $offer = $this->offerRepository->findOneBy([]);
        if (!$offer) {
            $offer = new Offer();
            $offer->setName('Sample Offer');
            $offer->setDescription('Description');
            $offer->setPrice(10.0);
            $offer->setCapacity(20);
            $offer->setRemainingCapacity(5);
            $this->entityManager->persist($offer);
            $this->entityManager->flush();
        }
    }

    public function testIndexPageLoads(): void
    {
        $crawler = $this->client->request('GET', '/boutique');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testNewOfferPageRequiresAdmin(): void
    {
        $this->client->request('GET', '/boutique/new');
        $this->assertResponseRedirects('/login');

        $adminUser = $this->getAdminUser();
        $this->client->loginUser($adminUser);
        $this->client->request('GET', '/boutique/new');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testCreateNewOfferAsAdmin(): void
    {
        $adminUser = $this->getAdminUser();
        $this->client->loginUser($adminUser);

        $crawler = $this->client->request('GET', '/boutique/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save')->form();

        $form['offer_form[name]'] = 'Test Offer';
        $form['offer_form[description]'] = 'Description test';
        $form['offer_form[price]'] = 99.99;
        $form['offer_form[capacity]'] = 10;

        $this->client->submit($form);
        $this->assertResponseRedirects('/boutique');

        $this->client->followRedirect();

        $offer = $this->offerRepository->findOneBy(['name' => 'Test Offer']);
        $this->assertNotNull($offer);
        $this->assertEquals('Description test', $offer->getDescription());
    }

    public function testShowOfferPage(): void
    {
        $offer = $this->offerRepository->findOneBy([]);
        if (!$offer) {
            $this->markTestSkipped('No offer found in DB.');
        }

        $this->client->request('GET', '/boutique/' . $offer->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', $offer->getName());
    }

    public function testEditOfferPageAsAdmin(): void
    {
        $offer = $this->offerRepository->findOneBy([]);
        if (!$offer) {
            $this->markTestSkipped('No offer found in DB.');
        }

        $adminUser = $this->getAdminUser();
        $this->client->loginUser($adminUser);

        $crawler = $this->client->request('GET', '/boutique/' . $offer->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Update')->form();

        $newName = 'Updated Offer Name';
        $form['offer_form[name]'] = $newName;
        $form['offer_form[capacity]'] = 10;

        $this->client->submit($form);
        $this->assertResponseRedirects('/boutique');

        $this->client->followRedirect();

        $updatedOffer = $this->offerRepository->find($offer->getId());
        $this->assertEquals($newName, $updatedOffer->getName());
    }

    private function getAdminUser()
    {
        $userRepository = self::GetContainer()->get('doctrine')->getRepository(\App\Entity\User::class);

        $adminUser = $userRepository->findOneBy(['email' => 'admin@test.com']);

        if (!$adminUser) {
            $this->markTestSkipped('No admin user found for testing.');
        }

        return $adminUser;
    }
}
