<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password_request')]
    public function request(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                // Generate a secure, random token
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
                $entityManager->flush();

                // Create the password reset URL
                $url = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                // Send the email (this requires Mailer configuration)
                $email = (new Email())
                    ->from('no-reply@eventplanner.com')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html("<p>Pour réinitialiser votre mot de passe, veuillez visiter le lien suivant :</p><a href='$url'>$url</a>");
                
                try {
                    $mailer->send($email);
                    $this->addFlash('success', 'Un email vous a été envoyé pour réinitialiser votre mot de passe.');
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Impossible d\'envoyer l\'email. Veuillez contacter un administrateur.');
                }
            } else {
                $this->addFlash('warning', 'Aucun compte n\'est associé à cet email.');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/request.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(string $token, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('danger', 'Le jeton de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            
            // Invalidate the token
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', ['token' => $token]);
    }
}