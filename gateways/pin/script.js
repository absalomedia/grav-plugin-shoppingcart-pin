(function() {

    /***********************************************************/
    /* Handle Proceed to Payment
    /***********************************************************/
    jQuery(function() {
        jQuery(document).on('proceedToPayment', function(event, ShoppingCart) {

            if (ShoppingCart.gateway !== 'pin') {
                return;
            }

            /***********************************************************/
            /* Configure Pin
            /***********************************************************/
            var pinHandler = PinCheckout.configure({
                key: ShoppingCart.settings.payment.methods.pin.publicKey,
                token: function(token, args) {
                    var order = {
                        products: storejs.get('grav-shoppingcart-basket-data'),
                        data: storejs.get('grav-shoppingcart-checkout-form-data'),
                        shipping: storejs.get('grav-shoppingcart-shipping-method'),
                        payment: 'pin',
                        token: storejs.get('grav-shoppingcart-order-token').token,
                        extra: { 'pinToken': token.id },
                        amount: ShoppingCart.totalOrderPrice.toString(),
                        gateway: ShoppingCart.gateway
                    };


                    jQuery.ajax({
                        url: ShoppingCart.settings.baseURL + ShoppingCart.settings.urls.save_order_url + '/task:pay',
                        data: order,
                        type: 'POST'
                    })
                    .success(function(redirectUrl) {
                        ShoppingCart.clearCart();
                        window.location = redirectUrl;
                    })
                    .error(function() {
                        alert('Payment not successful. Please contact us.');
                    });
                }
            });

            stripeHandler.open({
                name: ShoppingCart.settings.payment.methods.pin.name,
                description: ShoppingCart.settings.payment.methods.pin.description,
                email: storejs.get('grav-shoppingcart-checkout-form-data').email,
                amount: ShoppingCart.calculateTotalPriceIncludingTaxesAndShipping().toString().replace('.', ''),
                currency: ShoppingCart.settings.general.currency
            });
        });

    });

})();
