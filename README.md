# 🎫 Projet e-Tickets Jeux Olympiques 2024

## Sommaire

- [Présentation du projet](#présentation-du-projet)  
- [Installation locale](#installation-locale)  
- [Documentation technique](docs/documentation-technique.md)  
- [Manuel d’utilisation](docs/manuel-utilisateur.md)  
- [Tests dans la branche test](#tests)  
- [Évolutions futures](docs/documentation-technique.md#évolutions-futures)  

---

## Présentation du projet

Cette application Symfony 7 permet la réservation sécurisée de billets électroniques pour les Jeux Olympiques 2024 en France.  
Elle offre un site public, un espace utilisateur avec authentification, et un espace administrateur pour gérer les offres.

---

## Installation locale

```bash
git clone https://github.com/jeremyraimbault/Jeux-Olympiques-2024.git
cd Jeux-Olympiques-2024
composer install
cp .env .env.local
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
symfony server:start
