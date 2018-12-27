<?php

namespace StoreSpot\Personalization\Model;

class Feed
{
    private $productsHelper;
    private $dataHelper;
    private $storeManager;
    private $directoryList;
    private $io;

    public function __construct(
        \StoreSpot\Personalization\Helper\Products $productsHelper,
        \StoreSpot\Personalization\Helper\Data $dataHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\File $io
    )
    {
        $this->productsHelper = $productsHelper;
        $this->dataHelper = $dataHelper;
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
        $this->io = $io;
    }

    public function createFeed()
    {
        $dirPath = $this->directoryList->getPath('media') . '/storespot/';
        $fileName = "facebook-product-feed.xml";
        $fileUrl =  $this->storeManager->getStore()->getBaseUrl('media') . 'storespot/' . $fileName;

        if (!file_exists($dirPath)) {
            $this->io->mkdir($dirPath);
        }

        $feed = $this->createFeedHeader($fileUrl);
        $feed .= $this->createFeedContent();
        $feed .= $this->createFeedFooter();

        $this->io->open(array('path'=>$dirPath));
        $this->io->write($fileName, $feed, 0666);

        return $feed;
    }

    private function createFeedHeader($url)
    {
        header("Content-Type: application/xml; charset=utf-8");

        $header  = "";
        $header .= "<?xml version='1.0' encoding='UTF-8' ?>\n";
        $header .= "<feed xmlns='http://www.w3.org/2005/Atom' xmlns:g='http://base.google.com/ns/1.0'>\n";
        $header .= "  <title><![CDATA[" . $this->getStoreName() . " - Facebook Product Feed]]></title>\n";
        $header .= "  <link rel='self' href='" . $url . "'/>\n";
        return $header;
    }

    private function getStoreName()
    {
        return $this->storeManager->getStore()->getName();
    }


    private function createFeedContent()
    {
        $products = $this->productsHelper->getProducts();
        $content = "";
        $googleProductCategory = $this->dataHelper->getGeneralConfig('google_product_category');

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
        $title = ucwords(strtolower($product->getName()));
        $description = $this->productsHelper->getProductDescription($product);
        $availability = $this->productsHelper->getProductAvailability($product);
        $image = $this->productsHelper->getProductImage($product);
        $parent = $this->productsHelper->getParentProduct($product);
        $specialPrice = $product->getSpecialPrice();
        $specialFromDate = $product->getSpecialFromDate();
        $specialToDate = $product->getSpecialToDate();

        $xml = "";
        $xml .= "<g:id>" . $product->getSku() . "</g:id>";
        $xml .= "<g:title><![CDATA[" . $title . "]]></g:title>";
        $xml .= "<g:description><![CDATA[" . $description . "]]></g:description>";
        $xml .= "<g:availability>" . $availability . "</g:availability>";
        $xml .= "<g:condition>new</g:condition>";
        $xml .= "<g:link>" . $product->getProductUrl() . "</g:link>";
        $xml .= "<g:price>" . round($product->getPrice(), 2) . "</g:price>";
        $xml .= "<g:brand><![CDATA[" . $this->getStoreName() . "]]></g:brand>";
        $xml .= "<g:image_link>" . $image . "</g:image_link>";
        $xml .= "<g:google_product_category>" . $googleProductCategory . "</g:google_product_category>";

        if ($specialPrice) {
            $xml .= "<g:sale_price>" . round($specialPrice, 2) . "</g:sale_price>";
        }

        if ($specialFromDate) {
            $xml .= "<g:sale_price_start_date>" . $specialFromDate . "</g:sale_price_start_date>";
        }

        if ($specialToDate) {
            $xml .= "<g:sale_price_end_date>" . $specialToDate . "</g:sale_price_end_date>";
        }

        if ($parent) {
            $xml .= "<g:item_group_id>" . $parent . "</g:item_group_id>";
        }

        return $xml;
    }

    private function createFeedFooter()
    {
        return '</feed>';
    }
}