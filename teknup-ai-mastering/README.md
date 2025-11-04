# Teknup AI Mastering - Plugin WordPress

Plugin WordPress complet pour un service SaaS de mastering audio professionnel destiné aux producteurs de musique électronique. Propulsé par l'intelligence artificielle de Dolby.io.

## 🎯 Vue d'ensemble

Teknup AI Mastering permet aux utilisateurs d'uploader leurs morceaux et de recevoir un mastering de qualité studio en moins d'une minute grâce à l'IA Dolby.io. Le plugin s'intègre complètement avec WooCommerce et WooCommerce Subscriptions pour gérer les abonnements et les paiements.

## ⚡ Installation rapide

1. **Installer le plugin** via Extensions > Ajouter dans WordPress
2. Les **4 produits d'abonnement sont créés automatiquement** ✨
3. **Configurer la clé API** Dolby.io dans Teknup > Réglages
4. **Créer une page** et ajouter le shortcode `[teknup_app]`
5. **C'est prêt !** Vos utilisateurs peuvent masteriser leurs tracks

👉 **[Guide d'installation détaillé](INSTALLATION.md)**

## ✨ Fonctionnalités principales

### Pour les utilisateurs
- **Upload facile** : Interface moderne avec drag & drop pour uploader des fichiers audio (WAV, MP3, FLAC, AIFF)
- **Mastering rapide** : Traitement en moins d'une minute avec l'API Dolby.io
- **Paramètres avancés** : Contrôle de l'intensité, sélection du genre, réglage LUFS
- **Dashboard complet** : Vue d'ensemble des jobs, statistiques, historique
- **Comparaison A/B** : Player audio pour comparer l'original et le master
- **Téléchargements sécurisés** : URLs temporaires avec tokens pour la sécurité
- **Notifications email** : Alertes automatiques pour les jobs complétés ou échoués

### Pour les administrateurs
- **Dashboard complet** : Métriques business, graphiques d'activité, revenus
- **Gestion des jobs** : Vue détaillée de tous les jobs avec filtres et actions
- **Configuration API** : Interface simple pour configurer Dolby.io
- **Logs détaillés** : Mode debug pour le troubleshooting
- **Tâches automatiques** : Cron jobs pour la maintenance et le nettoyage

## 📋 Prérequis

- WordPress 6.0 ou supérieur
- PHP 8.0 ou supérieur
- WooCommerce 7.0 ou supérieur
- WooCommerce Subscriptions (recommandé)
- Compte Dolby.io avec API key
- Hébergement avec stockage suffisant (illimité recommandé)

## 🚀 Installation

### 1. Installation du plugin

1. Téléchargez le plugin ou clonez ce dépôt dans `/wp-content/plugins/`
2. Activez le plugin depuis l'admin WordPress
3. Le plugin créera automatiquement :
   - La table de base de données `wp_teknup_mastering_jobs`
   - La structure de dossiers dans `wp-content/teknup-storage/`
   - Les fichiers de protection `.htaccess` et `index.php`

### 2. Configuration de Dolby.io

1. Créez un compte sur [Dolby.io](https://dolby.io/)
2. Créez une application et récupérez votre clé API
3. Dans WordPress, allez dans **Teknup > Réglages**
4. Entrez votre clé API Dolby.io
5. Cliquez sur "Tester la connexion" pour vérifier

### 3. Configuration WooCommerce

1. Créez des produits WooCommerce de type "Abonnement" pour chaque plan :
   - **Free Trial** : 0€, 3 masters
   - **Starter** : 19€/mois, 20 masters
   - **Pro** : 39€/mois, masters illimités
   - **Label** : Prix personnalisé, masters illimités

2. Pour chaque produit, ajoutez un champ personnalisé :
   - Nom : `_teknup_plan_slug`
   - Valeur : `free_trial`, `starter`, `pro`, ou `label`

### 4. Build des assets frontend

```bash
cd wp-content/plugins/teknup-ai-mastering
npm install
npm run build
```

Pour le développement :
```bash
npm run dev
```

## 🎨 Design System Teknup

Le plugin utilise un design system cohérent avec ces caractéristiques :

**Couleurs**
- Rouge foncé : `#8B0000`
- Rouge : `#DC143C`
- Rouge clair : `#FF1744`
- Bordeaux : `#721C24`
- Fond sombre : `#0a0a0a`
- Texte blanc : `#ffffff`
- Texte gris : `#cccccc`

**Composants**
- Glass morphism sur les cards
- Bordures rouges avec légère transparence
- Ombres lumineuses rouges au hover
- Boutons avec gradient rouge
- Typographie moderne (Inter ou system font)
- Animations subtiles et fluides

## 📊 Plans d'abonnement

### Free Trial
- 3 masters gratuits
- Traitement standard
- Pas de carte bancaire requise

### Starter - 19€/mois
- 20 masters mensuels
- Tous formats audio
- Contrôles d'intensité
- Presets par genre
- Révisions illimitées
- Support email

### Pro - 39€/mois
- Mastering illimité
- File d'attente prioritaire
- Sélection LUFS cible
- Traitement par lot (5 pistes)
- Matching avec référence
- Support prioritaire
- Droits commerciaux

### Label - Sur mesure
- Tout du plan Pro
- Gestionnaire de compte dédié
- Accès API
- Option white-label
- Remises en volume
- Intégration personnalisée
- Garantie SLA

## 🔧 Architecture technique

### Structure du plugin

```
teknup-ai-mastering/
├── includes/              # Classes PHP core
│   ├── class-teknup-activator.php
│   ├── class-teknup-deactivator.php
│   ├── class-teknup-database.php
│   ├── class-teknup-file-manager.php
│   ├── class-teknup-job-manager.php
│   ├── class-teknup-subscription-manager.php
│   ├── class-teknup-dolby-api.php
│   ├── class-teknup-notifications.php
│   └── class-teknup-cron.php
├── admin/                 # Interface administrateur
│   ├── class-teknup-admin.php
│   ├── class-teknup-admin-settings.php
│   ├── class-teknup-admin-dashboard.php
│   ├── class-teknup-admin-jobs.php
│   ├── partials/
│   ├── css/
│   └── js/
├── public/                # Interface utilisateur
│   ├── class-teknup-public.php
│   ├── class-teknup-rest-api.php
│   ├── class-teknup-shortcodes.php
│   ├── class-teknup-woocommerce.php
│   ├── partials/
│   ├── css/
│   └── js/
├── src/                   # Composants React
│   ├── components/
│   │   ├── Upload/
│   │   └── Dashboard/
│   └── index.js
├── assets/                # Assets statiques
│   ├── css/
│   └── images/
└── storage/              # Stockage fichiers (créé à l'activation)
    ├── original/
    └── mastered/
```

### Base de données

Table `wp_teknup_mastering_jobs` :
- `id` : ID unique du job
- `user_id` : ID de l'utilisateur WordPress
- `original_filename` : Nom du fichier original
- `original_filepath` : Chemin du fichier original
- `mastered_filepath` : Chemin du fichier masterisé
- `status` : Statut (pending, uploaded, processing, completed, failed)
- `dolby_job_id` : ID du job chez Dolby
- `file_size` : Taille du fichier en bytes
- `settings` : Paramètres JSON du mastering
- `error_message` : Message d'erreur si échec
- `created_at` : Date de création
- `uploaded_at` : Date d'upload
- `processing_started_at` : Date de début de traitement
- `completed_at` : Date de complétion

### API REST

Endpoints disponibles (namespace `teknup/v1`) :

- `GET /user/subscription` : Informations d'abonnement
- `POST /upload` : Upload et démarrage d'un job
- `GET /jobs/{id}` : Récupérer un job
- `GET /jobs` : Liste des jobs de l'utilisateur
- `DELETE /jobs/{id}` : Supprimer un job
- `POST /jobs/{id}/retry` : Relancer un job échoué
- `GET /jobs/{id}/download` : Obtenir l'URL de téléchargement
- `GET /user/stats` : Statistiques de l'utilisateur

### Tâches cron

- **Toutes les 5 minutes** : Vérification des jobs en attente
- **Quotidien** : Nettoyage des fichiers de plus de 30 jours
- **Quotidien** : Nettoyage des jobs en BDD de plus de 30 jours

## 🔒 Sécurité

### Protection des fichiers
- Accès direct bloqué par `.htaccess`
- URLs temporaires avec tokens et expiration
- Vérification de propriété des jobs
- Sanitization de tous les noms de fichiers

### API
- Authentification WordPress standard
- Nonces pour toutes les requêtes
- Validation stricte des entrées
- Prepared statements pour SQL

### Données sensibles
- Clé API Dolby stockée de manière sécurisée
- Jamais exposée côté client
- Jamais loggée (même en debug)

## 📱 Utilisation avec Shortcodes

### Shortcode principal (Recommandé)

Ajoutez simplement ce shortcode sur n'importe quelle page WordPress :

```
[teknup_app]
```

Cela affichera l'application complète avec :
- ✅ Header avec informations du plan et quota
- ✅ Navigation intégrée (Dashboard, Upload, Historique)
- ✅ Interface utilisateur complète
- ✅ Responsive mobile

### Shortcodes individuels

Vous pouvez aussi utiliser les shortcodes séparément pour plus de flexibilité :

```
[teknup_dashboard]  // Dashboard uniquement
[teknup_upload]     // Interface d'upload uniquement
[teknup_history]    // Historique des jobs uniquement
```

### Exemples d'utilisation

**Page complète "Mon Studio"**
```
[teknup_app]
```

**Page dédiée "Upload"**
```
<h2>Uploadez votre track</h2>
[teknup_upload]
```

**Widget sidebar "Stats"**
```
[teknup_dashboard]
```

### Intégration WooCommerce (Automatique)

Le plugin s'intègre automatiquement dans l'espace "Mon compte" de WooCommerce avec trois nouveaux onglets :

1. **Teknup Dashboard** : Vue d'ensemble et statistiques
2. **Upload** : Interface d'upload et de paramétrage
3. **Historique** : Liste de tous les jobs avec filtres

> Les deux méthodes (shortcode et WooCommerce) fonctionnent simultanément

## 🛠️ Développement

### Mode debug

Activez le mode debug dans **Teknup > Réglages** pour :
- Logger tous les appels API Dolby
- Logger les webhooks reçus
- Logger les erreurs avec stack trace
- Afficher les logs dans l'interface admin

### Hooks disponibles

```php
// Avant la création d'un job
do_action('teknup_before_job_create', $user_id, $file, $settings);

// Après la création d'un job
do_action('teknup_after_job_create', $job_id, $job);

// Avant le démarrage du processing
do_action('teknup_before_processing_start', $job_id);

// Après la complétion d'un job
do_action('teknup_job_completed', $job_id, $job);

// En cas d'échec d'un job
do_action('teknup_job_failed', $job_id, $error_message);
```

### Filtres disponibles

```php
// Modifier les formats acceptés
$formats = apply_filters('teknup_allowed_formats', array('wav', 'mp3', 'flac', 'aiff'));

// Modifier la taille max
$max_size = apply_filters('teknup_max_file_size', 500);

// Modifier les paramètres Dolby par défaut
$settings = apply_filters('teknup_dolby_settings', $settings, $job_id);
```

## 📝 Changelog

### Version 1.0.0
- Version initiale
- Intégration complète Dolby.io
- Gestion des abonnements WooCommerce
- Interface React moderne
- Dashboard admin complet
- Notifications email
- Tâches cron automatiques

## 🆘 Support

### Problèmes courants

**Les jobs restent en "processing"**
- Vérifiez que les tâches cron WordPress fonctionnent
- Vérifiez la clé API Dolby.io
- Activez le mode debug et consultez les logs

**Erreur "Quota exceeded"**
- Vérifiez l'abonnement WooCommerce de l'utilisateur
- Vérifiez que le champ `_teknup_plan_slug` est correct sur le produit

**Fichiers non accessibles**
- Vérifiez les permissions des dossiers (755)
- Vérifiez que le fichier `.htaccess` est présent dans `/teknup-storage/`

### Logs

Avec le mode debug activé, les logs sont disponibles :
- Dans **Teknup > Réglages** (section Logs)
- Dans le fichier `wp-content/debug.log` (si `WP_DEBUG_LOG` activé)

## 📄 Licence

GPL v2 or later

## 👨‍💻 Auteur

Teknup - https://teknup.com

## 🙏 Crédits

- Dolby.io pour l'API de mastering
- WooCommerce pour le système d'abonnements
- React pour l'interface utilisateur
- Chart.js pour les graphiques
