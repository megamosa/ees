<?php
/**
 * MagoArab_EasYorder Quick Order Block
 *
 * @category    MagoArab
 * @package     MagoArab_EasYorder
 * @author      MagoArab Development Team
 * @copyright   Copyright (c) 2025 MagoArab
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace MagoArab\EasYorder\Block\Product;

use MagoArab\EasYorder\Helper\Data as HelperData;
use MagoArab\EasYorder\Api\QuickOrderServiceInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Directory\Model\Config\Source\Country;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Data\Form\FormKey;

/**
 * Class QuickOrder
 * 
 * Block for rendering quick order form on product pages
 */
class QuickOrder extends Template
{
    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var QuickOrderServiceInterface
     */
    private $quickOrderService;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * @var Country
     */
    private $countrySource;

    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * Constructor
     */
    public function __construct(
        Context $context,
        HelperData $helperData,
        QuickOrderServiceInterface $quickOrderService,
        Registry $registry,
        StoreManagerInterface $storeManager,
        PriceHelper $priceHelper,
        Country $countrySource,
        JsonHelper $jsonHelper,
        FormKey $formKey,
        array $data = []
    ) {
        $this->helperData = $helperData;
        $this->quickOrderService = $quickOrderService;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->priceHelper = $priceHelper;
        $this->countrySource = $countrySource;
        $this->jsonHelper = $jsonHelper;
        $this->formKey = $formKey;
        parent::__construct($context, $data);
    }

    /**
     * Check if quick order form should be displayed
     *
     * @return bool
     */
    public function canShowQuickOrder(): bool
    {
        return $this->helperData->isEnabled() && $this->getCurrentProduct() !== null;
    }

    /**
     * Get current product
     *
     * @return Product|null
     */
    public function getCurrentProduct(): ?Product
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Get form title
     *
     * @return string
     */
    public function getFormTitle(): string
    {
        return $this->helperData->getFormTitle();
    }

    /**
     * Get available payment methods
     *
     * @return array
     */
    public function getAvailablePaymentMethods(): array
    {
        try {
            return $this->quickOrderService->getAvailablePaymentMethods();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get countries for dropdown
     *
     * @return array
     */
    public function getCountries(): array
    {
        return $this->countrySource->toOptionArray();
    }

    /**
     * Get regions for specific country
     *
     * @param string $countryId
     * @return array
     */
    public function getRegions(string $countryId): array
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $regionCollection = $objectManager->create(\Magento\Directory\Model\ResourceModel\Region\Collection::class);
        $regionCollection->addCountryFilter($countryId)->load();
        
        $regions = [];
        foreach ($regionCollection as $region) {
            $regions[] = [
                'value' => $region->getId(),
                'label' => $region->getName()
            ];
        }
        
        return $regions;
    }

    /**
     * Check if country requires regions
     *
     * @param string $countryId
     * @return bool
     */
    public function countryHasRegions(string $countryId): bool
    {
        $regions = $this->getRegions($countryId);
        return !empty($regions);
    }

    /**
     * Check if postcode is required for country
     *
     * @param string $countryId
     * @return bool
     */
    public function isPostcodeRequired(string $countryId): bool
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $directoryHelper = $objectManager->get(\Magento\Directory\Helper\Data::class);
        
        return $directoryHelper->isZipCodeOptional($countryId) === false;
    }

    /**
     * Get default country
     *
     * @return string
     */
    public function getDefaultCountry(): string
    {
        return $this->_scopeConfig->getValue(
            'general/country/default',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ?: 'EG';
    }

    /**
     * Get form action URL
     *
     * @return string
     */
    public function getFormActionUrl(): string
    {
        return $this->getUrl('easyorder/order/create');
    }

    /**
     * Get shipping methods URL
     *
     * @return string
     */
    public function getShippingMethodsUrl(): string
    {
        return $this->getUrl('easyorder/ajax/shipping');
    }

    /**
     * Get calculate total URL
     *
     * @return string
     */
    public function getCalculateTotalUrl(): string
    {
        return $this->getUrl('easyorder/ajax/calculate');
    }

    /**
     * Get regions URL
     *
     * @return string
     */
    public function getRegionsUrl(): string
    {
        return $this->getUrl('easyorder/ajax/regions');
    }

    /**
     * Format price
     *
     * @param float $price
     * @return string
     */
    public function formatPrice(float $price): string
    {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * Get current product price
     *
     * @return float
     */
    public function getCurrentProductPrice(): float
    {
        $product = $this->getCurrentProduct();
        if (!$product) {
            return 0.0;
        }

        return (float)$product->getFinalPrice();
    }

    /**
     * Get current product formatted price
     *
     * @return string
     */
    public function getCurrentProductFormattedPrice(): string
    {
        return $this->formatPrice($this->getCurrentProductPrice());
    }

    /**
     * Get JSON configuration for JavaScript
     *
     * @return string
     */
    public function getJsonConfig(): string
    {
        $product = $this->getCurrentProduct();
        if (!$product) {
            return '{}';
        }

        $config = [
            'productId' => $product->getId(),
            'productPrice' => $this->getCurrentProductPrice(),
            'formattedPrice' => $this->getCurrentProductFormattedPrice(),
            'defaultCountry' => $this->getDefaultCountry(),
            'urls' => [
                'shipping' => $this->getShippingMethodsUrl(),
                'calculate' => $this->getCalculateTotalUrl(),
                'regions' => $this->getRegionsUrl(),
                'submit' => $this->getFormActionUrl()
            ],
            'messages' => [
                'loading' => __('Loading...'),
                'error' => __('An error occurred. Please try again.'),
                'selectShipping' => __('Please select a shipping method'),
                'selectPayment' => __('Please select a payment method'),
                'fillRequired' => __('Please fill all required fields')
            ]
        ];

        return $this->jsonHelper->jsonEncode($config);
    }

    /**
     * Get form security key
     *
     * @return string
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }
}