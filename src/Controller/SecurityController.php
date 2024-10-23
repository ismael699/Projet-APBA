<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Service\JWTService;
use App\Form\ChangePasswordType;
use App\Form\ForgotPasswordType;
use App\Service\SendEmailService;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    #[Route('/register', name: 'app.register', methods: ['GET', 'POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User;

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Votre compte a bien été créé. Vous pouvez vous connecter maintenant.');
            return $this->redirectToRoute('app.login');
        }

        return $this->render('Frontend/User/Register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/login', name: 'app.login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $lastEmail = $authenticationUtils->getLastUsername();
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($error) {
            $this->addFlash('error', 'Email ou mot de passe incorrect.');
        }

        return $this->render('Frontend/User/login.html.twig', [
            'last_email' => $lastEmail,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app.logout')]
    public function logout(): void
    {}

    #[Route('/forgot-password', name: 'app.forgot.password')]
    public function forgottenPasswod(Request $request, UserRepository $userRepo, JWTService $jwt, SendEmailService $mail): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère l'utilisateur par son email
            $user = $userRepo->findOneByEmail($form->get('email')->getData());

            // Si l'utilisateur existe
            if ($user) {
                // On génère un token
                $header = [
                    'typ' => 'JWT',
                    'alg' => 'HS256',
                ];

                $payload = [
                    'user_id' => $user->getId(),
                ];

                // On génère le token
                $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

                // On génère l'URL vers app.reset.password
                $url = $this->generateUrl('app.reset.password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                $mail->send(
                    'no-reply@APBA.fr',
                    $user->getEmail(),
                    'Récupération de mot de passe',
                    'email',
                    compact('user', 'url') // ['user' => $user, 'url'=>$url]
                );

                $this->addFlash('success', 'Email envoyé avec succès.');
                return $this->redirectToRoute('app.login');
            }

            // S'il n'existe pas
            $this->addFlash('error', 'Un problème est survenu.');
            return $this->redirectToRoute('app.login');
        }

        return $this->render('Security/Password/forgot_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app.reset.password')]
    public function resetPassword(JWTService $jwt, Request $request, UserRepository $userRepo, $token, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        // On vérifie si le token est valide (cohérent, pas expiré et signature correcte)
        if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))){
            $payload = $jwt->getPayload($token); // On récupère les données (payload)
            $user = $userRepo->find($payload['user_id']); // On récupère le user

            if($user){
                $form = $this->createForm(ChangePasswordType::class);
                $form->handleRequest($request);

                if($form->isSubmitted() && $form->isValid()){
                    // $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()));
                    $plainPassword = $form->get('plainPassword')->getData();
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                    $em->flush();

                    $this->addFlash('success', 'Mot de passe changé avec succès');
                    return $this->redirectToRoute('app.login');
                }
                return $this->render('Security/Password/reset.html.twig', [
                    'newPassword' => $form,
                ]);
            }
        }

        $this->addFlash('danger', 'Un problème est survenu, veuillez recommencer.');
        return $this->redirectToRoute('app.login');
    }
}
