# Diagnostic Erreur 503 - Pool Configurator

## Problème Identifié

L'erreur **503 Service Unavailable** indique que le serveur refuse de traiter les requêtes AJAX. Ce n'est PAS un problème de code du plugin, mais une configuration serveur/sécurité.

**Preuve**: D'autres requêtes WooCommerce échouent aussi avec 503:
- `?wc-ajax=get_compare_fragments`
- `?wc-ajax=get_wishlist_fragments`
- `?wc-ajax=get_refreshed_fragments`

## Causes Possibles

### 1. Plugin de Sécurité
Les plugins suivants peuvent bloquer les requêtes AJAX pour les utilisateurs non connectés:
- **Wordfence**
- **Sucuri Security**
- **iThemes Security**
- **All In One WP Security**

**Solution**: Vérifier les paramètres de ces plugins et autoriser les requêtes AJAX.

### 2. Web Application Firewall (WAF)
- **Cloudflare** (Security Level = High)
- **Sucuri Firewall**
- **ModSecurity**

**Solution**: Mettre le niveau de sécurité à "Medium" ou ajouter des exceptions pour `/wp-admin/admin-ajax.php`

### 3. Limite de Ressources Serveur
- Mémoire PHP insuffisante
- PHP-FPM saturé
- Timeout trop court

**Solution**: Augmenter `memory_limit` dans php.ini (recommandé: 256M minimum)

### 4. Configuration Apache/Nginx
- ModSecurity bloque les POST
- Rate limiting trop strict

## Tests de Diagnostic

### Test 1: Fichier Simple
Accédez à: `https://www.poolkit.clickdev.website/wp-content/plugins/pool-configurator/simple-test.php`

Cliquez sur "Tester AJAX".

**Si ça fonctionne**: Le problème vient de notre plugin
**Si ça échoue aussi**: Le problème vient du serveur/firewall

### Test 2: Logs d'Erreur WordPress

1. Activez le mode debug dans `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2. Consultez `/wp-content/debug.log` après avoir testé le configurateur

### Test 3: Logs d'Erreur PHP

Consultez les logs PHP du serveur (selon votre hébergeur):
- cPanel: Error Log dans File Manager
- Plesk: Logs > Error Logs
- SSH: `/var/log/apache2/error.log` ou `/var/log/php-fpm/error.log`

### Test 4: Désactiver Temporairement les Plugins de Sécurité

1. Désactivez tous les plugins de sécurité
2. Testez le configurateur
3. Réactivez les plugins un par un pour identifier le coupable

## Solutions Rapides

### Solution 1: Cloudflare
Si vous utilisez Cloudflare:
1. Allez dans Security > Settings
2. Changez Security Level de "High" à "Medium"
3. Ou ajoutez une Page Rule pour `/wp-admin/admin-ajax.php` avec Security Level: Low

### Solution 2: Wordfence
1. Allez dans Wordfence > Firewall
2. Vérifiez "Web Application Firewall Status"
3. Ajoutez une exception pour les requêtes vers `admin-ajax.php`

### Solution 3: Augmenter Mémoire PHP
Dans `wp-config.php`, ajoutez AVANT `/* C'est tout */`:
```php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

## Contact Support

Si le problème persiste après ces vérifications, contactez votre hébergeur avec ces informations:
- Les requêtes AJAX retournent 503 Service Unavailable
- URL affectée: `/wp-admin/admin-ajax.php`
- Type de requête: POST avec action=get_pool_data
- L'erreur apparaît pour les utilisateurs NON connectés uniquement (ou aussi pour les connectés?)

## Fichiers de Test Créés

1. `/pool-configurator/simple-test.php` - Test AJAX minimal
2. `/pool-configurator/test-ajax.php` - Test AJAX avec détails
3. Le plugin affiche maintenant des logs détaillés dans la console navigateur
