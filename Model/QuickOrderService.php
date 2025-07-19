<?php
/**
 * MagoArab_EasYorder Quick Order Service
 *
 * @category    MagoArab
 * @package     MagoArab_EasYorder
 * @author      MagoArab Development Team
 * @copyright   Copyright (c) 2025 MagoArab
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace MagoArab\EasYorder\Model;

use MagoArab\EasYorder\Api\QuickOrderServiceInterface;
use MagoArab\EasYorder\Api\Data\QuickOrderDataInterface;
use MagoArab\EasYorder\Helper\Data as HelperData;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Shipping\Model\Config as ShippingConfig;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Payment\Api\PaymentMethodListInterface;
use Psr\Log\LoggerInterface;

/**
 * Class QuickOrderService
 * 
 * Service for handling quick order operations
 */
class QuickOrderService implements QuickOrderServiceInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var ShippingConfig
     */
    private $shippingConfig;

    /**
     * @var PaymentConfig
     */
    private $paymentConfig;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShippingMethodManagementInterface
     */
    private $shippingMethodManagement;

    /**
     * @var PaymentMethodListInterface
     */
    private $paymentMethodList;
    
    /**
     * Constructor
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        QuoteFactory $quoteFactory,
        QuoteManagement $quoteManagement,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        OrderSender $orderSender,
        HelperData $helperData,
        ShippingConfig $shippingConfig,
        PaymentConfig $paymentConfig,
        CartRepositoryInterface $cartRepository,
        CartManagementInterface $cartManagement,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ShippingMethodManagementInterface $shippingMethodManagement,
        PaymentMethodListInterface $paymentMethodList
    ) {
        $this->productRepository = $productRepository;
        $this->quoteFactory = $quoteFactory;
        $this->quoteManagement = $quoteManagement;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderSender = $orderSender;
        $this->helperData = $helperData;
        $this->shippingConfig = $shippingConfig;
        $this->paymentConfig = $paymentConfig;
        $this->cartRepository = $cartRepository;
        $this->cartManagement = $cartManagement;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->paymentMethodList = $paymentMethodList;
    }

    /**
     * @inheritDoc
     * جلب طرق الشحن المفعلة فقط من النظام باستخدام الطرق الصحيحة
     */
    public function getAvailableShippingMethods(
        int $productId,
        string $countryId,
        ?string $region = null,
        ?string $postcode = null
    ): array {
        try {
            $store = $this->storeManager->getStore();
            
            // إنشاء quote مؤقت لجلب طرق الشحن الحقيقية
            $quote = $this->quoteFactory->create();
            $quote->setStore($store);
            $quote->setCustomerIsGuest(true);
            
            // إضافة المنتج للـ quote
            $product = $this->productRepository->getById($productId);
            $quote->addProduct($product, 1);
            
            // تعيين عنوان الشحن بالبيانات المطلوبة
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCountryId($countryId)
                ->setCity($region ?: 'Default City')
                ->setStreet(['Default Street'])
                ->setFirstname('Guest')
                ->setLastname('User')
                ->setTelephone('123456789')
                ->setEmail('guest@example.com');
                
            if ($region) {
                $shippingAddress->setRegion($region);
            }
            if ($postcode) {
                $shippingAddress->setPostcode($postcode);
            }
            
            // جمع طرق الشحن المتاحة من النظام
            $shippingAddress->setCollectShippingRates(true);
            $shippingAddress->collectShippingRates();
            
            // الحصول على جميع طرق الشحن المتاحة
            $rates = $shippingAddress->getGroupedAllShippingRates();
            $methods = [];
            
            // تحويل النتائج لصيغة مناسبة
            foreach ($rates as $carrierCode => $carrierRates) {
                foreach ($carrierRates as $rate) {
                    $methods[] = [
                        'carrier_code' => $rate->getCarrier(),
                        'method_code' => $rate->getMethod(),
                        'code' => $rate->getCode(),
                        'title' => $rate->getMethodTitle(),
                        'carrier_title' => $rate->getCarrierTitle(),
                        'price' => (float)$rate->getPrice(),
                        'cost' => (float)$rate->getPrice()
                    ];
                }
            }
            
            $this->logger->info('EasyOrder: Found shipping methods from system', [
                'count' => count($methods),
                'methods' => array_column($methods, 'code'),
                'country' => $countryId,
                'region' => $region,
                'postcode' => $postcode
            ]);
            
            return $methods;
            
        } catch (\Exception $e) {
            $this->logger->error('EasyOrder: Error getting shipping methods: ' . $e->getMessage(), [
                'country' => $countryId,
                'region' => $region,
                'postcode' => $postcode,
                'product_id' => $productId
            ]);
            return []; // إرجاع مصفوفة فارغة في حالة الخطأ
        }
    }

    /**
     * @inheritDoc
     * جلب طرق الدفع المفعلة فقط من النظام
     */
    public function getAvailablePaymentMethods(): array
    {
        try {
            $store = $this->storeManager->getStore();
            
            // جلب طرق الدفع المفعلة من الـ config مباشرة
            $activePaymentMethods = $this->paymentConfig->getActiveMethods();
            
            $methods = [];
            foreach ($activePaymentMethods as $methodCode => $methodConfig) {
                // التحقق من أن الطريقة مفعلة
                $isActive = $this->scopeConfig->getValue(
                    'payment/' . $methodCode . '/active',
                    ScopeInterface::SCOPE_STORE,
                    $store->getId()
                );
                
                if ($isActive) {
                    $title = $this->scopeConfig->getValue(
                        'payment/' . $methodCode . '/title',
                        ScopeInterface::SCOPE_STORE,
                        $store->getId()
                    ) ?: ucfirst(str_replace('_', ' ', $methodCode));
                    
                    $methods[] = [
                        'code' => $methodCode,
                        'title' => $title
                    ];
                }
            }
            
            $this->logger->info('EasyOrder: Found payment methods from system', [
                'count' => count($methods),
                'methods' => array_column($methods, 'code')
            ]);
            
            return $methods;
            
        } catch (\Exception $e) {
            $this->logger->error('Error getting payment methods: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @inheritDoc
     */
    public function createQuickOrder(QuickOrderDataInterface $orderData): array
    {
        try {
            $store = $this->storeManager->getStore();
            $quote = $this->quoteFactory->create();
            $quote->setStore($store);

            // Add product to quote
            $product = $this->productRepository->getById($orderData->getProductId(), false, $store->getId());
            $quote->addProduct($product, $orderData->getQty());

            // Set customer data (guest)
            $quote->setCustomerIsGuest(true);
            $quote->setCustomerGroupId($this->helperData->getDefaultCustomerGroup());

            $customerEmail = $orderData->getCustomerEmail();
            if (!$customerEmail && $this->helperData->isAutoGenerateEmailEnabled()) {
                $customerEmail = $this->helperData->generateGuestEmail($orderData->getCustomerPhone());
            }

            $quote->setCustomerEmail($customerEmail);
            $quote->setCustomerFirstname($orderData->getCustomerName());
            $quote->setCustomerLastname('');

            // Set billing address
            $billingAddress = $quote->getBillingAddress();
            $billingAddress->setFirstname($orderData->getCustomerName());
            $billingAddress->setLastname('');
            
            // Handle street address properly
            $streetAddress = $orderData->getAddress();
            if (strpos($streetAddress, ',') !== false) {
                $streetLines = array_map('trim', explode(',', $streetAddress));
            } else {
                $streetLines = [$streetAddress];
            }
            $billingAddress->setStreet($streetLines);
            
            $billingAddress->setCity($orderData->getCity());
            $billingAddress->setCountryId($orderData->getCountryId());
            $billingAddress->setTelephone($this->helperData->formatPhoneNumber($orderData->getCustomerPhone()));
            $billingAddress->setEmail($customerEmail);

            if ($orderData->getRegion()) {
                // Try to find region ID if region is provided as text
                $regionId = $this->getRegionIdByName($orderData->getRegion(), $orderData->getCountryId());
                if ($regionId) {
                    $billingAddress->setRegionId($regionId);
                }
                $billingAddress->setRegion($orderData->getRegion());
            }
            if ($orderData->getPostcode()) {
                $billingAddress->setPostcode($orderData->getPostcode());
            }

            // Set shipping address (same as billing)
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setFirstname($orderData->getCustomerName());
            $shippingAddress->setLastname('');
            $shippingAddress->setStreet($streetLines);
            $shippingAddress->setCity($orderData->getCity());
            $shippingAddress->setCountryId($orderData->getCountryId());
            $shippingAddress->setTelephone($this->helperData->formatPhoneNumber($orderData->getCustomerPhone()));
            $shippingAddress->setEmail($customerEmail);

            if ($orderData->getRegion()) {
                if ($regionId) {
                    $shippingAddress->setRegionId($regionId);
                }
                $shippingAddress->setRegion($orderData->getRegion());
            }
            if ($orderData->getPostcode()) {
                $shippingAddress->setPostcode($orderData->getPostcode());
            }

            // Set shipping method
            $shippingAddress->setCollectShippingRates(true);
            $shippingAddress->collectShippingRates();
            $shippingAddress->setShippingMethod($orderData->getShippingMethod());

            // Set payment method
            $quote->getPayment()->importData(['method' => $orderData->getPaymentMethod()]);

            // Collect totals and save quote
            $quote->collectTotals();
            $this->cartRepository->save($quote);

            // Create order
            $orderId = $this->cartManagement->placeOrder($quote->getId());
            $order = $this->getOrderById($orderId);

            // Send email if enabled
            if ($this->helperData->isEmailNotificationEnabled()) {
                try {
                    $this->orderSender->send($order);
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to send order email: ' . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'order_id' => $orderId,
                'increment_id' => $order->getIncrementId(),
                'message' => $this->helperData->getSuccessMessage()
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error creating quick order: ' . $e->getMessage());
            throw new LocalizedException(__('Unable to create order: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritDoc
     */
    public function calculateShippingCost(
        int $productId,
        string $shippingMethod,
        string $countryId,
        ?string $region = null,
        ?string $postcode = null,
        int $qty = 1
    ): float {
        try {
            $methods = $this->getAvailableShippingMethods($productId, $countryId, $region, $postcode);
            
            foreach ($methods as $method) {
                if ($method['code'] === $shippingMethod) {
                    return (float)$method['price'];
                }
            }

            return 0.0;
        } catch (\Exception $e) {
            $this->logger->error('Error calculating shipping cost: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get order by ID
     *
     * @param int $orderId
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws NoSuchEntityException
     */
    private function getOrderById(int $orderId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderRepository = $objectManager->get(\Magento\Sales\Api\OrderRepositoryInterface::class);
        return $orderRepository->get($orderId);
    }

    /**
     * Get region ID by name and country
     *
     * @param string $regionName
     * @param string $countryId
     * @return int|null
     */
    private function getRegionIdByName(string $regionName, string $countryId): ?int
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $regionCollection = $objectManager->create(\Magento\Directory\Model\ResourceModel\Region\Collection::class);
            
            $region = $regionCollection
                ->addCountryFilter($countryId)
                ->addFieldToFilter(['default_name', 'name'], [['eq' => $regionName], ['eq' => $regionName]])
                ->getFirstItem();
                
            return $region->getId() ? (int)$region->getId() : null;
        } catch (\Exception $e) {
            $this->logger->warning('Could not find region ID for: ' . $regionName . ' in country: ' . $countryId);
            return null;
        }
    }
}