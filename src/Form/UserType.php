<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email')
            ->add('userRoles', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'title',
				'query_builder' => function ( EntityRepository $er ){
					return $er->createQueryBuilder('u')
							->orderBy('u.title', 'ASC');
				},
                'required' => false,
                'multiple' => true,
                // 'label' => false,
			])
            ->add('password')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'method' => 'get',
			'csrf_protection' => false,

        ]);
    }
}
