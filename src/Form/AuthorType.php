<?php

namespace App\Form;

use App\Entity\Author;
use App\Form\GenericType;
// use Symfony\Component\Form\AbstractType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class AuthorType extends GenericType
{


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
			->add('lastName', TextType::class, $this->mkBasics("nom", "le nom de l'auteur(e)"))
            ->add('firstName', TextType::class, $this->mkBasics("prénom", "son prénom", false))
            ->add('birthYear', TextType::class, $this->mkBasics("naissance","l'année de sa naissance", false))
            ->add('deathYear', TextType::class, $this->mkBasics("mort","celle de sa mort", false))
            ->add('summary', TextType::class, $this->mkBasics("présentation", "Une phrase de présentation", false))
            ->add('content',CKEditorType::class, $this->mkBasics( "description", 
                                                                  "Une description détaillée de l'auteur",
                                                                  false,
                                                                  //
                                                                  // CKEditor config
                                                                  [
                                                                    'config' => [
                                                                        'uiColor' => '#0000FF' // blue !!
                                                                        ]
                                                                  ]))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Author::class,
        ]);
    }
}
