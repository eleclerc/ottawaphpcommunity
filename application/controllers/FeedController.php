<?php

class FeedController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $this->_helper->layout->disableLayout();

        $data = array();
        $modelBlogpost = new Model_DbTable_BlogPost();
        $modelTwitterPost = new Model_DbTable_TwitterPost();

        // Blog Posting
        foreach ($modelBlogpost->getLatestPosts() as $post) {
            $item = array(
                'date'        => $post['posted_on'],
                'title'       => $post['title'],
                'link'        => $post['url'],
                'pubDate'     => date('r', strtotime($post['posted_on'])),
                'guid'        => $post['guid'],
                'description' => $post['content'],
                'contentType' => 'blog',
            );
        
            $data[] = $item;
        }

        // Twitter Posting
        foreach ($modelTwitterPost->getLatestPosts() as $post) {
            $title = $post['content'];
            if (strlen($title) > 45) {
                $title = substr($title, 0, 45) . '|...|';
            }
        
            $item = array(
                'date'        => $post['posted_on'],
                'title'       => $title,
                'link'        => 'http://twitter.com/' . $post['screen_name'] . '/status/' . $post['guid'],
                'pubDate'     => date('r', strtotime($post['posted_on'])),
                'guid'        => $post['guid'],
                'description' => $post['screen_name'] . ': ' . $post['content'],
                'contentType' => 'twitter',
            );
        
            $data[] = $item;
        }
    
        // Order by date
        $sortable = array();
        foreach ($data as $key => $val) {
            $sortable[$key] = $val['date'];
        }
        array_multisort($sortable, SORT_DESC, $data);
    
        $this->view->data = $data;
    }
}