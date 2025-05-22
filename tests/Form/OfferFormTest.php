<?php

namespace App\Tests\Form;

use App\Entity\Offer;
use App\Form\OfferForm;
use Symfony\Component\Form\Test\TypeTestCase;

final class OfferFormTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'name' => 'Offre VIP',
            'description' => 'Accès VIP à la cérémonie.',
            'price' => 150.00,
            'capacity' => 100,
        ];

        $model = new Offer();
        $form = $this->factory->create(OfferForm::class, $model);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertSame('Offre VIP', $model->getName());
        $this->assertSame('Accès VIP à la cérémonie.', $model->getDescription());
        $this->assertSame(150.00, $model->getPrice());
        $this->assertSame(100, $model->getCapacity());
    }
}
