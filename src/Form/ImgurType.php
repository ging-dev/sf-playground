<?php

namespace App\Form;

use App\Entity\Imgur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @psalm-type ImgurResponseType = array{
 *     data: array{
 *         id: string,
 *         title: null,
 *         description: null,
 *         datetime: int,
 *         type: string,
 *         animated: bool,
 *         width: int,
 *         height: int,
 *         size: int,
 *         views: int,
 *         bandwidth: int,
 *         vote: null,
 *         favorite: bool,
 *         nsfw: null,
 *         section: null,
 *         account_url: null,
 *         account_id: int,
 *         is_ad: bool,
 *         in_most_viral: bool,
 *         has_sound: bool,
 *         tags: list<string>,
 *         ad_type: int,
 *         ad_url: string,
 *         edited: string,
 *         in_gallery: bool,
 *         deletehash: string,
 *         name: string,
 *         link: string
 *     },
 *     success: bool,
 *     status: int
 * }
 */
class ImgurType extends AbstractType
{
    public function __construct(private HttpClientInterface $imgurClient)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, [
                'mapped' => false,
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $formEvent) {
                /** @var array{image: UploadedFile} */
                $data = $formEvent->getData();
                $form = $formEvent->getForm();

                try {
                    /** @psalm-var ImgurResponseType */
                    $response = $this->imgurClient->request('POST', '/3/image', [
                        'body' => \fopen($data['image']->getPathname(), 'r'),
                    ])->toArray();
                } catch (ClientException $e) {
                    $form->addError(new FormError($e->getMessage()));

                    return;
                }

                $form->add('link', TextType::class, [
                    'empty_data' => $response['data']['link'],
                ]);
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Imgur::class,
        ]);
    }
}
