<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormInterface;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegisterPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="registration_form[firstname]"]');
        $this->assertSelectorExists('input[name="registration_form[lastname]"]');
        $this->assertSelectorExists('input[name="registration_form[plainPassword]"]');
    }

    public function testRegisterWithValidDataCreatesUser(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('S’inscrire')->form();

        $form['registration_form[firstname]'] = 'Jean';
        $form['registration_form[lastname]'] = 'Dupont';
        $form['registration_form[email]'] = 'jeandupont@example.com';
        $form['registration_form[plainPassword]'] = 'Password123!';
        $form['registration_form[agreeTerms]'] = true;

        $client->submit($form);

        $this->assertResponseRedirects();

        $client->followRedirect();

        $user = self::GetContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['username' => 'jean.dupont']);
        $this->assertNotNull($user);
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testRegisterWithInvalidDataShowsErrors(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('S’inscrire')->form();

        $form['registration_form[firstname]'] = '';
        $form['registration_form[lastname]'] = '';
        $form['registration_form[email]'] = '';
        $form['registration_form[plainPassword]'] = '123456';

        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
    }
}
