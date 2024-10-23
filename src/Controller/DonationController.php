<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Cause;
use App\Form\DonationType;
use Stripe\Checkout\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DonationController extends AbstractController
{
    #[Route('/donation', name: 'app.donation')]
    public function donate(Request $request): Response
    {
        $form = $this->createForm(DonationType::class);
        $form->handleRequest($request);

        $cause = null;
        $price = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $cause = $form->get('cause')->getData();
            $price = $cause->getPrice() * 100;

            Stripe::setApiKey($this->getParameter('stripe_secret_key'));
            try {
                $session = Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => [
                                'name' => $cause->getName(),
                            ],
                            'unit_amount' => $price,
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'success_url' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'cancel_url' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]);

                return $this->redirect($session->url, 303);
            } catch (\Exception $e) {
                $this->addFlash('error', 'There was an error with the payment: ' . $e->getMessage());
            }
        }

        return $this->render('Frontend/Donation/index.html.twig', [
            'form' => $form,
            'cause' => $cause,
            'price' => $price ? $price / 100 : null,
        ]);
    }

    #[Route('/donation/success', name: 'payment_success')]
    public function success(): Response
    {
        $this->addFlash('success', 'Don effectué avec succès.');
        return $this->render('Frontend/Donation/success.html.twig');
    }

    #[Route('/donation/cancel', name: 'payment_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('error', 'Une erreur est survenu, veuillez réessayer.');
        return $this->render('Frontend/Donation/cancel.html.twig');
    }

    #[Route('/cause/{id}/price', name: 'cause_price', methods: ['GET'])]
    public function getPrice(Cause $cause): JsonResponse
    {
        return new JsonResponse(['price' => $cause->getPrice()]);
    }
}
