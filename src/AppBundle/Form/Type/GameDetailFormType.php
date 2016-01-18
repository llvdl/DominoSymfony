<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class GameDetailFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->canDeal($builder)) {
            $builder->add('dealGame', SubmitType::class, array('label' => 'Deal'));
        }
    }

    private function canDeal($builder)
    {
        return $builder->getData()->getCanDeal();
    }
}
