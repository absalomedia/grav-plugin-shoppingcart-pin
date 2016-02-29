<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Grav;
use RocketTheme\Toolbox\Event\Event;
use Omnipay\Omnipay;

$path = realpath(__DIR__ . '/../../classes/gateway.php');
require_once($path);

class ShoppingCartGatewayPin extends ShoppingCartGateway
{
    protected $name = 'pin';

    /**
     * Handle paying via this gateway
     */
    public function onShoppingCartPay(Event $event)
    {
        if (!$this->isCurrentGateway($event['gateway'])) {
            return;
        }

        $order = $this->getOrderFromEvent($event);

        $amount = $order->amount;
        $currency = $this->grav['config']->get('plugins.shoppingcart.general.currency');
        $description = $this->grav['config']->get('plugins.shoppingcart.payment.methods.pin.description');

        $secretKey = $this->grav['config']->get('plugins.shoppingcart.payment.methods.pin.secretKey');
        $testMode  = $this->grav['config']->get('plugins.shoppingcart.payment.methods.pin.testMode');

        $gateway = Omnipay::create('PinGateway');

        $gateway->setSecretKey($secretKey);


        try {
            $response = $gateway->purchase([
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'card' => $card,
                'clientIp' => $_SERVER['REMOTE_ADDR']])->send();
            if (!$response->isSuccessful() && !$response->isRedirect()) {
                 // display error to customer
                throw new \RuntimeException("Payment not successful: " . $response->getMessage());
            }
            if ($response->isSuccessful()) {
                // mark order as complete
                $saleId = $response->getTransactionReference();
                $this->grav->fireEvent('onShoppingCartSaveOrder', new Event(['gateway' => $this->name, 'order' => $order, 'reference' => $saleId]));
                $this->grav->fireEvent('onShoppingCartReturnOrderPageUrlForAjax', new Event(['gateway' => $this->name, 'order' => $order, 'reference' => $saleId]));
            } elseif ($response->isRedirect()) {
                $response->redirect();
            }
               
        } catch (\Exception $e) {
            // internal error, log exception and display a generic message to the customer
            throw new \RuntimeException('Sorry, there was an error processing your payment: ' . $e->getMessage());
        }
    }
}
