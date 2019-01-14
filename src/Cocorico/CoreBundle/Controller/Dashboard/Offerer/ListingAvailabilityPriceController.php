<?php

/*
 * This file is part of the Cocorico package.
 *
 * (c) Cocolabs SAS <contact@cocolabs.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cocorico\CoreBundle\Controller\Dashboard\Offerer;

use Cocorico\CoreBundle\Document\ListingAvailability;
use Cocorico\CoreBundle\Entity\Listing;
use Cocorico\CoreBundle\Model\Manager\ListingAvailabilityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Listing Dashboard controller.
 *
 * @Route("/listing")
 */
class ListingAvailabilityPriceController extends Controller
{

    /**
     * Edits listing availabilities price.
     *
     * @Route("/{listing_id}/edit_availabilities_prices",
     *      name="cocorico_dashboard_listing_edit_availabilities_prices",
     *      requirements={"listing_id" = "\d+"}
     * )
     * @Security("is_granted('edit', listing)")
     * @ParamConverter("listing", class="CocoricoCoreBundle:Listing", options={"id" = "listing_id"})
     *
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @param         $listing
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAvailabilitiesPricesAction(Request $request, Listing $listing)
    {
        $selfUrl = $this->generateUrl(
            'cocorico_dashboard_listing_edit_availabilities_prices',
            array('listing_id' => $listing->getId())
        );

        $listingAvailabilityHandler = $this->get('cocorico.form.handler.listing_availability.price.dashboard');
        $form = $this->createEditAvailabilitiesPricesForm($listing);
        $success = $listingAvailabilityHandler->processMany($form);

        if ($success == 1) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('listing.edit.success', array(), 'cocorico_listing')
            );

            return $this->redirect($selfUrl);
        } elseif ($success == -1) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('form.error', array(), 'cocorico')
            );
        }

        return $this->render(
            'CocoricoCoreBundle:Dashboard/Listing:edit_availabilities_prices.html.twig',
            array(
                'listing' => $listing,
                'form_prices' => $form->createView()
            )
        );

    }

    /**
     * Creates a form to edit many prices Documents.
     *
     * @param Listing $listing The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditAvailabilitiesPricesForm(Listing $listing)
    {
        $form = $this->get('form.factory')->createNamed(
            'listing_availabilities_prices',
            'listing_edit_availabilities_prices',
            $listing,
            array(
                'action' => $this->generateUrl(
                    'cocorico_dashboard_listing_edit_availabilities_prices',
                    array('listing_id' => $listing->getId())
                ),
                'method' => 'POST',
            )
        );

        return $form;
    }


    /**
     * Update a ListingAvailability Document.
     *
     * @Route("/{listing_id}/{day}/{start_time}/{end_time}/edit_availability_price",
     *      name="cocorico_dashboard_listing_edit_availability_price",
     *      requirements={
     *          "listing_id" = "\d+",
     *          "day"= "^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$",
     *          "start_time" = "^([01]?[0-9]|2[0-3]):[0-5][0-9]$",
     *          "end_time" = "^([01]?[0-9]|2[0-3]):[0-5][0-9]$"
     *      },
     * )
     * @Security("is_granted('edit', listing)")
     * @ParamConverter("listing", class="CocoricoCoreBundle:Listing", options={"id" = "listing_id"})
     *
     * @Method({"GET", "POST"})
     *
     * @param  Request $request
     * @param  Listing $listing
     * @param  string  $day
     * @param  string  $start_time
     * @param  string  $end_time
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAvailabilityPriceAction(
        Request $request,
        Listing $listing,
        $day,
        $start_time,
        $end_time
    ) {
        $listingAvailabilityHandler = $this->get('cocorico.form.handler.listing_availability.price.dashboard');
        $form = $this->createEditAvailabilityPriceForm($listing->getId(), $day, $start_time, $end_time);
        $success = $listingAvailabilityHandler->processOne($form, $listing, $day, $start_time, $end_time);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get("cocorico.listing_availability.manager")->saveAvailability(
                $form->getData()
            );

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('listing.availability.edit.success', array(), 'cocorico_listing')
            );
        } elseif ($success == -1) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('form.error', array(), 'cocorico')
            );
        }

        return $this->render(
            'CocoricoCoreBundle:Dashboard/Listing:form_availability_price.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }


    /**
     * @param int     $listingId
     * @param string  $day
     * @param  string $startTime
     * @param  string $endTime
     *
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    private function createEditAvailabilityPriceForm($listingId, $day, $startTime, $endTime)
    {
        $form = $this->get('form.factory')->createNamed(
            'listing_availability',
            'listing_edit_availability_price',
            $availability,
            array(
                'method' => 'POST',
                'action' => $this->generateUrl(
                    'cocorico_dashboard_listing_edit_availability_price',
                    array(
                        'listing_id' => $listingId,
                        'day' => $day,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    )
                ),
            )
        );

        return $form;
    }

    /**
     * New ListingAvailability Document.
     *
     * @Route("/{listing_id}/{day}/new_availability_price",
     *      name="cocorico_dashboard_listing_new_availability_price",
     *      requirements={
     *          "listing_id" = "\d+",
     *          "day"= "^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$"
     *      },
     * )
     * @Security("is_granted('edit', listing)")
     * @ParamConverter("listing", class="CocoricoCoreBundle:Listing", options={"id" = "listing_id"})
     *
     * @Method({"GET", "POST"})
     *
     * @param  Request $request
     * @param  Listing $listing
     * @param  string  $day format yyyy-mm-dd
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function editAction(
        Request $request,
        Listing $listing,
        ListingAvailability $listing_availability,
        $start_time,
        $end_time
    ) {
        $listingAvailabilityManager = $this->get("cocorico.listing_availability.manager");

        if ($listing->getId() != $listing_availability->getListingId()) {
            throw new AccessDeniedHttpException("Edition impossible");
        }

        //No status edition if already booked
        if ($listingAvailabilityManager->getTimeUnitIsDay() &&
            $listing_availability->getStatus() == ListingAvailability::STATUS_BOOKED
        ) {
            throw $this->createNotFoundException('Edition impossible');
        }

        $form = $this->createEditForm($listing_availability, $start_time, $end_time);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ListingAvailability $listing_availability */
            $listing_availability = $form->getData();

            //convert object to array
            $listingAvailability = $listingAvailabilityManager->listingAvailabilityToArray($listing_availability);

            $listingAvailabilityManager->saveAvailabilityTimes(
                $listingAvailability,
                $start_time,
                $end_time,
                "price"
            );

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('listing.availability.new.success', array(), 'cocorico_listing')
            );
        }

        return $this->render(
            'CocoricoCoreBundle:Dashboard/Listing:form_availability_price.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @param ListingAvailability $availability
     *
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    private function createCreateForm(ListingAvailability $availability)
    {
        $form = $this->get('form.factory')->createNamed(
            'listing_availability',
            'listing_edit_availability_price',
            null,
            array(
                'method' => 'POST',
                'action' => $this->generateUrl(
                    'cocorico_dashboard_listing_new_availability_price',
                    array(
                        'listing_id' => $availability->getListingId(),
                        'day' => $availability->getDay()->format('Y-m-d')
                    )
                ),
            )
        );

        return $form;
    }

}
