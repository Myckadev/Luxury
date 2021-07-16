<?php

namespace App\Service\Panier;

use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PanierService{

    public $session;
    public $articleRepository;

    public function __construct(SessionInterface $session, ArticleRepository $articleRepository){

        $this->session = $session;
        $this->articleRepository = $articleRepository;

    }

    public function add(int $id){
        $panier = $this->session->get('panier', []);

        if (!empty($panier[$id])) {
            $panier[$id]++;
        }else{
            $panier[$id] = 1;
        }

        $this->session->set('panier', $panier);
    }

    public function remove(int $id){
        $panier = $this->session->get('panier', []);

        if(!empty($panier[$id]) && $panier[$id] > 1){
            $panier[$id]--;
        }else{
            unset($panier[$id]);
        }
        $this->session->set('panier', $panier);
    }

    public function delete(int $id){

        $panier = $this->session->get('panier', []);

        if(!empty($panier[$id])){
            unset($panier[$id]);
        }
        $this->session->set('panier', $panier);
    }

    public function deleteAll(){
        $this->session->set('panier', []);
    }

    public function getFullPanier(){

        $panier = $this->session->get('panier', []);

        $panierDetails = [];

        foreach ($panier as $id => $qtt){
            $panierDetails[]=[

                'article'=>$this->articleRepository->find($id),
                'qtt'=>$qtt,
                'totalByArticle'=>$this->articleRepository->find($id)->getPrix()*$qtt

            ];
        }
        return $panierDetails;
    }

    public function getTotal(){

        $total = 0;

        foreach ($this->getFullPanier() as $item){
            $total += $item['article']->getPrix()* $item['qtt'];
        }

        return $total;

    }


}