<?php

namespace App\Form;

use App\Entity\Anmeldung;
use App\Entity\Ruestzeit;
use App\Enum\MealType;
use App\Enum\RoomType;
use App\Generator\CurrentRuestzeitGenerator;
use App\Service\DynamicTranslationService;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\CustomField;
use App\Enum\CustomFieldType;

class AnmeldungType extends AbstractType
{
    public function __construct(
        private CurrentRuestzeitGenerator $currentRuestzeitGenerator,
        private DynamicTranslationService $translationService
    ) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentRuestzeit = $this->currentRuestzeitGenerator->get();

        $builder
            ->add('ruestzeit', EntityType::class, [
                // looks for choices from this entity
                'class' => Ruestzeit::class,

                // uses the User.username property as the visible option string
                'choice_label' => 'title',

                // used to render a select box, check boxes or radios
                // 'multiple' => true,
                // 'expanded' => true,
            ])
            ->add('firstname', TextType::class)
            ->add('lastname', TextType::class)
            ->add('phone', TextType::class)
            ->add('email', EmailType::class);

        if ($currentRuestzeit->isShowRegistrationAddress()) {
            $builder->add('postalcode', TextType::class)
            ->add('city', TextType::class)
            ->add('address', TextType::class);
        }

        if ($currentRuestzeit->isShowBirthday()) {
            $builder->add('birthdate', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd.MM.yyyy',
            ]);
        }

        if ($currentRuestzeit->isAskSchoolclass()) {
            $builder->add('schoolclass', TextType::class, [
                'label' => $this->translationService->trans("Schulklasse")
            ]);
        }

        if ($currentRuestzeit->isShowRoomRequest()) {
            $builder->add('roomRequest', EnumType::class, [
                'class' => RoomType::class,
                'label' => $this->translationService->translate("Wunsch der Unterbringung"),
                'help' => $this->translationService->translate("Wunsch der Unterbringung Hilfe", default: ""),
            ]);
        }
        if ($currentRuestzeit->isShowRoommate()) {
            $builder->add(
                'roommate',
                TextType::class,
                [
                    'label' => $this->translationService->translate("Doppelzimmer mit"),
                    'help' => $this->translationService->translate("Doppelzimmer mit Hilfe", default: ""),
                ]
            );
        }

        if ($currentRuestzeit->isShowReferer()) {
            $builder->add('referer', TextType::class, [
                'label' => $this->translationService->translate("Eingeladen von"),
                'help' => $this->translationService->translate("Eingeladen von Hilfe", default: ""),
            ]);
        }

        if ($currentRuestzeit->isShowMealtype()) {
            $builder->add('mealtype', EnumType::class, [
                'class' => MealType::class
            ]);
        }
        if ($currentRuestzeit->haveAdditionalQuestion1()) {
            $builder->add('additional_data1', TextType::class, [
                'label' => $currentRuestzeit->getAdditionalQuestion1()
            ]);
        }

        $builder
            ->add('notes', TextareaType::class, [
                "empty_data" => "",
            ]);

        // Add custom fields based on Ruestzeit configuration
        foreach ($currentRuestzeit->getCustomFields() as $customField) {
            $fieldName = 'custom_field_' . $customField->getId();

            switch ($customField->getType()) {
                case CustomFieldType::INPUT:
                    $builder->add($fieldName, TextType::class, [
                        'mapped' => false,
                        'label' => $customField->getTitle(),
                        'required' => !$customField->isOptional(),
                    ]);
                    break;
                case CustomFieldType::TEXTAREA:
                    $builder->add($fieldName, TextareaType::class, [
                        'mapped' => false,
                        'label' => $customField->getTitle(),
                        'required' => !$customField->isOptional(),
                    ]);
                    break;
                case CustomFieldType::DATE:
                    $builder->add($fieldName, DateType::class, [
                        'mapped' => false,
                        'label' => $customField->getTitle(),
                        'required' => !$customField->isOptional(),
                        'widget' => 'single_text',
                        'html5' => false,
                        'format' => 'dd.MM.yyyy',
                    ]);
                    break;
                case CustomFieldType::CHECKBOX:
                    $options = $customField->getOptions() ?? [];

                    $builder->add($fieldName, ChoiceType::class, [
                        'mapped' => false,
                        'label' => $customField->getTitle(),
                        'required' => !$customField->isOptional(),
                        'choices' => array_combine($options, $options),
                        'expanded' => true,
                        'multiple' => true,
                    ]);
                    break;
                case CustomFieldType::RADIO:
                    $options = $customField->getOptions() ?? [];
                    $builder->add($fieldName, ChoiceType::class, [
                        'mapped' => false,
                        'label' => $customField->getTitle(),
                        'required' => !$customField->isOptional(),
                        'choices' => array_combine($options, $options),
                        'expanded' => true,
                    ]);
                    break;
            }
        }

        $builder
            ->add('dsgvo_agree')->setRequired(true)
            ->add('agb_agree')->setRequired(true);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'data_class' => Anmeldung::class,
        ]);
    }
}
