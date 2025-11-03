jQuery(document).ready(function($) {
    'use strict';

    var productIndex = $('#pool-products-list .pool-product-item').length;

    // Ajouter un nouveau produit
    $('.add-product-btn').on('click', function(e) {
        e.preventDefault();

        var template = $('#pool-product-template').html();
        var newProduct = template.replace(/__INDEX__/g, productIndex);

        $('#pool-products-list').append(newProduct);
        productIndex++;

        // Animation d'apparition
        $('#pool-products-list .pool-product-item:last').hide().slideDown(300);
    });

    // Supprimer un produit
    $(document).on('click', '.remove-product-btn', function(e) {
        e.preventDefault();

        var productItem = $(this).closest('.pool-product-item');

        productItem.slideUp(300, function() {
            $(this).remove();
            updateProductNumbers();
        });
    });

    // Mettre à jour les numéros des produits
    function updateProductNumbers() {
        $('#pool-products-list .pool-product-item').each(function(index) {
            $(this).find('strong').first().text('Produit #' + (index + 1));
        });
    }
});
