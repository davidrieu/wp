jQuery(document).ready(function($) {
    'use strict';

    // État de la configuration
    const configuratorState = {
        currentStep: 1,
        totalSteps: 3,
        selectedSize: null,
        selectedOptions: [],
        poolData: null
    };

    // Initialisation
    init();

    function init() {
        loadPoolData();
        bindEvents();
    }

    function bindEvents() {
        $('#next-step').on('click', nextStep);
        $('#prev-step').on('click', prevStep);
        $('#submit-configuration').on('click', submitConfiguration);

        // Délégation d'événements pour les cartes
        $(document).on('click', '.pool-size-card', function() {
            selectSize($(this).data('id'));
        });

        $(document).on('click', '.pool-option-card', function() {
            toggleOption($(this).data('id'));
        });
    }

    function loadPoolData() {
        $.ajax({
            url: poolConfig.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_pool_data',
                nonce: poolConfig.nonce
            },
            success: function(response) {
                if (response.success) {
                    configuratorState.poolData = response.data;
                    renderPoolSizes(response.data.sizes);
                    renderPoolOptions(response.data.options);
                }
            },
            error: function() {
                showError('Erreur lors du chargement des données');
            }
        });
    }

    function renderPoolSizes(sizes) {
        const container = $('#pool-sizes-container');
        container.empty();

        if (!sizes || sizes.length === 0) {
            container.html('<p style="text-align: center; color: #718096;">Aucune taille de piscine disponible pour le moment.</p>');
            return;
        }

        sizes.forEach(function(size) {
            const card = createSizeCard(size);
            container.append(card);
        });
    }

    function createSizeCard(size) {
        let thumbnailHtml = '';
        if (size.thumbnail) {
            thumbnailHtml = `<img src="${size.thumbnail}" alt="${size.title}" class="card-thumbnail">`;
        }

        let productsHtml = '';
        if (size.products && size.products.length > 0) {
            productsHtml = '<div class="card-products-list"><h4>Produits inclus</h4><ul>';
            size.products.forEach(function(product) {
                productsHtml += `<li>${product.name} ${product.price > 0 ? '(+' + formatPrice(product.price) + ')' : ''}</li>`;
            });
            productsHtml += '</ul></div>';
        }

        return $(`
            <div class="pool-size-card" data-id="${size.id}" data-price="${size.total_price}">
                ${thumbnailHtml}
                <h3 class="card-title">${size.title}</h3>
                ${size.dimensions ? `<p class="card-dimensions">📏 ${size.dimensions}</p>` : ''}
                ${size.description ? `<p class="card-description">${size.description}</p>` : ''}
                ${productsHtml}
                <div class="card-price-label">À partir de</div>
                <p class="card-price">${formatPrice(size.total_price)}</p>
            </div>
        `);
    }

    function renderPoolOptions(options) {
        const container = $('#pool-options-container');
        container.empty();

        if (!options || options.length === 0) {
            container.html('<p style="text-align: center; color: #718096;">Aucune option disponible. Vous pouvez passer à l\'étape suivante.</p>');
            return;
        }

        options.forEach(function(option) {
            const card = createOptionCard(option);
            container.append(card);
        });
    }

    function createOptionCard(option) {
        let iconHtml = '';
        if (option.icon) {
            // Vérifier si c'est une classe Font Awesome ou un emoji
            if (option.icon.startsWith('fa')) {
                iconHtml = `<i class="${option.icon} option-icon"></i>`;
            } else {
                iconHtml = `<div class="option-icon">${option.icon}</div>`;
            }
        }

        let thumbnailHtml = '';
        if (option.thumbnail) {
            thumbnailHtml = `<img src="${option.thumbnail}" alt="${option.title}" class="card-thumbnail">`;
        }

        return $(`
            <div class="pool-option-card" data-id="${option.id}" data-price="${option.price}">
                ${thumbnailHtml}
                <div class="option-header">
                    ${iconHtml ? `<div class="option-icon-small">${option.icon}</div>` : ''}
                    <div class="option-info">
                        <h3 class="card-title">${option.title}</h3>
                    </div>
                </div>
                ${option.description ? `<p class="card-description">${option.description}</p>` : ''}
                <div class="card-price-label">Prix</div>
                <p class="card-price">${formatPrice(option.price)}</p>
            </div>
        `);
    }

    function selectSize(sizeId) {
        configuratorState.selectedSize = sizeId;

        $('.pool-size-card').removeClass('selected');
        $(`.pool-size-card[data-id="${sizeId}"]`).addClass('selected');

        updatePrice();

        // Animation du bouton suivant
        $('#next-step').prop('disabled', false).addClass('pulse');
        setTimeout(function() {
            $('#next-step').removeClass('pulse');
        }, 600);
    }

    function toggleOption(optionId) {
        const index = configuratorState.selectedOptions.indexOf(optionId);

        if (index > -1) {
            configuratorState.selectedOptions.splice(index, 1);
            $(`.pool-option-card[data-id="${optionId}"]`).removeClass('selected');
        } else {
            configuratorState.selectedOptions.push(optionId);
            $(`.pool-option-card[data-id="${optionId}"]`).addClass('selected');
        }

        updatePrice();
    }

    function updatePrice() {
        let totalPrice = 0;

        // Prix de la taille sélectionnée
        if (configuratorState.selectedSize) {
            const sizeCard = $(`.pool-size-card[data-id="${configuratorState.selectedSize}"]`);
            totalPrice += parseFloat(sizeCard.data('price')) || 0;
        }

        // Prix des options sélectionnées
        configuratorState.selectedOptions.forEach(function(optionId) {
            const optionCard = $(`.pool-option-card[data-id="${optionId}"]`);
            totalPrice += parseFloat(optionCard.data('price')) || 0;
        });

        // Animer le changement de prix
        animatePrice(totalPrice);
    }

    function animatePrice(newPrice) {
        const priceElement = $('#total-price');
        const currentPrice = parseFloat(priceElement.text().replace(/\s/g, '')) || 0;

        // Animation du compteur
        $({value: currentPrice}).animate({value: newPrice}, {
            duration: 600,
            easing: 'swing',
            step: function() {
                priceElement.text(formatPriceNumber(this.value));
            },
            complete: function() {
                priceElement.text(formatPriceNumber(newPrice));
            }
        });

        // Animation de pulsation
        priceElement.parent().addClass('pulse');
        setTimeout(function() {
            priceElement.parent().removeClass('pulse');
        }, 600);
    }

    function nextStep() {
        // Validation
        if (configuratorState.currentStep === 1 && !configuratorState.selectedSize) {
            showError('Veuillez sélectionner une taille de piscine');
            return;
        }

        if (configuratorState.currentStep < configuratorState.totalSteps) {
            configuratorState.currentStep++;
            updateStepDisplay();

            // Si on arrive à l'étape 3, générer le récapitulatif
            if (configuratorState.currentStep === 3) {
                renderSummary();
            }
        }
    }

    function prevStep() {
        if (configuratorState.currentStep > 1) {
            configuratorState.currentStep--;
            updateStepDisplay();
        }
    }

    function updateStepDisplay() {
        // Masquer toutes les étapes
        $('.pool-step').removeClass('active');

        // Afficher l'étape actuelle
        setTimeout(function() {
            $(`.pool-step[data-step="${configuratorState.currentStep}"]`).addClass('active');
        }, 100);

        // Mettre à jour la barre de progression
        const progressPercent = (configuratorState.currentStep / configuratorState.totalSteps) * 100;
        $('.progress-fill').css('width', progressPercent + '%');
        $('.current-step').text(configuratorState.currentStep);

        // Gérer les boutons de navigation
        if (configuratorState.currentStep === 1) {
            $('#prev-step').hide();
            $('#next-step').show();
            $('#submit-configuration').hide();
        } else if (configuratorState.currentStep === configuratorState.totalSteps) {
            $('#prev-step').show();
            $('#next-step').hide();
            $('#submit-configuration').show();
        } else {
            $('#prev-step').show();
            $('#next-step').show();
            $('#submit-configuration').hide();
        }

        // Scroll vers le haut
        $('html, body').animate({
            scrollTop: $('#pool-configurator').offset().top - 20
        }, 400);
    }

    function renderSummary() {
        const container = $('#pool-summary-container');
        container.empty();

        let html = '<div class="summary-section">';
        html += '<h3>🏊 Votre Piscine</h3>';

        // Taille sélectionnée
        if (configuratorState.selectedSize) {
            const size = configuratorState.poolData.sizes.find(s => s.id === configuratorState.selectedSize);
            if (size) {
                html += `
                    <div class="summary-item">
                        <div class="summary-item-name">
                            <strong>${size.title}</strong>
                            ${size.dimensions ? `<br><small>${size.dimensions}</small>` : ''}
                        </div>
                        <div class="summary-item-price">${formatPrice(size.total_price)}</div>
                    </div>
                `;

                // Afficher les produits inclus
                if (size.products && size.products.length > 0) {
                    html += '<div style="margin-top: 15px; padding-left: 20px; border-left: 3px solid #0ca9c1;">';
                    size.products.forEach(function(product) {
                        html += `
                            <div style="padding: 8px 0; color: #718096;">
                                <span>${product.name}</span>
                                ${product.price > 0 ? `<span style="float: right;">+${formatPrice(product.price)}</span>` : ''}
                            </div>
                        `;
                    });
                    html += '</div>';
                }
            }
        }

        html += '</div>';

        // Options sélectionnées
        if (configuratorState.selectedOptions.length > 0) {
            html += '<div class="summary-section">';
            html += '<h3>✨ Options Sélectionnées</h3>';

            configuratorState.selectedOptions.forEach(function(optionId) {
                const option = configuratorState.poolData.options.find(o => o.id === optionId);
                if (option) {
                    html += `
                        <div class="summary-item">
                            <div class="summary-item-name">
                                ${option.icon ? `<span style="margin-right: 10px;">${option.icon}</span>` : ''}
                                <strong>${option.title}</strong>
                            </div>
                            <div class="summary-item-price">${formatPrice(option.price)}</div>
                        </div>
                    `;
                }
            });

            html += '</div>';
        }

        // Total
        let totalPrice = 0;
        if (configuratorState.selectedSize) {
            const size = configuratorState.poolData.sizes.find(s => s.id === configuratorState.selectedSize);
            if (size) {
                totalPrice += size.total_price;
            }
        }

        configuratorState.selectedOptions.forEach(function(optionId) {
            const option = configuratorState.poolData.options.find(o => o.id === optionId);
            if (option) {
                totalPrice += option.price;
            }
        });

        html += `
            <div class="summary-total">
                <div class="summary-total-label">Prix Total</div>
                <div class="summary-total-price">${formatPrice(totalPrice)}</div>
            </div>
        `;

        container.html(html);
    }

    function submitConfiguration() {
        const form = $('#pool-contact-form');

        // Validation du formulaire
        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }

        // Récupérer les données du formulaire
        const customerData = {
            name: $('#customer-name').val(),
            email: $('#customer-email').val(),
            phone: $('#customer-phone').val(),
            message: $('#customer-message').val()
        };

        // Calculer le prix total
        let totalPrice = 0;
        if (configuratorState.selectedSize) {
            const size = configuratorState.poolData.sizes.find(s => s.id === configuratorState.selectedSize);
            if (size) {
                totalPrice += size.total_price;
            }
        }

        configuratorState.selectedOptions.forEach(function(optionId) {
            const option = configuratorState.poolData.options.find(o => o.id === optionId);
            if (option) {
                totalPrice += option.price;
            }
        });

        const configData = {
            size_id: configuratorState.selectedSize,
            options: configuratorState.selectedOptions,
            total_price: totalPrice
        };

        // Désactiver le bouton pendant l'envoi
        const submitBtn = $('#submit-configuration');
        submitBtn.prop('disabled', true).text('Envoi en cours...');

        // Envoyer la configuration
        $.ajax({
            url: poolConfig.ajaxurl,
            type: 'POST',
            data: {
                action: 'submit_pool_configuration',
                nonce: poolConfig.nonce,
                configuration: JSON.stringify(configData),
                customer: customerData
            },
            success: function(response) {
                if (response.success) {
                    showSuccessModal();
                } else {
                    showError(response.data.message || 'Erreur lors de l\'envoi');
                    submitBtn.prop('disabled', false).text('Envoyer ma demande');
                }
            },
            error: function() {
                showError('Erreur de connexion. Veuillez réessayer.');
                submitBtn.prop('disabled', false).text('Envoyer ma demande');
            }
        });
    }

    function showSuccessModal() {
        $('#pool-success-modal').fadeIn(300);
    }

    function showError(message) {
        alert(message); // On pourrait améliorer ça avec un toast ou une notification plus jolie
    }

    function formatPrice(price) {
        return formatPriceNumber(price) + ' €';
    }

    function formatPriceNumber(price) {
        return Math.round(price).toLocaleString('fr-FR').replace(/\s/g, ' ');
    }

    // Animation de pulsation pour les prix
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        .pulse {
            animation: pulse 0.6s ease-in-out;
        }
    `;
    document.head.appendChild(style);
});
