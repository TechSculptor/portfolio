<?php

// Un namespace regroupe les classes dans un "dossier logique" (ici App\Form)
// pour éviter les collisions de noms entre classes (deux classes "Type"
// peuvent coexister dans des namespaces différents) et permettre l'autoload :
// PHP/Composer retrouve le fichier de la classe à partir de son namespace
// (App\Form\PartSearchType -> src/Form/PartSearchType.php), sans include manuel.
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
