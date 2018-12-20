<?php

namespace StoreSpot\Personalization\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;


class Data extends AbstractHelper
{

    const XML_PATH_STORESPOT = 'storespot/';

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue($field, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_STORESPOT . 'general/' .$code, $storeId);
    }

    public function getCronConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_STORESPOT . 'cron/' .$code, $storeId);
    }

    public function saveCronConfig($key, $value, WriterInterface $configWriter)
    {
        error_log($key);
        $configWriter->save(self::XML_PATH_STORESPOT . 'cron/' . $key, $value);
        error_log('ok?');
    }
}
