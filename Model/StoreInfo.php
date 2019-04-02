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
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl('media');
        $logo = $this->scopeConfig->getValue(
            'design/header/logo_src',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $output = [[
            'currency'  => $currency,
            'country'   => $country,
            'media_url' => $mediaUrl,
            'logo'		=> $mediaUrl . $logo,
        ]];

        return $output;
    }
}
