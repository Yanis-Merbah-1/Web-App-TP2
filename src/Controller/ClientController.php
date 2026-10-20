<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Repository\ClientRepository;
use App\Entity\Client;

final class ClientController extends AbstractController
{
    
    
    #[Route('/client', name: 'app_client')]
    public function index(ClientRepository $repo): Response
    {
        return $this->render('client/index.html.twig', [
            'clients' => $repo->findAll(),
        ]);
    }

    #[Route('/client/{id}', name: 'app_client_show')]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }
}
