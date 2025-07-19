<?php
/**
 * MagoArab_EasYorder Order Create Controller
 *
 * @category    MagoArab
 * @package     MagoArab_EasYorder
 * @author      MagoArab Development Team
 * @copyright   Copyright (c) 2025 MagoArab
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace MagoArab\EasYorder\Controller\Order;

use MagoArab\EasYorder\Api\QuickOrderServiceInterface;
use MagoArab\EasYorder\Api\Data\QuickOrderDataInterfaceFactory;
use MagoArab\EasYorder\Helper\Data as HelperData;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Class Create
 * 
 * Controller for creating quick orders
 */
class Create implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var QuickOrderServiceInterface
     */
    private $quickOrderService;

    /**
     * @var QuickOrderDataInterfaceFactory
     */
    private $quickOrderDataFactory;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        QuickOrderServiceInterface $quickOrderService,
        QuickOrderDataInterfaceFactory $quickOrderDataFactory,
        HelperData $helperData,
        Validator $formKeyValidator,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->quickOrderService = $quickOrderService;
        $this->quickOrderDataFactory = $quickOrderDataFactory;
        $this->helperData = $helperData;
        $this->formKeyValidator = $formKeyValidator;
        $this->logger = $logger;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            // Check if module is enabled
            if (!$this->helperData->isEnabled()) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Quick order is not enabled.')
                ]);
            }

            // Validate form key
            if (!$this->formKeyValidator->validate($this->request)) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Invalid form key.')
                ]);
            }

            // Validate required fields
            $validation = $this->validateRequest();
            if (!$validation['valid']) {
                return $result->setData([
                    'success' => false,
                    'message' => $validation['message']
                ]);
            }

            // Create order data object
            $orderData = $this->quickOrderDataFactory->create();
            $orderData->setProductId((int)$this->request->getParam('product_id'));
            $orderData->setQty((int)$this->request->getParam('qty', 1));
            $orderData->setCustomerName(trim($this->request->getParam('customer_name')));
            $orderData->setCustomerPhone(trim($this->request->getParam('customer_phone')));
            $orderData->setCustomerEmail(trim($this->request->getParam('customer_email')));
            
            // Handle street address (array)
            $street = $this->request->getParam('street', []);
            if (is_array($street)) {
                $address = implode(', ', array_filter($street));
            } else {
                $address = trim($street);
            }
            $orderData->setAddress($address);
            
            $orderData->setCity(trim($this->request->getParam('city')));
            $orderData->setCountryId(trim($this->request->getParam('country_id')));
            
            // Handle region data
            $regionId = $this->request->getParam('region_id');
            $regionText = $this->request->getParam('region');
            if ($regionId) {
                $orderData->setRegion($regionText ?: $regionId);
            } else {
                $orderData->setRegion($regionText);
            }
            
            $orderData->setPostcode(trim($this->request->getParam('postcode')));
            $orderData->setShippingMethod(trim($this->request->getParam('shipping_method')));
            $orderData->setPaymentMethod(trim($this->request->getParam('payment_method')));

            // Create order
            $orderResult = $this->quickOrderService->createQuickOrder($orderData);

            return $result->setData($orderResult);

        } catch (LocalizedException $e) {
            $this->logger->error('Quick order creation failed: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in quick order creation: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => __('An unexpected error occurred. Please try again.')
            ]);
        }
    }

    /**
     * Validate request parameters
     *
     * @return array
     */
    private function validateRequest(): array
    {
        $requiredFields = [
            'product_id' => __('Product ID'),
            'customer_name' => __('Customer Name'),
            'customer_phone' => __('Customer Phone'),
            'city' => __('City'),
            'country_id' => __('Country'),
            'shipping_method' => __('Shipping Method'),
            'payment_method' => __('Payment Method')
        ];

        foreach ($requiredFields as $field => $label) {
            $value = $this->request->getParam($field);
            if (empty($value)) {
                return [
                    'valid' => false,
                    'message' => __('%1 is required.', $label)
                ];
            }
        }

        // Validate street address
        $street = $this->request->getParam('street', []);
        if (is_array($street)) {
            $streetLine1 = trim($street[0] ?? '');
        } else {
            $streetLine1 = trim($street);
        }
        
        if (empty($streetLine1)) {
            return [
                'valid' => false,
                'message' => __('Street address is required.')
            ];
        }

        // Validate product ID
        $productId = $this->request->getParam('product_id');
        if (!is_numeric($productId) || $productId <= 0) {
            return [
                'valid' => false,
                'message' => __('Invalid product ID.')
            ];
        }

        // Validate quantity
        $qty = $this->request->getParam('qty', 1);
        if (!is_numeric($qty) || $qty <= 0) {
            return [
                'valid' => false,
                'message' => __('Invalid quantity.')
            ];
        }

        // Validate phone number
        $phone = trim($this->request->getParam('customer_phone'));
        if (strlen($phone) < 8) {
            return [
                'valid' => false,
                'message' => __('Phone number must be at least 8 digits.')
            ];
        }

        // Validate email if provided
        $email = trim($this->request->getParam('customer_email'));
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'message' => __('Invalid email address.')
            ];
        }

        // Validate region (either region_id or region text should be provided)
        $regionId = $this->request->getParam('region_id');
        $regionText = $this->request->getParam('region');
        if (empty($regionId) && empty($regionText)) {
            return [
                'valid' => false,
                'message' => __('Region is required.')
            ];
        }

        return ['valid' => true];
    }
}