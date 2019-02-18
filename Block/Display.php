<?php

namespace StoreSpot\Personalization\Block;

class Display extends \Magento\Framework\View\Element\Template
{

    private $dataHelper;
    private $productsHelper;
    private $catalogHelper;
    private $product;
    private $checkoutSession;
    private $queryFactory;
    private $logger;


    /**
     * Display constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Helper\Data $catalogHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \StoreSpot\Personalization\Helper\Data $dataHelper
     * @param \StoreSpot\Personalization\Helper\Products $productsHelper
     * @param \Magento\Search\Model\QueryFactory $queryFactory
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Helper\Data $catalogHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \StoreSpot\Personalization\Helper\Data $dataHelper,
        \StoreSpot\Personalization\Helper\Products $productsHelper,
        \Magento\Search\Model\QueryFactory $queryFactory,
		\Psr\Log\LoggerInterface $logger
    )
    {
        $this->dataHelper = $dataHelper;
        $this->catalogHelper = $catalogHelper;
        $this->checkoutSession = $checkoutSession;
        $this->queryFactory = $queryFactory;
        $this->productsHelper = $productsHelper;
		$this->logger = $logger;
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
     * Returns Add to Cart JQuery
     * @param $params
     * @return string
     */
    private function addToCartMultiple($products)
    {
		return sprintf("
require(['jquery'], function($){
	$('form[data-role=\"tocart-form\"]').submit(function() {
		const products = %s;
		const product = products[$(this).children('input[name=\"product\"]').val()];
		if( product && product.content_type === 'product' ) {
			fbq('track', 'AddToCart', product)
		}
	})
})
", json_encode($products));
	}



    /**
     * Return ID of pixel
     * @return mixed
     */
    public function getPixelId()
    {
        return $this->dataHelper->getGeneralConfig('pixel_id');
    }

	/**
	 * Return ID of product
	 * @return string
	 */
	public function getContentId($product)
	{
		return 'stsp_' . $product->getId();
	}


    /**
     * Returns Facebook Pixel event code if necessary
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
	public function getEventCode()
	{
		$action = $this->getActionName();
		$params = array();

		switch ($action) {
			case 'catalog_product_view':
				$product = $this->getProduct();
				$type = $product->getTypeId();

				$params['value'] = $this->productsHelper->getProductPrice($product);
				$params['content_name'] = $product->getName();
				$params['content_type'] = ($type == 'configurable' ? 'product_group' : 'product');
				$params['content_ids'] = json_encode(array($this->getContentId( $product )));
				$params['currency'] = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();

				$p1 = $this->facebookEventCode('ViewContent', $params);
				$p2 = $this->addToCartClickCode($params);
				return $p1 . $p2;

			case 'checkout_index_index':
			case 'onepagecheckout_index_index':
			case 'onestepcheckout_index_index':
				$items = $this->getCartItems();
				$contents = array();
				foreach ( $items as $item ) {
					$content = array();
					$content['id'] = $this->getContentId( $item );
					$content['quantity'] = intval($item->getQty());
					$content['item_price'] = $this->productsHelper->getProductPrice($item);
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
					$content['id'] = $this->getContentId( $item );
					$content['quantity'] = intval($item->getQtyOrdered());
					$content['item_price'] = $this->productsHelper->getProductPrice($item);
					$contents[] = $content;
				}
				$params['currency'] = $order->getOrderCurrencyCode();
				$params['content_type'] = 'product';
				$params['contents'] = json_encode($contents);
				$params['value'] = round($order->getGrandTotal(), 2);

				return $this->facebookEventCode('Purchase', $params);

			case 'catalogsearch_result_index':
				$params['search_string'] = $this->queryFactory->get()->getQueryText();
				return $this->facebookEventCode('Search', $params);

			case 'catalogsearch_advanced_result':
				return $this->facebookEventCode('Search', $params);

			case 'catalog_category_view':
				$products = $this->getCategoryProducts();
				$js_const = [];
				foreach ( $products as $product ) {
					$id = $product->getId();
					$js_const[$id] = [
						'value'			=> $this->productsHelper->getProductPrice($product),
						'content_type'	=> ($product->getTypeId() == 'configurable' ? 'product_group' : 'product'),
						'content_ids'	=> [$this->getContentId( $product )],
						'currency'		=> $this->_storeManager->getStore()->getCurrentCurrency()->getCode()
					];
				}
				return $this->addToCartMultiple($js_const);

			default:
				return null;
		}
	}


    /**
     * Returns product
     * @return \Magento\Catalog\Model\Product|null
     */
    private function getProduct()
    {
        if(is_null($this->product)) {
            $this->product = $this->catalogHelper->getProduct();
        }
        return $this->product;
    }

	/**
     * Returns category
     */
    private function getCategoryProducts()
    {
        return $this->catalogHelper->getCategory()->getProductCollection()->addFinalPrice();
    }


    /**
     * Returns items in cart
     * @return array
     */
    private function getCartItems()
    {
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllItems();
        return $items;
    }


    /**
     * Returns last order
     * @return \Magento\Sales\Model\Order
     */
    private function getOrder()
    {
        return $this->checkoutSession->getLastRealOrder();
    }

}
