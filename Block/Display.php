<?php

namespace StoreSpot\Personalization\Block;

class Display extends \Magento\Framework\View\Element\Template
{

    protected $helperData;
    protected $catalogHelper;
    private $_product;
    private $_checkoutSession;
    private $_queryFactory;


    /**
     * Display constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Helper\Data $catalogHelper
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     * @param \StoreSpot\Pixel\Helper\Data $helperData
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Helper\Data $catalogHelper,
        \Magento\Checkout\Model\Session $_checkoutSession,
        \StoreSpot\Personalization\Helper\Data $helperData,
        \Magento\Search\Model\QueryFactory $queryFactory
    )
    {
        $this->helperData = $helperData;
        $this->catalogHelper = $catalogHelper;
        $this->_checkoutSession = $_checkoutSession;
        $this->_queryFactory = $queryFactory;
        parent::__construct($context);
    }

    /**
     * Returns action of current page
     * @return mixed
     */
    private function getActionName()
    {
        return $this->getRequest()->getFullActionName();
    }

    /**
     * Returns Facebook Pixel 'fbq' tracking code
     * @param $event
     * @param $parameters
     * @param string $method
     * @return string
     */
    private function facebookEventCode($event, $parameters, $method='track' )
    {
        return sprintf("fbq('%s', '%s', %s)", $method, $event, json_encode( $parameters, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT ) );
    }

    /**
     * Returns Add to Cart JQuery
     * @param $params
     * @return string
     */
    private function addToCartClickCode($params)
    {
        return sprintf("
        require(['jquery'], function($){
            $('#product-addtocart-button').click(function() {
                %s;
            })
        })
        ", $this->facebookEventCode('AddToCart', $params));
    }

    /**
     * Return ID of pixel
     * @return mixed
     */
    public function getPixelId()
    {
        return $this->helperData->getGeneralConfig('pixel_id');
    }

    /**
     * Returns Facebook Pixel event code if necessary
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getEventCode()
    {
        $action = $this->getActionName();
        echo $action;

        $params = array();
        switch ($action) {
            case 'catalog_product_view':
                $product = $this->getProduct();
                $type = $product->getTypeId();
                if ($type !== 'simple') { return null; } // TODO: Momenteel enkel Simple Products

                $params['value'] = $product->getFinalPrice();
                $params['content_name'] = $product->getName();
                $params['content_type'] = 'product'; // Want enkel simple products
                $params['content_ids'] = json_encode(array($product->getSku()));
                $params['currency'] = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();

                $p1 = $this->facebookEventCode('ViewContent', $params);
                $p2 = $this->addToCartClickCode($params);
                return $p1 . $p2;

            case 'catalog_category_view':
                echo $action;
                break;

            case 'checkout_index_index':
            case 'onepagecheckout_index_index':
            case 'onestepcheckout_index_index':
                $items = $this->getCartItems();
                $contents = array();
                foreach ( $items as $item ) {
                    $content = array();
                    $content['id'] = $item->getSku();
                    $content['quantity'] = intval($item->getQty());
                    $content['item_price'] = $item->getPrice();

                    $contents[] = $content;
                }
                $params['contents'] = json_encode($contents);
                $params['content_type'] = 'product';

                return $this->facebookEventCode('InitiateCheckout', $params);

            case 'checkout_onepage_success':
                $order = $this->getOrder();
                $items = $order->getAllVisibleItems();
                $contents = array();
                foreach ( $items as $item ) {
                    $content = array();
                    $content['id'] = $item->getSku();
                    $content['quantity'] = intval($item->getQtyOrdered());
                    $content['item_price'] = $item->getPriceInclTax();

                    $contents[] = $content;
                }
                $params['currency'] = $order->getOrderCurrencyCode();
                $params['content_type'] = 'product';
                $params['contents'] = json_encode($contents);
                $params['value'] = $order->getGrandTotal();

                return $this->facebookEventCode('Purchase', $params);

            case 'catalogsearch_result_index':
                $params['search_string'] = $this->_queryFactory->get()->getQueryText();

                return $this->facebookEventCode('Search', $params);

            case 'catalogsearch_advanced_result':
                return $this->facebookEventCode('Search', $params);
        }
    }


    /**
     * Returns product
     * @return \Magento\Catalog\Model\Product|null
     */
    private function getProduct()
    {
        if(is_null($this->_product)) {
            $this->_product = $this->catalogHelper->getProduct();
        }
        return $this->_product;
    }

    /**
     * Returns items in cart
     * @return array
     */
    private function getCartItems()
    {
        $quote = $this->_checkoutSession->getQuote();
        $items = $quote->getAllItems();
        return $items;
    }

    private function getOrder()
    {
        return $this->_checkoutSession->getLastRealOrder();
    }

}