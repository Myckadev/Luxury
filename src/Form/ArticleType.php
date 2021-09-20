<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options){

        if($options['ajout']==true){
            $builder
                ->add('nom', TextType::class, [
                    'required'=>false,
                    'label'=>false,
                    'attr'=>[
                        'placeholder'=>"Nom de l'article"
                    ]
                ])
                ->add('categori', EntityType::class, [
                    'required'=>false,
                    'class'=>Category::class,
                    'choice_label'=>"nom",
                    'attr'=>[
                        'placeholder'=>"Choix de la catégorie"
                    ]
                ])
                ->add('prix', NumberType::class, [
                    'required'=>false,
                    'label'=>false,
                    'attr'=>[
                        'placeholder'=>"Prix de l'article"
                    ]
                ])
                ->add('picture', FileType::class, [
                    'required'=>false,
                    'label'=>false
                ])
                ->add('description', TextareaType::class,[
                    'required'=>false,
                    'label'=>false,
                    'attr'=>[
                        'placeholder'=>"Description de l'article (max 2000 caractères)",
                        'max_length'=>2000,
                        
                    ]
                ])
                ->add('Save', SubmitType::class)
            ;

        }else{
            $builder
                ->add('nom', TextType::class, [
                    'required'=>false,
                    'label'=>false,
                    'attr'=>[
                        'placeholder'=>"Nom de l'article"
                    ]
                ])
                ->add('categori', EntityType::class, [
                    'required'=>false,
                    'class'=>Category::class,
                    'choice_label'=>"nom",
                    'attr'=>[
                        'placeholder'=>"Choix de la catégorie"
                    ]
                ])
                ->add('prix', NumberType::class, [
                    'required'=>false,
                    'label'=>false,
                    'attr'=>[
                        'placeholder'=>"Prix de l'article"
                    ]
                ])
                ->add('editPicture', FileType::class, [
                    'required'=>false,
                    'label'=>false
                ])
                ->add('description', TextareaType::class,[
                    'required'=>false,
                    'label'=>false,
                    'attr'=>[
                        'placeholder'=>"Description de l'article (max 2000 caractères)",
                        'max_length'=>2000,
                        'rows'=>10,
                        'max_rows'=>10
                    ]
                ])
                ->add('Save', SubmitType::class)
            ;
        }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'ajout'=>false
        ]);
    }
}
