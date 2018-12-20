<?php

namespace StoreSpot\Personalization\Controller\Index;


class Index extends \Magento\Framework\App\Action\Action
{
    private $feed;
    private $helper;
    private $resultForward;
    private $resultForwardFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \StoreSpot\Personalization\Helper\Data $helper,
        \StoreSpot\Personalization\Model\Feed $feed
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