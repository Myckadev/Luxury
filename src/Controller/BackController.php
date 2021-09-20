<?php

namespace App\Controller;

use App\Entity\Achat;
use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Commande;
use App\Form\ArticleType;
use App\Form\CategoryType;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommandeRepository;
use App\Repository\UserRepository;
use App\Service\Panier\PanierService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Swift_Image;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mime\Encoder\EncoderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Validator\Constraints\Uuid;

class BackController extends AbstractController
{
    /**
     * @Route("/addArticle", name="addArticle")
     */
    public function addArticle(Request $request, EntityManagerInterface $entityManager){

        $article = new Article(); // ici on instancie un nouvel objet Article que l'on va charger avec les données du formulaire
        $form = $this->createForm(ArticleType::class, $article, array('ajout'=>true)); // on instancie un objet Form qui va controler automatiquement la correspondance des champs de formulaire (Contenu dans ArticleType) avec l'entité Article (contenu dans $article)
        $form->handleRequest($request);//la méthode handleRequest de Form nous permet de préparer la requête

        if($form->isSubmitted() && $form->isValid()){  //Si le formulaire a été soumis et qu'il est valide (booléan de correspondance généré dans le createForm)
            $article->setCreateAt(new DateTime('now'));

            $photo = $form->get('picture')->getData(); // On récupère l'input file photo de notre formulaire grâce ) getData, on obtient $_FILE dans son intégralité

            if($photo){
                $nomPhoto = date('YmdHis').uniqid().$photo->getClientOriginalName(); // Permet de s'assurer de lui générer un id unique

                $photo->move($this->getParameter('upload_directory'), $nomPhoto); //equivalent de move_uploaded_file() en symfony attendant 2 paramètres, la direction de l'upload (Config/service.yaml, parameters: et le nom du fichier à insérer)
                $article->setPicture($nomPhoto);

                $entityManager->persist($article); //manager de symfony fait le lien entre l'entité et la BDD via l'orm (Object relationnal mapping) Doctrine.  Grâce à la méthode persist() il conserve en mémoire la requête préparé.
                $entityManager->flush(); // ici la méthode flush execute les requêtes en mémoire
                $this->addFlash('success', 'Article bien ajouté');
                return $this->redirectToRoute('listeArticle');
            }

        }

        return $this->render('back/addArticle.html.twig', [
            'form'=>$form->createView()
        ]);
    }

    /**
     * @Route("/listArticle", name="listeArticle")
     */
     public function listArticle(ArticleRepository $articleRepository){

         $articles = $articleRepository->findAll();

         return $this->render('back/listeArticle.html.twig', [
            'articles'=>$articles
         ]);
     }


     /**
      * @Route("/editArticle/{id}", name="editArticle")
      */
    public function editArticle(Article $article, Request $request, EntityManagerInterface $entityManager){

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()){
                $photo = $form->get('editPicture')->getData();

                if($photo){
                    $nomPhoto = date('YmdHis').uniqid().$photo->getClientOriginalName();

                    $photo->move($this->getParameter('upload_directory'), $nomPhoto);
                    unlink($this->getParameter('upload_directory').'/'.$article->getPicture());
                    $article->setPicture($nomPhoto);


                }
                $entityManager->persist($article);
                $entityManager->flush();
                $this->addFlash('success', 'Article bien modifié');
                return $this->redirectToRoute('listeArticle');

            }

