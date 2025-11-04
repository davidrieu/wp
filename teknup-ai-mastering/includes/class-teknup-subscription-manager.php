<?php
/**
 * Classe de gestion des abonnements WooCommerce
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Subscription_Manager {

    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Plans et leurs limites
     */
    private $plans = array(
        'free_trial' => array(
            'name' => 'Free Trial',
            'limit' => 3,
            'features' => array('standard_processing'),
        ),
        'starter' => array(
            'name' => 'Starter',
            'limit' => 20,
            'features' => array('all_formats', 'intensity_controls', 'genre_presets', 'unlimited_revisions'),
        ),
        'pro' => array(
            'name' => 'Pro',
            'limit' => -1, // Illimité
            'features' => array('unlimited', 'priority_queue', 'advanced_controls', 'lufs_control', 'batch_processing', 'reference_matching'),
        ),
        'label' => array(
            'name' => 'Label',
            'limit' => -1, // Illimité
            'features' => array('unlimited', 'priority_queue', 'api_access', 'white_label', 'dedicated_support'),
        ),
    );

    /**
     * Obtenir l'instance unique
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur
     */
    private function __construct() {
        // Hooks WooCommerce Subscriptions
        add_action('woocommerce_subscription_renewal_payment_complete', array($this, 'reset_usage_on_renewal'), 10, 2);
        add_action('woocommerce_subscription_status_cancelled', array($this, 'handle_subscription_cancelled'), 10, 1);
        add_action('woocommerce_subscription_status_expired', array($this, 'handle_subscription_expired'), 10, 1);
    }

    /**
     * Obtenir le plan actif d'un utilisateur
     *
     * @param int $user_id ID de l'utilisateur
     * @return string|null Slug du plan ou null
     */
    public function get_user_plan($user_id) {
        // Vérifier si l'utilisateur a un abonnement WooCommerce actif
        if (!function_exists('wcs_get_users_subscriptions')) {
            return 'free_trial'; // Par défaut si WC Subscriptions n'est pas actif
        }

        $subscriptions = wcs_get_users_subscriptions($user_id);

        foreach ($subscriptions as $subscription) {
            if ($subscription->has_status(array('active', 'pending-cancel'))) {
                // Récupérer le plan depuis les métadonnées du produit
                $items = $subscription->get_items();
                foreach ($items as $item) {
                    $product_id = $item->get_product_id();
                    $plan_slug = get_post_meta($product_id, '_teknup_plan_slug', true);

                    if ($plan_slug && isset($this->plans[$plan_slug])) {
                        return $plan_slug;
                    }
                }
            }
        }

        // Vérifier si l'utilisateur a déjà utilisé son essai gratuit
        $used_trial = get_user_meta($user_id, 'teknup_used_trial', true);
        if (!$used_trial) {
            return 'free_trial';
        }

        return null; // Pas d'abonnement actif
    }

    /**
     * Obtenir les informations d'un plan
     *
     * @param string $plan_slug Slug du plan
     * @return array|null Informations du plan
     */
    public function get_plan_info($plan_slug) {
        return $this->plans[$plan_slug] ?? null;
    }

    /**
     * Obtenir la limite mensuelle d'un utilisateur
     *
     * @param int $user_id ID de l'utilisateur
     * @return int Limite (-1 = illimité, 0 = aucun accès)
     */
    public function get_user_limit($user_id) {
        $plan = $this->get_user_plan($user_id);

        if (!$plan) {
            return 0; // Pas d'abonnement = pas d'accès
        }

        $plan_info = $this->get_plan_info($plan);
        return $plan_info['limit'] ?? 0;
    }

    /**
     * Obtenir l'usage mensuel actuel d'un utilisateur
     *
     * @param int $user_id ID de l'utilisateur
     * @return int Nombre de jobs complétés ce mois
     */
    public function get_user_usage($user_id) {
        return Teknup_Database::count_user_monthly_jobs($user_id);
    }

    /**
     * Obtenir le quota restant d'un utilisateur
     *
     * @param int $user_id ID de l'utilisateur
     * @return int|string Quota restant ou 'unlimited'
     */
    public function get_user_quota_remaining($user_id) {
        $limit = $this->get_user_limit($user_id);

        if ($limit === -1) {
            return 'unlimited';
        }

        if ($limit === 0) {
            return 0;
        }

        $usage = $this->get_user_usage($user_id);
        $remaining = $limit - $usage;

        return max(0, $remaining);
    }

    /**
     * Vérifier si un utilisateur peut uploader
     *
     * @param int $user_id ID de l'utilisateur
     * @return bool|WP_Error True si peut uploader, WP_Error sinon
     */
    public function can_user_upload($user_id) {
        $plan = $this->get_user_plan($user_id);

        if (!$plan) {
            return new WP_Error(
                'no_subscription',
                __('Vous devez avoir un abonnement actif pour utiliser ce service.', 'teknup-ai-mastering')
            );
        }

        $limit = $this->get_user_limit($user_id);

        // Si illimité, toujours autorisé
        if ($limit === -1) {
            return true;
        }

        // Vérifier le quota
        $usage = $this->get_user_usage($user_id);

        if ($usage >= $limit) {
            return new WP_Error(
                'quota_exceeded',
                sprintf(
                    __('Vous avez atteint votre limite mensuelle de %d masters. Passez à un plan supérieur pour continuer.', 'teknup-ai-mastering'),
                    $limit
                )
            );
        }

        return true;
    }

    /**
     * Incrémenter l'usage d'un utilisateur
     *
     * @param int $user_id ID de l'utilisateur
     * @return void
     */
    public function increment_usage($user_id) {
        // Si c'est le free trial, marquer comme utilisé après le 3ème
        $plan = $this->get_user_plan($user_id);
        if ($plan === 'free_trial') {
            $usage = $this->get_user_usage($user_id);
            if ($usage >= 3) {
                update_user_meta($user_id, 'teknup_used_trial', true);
            }
        }

        // L'incrémentation se fait automatiquement via le comptage des jobs complétés
        // Pas besoin de stocker un compteur séparé
    }

    /**
     * Réinitialiser l'usage lors du renouvellement
     *
     * @param WC_Subscription $subscription Abonnement
     * @param WC_Order $last_order Dernière commande
     * @return void
     */
    public function reset_usage_on_renewal($subscription, $last_order) {
        // Avec notre système de comptage par date, le reset est automatique
        // car on compte uniquement les jobs du mois en cours

        // On peut logger l'événement si nécessaire
        $user_id = $subscription->get_user_id();

        if (get_option('teknup_debug_mode', false)) {
            error_log("Teknup: Subscription renewed for user {$user_id}");
        }
    }

    /**
     * Gérer l'annulation d'un abonnement
     *
     * @param WC_Subscription $subscription Abonnement
     * @return void
     */
    public function handle_subscription_cancelled($subscription) {
        $user_id = $subscription->get_user_id();

        // Annuler tous les jobs en cours pour cet utilisateur
        $processing_jobs = Teknup_Database::get_user_jobs($user_id, array(
            'status' => 'processing',
            'limit' => 100,
        ));

        $job_manager = Teknup_Job_Manager::get_instance();
        foreach ($processing_jobs as $job) {
            $job_manager->cancel_job($job->id);
        }

        if (get_option('teknup_debug_mode', false)) {
            error_log("Teknup: Subscription cancelled for user {$user_id}");
        }
    }

    /**
     * Gérer l'expiration d'un abonnement
     *
     * @param WC_Subscription $subscription Abonnement
     * @return void
     */
    public function handle_subscription_expired($subscription) {
        $user_id = $subscription->get_user_id();

        // Annuler tous les jobs en cours pour cet utilisateur
        $processing_jobs = Teknup_Database::get_user_jobs($user_id, array(
            'status' => 'processing',
            'limit' => 100,
        ));

        $job_manager = Teknup_Job_Manager::get_instance();
        foreach ($processing_jobs as $job) {
            $job_manager->cancel_job($job->id);
        }

        if (get_option('teknup_debug_mode', false)) {
            error_log("Teknup: Subscription expired for user {$user_id}");
        }
    }

    /**
     * Obtenir les informations complètes d'abonnement d'un utilisateur
     *
     * @param int $user_id ID de l'utilisateur
     * @return array
     */
    public function get_user_subscription_info($user_id) {
        $plan = $this->get_user_plan($user_id);
        $plan_info = $this->get_plan_info($plan);

        return array(
            'plan_slug' => $plan,
            'plan_name' => $plan_info['name'] ?? 'Aucun',
            'limit' => $this->get_user_limit($user_id),
            'usage' => $this->get_user_usage($user_id),
            'quota_remaining' => $this->get_user_quota_remaining($user_id),
            'features' => $plan_info['features'] ?? array(),
            'can_upload' => !is_wp_error($this->can_user_upload($user_id)),
        );
    }

    /**
     * Vérifier si un utilisateur a accès à une fonctionnalité
     *
     * @param int $user_id ID de l'utilisateur
     * @param string $feature Fonctionnalité à vérifier
     * @return bool
     */
    public function user_has_feature($user_id, $feature) {
        $plan = $this->get_user_plan($user_id);
        $plan_info = $this->get_plan_info($plan);

        if (!$plan_info) {
            return false;
        }

        return in_array($feature, $plan_info['features']);
    }
}
