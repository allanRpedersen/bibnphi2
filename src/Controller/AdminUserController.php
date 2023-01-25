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
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/admin/user")
 * @IsGranted("ROLE_ADMIN")
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
     */
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="admin_user_new", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
    */
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
     * @Route("/{id}", name="admin_user_show", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="admin_user_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

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
	 * @Route ("/user/updatepwd", name="user_updatepwd")
	 * 
	 * @return Response
	 */
	public function update_password(Request $request, UserPasswordHasherInterface $userPwdHash, PasswordHasherInterface $pwdHasher){

        //, UserPasswordEncoderInterface $encoder

		$passwordUpdate = new PasswordUpdate();
		$user = $this->getUser();

		
		$form = $this->createForm(PasswordUpdateType::class, $passwordUpdate );
		
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()){

			// 1- vérif de l'ancien mot de passe

			$isPwdValid = $userPwdHash->isPasswordValid($user, $passwordUpdate->getOldPassword());
            //$isPwdValid = $pwdHasher->verify($user->getPassword(), $passwordUpdate->getOldPassword());

			if ( ! $isPwdValid ){
			
				// if (!password_verify($passwordUpdate->getOldPassword(), $user->getPassword())){
				// return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);

				// gérer l'erreur
				$form->get('oldPassword')->addError(new FormError("Vous n'avez pas saisi correctement votre mot de passe actuel !"));

			}
			else {
				$newPassword = $passwordUpdate->getNewPassword();

				//$hash = $encoder->encodePassword($user, $newPassword);
                $hash = $userPwdHash->hashPassword($newPassword);
				//$user->setPassword($hash);

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
					
				dd($user);
			}

		}

		return $this->render('admin/user/updatepwd.html.twig', [
			'form' => $form->createView()
		]);
	}


}
