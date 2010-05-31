<?php

class IndexController extends Zend_Controller_Action
{
    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $this->view->blogposts = Default_Model_BlogPostTable::getInstance()->getLatest();
            
        $this->view->tweets = Default_Model_TwitterPostTable::getInstance()->getLatest();
    }
}

