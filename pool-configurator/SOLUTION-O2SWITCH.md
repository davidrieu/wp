# Solution pour o2switch / Tiger Protect WAF

## Problème Identifié

**Tiger Protect WAF** (o2switch) bloque les requêtes AJAX vers `/wp-admin/admin-ajax.php` pour les utilisateurs non connectés.

Tiger Protect considère ces requêtes comme potentiellement malveillantes et demande une vérification JavaScript avant de les laisser passer. Cela empêche le configurateur de piscine de fonctionner.

## Solutions

### Solution 1 : Whitelister admin-ajax.php dans Tiger Protect (RECOMMANDÉ)

1. **Connectez-vous à votre cPanel o2switch**

2. **Allez dans "Tiger Protect"** (dans la section Sécurité)

3. **Accédez à "Règles personnalisées" ou "Whitelist"**

4. **Ajoutez une exception pour admin-ajax.php** :
   - Chemin : `/wp-admin/admin-ajax.php`
   - Type : Désactiver la protection pour ce chemin
   - Ou : Autoriser les requêtes POST

5. **Enregistrez et testez**

### Solution 2 : Whitelister les actions AJAX spécifiques

Si Tiger Protect permet de whitelister par paramètre, ajoutez ces actions :
- `action=get_pool_data`
- `action=save_pool_lead`
- `action=submit_pool_configuration`

### Solution 3 : Créer un fichier .htaccess d'exception

Dans `/wp-admin/`, créez ou modifiez `.htaccess` :

```apache
# Désactiver Tiger Protect pour admin-ajax.php
<Files "admin-ajax.php">
    # Tiger Protect bypass
    SecRuleEngine Off
</Files>
```

⚠️ **Attention** : Cette solution peut ne pas fonctionner selon la configuration de Tiger Protect.

### Solution 4 : Contact Support o2switch (SI AUCUNE SOLUTION NE FONCTIONNE)

Contactez le support o2switch avec ce message :

```
Bonjour,

Tiger Protect WAF bloque les requêtes AJAX légitimes vers /wp-admin/admin-ajax.php
pour mon plugin WordPress de configurateur de produits.

Les requêtes retournent un code 503 avec une page de challenge JavaScript qui empêche
le fonctionnement normal de mon site.

Actions AJAX concernées :
- get_pool_data
- save_pool_lead
- submit_pool_configuration

Pourriez-vous m'indiquer comment whitelister ces requêtes dans Tiger Protect
ou désactiver temporairement la protection pour ce chemin ?

URL du site : https://www.poolkit.clickdev.website
Chemin concerné : /wp-admin/admin-ajax.php

Merci de votre aide.
```

### Solution 5 : Désactiver temporairement Tiger Protect (TEST UNIQUEMENT)

⚠️ **Uniquement pour tester - NE PAS LAISSER DÉSACTIVÉ EN PRODUCTION**

1. Allez dans Tiger Protect dans cPanel
2. Désactivez temporairement Tiger Protect
3. Testez le configurateur
4. Si ça fonctionne, réactivez Tiger Protect et utilisez une des solutions ci-dessus

## Vérification

Après avoir appliqué une solution :

1. Déconnectez-vous de WordPress (ou utilisez la navigation privée)
2. Accédez au configurateur
3. Vérifiez dans la console du navigateur (F12)
4. Si vous ne voyez plus l'erreur 503, c'est résolu !

## Alternative : Modifier le Plugin pour Éviter AJAX Initial

Si aucune solution Tiger Protect ne fonctionne, je peux modifier le plugin pour :
- Charger les données directement dans le HTML (pas d'AJAX au chargement)
- Utiliser AJAX seulement pour la sauvegarde (quand l'utilisateur a passé le challenge)

Cette solution est moins élégante mais fonctionnera avec Tiger Protect.

## Ressources o2switch

- [Documentation Tiger Protect](https://faq.o2switch.fr/hebergement-mutualise/tutoriels-cpanel/tiger-protect)
- [Contact Support o2switch](https://www.o2switch.fr/support)
- Forum o2switch : Recherchez "Tiger Protect AJAX" ou "admin-ajax.php"

## Test de Confirmation

Une fois la solution appliquée, retournez sur :
```
https://www.poolkit.clickdev.website/wp-content/plugins/pool-configurator/simple-test.php
```

Cliquez sur "Tester AJAX" :
- ✅ Si vous voyez un message vert "Le serveur répond correctement!" → Problème résolu
- ❌ Si vous voyez toujours l'erreur 503 → Essayez une autre solution

---

**Note** : Tiger Protect est un excellent outil de sécurité, mais il faut configurer des exceptions pour les fonctionnalités WordPress légitimes comme admin-ajax.php.
