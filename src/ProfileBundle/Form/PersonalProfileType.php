<?php

namespace ProfileBundle\Form;

use LocationBundle\Validation\ValidationGroup;
use Payment\PaymentMethodBundle\Entity\ECheck;
use Payment\PaymentMethodBundle\Form\CreditCardType;
use Payment\PaymentMethodBundle\Form\ECheckType;
use ProfileBundle\Entity\PersonalProfile;
use ProfileBundle\Validator\Constraints\Phone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PersonalProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstName', TextType::class, [
            'label' => 'First Name *',
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter your first name',
                ]),
            ]
        ]);
        $builder->add('lastName', TextType::class, [
            'label' => 'Last Name *',
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter your last name',
                ]),
            ]
        ]);

        $builder->add('phone', TextType::class, [
            'label' => 'Your Phone *',
            'attr' => [
                'placeholder' => 'Stay up to date with the status of your bids via Text Message. Standard rates apply.',
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter your phone',
                ]),
                new Phone()
            ]
        ]);

        $builder->add('ssn', TextType::class, [
            'label' => 'Social Security Number *',
            'constraints' => [
                new NotBlank(['message' => 'Please enter ssn'])
            ]
        ]);

        $builder->add('contact_address_checkbox', CheckboxType::class, [
            'mapped' => false,
            'label' => 'Contact Address the Same as Company Address',
        ]);

        $builder->add('contactLocation', TextType::class, [
            'label' => 'Contact Address *',
            'mapped' => false,
            'attr' => [
                'class' => 'form-control'
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter your contact address',
                    'groups' => [
                        ValidationGroup::CHECKED_CONTACT_ADDRESS
                    ]
                ])
            ]
        ]);

        if (!$options['hasCreditCard']) {
            $builder->add('creditCardForAuth', CreditCardType::class, [
                'mapped' => true,
                'validation_groups' => [
                    ValidationGroup::DEFAULT,
                    ValidationGroup::SECURITY_CODE,
                    ValidationGroup::ADD_PAYMENT_METHOD,
                ]
            ]);
        }

        if (!$options['hasECheck']) {
            $builder->add('eCheckForAuth', ECheckType::class, [
                'data_class' => ECheck::class,
                'validation_groups' => [
                    ValidationGroup::DEFAULT,
                    ValidationGroup::ADD_PAYMENT_METHOD,
                ]
            ]);
        }

        $builder->add('billing_address_checkbox', CheckboxType::class, [
            'mapped' => false,
            'label' => 'Billing Address the Same as Contact Address',
        ]);

        $builder->add('billingLocation', TextType::class, [
            'label' => 'Billing Address *',
            'mapped' => false,
            'attr' => [
                'class' => 'form-control'
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter your billing address',
                    'groups' => [
                        ValidationGroup::CHECKED_BILLING_ADDRESS
                    ]
                ])
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('hasCreditCard')
            ->setRequired('hasECheck')
            ->setDefaults(array(
                'allow_extra_fields' => true,
                'data_class' => PersonalProfile::class,
                'attr' => [
                    'novalidate' => 'novalidate',
                ],
                'validation_groups' => function (FormInterface $form) {

                    $validationGroups = [ValidationGroup::DEFAULT];

                    if(!$form->get('billing_address_checkbox')->getData()) {
                        $validationGroups[] = ValidationGroup::CHECKED_BILLING_ADDRESS;
                    }

                    if(!$form->get('contact_address_checkbox')->getData()) {
                        $validationGroups[] = ValidationGroup::CHECKED_CONTACT_ADDRESS;
                    }

                    return $validationGroups;
                }
            ));
    }
}
