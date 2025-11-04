# Guide d'installation rapide - Teknup AI Mastering

## 📦 Installation du plugin

### Méthode 1 : Via l'admin WordPress (Recommandé)

1. **Préparer le plugin** (sur votre machine locale)
   ```bash
   cd teknup-ai-mastering
   npm install
   npm run build
   ```

2. **Créer l'archive ZIP**
   ```bash
   # Depuis le dossier parent
   zip -r teknup-ai-mastering.zip teknup-ai-mastering/ -x "*/node_modules/*" "*/.git/*"
   ```

3. **Installer sur WordPress**
   - Connectez-vous à votre admin WordPress
   - Allez dans **Extensions > Ajouter**
   - Cliquez sur **Téléverser une extension**
   - Sélectionnez le fichier `teknup-ai-mastering.zip`
   - Cliquez sur **Installer maintenant**
   - Activez le plugin

### Méthode 2 : Par FTP

1. Uploadez le dossier `teknup-ai-mastering` dans `/wp-content/plugins/`
2. Allez dans **Extensions** et activez "Teknup AI Mastering"

### Méthode 3 : Via Git (Développeurs)

```bash
cd /wp-content/plugins/
git clone <votre-repo> teknup-ai-mastering
cd teknup-ai-mastering
npm install
npm run build
```

## ⚙️ Configuration

### 1. Configuration Dolby.io

1. Créez un compte sur [dolby.io](https://dolby.io/)
2. Créez une application Media Enhancement
3. Récupérez votre clé API
4. Dans WordPress : **Teknup > Réglages**
5. Entrez votre clé API
6. Cliquez sur **"Tester la connexion"** ✓

### 2. Produits WooCommerce (Automatique ! ✨)

**Bonne nouvelle !** Les produits d'abonnement sont créés **automatiquement** lors de l'activation du plugin :

✅ **Teknup Free Trial** (0€) - 3 masters gratuits
✅ **Teknup Starter** (19€/mois) - 20 masters mensuels
✅ **Teknup Pro** (39€/mois) - Mastering illimité
✅ **Teknup Label** (99€/mois) - Solution label complète

Les produits sont créés dans la catégorie **"Abonnements Teknup"** et sont prêts à être utilisés immédiatement.

> **Note** : Si WooCommerce Subscriptions est installé, les produits seront de type "Abonnement". Sinon, ils seront créés comme produits simples (vous pourrez les convertir plus tard).

#### Personnalisation des produits (Optionnel)

Vous pouvez modifier les produits créés dans **Produits > Tous les produits** :
- Changer les prix
- Modifier les descriptions
- Ajouter des images
- Personnaliser les durées d'abonnement

⚠️ **Ne supprimez pas** le champ personnalisé `_teknup_plan_slug` - il est essentiel pour le fonctionnement du plugin.

## 🎨 Ajouter l'application sur votre site

### Shortcode principal (Recommandé)

Créez une nouvelle page WordPress et ajoutez simplement :

```
[teknup_app]
```

Ceci affichera l'application complète avec :
- Header avec plan et quota
- Navigation (Dashboard, Upload, Historique)
- Toutes les fonctionnalités

### Shortcodes individuels

Vous pouvez aussi utiliser les shortcodes séparés :

```
[teknup_dashboard]  // Dashboard uniquement
[teknup_upload]     // Upload uniquement
[teknup_history]    // Historique uniquement
```

### Exemples d'utilisation

**Page "Mon Studio"**
```
[teknup_app]
```

**Page "Uploader une track"**
```
[teknup_upload]
```

**Dans un onglet WooCommerce My Account**
L'intégration est automatique - 3 nouveaux onglets sont ajoutés :
- Teknup Dashboard
- Upload
- Historique

## 🔍 Vérification

### Checklist post-installation

- [ ] Plugin activé sans erreur
- [ ] Table `wp_teknup_mastering_jobs` créée
- [ ] Dossier `/wp-content/teknup-storage/` créé
- [ ] Fichiers de protection `.htaccess` présents
- [ ] **4 produits WooCommerce créés automatiquement** ✨
- [ ] Clé API Dolby.io configurée et testée ✓
- [ ] Page avec `[teknup_app]` créée
- [ ] Tâches cron WordPress actives

### Test rapide

1. Créez un compte de test
2. Souscrivez au plan Free Trial
3. Allez sur la page avec `[teknup_app]`
4. Uploadez un fichier audio de test
5. Vérifiez que le job démarre
6. Attendez la complétion (~1 minute)
7. Téléchargez le fichier masterisé

## 🛠️ Configuration avancée

### Réglages disponibles (Teknup > Réglages)

- **Taille max de fichier** : Par défaut 500 MB
- **Intensité par défaut** : Low, Medium, High
- **LUFS cible par défaut** : -14 dB
- **Nettoyage après** : 30 jours par défaut
- **Mode debug** : Pour le troubleshooting

### Personnalisation CSS

Pour personnaliser les couleurs, ajoutez dans votre thème :

```css
:root {
    --teknup-red-dark: #8B0000;
    --teknup-red: #DC143C;
    --teknup-bg-dark: #0a0a0a;
}
```

### Hooks disponibles

```php
// Modifier les formats acceptés
add_filter('teknup_allowed_formats', function($formats) {
    return array('wav', 'mp3', 'flac', 'aiff', 'ogg');
});

// Action après création d'un job
add_action('teknup_after_job_create', function($job_id, $job) {
    // Votre code
}, 10, 2);
```

## 🆘 Dépannage

### Le plugin ne s'active pas
- Vérifiez PHP >= 8.0
- Vérifiez que WooCommerce est installé
- Consultez les logs dans `/wp-content/debug.log`

### Les jobs restent en "processing"
- Activez le mode debug dans Teknup > Réglages
- Vérifiez les tâches cron : wp-admin > Outils > Cron Events
- Testez manuellement : Teknup > Dashboard > "Exécuter le cron"

### Erreur "Quota exceeded"
- Vérifiez l'abonnement WooCommerce de l'utilisateur
- Vérifiez le champ `_teknup_plan_slug` sur le produit
- Regardez Teknup > Dashboard pour les stats

### Fichiers non accessibles
- Vérifiez les permissions : 755 pour dossiers, 644 pour fichiers
- Vérifiez que `.htaccess` est présent dans `/teknup-storage/`
- Testez en mode debug

## 📞 Support

- Documentation complète : Voir [README.md](README.md)
- Issues GitHub : [Créer une issue](https://github.com/votre-repo/issues)
- Email support : support@teknup.com

---

✅ Une fois l'installation terminée, votre service de mastering est prêt à accueillir vos premiers utilisateurs !
