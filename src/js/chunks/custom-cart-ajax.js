(function ($) {

    const AJAX_URL = custom_cart_vars.ajaxurl;

    $(document).ready(function () {

        updateCartDisplay();

        $(document).on('click', '.add-to-cart-button', function (e) {
            e.preventDefault();
            let button = $(this);
            let product_id = button.data('product-id');
            let quantity = button.data('quantity') || 1;

            button.addClass('loading').prop('disabled', true).text('Добавление...');

            addToCart(product_id, quantity, button);
        });

        $(document).on('click', '.remove-from-cart-button', function (e) {
            e.preventDefault();
            let cart_item_key = $(this).data('cart-key');

            removeFromCart(cart_item_key);
        });

        $(document).on('click', '.open-cart-modal', function (e) {
            e.preventDefault();
            showLoadingInModal();
            getCartDetails();
            // Здесь должна быть логика открытия модального окна
            // openModal('#cart-modal');
        });

        $(document).on('click', '.checkout-tbank-button', function (e) {
            e.preventDefault();
            prepareOrderAndRedirect();
        });
    });

    function addToCart(product_id, quantity, button) {
        $.ajax({
            type: 'POST',
            url: AJAX_URL,
            data: {
                action: 'custom_add_to_cart',
                product_id: product_id,
                quantity: quantity
            },
            success: function (response) {
                if (response.success) {
                    handleCartUpdate(response.data.cart);
                    button.text('Добавлено!').removeClass('loading').prop('disabled', false);
                    setTimeout(() => button.text('Добавить в корзину'), 1500);
                } else {
                    alert('Не удалось добавить товар: ' + response.data.message);
                }
            },
            error: function () {
                alert('Произошла ошибка AJAX.');
            }
        });
    }

    function removeFromCart(cart_item_key) {
        $.ajax({
            type: 'POST',
            url: AJAX_URL,
            data: {
                action: 'custom_remove_from_cart',
                cart_item_key: cart_item_key
            },
            success: function (response) {
                if (response.success) {
                    handleCartUpdate(response.data.cart);
                    // Обновляем модальное окно после удаления
                    renderCartItems(response.data.cart);
                } else {
                    alert('Не удалось удалить товар: ' + response.data.message);
                }
            },
            error: function () {
                alert('Произошла ошибка AJAX.');
            }
        });
    }

    function getCartDetails() {
        $.ajax({
            type: 'POST',
            url: AJAX_URL,
            data: {
                action: 'custom_get_cart'
            },
            success: function (response) {
                if (response.success) {
                    handleCartUpdate(response.data.cart);
                    renderCartItems(response.data.cart);
                } else {
                    // Обработка ошибки
                }
            }
        });
    }

    function prepareOrderAndRedirect() {
        // Устанавливаем статус загрузки на кнопке
        $('.checkout-tbank-button').addClass('loading').prop('disabled', true).text('Подготовка к оплате...');

        $.ajax({
            type: 'POST',
            url: AJAX_URL,
            data: {
                action: 'custom_prepare_order'
            },
            success: function (response) {
                if (response.success && response.data.payment_url) {
                    // 4.1 Если URL оплаты получен, перенаправляем пользователя
                    window.location.href = response.data.payment_url;
                } else if (response.success && response.data.order_data) {
                    // 4.2 Если URL не получен (пока только данные), выводим данные для отладки
                    console.log('Данные для Т-Банка:', response.data.order_data.items_for_tbank);
                    alert('Данные заказа готовы, но API Т-Банка не вызван. Смотрите консоль.');
                    $('.checkout-tbank-button').removeClass('loading').prop('disabled', false).text('Оплатить (отладка)');

                } else {
                    alert('Не удалось подготовить заказ: ' + response.data.message);
                    $('.checkout-tbank-button').removeClass('loading').prop('disabled', false).text('Оплатить');
                }
            },
            error: function () {
                alert('Произошла ошибка при подготовке заказа.');
                $('.checkout-tbank-button').removeClass('loading').prop('disabled', false).text('Оплатить');
            }
        });
    }

    // --- ФУНКЦИИ ОБНОВЛЕНИЯ UI ---

    function handleCartUpdate(cartData) {
        updateCartCounter(cartData.count);
        // Здесь можно вызвать функцию обновления модального окна, если оно открыто
    }

    function updateCartDisplay() {
        getCartDetails();
    }

    function updateCartCounter(count) {
        $('.cart-count-display').text(count);
    }

    function showLoadingInModal() {
        $('#cart-modal-content').html('<div class="loading-spinner">Загрузка корзины...</div>');
    }

    function renderCartItems(cartData) {
        let itemsHtml = '';

        if (cartData.items.length === 0) {
            itemsHtml = '<p>Ваша корзина пуста.</p>';
        } else {
            itemsHtml += '<ul class="cart-items-list">';
            cartData.items.forEach(item => {
                itemsHtml += `
                    <li data-key="${item.key}">
                        <img src="${item.thumbnail_url || 'placeholder.png'}" alt="${item.name}">
                        <div class="item-info">
                            <h4>${item.name}</h4>
                            <p>${item.quantity} x ${item.price_formatted}</p>
                        </div>
                        <button class="remove-from-cart-button" data-cart-key="${item.key}">×</button>
                    </li>
                `;
            });
            itemsHtml += '</ul>';
        }

        itemsHtml += `<div class="cart-total">Итого: ${cartData.total}</div>`;
        itemsHtml += `<button class="checkout-tbank-button">Оплатить через Т-Банк</button>`;

        $('#cart-modal-content').html(itemsHtml);
    }

})(jQuery);