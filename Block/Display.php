<?php

namespace StoreSpot\Personalization\Block;

use Magento\Checkout\Model\Session;

class Display extends \Magento\Framework\View\Element\Template
{

    private $dataHelper;
    private $productsHelper;
    private $catalogHelper;
    private $product;
    private $checkoutSession;
    private $queryFactory;

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
        \StoreSpot\Personalization\Helper\Data $dataHelper,
        \StoreSpot\Personalization\Helper\Products $productsHelper,
        \Magento\Search\Model\QueryFactory $queryFactory,
        Session $checkoutSession
    ) {
        $this->dataHelper = $dataHelper;
        $this->catalogHelper = $catalogHelper;
        $this->queryFactory = $queryFactory;
        $this->productsHelper = $productsHelper;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
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
     * Return feed ID of product
     * @return string
     */
    public function getContentId($product_id)
    {
        return 'stsp_' . $product_id;
    }

    /**
     * Get currency
     * @return string
     */
    public function getCurrency()
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * Returns Facebook Pixel 'fbq' tracking code
     * @param $event
     * @param $parameters
     * @param string $method
     * @return string
     */
    private function facebookEventCode($event, $parameters, $method = 'track')
    {
        return sprintf(
            "fbq('%s', '%s', %s)",
            $method,
            $event,
            json_encode($parameters, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT)
        );
    }

    /**
     * Render view product event
     * @return string
     */
    private function renderViewContentEvent()
    {
        $product = $this->catalogHelper->getProduct();
        $type = $product->getTypeId() == 'configurable' ? 'product_group' : 'product';

        $params = [
            'content_type'  => $type,
            'content_ids'   => json_encode([$this->getContentId($product->getId())]),
            'value'         => $this->productsHelper->getProductPrice($product),
            'currency'      => $this->getCurrency()
        ];

        $p1 = $this->facebookEventCode('ViewContent', $params);

        return sprintf("%s

            require(['jquery'], function($){
                $('#product_addtocart_form').submit(function() {
                    const qty = $(this).find('input[name=\"qty\"]').val();
                    const val = %s * qty;
                    fbq('track', 'AddToCart', {
                        content_ids: ['stsp_%s'],
                        content_type: 'product',
                        value: val,
                        currency: '%s'
                    });
                })
            })

        ",
            $p1,
            $this->productsHelper->getProductPrice($product),
            $product->getId(),
            $this->getCurrency()
        );
    }

    /**
     * Render initiate checkout event
     * @return string
     */
    private function renderInitiateCheckoutEvent()
    {
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllItems();

        $contents = [];
        foreach ($items as $item) {
            $content = [];
            $content['id'] = $this->getContentId($item->getProductId());
            $content['quantity'] = (int) $item->getQty();
            $content['item_price'] = round($item->getPrice(), 2);
            $contents[] = $content;
        }

        $params = [
            'content_type'  => 'product',
            'contents'      => json_encode($contents),
            'currency'      => $this->getCurrency()
        ];

        return $this->facebookEventCode('InitiateCheckout', $params);
    }

    /**
     * Render purchase event
     * @return string
     */
    private function renderPurchaseEvent()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $items = $order->getAllVisibleItems();
        $contents = [];
        foreach ($items as $item) {
            $content = [];
            $content['id'] = $this->getContentId($item->getProductId());
            $content['quantity'] = (int) $item->getQtyOrdered();
            $content['item_price'] = round($item->getPrice(), 2);
            $contents[] = $content;
        }

        $params = [
            'content_type'  => 'product',
            'contents'      => json_encode($contents),
            'value'         => round($order->getGrandTotal(), 2),
            'currency'      => $order->getOrderCurrencyCode()
        ];

        return $this->facebookEventCode('Purchase', $params);
    }

    /**
     * Render add to cart event in form mode
     * @return string
     */
    private function renderAddToCartFormEvent()
    {
        $category = $this->catalogHelper->getCategory();
        $products = $category->getProductCollection()->addFinalPrice();
        $product_list = [];
        foreach ($products as $product) {
            $id = $product->getId();
            $type = $product->getTypeId() == 'configurable' ? 'product_group' : 'product';
            $product_list[$id] = [
                'value'         => $this->productsHelper->getProductPrice($product),
                'content_type'  => $type,
                'content_ids'   => [$this->getContentId($product->getId())],
                'currency'      => $this->getCurrency()
            ];
        }
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

        ", json_encode($product_list));
    }

    /**
     * Render add to cart event in button mode
     * @return string
     */
    public function renderAddToCartButtonEvent()
    {
        return sprintf("

            require(['jquery'], function($){
                try {
                    $('button.tocart[data-post]').click(function() {
                        const product = JSON.parse($(this).attr('data-post')).data.product
                        const priceBox = $('[data-role=\"priceBox\"][data-product-id=\"' + product + '\"]');
                        const price = priceBox.find('[data-price-amount]').attr('data-price-amount')
                        fbq('track', 'AddToCart', {
                            content_ids: ['stsp_' + product],
                            content_type: 'product',
                            value: price,
                            currency: '%s'
                        });
                    })
                }
                catch(e) {
                    return;
                }
            })

        ", $this->getCurrency());
    }

    /**
     * Returns Facebook Pixel event code if necessary
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getEventCode()
    {
        $action = $this->getRequest()->getFullActionName();
        switch ($action) {
            case 'catalog_product_view':
                return $this->renderViewContentEvent();

            case 'checkout_index_index':
            case 'onepagecheckout_index_index':
            case 'onestepcheckout_index_index':
                return $this->renderInitiateCheckoutEvent();

            case 'checkout_onepage_success':
                return $this->renderPurchaseEvent();

            case 'catalogsearch_result_index':
                $params['search_string'] = $this->queryFactory->get()->getQueryText();
                return $this->facebookEventCode('Search', $params);

            case 'catalogsearch_advanced_result':
                return $this->facebookEventCode('Search', $params);

            case 'catalog_category_view':
                return $this->renderAddToCartFormEvent();

            default:
                return null;
        }
    }
}
