<?php

namespace Habz\IPaymuLaravel;

use Habz\IPaymuLaravel\Config;
use Habz\IPaymuLaravel\Exceptions\VANotFound;
use Habz\IPaymuLaravel\Exceptions\ApiKeyNotFound;
use Habz\IPaymuLaravel\Traits\CurlTrait;

class IPaymu
{
    use CurlTrait;
    // use TraitsCurlTrait;

    /**
     * @var , Url redirect after payment page
     */
    protected $returnUrl;

    /**
     * @var , Url Notify when transaction paid
     */
    protected $notifyUrl;

    /**
     * @var , Url Redirect when user cancel the transaction
     */
    protected $cancelUrl;

    /**
     * @var , Cart Object Builder
     */
    protected $carts = [];

    /**
     * @var , Store Buyer information
     */
    protected $buyer;

    /**
     * @var , Store COD information
     */
    protected $cod;

    /**
     * @var , Store Amount information
     */
    protected $amount;

    /**
     * @var , Store Comments information
     */
    protected $comments;

    /**
     * @var , Store Expired information in hours
     */
    protected $expired;

    /**
     * @var , Store Reference ID
     */
    protected $referenceId;

    /**
     * @var , Store API Url
     */
    protected $config;

    /**
     * @var , Store Payment Method
     */
    protected $paymentMethod;


    /**
     * @var , Store Payment Channel
     */
    protected $paymentChannel;

