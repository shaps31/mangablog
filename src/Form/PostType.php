<?php

namespace App\Form;

use App\Entity\Post;
use App\Entity\Category;
use App\Entity\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Types
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints\File;

final class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => ['placeholder' => 'Ex: Top 10 Shonen 2025'],
            ])
            ->add('slug', TextType::class, [
                'required' => false,
                'help' => 'Laissez vide pour générer automatiquement depuis le titre.',
            ])
            ->add('content', TextareaType::class)

            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Brouillon' => 'draft',
                    'Publié'    => 'published',
                ],
                'placeholder' => 'Choisir un statut',
            ])

            ->add('publishedAt', DateTimeType::class, [
                'widget'   => 'single_text',
                'required' => false,
            ])

            // ✅ Cover en URL (validation + input type=url)
            ->add('cover', UrlType::class, [
                'required' => false,
                'help' => 'Colle une URL d’image (ex: https://exemple.com/cover.jpg)',
                'attr' => ['placeholder' => 'https://exemple.com/cover.jpg'],
                'default_protocol' => 'https',
            ])
            ->add('coverFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(maxSize: '4M', mimeTypes: ['image/*']),
                ],
                // UX Dropzone : on attache le contrôleur Stimulus au champ
                'attr' => [
                    'data-controller' => 'symfony--ux-dropzone--dropzone',
                    'data-symfony--ux-dropzone--dropzone-multiple-value' => 'false',
                    // Optionnel : message placeholder
                    'data-symfony--ux-dropzone--dropzone-label-value' => 'Glissez une image ici ou cliquez',
                ],
            ])

            // ✅ Rating en entier
            ->add('rating', IntegerType::class, [
                'required'   => false,
                'empty_data' => '',
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
                'expanded' => false,
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
