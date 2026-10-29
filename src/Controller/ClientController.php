<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ClientController extends AbstractController
{
    #[Route('/client', name: 'app_client')]
    public function index(ClientRepository $repo): Response
    {
        return $this->render('client/index.html.twig', [
            'clients' => $repo->findAll(),
        ]);
    }

    #[Route('/client/new', name: 'client_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($client);
            $em->flush();
            return $this->redirectToRoute('app_client');
        }

        return $this->render('client/form.html.twig', [
            'form' => $form,
            'titre' => 'Nouveau client',
        ]);
    }

    #[Route('/client/{id}', name: 'app_client_show', requirements: ['id' => '\d+'])]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/client/{id}/edit', name: 'client_edit', requirements: ['id' => '\d+'])]
    public function edit(Client $client, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_client');
        }

        return $this->render('client/form.html.twig', [
            'form' => $form,
            'titre' => 'Modifier le client',
        ]);
    }

        #[Route('/client/{id}/delete', name: 'client_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Client $client, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $client->getId(), $request->request->get('_token'))) {

            if (count($client->getCommandes()) > 0) {
                $this->addFlash('error', 'Ce client a des commandes, suppression impossible.');
                return $this->redirectToRoute('app_client');
            }

            $em->remove($client);
            $em->flush();
            $this->addFlash('success', 'Client supprimé.');
        }

        return $this->redirectToRoute('app_client');
    
    }
}