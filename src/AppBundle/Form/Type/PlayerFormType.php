<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class PlayerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('turnNumber'/*, HiddenType::class*/)
            ->add('move', ChoiceType::class, [
                'choices' => $this->getMoves($builder),
                'choices_as_values' => true,
                'choice_label' => function ($move, $key, $index) {
                    return $move->getLabel();
                },
                'choice_value' => function ($move) {
                    return $move ? $move->toValue() : '';
                },
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('play', SubmitType::class, array('label' => 'Play'));
    }

    private function getMoves(FormBuilderInterface $builder)
    {
        $playerForm = $builder->getData();

        return $playerForm->getMoves();
    }
}
