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
    ) {
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

        if (!$this->io->fileExists($dirPath, $onlyFile = false)) {
            $this->io->mkdir($dirPath);
        }

        $feed = $this->createFeedHeader($fileUrl);
        $feed .= $this->createFeedContent();
        $feed .= $this->createFeedFooter();

        $this->io->open(['path'=>$dirPath]);
        $this->io->write($fileName, $feed, 0666);

        return $feed;
    }

    private function createFeedHeader($url)
    {
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

        foreach ($products as $product) {
            $content .= $this->createProductXML($product, $googleProductCategory);
        }

        return $content;
    }

    private function createProductXML($product, $googleProductCategory)
    {
        if( $product->getTypeId() == 'configurable') {
            return;
        }

        $parent = $this->productsHelper->getParentProduct($product);
        if (! $product->getImage()) {
            if (! $parent) {
                return;
            }
            if (! $parent->getImage()) {
                return;
            }
        }

        $product_id     = $product->getId();
        $feed_id        = 'stsp_' . $product_id;
        $currency       = $this->storeManager->getStore()->getCurrentCurrency()->getCode();

        $group_id       = $parent ? 'stsp_' . $parent->getId() : null;
        $product_name   = ucwords(strtolower($product->getName()));
        $product_price  = round($product->getPrice(), 2);
        $description    = $this->productsHelper->getProductDescription($product);
        $availability   = $this->productsHelper->getProductAvailability($product);
        $condition      = 'new';
        $image_link     = $this->productsHelper->getProductImage($product);
        $brand          = $this->getStoreName();
        $link           = $product->getProductUrl();

        $sale_price     = round($product->getSpecialPrice(), 2);
        $on_sale        = $sale_price ===  $product_price;
        $sale_from      = $product->getSpecialFromDate();
        $sale_to        = $product->getSpecialToDate();

        $xml = "<entry>";
        $xml .= "<g:id>" . $feed_id . "</g:id>";
        $xml .= "<g:title><![CDATA[" . $product_name . "]]></g:title>";
        $xml .= "<g:description><![CDATA[" . $description . "]]></g:description>";
        $xml .= "<g:availability>" . $availability . "</g:availability>";
        $xml .= "<g:condition>" . $condition . "</g:condition>";
        $xml .= "<g:link>" . $link . "</g:link>";
        $xml .= "<g:price>" . $product_price . ' ' . $currency ."</g:price>";
        $xml .= "<g:brand><![CDATA[" . $brand . "]]></g:brand>";
        $xml .= "<g:image_link>" . $image_link . "</g:image_link>";
        $xml .= "<g:google_product_category>" . $googleProductCategory . "</g:google_product_category>";

        if ($on_sale) {
            $xml .= "<g:sale_price>" . $sale_price . ' ' . $currency . "</g:sale_price>";
        }

        if ($sale_from) {
            $xml .= "<g:sale_price_start_date>" . $sale_from . "</g:sale_price_start_date>";
        }

        if ($sale_to) {
            $xml .= "<g:sale_price_end_date>" . $sale_to . "</g:sale_price_end_date>";
        }

        if ($group_id) {
            $xml .= "<g:item_group_id>" . $group_id . "</g:item_group_id>";
        }
        $xml .= "</entry>";

        return $xml;
    }

    private function createFeedFooter()
    {
        return '</feed>';
    }
}
