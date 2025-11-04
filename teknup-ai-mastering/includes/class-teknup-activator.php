<?php
/**
 * Classe d'activation du plugin
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Activator {

    /**
     * Actions à effectuer lors de l'activation du plugin
     */
    public static function activate() {
        // Créer la table de base de données
        self::create_database_table();

        // Créer la structure de dossiers
        self::create_storage_directories();

        // Créer les fichiers de protection
        self::create_protection_files();

        // Configurer les options par défaut
        self::set_default_options();

        // Créer les produits WooCommerce (abonnements)
        self::create_woocommerce_products();

        // Planifier les tâches cron
        self::schedule_cron_jobs();

        // Flush des rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Créer la table de base de données pour les jobs
     */
    private static function create_database_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . TEKNUP_TABLE_JOBS;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            original_filename varchar(255) NOT NULL,
            original_filepath varchar(500) NOT NULL,
            mastered_filepath varchar(500) DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            dolby_job_id varchar(255) DEFAULT NULL,
            file_size bigint(20) DEFAULT NULL,
            settings longtext DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime NOT NULL,
            uploaded_at datetime DEFAULT NULL,
            processing_started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY created_at (created_at),
            KEY dolby_job_id (dolby_job_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Créer la structure de dossiers de stockage
     */
    private static function create_storage_directories() {
        $directories = array(
            TEKNUP_STORAGE_DIR,
            TEKNUP_STORAGE_DIR . 'original/',
            TEKNUP_STORAGE_DIR . 'mastered/',
        );

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                chmod($dir, 0755);
            }
        }
    }

    /**
     * Créer les fichiers de protection (.htaccess et index.php)
     */
    private static function create_protection_files() {
        // Protection .htaccess pour empêcher l'accès direct
        $htaccess_content = "# Teknup AI Mastering - Protection des fichiers\n";
        $htaccess_content .= "Order Deny,Allow\n";
        $htaccess_content .= "Deny from all\n";
        $htaccess_content .= "<FilesMatch '\.(php)$'>\n";
        $htaccess_content .= "    Allow from all\n";
        $htaccess_content .= "</FilesMatch>\n";

        $htaccess_file = TEKNUP_STORAGE_DIR . '.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, $htaccess_content);
        }

        // Fichiers index.php pour éviter le listing de répertoires
        $index_content = "<?php\n// Silence is golden.\n";
        $directories = array(
            TEKNUP_STORAGE_DIR,
            TEKNUP_STORAGE_DIR . 'original/',
            TEKNUP_STORAGE_DIR . 'mastered/',
        );

        foreach ($directories as $dir) {
            $index_file = $dir . 'index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, $index_content);
            }
        }
    }

    /**
     * Définir les options par défaut
     */
    private static function set_default_options() {
        $default_options = array(
            'teknup_dolby_api_key' => '',
            'teknup_max_file_size' => 500, // MB
            'teknup_default_intensity' => 'medium',
            'teknup_default_lufs' => -14,
            'teknup_cleanup_days' => 30,
            'teknup_debug_mode' => false,
            'teknup_version' => TEKNUP_VERSION,
        );

        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Créer les produits WooCommerce (abonnements)
     */
    private static function create_woocommerce_products() {
        // Vérifier que WooCommerce est actif
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Vérifier si les produits ont déjà été créés
        if (get_option('teknup_products_created')) {
            return;
        }

        // Définition des plans d'abonnement
        $plans = array(
            array(
                'name' => 'Teknup Free Trial',
                'slug' => 'free_trial',
                'price' => 0,
                'billing_period' => 'month',
                'billing_interval' => 1,
                'description' => 'Essai gratuit - 3 masters pour tester le service. Aucune carte bancaire requise.',
                'features' => array(
                    '3 masters gratuits',
                    'Traitement standard',
                    'Tous les formats audio supportés',
                    'Support par email',
                ),
            ),
            array(
                'name' => 'Teknup Starter',
                'slug' => 'starter',
                'price' => 19,
                'billing_period' => 'month',
                'billing_interval' => 1,
                'description' => 'Plan Starter - 20 masters par mois pour producteurs réguliers.',
                'features' => array(
                    '20 masters mensuels',
                    'Tous les formats audio supportés',
                    'Contrôles d\'intensité avancés',
                    'Presets par genre musical',
                    'Révisions illimitées',
                    'Historique de téléchargements',
                    'Support par email',
                ),
            ),
            array(
                'name' => 'Teknup Pro',
                'slug' => 'pro',
                'price' => 39,
                'billing_period' => 'month',
                'billing_interval' => 1,
                'description' => 'Plan Pro - Mastering illimité pour professionnels.',
                'features' => array(
                    'Mastering illimité',
                    'File d\'attente prioritaire',
                    'Tous les contrôles avancés',
                    'Sélection du LUFS cible',
                    'Traitement par lot (5 pistes)',
                    'Matching avec piste de référence',
                    'Support prioritaire',
                    'Droits d\'utilisation commerciale',
                ),
            ),
            array(
                'name' => 'Teknup Label',
                'slug' => 'label',
                'price' => 99,
                'billing_period' => 'month',
                'billing_interval' => 1,
                'description' => 'Plan Label - Solution sur mesure pour labels et studios.',
                'features' => array(
                    'Tout du plan Pro',
                    'Gestionnaire de compte dédié',
                    'Accès API',
                    'Option white-label disponible',
                    'Remises en volume',
                    'Intégration personnalisée',
                    'Garantie SLA',
                    'Support téléphonique',
                ),
            ),
        );

        $created_products = array();

        foreach ($plans as $plan) {
            // Vérifier si le produit existe déjà (par slug)
            $existing = get_page_by_path($plan['slug'], OBJECT, 'product');
            if ($existing) {
                $created_products[$plan['slug']] = $existing->ID;
                continue;
            }

            // Créer le produit
            $product_data = array(
                'post_title' => $plan['name'],
                'post_name' => $plan['slug'],
                'post_content' => $plan['description'],
                'post_status' => 'publish',
                'post_type' => 'product',
            );

            $product_id = wp_insert_post($product_data);

            if ($product_id && !is_wp_error($product_id)) {
                // Définir comme produit d'abonnement si WooCommerce Subscriptions est actif
                if (class_exists('WC_Subscriptions')) {
                    wp_set_object_terms($product_id, 'subscription', 'product_type');

                    // Métadonnées d'abonnement
                    update_post_meta($product_id, '_subscription_price', $plan['price']);
                    update_post_meta($product_id, '_subscription_period', $plan['billing_period']);
                    update_post_meta($product_id, '_subscription_period_interval', $plan['billing_interval']);
                    update_post_meta($product_id, '_subscription_length', 0); // Renouvellement jusqu'à annulation
                } else {
                    // Si pas de Subscriptions, créer comme produit simple
                    wp_set_object_terms($product_id, 'simple', 'product_type');
                }

                // Métadonnées du produit
                update_post_meta($product_id, '_regular_price', $plan['price']);
                update_post_meta($product_id, '_price', $plan['price']);
                update_post_meta($product_id, '_virtual', 'yes');
                update_post_meta($product_id, '_sold_individually', 'yes');
                update_post_meta($product_id, '_tax_status', 'taxable');
                update_post_meta($product_id, '_tax_class', '');

                // IMPORTANT : Ajouter le slug du plan Teknup
                update_post_meta($product_id, '_teknup_plan_slug', $plan['slug']);

                // Ajouter les features comme description courte
                $features_html = '<ul>';
                foreach ($plan['features'] as $feature) {
                    $features_html .= '<li>' . esc_html($feature) . '</li>';
                }
                $features_html .= '</ul>';

                wp_update_post(array(
                    'ID' => $product_id,
                    'post_excerpt' => $features_html,
                ));

                // Catégorie "Abonnements Teknup"
                $category_id = self::get_or_create_product_category('Abonnements Teknup', 'teknup-subscriptions');
                wp_set_object_terms($product_id, array($category_id), 'product_cat');

                $created_products[$plan['slug']] = $product_id;
            }
        }

        // Enregistrer les IDs des produits créés
        update_option('teknup_product_ids', $created_products);
        update_option('teknup_products_created', true);
    }

    /**
     * Créer ou récupérer une catégorie de produit
     */
    private static function get_or_create_product_category($name, $slug) {
        $term = get_term_by('slug', $slug, 'product_cat');

        if ($term) {
            return $term->term_id;
        }

        $result = wp_insert_term($name, 'product_cat', array('slug' => $slug));

        if (is_wp_error($result)) {
            return 0;
        }

        return $result['term_id'];
    }

    /**
     * Planifier les tâches cron
     */
    private static function schedule_cron_jobs() {
        // Vérification des jobs toutes les 5 minutes
        if (!wp_next_scheduled('teknup_check_pending_jobs')) {
            wp_schedule_event(time(), 'teknup_five_minutes', 'teknup_check_pending_jobs');
        }

        // Nettoyage quotidien des vieux fichiers
        if (!wp_next_scheduled('teknup_cleanup_old_files')) {
            wp_schedule_event(time(), 'daily', 'teknup_cleanup_old_files');
        }

        // Nettoyage quotidien des vieux jobs
        if (!wp_next_scheduled('teknup_cleanup_old_jobs')) {
            wp_schedule_event(time(), 'daily', 'teknup_cleanup_old_jobs');
        }
    }
}
