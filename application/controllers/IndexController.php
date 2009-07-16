<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $modelTwitter = new Model_DbTable_Twitter();
        $modelBlog = new Model_DbTable_Blog();
        
        $db = $this->getInvokeArg('bootstrap')->getResource('db');

        $blogSelect = $db->select()
            ->from('blog', 'url')
            ->join('blog_post', 'blog.id=blog_post.blog_id', array('title', 'content', 'posted_on', 'oriUrl' => 'url', 'tags'))
            ->where('live = 1')
            ->order('posted_on DESC')
            ->limit(15);
            
        $this->view->blogposts = $db->fetchAll($blogSelect);
        
        $twitterSelect = $db->select()
            ->from('twitter', 'screen_name')
            ->join('twitter_post', 'twitter.id=twitter_post.twitter_id', array('content', 'posted_on'))
            ->where('live = 1')
            ->order('posted_on DESC')
            ->limit(22);

        $this->view->tweets = $db->fetchAll($twitterSelect);
        
        $this->view->twitterAccounts = $modelTwitter->getActiveAccounts();
        $this->view->blogAccounts = $modelBlog->getActiveAccounts();
    }
}