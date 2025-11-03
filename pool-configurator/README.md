# 🏊 Configurateur de Piscines WooCommerce

Un plugin WordPress moderne et interactif pour créer un configurateur de piscines avec une interface style Typeform, intégré à WooCommerce.

## 📋 Fonctionnalités

### Backend (Administration)
- ✅ Création de tailles de piscine personnalisées
- ✅ **Sélection de produits WooCommerce** à inclure dans chaque taille
- ✅ **Liaison des options compatibles** par taille de piscine
- ✅ Création d'options supplémentaires (équipements, finitions, accessoires)
- ✅ Gestion automatique des prix via WooCommerce
- ✅ Visualisation des demandes clients avec toutes les informations

### Frontend (Interface Client)
- ✅ Interface moderne style Typeform
- ✅ Navigation fluide entre les étapes avec animations
- ✅ Barre de progression animée
- ✅ Calcul du prix en temps réel
- ✅ **Affichage des options compatibles** selon la taille choisie
- ✅ Système de sélection intuitif avec feedback visuel
- ✅ Formulaire de contact intégré
- ✅ **Ajout automatique au panier WooCommerce**
- ✅ **Redirection vers le panier** pour finaliser l'achat

## 🎨 Code Couleur

Le plugin utilise la palette de couleurs suivante :
- **Primaire** : `#00575d` (Bleu-vert foncé)
- **Secondaire** : `#0ca9c1` (Bleu turquoise)

## 📦 Installation

### Prérequis
- WordPress 5.0 ou supérieur
- **WooCommerce 3.0 ou supérieur** (obligatoire)

### Étapes d'installation
1. Assurez-vous que WooCommerce est installé et activé
2. Téléchargez le dossier `pool-configurator`
3. Placez-le dans le répertoire `/wp-content/plugins/` de votre installation WordPress
4. Activez le plugin depuis le menu "Extensions" de WordPress
5. Un nouveau menu "Configurateur Piscines" apparaîtra dans votre administration

## 🚀 Utilisation

### Configuration en Backoffice

#### 1. Créer des Produits WooCommerce (Prérequis)

Avant de configurer les piscines, créez vos produits WooCommerce :
- Allez dans **WooCommerce > Produits > Ajouter un produit**
- Créez tous les produits que vous souhaitez inclure (liner, filtration, échelle, etc.)
- Définissez le prix de chaque produit

#### 2. Créer des Tailles de Piscine

1. Allez dans **Configurateur Piscines > Ajouter une taille**
2. Remplissez les informations :
   - **Titre** : Nom de la taille (ex: "Piscine Compacte")
   - **Image mise en avant** : Photo de la piscine
   - **Dimensions** : Les dimensions (ex: "8m x 4m")
   - **Prix de base** : Le prix de départ en euros
   - **Description** : Texte descriptif pour le client

3. **Sélectionnez les Produits WooCommerce Inclus** :
   - Cliquez sur "Ajouter un produit WooCommerce"
   - Sélectionnez un produit dans la liste déroulante
   - Le prix sera automatiquement récupéré depuis WooCommerce
   - Ajoutez autant de produits que nécessaire

4. **Liez les Options Compatibles** :
   - Cochez les options qui seront disponibles pour cette taille
   - Seules ces options seront affichées au client
   - Cela permet d'avoir des options différentes selon les tailles

5. Publiez la taille

#### 3. Créer des Options

1. Allez dans **Configurateur Piscines > Options > Ajouter une option**
2. Remplissez les informations :
   - **Titre** : Nom de l'option (ex: "Chauffage solaire")
   - **Image mise en avant** : Photo de l'option
   - **Prix de l'option** : Prix en euros
   - **Description détaillée** : Informations pour le client
   - **Icône** : Emoji ou classe Font Awesome (ex: "🔥" ou "fas fa-fire")

3. Optionnellement, assignez l'option à une **Catégorie** (Équipement, Finition, Accessoires...)

