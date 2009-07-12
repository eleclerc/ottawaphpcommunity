<?php

//TODO add Zend_Log

define('APPLICATION_ENV', 'development');

require realpath(dirname(__FILE__) . '/../application/cli_bootstrap.php');

$script = new FetchContent($application);
$script->run();

class FetchContent
{
    // Content need one of these words to be accepted
    //TODO move this in db
    public $whitelist = array(
        'php',
        'zend',
        'symfony',
        'cakephp',
        'codeigniter',
        'pear',
        'pecl',
        'codeigniter',
        'yii',
        'flow3',
        'recess',
        'limonade',
        'wordpress',
        'drupal',
        'joomla');
    
    // Used to tokenize content before verifiying acceptance
    public $tokens = " \n\t!.,?";
    
    protected $_db;
    
    function __construct($application)
    {
        $application->getBootstrap()->bootstrap(array('autoload', 'db'));
    }
 
    function run()
    {
        $this->getBlogs();
        $this->getMicroblogs();
    }
    
    function getBlogs()
    {
        $blog = new Model_DbTable_Blog();
        $blogPost = new Model_DbTable_BlogPost();
        
        foreach ($blog->fetchAll('live = 1') as $blog) {
            echo PHP_EOL . 'fetching blog: ' . $blog->feed . PHP_EOL;
        
            try { 
                $rss = Zend_Feed::import($blog->feed);
            } catch (Exception $e) {
                echo ' ! cannot read feed' . PHP_EOL;
                $rss = array();
            }
        
            foreach ($rss as $item) {
                if (! $this->isAcceptable($item->title() . ' ' . $item->content())) {
                    continue;
                }
                /* 
                if (!preg_match('/([^\.]php|zend|symfony|cake|codeigniter|pear|pecl|codeigniter|yii|flow3|recess|lemonade)(\W|_)+/i', $item->title() . ' ' . $item->content())) {
                    continue;
                }
                 */
        
                $post = array();
                $post['blog_id'] = $blog->id;
                $post['title'] = $item->title();
                $post['content'] = $this->cleanContent($item->content());
                $post['url'] = $item->link();
                $postedOn = new Zend_Date($item->pubDate(), Zend_Date::RFC_2822); 
                $post['posted_on'] = date('Y-m-d H:i:s', $postedOn->getTimestamp());
                $post['guid'] = $item->guid();
        
                try { 
                    $blogPost->insert($post);
                    echo ' > ' . $item->title() . PHP_EOL;
                } catch (Exception $e) {
                    if (preg_match('/^(SQLSTATE\[23000\]|SQLSTATE\[HY000\])/i', $e->getMessage())) {
                        //noop don't stop on duplicate
                    } else {
                        Throw $e;
                    }
                }
            }
        }
    }
    
    function getMicroblogs()
    {
        $timelineUrl = 'http://twitter.com/statuses/user_timeline/';
        
        $client = new Zend_Http_Client();

        $twitter = new Model_DbTable_Twitter();
        $twitterPost = new Model_DbTable_TwitterPost();

        foreach ($twitter->fetchAll('live = 1') as $account) {
            echo PHP_EOL . 'fetching tweet: ' . $account->screen_name . PHP_EOL;
        
            $client->setUri($timelineUrl . $account->screen_name . '.json');
            $response = $client->request();
            $content = Zend_Json::decode($response->getbody());

            foreach ($content as $tweet) { 
                if ($this->isAcceptable($tweet['text'])) {
                    $post = array();
                    $post['twitter_id'] = $account->id;
                    $post['content'] = $tweet['text'];
                    $postedOn = new Zend_Date($tweet['created_at']); 
                    $post['posted_on'] = date('Y-m-d H:i:s', strtotime($tweet['created_at']));
                    $post['guid'] = (string) $tweet['id'];

                    try { 
                        $twitterPost->insert($post);
                        echo ' > ' . $tweet['user']['screen_name'] . ': ' . $tweet['text'] . PHP_EOL;
                    } catch (Exception $e) {
                        if (preg_match('/^(SQLSTATE\[23000\]|SQLSTATE\[HY000\])/i', $e->getMessage())) {
                            //noop don't stop on duplicate
                        } else {
                            Throw $e;
                        }
                    }
                }
            }
        }
    }
    
    /**
    * check to see if post is PHP related
    *
    * @var string
    * @return boolean
    * */
    function isAcceptable($val) 
    {
        $tokenized = array();
        
        //strip out url to avoid false positive
        $val = preg_replace('/((http:|https:|ftp:)\/\/(\S|\.|!|\?)+)/',
                            '',
                            $val);
        
        //tokenize and build an array out of it
        $word = strtok($val, $this->tokens);
        while ($word !== false) {
            $tokenized[] = strtolower($word);
            $word = strtok($this->tokens);
        }
        
        // see if this post contains stuff that we want
        $matches = array_intersect($tokenized, $this->whitelist);
        if (count($matches) > 0) {
            return true;
        } else {
            return false;
        }
    }
 
    /**
     *
     * Return the first paragraph, or first two if smaller than X chars and strip html tags
     *
     * @var string
     * @return string plain text
     * */
    function cleanContent($val)
    {
       $stripped = false;
        // if longer than 80 chars, cut it
        if (strlen($val) > 80) {
             //
             
            $pos = stripos($val, "\n");
            if ($pos) {
                $stripped = true;
                $val = substr($val, 0, $pos);
            }
            $pos = stripos($val, '</p');
            if ($pos) {
                $stripped = true;
                $val = substr($val, 0, $pos);
            }
            $pos = stripos($val, '<br');
            if ($pos) {
                $stripped = true;
                $val = substr($val, 0, $pos);
            }
            $pos = stripos($val, '</div');
            if ($pos) {
                $stripped = true;
                $val = substr($val, 0, $pos);
            }
        }
        
        $val = strip_tags($val, '<a>');
        $val = nl2br($val);
        
        if ($stripped) {
            $val .= ' [...]';
        }
   
       return $val;
    }   
}
