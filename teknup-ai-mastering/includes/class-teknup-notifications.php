<?php
/**
 * Classe de gestion des notifications email
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Notifications {

    /**
     * Instance unique
     */
    private static $instance = null;

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
        // Hooks pour personnaliser les emails
        add_filter('wp_mail_content_type', array($this, 'set_html_content_type'));
    }

    /**
     * Définir le content type HTML pour les emails
     *
     * @return string
     */
    public function set_html_content_type() {
        return 'text/html';
    }

    /**
     * Envoyer une notification de complétion
     *
     * @param int $job_id ID du job
     * @return bool
     */
    public function send_completion_notification($job_id) {
        $job = Teknup_Database::get_job($job_id);

        if (!$job) {
            return false;
        }

        $user = get_user_by('ID', $job->user_id);
        if (!$user) {
            return false;
        }

        $download_url = Teknup_File_Manager::generate_download_url($job_id, $job->user_id);

        $subject = __('Votre mastering est prêt !', 'teknup-ai-mastering');

        $message = $this->get_email_template('completion', array(
            'user_name' => $user->display_name,
            'filename' => $job->original_filename,
            'download_url' => $download_url,
            'job_id' => $job_id,
        ));

        return wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Envoyer une notification d'échec
     *
     * @param int $job_id ID du job
     * @return bool
     */
    public function send_failure_notification($job_id) {
        $job = Teknup_Database::get_job($job_id);

        if (!$job) {
            return false;
        }

        $user = get_user_by('ID', $job->user_id);
        if (!$user) {
            return false;
        }

        $subject = __('Problème avec votre mastering', 'teknup-ai-mastering');

        $message = $this->get_email_template('failure', array(
            'user_name' => $user->display_name,
            'filename' => $job->original_filename,
            'error_message' => $job->error_message,
            'support_url' => home_url('/contact/'),
            'job_id' => $job_id,
        ));

        return wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Envoyer une notification de limite atteinte
     *
     * @param int $user_id ID de l'utilisateur
     * @return bool
     */
    public function send_limit_reached_notification($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return false;
        }

        $subscription_manager = Teknup_Subscription_Manager::get_instance();
        $sub_info = $subscription_manager->get_user_subscription_info($user_id);

        $subject = __('Limite mensuelle atteinte', 'teknup-ai-mastering');

        $message = $this->get_email_template('limit_reached', array(
            'user_name' => $user->display_name,
            'plan_name' => $sub_info['plan_name'],
            'limit' => $sub_info['limit'],
            'upgrade_url' => home_url('/pricing/'),
        ));

        return wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Envoyer un email de bienvenue
     *
     * @param int $user_id ID de l'utilisateur
     * @return bool
     */
    public function send_welcome_email($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return false;
        }

        $subject = __('Bienvenue sur Teknup AI Mastering', 'teknup-ai-mastering');

        $message = $this->get_email_template('welcome', array(
            'user_name' => $user->display_name,
            'dashboard_url' => wc_get_account_endpoint_url('teknup-dashboard'),
            'upload_url' => wc_get_account_endpoint_url('teknup-upload'),
        ));

        return wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Obtenir un template d'email
     *
     * @param string $template_name Nom du template
     * @param array $variables Variables à injecter
     * @return string HTML du template
     */
    private function get_email_template($template_name, $variables = array()) {
        $templates = array(
            'completion' => $this->get_completion_template($variables),
            'failure' => $this->get_failure_template($variables),
            'limit_reached' => $this->get_limit_reached_template($variables),
            'welcome' => $this->get_welcome_template($variables),
        );

        return $templates[$template_name] ?? '';
    }

    /**
     * Template email de complétion
     */
    private function get_completion_template($vars) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #0a0a0a; color: #fff; }
                .header { text-align: center; padding: 30px 0; border-bottom: 2px solid #8B0000; }
                .header h1 { color: #DC143C; margin: 0; text-transform: uppercase; letter-spacing: 2px; }
                .content { padding: 30px 0; }
                .button { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #8B0000 0%, #DC143C 100%); color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
                .button:hover { background: linear-gradient(135deg, #DC143C 0%, #8B0000 100%); }
                .footer { text-align: center; padding: 20px 0; border-top: 1px solid #333; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>TEKNUP AI MASTERING</h1>
                </div>
                <div class="content">
                    <h2>🎉 Votre mastering est prêt !</h2>
                    <p>Bonjour <?php echo esc_html($vars['user_name']); ?>,</p>
                    <p>Excellente nouvelle ! Le mastering de votre track <strong><?php echo esc_html($vars['filename']); ?></strong> est terminé.</p>
                    <p>Votre musique a été traitée avec notre technologie d'IA professionnelle et est maintenant prête à déchirer les dancefloors.</p>
                    <p style="text-align: center; margin: 40px 0;">
                        <a href="<?php echo esc_url($vars['download_url']); ?>" class="button">TÉLÉCHARGER VOTRE MASTER</a>
                    </p>
                    <p>Ce lien est valide pendant 7 jours. Vous pouvez également retrouver tous vos masters dans votre tableau de bord.</p>
                    <p>Bon mix ! 🎧</p>
                </div>
                <div class="footer">
                    <p>Teknup AI Mastering - Mastering professionnel en moins d'une minute</p>
                    <p>Job ID: <?php echo esc_html($vars['job_id']); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Template email d'échec
     */
    private function get_failure_template($vars) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #0a0a0a; color: #fff; }
                .header { text-align: center; padding: 30px 0; border-bottom: 2px solid #8B0000; }
                .header h1 { color: #DC143C; margin: 0; text-transform: uppercase; letter-spacing: 2px; }
                .content { padding: 30px 0; }
                .error-box { background: rgba(220, 20, 60, 0.1); border-left: 4px solid #DC143C; padding: 15px; margin: 20px 0; }
                .button { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #8B0000 0%, #DC143C 100%); color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
                .footer { text-align: center; padding: 20px 0; border-top: 1px solid #333; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>TEKNUP AI MASTERING</h1>
                </div>
                <div class="content">
                    <h2>⚠️ Problème avec votre mastering</h2>
                    <p>Bonjour <?php echo esc_html($vars['user_name']); ?>,</p>
                    <p>Nous sommes désolés, mais un problème est survenu lors du traitement de votre fichier <strong><?php echo esc_html($vars['filename']); ?></strong>.</p>
                    <?php if (!empty($vars['error_message'])) : ?>
                    <div class="error-box">
                        <strong>Détail de l'erreur :</strong><br>
                        <?php echo esc_html($vars['error_message']); ?>
                    </div>
                    <?php endif; ?>
                    <p>Notre équipe a été notifiée et nous allons vérifier le problème. Vous pouvez essayer de relancer le traitement ou contacter notre support pour une assistance immédiate.</p>
                    <p style="text-align: center; margin: 40px 0;">
                        <a href="<?php echo esc_url($vars['support_url']); ?>" class="button">CONTACTER LE SUPPORT</a>
                    </p>
                </div>
                <div class="footer">
                    <p>Teknup AI Mastering - Mastering professionnel en moins d'une minute</p>
                    <p>Job ID: <?php echo esc_html($vars['job_id']); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Template email limite atteinte
     */
    private function get_limit_reached_template($vars) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #0a0a0a; color: #fff; }
                .header { text-align: center; padding: 30px 0; border-bottom: 2px solid #8B0000; }
                .header h1 { color: #DC143C; margin: 0; text-transform: uppercase; letter-spacing: 2px; }
                .content { padding: 30px 0; }
                .button { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #8B0000 0%, #DC143C 100%); color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
                .footer { text-align: center; padding: 20px 0; border-top: 1px solid #333; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>TEKNUP AI MASTERING</h1>
                </div>
                <div class="content">
                    <h2>📊 Limite mensuelle atteinte</h2>
                    <p>Bonjour <?php echo esc_html($vars['user_name']); ?>,</p>
                    <p>Vous avez atteint votre limite mensuelle de <?php echo esc_html($vars['limit']); ?> masters avec votre plan <strong><?php echo esc_html($vars['plan_name']); ?></strong>.</p>
                    <p>Passez à un plan supérieur pour continuer à masteriser vos tracks et débloquer des fonctionnalités avancées :</p>
                    <ul>
                        <li>Mastering illimité</li>
                        <li>File d'attente prioritaire</li>
                        <li>Contrôles avancés (LUFS, référence)</li>
                        <li>Traitement par lot</li>
                        <li>Support prioritaire</li>
                    </ul>
                    <p style="text-align: center; margin: 40px 0;">
                        <a href="<?php echo esc_url($vars['upgrade_url']); ?>" class="button">UPGRADER MAINTENANT</a>
                    </p>
                </div>
                <div class="footer">
                    <p>Teknup AI Mastering - Mastering professionnel en moins d'une minute</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Template email de bienvenue
     */
    private function get_welcome_template($vars) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #0a0a0a; color: #fff; }
                .header { text-align: center; padding: 30px 0; border-bottom: 2px solid #8B0000; }
                .header h1 { color: #DC143C; margin: 0; text-transform: uppercase; letter-spacing: 2px; }
                .content { padding: 30px 0; }
                .button { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #8B0000 0%, #DC143C 100%); color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin: 10px; }
                .footer { text-align: center; padding: 20px 0; border-top: 1px solid #333; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>TEKNUP AI MASTERING</h1>
                </div>
                <div class="content">
                    <h2>🎵 Bienvenue sur Teknup !</h2>
                    <p>Bonjour <?php echo esc_html($vars['user_name']); ?>,</p>
                    <p>Merci de nous avoir rejoints ! Vous êtes maintenant prêt à masteriser vos tracks avec notre technologie d'IA professionnelle propulsée par Dolby.io.</p>
                    <h3>Pour commencer :</h3>
                    <ol>
                        <li>Uploadez votre fichier audio (WAV, MP3, FLAC, AIFF)</li>
                        <li>Choisissez vos paramètres (intensité, genre, LUFS)</li>
                        <li>Recevez votre master en moins d'une minute</li>
                    </ol>
                    <p>C'est aussi simple que ça !</p>
                    <p style="text-align: center; margin: 40px 0;">
                        <a href="<?php echo esc_url($vars['upload_url']); ?>" class="button">UPLOADER MA PREMIÈRE TRACK</a>
                        <a href="<?php echo esc_url($vars['dashboard_url']); ?>" class="button">VOIR MON DASHBOARD</a>
                    </p>
                    <p>Si vous avez des questions, notre équipe est là pour vous aider.</p>
                    <p>Let's make some noise! 🔊</p>
                </div>
                <div class="footer">
                    <p>Teknup AI Mastering - Mastering professionnel en moins d'une minute</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
