<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\DashboardController;
use App\Controller\Admin\OfferCrudController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/*───────────────────────────────────────────────
 | 1. Tests unitaires : titre & menu
 *───────────────────────────────────────────────*/
final class DashboardControllerUnitTest extends KernelTestCase
{
    public function testConfigureMenuItemsContainsExpectedEntries(): void
    {
        $controller = new DashboardController();

        /** @var MenuItem[] $items */
        $items = iterator_to_array($controller->configureMenuItems());

        $this->assertCount(3, $items);
    }
}

/*───────────────────────────────────────────────
 | 2. Tests fonctionnels : sécurité & redirections 
 *───────────────────────────────────────────────*/
final class DashboardControllerFunctionalTest extends WebTestCase
{
    /** URL déclarée via #[AdminDashboard(routePath: '/admin')] */
    private const ADMIN_PATH = '/admin';
    private EntityManagerInterface $entitymanager;

    /* ── anonyme : redirection vers le login ───────────────────────── */
    public function testIndexRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', self::ADMIN_PATH);

        $this->assertTrue(
            $client->getResponse()->isRedirect(),
            'Un utilisateur anonyme doit être redirigé (généralement vers /login).'
        );
    }

    public function testIndexRedirectsToOfferCrudWhenAdmin(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getAdminUser());

        $client->request('GET', self::ADMIN_PATH);

        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect(), 'Après connexion, on attend une redirection interne.');
        $location = $response->headers->get('Location');

    }

    /* ───────────────────────── helpers ────────────────────────────── */
    private function getAdminUser(): User
    {
        $repo = self::getContainer()->get('doctrine')->getRepository(User::class);

        $admin = $repo->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', 'ROLE_ADMIN')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        
        if (!$admin) {
            $admin = new User();
            $this->entitymanager = self::GetContainer()->get(EntityManagerInterface::class);
            $admin->setEmail('admin@test.com');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setPassword(
                password_hash('adminpassword', PASSWORD_BCRYPT)
            );
            $admin->setusername('admin');
            $this->entitymanager->persist($admin);
            $this->entitymanager->flush();
        }

        return $admin;
    }
}
