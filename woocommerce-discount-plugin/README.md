# Ivo - Automatic Cart Discount (WooCommerce plugin)

Custom WooCommerce plugin that automatically applies a 10% discount as a cart fee once the cart subtotal passes a configurable threshold (default: over 100).

## How it works

Hooks into `woocommerce_cart_calculate_fees` and adds a negative fee line ("10% discount (over 100)") whenever `cart->get_subtotal() > 100`.

## Live example

Tested on a local WooCommerce install - cart with subtotal 240,00 EUR automatically gets a -24,00 EUR discount line, estimated total 216,00 EUR:

<img width="600" alt="Cart with automatic 10% discount applied" src="https://github.com/user-attachments/assets/6bbb3b0d-cffb-48bc-a345-3834d7c1bb82" />
