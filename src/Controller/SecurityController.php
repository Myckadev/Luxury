<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    
    /**
     * @Route("/register", name="register")
     */
     public function register(EntityManagerInterface $entityManager, Request $request, UserPasswordHasherInterface $encoder){

        $user = new User();

        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $password = $user->getPassword();
            $hashPassword = $encoder->hashPassword($user ,$password);
            $user->setPassword($hashPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', "Inscription réussi !");
            return $this->redirectToRoute('home');
        }

        return $this->render('security/registration.html.twig', [
            'form'=>$form->createView()
        ]);
     }

    /**
    * @Route("/login", name="login")
    */
    public function login(){


        return $this->render('security/login.html.twig');
    }

    /**
    * @Route("/logout", name="logout")
    */
    public function logout(){

    }

    
}
