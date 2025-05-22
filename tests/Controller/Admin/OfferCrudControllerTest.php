<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\OfferCrudController;
use App\Entity\Offer;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 *  1.  TESTS UNITAIRES (KernelTestCase)
 *       – structure statique du CRUD : entity FQCN + champs déclarés
 *
 *  2.  TESTS FONCTIONNELS (WebTestCase)
 *       – accès aux pages EasyAdmin (index) protégé pour les anonymes
 *       – chargement correct pour un administrateur
 */

/**
 * Partie unitaires
 */
final class OfferCrudControllerTest extends KernelTestCase
{
    public function testGetEntityFqcn(): void
    {
        self::bootKernel();
        $this->assertSame(Offer::class, OfferCrudController::getEntityFqcn());
    }

    public function testConfigureFieldsContainsExpectedFields(): void
    {
        $controller = new OfferCrudController();

        /** @var \Traversable<int,\EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface> $fields */
        $fields = $controller->configureFields(Crud::PAGE_INDEX);
        $fields = iterator_to_array($fields);

        $this->assertCount(6, $fields);

        $this->assertInstanceOf(IdField::class,        $fields[0]);
        $this->assertInstanceOf(TextField::class,      $fields[1]);
        $this->assertInstanceOf(TextareaField::class,  $fields[2]);
        $this->assertInstanceOf(MoneyField::class,     $fields[3]);
        $this->assertInstanceOf(IntegerField::class,   $fields[4]);
        $this->assertInstanceOf(IntegerField::class,   $fields[5]);
    }
}

/**
 * Partie fonctionnelle : nécessite le client HTTP.
 */
final class OfferCrudControllerFunctionalTest extends WebTestCase
{
    private static function adminIndexUrl(): string
    {
        // URL standard : /admin?crudAction=index&crudControllerFqcn=...
        return '/admin?crudAction=index&crudControllerFqcn=' .
            rawurlencode(OfferCrudController::class);
    }

    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', self::adminIndexUrl());

        $this->assertTrue(
            $client->getResponse()->isRedirect(),
            'L’utilisateur anonyme doit être redirigé vers /login'
        );
    }

    public function testIndexLoadsForAdmin(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getAdminUser());

        $crawler = $client->request('GET', self::adminIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des offres');
    }

    /* ---------- helpers ---------- */

    private function getAdminUser(): User
    {
        $repo = self::$container->get('doctrine')->getRepository(User::class);
        $admin = $repo->createQueryBuilder('u')
            ->andWhere(':role MEMBER OF u.roles')
            ->setParameter('role', 'ROLE_ADMIN')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$admin) {
            $this->markTestSkipped('Aucun utilisateur avec ROLE_ADMIN dans la base de tests.');
        }

        return $admin;
    }
}
