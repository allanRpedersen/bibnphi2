<?php

namespace App\Form;

use App\Form\GenericType;
use App\Entity\BookParagraph;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookParagraphType extends GenericType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('content',CKEditorType::class, $this->mkBasics( "description", 
                                                                  "(facultatif)",
                                                                  false,
                                                                  //
                                                                  // CKEditor config
                                                                  [
                                                                    'config' => [
                                                                        'uiColor' => '#0000FF', // blue !!
                                                                        'editorplaceholder' => "Une description détaillée de l'auteur (facultatif)"
                                                                        ]
                                                                  ]))
            // ->add('book')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BookParagraph::class,
        ]);
    }
}
