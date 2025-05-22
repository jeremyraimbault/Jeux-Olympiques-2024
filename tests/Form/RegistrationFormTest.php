<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\RegistrationForm;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

final class RegistrationFormTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new PreloadedExtension([], []),        
            new ValidatorExtension($validator),    
        ];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'firstname' => 'Alice',
            'lastname' => 'Durand',
            'email' => 'alice@example.com',
            'agreeTerms' => true,
            'plainPassword' => 'MotDePasse1',
        ];

        $user = new User();

        $form = $this->factory->create(RegistrationForm::class, $user);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertSame('alice@example.com', $user->getEmail());
    }

    public function testSubmitInvalidPassword(): void
    {
        $formData = [
            'firstname' => 'Bob',
            'lastname' => 'Martin',
            'email' => 'bob@example.com',
            'agreeTerms' => true,
            'plainPassword' => 'abc',
        ];

        $form = $this->factory->create(RegistrationForm::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid(), 'Le formulaire doit être invalide avec un mot de passe faible.');
    }

    public function testSubmitWithoutAgreeingTerms(): void
    {
        $formData = [
            'firstname' => 'Claire',
            'lastname' => 'Bernard',
            'email' => 'claire@example.com',
            'agreeTerms' => false,
            'plainPassword' => 'UnMotDePasse2',
        ];

        $form = $this->factory->create(RegistrationForm::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid(), 'Le formulaire doit être invalide si les CGU ne sont pas acceptées.');
    }
}
