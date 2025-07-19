<?php
declare(strict_types=1);

namespace MagoArab\EasYorder\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    private const XML_PATH_ENABLED = 'magoarab_easyorder/general/enabled';
    private const XML_PATH_FORM_TITLE = 'magoarab_easyorder/general/form_title';
    private const XML_PATH_SUCCESS_MESSAGE = 'magoarab_easyorder/general/success_message';
    private const XML_PATH_EMAIL_NOTIFICATION = 'magoarab_easyorder/general/send_email_notification';
    private const XML_PATH_CUSTOMER_GROUP = 'magoarab_easyorder/general/default_customer_group';
    private const XML_PATH_FORM_POSITION = 'magoarab_easyorder/general/form_position';
    private const XML_PATH_AUTO_GENERATE_EMAIL = 'magoarab_easyorder/general/auto_generate_email';
    private const XML_PATH_PHONE_VALIDATION = 'magoarab_easyorder/general/phone_validation';
    private const XML_PATH_REQUIRE_EMAIL = 'magoarab_easyorder/form_fields/require_email';
    private const XML_PATH_REQUIRE_POSTCODE = 'magoarab_easyorder/form_fields/require_postcode';
    private const XML_PATH_REQUIRE_REGION = 'magoarab_easyorder/form_fields/require_region';
    private const XML_PATH_SHOW_STREET_2 = 'magoarab_easyorder/form_fields/show_street_2';
    private const XML_PATH_REQUIRE_CITY = 'magoarab_easyorder/form_fields/require_city';
    private const XML_PATH_FORCE_FALLBACK_SHIPPING = 'magoarab_easyorder/shipping/force_fallback_shipping';
    private const XML_PATH_DEFAULT_SHIPPING_PRICE = 'magoarab_easyorder/shipping/default_shipping_price';
    private const XML_PATH_FREE_SHIPPING_THRESHOLD = 'magoarab_easyorder/shipping/free_shipping_threshold';

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get form title
     *
     * @param int|null $storeId
     * @return string
     */
    public function getFormTitle(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_FORM_TITLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get success message
     *
     * @param int|null $storeId
     * @return string
     */
    public function getSuccessMessage(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_SUCCESS_MESSAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if email notification is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEmailNotificationEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_EMAIL_NOTIFICATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get default customer group
     *
     * @param int|null $storeId
     * @return int
     */
    public function getDefaultCustomerGroup(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_CUSTOMER_GROUP,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get form position
     *
     * @param int|null $storeId
     * @return string
     */
    public function getFormPosition(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_FORM_POSITION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if auto generate email is enabled
     */
    public function isAutoGenerateEmailEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_AUTO_GENERATE_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if phone validation is enabled
     */
    public function isPhoneValidationEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PHONE_VALIDATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if email field is required
     */
    public function isEmailRequired(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_REQUIRE_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if postcode field is required
     */
    public function isPostcodeRequired(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_REQUIRE_POSTCODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if region field is required
     */
    public function isRegionRequired(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_REQUIRE_REGION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if second street line should be shown
     */
    public function showStreet2(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_STREET_2,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if city field is required
     */
    public function isCityRequired(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_REQUIRE_CITY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if fallback shipping should be forced
     */
    public function isForceFallbackShipping(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FORCE_FALLBACK_SHIPPING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get default shipping price
     */
    public function getDefaultShippingPrice(?int $storeId = null): float
    {
        return (float)$this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_SHIPPING_PRICE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get free shipping threshold
     */
    public function getFreeShippingThreshold(?int $storeId = null): float
    {
        return (float)$this->scopeConfig->getValue(
            self::XML_PATH_FREE_SHIPPING_THRESHOLD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Generate guest email from phone number using easypay.com domain
     */
    public function generateGuestEmail(string $phone): string
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        return $cleanPhone . '@easypay.com';
    }

    /**
     * Format and validate phone number
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Add Egyptian country code if not present
        if (!str_starts_with($cleanPhone, '+') && !str_starts_with($cleanPhone, '20')) {
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '+2' . $cleanPhone;
            } else {
                $cleanPhone = '+20' . $cleanPhone;
            }
        }
        
        return $cleanPhone;
    }

    /**
     * Validate phone number format
     */
    public function validatePhoneNumber(string $phone): bool
    {
        if (!$this->isPhoneValidationEnabled()) {
            return true;
        }
        
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Egyptian phone number validation
        $patterns = [
            '/^\+20[0-9]{10}$/',  // +20xxxxxxxxxx
            '/^20[0-9]{10}$/',    // 20xxxxxxxxxx
            '/^0[0-9]{10}$/',     // 0xxxxxxxxxx
            '/^[0-9]{11}$/'       // xxxxxxxxxxx
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleanPhone)) {
                return true;
            }
        }
        
        return false;
    }
}