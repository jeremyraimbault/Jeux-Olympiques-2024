# 📘 Documentation Technique – e-Tickets Jeux Olympiques 2024

## 1. Présentation du projet

Ce projet permet la réservation sécurisée de billets électroniques pour les Jeux Olympiques 2024 via une application Symfony. Il inclut un site public, un espace utilisateur et une interface administrateur.

## 2. Architecture de l'application

| Composant         | Détail                                         |
|-------------------|------------------------------------------------|
| Front-end         | Twig + JavaScript (`fetch`, dynamique)         |
| Back-end          | Symfony 7.x + PHP 8.2                          |
| ORM               | Doctrine ORM                                   |
| Base de données   | MySQL                                          |
| Authentification  | Symfony Security + rôles `ROLE_USER`/`ADMIN`   |
| Déploiement       | GitHub / Heroku                                |

## 3. Fonctionnalités principales

- Page d’accueil dynamique
- Consultation et ajout au panier des offres
- Création de compte avec clé unique (K1)
- Réservation avec simulation de paiement
- Génération de clé (K2) et QR Code sécurisé
- Interface administrateur (ajout/modification d’offres)

## 4. Modèle de données

- **User** : id, nom, prénom, email, motDePasse, cléK1, rôle
- **Offer** : id, nom, description, nbPersonnes, prix
- **Reservation** : id, user_id, offer_id, date, cléK2, qr_code

## 5. Sécurité

| Élément                    | Méthode                                                            |
|---------------------------|--------------------------------------------------------------------|
| Mot de passe sécurisé     | Min 12 caractères, majuscule, chiffre, spécial (via Regex)         |
| Clé utilisateur (K1)      | Générée à l’inscription (UUID)                                     |
| Clé transaction (K2)      | Générée à la réservation                                           |
| QR Code                   | Hash `sha256(K1 . K2)` (lib `endroid/qr-code`)                     |
| Authentification Symfony  | Sessions sécurisées + rôles                                        |
| Admin non créable en ligne| Compte ajouté en BDD ou fixtures                                   |

## 6. Tests

| Type          | Outils            | Objectifs                          |
|---------------|-------------------|------------------------------------|
| Unitaire      | PHPUnit           | Entités, services (clé, panier...) |
| Fonctionnel   | WebTestCase       | Routes, formulaires, sessions      |
| Couverture    | PHPUnit + Xdebug  | Rapport > 70% visé                 |

## 7. Évolutions prévues

- EasyAdmin pour l'administration
- Améliorer Statistiques des ventes par offre
- Envoi d’email lors de la création de compte pour confirmer l'email
- Export CSV des ventes
- Ajout d’un cache HTTP
- Support mobile / PWA

## 8. Installation locale

```bash
git clone https://github.com/jeremyraimbault/Jeux-Olympiques-2024.git
cd Jeux-Olympiques-2024
composer install
cp .env .env.local
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
symfony server:start
