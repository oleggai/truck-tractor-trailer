<?php

namespace ProfileBundle\Controller;

use LocationBundle\Entity\CompanyAddress;
use MailerBundle\Service\MailerService;
use Payment\PaymentBundle\Entity\Payment;
use Payment\PaymentMethodBundle\Entity\PaymentMethod;
use ProfileBundle\Entity\Company;
use ProfileBundle\Entity\Confirmation;
use ProfileBundle\Entity\User;
use ProfileBundle\Form\ChangePasswordType;
use ProfileBundle\Form\CompanyType;
use ProfileBundle\Form\PreferencesType;
use ProfileBundle\Form\UserAvatarType;
use ProfileBundle\Form\UserPersonalInformationType;
use ProfileBundle\Repository\ConfirmationRepository;
use ProfileBundle\Service\SignupService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class AccountController extends Controller
{
    /**
     * @return Response
     */
    public function acquaintanceAction()
    {

        return $this->render('@Profile/Account/acquaintance.html.twig');
    }

    /**
     * @param Request $request
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function avatarAction(
        Request $request,
        UserInterface $user = null
    ) {

        $formChangeAvatar = $this->createForm(UserAvatarType::class, $user, [
            'action' => $this->generateUrl('change_avatar'),
            'validation_groups' => [
                'editAvatar',
                'Default',
            ],
        ]);

        $formChangeAvatar->handleRequest($request);

        if ($request->isXmlHttpRequest() && $request->get('action')) {
            $options = [];

            if ($request->get('action') == 'edit') {
                $options['formChangeAvatar'] = $formChangeAvatar->createView();
            }

            $template = $this->render('ProfileBundle:includes:profile_user_avatar.html.twig', $options);

            return new JsonResponse([
                'htmlBlock' => $template->getContent()
            ]);
        }

        $confirmClicked = $formChangeAvatar->get('submit')->isClicked();

        if ($formChangeAvatar->isSubmitted() && $formChangeAvatar->isValid() && $confirmClicked) {

            $accountService = $this->get('ttt.profile.account');

            $file = $formChangeAvatar->get('avatarFile')->getData();
            $accountService->saveAvatarProfile($user, $file);

            $this->get('ttt.flash')->info('Personal Profile', 'Avatar updated successfully', true);
            return $this->redirectToRoute('account_information');
        }

        if($formChangeAvatar->isSubmitted() && !$confirmClicked) {
            $errors = [];
            foreach ($formChangeAvatar->getErrors(true) as $key => $error) {
                $errorMessage = $error->getMessage() == 'This file is not a valid image.' ?
                    'Incorrect format of the attached image' : $error->getMessage();
                $errors[$formChangeAvatar->getName()] = $errorMessage;
            }

            return new JsonResponse($errors);
        }

        return $this->render('@Profile/Account/index.html.twig', [
            'file' => '@Profile/Account/ajax/pages/personal_profile.html.twig',
            'user' => $user,
            'formChangeAvatar' => $formChangeAvatar->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function accountInformationAction(
        Request $request
    ) {
        if ($request->isXmlHttpRequest()) {
            $renderedTemplate = $this->render('@Profile/Account/ajax/pages/personal_profile.html.twig');

            return new JsonResponse([
                'htmlBlock' => $renderedTemplate->getContent()
            ]);
        }

        return $this->render('@Profile/Account/index.html.twig', [
            'file' => '@Profile/Account/ajax/pages/personal_profile.html.twig',
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function personalInformationAction(
        Request $request,
        UserInterface $user = null
    ) {
        $em = $this->container->get('doctrine')->getManager();

        $formPersonalInformation = $this->createForm(UserPersonalInformationType::class, $user, [
            'action' => $this->generateUrl('personal_information')
        ]);
        $formPersonalInformation->handleRequest($request);

        if ($request->isXmlHttpRequest() && $request->get('action')) {
            $options = [];

            if ($request->get('action') == 'edit') {
                $options['formPersonalInformation'] = $formPersonalInformation->createView();
            }

            $template = $this->render('@Profile/Account/ajax/blocks/profile_personal_information.html.twig', $options);

            return new JsonResponse([
                'htmlBlock' => $template->getContent()
            ]);
        }

        if ($formPersonalInformation->isSubmitted()) {

            if ($request->isXmlHttpRequest()) {
                $response = '';

                if (!$formPersonalInformation->isValid()) {
                    $response = $this->get('app.form_errors')->getArray($formPersonalInformation);
                }

                return new JsonResponse($response);
            } else {

                if ($formPersonalInformation->isValid()) {
                    $user = $formPersonalInformation->getData();
                    $em->persist($user);
                    $em->flush();

                    return $this->redirectToRoute('account_information');
                }
            }
        }

        return $this->render('@Profile/Account/index.html.twig', [
            'file' => '@Profile/Account/ajax//pages/personal_profile.html.twig',
            'user' => $user,
            'formPersonalInformation' => $formPersonalInformation->createView()
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @return Response
     */
    public function passwordAction(
        Request $request,
        UserInterface $user = null
    ) {
        $formChangePassword = $this->createForm(ChangePasswordType::class, null, [
            'action' => $this->generateUrl('password')
        ]);
        $formChangePassword->handleRequest($request);

        if ($request->isXmlHttpRequest() && $request->get('action')) {
            $options = [];

            if ($request->get('action') == 'edit') {
                $options['formChangePassword'] = $formChangePassword->createView();
            }

            $template = $this->render('@Profile/Account/ajax/blocks/change_password.html.twig', $options);

            return new JsonResponse([
                'htmlBlock' => $template->getContent()
            ]);
        }

        if ($formChangePassword->isSubmitted()) {

            if ($request->isXmlHttpRequest()) {
                $response = '';

                if (!$formChangePassword->isValid()) {
                    $response = $this->get('app.form_errors')->getArray($formChangePassword);
                }

                return new JsonResponse($response);
            } else {

                if ($formChangePassword->isValid()) {
                    $data = $formChangePassword->getData();

                    $accountService = $this->get('ttt.profile.account');

                    /** @var User $user */
                    $errors = $accountService->changePassword($data, $user);

                    if (count($errors) > 0) {
                        foreach ($errors as $errorMessage) {
                            $formChangePassword->get('current_password')->addError(new FormError($errorMessage));
                        }
                    } else {
                        return $this->redirectToRoute('account_information');
                    }
                }
            }
        }

        return $this->render('@Profile/Account/index.html.twig', [
            'file' => '@Profile/Account/ajax//pages/personal_profile.html.twig',
            'user' => $user,
            'formChangePassword' => $formChangePassword->createView()
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface|null $user
     * @param $formAction
     * @return Response
     */
    public function companyAction(
        Request $request,
        UserInterface $user = null,
        $formAction
    ) {
        $em = $this->container->get('doctrine')->getManager();
        $parameters = [];

        /** @var User $user */
        $company = $user->getCompany();

        if (!$company) {
            $company = new Company();
        }

        switch ($formAction) {
            case 'edit':
                $formService = $this->get('ttt.form_service');

                $address = $company->getId() ? $company->getAddress() : new CompanyAddress();
                $formCompanyInformation = $this->createForm(CompanyType::class, $company, [
                    'withButtons' => true,
                    'action' => $this->generateUrl('company_information', ['formAction' => 'edit'])
                ]);
                $formService->setLocation($address, $formCompanyInformation->get('companyLocation'));
                $formCompanyInformation->handleRequest($request);

                if ($request->isXmlHttpRequest() && $request->get('action')) {
                    $options = [];

                    if ($request->get('action') == 'edit') {
                        $options['companyForm'] = $formCompanyInformation->createView();
                    }

                    $template = $this->render('@Profile/Account/ajax/blocks/profile_company.html.twig', $options);

                    return new JsonResponse([
                        'htmlBlock' => $template->getContent()
                    ]);
                }

                if ($formCompanyInformation->isSubmitted()) {

                    if ($request->isXmlHttpRequest()) {
                        $response = '';

                        if (!$formCompanyInformation->isValid()) {
                            $response = $this->get('app.form_errors')->getArray($formCompanyInformation);
                        }

                        return new JsonResponse($response);
                    } else {

                        if ($formCompanyInformation->isValid()) {
                            $cityStateService = $this->container->get('ttt.city_state_service');
                            $company = $formCompanyInformation->getData();
                            $address = $company->getId() ? $company->getAddress() : new CompanyAddress();
                            $companyLocation = json_decode($formCompanyInformation->get('companyLocation')->getData());
                            $companyAddress = $cityStateService->createAddress($address, $companyLocation);

                            $company->setAddress($companyAddress);
                            $em->persist($company);

                            $user->setCompany($company);
                            $em->persist($user);
                            $em->flush();

                            return $this->redirectToRoute('company_information');
                        }
                    }
                }

                if (!$formCompanyInformation->isSubmitted() || !$formCompanyInformation->isValid()) {
                    $parameters['companyForm'] = $formCompanyInformation->createView();
                }

                break;
            case 'remove':
                $user->setCompany(null);
                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute('company_information');
        }

        if ($request->isXmlHttpRequest()) {
            $renderedTemplate = $this->render('@Profile/Account/ajax/pages/company_profile.html.twig', $parameters);

            return new JsonResponse([
                'htmlBlock' => $renderedTemplate->getContent()
            ]);
        }

        return $this->render('@Profile/Account/index.html.twig', [
            'file' => '@Profile/Account/ajax//pages/company_profile.html.twig',
            $parameters,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function quickRegistrationAction(Request $request)
    {
        $userEmail = $request->get('userEmail');

        $signUpService = $this->get('ttt.profile.signup');
        $mailerService = $this->get('ttt.mailer');
        $session = $this->get('session');
        $session->set('quickRegistration', '1');

        $result = $signUpService->quickRegistration($userEmail, $request);

        if (!empty($result['errors'])) {
            return new JsonResponse(['errors' => $result['errors']]);
        }

        /** @var $user User */
        $user = $result['user'];
        /** @var $confirmation Confirmation */
        $confirmationRegistration = $signUpService->createConfirmation($user, Confirmation::REGISTRATION);

        $mailerService->send($user->getEmail(), MailerService::TEMPLATE_USER_CONFIRMATION,
            [
                'token' => $confirmationRegistration->getToken(),
                'user' => $user
            ]);

        $this->get('ttt.flash')->info(SignupService::QUICK_REGISTRATION_MESSAGE);

        return new JsonResponse(['success' => '']);
    }

    /**
     * @param Request $request
     * @param UserInterface|null $user
     * @return Response
     */
    public function transactionHistoryAction(Request $request, UserInterface $user = null)
    {
        /** @var User $user */
        $payments = $this->getDoctrine()->getRepository(Payment::class)->findAllUserPayments($user);

        if ($request->isXmlHttpRequest()) {
            $renderedTemplate = $this->render('@Profile/Account/ajax/pages/transaction_history.html.twig', [
                'payments' => $payments,
            ]);

            return new JsonResponse([
                'htmlBlock' => $renderedTemplate->getContent()
            ]);
        }

        return $this->render('@Profile/Account/index.html.twig', [
            'file' => '@Profile/Account/ajax//pages/transaction_history.html.twig',
            'payments' => $payments,
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface|null $user
     * @return JsonResponse|Response
     */
    public function paymentMethodsAction(Request $request, UserInterface $user = null)
    {
        /** @var User $user */
        $paymentMethods = $this->getDoctrine()->getRepository(PaymentMethod::class)->findAllUserPaymentMethods($user);

        if ($request->isXmlHttpRequest()) {
            $renderedTemplate = $this->render('@Profile/Account/ajax/pages/payment_methods.html.twig', [
                'paymentMethods' => $paymentMethods,
            ]);

            return new JsonResponse([
                'htmlBlock' => $renderedTemplate->getContent()
            ]);
        }

        return $this->render('@Profile/Account/index.html.twig', [
            'file' => '@Profile/Account/ajax//pages/payment_methods.html.twig',
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * @param Confirmation $confirmation
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function changeEmailAction(
        Confirmation $confirmation
    ) {

        $em = $this->getDoctrine()->getManager();
        $user = $confirmation->getUser();

        /** @var $confirmationRepo ConfirmationRepository */
        $confirmationRepo = $em->getRepository(Confirmation::class);

        $accountService = $this->get('ttt.profile.account');

        if ($accountService->isExpiredConfirmation($confirmation)) {

            $user->setNewEmail('');
            $em->flush();
            $confirmationRepo->deleteUserConfirmations($confirmation->getType(), $user);

            throw $this->createNotFoundException();
        }

        $user->setEmail($user->getNewEmail());
        $user->setNewEmail('');

        $confirmationRepo->deleteUserConfirmations($confirmation->getType(), $user);

        $em->flush();

        $this->get('ttt.flash')->info('Email successfully changed');

        return $this->redirectToRoute('website_home');
    }
}
