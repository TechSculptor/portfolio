<?php

// A namespace groups classes in a "logical folder" (here App\Form)
// to avoid name collisions between classes (two "Type" classes
// can coexist in different namespaces) and to enable autoloading:
// PHP/Composer finds the class file from its namespace
// (App\Form\PartSearchType -> src/Form/PartSearchType.php), without a manual include.

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PartSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('query', SearchType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Search...',
                    'class' => 'w-full p-3 pl-10 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'method' => Request::METHOD_GET,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