    /**
     * iPaymu constructor.
     *
     * @param null  $apiKey
     *
     * @throws ApiKeyNotFound
     */
    public function __construct()
    {
        $this->config = new Config(config('ipaymu.production'));
        $this->notifyUrl = config('ipaymu.notifyUrl');
        $this->returnUrl = config('ipaymu.returnUrl');
        $this->cancelUrl = config('ipaymu.cancelUrl');
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @param string $refId
     */
    public function setReferenceId($refId)
    {
        $this->referenceId = $refId;
    }

    /**
     * @param string $paymentMethod
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @param string $paymentChannel
     */
    public function setPaymentChannel($paymentChannel)
    {
        $this->paymentChannel = $paymentChannel;
    }

    /**
     * @param int $expired
     */
    public function setExpired(int $expired = 24) //24 hours
    {
        $this->expired = $expired;
    }

    /**
     * @param mixed $url
     */
    public function setURL($url)
    {
        $this->returnUrl = $url['returnUrl'];
        $this->cancelUrl = $url['cancelUrl'];
        $this->notifyUrl = $url['notifyUrl'];
    }

    /**
     * @param mixed $buyer
     */
    public function setBuyer($buyer)
    {
        $this->buyer['buyerName'] = $buyer['name'] ?? null;
        $this->buyer['buyerPhone'] = $buyer['phone'] ?? null;
        $this->buyer['buyerEmail'] = $buyer['email'] ?? null;
    }

    public function setCOD($cod)
    {
        $this->cod['pickupArea'] = $cod['pickupArea'] ?? null;
        $this->cod['pickupAddress'] = $cod['pickupAddress'] ?? null;
        $this->cod['deliveryArea'] = $cod['deliveryArea'] ?? null;
        $this->cod['deliveryAddress'] = $cod['deliveryAddress'] ?? null;
    }

    /**
     * @param mixed $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * @param mixed $cart
     */
    public function addCart($cart)
    {
        // dd($this->carts);
        // $this->carts[array_keys($this->carts)][count($this->carts)] = $cart;
        $this->carts[count($this->carts)] = $cart;
        // $this->carts = $cart;
        // dd($this->carts);
    }

    public function add($id, string $product, float $productsPrice, int $productsQty, string $productsDesc = null, $productsWeight = null, $productsLength = null, $productsWidth = null, $productsHeight = null)
    {
        $this->carts[] = [
            'id' => $id,
            'product' => trim($product),
            'price' => trim($productsPrice),
            'quantity' => trim($productsQty),
            'description' => trim($productsDesc),
            'weight' => trim($productsWeight),
            'length' => trim($productsLength),
            'width' => trim($productsWidth),
            'height' => trim($productsHeight)
        ];
    }

    /**
     * @param $id
     */
    public function remove($id)
    {
        foreach ($this->carts as $key => $cart) {
            if (isset($cart['id']) == $id) {
                unset($this->carts[$key]);
            }
        }
    }

    /**
     * @param string $comments
     *
     * @return mixed
     */
    private function buildCarts()
    {
        $productsName = [];
        $productsPrice = [];
        $productsQty = [];
        $productsDesc = [];
        $productsWeight = [];
        $productDimension = [];
        $productsLength   = [];
        $productsWidth    = [];
        $productsHeight   = [];
        foreach ($this->carts as $rcarts) {
            if (!empty($rcarts['product'])) {
                $productsName[] = trim($rcarts['product']);
            }
            if (!empty($rcarts['price'])) {
                $productsPrice[] = trim(floatval($rcarts['price']));
            }

            if (!empty($rcarts['quantity'])) {
                $productsQty[] = trim(intval($rcarts['quantity']));
            }

            if (!empty($rcarts['description'])) {
                $productsDesc[] = trim($rcarts['description']);
            }

            if (!empty($rcarts['weight'])) {
                $productsWeight[] = trim($rcarts['weight']);
            }

            if (!empty($rcarts['length']) && !empty($rcarts['width']) && !empty($rcarts['height'])) {

                $length  = trim($rcarts['length'] ?? 0);
                $width   = trim($rcarts['width'] ?? 0);
                $height  = trim($rcarts['height'] ?? 0);
                $productDimension[] = $length . ':'  . $width . ':' . $height;
            }
        }

        $params['product'] = $productsName ?? null;
        $params['price'] = $productsPrice ?? null;
        $params['quantity'] = $productsQty ?? null;
        $params['description'] = $productsDesc ?? null;
        $params['weight'] = $productsWeight ?? null;
        $params['length']  = $productsLength;
        $params['width']  = $productsWidth;
        $params['height']  = $productsHeight;

        return $params;
    }

    /**
     * List Trx.
     */
    public function historyTransaction($data)
    {
        $response = $this->request(
            $this->config->history,
            $data
        );

        return $response;
    }

    /**
     * Check Balance.
     */
    public function checkBalance()
    {
        $response = $this->request(
            $this->config->balance,
            [
                'account' => config('ipaymu.virtual_account')
            ]
        );

        return $response;
    }

    /**
     * Check Transactions.
     */
    public function checkTransaction($id)
    {
        $response =  $this->request(
            $this->config->transaction,
            [
                'transactionId' => $id
            ]
        );

        return $response;
    }

    /**
     * Checkout Transactions redirect to payment page.
     */
    public function redirectPayment($paymentData = null)
    {
        $currentCarts = $this->buildCarts();

        $response =  $this->request(
            $this->config->redirectpayment,
            [
                'account' => config('ipaymu.virtual_account'),
                'product' => $currentCarts['product'] ?? null,
                'qty' => $currentCarts['quantity'] ?? null,
                'price' => $currentCarts['price'] ?? null,
                'description' => $currentCarts['description'] ?? null,
                'notifyUrl' => $this->notifyUrl,
                'returnUrl' => $this->returnUrl,
                'cancelUrl' => $this->cancelUrl,
                'weight' => $currentCarts['weight'] ?? null,
                'name' => $this->buyer['buyerName'] ?? null,
                'email' => $this->buyer['buyerEmail'] ?? null,
                'phone' => $this->buyer['buyerPhone'] ?? null,
                'pickupArea' => $this->cod['pickupArea'] ?? null,
                'pickupAddress' => $this->cod['pickupAddress'] ?? null,
                'buyerName' => $this->buyer['buyerName'] ?? null,
                'buyerEmail' => $this->buyer['buyerEmail'] ?? null,
                'buyerPhone' => $this->buyer['buyerPhone'] ?? null,
                'referenceId' => $this->referenceId ?? null,
                'expired' => $this->expired ?? 24,
            ]
        );

        return $response;
    }

    /**
     * Checkout Transactions direct api call.
     */
    public function directPayment()
    {
        $currentCarts = $this->buildCarts();
        $total = 0;
        foreach ($currentCarts['price'] as $key => $rcart) {
            $total += $rcart * $currentCarts['quantity'][$key];
        }
        $this->amount =  $total;


        $data = [
            'account' => config('ipaymu.virtual_account'),
            'name' => $this->buyer['buyerName'] ?? null,
            'email' => $this->buyer['buyerEmail'] ?? null,
            'phone' => $this->buyer['buyerPhone'] ?? null,
            'amount' => $this->amount ?? 0,
            'paymentMethod' => $this->paymentMethod ?? null,
            'paymentChannel' => $this->paymentChannel ?? null,
            'comments' => $this->comments ?? null,
            'notifyUrl' => $this->notifyUrl,
            'description' => $currentCarts['description'] ?? null,
            'referenceId' => $this->referenceId ?? null,
            'product' => $currentCarts['product'] ?? null,
            'qty' => $currentCarts['quantity'] ?? null,
            'price' => $currentCarts['price'] ?? null,
            'weight' => $currentCarts['weight'] ?? null,
            'length' => $currentCarts['length'] ?? null,
            'width' => $currentCarts['width'] ?? null,
            'height' => $currentCarts['height'] ?? null,
            'deliveryArea' => $this->cod['deliveryArea'] ?? null,
            'deliveryAddress' => $this->cod['deliveryAddress'] ?? null,
            'pickupArea' => $this->cod['pickupArea'] ?? null,
            'pickupAddress' => $this->cod['pickupAddress'] ?? null,
            'expired' => $this->expired ?? 24,
        ];

        $response =  $this->request(
            $this->config->directpayment,
            $data
        );

        return $response;
    }
}
