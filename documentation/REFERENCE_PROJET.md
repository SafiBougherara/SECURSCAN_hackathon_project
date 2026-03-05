# 📋 SecureScan — Référentiel Hackathon IPSSI 2026

> **Contexte** : Développer en une semaine une plateforme web d'analyse de qualité et sécurité de code pour CyberSafe Solutions. La plateforme doit orchestrer des outils open source, agréger leurs résultats, les mapper sur l'OWASP Top 10 : 2025 et proposer des corrections automatisées.

---

## ⚠️ Deadline absolue
- **Rendu des livrables : Jeudi 5 mars 2026 à 17h00** (délai impératif)
- **Soutenances : Vendredi 6 mars 2026** — 20 min de présentation + 5-10 min de questions

---

## ✅ Fonctionnalités OBLIGATOIRES

### A. Soumission de projet
- [ ] Saisie d'une URL de repository Git (GitHub / GitLab) **ou** upload d'une archive ZIP
- [ ] Clonage automatique du repository côté serveur
- [ ] Détection automatique du langage/framework (PHP, JavaScript, Node.js, Python…)

### B. Analyse de sécurité automatisée
Intégrer et orchestrer **au minimum 3 outils** parmi :

| Type | Outils suggérés | Description |
|------|----------------|-------------|
| SAST | Semgrep, ESLint Security | Analyse statique du code source |
| Dépendances | npm audit, Composer audit | Scan des CVE connues |
| Secrets | git-secrets, TruffleHog | Détection de clés API, mots de passe, tokens |
| Qualité code | ESLint, PHPStan | Analyse de qualité générale |

- [ ] Chaque outil lancé via **CLI**
- [ ] Sortie parsée en **JSON** et stockée

### C. Mapping OWASP Top 10 : 2025
Couvrir **au minimum 5 catégories sur 10** :

| Rang | Catégorie | Exemples |
|------|-----------|---------|
| A01 | Broken Access Control | IDOR, CORS mal configuré, escalade de privilèges |
| A02 | Security Misconfiguration | Headers manquants, debug actif, config par défaut |
| A03 | Software Supply Chain Failures | Dépendances vulnérables, packages malveillants |
| A04 | Cryptographic Failures | Mots de passe en clair, algorithmes obsolètes |
| A05 | Injection | SQL injection, XSS, command injection |
| A06 | Insecure Design | Absence de validation, flux non sécurisés |
| A07 | Authentication Failures | Brute force, sessions non invalidées |
| A08 | Software/Data Integrity Failures | CI/CD non sécurisé, désérialisation |
| A09 | Logging & Alerting Failures | Logs absents, pas d'alertes sur erreurs |
| A10 | Mishandling of Exceptional Conditions | Erreurs non gérées, fail-open, stack traces exposées |

### D. Dashboard de visualisation
- [ ] Score de sécurité global (A/B/C/D/F ou note sur 100)
- [ ] Répartition des vulnérabilités par sévérité (critique, haute, moyenne, basse)
- [ ] Distribution par catégorie OWASP Top 10 (graphique)
- [ ] Liste détaillée des findings avec : fichier, ligne, description, sévérité, catégorie OWASP
- [ ] Filtres et tri (par sévérité, par outil source, par catégorie OWASP)

### E. Système de correction automatisé (template-based)
- [ ] Injection SQL → requêtes préparées / paramétrées
- [ ] XSS → échappement des sorties (`htmlspecialchars`, `DOMPurify`)
- [ ] Dépendances vulnérables → suggestion de version patchée
- [ ] Secrets exposés → remplacement par variable d'environnement
- [ ] Mots de passe en clair → hachage `bcrypt`/`argon2`
- [ ] L'utilisateur peut **valider ou rejeter** chaque correction avant application

### F. Intégration Git automatisée
- [ ] Création automatique d'une branche de correction (ex : `fix/securescan-2026-03-05`)
- [ ] Application des corrections validées sur cette branche
- [ ] Push automatique via l'API Git (GitHub API / Octokit ou git CLI)
- [ ] Génération d'un rapport de sécurité **(HTML ou PDF)** résumant l'analyse et les corrections

---

## 🌟 Fonctionnalité BONUS — Correction intelligente par IA (+1 à +3 pts)

