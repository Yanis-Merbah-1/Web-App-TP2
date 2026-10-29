<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommandeController extends AbstractController
{
    #[Route('/commande', name: 'app_commande')]
    public function index(CommandeRepository $repo): Response
    {
        return $this->render('commande/index.html.twig', [
            'commandes' => $repo->findBy([], ['date' => 'DESC']),
        ]);
    }

    #[Route('/commande/new', name: 'commande_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $commande = new Commande();
        $commande->setDate(new \DateTimeImmutable());
        $commande->setStatut('En cours');
        
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $stockError = false;
            foreach ($commande->getLigneCommandes() as $ligne) {
                $produit = $ligne->getProduit();
                if ($produit && $ligne->getQuantite() > $produit->getStock()) {
                    $this->addFlash('error', 'Stock insuffisant pour le produit ' . $produit->getNom() . ' ! Il ne reste que ' . $produit->getStock() . ' unités.');
                    $stockError = true;
                }
            }

            if ($stockError) {
                return $this->render('commande/form.html.twig', [
                    'form' => $form,
                    'titre' => 'Nouvelle commande',
                ]);
            }

            foreach ($commande->getLigneCommandes() as $ligne) {
                $produit = $ligne->getProduit();
                
                $montant = $ligne->getQuantite() * (float) $produit->getPrix();
                $ligne->setPrixUnitaire($produit->getPrix());
                $ligne->setMontant(number_format($montant, 2, '.', ''));
                
                $nouveauStock = $produit->getStock() - $ligne->getQuantite();
                $produit->setStock($nouveauStock);
                
                $em->persist($ligne);
            }
            
            $commande->recalculerTotal();
            $em->persist($commande);
            $em->flush();
            
            $this->addFlash('success', 'Commande créée avec succès.');
            return $this->redirectToRoute('app_commande');
        }

        return $this->render('commande/form.html.twig', [
            'form' => $form,
            'titre' => 'Nouvelle commande',
        ]);
    }

    #[Route('/commande/{id}', name: 'app_commande_show', requirements: ['id' => '\d+'])]
    public function show(Commande $commande): Response
    {
        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/commande/{id}/edit', name: 'commande_edit', requirements: ['id' => '\d+'])]
    public function edit(Commande $commande, Request $request, EntityManagerInterface $em): Response
    {
        $anciennesLignes = [];
        foreach ($commande->getLigneCommandes() as $ligne) {
            if ($ligne->getProduit()) {
                $anciennesLignes[] = [
                    'produit' => $ligne->getProduit(),
                    'quantite' => $ligne->getQuantite()
                ];
            }
        }

        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            foreach ($commande->getLigneCommandes() as $ligne) {
                $produit = $ligne->getProduit();
                if (!$produit) continue;

                $ancienneQuantite = 0;
                foreach ($anciennesLignes as $ancienne) {
                    if ($ancienne['produit']->getId() === $produit->getId()) {
                        $ancienneQuantite = $ancienne['quantite'];
                    }
                }

                $stockDisponible = $produit->getStock() + $ancienneQuantite;

                if ($ligne->getQuantite() > $stockDisponible) {
                    $this->addFlash('error', 'Stock insuffisant pour ' . $produit->getNom() . ' ! Il ne reste que ' . $stockDisponible . ' unités disponibles.');
                    return $this->render('commande/form.html.twig', [
                        'form' => $form,
                        'titre' => 'Modifier la commande',
                    ]);
                }
            }

            foreach ($anciennesLignes as $ancienne) {
                $produit = $ancienne['produit'];
                $produit->setStock($produit->getStock() + $ancienne['quantite']);
            }

            foreach ($commande->getLigneCommandes() as $ligne) {
                $produit = $ligne->getProduit();
                if (!$produit) continue;

                $montant = $ligne->getQuantite() * (float) $produit->getPrix();
                $ligne->setPrixUnitaire($produit->getPrix());
                $ligne->setMontant(number_format($montant, 2, '.', ''));
                
                $nouveauStock = $produit->getStock() - $ligne->getQuantite();
                $produit->setStock($nouveauStock);
                
                $em->persist($ligne);
            }
            
            $commande->recalculerTotal();

            $em->flush();
            $this->addFlash('success', 'Commande modifiée.');
            return $this->redirectToRoute('app_commande');
        }

        return $this->render('commande/form.html.twig', [
            'form' => $form,
            'titre' => 'Modifier la commande',
        ]);
    }

    #[Route('/commande/{id}/delete', name: 'commande_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Commande $commande, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $commande->getId(), $request->request->get('_token'))) {
            
            foreach ($commande->getLigneCommandes() as $ligne) {
                $produit = $ligne->getProduit();
                if ($produit) {
                    $produit->setStock($produit->getStock() + $ligne->getQuantite());
                }
            }

            $em->remove($commande);
            $em->flush();
            $this->addFlash('success', 'Commande supprimée et stock restauré.');
        }

        return $this->redirectToRoute('app_commande');
    }
}