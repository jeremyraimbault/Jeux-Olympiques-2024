<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminStatsControllerTest extends WebTestCase
{
    public function testAdminStatsRequiresAuthentication()
    {
        $client = static::createClient();

        $client->request('GET', '/admin/stats');
        $this->assertTrue(
            $client->getResponse()->isRedirect(),
            'Expected redirect to login page for anonymous user'
        );
    }
}
