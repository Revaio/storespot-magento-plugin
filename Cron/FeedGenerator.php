<?php
namespace StoreSpot\Personalization\Cron;

class FeedGenerator {
    protected $feed;
    
    public function __construct(
        \StoreSpot\Personalization\Model\Feed $feed
    )
    {
        $this->feed = $feed;
    }

    public function execute() {
        error_log('New feed generation');
        $this->feed->createFeed();
        error_log('Done with the feed');
    }

}