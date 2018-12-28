<?php
namespace StoreSpot\Personalization\Cron;

class FeedGenerator {
    private $feed;
    private $dataHelper;

    public function __construct(
        \StoreSpot\Personalization\Model\Feed $feed,
        \StoreSpot\Personalization\Helper\Data $dataHelper
    )
    {
        $this->feed = $feed;
        $this->dataHelper = $dataHelper;
    }

    public function execute() {
        if ($this->dataHelper->getGeneralConfig('feed_enabled')) {
            $this->feed->createFeed();
        }
    }
}
