<?php
/**
 * Bolt magento2 plugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Bolt
 * @package    Bolt_Boltpay
 * @copyright  Copyright (c) 2017-2023 Bolt Financial, Inc (https://www.bolt.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Bolt\Boltpay\Model\Api;

use Bolt\Boltpay\Api\DiscountCodeValidationInterface;
use Bolt\Boltpay\Helper\Cart as CartHelper;
use Bolt\Boltpay\Helper\Discount;
use Bolt\Boltpay\Helper\Shared\CurrencyUtils;
use Bolt\Boltpay\Model\ErrorResponse as BoltErrorResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception as WebApiException;
use Bolt\Boltpay\Exception\BoltException;

/**
 * Discount Code Validation class
 * @api
 */
class DiscountCodeValidation extends UpdateCartCommon implements DiscountCodeValidationInterface
{
    use UpdateDiscountTrait {
        __construct as private UpdateDiscountTraitConstructor;
    }

    /**
     * @var array
     */
    private $requestArray;

    /**
     * DiscountCodeValidation constructor.
     *
     * @param UpdateCartContext          $updateCartContext
     */
    public function __construct(
        UpdateCartContext $updateCartContext
    ) {
        parent::__construct($updateCartContext);
        $this->UpdateDiscountTraitConstructor($updateCartContext);
    }

    /**
     * @api
     * @return bool
     * @throws \Exception
     */
    public function validate()
    {
        $this->logHelper->addInfoLog('[-= Validate discount request =-]');
        $this->logHelper->addInfoLog(file_get_contents('php://input'));
        try {
            list($result, $immutableQuote) = $this->handleRequest();
            $this->sendSuccessResponse($result, $immutableQuote);
        } catch (BoltException $e) {
            $this->sendErrorResponse(
                $e->getCode(),
                $e->getMessage(),
                422,
                $e->getQuote()
            );
            return false;
        } catch (WebApiException $e) {
            $this->sendErrorResponse(
                BoltErrorResponse::ERR_SERVICE,
                $e->getMessage(),
                $e->getHttpCode(),
                (isset($immutableQuote)) ? $immutableQuote : null
            );

            return false;
        } catch (LocalizedException $e) {
            $this->sendErrorResponse(
                BoltErrorResponse::ERR_SERVICE,
                $e->getMessage(),
                500
            );

            return false;
        } catch (\Exception $e) {
            $this->sendErrorResponse(
                BoltErrorResponse::ERR_SERVICE,
                $e->getMessage(),
                500
            );

            return false;
        }

        return true;
    }

