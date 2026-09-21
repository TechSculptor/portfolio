<?php

namespace App\Form;

use App\Entity\Starship;
use App\Entity\StarshipPart;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class StarshipPartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'constraints' => [
                    new NotBlank(message: 'Every part should have a name!'),
                ],
            ])
            ->add('price', null, [
                'constraints' => [
                    new GreaterThan(value: 0, message: 'Starship part cannot be free'),
                    new NotBlank(message: 'You forgot to set the price!'),
                ],
                'help' => 'We don\'t allow free parts! Set up a price',
            ])
            ->add('notes')
            ->add('starship', EntityType::class, [
                'priority' => 10,
                'class' => Starship::class,
                'choice_label' => function (Starship $starship) {
                    return sprintf(
                        '%s (by %s)',
                        $starship->getName(),
                        $starship->getCaptain(),
                    );
                },
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('starship')
                        ->orderBy('starship.name', Order::Ascending->value);
                },
            ])
            ->add('createAndAddNew', SubmitType::class, [
                'validate' => false,
                'attr' => [
                    'class' => 'text-white bg-blue-700 hover:bg-blue-800 rounded-lg px-5 py-2.5 me-2 mb-2 cursor-pointer',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StarshipPart::class,
        ]);
    }
}
