<?php

namespace App\Controller;

use App\Entity\Imgur;
use App\Form\ImgurType;
use App\Repository\ImgurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @psalm-type ImgurResponse = array{data: array{id: string,title: null,description: null,datetime: int,type: string,animated: bool,width: int,height: int,size: int,views: int,bandwidth: int,vote: null,favorite: bool,nsfw: null,section: null,account_url: null,account_id: int,is_ad: bool,in_most_viral: bool,has_sound: bool,tags: list<string>,ad_type: int,ad_url: string,edited: string,in_gallery: bool,deletehash: string,name: string,link: string},success: bool,status: int}
 */
#[Route('/imgur')]
class ImgurController extends AbstractController
{
    #[Route('/', name: 'app_imgur_index', methods: ['GET'])]
    public function index(ImgurRepository $imgurRepository): Response
    {
        return $this->render('imgur/index.html.twig', [
            'imgurs' => $imgurRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_imgur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ImgurRepository $imgurRepository, HttpClientInterface $imgurClient): Response
    {
        $imgur = new Imgur();

        $form = $this->createForm(ImgurType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile */
            $image = $form->get('image')->getData();

            /** @var ImgurResponse */
            $response = $imgurClient->request('POST', '/3/image', [
                'body' => \fopen($image->getPathname(), 'r'),
            ])->toArray();

            $imgur->setLink($response['data']['link']);

            $imgurRepository->save($imgur, true);

            return $this->redirectToRoute('app_imgur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('imgur/new.html.twig', [
            'imgur' => $imgur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_imgur_show', methods: ['GET'])]
    public function show(Imgur $imgur): Response
    {
        return $this->render('imgur/show.html.twig', [
            'imgur' => $imgur,
        ]);
    }

    #[Route('/{id}', name: 'app_imgur_delete', methods: ['POST'])]
    public function delete(Request $request, Imgur $imgur, ImgurRepository $imgurRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$imgur->getId(), (string) $request->request->get('_token'))) {
            $imgurRepository->remove($imgur, true);
        }

        return $this->redirectToRoute('app_imgur_index', [], Response::HTTP_SEE_OTHER);
    }
}
