<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $o): void
    {
        $b
            ->add('firstName', TextType::class, [
                'required' => false, 'label' => 'Prénom',
            ])
            ->add('lastName', TextType::class, [
                'required' => false, 'label' => 'Nom',
            ])
            ->add('bio', TextareaType::class, [
                'required' => false, 'label' => 'Bio', 'attr' => ['rows' => 4],
            ])
            // Optionnel: on garde un champ URL au cas où tu préfères mettre un lien
            ->add('avatarUrl', TextType::class, [
                'required' => false, 'label' => 'Avatar (URL)',
                'help' => 'Si vous uploadez un fichier ci-dessous, il remplacera cette URL.',
            ])
            // Champ fichier non mappé: on gère l’upload dans le contrôleur
            ->add('avatarFile', FileType::class, [
                'mapped' => false, 'required' => false, 'label' => 'Avatar (fichier)',
                'constraints' => [
                    new File([
                        'maxSize' => '3M',
                        'mimeTypes' => ['image/png', 'image/jpeg', 'image/webp', 'image/gif'],
                        'mimeTypesMessage' => 'Formats acceptés: PNG, JPG, WEBP, GIF (<=3 Mo).',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
