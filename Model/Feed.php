<?php

namespace StoreSpot\Personalization\Model;

class Feed
{
    private $_productsHelper;
    private $_dataHelper;
    private $_storeManager;
    private $_directoryList;
    private $_io;

    public function __construct(
        \StoreSpot\Personalization\Helper\Products $productsHelper,
        \StoreSpot\Personalization\Helper\Data $dataHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\File $io
    )
    {
        $this->_productsHelper = $productsHelper;
        $this->_dataHelper = $dataHelper;
        $this->_storeManager = $storeManager;
        $this->_directoryList = $directoryList;
        $this->_io = $io;
    }

    public function createFeed()
    {
        $dirPath = $this->_directoryList->getPath('media') . '/storespot/';
        $fileName = "facebook-product-feed.xml";

        if (!file_exists($dirPath)) {
            $this->_io->mkdir($dirPath);
        }

        $feed = $this->createFeedHeader();
        $feed .= $this->createFeedContent();
        $feed .= $this->createFeedFooter();

        $this->_io->open(array('path'=>$dirPath));
        $this->_io->write($fileName, $feed, 0666);

        return $feed;
    }

    private function createFeedHeader()
    {
        header("Content-Type: application/xml; charset=utf-8");

        $header  = "";
        $header .= "<?xml version='1.0' encoding='UTF-8' ?>\n";
        $header .= "<feed xmlns='http://www.w3.org/2005/Atom' xmlns:g='http://base.google.com/ns/1.0'>\n";
        $header .= "  <title><![CDATA[" . $this->getStoreName() . " - Facebook Product Feed]]></title>\n";
        $header .= "  <link rel='self' href='" . $this->getStoreURL() . "'/>\n";
        return $header;
    }

    private function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }

    private function getStoreURL()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    private function createFeedContent()
    {
        $products = $this->_productsHelper->getProducts();
        $content = "";
        $googleProductCategory = $this->_dataHelper->getGeneralConfig('google_product_category');

        foreach ($products as $product)
        {
            $content .= "<entry>";
            $content .= $this->createProductXML($product, $googleProductCategory);
            $content .= "</entry>\n";
        }

        return $content;
    }

    private function createProductXML($product, $googleProductCategory)
    {
        $description = $this->_productsHelper->getProductDescription($product);
        $availability = $this->_productsHelper->getProductAvailability($product);
        $image = $this->_productsHelper->getProductImage($product);


        $xml = "";
        $xml .= "<g:id>" . $product->getSku() . "</g:id>";
        $xml .= "<g:title><![CDATA[" . $product->getName() . "]]></g:title>";
        $xml .= "<g:description><![CDATA[" . $description . "]]></g:description>";
        $xml .= "<g:availability>" . $availability . "</g:availability>";
        $xml .= "<g:condition>new</g:condition>";
        $xml .= "<g:link>" . $product->getProductUrl() . "</g:link>";
        $xml .= "<g:price>" . $product->getPrice() . "</g:price>";
        $xml .= "<g:brand><![CDATA[" . $this->getStoreName() . "]]></g:brand>";
        $xml .= "<g:image_link>" . $image . "</g:image_link>";
        $xml .= "<g:google_product_category>" . $googleProductCategory . "</g:google_product_category>";

        if ($product->getSpecialPrice()) {
            $xml .= "<g:sale_price>" . $product->getSpecialPrice() . "</g:sale_price>";
        }

        if ($product->getSpecialFromDate()) {
            $xml .= "<g:sale_price_start_date>" . $product->getSpecialFromDate() . "</g:sale_price_start_date>";
        }

        if ($product->getSpecialToDate()) {
            $xml .= "<g:sale_price_end_date>" . $product->getSpecialToDate() . "</g:sale_price_end_date>";
        }

        return $xml;
    }

    private function createFeedFooter()
    {
        return '</feed>';
    }
}