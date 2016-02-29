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

        $gateway = Omnipay::create('PinGateway');
        $gateway->setSecretKey($secretKey);
        $gateway->getDefaultParameters();

        try {
            $response = $gateway->purchase([
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'clientIp' => $_SERVER['REMOTE_ADDR']])->send();
            if ($response->isSuccessful()) {
                // mark order as complete
                $sale_id = $response->getTransactionReference();
                $this->grav->fireEvent('onShoppingCartSaveOrder', new Event(['gateway' => $this->name, 'order' => $order, 'reference' => $sale_id]));
                $this->grav->fireEvent('onShoppingCartReturnOrderPageUrlForAjax', new Event(['gateway' => $this->name, 'order' => $order, 'reference' => $sale_id]));
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
