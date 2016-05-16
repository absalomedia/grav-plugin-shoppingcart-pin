<?php
namespace Grav\Plugin;

use RocketTheme\Toolbox\Event\Event;
use Omnipay\Omnipay;

/**
 * Class ShoppingCartGatewayPin
 * @package Grav\Plugin\ShoppingCart
 */
class ShoppingCartGatewayPin extends ShoppingCartGateway
{
    protected $name = 'pin';

    /**
     * Handle paying via this gateway
     *
     * @param Event $event
     */
    protected function setupGateway($gateway)
    {
        if (!$this->isCurrentGateway($gateway)) {
            return false;
        }

        $pluginConfig = $this->grav['config']->get('plugins.shoppingcart');
        $gatewayConfig = $pluginConfig['payment']['methods']['pin'];

        $secretKey = $gatewayConfig['secretKey'];
        $test_mode = $pluginConfig['test_mode'];

        $gateway = Omnipay::create('Pin');
        $gateway->setSecretKey($secretKey);

        return $gateway;
    }

    /**
     * @param Event $event
     */
    public function onShoppingCartPreparePayment(Event $event)
    {
        $gatewayName = $event['gateway'];
        if (!$this->isCurrentGateway($gatewayName)) {
            return;
        }

        $pluginConfig = $this->grav['config']->get('plugins.shoppingcart');
        $currency = $pluginConfig['general']['currency'];

        $gateway = $this->setupGateway($gatewayName);

        $baseUrl = $this->grav['base_url_absolute'];
        $returnUrl = $baseUrl . '/shoppingcart/pin/success?';
        $cancelUrl = $baseUrl . '/shoppingcart/pin/cancelled?';

        $order = $this->getOrderFromEvent($event);

        $this->grav['session']->order = $order->toArray();

        $params = [
            'cancelUrl'=> $cancelUrl,
            'returnUrl'=> $returnUrl,
            'amount' =>  $order->amount,
            'currency' => $currency,
            //'description' => 'Test Purchase for 12.99'
        ];

        $response = $gateway->purchase($params)->send();

        echo $response->getRedirectUrl();
        exit();
    }

    /**
     * @param Event $event
     */
    public function onShoppingCartGotBackFromGateway(Event $event)
    {
        $gatewayName = $event['gateway'];
        if (!$this->isCurrentGateway($gatewayName)) {
            return;
        }

        $token = $_GET['token'];
        $payer_id = $_GET['PayerID'];
        $order = $this->grav['session']->order;

        $this->grav->fireEvent('onShoppingCartPay', new Event([ 'gateway' => $this->name,
                                                                'payer_id' => $payer_id,
                                                                'token' => $token,
                                                                'order' => $order]));
    }

    /**
{
    protected $name = 'pin';

    /**
     * Handle paying via this gateway
     */
    public function onShoppingCartPay(Event $event)
    {
        $gatewayName = $event['gateway'];
        if (!$this->isCurrentGateway($gatewayName)) {
            return;
        }

        $order = $this->getOrderFromEvent($event);
        $gateway = $this->setupGateway($gatewayName);

        $pluginConfig = $this->grav['config']->get('plugins.shoppingcart');
        $currency = $pluginConfig['general']['currency'];
        description = $this->grav['config']->get('plugins.shoppingcart.payment.methods.pin.description');

        $token = $order->extra['stripeToken'];
        $secretKey = $this->grav['config']->get('plugins.shoppingcart.payment.methods.pin.secretKey');



        try {
            $response = $gateway->purchase([
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'token' => $token])->send();

            if ($response->isSuccessful()) {
                // mark order as complete
                $this->grav->fireEvent('onShoppingCartSaveOrder', new Event(['gateway' => $this->name, 'order' => $order]));
                $this->grav->fireEvent('onShoppingCartReturnOrderPageUrlForAjax', new Event(['gateway' => $this->name, 'order' => $order]));
            } elseif ($response->isRedirect()) {
                $response->redirect();
            } else {
                // display error to customer
                throw new \RuntimeException("Payment not successful: " . $response->getMessage());
            }
        } catch (\Exception $e) {
            // internal error, log exception and display a generic message to the customer
            throw new \RuntimeException('Sorry, there was an error processing your payment: ' . $e->getMessage());
        }
    }
}
