<?php
/**
* Plugin Name: Ivo - Automatic Cart Discount
* Description: Automatically applies a 10% discount when the cart subtotal passes a configurable threshold.
* Version: 1.0
* Author: Ivo Pengezov
*/

if (!defined('ABSPATH')) exit;

add_action('woocommerce_cart_calculate_fees', 'ivo_auto_discount_over_100');

function ivo_auto_discount_over_100($cart) {
if (is_admin() && !defined('DOING_AJAX')) {
return;
}

$subtotal = $cart->get_subtotal();

if ($subtotal > 100) {
$discount = -($subtotal * 0.10);
$cart->add_fee(__('10% discount (over 100)', 'ivo-discount'), $discount);
}
}