            return $this->render('back/editArticle.html.twig', [
                'form'=>$form->createView(),
                'article'=>$article
            ]);

    }

    /**
     * @Route("/delArticle/{id}", name="delArticle")
     */
    public function delArticle(Article $article, EntityManagerInterface $manager){

        $manager->remove($article);
        $manager->flush();
        $this->addFlash('success', "L'article a bien été supprimé");

        return $this->redirectToRoute('listeArticle');

    }

    /**
     * @Route("/addCategory", name="addCategory")
     * @Route("/editCategory/{id}", name="editCategory")
     */
    public function category(Category $category=null, EntityManagerInterface $entityManager, Request $request){

        if(!$category){
            $category = new Category();
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($category);
            $entityManager->flush();
            $this->addFlash("sucess", "La catégorie a bien été créée");

            return $this->redirectToRoute('listCategory');
        }

        return $this->render("back/category.html.twig", [
           'form'=>$form->createView()
        ]);

    }

    /**
     * @Route("/listCategory", name="listCategory")
     */
    public function listCategory(CategoryRepository $categoryRepository){
        $categories = $categoryRepository->findAll();

        return $this->render('back/listeCategory.html.twig', [
            'categories'=>$categories
        ]);
    }

    /**
     * @Route("/delCategory/{id}", name="delCategory")
     */
    public function delCategory(EntityManagerInterface $entityManager, Category $category){

        $entityManager->remove($category);
        $entityManager->flush();
        $this->addFlash('success', "La catégorie a bien été supprimé");

        return $this->redirectToRoute('listCategory');
    }

    /**
     * @Route("/addPanier/{id}", name="addPanier")
     */
    public function addPanier(int $id, PanierService $panierService){
        
        $panierService->add($id);
        $panier = $panierService->getFullPanier();
        $total = $panierService->getTotal();

        return $this->redirectToRoute('home', [
            'panier'=>$panier,
            'total'=>$total
        ]);
    }


    /**
     * @Route("/plusPanier/{id}", name="plusPanier")
     */
    public function plusPanier(int $id, PanierService $panierService){

        $panierService->add($id);
        $panier = $panierService->getFullPanier();
        $total = $panierService->getTotal();

        return $this->redirectToRoute('showPanier', [
            'panier'=>$panier,
            'total'=>$total
        ]);
    }

    /**
     * @Route("/removePanier/{id}", name="removePanier")
     */
    public function removePanier(int $id, PanierService $panierService){

        $panierService->remove($id);
        $panier = $panierService->getFullPanier();
        $total = $panierService->getTotal();

        return $this->redirectToRoute('showPanier', [
            'panier'=>$panier,
            'total'=>$total
        ]);
    }
    
    /**
     * @Route("/clearPanier", name="clearPanier")
     */
    public function clearPanier(PanierService $panierService){

        $panierService->deleteAll();
        $panier = $panierService->getFullPanier();
        $total = $panierService->getTotal();

        return $this->redirectToRoute('showPanier', [
            'panier'=>$panier,
            'total'=>$total
        ]);
    }

    /**
     * @Route("/deleteArticleFromPanier/{id}", name="deleteArticleFromPanier")
     */
    public function deleteArticleFromPanier(int $id,PanierService $panierService){

        $panierService->delete($id);
        $panier = $panierService->getFullPanier();
        $total = $panierService->getTotal();

        return $this->redirectToRoute('showPanier', [
            'panier'=>$panier,
            'total'=>$total
        ]);

    }

    /**
     * @Route("/confirmCommand", name="confirmCommand")
     */
    public function confirmCommand(PanierService $panierService, SessionInterface $session, EntityManagerInterface $entityManager){

        $panier = $panierService->getFullPanier();
        $commande = new Commande();
        $commande->setMontantTotal($panierService->getTotal());
        $commande->setUser($this->getUser());
        $commande->setDate(new DateTime());
        $commande->setStatut(0);

        foreach ($panier as $item){
            $article = $item['article'];
            $achat = new Achat();
            $achat->setArticle($article);
            $achat->setQuantite($item['qtt']);
            $achat->setTotalByArticle($item['qtt'], $article);
            $achat->setCommande($commande);

            $entityManager->persist($achat);

        }
        $entityManager->persist($commande);
        $entityManager->flush();

        $panierService->deleteAll();
        $this->addFlash('success', "Votre commande à bien été prise en compte");

        return $this->redirectToRoute('listeCommandes');

    }

    /**
     * @Route("/listeCommandes", name="listeCommandes")
     */
    public function listeCommandes(CommandeRepository $commandeRepository){

        $commande = $commandeRepository->findBy(['user'=>$this->getUser()]);

        return $this->render('front/listeCommandes.html.twig', [
            'commandes'=>$commande
        ]);
    }

    /**
     * @Route("/gestionCommande", name="gestionCommande")
     */
    public function gestionCommande(CommandeRepository $commandeRepository){

        $commande = $commandeRepository->findBy([], ['statut'=>'ASC']);

        return $this->render('back/gestionCommande.html.twig', [
            "commandes"=>$commande
        ]);
    }

    /**
     * @Route("/status/{id}/{param}", name="status")
     */
    public function status(CommandeRepository $commandeRepository, EntityManagerInterface $entityManager, $id, $param){

        $command = $commandeRepository->find($id);
        $command->setStatut($param);

        $entityManager->persist($command);
        $entityManager->flush();

        return $this->redirectToRoute('gestionCommande');
    }

    /**
     * @Route("/userManager", name="userManager")
     */
    public function userManager(UserRepository $userRepository){

        $users = $userRepository->findAll();

        return $this->render('back/userManager.html.twig', [
            'users'=>$users,
        ]);
    }

    /**
     * @Route("/sendMail", name="sendMail")
     */
    public function sendMail(Request $request){

        $transporter = (new Swift_SmtpTransport('smtp.gmail.com', 465, 'ssl'))
        ->setUsername('contact.bloutime@gmail.com')
        ->setPassword('W5ciY9Yv7');

        $mailer = new Swift_Mailer($transporter);

        $mess = $request->request->get('message');
        $name = $request->request->get('name');
        $surname = $request->request->get('surname');
        $subject = $request->request->get('need');
        $from = $request->request->get('email');

        $message = (new Swift_Message($subject))
            ->setFrom($from)
            ->setTo($transporter->getUsername());

        $cid = $message->embed(Swift_Image::fromPath('upload/mailogo.png'));
        $message->setBody(

            $this->renderView('mail/mailTemplate.html.twig', [
                'from'=>$from,
                'name'=>$name,
                'surname'=>$surname,
                'subject'=>$subject,
                'message'=>$mess,
                'picture'=>$cid,
                'objectif'=>'Aller vers le site',
                'liens'=>'http://127.0.0.1:8001/'

            ]),
            'text/html'

        );

        $mailer->send($message);

        $this->addFlash('success', "Votre mail à bien été envoyé");
        return $this->redirectToRoute('mailForm');
    }

    /**
     * @Route("/mailForm", name="mailForm")
     */
    public function mailForm(){

        return $this->render('mail/mailForm.html.twig', [

        ]);
    }


    /**
     * @Route("/mailTemplate", name="mailTemplate")
     */
    public function mailTemplate(){

        return $this->render('mail/mailTemplate.html.twig', [

        ]);
    }

    /**
     * @Route("/forgotPassword", name="forgotPassword")
     */
    public function forgotPassword(Request $request, UserRepository $repository, EntityManagerInterface $entityManager){

        if($_POST){
            $email = $request->request->get('email');
            $user = $repository->findOneBy(['email'=>$email]);

            if($user){

                $transporter = (new Swift_SmtpTransport('smtp.gmail.com', 465, 'ssl'))
                    ->setUsername('contact.bloutime@gmail.com')
                    ->setPassword('W5ciY9Yv7');

                $mailer = new Swift_Mailer($transporter);

                $mess = "Vous avez fait une demande de changement de mot de passe.
                Si elle ne viens pas de vous, ignorer ce mail.
                Sinon cliquer sur le lien ci-dessous";

                $name = "";
                $surname = "";
                $subject = "Mot de passe oublié";
                $from = $transporter->getUsername();

                $message = (new Swift_Message($subject))
                    ->setFrom($from)
                    ->setTo($email);

                $mail = $user->getId();
                $token = uniqid();
                $user->setReset($token);

                $entityManager->persist($user);
                $entityManager->flush();

                $cid = $message->embed(Swift_Image::fromPath('upload/mailogo.png'));
                $message->setBody(

                    $this->renderView('mail/mailTemplate.html.twig', [
                        'from'=>$from,
                        'name'=>$name,
                        'surname'=>$surname,
                        'subject'=>$subject,
                        'message'=>$mess,
                        'picture'=>$cid,
                        'objectif'=>'Réinitialiser',
                        'liens'=>'http://127.0.0.1:8001/resetToken/'.$mail.'/'.$token

                    ]),
                    'text/html'

                );

                $mailer->send($message);
                $this->addFlash('success', "Un liens pour réinitialisé votre mot de passe a été envoyé à votre adresse mail");
                $this->redirectToRoute('forgotPassword');
            }else{
                $this->addFlash('error', 'Aucun utilisateur ne correspond à cet email');
                $this->redirectToRoute('forgotPassword');
            }
        }

        return $this->render('security/forgotPassword.html.twig');
    }

    /**
     * @Route("/resetToken/{email}/{token}", name="resetToken")
     */
    public function resetToken($email, $token, UserRepository $userRepository){

        $user = $userRepository->findOneBy(['id'=>$email, 'reset'=>$token]);
        //dd($user);
        if($user){
            return $this->render('security/resetPassword.html.twig',[
                'id'=>$user->getId()
            ]);
        }else{
            $this->addFlash('error', "Une erreur s'est produite, refaire une demande de réinitialisation");
            return $this->redirectToRoute('login');
        }

    }

    /**
     * @Route("/resetPassword", name="resetPassword")
     */
    public function resetPassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher, UserRepository $userRepository){


        if($_POST){
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirmPassword');
            $id = $request->request->get('id');
            if($password == $confirmPassword){

                $user = $userRepository->find($id);
                $hash = $userPasswordHasher->hashPassword($user, $password);
                $user->setPassword($hash);
                $user->setReset(null);
                $this->addFlash('success', "Votre mot de passe à bien été modifié");
                $entityManager->persist($user);
                $entityManager->flush();
                return $this->redirectToRoute('login');

            }else{
                $this->addFlash('error', "Vos mot de passe ne corresponde pas, veuillez les saisirs de nouveau");
                return $this->render('security/resetPassword.html.twig',[
                    'id'=>$id
                ]);
            }
        }
        return $this->render('security/resetPassword.html.twig');

    }
}
