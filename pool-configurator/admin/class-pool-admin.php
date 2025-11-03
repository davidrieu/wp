<?php
/**
 * Gestion de l'interface d'administration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Pool_Admin {

    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post', array(__CLASS__, 'save_pool_size_meta'));
        add_action('save_post', array(__CLASS__, 'save_pool_option_meta'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }

    public static function enqueue_admin_scripts($hook) {
        global $post_type;

        if (in_array($post_type, array('pool_size', 'pool_option'))) {
            wp_enqueue_style('pool-admin-css', POOL_CONFIGURATOR_PLUGIN_URL . 'admin/css/admin.css', array(), POOL_CONFIGURATOR_VERSION);
            wp_enqueue_script('pool-admin-js', POOL_CONFIGURATOR_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), POOL_CONFIGURATOR_VERSION, true);
        }
    }

    public static function add_meta_boxes() {
        // Metabox pour les tailles de piscine
        add_meta_box(
            'pool_size_details',
            'Détails de la Taille',
            array(__CLASS__, 'render_pool_size_metabox'),
            'pool_size',
            'normal',
            'high'
        );

        add_meta_box(
            'pool_size_products',
            'Produits Inclus',
            array(__CLASS__, 'render_pool_products_metabox'),
            'pool_size',
            'normal',
            'high'
        );

        // Metabox pour les options
        add_meta_box(
            'pool_option_details',
            'Détails de l\'Option',
            array(__CLASS__, 'render_pool_option_metabox'),
            'pool_option',
            'normal',
            'high'
        );

        // Metabox pour les configurations clients
        add_meta_box(
            'pool_configuration_details',
            'Détails de la Demande',
            array(__CLASS__, 'render_configuration_metabox'),
            'pool_configuration',
            'normal',
            'high'
        );
    }

    public static function render_pool_size_metabox($post) {
        wp_nonce_field('pool_size_meta_nonce', 'pool_size_nonce');

        $dimensions = get_post_meta($post->ID, '_pool_dimensions', true);
        $base_price = get_post_meta($post->ID, '_pool_base_price', true);
        $description = get_post_meta($post->ID, '_pool_description', true);
        ?>
        <div class="pool-meta-fields">
            <p>
                <label for="pool_dimensions"><strong>Dimensions (ex: 8m x 4m)</strong></label><br>
                <input type="text" id="pool_dimensions" name="pool_dimensions" value="<?php echo esc_attr($dimensions); ?>" style="width: 100%;" placeholder="8m x 4m">
            </p>

            <p>
                <label for="pool_base_price"><strong>Prix de base (€)</strong></label><br>
                <input type="number" id="pool_base_price" name="pool_base_price" value="<?php echo esc_attr($base_price); ?>" step="0.01" min="0" style="width: 100%;" placeholder="15000.00">
            </p>

            <p>
                <label for="pool_description"><strong>Description</strong></label><br>
                <textarea id="pool_description" name="pool_description" rows="4" style="width: 100%;"><?php echo esc_textarea($description); ?></textarea>
            </p>
        </div>
        <?php
    }

    public static function render_pool_products_metabox($post) {
        $products = get_post_meta($post->ID, '_pool_products', true);
        if (!is_array($products)) {
            $products = array();
        }
        ?>
        <div class="pool-products-wrapper">
            <div id="pool-products-list">
                <?php
                if (!empty($products)) {
                    foreach ($products as $index => $product) {
                        self::render_product_item($index, $product);
                    }
                }
                ?>
            </div>
            <button type="button" class="button add-product-btn">Ajouter un produit</button>
        </div>

        <script type="text/html" id="pool-product-template">
            <?php self::render_product_item('__INDEX__', array()); ?>
        </script>
        <?php
    }

    private static function render_product_item($index, $product = array()) {
        $name = isset($product['name']) ? $product['name'] : '';
        $price = isset($product['price']) ? $product['price'] : '';
        $description = isset($product['description']) ? $product['description'] : '';
        ?>
        <div class="pool-product-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; background: #f9f9f9;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <strong>Produit #<?php echo $index + 1; ?></strong>
                <button type="button" class="button remove-product-btn" style="color: #a00;">Supprimer</button>
            </div>

            <p>
                <label><strong>Nom du produit</strong></label><br>
                <input type="text" name="pool_products[<?php echo $index; ?>][name]" value="<?php echo esc_attr($name); ?>" style="width: 100%;" placeholder="Ex: Liner bleu">
            </p>

            <p>
                <label><strong>Prix (€)</strong></label><br>
                <input type="number" name="pool_products[<?php echo $index; ?>][price]" value="<?php echo esc_attr($price); ?>" step="0.01" min="0" style="width: 100%;" placeholder="0.00">
            </p>

            <p>
                <label><strong>Description</strong></label><br>
                <textarea name="pool_products[<?php echo $index; ?>][description]" rows="2" style="width: 100%;"><?php echo esc_textarea($description); ?></textarea>
            </p>
        </div>
        <?php
    }

    public static function render_pool_option_metabox($post) {
        wp_nonce_field('pool_option_meta_nonce', 'pool_option_nonce');

        $price = get_post_meta($post->ID, '_pool_option_price', true);
        $description = get_post_meta($post->ID, '_pool_option_description', true);
        $icon = get_post_meta($post->ID, '_pool_option_icon', true);
        ?>
        <div class="pool-option-fields">
            <p>
                <label for="pool_option_price"><strong>Prix de l'option (€)</strong></label><br>
                <input type="number" id="pool_option_price" name="pool_option_price" value="<?php echo esc_attr($price); ?>" step="0.01" min="0" style="width: 100%;" placeholder="1500.00">
            </p>

            <p>
                <label for="pool_option_description"><strong>Description détaillée</strong></label><br>
                <textarea id="pool_option_description" name="pool_option_description" rows="4" style="width: 100%;"><?php echo esc_textarea($description); ?></textarea>
            </p>

            <p>
                <label for="pool_option_icon"><strong>Icône (classe Font Awesome ou emoji)</strong></label><br>
                <input type="text" id="pool_option_icon" name="pool_option_icon" value="<?php echo esc_attr($icon); ?>" style="width: 100%;" placeholder="fas fa-swimmer ou 🏊">
            </p>

            <p class="description">
                <em>L'éditeur ci-dessus peut être utilisé pour ajouter plus de détails sur l'option.</em>
            </p>
        </div>
        <?php
    }

    public static function save_pool_size_meta($post_id) {
        // Vérifications de sécurité
        if (!isset($_POST['pool_size_nonce']) || !wp_verify_nonce($_POST['pool_size_nonce'], 'pool_size_meta_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) !== 'pool_size') {
            return;
        }

        // Sauvegarder les métadonnées
        if (isset($_POST['pool_dimensions'])) {
            update_post_meta($post_id, '_pool_dimensions', sanitize_text_field($_POST['pool_dimensions']));
        }

        if (isset($_POST['pool_base_price'])) {
            update_post_meta($post_id, '_pool_base_price', floatval($_POST['pool_base_price']));
        }

        if (isset($_POST['pool_description'])) {
            update_post_meta($post_id, '_pool_description', sanitize_textarea_field($_POST['pool_description']));
        }

        // Sauvegarder les produits
        if (isset($_POST['pool_products']) && is_array($_POST['pool_products'])) {
            $products = array();
            foreach ($_POST['pool_products'] as $product) {
                if (!empty($product['name'])) {
                    $products[] = array(
                        'name' => sanitize_text_field($product['name']),
                        'price' => floatval($product['price']),
                        'description' => sanitize_textarea_field($product['description'])
                    );
                }
            }
            update_post_meta($post_id, '_pool_products', $products);
        }
    }

    public static function save_pool_option_meta($post_id) {
        // Vérifications de sécurité
        if (!isset($_POST['pool_option_nonce']) || !wp_verify_nonce($_POST['pool_option_nonce'], 'pool_option_meta_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) !== 'pool_option') {
            return;
        }

        // Sauvegarder les métadonnées
        if (isset($_POST['pool_option_price'])) {
            update_post_meta($post_id, '_pool_option_price', floatval($_POST['pool_option_price']));
        }

        if (isset($_POST['pool_option_description'])) {
            update_post_meta($post_id, '_pool_option_description', sanitize_textarea_field($_POST['pool_option_description']));
        }

        if (isset($_POST['pool_option_icon'])) {
            update_post_meta($post_id, '_pool_option_icon', sanitize_text_field($_POST['pool_option_icon']));
        }
    }

    public static function render_configuration_metabox($post) {
        $config_data = get_post_meta($post->ID, '_config_data', true);
        $customer_name = get_post_meta($post->ID, '_customer_name', true);
        $customer_email = get_post_meta($post->ID, '_customer_email', true);
        $customer_phone = get_post_meta($post->ID, '_customer_phone', true);
        $customer_message = get_post_meta($post->ID, '_customer_message', true);
        $config_date = get_post_meta($post->ID, '_config_date', true);
        ?>
        <div class="pool-configuration-details">
            <div style="background: #f0f8ff; padding: 20px; border-left: 4px solid #00575d; margin-bottom: 20px;">
                <h3 style="margin-top: 0;">📋 Informations Client</h3>
                <p><strong>Nom :</strong> <?php echo esc_html($customer_name); ?></p>
                <p><strong>Email :</strong> <a href="mailto:<?php echo esc_attr($customer_email); ?>"><?php echo esc_html($customer_email); ?></a></p>
                <p><strong>Téléphone :</strong> <a href="tel:<?php echo esc_attr($customer_phone); ?>"><?php echo esc_html($customer_phone); ?></a></p>
                <p><strong>Date de la demande :</strong> <?php echo esc_html($config_date); ?></p>
                <?php if ($customer_message): ?>
                    <p><strong>Message :</strong><br><?php echo nl2br(esc_html($customer_message)); ?></p>
                <?php endif; ?>
            </div>

            <?php if ($config_data): ?>
                <div style="background: #fff3cd; padding: 20px; border-left: 4px solid #0ca9c1; margin-bottom: 20px;">
                    <h3 style="margin-top: 0;">🏊 Configuration de la Piscine</h3>

                    <?php
                    // Récupérer les détails de la taille
                    if (isset($config_data['size_id'])) {
                        $size = get_post($config_data['size_id']);
                        if ($size) {
                            $dimensions = get_post_meta($size->ID, '_pool_dimensions', true);
                            $base_price = get_post_meta($size->ID, '_pool_base_price', true);
                            $products = get_post_meta($size->ID, '_pool_products', true);

                            echo '<div style="margin-bottom: 20px;">';
                            echo '<h4>Taille sélectionnée</h4>';
                            echo '<p><strong>' . esc_html($size->post_title) . '</strong></p>';
                            if ($dimensions) {
                                echo '<p>Dimensions : ' . esc_html($dimensions) . '</p>';
                            }
                            echo '<p>Prix de base : ' . number_format($base_price, 2, ',', ' ') . ' €</p>';

                            if (is_array($products) && !empty($products)) {
                                echo '<p><strong>Produits inclus :</strong></p><ul>';
                                $total_products_price = 0;
                                foreach ($products as $product) {
                                    echo '<li>' . esc_html($product['name']) . ' - ' . number_format($product['price'], 2, ',', ' ') . ' €</li>';
                                    $total_products_price += $product['price'];
                                }
                                echo '</ul>';
                                echo '<p><em>Sous-total avec produits : ' . number_format($base_price + $total_products_price, 2, ',', ' ') . ' €</em></p>';
                            }
                            echo '</div>';
                        }
                    }

                    // Récupérer les options
                    if (isset($config_data['options']) && !empty($config_data['options'])) {
                        echo '<div style="margin-bottom: 20px;">';
                        echo '<h4>Options sélectionnées</h4>';
                        echo '<ul>';
                        $total_options_price = 0;
                        foreach ($config_data['options'] as $option_id) {
                            $option = get_post($option_id);
                            if ($option) {
                                $option_price = get_post_meta($option->ID, '_pool_option_price', true);
                                $option_icon = get_post_meta($option->ID, '_pool_option_icon', true);
                                echo '<li>';
                                if ($option_icon) {
                                    echo '<span style="margin-right: 5px;">' . esc_html($option_icon) . '</span>';
                                }
                                echo esc_html($option->post_title) . ' - ' . number_format($option_price, 2, ',', ' ') . ' €</li>';
                                $total_options_price += $option_price;
                            }
                        }
                        echo '</ul>';
                        echo '</div>';
                    } else {
                        echo '<p><em>Aucune option sélectionnée</em></p>';
                    }

                    // Prix total
                    if (isset($config_data['total_price'])) {
                        echo '<div style="background: #00575d; color: white; padding: 15px; border-radius: 5px; text-align: center;">';
                        echo '<h3 style="margin: 0; color: white;">Prix Total : ' . number_format($config_data['total_price'], 2, ',', ' ') . ' €</h3>';
                        echo '</div>';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div style="background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50;">
                <p style="margin: 0;"><strong>💡 Conseil :</strong> Contactez le client rapidement pour finaliser sa demande !</p>
            </div>
        </div>
        <?php
    }
}
