<?php

namespace App\Form;

use App\Entity\Post;
use App\Entity\Category;
use App\Entity\Tag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('slug') // on le tape à la main (simple à expliquer)
            ->add('content')
            ->add('status', ChoiceType::class, [
                'choices' => ['Brouillon' => 'draft', 'Publié' => 'published'],
                'placeholder' => 'Choisir un statut',
            ])
            ->add('publishedAt', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('cover', null, ['required' => false])
            ->add('rating', IntegerType::class, [
                'required' => false,
                'attr' => ['min' => 0, 'max' => 10],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une catégorie',
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
            ]);
        // PAS de champ author ici : on le fixe dans le contrôleur.
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // CRUCIAL : sans ça, l’hydratation ne se fait pas
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
