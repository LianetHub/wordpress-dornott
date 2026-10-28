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
            <?php wp_nonce_field('dornott_cart', '_ajax_nonce', false); ?>
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
                            <label class="order__field form__field order__field--large">
                                <textarea name="message" class="form__control" placeholder="Комментарий к заказу"></textarea>
                            </label>
                        </div>
                    </div>
                    <div class="order__step">
                        <div class="order__caption">Доставка:</div>
                        <div class="cdek-pickup" data-cdek-pickup>
                            <input type="radio" name="delivery" value="cdek" data-price="0" checked class="hidden" hidden>
                            <input type="hidden" name="cdek_pvz_code" value="">
                            <input type="hidden" name="city" value="">
                            <input type="hidden" name="address" value="">
                            <div class="cdek-pickup__card">
                                <div class="cdek-pickup__header">
                                    <img class="cdek-pickup__logo" src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/cdek.svg'); ?>" alt="" width="40" height="40">
                                    <div class="cdek-pickup__heading">
                                        <div class="cdek-pickup__title" data-cdek-title>Доставка СДЭК</div>
                                        <p class="cdek-pickup__subtitle" data-cdek-subtitle>В пункт выдачи заказов</p>
                                    </div>
                                    <span class="cdek-pickup__badge" data-cdek-badge>Бесплатно</span>
                                </div>
                                <div class="cdek-pickup__empty" data-cdek-select>
                                    <p>Выберите удобный пункт выдачи</p>
                                    <button type="button" class="cdek-pickup__button" data-cdek-open aria-controls="cdek-pickup-dialog" aria-haspopup="dialog" aria-expanded="false">
                                        Выбрать пункт выдачи
                                    </button>
                                </div>
                                <div class="cdek-pickup__selection" data-cdek-selection aria-live="polite" hidden>
                                    <div class="cdek-pickup__location">
                                        <svg class="cdek-pickup__pin" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <div class="cdek-pickup__details">
                                            <p class="cdek-pickup__address" data-cdek-address></p>
                                            <p class="cdek-pickup__meta" data-cdek-hours hidden></p>
                                        </div>
                                    </div>
                                    <div class="cdek-pickup__actions">
                                        <button type="button" class="cdek-pickup__button" data-cdek-open data-cdek-edit aria-controls="cdek-pickup-dialog" aria-haspopup="dialog" aria-expanded="false">
                                            Изменить пункт
                                        </button>
                                    </div>
                                </div>
                            </div>
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
                    <?php dornott_smartcaptcha_html(); ?>
                    <div class="cart__checkout" data-cart-checkout>
                        <button type="submit" id="checkout-button" class="btn btn-primary" aria-describedby="cart-validation-warning" disabled>оплатить заказ</button>
                    </div>
                </div>
                <div id="cart-validation-warning" class="cart__warning hidden" role="alert">
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
<div class="cdek-dialog" id="cdek-pickup-dialog" aria-labelledby="cdek-dialog-title" style="display: none;">
    <div class="cdek-dialog__header">
        <h2 class="cdek-dialog__title" id="cdek-dialog-title">Выберите пункт выдачи</h2>
        <p class="cdek-dialog__subtitle">СДЭК · Доставка бесплатно</p>
        <button type="button" class="cdek-dialog__close" data-cdek-close aria-label="Закрыть выбор пункта выдачи">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="m6 6 12 12M18 6 6 18"/></svg>
        </button>
    </div>
    <div class="cdek-dialog__map" id="cdek-pvz-map"></div>
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
