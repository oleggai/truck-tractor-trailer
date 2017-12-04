<?php

namespace LocationBundle\Form;

use Doctrine\ORM\EntityRepository;
use ListingBundle\Validator\Constraints\ZipCode;
use LocationBundle\Entity\Address;
use LocationBundle\Entity\City;
use LocationBundle\Entity\CompanyAddress;
use LocationBundle\Entity\ContactAddress;
use LocationBundle\Entity\ListingAddress;
use LocationBundle\Entity\State;
use LocationBundle\Validation\ValidationGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddressType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $address = $event->getData();
            $form = $event->getForm();

            if (!is_null($address)) {
                if (is_object($address)) {
                    $form->get('state')->setData($address->getCity()->getState());
                } else {
                    $form->get('state')->setData($address['city']->getState());
                }
            }
        });

        $builder->add('line1', TextType::class, [
            'label' => 'Address Line 1',
            'required' => true,
            'constraints' => [
                new NotBlank(['message' => 'Please, enter the address line 1', 'groups' => [ValidationGroup::PROFILE]])
            ]
        ]);
        $builder->add('line2', TextType::class, [
            'label' => 'Address Line 2',
            'required' => false,
        ]);
        $builder->add('state', EntityType::class, [
            'mapped' => false,
            'required' => false,
            'class' => State::class,
            'choice_label' => 'name',
            'placeholder' => '-- choose --',
            'label' => 'State',
            'constraints' => [
                new NotBlank(['message' => 'Please, enter the state'])
            ],
            'query_builder' => function (EntityRepository $repository) {
                return $repository->createQueryBuilder('s')
                    ->orderBy('s.name', 'ASC');
            }
        ]);
        $builder->add('city', EntityType::class, [
            'class' => City::class,
            'choice_label' => 'name',
            'placeholder' => '-- choose --',
            'label' => 'City',
            'constraints' => [
                new NotBlank(['message' => 'Please choose your city', 'groups' => [
                    ValidationGroup::REGISTRATION, ValidationGroup::PROFILE, ValidationGroup::LISTING
                ]
                ])
            ],
            'query_builder' => function (EntityRepository $repository) {
                return $repository->createQueryBuilder('c')
                    ->orderBy('c.name', 'ASC');
            }
        ]);
        $builder->add('zip', TextType::class, [
            'label' => 'ZIP code',
            'constraints' => [
                new NotBlank(['message' => 'Please enter ZIP code', 'groups' => [
                    ValidationGroup::REGISTRATION, ValidationGroup::PROFILE
                ]]),
                new ZipCode(['groups' => [
                    ValidationGroup::REGISTRATION, ValidationGroup::PROFILE
                ]])
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([

            'attr' => ['novalidate' => 'novalidate'],
            'validation_groups' => function (FormInterface $form) {
                /** @var $address Address */
                $address = $form->getData();

                if($address instanceof CompanyAddress) {
                    return $address->getId() ?
                        [ValidationGroup::PROFILE, ValidationGroup::DEFAULT] :
                            [ValidationGroup::REGISTRATION, ValidationGroup::DEFAULT];
                }

                if($address instanceof ListingAddress) {
                    return [ValidationGroup::LISTING];
                }

                if($address instanceof ContactAddress) {
                    return [ValidationGroup::CONTACT];
                }

                return [ValidationGroup::DEFAULT];

            }

        ]);
    }

}