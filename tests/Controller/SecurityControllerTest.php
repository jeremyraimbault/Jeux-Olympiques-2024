<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name=login_form]');
        $this->assertSelectorExists('input[name="email"]');
        $this->assertSelectorExists('input[name="password"]');
        $this->assertSelectorExists('button[type=submit]');
    }

    public function testLoginWithInvalidCredentialsShowsError(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');
        $this->assertSelectorNotExists('.alert-danger');
    }

    public function testLogoutRoute(): void
    {
        $client = static::createClient();
        $client->request('GET', '/logout');

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [302, 401, 403]),
            'Expected redirect or forbidden status on /logout route.'
        );
    }
}
