(function() {

    /***********************************************************/
    /* Handle Proceed to Payment
    /***********************************************************/
    jQuery(function() {
        jQuery(document).on('proceedToPayment', function(event, ShoppingCart) {

            if (ShoppingCart.gateway !== 'pin') {
                return;
            }

            var test = '';
            var key = ShoppingCart.settings.payment.methods.pin.secretKey;
            var tester = ShoppingCart.settings.payment.methods.pin.testMode;
            if (tester == 1) {
                test = ' test';
            }

            var pinApi = new Pin.Api(key, test);


var form = $('form.pin'),
      submitButton = form.find(":submit"),
      errorContainer = form.find('.errors'),
      errorList = errorContainer.find('ul'),
      errorHeading = errorContainer.find('h3');

  // 3. Add a submit handler to the form which calls Pin.js to
  // retrieve a card token, and then add that token to the form and
  // submit the form to your server.
  form.submit(function(e) {
    e.preventDefault();

    // Clear previous errors
    errorList.empty();
    errorHeading.empty();
    errorContainer.hide();

    // Disable the submit button to prevent multiple clicks
    submitButton.attr({disabled: true});

    // Fetch details required for the createToken call to Pin Payments
    var card = {
      number:           $('#cc-number').val(),
      name:             $('#cc-name').val(),
      expiry_month:     $('#cc-expiry-month').val(),
      expiry_year:      $('#cc-expiry-year').val(),
      cvc:              $('#cc-cvc').val(),
      address_line1:    $('#address-line1').val(),
      address_line2:    $('#address-line2').val(),
      address_city:     $('#address-city').val(),
      address_state:    $('#address-state').val(),
      address_postcode: $('#address-postcode').val(),
      address_country:  $('#address-country').val()
    };

    // Request a token for the card from Pin Payments
    pinApi.createCardToken(card).then(handleSuccess, handleError).done();
  });

  function handleSuccess(card) {

    var order = {
                products: storejs.get('grav-shoppingcart-basket-data'),
                address: storejs.get('grav-shoppingcart-person-address'),
                shipping: storejs.get('grav-shoppingcart-shipping-method'),
                payment: storejs.get('grav-shoppingcart-payment-method'),
                token: storejs.get('grav-shoppingcart-order-token').token,
                amount: ShoppingCart.totalOrderPrice.toString(),
                gateway: ShoppingCart.gateway
            };

    // Add the card token to the form
    //
    // Once you have the card token on your server you can use your
    // private key and Charges API to charge the credit card.
    $('<input>')
      .attr({type: 'hidden', name: 'card_token'})
      .val(card.token)
      .appendTo(form);

    // Resubmit the form to the server
    //
    // Only the card_token will be submitted to your server. The
    // browser ignores the original form inputs because they don't
    // have their 'name' attribute set.
    form.get(0).submit();
    ShoppingCart.clearCart();
    window.location = redirectUrl;

  }

  function handleError(response) {
    errorHeading.text(response.error_description);

    if (response.messages) {
      $.each(response.messages, function(index, paramError) {
        $('<li>')
          .text(paramError.param + ": " + paramError.message)
          .appendTo(errorList);
      });
    }

    errorContainer.show();

    // Re-enable the submit button
    submitButton.removeAttr('disabled');
  };

          

    }); 
            });
        });

})();
