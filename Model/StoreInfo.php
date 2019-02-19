<?php
namespace StoreSpot\Personalization\Model;

use StoreSpot\Personalization\Api\StoreInfoInterface as ApiInterface;

class StoreInfo implements ApiInterface
{

    private $storeManager;
    private $scopeConfig;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns default settings.
     *
     * @api
     * @return string $currency Default currency
     * @return string $country Default country
     */
    public function getStoreInfo()
    {
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        $currency = $this->storeManager->getStore()->getBaseCurrencyCode();
        $country = $this->scopeConfig->getValue('general/country/default', $scope);

        $output = [[
            'currency'  => $currency,
            'country'   => $country,
        ]];

        return $output;
    }

    /**
     * Sets the StoreSpot settings.
     *
     * @api
     * @param string $pixel_id Facebook pixel id
     * @param boolean $pixel_enabled Enable pixel
     * @param boolean $feed_enabled Enable product feed
     * @param string $product_category Google product category
     * @return boolean $updated
     */
    public function setSettings($pixel_id, $pixel_enabled, $feed_enabled, $product_category)
    {
        $this->setPixelId($pixel_id);
        $this->setPixelEnabled($pixel_enabled);
        $this->setFeedEnabled($feed_enabled);
        $this->setProductCategory($product_category);
        $this->cacheTypeList->cleanType(
            \Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER
        );
        return [['updated' => true]];
    }
}
