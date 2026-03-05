# 🛡️ SecureScan — Plateforme Multi-Scanner de Sécurité

[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Gemini AI](https://img.shields.io/badge/Gemini_AI-4285F4?style=for-the-badge&logo=google-gemini&logoColor=white)](https://deepmind.google/technologies/gemini/)

**SecureScan** est une plateforme d'analyse de sécurité automatisée conçue pour les développeurs et les équipes de sécurité. Elle permet de scanner des dépôts GitHub, d'agréger les résultats de plusieurs outils de sécurité (SAST, Secrets, Dépendances) et de proposer des corrections intelligentes via l'IA.

---

### 📚 Documentation du Projet
Pour une immersion rapide dans les entrailles du projet, consultez nos guides dédiés :
- 📘 **[Documentation Technique](./documentation/DOCUMENTATION_TECHNIQUE.md)** (Architecture, IA, Mapping OWASP)
- 🏗️ **[Architecture Application](./documentation/app_architecture.md)** (MVC, Services, Jobs)
- 🗄️ **[Architecture BDD](./documentation/database_architecture.md)** (Schémas MCD/MLD)
- 📖 **[Référence Projet](./documentation/REFERENCE_PROJET.md)** (Contexte et Roadmap)

---

## 🚀 Fonctionnalités Clés

- **Approche Multi-Scanner** : Orchestration de `Semgrep`, `TruffleHog`, `Bandit`, `ESLint` et `npm audit`.
- **Mapping OWASP Top 10 : 2025** : Classification automatique de chaque vulnérabilité selon le dernier référentiel OWASP.
- **Analyse Asynchrone** : Gestion des scans via une file d'attente (**Laravel Queues**) pour ne pas bloquer l'interface.
- **Correction par IA (Gemini)** : Explications pédagogiques et suggestions de code correct pour chaque faille détectée.
- **Dashboard Dynamique** : Visualisation claire du score de sécurité (0-100) et de la distribution des risques.
- **Intégration Git** : Génération de Pull Requests avec les correctifs validés et export de rapports PDF.

---

## 🛠️ Installation et Configuration

### Prérequis
- PHP 8.3+
- Composer
- Node.js & NPM
- Python 3.x (pour Semgrep et Bandit)
- Laragon ou un serveur web local

### Installation

1. **Cloner le projet** :
   ```bash
   git clone https://github.com/votre-compte/Projet_hackathon.git
   cd Projet_hackathon
   ```

2. **Installer les dépendances** :
   ```bash
   composer install
   npm install && npm run dev
   ```

3. **Configurer l'environnement** :
   Copiez `.env.example` vers `.env` et générez la clé d'application :
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Variables d'environnement importantes** :
   Configurez les chemins vers vos outils de sécurité dans le `.env` :
   ```env
   # Chemins vers les exécutables (Etape cruciale sur Windows)
   SEMGREP_PATH="C:\chemin\vers\python\Scripts\semgrep.exe"
   BANDIT_PATH="C:\chemin\vers\python\Scripts\bandit.exe"
   TRUFFLEHOG_PATH="C:\laragon\www\Projet_hackathon\trufflehog.exe"
   
   # IA
   GEMINI_API_KEY="votre_cle_api"
   ```

5. **Initialiser la base de données** :
   ```bash
   php artisan migrate --seed
   ```

6. **Lancer le serveur et le worker** :
   ```bash
   # Terminal 1 : Serveur web
   php artisan serve
   
   # Terminal 2 : Gestionnaire de tâches (pour les scans)
   php artisan queue:work
   ```

---

## 🏗️ Architecture

Le projet suit une structure **Service-Oriented Architecture (SOA)** intégrée dans Laravel :

- **`App\Services`** : Contient la logique isolée pour chaque outil de scan et l'intégration IA.
- **`App\Jobs`** : Gère l'orchestration asynchrone des analyses.
- **`App\Models`** : Modèles `Scan`, `Vulnerability` et `User`.
- **`documentation/`** : Contient les diagrammes UML, PlantUML et la documentation technique détaillée.

---

## 🧪 Tests

Des scripts de vérification sont disponibles dans le dossier `tests/Scripts` pour valider l'installation des scanners :
```bash
php tests/Scripts/test_all_tools.php
```

---

## 🎓 Projet Hackathon

Ce projet a été réalisé dans le cadre du **Hackathon IPSSI 2026**. 
Responsable : **Safi Bougherara**

---

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.
