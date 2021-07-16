<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Service\Panier\PanierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{

    /**
     * @Route("/", name="home")
     */
     public function homePage(ArticleRepository $articleRepository){

         $articles = $articleRepository->findAll();

         return $this->render('front/home.html.twig', [
             'articles'=>$articles
         ]);
     }



    /**
     * @Route("/showPanier", name="showPanier")
     */
    public function showPanier(PanierService $panierService){

        $panier= $panierService->getFullPanier();
        $total = $panierService->getTotal();

        return $this->render('front/showPanier.html.twig', [
            'panier'=>$panier,
            'total'=>$total
        ]);
    }

}