    /**
     * @return array
     * @throws BoltException
     */
    private function handleRequest()
    {
        $request = $this->getRequestContent();

        $requestArray = json_decode(json_encode($request), true);

        // V2 webhooks send requests as {"type": ... "data":{requestContent}} so we need to extract the data we want
        if (isset($requestArray['data'])) {
            $requestArray = $requestArray['data'];
        }

        if (isset($requestArray['cart']['currency']) && isset($requestArray['cart']['currency']['currency'])) {
            $this->cartHelper->setCurrentCurrencyCode($requestArray['cart']['currency']['currency']);
        }

        if (isset($requestArray['cart']['order_reference'])) {
            $parentQuoteId = $requestArray['cart']['order_reference'];
            $immutableQuoteId = $this->cartHelper->getImmutableQuoteIdFromBoltCartArray($requestArray['cart']);
            if (!$immutableQuoteId) {
                $immutableQuoteId = $parentQuoteId;
            }
        } else {
            throw new BoltException(
                __('The cart.order_reference is not set or empty.'),
                null,
                BoltErrorResponse::ERR_INSUFFICIENT_INFORMATION
            );
        }

        // Get the coupon code
        $discount_code = $requestArray['discount_code'] ?? $requestArray['cart']['discount_code'] ?? null;
        $couponCode = trim($discount_code);

        // Check if empty coupon was sent
        if ($couponCode === '') {
            throw new BoltException(
                __('No coupon code provided'),
                null,
                BoltErrorResponse::ERR_CODE_INVALID
            );
        }

        $this->requestArray = $requestArray;

        $result = $this->validateQuote($immutableQuoteId);

        list($parentQuote, $immutableQuote) = $result;

        $storeId = $parentQuote->getStoreId();

        $this->preProcessWebhook($storeId);

        $parentQuote->getStore()->setCurrentCurrencyCode($parentQuote->getQuoteCurrencyCode());

        $this->updateSession($parentQuote, $requestArray['cart']['metadata'] ?? []);

        // Set the shipment if request payload has that info.
        if (!empty($requestArray['cart']['shipments'][0]['reference'])) {
            $this->setShipment($requestArray['cart']['shipments'][0], $immutableQuote);
            $this->setShippingAssignments($immutableQuote);
            if (!$immutableQuote->isVirtual() && $this->cartHelper->checkIfQuoteHasCartFixedAmountAndApplyToShippingRule($immutableQuote)) {
                    // If a customer applies a cart rule (fixed amount for whole cart and apply to shipping) via the cart page,
                    // the function Magento\SalesRule\Helper\CartFixedDiscount::calculateShippingAmountWhenAppliedToShipping does not return correct value for tax calculation,
                    // it is because the $address->getShippingAmount() still returns shipping amount of last selected shipping method.
                    // So we need to correct the shipping amount.
                    $shippingCost = CurrencyUtils::toMajor($requestArray['cart']['shipments'][0]['cost']['amount'], $immutableQuote->getQuoteCurrencyCode());
                    $immutableQuote->getShippingAddress()->setShippingAmount($shippingCost);
            }
        }
        // Verify if the code is coupon or gift card and return proper object
        $result = $this->verifyCouponCode($couponCode, $parentQuote);

        list($coupon, $giftCard) = $result;

        $this->eventsForThirdPartyModules->dispatchEvent("beforeApplyDiscount", $parentQuote);

        if ($coupon && ($coupon->getCouponId() || $this->eventsForThirdPartyModules->runFilter("isValidCouponObj", false, $coupon, $couponCode))) {
            if ($this->shouldUseParentQuoteShippingAddressDiscount($couponCode, $immutableQuote, $parentQuote)) {
                $result = $this->getParentQuoteDiscountResult($couponCode, $coupon, $parentQuote);
            } else {
                $result = $this->applyingCouponCode($couponCode, $coupon, $immutableQuote, $parentQuote);
            }
        } elseif ($giftCard && $giftCard->getId()) {
            $result = $this->applyingGiftCardCode($couponCode, $giftCard, $immutableQuote, $parentQuote);
        } else {
            throw new WebApiException(__('Something happened with current code.'));
        }

        // we shouldn't be able to get inside this if statement. Anything resulting in it
        // evaluating to true would have thrown an exception already
        if (!$result || (isset($result['status']) && $result['status'] === 'error')) {
            // Already sent a response with error, so just return.
            return false;
        }

        //remove previously cached Bolt order since we altered related immutable quote by applying a discount
        $this->cache->clean([CartHelper::BOLT_ORDER_TAG . '_' . $parentQuoteId]);

        return [$result, $immutableQuote];
    }

    /**
     * @param $code
     * @param $giftCard
     * @param Quote $immutableQuote
     * @param Quote $parentQuote
     * @return array
     * @throws \Exception
     */
    private function applyingGiftCardCode($code, $giftCard, $immutableQuote, $parentQuote)
    {
        $result = [];
        try {
            if ($giftCard instanceof \Unirgy\Giftcert\Model\Cert) {
                if (empty($immutableQuote->getData($giftCard::GIFTCERT_CODE))) {
                    $this->discountHelper->addUnirgyGiftCertToQuote($immutableQuote, $giftCard);
                }

                if (empty($parentQuote->getData($giftCard::GIFTCERT_CODE))) {
                    $this->discountHelper->addUnirgyGiftCertToQuote($parentQuote, $giftCard);
                }

                // The Unirgy_GiftCert require double call the function addCertificate().
                // Look on Unirgy/Giftcert/Controller/Checkout/Add::execute()
                $this->discountHelper->addUnirgyGiftCertToQuote($this->checkoutSession->getQuote(), $giftCard);

                $giftAmount = $giftCard->getBalance();
            } else {
                // TODO: move all cases above into filter
                $result = $this->eventsForThirdPartyModules->runFilter(
                    "applyGiftcard",
                    null,
                    $code,
                    $giftCard,
                    $immutableQuote,
                    $parentQuote
                );
                if (empty($result)) {
                    throw new \Exception('Unknown giftCard class');
                }
                if ($result['status']=='failure') {
                    throw new \Exception($result['error_message']);
                }
            }
        } catch (\Exception $e) {
            throw new BoltException(
                $e->getMessage(),
                null,
                BoltErrorResponse::ERR_SERVICE
            );
        }

        if (!$result) {
            $result = [
                'status'            => 'success',
                'discount_code'     => $code,
                'discount_amount'   => abs(CurrencyUtils::toMinor($giftAmount, $immutableQuote->getQuoteCurrencyCode())),
                'description'       =>  __('Gift Card'),
                'discount_type'     => Discount::BOLT_DISCOUNT_TYPE_FIXED,
                'discount_category' => Discount::BOLT_DISCOUNT_CATEGORY_GIFTCARD,
            ];
        }

        $this->logHelper->addInfoLog('### Gift Card Result');
        $this->logHelper->addInfoLog(json_encode($result));

        return $result;
    }

