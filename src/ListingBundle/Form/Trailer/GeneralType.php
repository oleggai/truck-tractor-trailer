<?php

namespace ListingBundle\Form\Trailer;

use Doctrine\ORM\EntityRepository;
use ListingBundle\Entity\Listing\Item\TrailerItem;
use ListingBundle\Entity\Vehicle\Brand;
use ListingBundle\Entity\Vehicle\Condition;
use ListingBundle\Entity\Vehicle\Model;
use ListingBundle\Entity\Vehicle\TrailerType;
use ListingBundle\Service\Vehicle\TypeService;
use LocationBundle\Form\AddressType;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;

class GeneralType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('name', TextType::class, [
            'label' => 'Name',
            'constraints' => [
                new NotBlank(['message' => 'Name is required'])
            ]
        ]);

        $type = TypeService::TRAILER;
        $builder->add('type', EntityType::class, [
            'class' => TrailerType::class,
            'choice_label' => 'name',
            'placeholder' => '-- choose --',
            'label' => 'Trailer Type',
            'query_builder' => function (EntityRepository $repository) {
                return $repository->createQueryBuilder('t')
                    ->orderBy('t.name', 'ASC');
            },
            'constraints' => [
                /*new NotBlank(['message' => 'Trailer Type is required'])*/
            ]
        ]);
        $builder->add('brand', EntityType::class, [
            'class' => Brand::class,
            'choice_label' => 'name',
            'placeholder' => '-- choose --',
            'label' => 'Trailer Brand',
            'query_builder' => function (EntityRepository $repository) use ($type) {
                return $repository->createQueryBuilder('b')
                    ->innerJoin('b.types', 't')
                    ->where('t.name = :type_name')
                    ->setParameters(['type_name' => $type])
                    ->orderBy('b.name', 'ASC');
            },
            'constraints' => [
                new NotBlank(['message' => 'Brand is required'])
            ]
        ]);
        $builder->add('model', EntityType::class, [
            'class' => Model::class,
            'choice_label' => 'name',
            'placeholder' => '-- choose --',
            'label' => 'Trailer Model',
            'query_builder' => function (EntityRepository $repository) use ($type) {
                return $repository->createQueryBuilder('m')
                    ->innerJoin('m.type', 't')
                    ->where('t.name = :type_name')
                    ->setParameters(['type_name' => $type])
                    ->orderBy('m.name', 'ASC');
            },
            'constraints' => [
                new NotBlank(['message' => 'Model is required'])
            ]
        ]);
        $builder->add('year', IntegerType::class, [
            'label' => 'Year',
            'constraints' => [
                new NotBlank(['message' => 'Year is required']),
                new Type([
                    'type' => 'integer',
                    'message' => 'Year should be an integer'
                ]),
                new Range([
                    // TODO move minimal year to system variables
                    'min' => 1900,
                    'minMessage' => 'Year cannot be less than {{ limit }}',
                    'max' => (int)date('Y'),
                    'maxMessage' => 'Year cannot be greater than {{ limit }}'
                ])
            ]
        ]);
        $builder->add('condition', EntityType::class, [
            'class' => Condition::class,
            'choice_label' => 'name',
            'placeholder' => '-- choose --',
            'label' => 'Condition',
            'constraints' => [
                new NotBlank(['message' => 'Constraint is required']),
            ]
        ]);
        $builder->add('multiple', CheckboxType::class, [
            'label' => 'I have multiple vehicles of the same Brand, Model, and Year'
        ]);
        $builder->add('items', CollectionType::class, [
            'entry_type' => ItemType::class,
            'entry_options' => [
                'label' => false,
                'data_class' => TrailerItem::class
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'label' => false,
            'attr' => [
                'class' => 'collection-type',
                'data-checkbox' => 'multiple'
            ]
        ]);
        $builder->add('fleet_maintained', CheckboxType::class, [
            'label' => 'This vehicle has been fleet maintained'
        ]);
        $builder->add('address', AddressType::class, [
            'label' => 'Address'
        ]);
        $builder->add('contact_address', AddressType::class, [
            'label' => 'Contact Address'
        ]);
        $builder->add('contact_phone', TextType::class, [
            'label' => 'Contact Phone',
            'constraints' => [
                /*new NotBlank(['message' => 'Contact Phone is required']),*/
                new PhoneNumber(['defaultRegion' => 'US'])
            ]
        ]);
        $builder->add('contact_email', EmailType::class, [
            'label' => 'Contact Email',
            'constraints' => [
                /*new NotBlank(['message' => 'Contact Email is required']),*/
                new Email(['strict' => true])
            ]
        ]);
        $builder->add('description', TextareaType::class, [
            'label' => 'Description',
            'constraints' => [
                new NotBlank(['message' => 'Description is required'])
            ]
        ]);
        $builder->add('allow_offers', CheckboxType::class, [
            'label' => 'Allow Offers'
        ]);
        $builder->add('allow_partial_offers', CheckboxType::class, [
            'label' => 'Allow Partial Offers'
        ]);
        $builder->add('approved_by_user', CheckboxType::class, [
            'label' => 'Published'
        ]);
    }

}