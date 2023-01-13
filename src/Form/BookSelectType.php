<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\Author;
use App\Entity\BookSelect;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class BookSelectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('authors', EntityType::class, [
				'class' => Author::class,
				'choice_label' => 'lastName',
				'query_builder' => function (EntityRepository $ar){
					return $ar->createQueryBuilder('u')
							->orderBy('u.lastName', 'ASC');
				},
				'required' => false,
				'multiple' => true,
				'label' => false,
			])
            ->add('books', EntityType::class, [
				'class' => Book::class,
				'choice_label' => 'title',
				'query_builder' => function (EntityRepository $br){
					return $br->createQueryBuilder('u')
							->orderBy('u.title', 'ASC');
				},
				'required' => false,
				'multiple' => true,
				'label' => false,
			] )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
			'data_class' => BookSelect::class,
			'method' => 'get',
			'csrf_protection' => false,
        ]);
	}
	
	// public function getBlockPrefix()
	// {
	// 	return '';
	// }
}
