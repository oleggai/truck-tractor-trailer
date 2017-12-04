<?php

namespace ListingBundle\Form\Tractor;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;

class ItemType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('mileage', IntegerType::class, [
            'label' => 'Mileage',
            'constraints' => [
                /*new NotBlank(['message' => 'Mileage is required']),*/
                new Type([
                    'type' => 'integer',
                    'message' => 'Mileage should be an integer'
                ]),
                new Range([
                    'min' => 1,
                    'minMessage' => 'Mileage should be a positive integer'
                ])
            ]
        ]);
        $builder->add('price', MoneyType::class, [
            'label' => 'Price',
            'currency' => 'USD',
            'constraints' => [
                /*new NotBlank(['message' => 'Mileage is required']),*/
                new Type([
                    'type' => 'numeric',
                    'message' => 'Price should be a number'
                ]),
                new Range([
                    'min' => 0,
                    'minMessage' => 'Price should be a positive number'
                ])
            ]
        ]);
        $builder->add('vin', TextType::class, [
            'label' => 'VIN Number',
            'constraints' => [
                new NotBlank(['message' => 'VIN Number is required'])
            ]
        ]);
    }

}