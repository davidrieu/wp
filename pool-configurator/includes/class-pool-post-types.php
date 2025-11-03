<?php
/**
 * Gestion des Custom Post Types
 */

if (!defined('ABSPATH')) {
    exit;
}

class Pool_Post_Types {

    public static function init() {
        add_action('init', array(__CLASS__, 'register_post_types'));
        add_action('init', array(__CLASS__, 'register_taxonomies'));
    }

    public static function register_post_types() {
        // Custom Post Type: Tailles de Piscine
        register_post_type('pool_size', array(
            'labels' => array(
                'name' => 'Tailles de Piscine',
                'singular_name' => 'Taille de Piscine',
                'add_new' => 'Ajouter une taille',
                'add_new_item' => 'Ajouter une nouvelle taille',
                'edit_item' => 'Modifier la taille',
                'new_item' => 'Nouvelle taille',
                'view_item' => 'Voir la taille',
                'search_items' => 'Rechercher des tailles',
                'not_found' => 'Aucune taille trouvée',
                'not_found_in_trash' => 'Aucune taille dans la corbeille',
                'menu_name' => 'Configurateur Piscines'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-admin-multisite',
            'menu_position' => 20,
            'supports' => array('title', 'thumbnail'),
            'has_archive' => false,
            'rewrite' => array('slug' => 'pool-size'),
            'capability_type' => 'post',
        ));

        // Custom Post Type: Options
        register_post_type('pool_option', array(
            'labels' => array(
                'name' => 'Options de Piscine',
                'singular_name' => 'Option de Piscine',
                'add_new' => 'Ajouter une option',
                'add_new_item' => 'Ajouter une nouvelle option',
                'edit_item' => 'Modifier l\'option',
                'new_item' => 'Nouvelle option',
                'view_item' => 'Voir l\'option',
                'search_items' => 'Rechercher des options',
                'not_found' => 'Aucune option trouvée',
                'not_found_in_trash' => 'Aucune option dans la corbeille',
                'menu_name' => 'Options'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=pool_size',
            'supports' => array('title', 'editor', 'thumbnail'),
            'has_archive' => false,
            'rewrite' => array('slug' => 'pool-option'),
            'capability_type' => 'post',
        ));

        // Custom Post Type: Configurations (demandes clients)
        register_post_type('pool_configuration', array(
            'labels' => array(
                'name' => 'Demandes Clients',
                'singular_name' => 'Demande Client',
                'view_item' => 'Voir la demande',
                'search_items' => 'Rechercher des demandes',
                'not_found' => 'Aucune demande trouvée',
                'not_found_in_trash' => 'Aucune demande dans la corbeille',
                'menu_name' => 'Demandes'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=pool_size',
            'supports' => array('title'),
            'has_archive' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => false, // Empêche la création manuelle
            ),
            'map_meta_cap' => true,
        ));
    }

    public static function register_taxonomies() {
        // Catégories d'options (ex: Équipement, Finition, Accessoires)
        register_taxonomy('pool_option_category', 'pool_option', array(
            'labels' => array(
                'name' => 'Catégories d\'Options',
                'singular_name' => 'Catégorie d\'Option',
                'search_items' => 'Rechercher des catégories',
                'all_items' => 'Toutes les catégories',
                'edit_item' => 'Modifier la catégorie',
                'update_item' => 'Mettre à jour la catégorie',
                'add_new_item' => 'Ajouter une catégorie',
                'new_item_name' => 'Nouvelle catégorie',
                'menu_name' => 'Catégories'
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'pool-option-category'),
        ));
    }
}
