<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Commande;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numero', TextType::class, ['label' => 'Numéro de commande'])
            ->add('statut', TextType::class, ['label' => 'Statut'])
            ->add('date', DateTimeType::class, [
                'label' => 'Date',
                'widget' => 'single_text'
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => function(Client $client) {
                    return $client->getNom() . ' ' . $client->getPrenom();
                },
                'label' => 'Client'
            ])
            ->add('ligneCommandes', CollectionType::class, [
                'entry_type' => LigneCommandeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Produits commandés'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}