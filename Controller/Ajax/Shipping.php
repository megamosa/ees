<?php
/**
 * MagoArab_EasYorder Ajax Shipping Controller
 *
 * @category    MagoArab
 * @package     MagoArab_EasYorder
 * @author      MagoArab Development Team
 * @copyright   Copyright (c) 2025 MagoArab
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace MagoArab\EasYorder\Controller\Ajax;

use MagoArab\EasYorder\Api\QuickOrderServiceInterface;
use MagoArab\EasYorder\Helper\Data as HelperData;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Class Shipping
 * 
 * Ajax controller for getting shipping methods
 */
class Shipping implements HttpPostActionInterface
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
     * @var HelperData
     */
    private $helperData;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param QuickOrderServiceInterface $quickOrderService
     * @param HelperData $helperData
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        QuickOrderServiceInterface $quickOrderService,
        HelperData $helperData,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->quickOrderService = $quickOrderService;
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    /**
     * Execute action to get shipping methods
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

            // Get request parameters
            $productId = (int)$this->request->getParam('product_id');
            $countryId = trim($this->request->getParam('country_id'));
            $regionId = trim($this->request->getParam('region_id'));
            $region = trim($this->request->getParam('region'));
            $postcode = trim($this->request->getParam('postcode'));
            $city = trim($this->request->getParam('city'));

            // Validate required parameters
            if (!$productId || !$countryId) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Product ID and Country are required.')
                ]);
            }

            // Log request for debugging
            $this->logger->info('EasyOrder Shipping Request', [
                'product_id' => $productId,
                'country_id' => $countryId,
                'region_id' => $regionId,
                'region' => $region,
                'city' => $city,
                'postcode' => $postcode
            ]);

            // Use region ID if available, otherwise use region text
            $regionToUse = $regionId ?: $region;

            // Get shipping methods
            $shippingMethods = $this->quickOrderService->getAvailableShippingMethods(
                $productId,
                $countryId,
                $regionToUse,
                $postcode ?: null
            );

            // Log shipping methods found with detailed info
            $this->logger->info('EasyOrder Shipping Methods Result', [
                'count' => count($shippingMethods),
                'methods' => $shippingMethods,
                'first_method' => !empty($shippingMethods) ? $shippingMethods[0] : null
            ]);

            // If no methods found, log admin URLs to check
            if (empty($shippingMethods)) {
                $this->logger->warning('EasyOrder: No shipping methods found!', [
                    'admin_check' => 'Go to Admin -> Stores -> Configuration -> Sales -> Delivery Methods',
                    'suggestion' => 'Enable at least one shipping method (Flat Rate, Free Shipping, etc.)'
                ]);
            }

            // Return successful response
            return $result->setData([
                'success' => true,
                'shipping_methods' => $shippingMethods,
                'debug_info' => [
                    'product_id' => $productId,
                    'country_id' => $countryId,
                    'region' => $regionToUse,
                    'methods_count' => count($shippingMethods),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'note' => empty($shippingMethods) ? 'No shipping methods configured or enabled' : 'Methods found successfully'
                ]
            ]);

        } catch (LocalizedException $e) {
            $this->logger->error('EasyOrder LocalizedException: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('EasyOrder Exception: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => __('Unable to get shipping methods.')
            ]);
        }
    }
}