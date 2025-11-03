jQuery(document).ready(function($) {
    'use strict';

    var wcProductIndex = $('#pool-wc-products-list .pool-wc-product-item').length;

    // Initialiser Select2 sur les produits existants
    $('.wc-product-select').select2({
        width: '100%',
        placeholder: '-- Sélectionner un produit --'
    });

    // Ajouter un nouveau produit WooCommerce
    $('.add-wc-product-btn').on('click', function(e) {
        e.preventDefault();

        var template = $('#pool-wc-product-template').html();
        var newProduct = template.replace(/__INDEX__/g, wcProductIndex);

        $('#pool-wc-products-list').append(newProduct);

        // Initialiser Select2 sur le nouveau champ
        $('#pool-wc-products-list .wc-product-select:last').select2({
            width: '100%',
            placeholder: '-- Sélectionner un produit --'
        });

        wcProductIndex++;

        // Animation d'apparition
        $('#pool-wc-products-list .pool-wc-product-item:last').hide().slideDown(300);
    });

    // Supprimer un produit WooCommerce
    $(document).on('click', '.remove-wc-product-btn', function(e) {
        e.preventDefault();

        var productItem = $(this).closest('.pool-wc-product-item');

        productItem.slideUp(300, function() {
            $(this).remove();
        });
    });
});
