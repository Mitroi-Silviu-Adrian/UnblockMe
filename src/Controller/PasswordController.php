<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class PasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private $resetPasswordHelper;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
    }

    /**
     * Display & process form to request a password reset.
     */
    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $mailer
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordEncoderInterface $passwordEncoder, string $token = null): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                'There was a problem validating your reset request - %s',
                $e->getReason()
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode the plain password, and set it.
            $encodedPassword = $passwordEncoder->encodePassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $this->getDoctrine()->getManager()->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer): RedirectResponse
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'app_forgot_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            // $this->addFlash('reset_password_error', sprintf(
            //     'There was a problem handling your password reset request - %s',
            //     $e->getReason()
            // ));

            return $this->redirectToRoute('app_check_email');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('UnBlockMe@app.com', 'Reset Password'))
            ->to($user->getEmail())
            ->subject('Your password reset request')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        $mailer->send($email);

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }

    private function checkPasswordContent(string $password): bool
    {
        $firstDigit = '0';
        $lastDigit = '9';
        $digitFound = false;

        for($digit = $firstDigit; $digit<= $lastDigit; $digit++)
            if (strchr($password, $digit) != null) {
                $digitFound = true;
                break;
            }

        $firstLWletter = 'a';
        $lastLWletter = 'z';

        $lwLetter = false;
        for($letter = $firstLWletter; $letter<= $lastLWletter; $letter++)
            if(strchr($password,$letter))
            {
                $lwLetter = true;
                break;
            }

        $firstUPletter = 'A';
        $lastUPletter = 'Z';

        $UPLetter = false;
        for($letter = $firstUPletter; $letter<= $lastUPletter; $letter++)
            if(strchr($password,$letter))
            {
                $UPLetter = true;
                break;
            }

        return $digitFound && $UPLetter && $lwLetter;
    }

    #[Route('/changePassword', name: 'changePassword',  methods: ['GET', 'POST'])]
    public function changePassword(Request $request,
                                   UserPasswordHasherInterface $passwordHasher,
                                   MailerController $mail,
                                   MailerInterface $mailer): Response
    {
        if($this->getUser() == null)
            return $this->redirectToRoute("app_login");

        $Password = null;

        $passwordForm = $this->createFormBuilder($Password)
            ->add('OldPassword', PasswordType::class,[
                'invalid_message' => 'Wrong Password',
                'mapped' => false,
                'attr' => [
                    'style' =>
                        '
                            width: 300px;
                        ',
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'help' => 'The password must have at least 8 characters in length,at least a lowercase and uppercase letter, and a digit',
                'first_options' => [
                    'mapped' => false,
                    'attr' => [
                        'style' =>
                            '
                            width: 300px;
                            '
                        ],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter a password',
                        ]),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Your password should be at least {{ limit }} characters',
                            // max length allowed by Symfony for security reasons
                            'max' => 4096,
                        ]),
                    ],
                    'label' => 'New password',
                ],
                'second_options' => [
                    'mapped' => false,
                    'attr' => [
                        'style' =>
                            '
                            width: 300px;
                            '
                        ],
                    'label' => 'Repeat Password',
                ],
                'invalid_message' => 'The password fields must match.',
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
            ])
            ->add('Save',SubmitType::class)
            ->getForm();


        $passwordForm->handleRequest($request);

        if($passwordForm->isSubmitted() && $passwordForm->isValid())
        {
            $oldPassword = $passwordForm->get('OldPassword')->getData();
            $newPassword = $passwordForm->get('plainPassword')->get('first')->getData();
            $newPasswordAgain = $passwordForm->get('plainPassword')->get('second')->getData();

            $user = $this->getUser();

            if($passwordHasher->isPasswordValid($user,$oldPassword) == true &&
                count_chars($newPassword)>=8 &&
                count_chars($newPasswordAgain)>=8 &&
                $newPassword != $oldPassword &&
                $newPassword == $newPasswordAgain &&
                $this->checkPasswordContent($newPassword)
            )
            {
                $user->setPassword($passwordHasher->hashPassword($user,$newPassword));
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('notice','Password changed!');

                return $mail->sendEmail($mailer,$user->getEmail(),$newPassword);
            }
            else
                $this->addFlash("notice", "Either the old password is incorrect, or the format of the new password isn't respected");

            return $this->render('user/newPassword.html.twig',[
                'form'=>$passwordForm->createView(),
                'password' =>$Password,
            ]);
        }

        return $this->render('user/newPassword.html.twig',[
            'form'=>$passwordForm->createView(),
            'password' =>$Password,
        ]);
    }
}
