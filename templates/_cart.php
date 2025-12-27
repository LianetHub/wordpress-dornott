<?php
$address = get_field('pickup_address', 'option') ?? '';
?>
<div class="popup popup--white popup--large cart" id="cart">
    <h2 class="cart__title">Корзина</h2>
    <div id="cart-empty-state" class="cart__empty">
        <p class="cart__empty-content">Ваша корзина пока пуста</p>
        <button type="button" data-fancybox-close data-goto-catalog class="btn btn-primary">Перейти к покупкам</button>
    </div>
    <div class="cart__body" style="display: none;">
        <form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="POST" id="cart-form" class="cart__form">
            <input type="hidden" name="cart_items" value="">
            <input type="hidden" name="order_id" value="">
            <input type="hidden" name="delivery_price" value="">
            <input type="hidden" name="full_address" value="">
            <input type="hidden" name="action" value="send_order_form">
            <div class="cart__main">
                <div class="cart__products">
                    <div class="cart__products-header">
                        <label class="cart__select-all checkbox">
                            <input type="checkbox" name="select_all" class="checkbox__input hidden" hidden>
                            <span class="checkbox__text">
                                Выбрать все
                            </span>
                        </label>
                        <button type="button" class="cart__clear">Удалить</button>
                    </div>
                    <div class="cart__table">
                        <div class="cart__table-caption">
                            <div class="cart__table-block">Товар</div>
                            <div class="cart__table-block">Кол-во</div>
                            <div class="cart__table-block">Цена</div>
                        </div>
                        <div id="cart-items-container" class="cart__table-items"></div>
                    </div>
                </div>
                <div class="cart__order order">
                    <div class="order__step">
                        <div class="order__caption">Получатель:</div>
                        <div class="order__fields">
                            <label class="order__field form__field order__field--large">
                                <input type="text" name="username" data-required class="form__control" placeholder="Ваше имя">
                            </label>
                            <label class="order__field form__field">
                                <input type="tel" name="phone" data-required class="form__control" placeholder="Телефон">
                            </label>
                            <label class="order__field form__field">
                                <input type="email" name="email" data-required class="form__control" placeholder="Email">
                            </label>
                        </div>
                    </div>
                    <div class="order__step">
                        <div class="order__caption">Способ доставки:</div>
                        <div class="order__step-options">
                            <?php
                            $pickup_address = get_field('pickup_address', 'option') ?? '';
                            ?>

                            <label class="order__step-option">
                                <input type="radio" name="delivery" value="pickup" data-price="0" checked class="order__step-input hidden" hidden>
                                <span class="order__card">
                                    <span class="order__card-header">
                                        <span class="order__step-header-title">Самовывоз со склада Dornott</span>
                                    </span>
                                    <span class="order__card-body"><?php echo esc_html($pickup_address); ?></span>
                                    <span class="order__card-info">0 ₽</span>
                                </span>
                            </label>

                            <?php if (have_rows('delivery', 'option')): ?>
                                <?php while (have_rows('delivery', 'option')): the_row();
                                    $name = get_sub_field('company_name');
                                    $logo = get_sub_field('company_logo');
                                    $desc = get_sub_field('company_description');
                                    $price = get_sub_field('company_delivery_price');
                                    $slug = sanitize_title($name);
                                ?>
                                    <label class="order__step-option">
                                        <input type="radio"
                                            name="delivery"
                                            value="<?php echo esc_attr($slug); ?>"
                                            data-price="<?php echo esc_attr($price); ?>"
                                            class="order__step-input hidden"
                                            hidden>
                                        <span class="order__card">
                                            <span class="order__card-header">
                                                <span class="order__step-header-title">Доставка <?php echo esc_html($name); ?></span>
                                                <?php if ($logo): ?>
                                                    <span class="order__step-logo">
                                                        <img src="<?php echo esc_url($logo['url']); ?>" alt="<?php echo esc_attr($logo['alt']); ?>">
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                            <?php if ($desc): ?>
                                                <span class="order__card-body">
                                                    <?php echo wp_kses_post($desc); ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="order__card-info green">
                                                <?php echo $price > 0 ? number_format($price, 0, '.', ' ') . ' ₽' : 'Бесплатно'; ?>
                                            </span>
                                        </span>
                                    </label>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="order__step">
                        <div class="order__caption">Адрес доставки:</div>
                        <div class="order__fields">
                            <label class="order__field form__field order__field--small">
                                <input type="text" name="city" data-required class="form__control" placeholder="Город">
                            </label>
                            <label class="order__field form__field order__field--medium">
                                <input type="text" name="address" data-required class="form__control" placeholder="Введите адрес">
                            </label>
                            <label class="order__field form__field order__field--large">
                                <textarea name="message" class="form__control" placeholder="Напишите комментарий к доставке, например предпочтительную транспортную компанию или особенности..."></textarea>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="cart__side">
                <div class="cart__side-main">
                    <div class="cart__side-header">
                        <div class="cart__caption">Итого</div>
                        <div class="cart__quantity">
                            <div class="cart__text">Товары:</div>
                            <div id="total-qty" class="cart__value">0 шт.</div>
                        </div>
                    </div>
                    <div class="cart__details">
                        <div class="cart__row">
                            <div class="cart__text">Сумма:</div>
                            <div id="subtotal-price" class="cart__value">0 ₽</div>
                        </div>
                        <div class="cart__row">
                            <div class="cart__text">Скидка:</div>
                            <div id="total-discount" class="cart__value">0 ₽</div>
                        </div>
                        <div class="cart__row">
                            <div class="cart__text">Доставка:</div>
                            <div id="delivery-price" class="cart__value">0 ₽</div>
                        </div>
                    </div>
                    <div class="cart__total">
                        <div class="cart__text">Всего к оплате:</div>
                        <div id="final-price" class="cart__total-value">0 ₽</div>
                    </div>

                    <label class="checkbox">
                        <input type="checkbox" checked name="policy" data-required class="checkbox__input hidden" hidden>
                        <span class="checkbox__text">
                            Нажимая на кнопку, вы соглашаетесь на <a href="#data-protection" data-src="#policies" data-fancybox>обработку персональных данных</a>,
                            а также с <a href="#privacy-policy" data-src="#policies" data-fancybox>политикой конфиденциальности</a>
                        </span>
                    </label>
                    <button type="submit" id="checkout-button" class="btn btn-primary">оплатить заказ</button>
                </div>
                <div id="cart-validation-warning" class="cart__warning hidden">
                    <div class="cart__warning-icon">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/alert-triangle.svg" alt="Иконка">
                    </div>
                    <div class="cart__warning-text">
                        Сначала заполните данные получателя и способ доставки, чтобы оформить заказ.
                    </div>
                </div>
            </div>
        </form>
        <div id="payment-container" class="cart__payment-widget" style="display: none;"></div>
    </div>
</div>
<script src="https://integrationjs.tbank.ru/integration.js" onload="onPaymentIntegrationLoad()" async></script>
<script>
    function onPaymentIntegrationLoad() {
        const initConfig = {
            terminalKey: "<?php echo $_ENV['TBANK_TERMINAL_KEY'] ?? '' ?>",
            product: 'eacq',
            features: {
                payment: {},
                iframe: {
                    container: document.getElementById('payment-container')
                }
            }
        };

        PaymentIntegration.init(initConfig)
            .then((integration) => {
                window.tbankSDK = integration;
                console.log('T-Bank SDK Ready');
            })
            .catch((err) => console.error('T-Bank SDK Error:', err));
    }
</script>