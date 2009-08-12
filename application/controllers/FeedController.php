<?php

class FeedController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $this->_helper->layout->disableLayout();

        $data = array();
        $modelBlogpost = new Model_DbTable_BlogPost();
        $modelTwitterPost = new Model_DbTable_TwitterPost();

        
        $feedData = array(
            'title' => 'Ottawa PHP Community',
            'link'  => 'http://ottawaphpcommunity.ca/feed',
            'published' => time(),
            'charset' => 'UTF-8',
            'description' => 'Aggregating the php developers in the Ottawa Valley');
 
        // Blog Posting
        foreach ($modelBlogpost->getLatestPosts() as $post) {
            $item = array(
                'sortCol'     => $post['posted_on'],
                'title'       => $post['title'],
                'link'        => $post['url'],
                'lastUpdate'  => strtotime($post['posted_on']),
                'guid'        => $post['guid'],
                'description' => strip_tags($post['content']),
                'content'     => $post['content'],
            );
        
            $data[] = $item;
        }
        
        $tweetDigest = array();
        // Twitter Posting
        foreach ($modelTwitterPost->getLatestPosts(50) as $post) {
            $day = date('Y-m-d', strtotime($post['posted_on']));
            ini_set('display_errors', '1'); error_reporting(E_ALL); 
            if ($day == date('Y-m-d')) {
                // don't create digest for today... as it is not 'till it's done
                continue;
            }

            if (!isset($tweetDigest[$day])) {
            
                $tweetDigest[$day] = array(
                    'sortCol'        => $post['posted_on'],
                    'title'       => 'Tweets for ' . $day,
                    'link'        => 'http://ottawaphpcommunity.ca',
                    'lastUpdate'  => strtotime($post['posted_on']),
                    'guid'        => 'http://ottawaphpcommunity.ca/#tweets-' . $day,
                    'description' => '@' . $post['screen_name'] . ': ' . strip_tags($post['content']) . PHP_EOL,
                    'content'     => '<strong>@' . $post['screen_name'] . '</strong>: ' . $post['content'] . '<br />' . PHP_EOL,
                    'contentType' => 'twitter',
                );
            } else {
                $tweetDigest[$day]['description'] .= '@' . $post['screen_name'] . ': ' . strip_tags($post['content']) . PHP_EOL;
                $tweetDigest[$day]['content'] .= '<strong>@' . $post['screen_name'] . '</strong>: ' . $post['content'] . '<br />' . PHP_EOL;
            }
        }
        
        $entries = array_merge($data, $tweetDigest);

        // Order by date
        $sortable = array();
        foreach ($entries as $key => $val) {
            $sortable[$key] = $val['sortCol'];
        }
        array_multisort($sortable, SORT_DESC, $entries);
        
        $feedData['entries'] = $entries;
        $rss = Zend_Feed::importArray($feedData ,'rss');
        
        $rss->send();
        die();
    }
}
