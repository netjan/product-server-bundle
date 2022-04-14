<?php

namespace NetJan\ProductServerBundle\Form;

use NetJan\ProductServerBundle\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required' => $options['fields_required'],
            ])
            ->add('amount', IntegerType::class, [
                'required' => $options['fields_required'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => Product::class,
            'fields_required' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