    /**
     * @param Quote $quote
     * @return array
     * @throws \Exception
     */
    private function getCartTotals($quote)
    {
        $is_has_shipment = !empty($this->requestArray['cart']['shipments'][0]['reference']);
        $payload = $this->createPayloadForVirtualQuote($quote, $this->requestArray);
        // if a quote is virtual and the payload has shipment it doesn't mean it is the payment only checkout
        // virtual quote can have shipment if the quote was physical and then become to virtual in case with addons flow for example
        if ($quote->getIsVirtual()) {
            $is_has_shipment = false;
        }
        $cart = $this->cartHelper->getCartData($is_has_shipment, $payload, $quote);
        if (empty($cart)) {
            throw new \Exception('Something went wrong when getting cart data.');
        }
        return [
            'total_amount' => $cart['total_amount'],
            'tax_amount'   => $cart['tax_amount'],
            'discounts'    => $cart['discounts'],
        ];
    }

    /**
     * @param int        $errCode
     * @param string     $message
     * @param int        $httpStatusCode
     * @param null|Quote $quote
     *
     * @return void
     * @throws \Exception
     */
    protected function sendErrorResponse($errCode, $message, $httpStatusCode, $quote = null)
    {
        $additionalErrorResponseData = [];
        if ($quote) {
            $additionalErrorResponseData['cart'] = $this->getCartTotals($quote);
        }

        $encodeErrorResult = $this->errorResponse
            ->prepareErrorMessage($errCode, $message, $additionalErrorResponseData);

        $this->logHelper->addInfoLog('### sendErrorResponse');
        $this->logHelper->addInfoLog($encodeErrorResult);

        $this->bugsnag->notifyException(new \Exception($message));

        $this->response->setHttpResponseCode($httpStatusCode);
        $this->response->setBody($encodeErrorResult);
        $this->response->sendResponse();
    }

    /**
     * @param array $result
     * @param Quote $quote
     * @return array
     * @throws \Exception
     */
    protected function sendSuccessResponse($result, $quote = null)
    {
        $result['cart'] = $this->getCartTotals($quote);

        $this->response->setBody(json_encode($result));
        $this->response->sendResponse();

        $this->logHelper->addInfoLog('### sendSuccessResponse');
        $this->logHelper->addInfoLog(json_encode($result));
        $this->logHelper->addInfoLog('=== END ===');

        return $result;
    }



    /**
     * @param string $couponCode
     * @param Quote  $immutableQuote
     * @param Quote  $parentQuote
     *
     * @return bool
     */
    protected function shouldUseParentQuoteShippingAddressDiscount(
        $couponCode,
        \Magento\Quote\Model\Quote $immutableQuote,
        \Magento\Quote\Model\Quote $parentQuote
    ) {
        $ignoredShippingAddressCoupons = $this->configHelper->getIgnoredShippingAddressCoupons(
            $parentQuote->getStoreId()
        );

        return $immutableQuote->getCouponCode() == $couponCode &&
               $immutableQuote->getCouponCode() == $parentQuote->getCouponCode() &&
               in_array($couponCode, $ignoredShippingAddressCoupons);
    }

    /**
     * @param string $couponCode
     * @param Quote  $parentQuote
     * @param Coupon $coupon
     *
     * @return array|false
     * @throws \Exception
     */
    protected function getParentQuoteDiscountResult($couponCode, $coupon, $parentQuote)
    {
        try {
            // Load the coupon discount rule
            $rule = $this->ruleRepository->getById($coupon->getRuleId());
        } catch (NoSuchEntityException $e) {
            throw new BoltException(
                __('The coupon code %s was not found', $couponCode),
                null,
                BoltErrorResponse::ERR_CODE_INVALID
            );
        }

        $address = $parentQuote->isVirtual() ? $parentQuote->getBillingAddress() : $parentQuote->getShippingAddress();
        $description = $address->getDiscountDescription(); // Try coupon description first

        if ($description == '') { // Try store-specific label
            try {
                $discountLabels = $rule->getStoreLabels();
            } catch (\Exception $e) {
                // Ignore "resource not set" Exception
            }
            if (!empty($discountLabels)) {
                $description = $discountLabels[0]->getStoreLabel();
            }
        }

        if ($description == '') { // Try default label
            $description = $rule->getDescription();
        }

        $description = $description !== '' ? $description : 'Discount (' . $couponCode . ')'; // coupon code fallback

        return $result = [
            'status'            => 'success',
            'discount_code'     => $couponCode,
            'discount_amount'   => abs(CurrencyUtils::toMinor($address->getDiscountAmount(), $parentQuote->getQuoteCurrencyCode())),
            'description'       => $description,
            'discount_type'     => $this->discountHelper->convertToBoltDiscountType($couponCode),
            'discount_category' => Discount::BOLT_DISCOUNT_CATEGORY_COUPON,
        ];
    }
}
