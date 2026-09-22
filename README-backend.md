# ⚙️ Portfolio — Backend API

🔗 **Frontend / Front-end:** [Portfolio-Frontend](https://github.com/Rayane-Merzougui/Portfolio-Frontend)
🔗 **Live demo / Démo en ligne :** [portfoliofrontend-kohl.vercel.app](https://portfoliofrontend-kohl.vercel.app/)

**🇫🇷 [Français](#-français)** | **🇬🇧 [English](#-english)**

---

## 📸 Screenshots / Captures d'écran

> _Add screenshots (e.g. Postman/Insomnia calls, terminal output, architecture diagram) to a `docs/screenshots/` folder and update the paths below._
> _Ajoute des captures (ex. appels Postman/Insomnia, sortie terminal, schéma d'architecture) dans un dossier `docs/screenshots/` et mets à jour les chemins ci-dessous._

| API root (`/api.php`) | Login response | Articles list | Render dashboard |
|:---:|:---:|:---:|:---:|
| ![API root](docs/screenshots/api-root.png) | ![Login response](docs/screenshots/login-response.png) | ![Articles list](docs/screenshots/articles-list.png) | ![Render dashboard](docs/screenshots/render-dashboard.png) |

---

## 🇫🇷 Français

API REST en **PHP natif** (sans framework) qui alimente mon [portfolio](https://portfoliofrontend-kohl.vercel.app/) : gestion de l'authentification par session, des articles, et des avatars utilisateurs.

### 🛠️ Stack technique

| Catégorie          | Technologies                                        |
|---------------------|-------------------------------------------------------|
| Langage              | PHP 8.2 (natif, sans framework)                       |
| Base de données      | MySQL (local) — SQLite / PostgreSQL (production)      |
| Accès aux données    | PDO (requêtes préparées)                               |
| Serveur              | Apache (Docker) / serveur intégré PHP                  |
| Sessions             | Sessions PHP natives (cookies `SameSite`/`Secure`)      |
| Déploiement          | [Render](https://render.com/) (+ Docker)                |

> 💡 Le backend est conçu pour être **portable** : il utilise MySQL en développement local, et bascule automatiquement sur SQLite (avec repli sur PostgreSQL) en production, selon les contraintes d'hébergement de Render.

### ✨ Fonctionnalités de l'API

- 🔐 **Authentification par session** — inscription, connexion, déconnexion, récupération du profil courant
- 📰 **Articles** — listing paginé (avec auteur et avatar) et création d'articles
- 🖼️ **Upload d'avatar** — envoi et stockage d'images de profil
- 🌍 **CORS configuré** — origines autorisées distinctes en développement et en production
- 🔒 **Mots de passe hashés** (`password_hash` / `password_verify`)
- ✅ **Validation des entrées** (email, longueur du mot de passe, champs requis)

### 📚 Endpoints de l'API

Toutes les routes passent par le point d'entrée unique `api.php` (ex. `/api.php/login` en production).

| Méthode | Endpoint          | Description                              | Auth |
|---------|-------------------|-------------------------------------------|:---:|
| `POST`  | `/register`         | Créer un compte                           | ❌ |
| `POST`  | `/login`             | Se connecter                              | ❌ |
| `POST`  | `/logout`             | Se déconnecter                            | ✅ |
| `GET`   | `/me`                  | Récupérer l'utilisateur connecté          | ✅ |
| `GET`   | `/articles`             | Lister les articles (`page`, `per_page`) | ❌ |
| `POST`  | `/articles`              | Créer un article                          | ✅ |
| `POST`  | `/upload_avatar`          | Changer son avatar                        | ✅ |

**Exemple de réponse — `GET /articles`**
```json
{
  "items": [
    {
      "id": 1,
      "title": "Mon premier article",
      "body": "...",
      "created_at": "2026-01-15 10:30:00",
      "author_name": "Rayane",
      "avatar_url": "/uploads/avatar.jpg"
    }
  ],
  "page": 1,
  "perPage": 10,
  "total": 1,
  "totalPages": 1,
  "hasMore": false
}
```

### 📁 Structure du projet

```
├── api/
│   ├── register.php        # Inscription
│   ├── login.php            # Connexion
│   ├── logout.php            # Déconnexion
│   ├── me.php                 # Utilisateur courant
│   ├── articles.php            # Liste / création d'articles
│   └── upload_avatar.php        # Upload d'avatar
├── config/
│   └── config.php                # CORS, sessions, connexion DB, helpers JSON
├── public/
│   └── uploads/                    # Fichiers uploadés (avatars)
├── api.php                          # Point d'entrée / routeur
├── index.php                         # Page d'accueil de l'API
├── healthcheck.php                    # Route de vérification (health check) pour Render
├── Dockerfile                          # Image Docker (Apache + PHP)
└── render.yaml                          # Configuration de déploiement Render
```

### 🚀 Installation et lancement en local

**Prérequis :** PHP ≥ 8.2 avec les extensions `pdo`, `pdo_mysql`, `mbstring`, `json`, et MySQL (ou MariaDB) en local.

**1. Cloner le dépôt**
```bash
git clone https://github.com/Rayane-Merzougui/Portfolio-Backend.git
cd Portfolio-Backend
```

**2. Créer la base de données locale**
```sql
CREATE DATABASE portfolio_db;
CREATE USER 'portfolio_user'@'localhost' IDENTIFIED BY 'portfolio_pass';
GRANT ALL PRIVILEGES ON portfolio_db.* TO 'portfolio_user'@'localhost';
```
> Les tables `users` et `articles` sont créées automatiquement au premier lancement (voir `config/config.php`) pour SQLite/PostgreSQL ; en MySQL local, pensez à créer ces deux tables si elles n'existent pas encore (colonnes : `id`, `email`, `password_hash`, `name`, `avatar_url`, `created_at` pour `users` ; `id`, `user_id`, `title`, `body`, `created_at` pour `articles`).

**3. Lancer le serveur PHP intégré**
```bash
php -S localhost:8000
```

L'API est alors accessible sur `http://localhost:8000`, et acceptera les requêtes du frontend lancé sur `http://localhost:5173`.

**Alternative avec Docker :**
```bash
docker build -t portfolio-backend .
docker run -p 8080:8080 portfolio-backend
```

### ☁️ Déploiement

Le backend est déployé sur **Render** (voir `render.yaml`) :
- Environnement PHP avec démarrage via `php -S 0.0.0.0:10000`
- Stockage persistant (disque) pour la base SQLite et les fichiers uploadés
- Variables d'environnement : `APP_ENV=production`, `FRONTEND_URL`

### 🔒 Sécurité

- Requêtes SQL exclusivement via des **requêtes préparées (PDO)**
- Mots de passe **hashés**, jamais stockés en clair
- Cookies de session en `HttpOnly`, `Secure` et `SameSite=None` en production
- Liste blanche stricte des origines autorisées (CORS)

### 👤 Auteur

**Rayane Merzougui** — API réalisée pour démontrer mes compétences en **PHP et MySQL**, dans le cadre de mon portfolio full-stack (**HTML5, CSS3, JavaScript, React.js, PHP, MySQL**).

- Frontend associé : [Portfolio-Frontend](https://github.com/Rayane-Merzougui/Portfolio-Frontend)
- Démo : [portfoliofrontend-kohl.vercel.app](https://portfoliofrontend-kohl.vercel.app/)

---

## 🇬🇧 English

REST API built with **native PHP** (no framework) powering my [portfolio](https://portfoliofrontend-kohl.vercel.app/): handles session-based authentication, articles, and user avatars.

### 🛠️ Tech stack

| Category            | Technologies                                          |
|----------------------|---------------------------------------------------------|
| Language              | PHP 8.2 (native, no framework)                          |
| Database              | MySQL (local) — SQLite / PostgreSQL (production)        |
| Data access            | PDO (prepared statements)                                |
| Server                  | Apache (Docker) / PHP built-in server                    |
| Sessions                 | Native PHP sessions (`SameSite`/`Secure` cookies)         |
| Deployment                | [Render](https://render.com/) (+ Docker)                  |

> 💡 The backend is built to be **portable**: it uses MySQL for local development, and automatically switches to SQLite (with a PostgreSQL fallback) in production, based on Render's hosting constraints.

### ✨ API features

- 🔐 **Session-based authentication** — register, login, logout, get current user
- 📰 **Articles** — paginated listing (with author and avatar) and article creation
- 🖼️ **Avatar upload** — upload and store profile pictures
- 🌍 **CORS configured** — distinct allowed origins for development and production
- 🔒 **Hashed passwords** (`password_hash` / `password_verify`)
- ✅ **Input validation** (email, password length, required fields)

### 📚 API endpoints

All routes go through the single entry point `api.php` (e.g. `/api.php/login` in production).

| Method  | Endpoint          | Description                              | Auth |
|---------|-------------------|-------------------------------------------|:---:|
| `POST`  | `/register`         | Create an account                         | ❌ |
| `POST`  | `/login`             | Log in                                    | ❌ |
| `POST`  | `/logout`             | Log out                                   | ✅ |
| `GET`   | `/me`                  | Get the current logged-in user            | ✅ |
| `GET`   | `/articles`             | List articles (`page`, `per_page`)       | ❌ |
| `POST`  | `/articles`              | Create an article                         | ✅ |
| `POST`  | `/upload_avatar`          | Update avatar                             | ✅ |

**Sample response — `GET /articles`**
```json
{
  "items": [
    {
      "id": 1,
      "title": "My first article",
      "body": "...",
      "created_at": "2026-01-15 10:30:00",
      "author_name": "Rayane",
      "avatar_url": "/uploads/avatar.jpg"
    }
  ],
  "page": 1,
  "perPage": 10,
  "total": 1,
  "totalPages": 1,
  "hasMore": false
}
```

### 📁 Project structure

```
├── api/
│   ├── register.php        # Registration
│   ├── login.php            # Login
│   ├── logout.php            # Logout
│   ├── me.php                 # Current user
│   ├── articles.php            # List / create articles
│   └── upload_avatar.php        # Avatar upload
├── config/
│   └── config.php                # CORS, sessions, DB connection, JSON helpers
├── public/
│   └── uploads/                    # Uploaded files (avatars)
├── api.php                          # Entry point / router
├── index.php                         # API landing page
├── healthcheck.php                    # Health check route for Render
├── Dockerfile                          # Docker image (Apache + PHP)
└── render.yaml                          # Render deployment config
```

### 🚀 Local setup

**Requirements:** PHP ≥ 8.2 with the `pdo`, `pdo_mysql`, `mbstring`, `json` extensions, and MySQL (or MariaDB) running locally.

**1. Clone the repo**
```bash
git clone https://github.com/Rayane-Merzougui/Portfolio-Backend.git
cd Portfolio-Backend
```

**2. Create the local database**
```sql
CREATE DATABASE portfolio_db;
CREATE USER 'portfolio_user'@'localhost' IDENTIFIED BY 'portfolio_pass';
GRANT ALL PRIVILEGES ON portfolio_db.* TO 'portfolio_user'@'localhost';
```
> The `users` and `articles` tables are auto-created on first run (see `config/config.php`) for SQLite/PostgreSQL; for local MySQL, create these two tables yourself if they don't exist yet (columns: `id`, `email`, `password_hash`, `name`, `avatar_url`, `created_at` for `users`; `id`, `user_id`, `title`, `body`, `created_at` for `articles`).

**3. Start the built-in PHP server**
```bash
php -S localhost:8000
```

The API is then available at `http://localhost:8000`, and will accept requests from the frontend running on `http://localhost:5173`.

**Alternative with Docker:**
```bash
docker build -t portfolio-backend .
docker run -p 8080:8080 portfolio-backend
```

### ☁️ Deployment

The backend is deployed on **Render** (see `render.yaml`):
- PHP environment started via `php -S 0.0.0.0:10000`
- Persistent disk storage for the SQLite database and uploaded files
- Environment variables: `APP_ENV=production`, `FRONTEND_URL`

### 🔒 Security

- SQL queries exclusively via **prepared statements (PDO)**
- Passwords are **hashed**, never stored in plain text
- Session cookies set as `HttpOnly`, `Secure` and `SameSite=None` in production
- Strict allow-list of authorized CORS origins

### 👤 Author

**Rayane Merzougui** — API built to demonstrate my skills in **PHP and MySQL**, as part of my full-stack portfolio (**HTML5, CSS3, JavaScript, React.js, PHP, MySQL**).

- Frontend: [Portfolio-Frontend](https://github.com/Rayane-Merzougui/Portfolio-Frontend)
- Live demo: [portfoliofrontend-kohl.vercel.app](https://portfoliofrontend-kohl.vercel.app/)
