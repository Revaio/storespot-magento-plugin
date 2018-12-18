<?php

namespace StoreSpot\Pixel\Controller\Index;


class Index extends \Magento\Framework\App\Action\Action
{
    protected $feed;
    private $helper;
    private $resultForward;
    protected $resultForwardFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \StoreSpot\Pixel\Helper\Data $helper,
        \StoreSpot\Pixel\Model\Feed $feed
    )
    {
        $this->feed = $feed;
        $this->helper = $helper;
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultForward = $this->resultForwardFactory->create();

        echo $this->feed->createFeed();

//        if (!empty($this->helper->getConfigValue('enable'))) {
//            echo $this->feed->createFeed();
//        } else {
//            error_log('No Route');
//            $resultForward->forward('noroute');
//        }
    }
}