<?php

namespace ListingBundle\Form;

use ListingBundle\Service\AttributeService;
use ListingBundle\Service\Vehicle\TypeService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListingType extends AbstractType
{

    protected $attributeService;
    protected $typeService;

    public function __construct(TypeService $type, AttributeService $attribute)
    {
        $this->typeService = $type;
        $this->attributeService = $attribute;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $meta = $this->typeService->getMeta($options['type']);
        $tabBuilder = $builder->getFormFactory()->createNamedBuilder('tabs');
        $tabBuilder->add('general', $meta->getFormGeneralTypeClass(), ['label' => 'General Info']);
        $fieldsets = $this->attributeService->getFieldsets($meta->getEntityClass());
        foreach ($fieldsets as $fieldset) {
            $tabBuilder->add($this->attributeService->slugify($fieldset), OptionalTabType::class, [
                'label' => $fieldset,
                'type' => $meta->getEntityClass(),
                'fieldset' => $fieldset
            ]);
        }
        $tabBuilder->add('media', HiddenType::class, ['label' => 'Media']);
        $builder->add($tabBuilder);
        $builder->add('save', SubmitType::class, [
            'label' => 'Save'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('type');
    }

}