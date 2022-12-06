<?php

namespace App\Form;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function __construct(private CategoryRepository $cr)
    {
    }

    /**
     * @return array<string,Category>
     */
    public function getCategories(Category $category): array
    {
        $choices = [];
        $recursive = function (Category $currentCategory, int $lvl = 0) use (&$choices, &$recursive, $category) {
            if ($currentCategory === $category) {
                return;
            }
            $choices[str_repeat('-', $lvl).' '.$currentCategory->getName()] = $currentCategory;
            foreach ($currentCategory->getSubCategories() as $subCategory) {
                $recursive($subCategory, $lvl + 1);
            }
        };

        $rootCategories = $this->cr->findRootCategories();
        foreach ($rootCategories as $rootCategory) {
            $recursive($rootCategory);
        }

        return $choices;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('parent', ChoiceType::class, [
                'choices' => $this->getCategories($options['data']),
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
