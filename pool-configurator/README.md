# 🏊 Configurateur de Piscines

Un plugin WordPress moderne et interactif pour créer un configurateur de piscines avec une interface style Typeform.

## 📋 Fonctionnalités

### Backend (Administration)
- ✅ Création de tailles de piscine personnalisées
- ✅ Ajout de produits inclus avec prix pour chaque taille
- ✅ Création d'options supplémentaires (équipements, finitions, accessoires)
- ✅ Gestion des prix individuels pour chaque élément
- ✅ Visualisation des demandes clients avec toutes les informations

### Frontend (Interface Client)
- ✅ Interface moderne style Typeform
- ✅ Navigation fluide entre les étapes avec animations
- ✅ Barre de progression animée
- ✅ Calcul du prix en temps réel
- ✅ Système de sélection intuitif avec feedback visuel
- ✅ Formulaire de contact intégré
- ✅ Modal de confirmation avec animation

## 🎨 Code Couleur

Le plugin utilise la palette de couleurs suivante :
- **Primaire** : `#00575d` (Bleu-vert foncé)
- **Secondaire** : `#0ca9c1` (Bleu turquoise)

## 📦 Installation

1. Téléchargez le dossier `pool-configurator`
2. Placez-le dans le répertoire `/wp-content/plugins/` de votre installation WordPress
3. Activez le plugin depuis le menu "Extensions" de WordPress
4. Un nouveau menu "Configurateur Piscines" apparaîtra dans votre administration

## 🚀 Utilisation

### Configuration en Backoffice

#### 1. Créer des Tailles de Piscine

1. Allez dans **Configurateur Piscines > Ajouter une taille**
2. Remplissez les informations :
   - **Titre** : Nom de la taille (ex: "Piscine Compacte")
   - **Image mise en avant** : Photo de la piscine
   - **Dimensions** : Les dimensions (ex: "8m x 4m")
   - **Prix de base** : Le prix de départ en euros
   - **Description** : Texte descriptif pour le client

3. Ajoutez les **Produits Inclus** :
   - Cliquez sur "Ajouter un produit"
   - Renseignez le nom, le prix et la description
   - Ajoutez autant de produits que nécessaire

4. Publiez la taille

#### 2. Créer des Options

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
- Le client peut sélectionner/désélectionner des options
- Les options sont optionnelles
- Le prix se met à jour en temps réel

**Étape 3 : Récapitulatif et contact**
- Affichage du récapitulatif complet
- Formulaire de coordonnées (nom, email, téléphone, message)
- Validation et envoi de la demande

## 🎭 Animations

Le plugin inclut de nombreuses animations pour une expérience utilisateur agréable :
- ✨ Apparition progressive des cartes
- 🎯 Effet hover avec élévation des cartes
- ✓ Animation checkmark lors de la sélection
- 📊 Barre de progression fluide
- 💰 Animation du prix en temps réel
- 🎬 Transitions douces entre les étapes
- 🎉 Modal de succès avec animation

## 📧 Notifications

Quand un client envoie une demande :
1. La configuration est enregistrée dans WordPress
2. Un email de notification est envoyé à l'administrateur du site
3. Le client voit une confirmation avec un message de succès

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
