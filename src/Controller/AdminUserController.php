<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Entity\PasswordUpdate;
use App\Form\PasswordUpdateType;
use App\Repository\UserRepository;
use Symfony\Component\Form\FormError;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\PlaintextPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @Route("/admin/user")
 */
class AdminUserController extends AbstractController
{
    private $em;
    private $uRepo;

    public function __construct( EntityManagerInterface $em, UserRepository $uRepo){

        $this->em = $em;
        $this->uRepo = $uRepo;
    }

    /**
     * @Route("/", name="admin_user_index", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $this->uRepo->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="admin_user_new", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
    */
    public function new(Request $request, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            // PlaintextPasswordHasher ?? !!
            // add a default password : user
            $plainTextPassword = 'user';

            $hash = $hasher->hashPassword($user, $plainTextPassword);
            $user->setPassword($hash);

            $this->em->persist($user);
            $this->em->flush();

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="admin_user_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(Request $request, User $user): Response
    {
        //

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //

            //
            //
            $userRoles = $user->getUserRoles();

            foreach($userRoles as $userRole){
                $userRole->addUser($user);
                $this->em->persist($userRole);
            }

            $this->em->persist($user);
            $this->em->flush();
            
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_user_delete", methods={"DELETE", "POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->em->remove($user);
            $this->em->flush();
        }

        return $this->redirectToRoute('admin_user_index');
	}
	
	/**
	 * Mise à jour du mot de passe
	 * 
	 * @Route ("/updatepwd/{id}", name="user_updatepwd")
	 * @IsGranted("ROLE_USER")
     * 
	 * @return Response
	 */
	public function update_password(Request $request, User $user, UserPasswordHasherInterface $userPwdHash){

		$passwordUpdate = new PasswordUpdate();
		// $user = $this->getUser();


		$form = $this->createForm(PasswordUpdateType::class, $passwordUpdate );
		
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()){

			// 1- vérif de l'ancien mot de passe

			$isPwdValid = $userPwdHash->isPasswordValid($user, $passwordUpdate->getOldPassword());
			if ( ! $isPwdValid ){
			
				// gérer l'erreur
				$form->get('oldPassword')->addError(new FormError("Vous n'avez pas saisi correctement votre mot de passe actuel !"));

			}
			else {
				$newPassword = $passwordUpdate->getNewPassword();

                $hash = $userPwdHash->hashPassword($user, $newPassword); // $user->setPassword($hash)
                $user->setPassword($hash);

				$this->em->persist($user);
				$this->em->flush();
	
				$this->addFlash(
					"success",
					"Votre mot de passe a bien été modifié"
				);

				return $this->redirectToRoute('front', [
					// 'slug' => $user->getSlug(),
					// 'id' => $user->getId()
					]);
					
			}

		}

		return $this->render('admin/user/updatepwd.html.twig', [
			'form' => $form->createView()
		]);
	}


}
