<?php

namespace ProfileBundle\Controller;

use MailerBundle\Service\MailerService;
use ProfileBundle\Entity\Confirmation;
use ProfileBundle\Entity\User;
use ProfileBundle\Repository\ConfirmationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ConfirmationController extends Controller
{

    /**
     * @param $token
     * @param $typeConfirmation
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function confirmAction($token, $typeConfirmation)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var $confirmationRepo ConfirmationRepository */
        $confirmationRepo = $em->getRepository(Confirmation::class);
        /** @var $confirmation Confirmation */
        $confirmation = $confirmationRepo->findOneBy(['token' => $token]);

        $response = null;

        switch ($typeConfirmation) {

            case Confirmation::CHANGE_EMAIL:
                $response = $this->checkConfirmation(
                    $confirmation,
                    'ProfileBundle:Account:changeEmail',
                    ['confirmation' => $confirmation],
                    'Change Email',
                    'This link has expired',
                    'This link was already used for to change email.'
                );
                break;

            case Confirmation::REGISTRATION:
                $url = $this->generateUrl('profile_resend_confirmation', ['token' => $token]);
                $response = $this->checkConfirmation(
                    $confirmation,
                    'ProfileBundle:Security:confirm',
                    ['confirmation' => $confirmation],
                    'Confirmation link expired',
                    'Your registration confirmation link has expired. Please click the below button to genereate a new one
                    <div class="row align-center modal-footer-custom">
                    <a class="red-btn add-button" href="'.$url.'">Generate link</a></div>',
                    'This link was already used for confirm registration.'
                );
                break;

            case Confirmation::RESET_PASSWORD:
                $response = $this->checkConfirmation(
                    $confirmation,
                    'ProfileBundle:Security:confirmResetPassword',
                    ['confirmation' => $confirmation],
                    'Reset Password',
                    'This link has expired. Please reset password again.',
                    'This link was already used for reset password.'
                );
                break;

            case Confirmation::BUYER_PROFILE_EMAIL:
                $response = $this->checkConfirmation($confirmation,
                    'ProfileBundle:Account:buyerConfirm',
                    ['confirmation' => $confirmation],
                    'Buyer Profile',
                    'This link has expired. Please fill buyer profile again.',
                    'This link was already used for creation buyer profile.'
                );
                break;

            case Confirmation::SELLER_PROFILE_EMAIL:
                $response = $this->checkConfirmation($confirmation,
                    'ProfileBundle:Account:sellerConfirm',
                    ['confirmation' => $confirmation],
                    'Seller Profile',
                    'This link has expired. Please fill seller profile again.',
                    'This link was already used for creation seller profile.'
                );
                break;

            case Confirmation::REGISTRATION_VENDOR:
                $response = $this->checkConfirmation(
                    $confirmation,
                    'ProfileBundle:Security:vendorConfirm',
                    ['confirmation' => $confirmation],
                    'Registration Vendor',
                    'This link has expired. Please register vendor again.',
                    'This link was already used for register vendor.'
                );
                break;
        }

        $em->flush();

        return $response;

    }

    /**
     * @param Confirmation|null $confirmation
     * @param $controller
     * @param $path
     * @param $title
     * @param $expiredConfirmationMessage
     * @param $alreadyUsedConfirmationMessage
     * @param bool $smallModal
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    private function checkConfirmation(
        Confirmation $confirmation = null,
        $controller,
        $path,
        $title,
        $expiredConfirmationMessage,
        $alreadyUsedConfirmationMessage,
        $smallModal = false
    ) {
        $profileAccountService = $this->get('ttt.profile.account');
        if ($confirmation) {
            if ($profileAccountService->isExpiredConfirmation($confirmation)) {
                $this->get('ttt.flash')->alert($title, $expiredConfirmationMessage, $smallModal);
            } else {
                return $this->forward($controller, $path);
            }
        } else {
            $this->get('ttt.flash')->alert($title, $alreadyUsedConfirmationMessage, $smallModal);
        }

        return $this->redirectToRoute('website_home');
    }

    public function resendConfirmationMessageAction($token)
    {
        /** @var User $user */
        $em = $this->get('doctrine.orm.entity_manager');
        $confirmation = $em->getRepository(Confirmation::class)->findOneBy(['token' => $token]);
        if ($confirmation) {
            $confirmation->setCreatedAt(new \DateTime());
            $em->persist($confirmation);
            $em->flush();
            $mailer = $this->get('ttt.mailer');
            $mailer->send($confirmation->getUser()->getEmail(), MailerService::TEMPLATE_USER_CONFIRMATION,
                [
                    'token' => $confirmation->getToken(),
                    'userName' => $confirmation->getUser()->getName()
                ]);

            $session = $this->get('session');
            $session->set('resendMessage', true);

            $this->get('ttt.flash')->info(
                'Confirmation link generated',
                'New registration confirmation link generated. Please look for an email from us on '
                .$confirmation->getUser()->getEmail(), false
            );
        }

        return $this->redirect($this->generateUrl('dashboard'));
    }

    /**
     * @param Confirmation $confirmation
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function buyerConfirmAction(
        Confirmation $confirmation
    ) {
        $accountService = $this->get('ttt.profile.account');
        $buyerProfile = $accountService->confirmBuyerProfile($confirmation);

        if (empty($buyerProfile)) {
            throw $this->createNotFoundException();
        }

        $this->get('ttt.flash')->info('E-mail confirmed for buying.');

        return $this->redirectToRoute('website_home');
    }

    /**
     * @param Confirmation $confirmation
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sellerConfirmAction(
        Confirmation $confirmation
    ) {
        $accountService = $this->get('ttt.profile.account');
        $sellerProfile = $accountService->confirmSellerProfile($confirmation);

        if (empty($sellerProfile)) {
            throw $this->createNotFoundException();
        }

        $this->get('ttt.flash')->info('E-mail confirmed for selling.');

        return $this->redirectToRoute('website_home');
    }
}