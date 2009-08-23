<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // Stuff posted on blogs and Twitter
        $modelBlogpost = new Model_DbTable_BlogPost();
        $modelTwitterPost = new Model_DbTable_TwitterPost();

        $this->view->blogposts = $modelBlogpost->getLatestPosts();
        $this->view->tweets = $modelTwitterPost->getLatestPosts();

        // Blog and Twitter accounts monitored
        $modelBlog = new Model_DbTable_Blog();
        $modelTwitter = new Model_DbTable_Twitter();

        $this->view->twitterAccounts = $modelTwitter->getActiveAccounts();
        $this->view->blogAccounts = $modelBlog->getActiveAccounts();
        
        // Next Event
        $modelEvent = new Model_DbTable_Event();
        $this->view->nextEvent = $modelEvent->getNextEvent();
    }
}