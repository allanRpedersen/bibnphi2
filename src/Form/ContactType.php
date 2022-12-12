<?php

namespace App\Form;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
// use App\Form\GenericType;
// use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\FormBuilderInterface;
// use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\OptionsResolver\OptionsResolver;
// use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactType extends AbstractType
{


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('name', TextType::class)
        ->add('phone', TextType::class)
        ->add('email', TextType::class)
        ->add('message', TextareaType::class)
            
            // ->add('lastName',    TextType::class,      $this->mkBasics("nom / nom d'usage", "son nom ou nom d'usage"))
            // ->add('firstName',   TextType::class,      $this->mkBasics("prénom", "son prénom (facultatif)", false))
            // ->add('birthYear',   TextType::class,      $this->mkBasics("naissance","année de naissance (facultatif)", false))
            // ->add('deathYear',   TextType::class,      $this->mkBasics("mort","année de mort (facultatif)", false))
            // ->add('summary',     TextType::class,      $this->mkBasics("présentation", "Une phrase de présentation (facultatif)", false))
            // ->add('pictureFile', VichImageType::class, $this->mkBasics( "photo/portrait",
            //                                                             "Un portrait (facultatif)",
            //                                                             false,
            //                                                             [
            //                                                                 'imagine_pattern' => 'fp_thumb'
            //                                                             ]))
            // ->add('content',     CKEditorType::class,  $this->mkBasics( "description", 
            //                                                             "(facultatif)",
            //                                                       false,
            //                                                       //
            //                                                       // CKEditor config
            //                                                       [
            //                                                         'config' => [
            //                                                             'uiColor' => '#0000FF', // blue !!
            //                                                             'editorplaceholder' => "Une description détaillée de l'auteur (facultatif)"
            //                                                             ]
            //                                                       ]))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
