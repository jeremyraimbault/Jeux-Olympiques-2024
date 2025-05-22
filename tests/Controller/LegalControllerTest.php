<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LegalControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = self::GetContainer();
        $this->userRepository = $container->get(UserRepository::class);
        $this->entityManager = $container->get('doctrine')->getManager();

        $adminUser = $this->userRepository->findOneBy(['email' => 'admin@legaltest.com']);
        if (!$adminUser) {
            $adminUser = new \App\Entity\User();
            $adminUser->setEmail('admin@legaltest.com');
            $adminUser->setRoles(['ROLE_ADMIN']);
            $adminUser->setPassword(
                password_hash('adminpassword', PASSWORD_BCRYPT)
            );
            $adminUser->setusername('admin');
            $this->entityManager->persist($adminUser);
            $this->entityManager->flush();
        }

    }

    public function testPrivacyPolicyPage(): void
    {
        $this->client->request('GET', '/politique-confidentialite');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testTermsOfUsePage(): void
    {
        $this->client->request('GET', '/conditions-utilisation');
        $this->assertResponseIsSuccessful();
    }

    public function testCookiePolicyPage(): void
    {
        $this->client->request('GET', '/politique-cookies');
        $this->assertResponseIsSuccessful();
    }

    public function testCookieSettingsPage(): void
    {
        $this->client->request('GET', '/parametres-cookies');
        $this->assertResponseIsSuccessful();
    }

    public function testAdminEditLegalPageRequiresLogin(): void
    {
        $this->client->request('GET', '/admin/legal/edit/privacy');
        $this->assertResponseRedirects('/login');
    }

    public function testAdminEditLegalPageWithInvalidPage(): void
    {
        $adminUser = $this->getAdminUser();
        $this->client->loginUser($adminUser);

        $this->client->request('GET', '/admin/legal/edit/unknownpage');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAdminEditLegalPageValid(): void
    {
        $adminUser = $this->getAdminUser();
        $this->client->loginUser($adminUser);

        $pages = ['privacy', 'terms', 'cookies'];
        foreach ($pages as $page) {
            $this->client->request('GET', "/admin/legal/edit/$page");
            $this->assertResponseIsSuccessful();
            $this->assertSelectorTextContains('body', $page);
        }
    }

    private function getAdminUser()
    {
        $userRepository = self::getContainer()->get('doctrine')->getRepository(\App\Entity\User::class);

        $adminUser = $userRepository->findOneBy(['email' => 'admin@legaltest.com']);

        if (!$adminUser) {
            $this->markTestSkipped('No admin user found for testing.');
        }

        return $adminUser;
    }
}
