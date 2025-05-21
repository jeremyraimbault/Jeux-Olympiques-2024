<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class LegalController extends AbstractController
{
    #[Route('/politique-confidentialite', name: 'app_privacy_policy')]
    public function privacyPolicy(): Response
    {
        return $this->render('legal/privacy_policy.html.twig');
    }

    #[Route('/conditions-utilisation', name: 'app_terms_of_use')]
    public function termsOfUse(): Response
    {
        return $this->render('legal/terms_of_use.html.twig');
    }

    #[Route('/politique-cookies', name: 'app_cookie_policy')]
    public function cookiePolicy(): Response
    {
        return $this->render('legal/cookie_policy.html.twig');
    }

    #[Route('/parametres-cookies', name: 'app_cookie_settings')]
    public function cookieSettings(): Response
    {
        return $this->render('legal/cookie_settings.html.twig');
    }

    // Admin uniquement : édition (bonus)
    #[Route('/admin/legal/edit/{page}', name: 'admin_legal_edit')]
    public function editLegalPage(string $page): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $templateMap = [
            'privacy' => 'legal/privacy_policy.html.twig',
            'terms' => 'legal/terms_of_use.html.twig',
            'cookies' => 'legal/cookie_policy.html.twig',
        ];

        if (!array_key_exists($page, $templateMap)) {
            throw $this->createNotFoundException("Page légale inconnue.");
        }

        return $this->render('admin/legal_edit_placeholder.html.twig', [
            'page' => $page,
            'template' => $templateMap[$page],
        ]);
    }
}
