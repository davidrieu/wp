<?php
/**
 * Fichier de test pour diagnostiquer les erreurs 503
 * Accédez à ce fichier directement : /wp-content/plugins/pool-configurator/test-ajax.php
 */

// Charger WordPress
require_once('../../../wp-load.php');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test AJAX Pool Configurator</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <h1>Test AJAX Pool Configurator</h1>
    <div id="results"></div>

    <script>
    jQuery(document).ready(function($) {
        const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const nonce = '<?php echo wp_create_nonce('pool_configurator_nonce'); ?>';

        $('#results').html('<p>Testing AJAX...</p>');

        // Test 1: Simple test sans action
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_pool_data',
                nonce: nonce
            },
            success: function(response) {
                console.log('SUCCESS:', response);
                $('#results').append('<p style="color: green;">✓ AJAX fonctionne! Response: ' + JSON.stringify(response).substring(0, 200) + '</p>');
            },
            error: function(xhr, status, error) {
                console.error('ERROR:', xhr, status, error);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);

                $('#results').append('<p style="color: red;">✗ Erreur ' + xhr.status + ': ' + error + '</p>');
                $('#results').append('<pre>' + xhr.responseText.substring(0, 1000) + '</pre>');
            }
        });

        // Information système
        $('#results').append('<hr><h2>Informations</h2>');
        $('#results').append('<p>AJAX URL: ' + ajaxurl + '</p>');
        $('#results').append('<p>Nonce: ' + nonce + '</p>');
        $('#results').append('<p>User logged in: <?php echo is_user_logged_in() ? "Yes" : "No"; ?></p>');
    });
    </script>
</body>
</html>
