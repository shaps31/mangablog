<?php

namespace App\Form;

use App\Entity\Post;
use App\Entity\Category;
use App\Entity\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// ⬇️ types de champs utiles
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('slug')
            ->add('content')

            // Statut lisible
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Brouillon' => 'draft',
                    'Publié'    => 'published',
                ],
                'placeholder' => 'Choisir un statut',
            ])

            // Date/heure simple
            ->add('publishedAt', null, [
                'widget' => 'single_text',
                'required' => false,
            ])

            // Cover = URL d’image (simple)
            ->add('cover', UrlType::class, [
                'required' => false,
                'help' => 'Colle une URL d’image (ex: https://exemple.com/cover.jpg)',
            ])

            ->add('rating', null, [
                'required' => false,
            ])

            // Catégorie via EntityType
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une catégorie',
            ])

            // Tags = select multiple (Ctrl/Cmd + clic pour plusieurs)
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false, // false = liste déroulante multi; true = cases à cocher
                'required' => false,
                'help' => 'Astuce: Ctrl/Cmd + clic pour sélectionner plusieurs',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
