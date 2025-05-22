<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\UserAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class UserAuthenticatorTest extends TestCase
{
    private UserAuthenticator $authenticator;

    protected function setUp(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')
            ->with(UserAuthenticator::LOGIN_ROUTE)
            ->willReturn('/login');

        $this->authenticator = new UserAuthenticator($urlGenerator);
    }

    /* -----------------------------------------------------------------
     * Helpers
     * -----------------------------------------------------------------*/
    private function makeJsonRequest(array $payload): Request
    {
        $request = Request::create(
            '/login',
            'POST',
            [],      // query
            [],      // files & cookies
            [],      // server bag
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    public function testAuthenticateBuildsAValidPassport(): void
    {
        $email    = 'john@example.com';
        $password = 'Secret123!';
        $csrf     = 'csrfToken';

        $request  = $this->makeJsonRequest([
            'email'       => $email,
            'password'    => $password,
            '_csrf_token' => $csrf,
        ]);

        $passport = $this->authenticator->authenticate($request);

        $this->assertSame(
            $email,
            $request->getSession()->get(SecurityRequestAttributes::LAST_USERNAME)
        );

        /* ---- Password ---- */
        /** @var PasswordCredentials $cred */
        $cred = $passport->getBadge(PasswordCredentials::class);
        $this->assertInstanceOf(PasswordCredentials::class, $cred);
        $this->assertSame($password, $cred->getPassword());

        /* ---- CsrfToken ---- */
        /** @var CsrfTokenBadge $csrfBadge */
        $csrfBadge = $passport->getBadge(CsrfTokenBadge::class);
        $this->assertSame($csrf, $csrfBadge->getCsrfToken());

        $this->assertTrue($passport->hasBadge(RememberMeBadge::class));
    }

    public function testSuccessRedirectsToTargetPathIfDefined(): void
    {
        $request = $this->makeJsonRequest([]);
        $request->getSession()->set('_security.main.target_path', '/dashboard');

        $response = $this->authenticator->onAuthenticationSuccess(
            $request,
            $this->createMock(TokenInterface::class),
            'main'
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/dashboard', $response->getTargetUrl());
    }

    public function testSuccessRedirectsToLoginWhenNoTarget(): void
    {
        $request  = $this->makeJsonRequest([]);

        $response = $this->authenticator->onAuthenticationSuccess(
            $request,
            $this->createMock(TokenInterface::class),
            'main'
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getTargetUrl());
    }
}
