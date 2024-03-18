# MTG Viewer    

## Description
MTG Viewer est un projet permettant de parcourrir et rechercher des cartes de Magic The Gathering. 
Il est basé sur une API REST, et utilise le framework Symfony pour le back-end et VueJs pour le front-end.

## Stack technique
- Symfony 7.0
- PHP 8.2
- MariaDB 10
- NodeJs 20
- VueJs 3 / Vite 2
- Swagger

## Documentation / Lien
- Symfony : https://symfony.com/doc/current/index.html
- Installation de docker : https://docs.docker.com/get-docker/

## Installation
### Prérequis
- Docker
- Docker compose
- *Optionnel* : Make

### Installation du projet avec Make
Il suffit de lancer make install pour installer le projet, puis récuper les données necessaire pour le projet.
```bash
make install
make get-data
```

### Installation du projet sans Make
Il faut: 
- copier le fichier .env.example en .env, et l'alimenter avec les bonnes valeurs
- récupérer les container
- build le container php 
- installer les dépendances 
- lancer les migrations de la base de données

```bash
cp .env.example .env
docker compose pull
docker compose build
docker compose run --rm php composer install
docker compose run --rm vite npm install
docker compose run --rm php php bin/console doctrine:migrations:migrate --no-interaction
````

Ensuite, il faut récupérer les données nécessaires pour le projet
```bash
curl https://mtgjson.com/api/v5/AllPrintingsCSVFiles.zip -o data/AllPrintingsCSVFiles.zip
unzip -o data/AllPrintingsCSVFiles.zip -d data
```
Si vous n'avez pas curl, vous pouvez télécharger le fichier manuellement et le placer dans le dossier data.

## Importer les données
Pour importer les données dans la base de données, il faut lancer la commande suivante:
```bash
docker compose run --rm php php bin/console import:card
```

## Documentation de l'API
La documentation de l'API est disponible à l'adresse suivante: [http://localhost/api/doc](http://localhost/api/doc). Pensez à adapter l'adresse si vous n'êtes pas en local, ou sur un autre port que le 80.  
La documentation est générée avec [Swagger](https://swagger.io/) en respectant la norme [OpenAPI](https://swagger.io/specification/).  
Vous pouvez voir le controller ApiCardController.php pour un premier exemple.

## Lancer les linters
Nous avons dans ce projets 3 linters: phpstan, phpcs et eslint.
Pour les lancer il faut utiliser les commandes suivantes:
```bash
docker compose run --rm php composer run-script phpstan
docker compose run --rm php composer run-script phpcs
docker compose run --rm vite npm run lint
```  
Il existe également 2 commandes pour corriger automatiquement les erreurs de phpcs et eslint
```bash
docker compose run --rm php composer run-script phpcs:fix
docker compose run --rm vite npm run lint:fix
```

Les différente documentation des linters:   
- phpstan : https://phpstan.org/
- phpcs : https://github.com/squizlabs/PHP_CodeSniffer
- eslint : https://eslint.org/


## Lancer le projet
Une fois le projet installé, il suffit de faire: 
```bash
docker compose up
```

## Excercice
Voir le fichier [EXERCICE.md](EXERCICE.md)
