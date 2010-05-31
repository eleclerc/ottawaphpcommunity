<?php

class FeedController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $this->_helper->layout->disableLayout();

        $data = array();
        
        $feedData = array(
            'title' => 'Ottawa PHP Community',
            'link'  => 'http://ottawaphpcommunity.ca/feed',
            'published' => time(),
            'charset' => 'UTF-8',
            'description' => 'Aggregating the php developers in the Ottawa Valley');
 
        // Blog Posting
        foreach (Default_Model_BlogPostTable::getInstance()->getLatest(15) as $post) {
            $blogUrl = str_replace('http://', '', $post['Blog']['url']);
            $item = array(
                'sortCol'     => $post['posted_on'],
                'title'       => $post['title'],
                'link'        => $post['url'],
                'lastUpdate'  => strtotime($post['posted_on']),
                'guid'        => $post['guid'],
                'description' => 'from ' . $blogUrl . ':  ' . PHP_EOL 
                              . strip_tags($post['content']),
                'content'     => '<em>from ' . $blogUrl . ':</em><br />'. PHP_EOL
                              . $post['content'],
            );
        
            $data[] = $item;
        }
        
        $tweetDigest = array();
        // Twitter Posting
        foreach (Default_Model_TwitterPostTable::getInstance()->getLatest(50) as $post) {
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
                    'description' => '@' . $post['Twitter']['screen_name'] . ': ' . strip_tags($post['content']) . PHP_EOL,
                    'content'     => '<strong>@' . $post['Twitter']['screen_name'] . '</strong>: ' . $post['content'] . '<br />' . PHP_EOL,
                );
            } else {
                $tweetDigest[$day]['description'] .= '@' . $post['Twitter']['screen_name'] . ': ' . strip_tags($post['content']) . PHP_EOL;
                $tweetDigest[$day]['content'] .= '<strong>@' . $post['Twitter']['screen_name'] . '</strong>: ' . $post['content'] . '<br />' . PHP_EOL;
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

