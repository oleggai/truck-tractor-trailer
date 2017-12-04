<?php

namespace NegotiationsBundle\Controller;

use ListingBundle\Entity\Listing;
use NegotiationsBundle\Entity\Offer;
use NegotiationsBundle\Form\OfferType;
use ProfileBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class OfferController extends Controller
{

    public function listingNegotiationsAction($listingId, $page)
    {
        $systemVariableService = $this->get('ttt.variable');
        $limit = $systemVariableService->get('listingNegotiationsPerPage') ?: 5;

        $listing = $this->getListingSecured($listingId);
        $offerRepository = $this->getDoctrine()->getRepository(Offer::class);
        $offers = $offerRepository->findAllListingOffers($listingId, $limit, $page);
        $maxPages = ceil(count($offers) / $limit);

        return $this->render('@Negotiations/Negotiations/show_listing_offers.html.twig', [
            'listing' => $listing,
            'items' => $offers,
            'routing' => 'listingNegotiations',
            'maxPages' => $maxPages,
            'thisPage' => $page,
            'backHref' => $this->generateUrl('selling_negotiations'),
        ]);
    }

    public function dashboardBuyingNegotiationsAction(UserInterface $user = null, $page)
    {
        /** @var User $user */
        $systemVariableService = $this->get('ttt.variable');
        $limit = $systemVariableService->get('allBuyingNegotiationsPerPage') ?: 5;

        $offerRepository = $this->getDoctrine()->getRepository(Offer::class);
        $bids = $offerRepository->findAllUserBids($user, $limit, $page);
        $maxPages = ceil(count($bids) / $limit);

        return $this->render('@Dashboard/negotiations.html.twig', [
            'items' => $bids,
            'type' => 'buying',
            'routing' => 'buying_negotiations',
            'maxPages' => $maxPages,
            'thisPage' => $page,
        ]);
    }

    public function dashboardSellingNegotiationsAction(UserInterface $user = null, $page)
    {
        /** @var User $user */
        $systemVariableService = $this->get('ttt.variable');
        $limit = $systemVariableService->get('allSellingNegotiationsPerPage') ?: 5;

        $offerRepository = $this->getDoctrine()->getRepository(Offer::class);
        $offers = $offerRepository->findAllUserOffers($user, $limit, $page);
        $maxPages = ceil(count($offers) / $limit);

        return $this->render('@Dashboard/negotiations.html.twig', [
            'items' => $offers,
            'type' => 'selling',
            'routing' => 'selling_negotiations',
            'maxPages' => $maxPages,
            'thisPage' => $page
        ]);
    }

    /**
     * @param UserInterface | User | null $user
     * @param $id
     * @return Response
     */
    public function showOfferAction(UserInterface $user = null, $id)
    {
        $offer = $this->getOfferSecured($id);
        $form = $this->createForm(OfferType::class, $offer);

        $offerService = $this->get('ttt.offer_service');
        if ($offerService->isSeller($offer, $user)) {
            return $this->redirectToRoute('listingNegotiations', ['listingId' => $offer->getListing()->getId()]);
        }

//        if ($request->isXmlHttpRequest()) {
//            $template = $this->renderView('@Negotiations/Negotiations/show_offer.html.twig', [
//                'form' => $form->createView(),
//                'offer' => $offer,
//                'listing' => $offer->getListing(),
//            ]);
//
//            return new JsonResponse($template);
//
//        } else {
        return $this->render('@Negotiations/Negotiations/show_offer.html.twig', [
            'form' => $form->createView(),
            'offer' => $offer,
        ]);
//        }
    }

    public function showOfferHistoryAction(Request $request, $id)
    {
        $offer = $this->getOfferSecured($id);

        if ($request->isXmlHttpRequest()) {
            $template = $this->renderView('@Negotiations/Negotiations/offer_history_modal.html.twig', [
                'offer' => $offer,
            ]);
            return new JsonResponse($template);
        } else {
            return $this->createNotFoundException('Nothing here');
        }
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param $listingId
     * @param $offerId
     * @return Response
     */
    public function makeOfferAction(Request $request, UserInterface $user = null, $listingId, $offerId = null)
    {
        /** @var Listing $listing */
        /** @var User $user */
        $listingRepository = $this->getDoctrine()->getRepository(Listing::class);
        $listing = $listingRepository->findOneBy(['id' => $listingId]);

        $existingOffer = null;
        if ($user) {
            if ($offerId) {
                $existingOffer = $this->getDoctrine()->getRepository(Offer::class)->findOneBy([
                    'id' => $offerId,
                ]);
            } else {
                $existingOffer = $this->getDoctrine()->getRepository(Offer::class)
                    ->findExistingOffer($listing, $user);
            }
        }

        if (!$existingOffer) {
            $offer = new Offer();
            $offer->setListing($listing);
            $offer->setUser($user);
        } else {
            $offer = $existingOffer;
        }

        $form = $this->createForm(OfferType::class, $offer);

        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                $errors = $this->get('app.form_errors')->getArray($form);
                $response = new JsonResponse($errors);

                return $response;
            } else {
                $template = $this->renderView('@Negotiations/make_offer.html.twig', [
                    'form' => $form->createView(),
                    'offer' => $offer,
                ]);

                return new JsonResponse($template);
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $offerService = $this->get('ttt.offer_service');

            if (!$offerService->canMakeOffer($offer, $user)) {
                $this->get('ttt.flash')->error(
                    'Offer not submitted!',
                    'You have already made an offer for this listing.'
                );

                return $this->redirect($request->headers->get('referer'));
            }

            if ($existingOffer) {
                $offerService->counterOffer($offer, $user);

                $to = $offer->getUser() === $user ? 'Seller' : 'Buyer';
                $counterOfferIsMade = $this->render('@Negotiations/Modal/counter_offer_has_been_sent.html.twig', [
                    'to' => $to
                ]);
                $this->get('ttt.flash')->info('', $counterOfferIsMade->getContent(), false);
            } else {
                $offerService->createOffer($offer, $user);

                $offerIsMade = $this->render('@Negotiations/Modal/offer_is_made_by_buyer.html.twig');
                $this->get('ttt.flash')->info('', $offerIsMade->getContent(), false);
            }
        }

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function notAvailableOfferAction(Request $request, $id)
    {
        $offerService = $this->container->get('ttt.offer_service');

        $offer = $this->getOfferSecured($id);

        $offerService->setNotAvailable($offer->getListing());

        $this->get('ttt.flash')->info(
            'Listing is not available anymore!',
            'Listing is not available anymore. It is not visible for buyers. All active offers was rejected.'
        );


        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param $id
     * @return Response
     */
    public function rejectOfferAction(Request $request, UserInterface $user = null, $id)
    {
        $offerService = $this->container->get('ttt.offer_service');

        /** @var User $user */
        $offer = $this->getOfferSecured($id);

        if ($offerService->canRejectOffer($offer, $user)) {
            $offerService->reject($offer, $user);

            $this->get('ttt.flash')->info('You rejects offer.');
        } else {
            $this->get('ttt.flash')->error('Offer can not be rejected.');
        }

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param int $id
     * @return Response
     */
    public function acceptOfferAction(Request $request, UserInterface $user = null, $id)
    {
        $offerService = $this->container->get('ttt.offer_service');

        /** @var User $user */
        $offer = $this->getOfferSecured($id);

        if ($offerService->canAcceptOffer($offer, $user)) {
            $offerAccepted = $this->render('@Negotiations/Modal/offer_accepted.html.twig', [
                'offer' => $offer
            ]);
            $this->get('ttt.flash')->info('', $offerAccepted->getContent(), false);

            $offerService->accept($offer, $user);
        } else {
            $this->get('ttt.flash')->error('Offer can not be accepted.');
        }

        return $this->redirect($request->headers->get('referer'));
    }

    private function getListingSecured($listingId)
    {
        $listingRepository = $this->getDoctrine()->getRepository(Listing::class);
        $listing = $listingRepository->getListingByIdAndUser($listingId, $this->getUser());

        if ($listing) {
            return $listing;
        }
        throw $this->createNotFoundException("Listing not found!");
    }

    /**
     * @param $id
     * @return Offer
     */
    private function getOfferSecured($id)
    {
        $offerRepository = $this->getDoctrine()->getRepository(Offer::class);
        $offer = $offerRepository->getOfferByIdAndUser($id, $this->getUser());

        if ($offer) {
            return $offer;
        }
        throw $this->createNotFoundException("Offer not found!");
    }
}
