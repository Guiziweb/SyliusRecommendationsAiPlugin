
# Documentation du Plugin Sylius Vertex AI Search for Retail

## Introduction

Ce plugin permet d'intégrer **Vertex AI Search for Retail** avec votre boutique Sylius, en synchronisant les commandes historiques et le catalogue tout en générant des événements utilisateur pour améliorer l'expérience d'achat.

## Commandes

- **Description** : Synchronise toutes les commandes passé.
  ```bash
  bin/console sync:google-orders
  ```

## Produits

- **Description** : Synchronise les produits avec Vertex AI Search for Retail en effectuant un différentiel entre Sylius et l'état actuel des produits dans Vertex AI. Cette commande crée de nouveaux produits s'ils n'existent pas encore et met à jour les produits existants si des modifications sont nécessaires, garantissant ainsi que votre catalogue est toujours à jour.
  ```bash
  bin/console google-sync-products
  ```

## Événements Utilisateur

Le plugin génère des événements utilisateur en fonction des actions réalisées par un customer lorsqu'il est connecté. Les événements gérés sont les suivants :

| Événement              | Description                                                  |
|-----------------------|--------------------------------------------------------------|
| **purchase-complete** | Créé lors de l'achèvement d'une commande.                   |
| **detail-page-view**  | Enregistré lorsque l'utilisateur consulte une page produit.  |
| **add-to-cart**      | Généré lorsque l'utilisateur ajoute un produit au panier.    |
| **remove-from-cart**  | Créé lorsque l'utilisateur retire un produit de son panier.   |

Ces événements sont gérés de manière asynchrone via le composant Messenger de Symfony.


# Frais liés aux recommandations

## Coûts des opérations
- **Importation et gestion** : Gratuits pour les événements utilisateur et les informations du catalogue.
- **Frais appliqués** : Seules les opérations d'entraînement, de réglage et de prédiction entraînent des frais.

### Coûts d'entraînement
- **Tarif** : Par nœud et par heure, facturé quotidiennement si le modèle est activement entraîné.
- Aucune facturation lors de la mise en pause ou de la suppression du modèle.

### Coûts de réglage
- **Tarif** : Par nœud et par heure, facturé une fois le réglage effectué.
- Un réglage incomplet est facturé si le modèle est mis en pause ou supprimé avant la fin du réglage.

## Tarification des prédictions
| Requêtes de prédiction par mois   | Prix pour 1 000 prédictions |
|------------------------------------|-----------------------------|
| Jusqu'à 20 000 000                 | 0,27 $                      |
| Les 280 000 000 suivantes           | 0,18 $                      |
| Après 300 000 000                  | 0,10 $                      |

### Coût d'entraînement et de réglage
- **Entraînement et réglage** : 2,50 $ par nœud et par heure.

## Exemples de coûts

### Exemple A
- **Prédictions** : 1 000 000 000 requêtes.
- **Entraînement** : 500 nœuds-heure par mois.
- **Réglage** : 100 nœuds-heure par mois.

**Calcul des coûts** :
- Prédictions :
    - 20 000 000 de prédictions = 5 400 $
    - 280 000 000 de prédictions = 50 400 $
    - 700 000 000 de prédictions = 70 000 $
- Total prédictions = 125 800 $

- Entraînement : 1 250 $
- Réglage : 250 $
- **Coût total** = 127 300 $

### Exemple B
- **Prédictions** : 10 000 000 requêtes.
- **Entraînement** : 150 nœuds-heure par mois.
- **Réglage** : 30 nœuds-heure par mois.

**Calcul des coûts** :
- Prédictions :
    - 10 000 000 de prédictions = 2 700 $
- Entraînement : 375 $
- Réglage : 75 $
- **Coût total** = 3 150 $ 
