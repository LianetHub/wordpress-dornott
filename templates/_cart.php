<?php
$address = get_field('address', 'option') ?? '';
?>
<div class="popup popup--white popup--large cart" id="cart">
    <h2 class="cart__title">Корзина</h2>
    <div class="cart__body">
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
                    <div class="cart__table-items">
                        <div class="cart__item">
                            <div class="cart__item-block">
                                <label class="cart__item-checkbox checkbox">
                                    <input type="checkbox" name="select_product_id" class="checkbox__input hidden" hidden>
                                    <span class="checkbox__text"></span>
                                </label>
                                <div class="cart__item-thumb">
                                    <img src="" alt="">
                                </div>
                                <div class="cart__item-info">
                                    <div class="cart__item-sku">250300</div>
                                    <div class="cart__item-name">Кашемировый плед</div>
                                </div>
                            </div>
                            <div class="cart__item-block">
                                <div class="cart__item-quantity quantity-block">
                                    <button type="button" class="quantity-block__down icon-minus"></button>
                                    <input type="text" name="product-quantity" maxlength="3" class="quantity-block__input" value="1">
                                    <button type="button" class="quantity-block__up icon-plus"></button>
                                </div>
                            </div>
                            <div class="cart__item-block">
                                <div class="cart__item-price price-block">
                                    <div class="price-block__header">
                                        <div class="price-block__old">
                                            3 844 ₽
                                        </div>
                                        <div class="price-block__sale">
                                            -55%
                                        </div>
                                    </div>
                                    <div class="price-block__current">
                                        1 899 ₽
                                    </div>
                                </div>
                                <button type="button" aria-label="Удалить товар из корзины" class="cart__item-remove icon-cross"></button>
                            </div>
                        </div>
                    </div>
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
                        <label class="order__step-option">
                            <input type="radio" name="delivery" value="" data-required checked class="order__step-input hidden" hidden>
                            <span class="order__card">
                                <span class="order__card-header">
                                    <span class="order__step-header-title">Самовывоз со склада Dornott</span>
                                </span>
                                <span class="order__card-body"><?php echo esc_html($address); ?></span>
                                <span class="order__card-info">0 ₽</span>
                            </span>
                        </label>
                        <label class="order__step-option">
                            <input type="radio" name="delivery" value="" data-required class="order__step-input hidden" hidden>
                            <span class="order__card">
                                <span class="order__card-header">
                                    <span class="order__step-header-title">Доставка ТК</span>
                                    <span class="order__step-logo">
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/cdek.svg" alt="Лого">
                                    </span>
                                </span>
                                <span class="order__card-body">
                                    Мы отправим вам товар обычной доставкой любой транспортной компанией. По умолчанию отправляем СДЭК, если нужна другая, укажите ниже.
                                </span>
                                <span class="order__card-info green">290 ₽</span>
                            </span>
                        </label>
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
                        <div class="cart__value">4 шт.</div>
                    </div>
                </div>
                <div class="cart__details">
                    <div class="cart__row">
                        <div class="cart__text">Сумма:</div>
                        <div class="cart__value">10 783 ₽</div>
                    </div>
                    <div class="cart__row">
                        <div class="cart__text">Скидка:</div>
                        <div class="cart__value">2 762 ₽</div>
                    </div>
                    <div class="cart__row">
                        <div class="cart__text">НДС:</div>
                        <div class="cart__value">0 ₽</div>
                    </div>
                    <div class="cart__row">
                        <div class="cart__text">Кэшбэк:</div>
                        <div class="cart__value">0 ₽</div>
                    </div>
                    <div class="cart__row">
                        <div class="cart__text">Доставка:</div>
                        <div class="cart__value">290 ₽</div>
                    </div>
                </div>
                <div class="cart__total">
                    <div class="cart__text">Всего с учётом доставки:</div>
                    <div class="cart__total-value">8 311 ₽</div>
                </div>
                <label class="checkbox">
                    <input type="checkbox" checked name="policy" class="checkbox__input hidden" hidden>
                    <span class="checkbox__text">
                        Нажимая на кнопку, вы соглашаетесь на <a href="#data-protection" data-src="#policies" data-fancybox>обработку персональных данных</a>,
                        а также с <a href="#privacy-policy" data-src="#policies" data-fancybox>политикой конфиденциальности</a>
                    </span>
                </label>
                <button type="submit" class="btn btn-primary">оплатить заказ</button>
            </div>
            <div class="cart__warning">
                <div class="cart__warning-icon">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/alert-triangle.svg" alt="Иконка">
                </div>
                <div class="cart__warning-text">
                    Сначала заполните данные получателя и способ доставки, чтобы оформить заказ.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        Fancybox.show([{
            src: "#cart"
        }])
    })
</script>