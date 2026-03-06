# Immobilier API

API RESTful pour la gestion d'annonces immobilières, construite avec **Laravel 12 + Octane (RoadRunner)**.  
Elle couvre l'authentification, la gestion des rôles/permissions, la publication d'annonces avec attributs dynamiques, la recherche full-text via MeiliSearch et le stockage de médias via MinIO.

---

## Table des matières

1. [Architecture de l'application](#1-architecture-de-lapplication)
   - [Rôles et permissions](#11-rôles-et-permissions)
2. [Modèles et relations](#2-modèles-et-relations)
3. [Trigger PostgreSQL — validation des attributs](#3-trigger-postgresql--validation-des-attributs)
4. [Lancer le projet](#4-lancer-le-projet)
5. [Tester via Swagger — exemples de données](#5-tester-via-swagger--exemples-de-données)
   - [Authentification](#51-authentification)
   - [Propriétés](#52-propriétés)
   - [Rôles](#53-rôles)
   - [Utilisateurs](#54-utilisateurs)
6. [Stack technique](#6-stack-technique)
7. [Prochaines fonctionnalités](#7-prochaines-fonctionnalités)

---

## 1. Architecture de l'application

L'application suit une structure en couches classique, pensée pour la maintenabilité et la testabilité :

```
HTTP Request
    └── Controller      ← reçoit la requête, délègue au service
          └── DTO       ← valide les entrées (spatie/laravel-data)
          └── Service   ← contient la logique métier
                └── Repository  ← abstrait l'accès aux données (Eloquent)
                      └── Model ← mapping base de données
```

### Détail des couches

| Couche | Rôle |
|---|---|
| **Controllers** (`app/Http/Controllers`) | Reçoivent la requête HTTP, appellent le service correspondant et retournent la réponse JSON. |
| **DTOs** (`app/Data`) | Objets de transfert de données basés sur **`spatie/laravel-data`**. Ils servent à la fois à la **validation des entrées** (via des attributs PHP 8 directement sur les propriétés du DTO) et au transport des données entre les couches. Chaque DTO étend `Spatie\LaravelData\Data` et remplace les Form Requests classiques. |
| **Services** (`app/Services`) | Contiennent la logique métier (ex. génération du titre d'une propriété, gestion des médias, scoping des permissions). |
| **Repositories** (`app/Repositories`) | Implémentent une interface contractuelle (`Contracts/`) et encapsulent toutes les requêtes Eloquent. |
| **Observers** (`app/Observers`) | Réagissent aux événements du cycle de vie Eloquent (création, mise à jour, suppression) pour automatiser des comportements transverses (ex. renumérotation de l'ordre des attributs). |
| **Policies** (`app/Policies`) | Définissent les règles d'autorisation par modèle, utilisées via `Gate` et `authorize()`. |

### Moteur HTTP — Laravel Octane + RoadRunner

L'application tourne sous **Laravel Octane** avec le worker **RoadRunner** (fichier binaire `rr` inclus).  
Contrairement à PHP-FPM qui recharge le framework à chaque requête, RoadRunner maintient le bootstrap de Laravel en mémoire et réutilise les workers, ce qui réduit drastiquement la latence.

---

## 1.1 Rôles et permissions

Le système RBAC repose sur **`spatie/laravel-permission`**. Trois rôles sont définis :

| Rôle | Accès |
|---|---|
| **Super-Admin** | Accès total à toutes les ressources, sans restriction. Un hook `Gate::before` court-circuite tous les checks de policy et retourne `true` dès que l'utilisateur possède ce rôle. |
| **agent** | Peut créer et gérer toutes les ressources métier (Property, PropertyType, Attribute, Region, Image). Sur les propriétés, les policies limitent les actions (`Update`, `Delete`, `Restore`, `ForceDelete`) aux **enregistrements créés par l'agent lui-même**. |
| **visiteur** | Rôle en lecture seule. Les routes publiques `GET /properties` ne nécessitent aucune authentification. |

### Convention de nommage des permissions

Les permissions suivent le format `Action:Modèle`, aligné sur les méthodes standard des policies Laravel :

```
Create:Property        Update:Property        Delete:Property
Restore:Property       ForceDelete:Property   ForceDeleteAny:Property
ViewDeleted:Property   ViewAnyDeleted:Property

Create:Attribute       Update:Attribute       Delete:Attribute       ...
Create:PropertyType    Create:Region          Create:PropertyTypeAttribute  ...
ViewAny:User           AssignRole:User        AssignPermission:Role  ...
```

Les rôles et permissions sont entièrement gérables via l'API (`POST /roles`, `POST /users/{id}/roles`, `POST /roles/{id}/permissions`…) — un Super-Admin peut composer des rôles personnalisés à la volée.

---

## 2. Modèles et relations

### Vue d'ensemble des relations

```
User
 ├── HasRoles (Spatie)
 └── HasApiTokens (Sanctum)

PropertyType ──< PropertyTypeAttribute >── Attribute
     │
     └──< Property
               ├── Region (country_region_id, root_region_id, region_id)
               └── Media (Spatie MediaLibrary)
```

---

### `User`

Table `users`. Représente un utilisateur de la plateforme.

| Colonne | Rôle |
|---|---|
| `name` | Nom affiché |
| `email` | Identifiant unique de connexion |
| `password` | Mot de passe hashé (bcrypt) |
| `email_verified_at` | Date de vérification de l'e-mail |

**Traits / Packages :**
- `HasApiTokens` (Sanctum) — génère les tokens Bearer pour l'authentification API.
- `HasRoles` (Spatie Permission) — gestion des rôles et permissions RBAC.

---

### `Property`

Table `properties`. Cœur du domaine : représente une annonce immobilière.

| Colonne | Rôle |
|---|---|
| `property_type_id` | FK → type de bien (appartement, maison, terrain…) |
| `listing_type` | `sale` ou `rent` — type d'annonce |
| `title` | Titre généré automatiquement (type + attribut configuré + localisation) |
| `description` | Description libre |
| `attributes` | **JSONB** — valeurs des attributs dynamiques du bien (ex. `{"surface": 85, "rooms": 3}`) |
| `price` | Prix en décimal (15, 2) |
| `country_region_id` | FK → région pays (niveau 0 de la hiérarchie) |
| `root_region_id` | FK → région état/wilaya (niveau 1) |
| `region_id` | FK → région la plus précise (commune, quartier…) |
| `address` | Adresse textuelle libre |
| `is_published` | Booléen — l'annonce est-elle visible publiquement |
| `published_at` | Horodatage de la première publication |
| `status` | `available`, `sold` ou `rented` |
| `available_at` | Date de disponibilité du bien |
| `created_by` | FK → utilisateur l'ayant créé |
| `deleted_by` | FK → utilisateur l'ayant supprimé (soft delete tracé) |

**Particularités :**
- **Soft Delete** : la suppression est logique (`deleted_at`), restaurable via `PATCH /{id}/restore`.
- **JSONB + index GIN** : le champ `attributes` bénéficie d'un index GIN pour des requêtes de filtrage performantes (`attributes @> '{"rooms": 3}'`).
- **Scout / MeiliSearch** : les attributs dynamiques sont indexés à plat avec le préfixe `attr_` (ex. `attr_surface`, `attr_rooms`) pour des filtres scalaires ultra-rapides côté MeiliSearch.
- **Génération de titre automatique** : la méthode `generateTitle()` compose le titre sous la forme `[Type] [valeur attribut] [label] à [Wilaya - Commune]` en se basant sur l'attribut marqué `is_used_for_title` pour ce type de bien.
- **Médias** : via `spatie/laravel-medialibrary`, la propriété expose deux collections : `main_image` (image unique) et `images` (galerie). Chaque image uploadée (JPG, PNG ou WebP) est **convertie en WebP à la qualité 85** avant d'être transmise à MediaLibrary — aucun original n'est jamais stocké dans MinIO.

---

### `PropertyType`

Table `property_types`. Catégorie du bien (Appartement, Villa, Terrain…).

| Colonne | Rôle |
|---|---|
| `title` | Nom unique du type |
| `description` | Description optionnelle |
| `order` | Ordre d'affichage dans les listes |
| `created_by` | FK → utilisateur créateur |

**Relations :**
- `attributes()` — `BelongsToMany` via `property_type_attributes` (avec pivot `is_required`, `order`, `is_used_for_title`).
- `properties()` — `HasMany` vers `Property`.

---

### `Attribute`

Table `attributes`. Définition d'un attribut dynamique réutilisable.

| Colonne | Rôle |
|---|---|
| `title` | Identifiant textuel unique de l'attribut (utilisé comme clé dans le JSONB) |
| `description` | Description à usage documentaire/UI |
| `type` | `string`, `integer`, `decimal` ou `boolean` — type de la valeur attendue |
| `options` | **JSONB** — liste de valeurs autorisées (enum) pour les attributs de type `string` |
| `min_value` | Valeur minimale autorisée (décimal 15,8) pour `integer` et `decimal` |
| `max_value` | Valeur maximale autorisée (décimal 15,8) pour `integer` et `decimal` |
| `is_filterable` | Booléen — l'attribut peut-il être utilisé comme filtre de recherche |
| `property_title_label` | Label affiché après la valeur dans le titre généré (ex. `"m²"`, `"pièces"`) |
| `created_by` | FK → utilisateur créateur |

---

### `PropertyTypeAttribute`

Table pivot `property_type_attributes`. Lie un `PropertyType` à un `Attribute` et enrichit cette liaison.

| Colonne | Rôle |
|---|---|
| `property_type_id` | FK → PropertyType |
| `attribute_id` | FK → Attribute |
| `is_required` | Booléen — cet attribut est-il obligatoire pour ce type de bien |
| `is_used_for_title` | Booléen — cet attribut alimente-t-il le titre généré (max 1 par type, garanti par index unique partiel) |
| `order` | Ordre d'affichage du champ dans le formulaire |
| `created_by` | FK → utilisateur créateur |

**Contraintes base de données :**
- Index `UNIQUE (property_type_id, attribute_id)` — un même attribut ne peut être lié qu'une fois au même type.
- Index unique partiel `WHERE is_used_for_title = true` — garantit qu'au maximum **un seul** attribut par type de bien est désigné pour le titre.

**Observer — `PropertyTypeAttributeObserver` :**  
Gère automatiquement la renumérotation de l'ordre lors des créations/mises à jour : insertion en fin de liste si aucun ordre fourni, décalage des autres enregistrements sinon.

---

### `Region`

Table `regions`. Représente une région géographique à n'importe quel niveau de la hiérarchie pays → wilaya/état → commune → quartier.

| Colonne | Rôle |
|---|---|
| `parent_id` | FK auto-référentielle → région parente (`null` pour un pays) |
| `name` | Nom de la région |
| `type` | Type sémantique libre : `country`, `state`, `city`, `district`… |
| `depth` | Profondeur dans l'arbre (0 = pays, 1 = état, 2 = commune…) |
| `code` | Code ISO ou code interne optionnel |
| `created_by` | FK → utilisateur créateur |

**Particularités :**
- `allDescendantIds()` — utilise une **CTE récursive PostgreSQL** pour récupérer en une seule requête tous les IDs descendants à n'importe quelle profondeur. Utile pour filtrer les propriétés d'une wilaya entière.
- Index composé `(parent_id, depth)` pour les requêtes de navigation d'arbre.

---

## 3. Trigger PostgreSQL — validation des attributs

### Objectif

La colonne `attributes` de `properties` est un champ JSONB libre par nature.  
Un trigger PostgreSQL (`trg_validate_property_attributes`) garantit, **au niveau base de données**, que les données insérées ou modifiées sont toujours cohérentes avec la définition des attributs du type de bien.

### Déclenchement

```sql
BEFORE INSERT OR UPDATE OF attributes, property_type_id ON properties
FOR EACH ROW EXECUTE FUNCTION validate_property_attributes();
```

Le trigger ne s'exécute que lorsque les colonnes `attributes` ou `property_type_id` changent réellement, évitant un surcoût lors des mises à jour d'autres colonnes.

### Règles appliquées

| # | Règle | Comportement en cas d'échec |
|---|---|---|
| 1 | `attributes` doit être un **objet JSON** (pas un tableau, pas un scalaire) | Exception `check_violation` |
| 2 | Tout attribut marqué `is_required` doit être **présent et non null** | Exception avec le nom du champ manquant |
| 3a | Type `string` → la valeur JSON doit être une chaîne | Exception |
| 3b | Type `string` + `options` définies → la valeur doit figurer dans la liste | Exception avec les options autorisées |
| 3c | Type `integer` → nombre JSON sans partie décimale | Exception |
| 3d | Type `decimal` → nombre JSON (entier ou flottant) | Exception |
| 3e | Types numériques → respect de `min_value` / `max_value` | Exception avec la borne dépassée |
| 3f | Type `boolean` → valeur JSON booléenne | Exception |
| 4 | Toute clé du JSON doit correspondre à un attribut **défini pour ce type de bien** | Exception avec le nom de la clé inconnue |

> Les attributs dont le `deleted_at` est renseigné (soft-deleted) sont **exclus** des vérifications, ce qui permet de retirer un attribut sans rendre invalides les propriétés existantes.

### Avantage

Cette logique de validation vit **dans la base de données** : même si un bug applicatif, un script de migration, ou un accès direct à PostgreSQL tente d'insérer des données incohérentes, le trigger les bloque systématiquement.

---

## 4. Lancer le projet

### Prérequis

- [Docker](https://www.docker.com/) + [Docker Compose](https://docs.docker.com/compose/)

### Étapes

```bash
# 1. Cloner le dépôt
git clone <url-du-repo> immobilier
cd immobilier

# 2. Créer le fichier d'environnement Laravel
cp .env.example .env
# → Remplir au minimum : APP_KEY, DB_*, MEILISEARCH_KEY, AWS_* (MinIO)

# 3. Créer le fichier d'environnement Docker (optionnel — des valeurs par défaut existent)
cp .env.example .env.docker
# → Personnaliser les ports exposés si nécessaire

# 4. Construire et démarrer tous les services
docker compose up --build
```

L'API sera accessible sur `http://localhost:8080` (ou le port défini par `NGINX_PORT`).  
La console MinIO sera disponible sur `http://localhost:9001`.

### Utilisateurs de test (seedés automatiquement)

Le seeder `PermissionSeeder` crée trois utilisateurs prêts à l'emploi :

| Rôle | Email | Mot de passe |
|---|---|---|
| **Super-Admin** | `super-admin@example.com` | `admin-password` |
| **agent** | `agent@example.com` | `agent-password` |
| **visiteur** | `visiteur@example.com` | `visiteur-password` |

> Ces comptes sont destinés au développement et aux tests uniquement. Pensez à les supprimer ou à changer leurs mots de passe en production.

### Données de test — propriétés seedées

Le seeder `PropertySeeder` insère **50 000 propriétés** générées aléatoirement, couvrant tous les types de biens, régions et attributs disponibles. Les **500 premières** se voient attribuer des images (image principale + galerie).

Ce volume est intentionnel : il permet de démontrer les performances de l'application sous charge réaliste — pagination Eloquent, recherche et filtrage MeiliSearch sur des attributs dynamiques aplatis, et requêtes JSONB avec index GIN sur PostgreSQL.

---

## 5. Tester via Swagger — exemples de données

La documentation interactive Swagger est disponible sur **`http://localhost:8080/docs`**.  
Pour les routes protégées, saisissez votre token Bearer obtenu via `POST /auth/login`.

> **IDs de référence (base seedée par défaut)**  
> Les exemples ci-dessous utilisent les IDs créés par les seeders. Si la base a été re-seedée, ces IDs restent stables :
>
> | Ressource | ID | Valeur |
> |---|---|---|
> | Pays | `1` | Algeria |
> | Wilaya | `46` | Alger |
> | Commune | `48` | Alger Centre |
> | PropertyType | `1` | appartement |
> | PropertyType | `2` | villa |
> | PropertyType | `3` | terrain |
> | PropertyType | `4` | maison |

---

### 5.1 Authentification

#### `POST /auth/register`

```json
{
  "name": "Alice Dupont",
  "email": "alice@example.com",
  "password": "motdepasse123"
}
```

#### `POST /auth/login`

```json
{
  "email": "super-admin@example.com",
  "password": "admin-password"
}
```

> Copiez la valeur de `token` dans la réponse et collez-la dans le champ **Authorize** de Swagger (`Bearer <token>`).

---

### 5.2 Propriétés

#### `GET /properties` — paramètres de query

```
q=appartement
listing_type=sale
status=available
price_min=5000000
price_max=20000000
property_type_id=1
root_region_id=46
per_page=10
```

#### `GET /properties/{id}`

```
id = 1
```

#### `POST /properties` — `multipart/form-data` _(auth requise)_

Ce endpoint utilise `multipart/form-data` pour accepter les fichiers. Dans Swagger, remplissez chaque champ séparément :

| Champ | Valeur exemple |
|---|---|
| `property_type_id` | `1` |
| `listing_type` | `sale` |
| `description` | `Bel appartement lumineux au cœur d'Alger Centre, idéalement situé.` |
| `attributes` | `{"surface": 85, "nombre de pièces": 3, "Etage": 2, "Parking sous sol": true}` |
| `price` | `12500000` |
| `country_region_id` | `1` |
| `root_region_id` | `46` |
| `region_id` | `48` |
| `address` | `12 Rue Didouche Mourad, Alger Centre` |
| `is_published` | `true` |
| `status` | `available` |
| `available_at` | `2026-04-01` |
| `main_image` | _(sélectionner un fichier image JPG/PNG ≤ 5 Mo)_ |

#### `POST /properties/{id}` ou `PATCH /properties/{id}` _(auth requise)_

```json
{
  "price": 11000000,
  "description": "Grand appartement rénové, vue dégagée, parking inclus.",
  "status": "available",
  "is_published": true
}
```

> Seuls les champs à modifier sont nécessaires (PATCH partiel).

#### `DELETE /properties/{id}` _(auth requise — soft delete)_

```
id = 1
```

#### `PATCH /properties/{id}/restore` _(auth requise)_

```
id = 1
```

#### `DELETE /properties/{id}/force` _(auth requise — suppression définitive)_

```
id = 1
```

---

### 5.3 Rôles

#### `GET /roles` — aucun corps

#### `POST /roles` _(auth requise)_

```json
{
  "name": "moderateur"
}
```

#### `GET /roles/{id}`

```
id = 1
```

#### `PATCH /roles/{id}` _(auth requise)_

```json
{
  "name": "coordinateur"
}
```

#### `DELETE /roles/{id}` _(auth requise)_

```
id = 1
```

#### `POST /roles/{id}/permissions` — assigner une permission _(auth requise)_

```json
{
  "permission": "Create:Property"
}
```

> Permissions disponibles : `Create:Property`, `Update:Property`, `Delete:Property`, `Restore:Property`, `ForceDelete:Property`, `ForceDeleteAny:Property`, `ViewDeleted:Property`, `ViewAnyDeleted:Property`, `Create:Attribute`, `Update:Attribute`, `Delete:Attribute`, `Create:PropertyType`, `Create:Region`, `Create:PropertyTypeAttribute`, `ViewAny:User`, `AssignRole:User`, `AssignPermission:Role`

#### `DELETE /roles/{id}/permissions` — révoquer une permission _(auth requise)_

```json
{
  "permission": "Create:Property"
}
```

---

### 5.4 Utilisateurs

#### `GET /users` — aucun corps _(auth requise)_

#### `POST /users` _(auth requise)_

```json
{
  "name": "Bob Martin",
  "email": "bob@example.com",
  "password": "motdepasse123",
  "password_confirmation": "motdepasse123"
}
```

#### `GET /users/{id}` _(auth requise)_

```
id = 1
```

#### `PATCH /users/{id}` _(auth requise)_

```json
{
  "name": "Bob Martin Jr.",
  "email": "bobjr@example.com"
}
```

#### `DELETE /users/{id}` _(auth requise)_

```
id = 1
```

#### `POST /users/{id}/roles` — assigner un rôle _(auth requise)_

```json
{
  "role": "agent"
}
```

> Rôles disponibles : `Super-Admin`, `agent`, `visiteur` (et tout rôle créé via `POST /roles`).

#### `DELETE /users/{id}/roles` — révoquer un rôle _(auth requise)_

```json
{
  "role": "agent"
}
```

---

## 6. Stack technique

### PostgreSQL 16

Base de données relationnelle principale.  
Utilisée pour sa robustesse, son support natif du **JSONB** (index GIN, opérateurs de containment), ses **CTE récursives** (arbre de régions) et la possibilité d'écrire des fonctions/triggers en PL/pgSQL directement dans les migrations Laravel.

### MeiliSearch v1.12

Moteur de recherche full-text intégré via **Laravel Scout**.  
Les propriétés sont indexées avec leurs attributs dynamiques aplatis (`attr_surface`, `attr_rooms`…) pour permettre des filtres scalaires performants côté moteur de recherche.  
Les routes publiques `GET /properties` utilisent MeiliSearch dès qu'un paramètre `q` est fourni ; les requêtes sans recherche restent sur Eloquent.

### MinIO

Stockage objet compatible S3 auto-hébergé.  
Utilisé par **`spatie/laravel-medialibrary`** pour stocker les images des propriétés (image principale + galerie).  
Toutes les images sont **converties en WebP (qualité 85) côté serveur** avant l'envoi — seuls des fichiers `.webp` atterrissent dans le bucket, ce qui réduit significativement le volume stocké et la bande passante de livraison.  
À la création du conteneur, un service `minio-init` crée automatiquement le bucket et le rend accessible en lecture publique.

### Laravel Octane + RoadRunner

Exécution de Laravel en mode long-running process via le binaire RoadRunner (`rr`).  
Élimine le coût de bootstrap de Laravel à chaque requête, idéal pour une API à fort trafic.

### Nginx

Reverse proxy qui expose l'application en HTTP sur le port 80 et transmet les requêtes au service `app` (RoadRunner).

### Spatie Laravel Permission

Gestion RBAC (Roles & Permissions) complète avec support des guards Sanctum.  
Les politiques Laravel (`Policies/`) s'appuient sur ce système pour autoriser chaque action.

### Laravel Sanctum

Authentification stateless par token Bearer pour l'API.  
Les routes protégées utilisent le middleware `auth:sanctum`.

### Documentation API — Swagger / OpenAPI

La documentation interactive est générée avec **Scribe** et exposée au format Swagger UI.  
Elle est accessible à l'adresse : **`http://localhost:8080/docs`**

Elle liste l'ensemble des endpoints, leurs paramètres, les corps de requête attendus et des exemples de réponses.

---

## 7. Prochaines fonctionnalités

### Files d'attente avec Redis

Actuellement, la mise à jour du titre d'une propriété est synchrone.  
Lorsqu'un attribut est modifié (ex. changement de son `property_title_label`, de son `title` ou de ses `options`), toutes les propriétés dont le `PropertyType` utilise cet attribut pour le titre doivent être re-générées.

L'objectif est de déléguer ce travail à des **jobs Laravel** dispatchés dans une file Redis :

```
AttributeUpdated event
    └── Dispatch: RegeneratePropertyTitlesJob(attributeId)
          └── Chunk les propriétés concernées
          └── Re-génère chaque titre via Property::generateTitle()
          └── Met à jour l'index MeiliSearch
```

Cela permettra de :
- Ne pas bloquer la réponse HTTP lors de la modification d'un attribut utilisé par des milliers de propriétés.
- Rejouer les jobs échoués automatiquement (retry + backoff).
- Monitorer les files via **Laravel Horizon**.

### Autres pistes

- **Notifications** (email, push) lors du changement de statut d'une propriété.
- **Multi-tenant** : isoler les annonces par agence immobilière.