- [ ] Intégration d'une API LLM (OpenAI, Anthropic Claude ou Mistral)
- [ ] Analyse du contexte complet du code vulnérable (pas un template générique)
- [ ] Génération d'une correction adaptée au code existant
- [ ] Explication pédagogique de la vulnérabilité et du fix
- [ ] Affichage en **diff côte-à-côte** dans l'interface
- [ ] L'utilisateur peut valider, modifier ou rejeter la suggestion IA

---

## 📦 Livrables attendus (avant le 5 mars 17h00)

- [ ] **Repository Git** — historique de commits propre + README complet
- [ ] **Maquettes / wireframes** de l'interface utilisateur
- [ ] **Diagrammes UML** — cas d'utilisation, classes, activité, séquence
- [ ] **Application fonctionnelle** — workflow complet : soumission → analyse → résultats → correction → push
- [ ] **Dashboard** de visualisation avec mapping OWASP
- [ ] **Rapport de sécurité généré** (exemple de sortie HTML ou PDF)
- [ ] **Présentation PowerPoint** pour la soutenance
- [ ] **Documentation technique** — installation, configuration des outils, architecture

---

## 🏆 Barème — Note technique (/20 — coefficient 2)

| Critère | Points | Compétence |
|---------|--------|------------|
| Repository Git (historique, README, .gitignore, branches) | 2 pts | Organisation |
| Architecture & intégration des outils de sécurité | 3 pts | Technique |
| Mapping OWASP Top 10 : 2025 (couverture et pertinence) | **4 pts** | Sécurité |
| Système de correction automatisé (template-based + Git) | **4 pts** | Développement |
| Dashboard & visualisation des résultats | 3 pts | Frontend |
| Projet fonctionnel et démonstration du workflow complet | **4 pts** | Global |
| **BONUS** : Correction intelligente par IA | +1 à +3 pts | Bonus |
| **TOTAL** | **20 pts** | |

---

## 🎤 Barème — Soutenance orale (/20 — coefficient 1)

| Critère | Points | Catégorie |
|---------|--------|-----------|
| Démonstration de l'intégration des outils de sécurité | 1 pt | Technique |
| Interface utilisateur et expérience utilisateur | 2 pts | Frontend |
| Analyse et agrégation des résultats | 2 pts | Technique |
| Mapping OWASP Top 10 et classification | 2 pts | Sécurité |
| Système de fix automatisé (démonstration live) | 1 pt | Développement |
| Intégration Git API & gestion des branches | 1 pt | Technique |
| Génération de rapports de sécurité | 2 pts | Livrable |
| Démonstration complète du workflow | 2 pts | Global |
| Aisance à l'oral et réponses aux questions | 1.5 pt | Présentation |
| Qualité de la présentation (slides, structure) | 1.5 pt | Présentation |
| Projet final (impression générale, aboutissement) | **4 pts** | Global |
| **TOTAL** | **20 pts** | |

> **Formule finale** : `Moyenne = (Note technique × 2 + Note orale × 1) / 3`

---

## 🔧 Stack technologique recommandée

- **Backend** : Node.js (Express), PHP (Symfony/Laravel), Python (Flask/FastAPI)
- **Frontend** : React, Vue.js, ou HTML/CSS/JS natif
- **BDD** : MySQL, PostgreSQL, MongoDB, SQLite
- **Outils sécurité** : Semgrep CLI, ESLint + eslint-plugin-security, npm audit, Composer audit, git-secrets, TruffleHog
- **Git API** : Octokit (GitHub API), GitLab API, ou git CLI
- **BONUS IA** : API OpenAI, API Anthropic Claude, API Mistral

---

## 🔗 Ressources officielles

- OWASP Top 10 : 2025 → https://owasp.org/Top10/2025/
- Semgrep → https://semgrep.dev/docs/
- ESLint Plugin Security → https://github.com/eslint-community/eslint-plugin-security
- npm audit → https://docs.npmjs.com/cli/v6/commands/npm-audit
- Octokit → https://github.com/octokit/octokit.js
- TruffleHog → https://github.com/trufflesecurity/trufflehog
- OWASP Cheat Sheet Series → https://cheatsheetseries.owasp.org/
