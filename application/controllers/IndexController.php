<?php
//require_once 'BaseMessage.php';
//require_once 'Message.php';

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $db = $this->getInvokeArg('bootstrap')->getResource('db');

        $blogSelect = $db->select()
            ->from('blog', 'url')
            ->join('blog_post', 'blog.id=blog_post.blog_id', array('title', 'content', 'posted_on', 'oriUrl' => 'url'))
            ->where('live = 1')
            ->order('posted_on DESC')
            ->limit(30);
            
        $this->view->blogposts = $db->fetchAll($blogSelect);


        $twitterSelect = $db->select()
            ->from('twitter', 'screen_name')
            ->join('twitter_post', 'twitter.id=twitter_post.twitter_id', array('content', 'posted_on'))
            ->where('live = 1')
            ->order('posted_on DESC')
            ->limit(30);

        $this->view->tweets = $db->fetchAll($twitterSelect);
    }
}

