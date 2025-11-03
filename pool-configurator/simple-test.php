<?php
/**
 * Test ultra-simple pour vérifier si le serveur répond
 * Accédez à: /wp-content/plugins/pool-configurator/simple-test.php
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Charger WordPress
require_once('../../../wp-load.php');

// Créer une action AJAX de test très simple
add_action('wp_ajax_simple_test', 'simple_test_callback');
add_action('wp_ajax_nopriv_simple_test', 'simple_test_callback');

function simple_test_callback() {
    wp_send_json_success(array('message' => 'Le serveur répond correctement!'));
}

// Si appelé directement, afficher une page de test
if (!isset($_REQUEST['action'])) {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Test</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <h1>Test Simple AJAX</h1>
    <button id="test-btn">Tester AJAX</button>
    <div id="result"></div>

    <script>
    jQuery('#test-btn').on('click', function() {
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'simple_test',
                nonce: '<?php echo wp_create_nonce('test'); ?>'
            },
            success: function(response) {
                console.log('SUCCESS:', response);
                jQuery('#result').html('<p style="color: green;">✓ ' + response.data.message + '</p>');
            },
            error: function(xhr, status, error) {
                console.error('ERROR:', xhr.status, error);
                console.error('Response:', xhr.responseText);
                jQuery('#result').html('<p style="color: red;">✗ Erreur ' + xhr.status + ': ' + error + '</p><pre>' + xhr.responseText.substring(0, 500) + '</pre>');
            }
        });
    });
    </script>

    <hr>
    <h2>Informations</h2>
    <p>WordPress chargé: <?php echo defined('ABSPATH') ? 'Oui' : 'Non'; ?></p>
    <p>Utilisateur connecté: <?php echo is_user_logged_in() ? 'Oui' : 'Non'; ?></p>
    <p>AJAX URL: <?php echo admin_url('admin-ajax.php'); ?></p>
    <p>PHP Version: <?php echo phpversion(); ?></p>
    <p>Memory Limit: <?php echo ini_get('memory_limit'); ?></p>
</body>
</html>
<?php
}
?>