4. Publiez l'option

#### 3. Consulter les Demandes

Les demandes clients apparaissent dans **Configurateur Piscines > Demandes**. Chaque demande contient :
- Informations du client (nom, email, téléphone)
- Configuration détaillée de la piscine
- Taille sélectionnée avec produits inclus
- Options choisies
- Prix total
- Date de la demande

### Affichage Frontend

#### Ajouter le configurateur à une page

1. Créez ou éditez une page
2. Ajoutez le shortcode suivant :
   ```
   [pool_configurator]
   ```
3. Publiez la page

#### Utilisation par les clients

Le configurateur guide les clients à travers 3 étapes :

**Étape 1 : Choix de la taille**
- Le client visualise toutes les tailles disponibles
- Chaque carte affiche les dimensions, la description et le prix
- Les produits inclus sont listés

**Étape 2 : Choix des options**
- **Seules les options compatibles** avec la taille choisie sont affichées
- Le client peut sélectionner/désélectionner des options
- Les options sont optionnelles
- Le prix se met à jour en temps réel

**Étape 3 : Récapitulatif et contact**
- Affichage du récapitulatif complet
- Formulaire de coordonnées (nom, email, téléphone, message)
- **Ajout automatique au panier WooCommerce**
- **Redirection vers le panier** pour finaliser la commande

## 🛒 Processus d'Achat

1. Le client configure sa piscine (taille + options)
2. Il remplit ses coordonnées
3. Clic sur "Ajouter au panier"
4. La configuration est automatiquement ajoutée au panier WooCommerce :
   - Un produit pour la configuration de base (taille + produits inclus)
   - Les produits WooCommerce sélectionnés
   - Un produit pour chaque option choisie
5. Le client est redirigé vers le panier WooCommerce
6. Il peut finaliser sa commande via le processus standard WooCommerce

## 🎭 Animations

Le plugin inclut de nombreuses animations pour une expérience utilisateur agréable :
- ✨ Apparition progressive des cartes
- 🎯 Effet hover avec élévation des cartes
- ✓ Animation checkmark lors de la sélection
- 📊 Barre de progression fluide
- 💰 Animation du prix en temps réel
- 🎬 Transitions douces entre les étapes
- 🎉 Modal de succès avec animation

## 📧 Notifications et Commandes

Quand un client valide sa configuration :
1. La configuration est ajoutée au panier WooCommerce
2. Une entrée est créée dans **Configurateur Piscines > Demandes** pour historique
3. Les coordonnées du client sont stockées dans la session WooCommerce
4. Le client est redirigé vers le panier pour finaliser sa commande
5. Une fois la commande passée, vous recevrez la notification standard de WooCommerce

## 🔧 Personnalisation

### Modifier les couleurs

Éditez le fichier `/public/css/public.css` et modifiez les variables CSS :
```css
:root {
    --primary-color: #00575d;
    --secondary-color: #0ca9c1;
}
```

### Modifier l'email de notification

Le contenu de l'email peut être personnalisé dans le fichier `/public/class-pool-public.php`, méthode `ajax_submit_configuration()`.

## 📂 Structure du Plugin

```
pool-configurator/
├── admin/
│   ├── css/
│   │   └── admin.css
│   ├── js/
│   │   └── admin.js
│   └── class-pool-admin.php
├── includes/
│   └── class-pool-post-types.php
├── public/
│   ├── css/
│   │   └── public.css
│   ├── js/
│   │   └── public.js
│   └── class-pool-public.php
├── pool-configurator.php
└── README.md
```

## 🆘 Support

Pour toute question ou suggestion d'amélioration, n'hésitez pas à nous contacter.

## 📄 Licence

GPL v2 or later

## 🎉 Crédits

Plugin développé pour offrir une expérience utilisateur moderne et intuitive dans la configuration de piscines.

---

**Version** : 1.0.0
**Testé jusqu'à WordPress** : 6.4
**Nécessite au moins** : 5.0
