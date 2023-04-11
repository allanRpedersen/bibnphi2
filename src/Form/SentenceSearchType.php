<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\Author;
use App\Entity\SentenceSearch;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class SentenceSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('stringToSearch', TextType::class, [
				'required' => false,
				'label' => false,
				'attr' => [
					'style' => 'width: 100%',
					'placeholder' => 'un mot/une phrase',
				],
			] )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
			'data_class' => SentenceSearch::class,
			'method' => 'get',
			'csrf_protection' => false,
        ]);
	}
	
	// public function getBlockPrefix()
	// {
	// 	return '';
	// }
}
