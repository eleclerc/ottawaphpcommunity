<?php
/**
 * script/cronjob to get content from blog and twitter
 *
 * @TODO add Zend_Log
 */

define('APPLICATION_ENV', 'development');

require realpath(dirname(__FILE__) . '/../application/cli_bootstrap.php');

$script = new FetchContent($application);
$script->run();

class FetchContent
{
    // Content need one of these words to be accepted (from db)
    public $whitelist;
    
    // Used to tokenize content before verifiying acceptance
    public $tokens = " \n\t!.,?_:";
    
    protected $_db;
    
    public function __construct($application)
    {
        $application->getBootstrap()->bootstrap(array('autoload', 'db'));
        $this->_db = $application->getBootstrap()->getResource('db');
        
        $this->whitelist = $this->_db->fetchCol('SELECT LOWER(tag) FROM tag');
    }

    /**
    * Main Entry Point
    * */
    public function run()
    {
        //deal with command line argument 
        try {
            $opts = new Zend_Console_Getopt(array(
                'discover|d' => 'special Twitter search for PHP around Ottawa (not saved)'));
            $opts->parse();
        } catch (Zend_Console_Getopt_Exception $e) {
            echo $e->getUsageMessage();
            exit;
        }
    
        // if special argument is received
        if (isset($opts->d)) {
            $this->fetchTwitterSpecial();
        } else {
            $this->fetchBlogs();
            $this->fetchTwitter();
        }

        // clear all cache
        echo PHP_EOL . '->clearing cache' . PHP_EOL;
        $cache = Zend_Cache::Factory('Page', 'File');
        $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
    }
    
    /**
     * Fetch blogs content from rss/atom feed
     * */
    public function fetchBlogs()
    {
        $blog = new Model_DbTable_Blog();
        $blogPost = new Model_DbTable_BlogPost();
        
        foreach ($blog->fetchAll('live = 1') as $blog) {
            echo PHP_EOL . 'fetching blog: ' . $blog->feed . PHP_EOL;
        
            try { 
                $rss = Zend_Feed_Reader::import($blog->feed);
            } catch (Exception $e) {
                echo ' ! cannot read feed' . PHP_EOL;
                $rss = array();
            }
        
            foreach ($rss as $item) {
                $tags = $this->getTags($item->getTitle() . ' ' . $item->getContent());
                if (empty($tags)) {
                    continue;
                }
                
                //FIXME we sometime have empty content, just drop these post while it's fixed (this is not a mission critical project :)
                $content = $this->cleanContent($item->getContent());
                if (strlen($content) < 20) {
                    continue;
                }
                
                $post = array();
                $post['blog_id'] = $blog->id;
                $post['title'] = $item->getTitle();
                $post['content'] = $this->cleanContent($item->getContent());
                $post['url'] = $item->getLink();
                $post['posted_on'] = date('Y-m-d H:i:s', $item->getDateCreated()->getTimestamp());
                $post['guid'] = $item->getId();
                $post['tags'] = Zend_Json::encode($tags);
        
                try { 
                    $blogPost->insert($post);
                    echo ' > ' . $item->getTitle() . PHP_EOL;
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
    
    /**
     * Get twitter Content 
     */
    public function fetchTwitter()
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
                $tags = $this->getTags($tweet['text']);
                if (!empty($tags)) {
                    $post = array();
                    $post['twitter_id'] = $account->id;
                    $post['content'] = $tweet['text'];
                    $postedOn = new Zend_Date($tweet['created_at']); 
                    $post['posted_on'] = date('Y-m-d H:i:s', strtotime($tweet['created_at']));
                    $post['guid'] = (string) $tweet['id'];
                    $post['tags'] = Zend_Json::encode($tags);

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
     * Test function to search twitter for mention of php from user close to Ottawa
     * */
    public function fetchTwitterSpecial() 
    {
        // search tweets from user located 25km from downtown ottawa
        // php, zend (framework), or symfony for now, because this is my favorite stuff this week
        $searchUrl = 'http://search.twitter.com/search.json?q=zf+OR+zend+OR+php+OR+symfony&geocode=45.420263%2C-75.701637%2C25km';

        $client = new Zend_Http_Client();
        $client->setUri($searchUrl);
        $response = $client->request();
        $content = Zend_Json::decode($response->getBody());

        if (!empty($content['results'])) {
            echo 'You should consider these accounts:' . PHP_EOL;
        }
        foreach ($content['results'] as $tweet) {
            $tags = $this->getTags($tweet['text']);
            if (!empty($tags)) {
                echo ' @' . $tweet['from_user'] . ': ' . $tweet['text'] . PHP_EOL;
            }
        }
    }

    /**
    * check to see if post contains PHP related tags
    *
    * @var string
    * @return array list of found tags
    * */
    public function getTags($val) 
    {
        $tokenized = array();
        
        $val = strtolower($val);
        $val = strip_tags($val);

        //strip out url to avoid false positive
        $val = preg_replace('/((http:|https:|ftp:)\/\/(\S|\.|!|\?)+)/',
                            '',
                            $val);
        
        //argh, some url don't have http://
        $val = str_replace('.php', '', $val);

        //tokenize and build an array out of it
        $word = strtok($val, $this->tokens);
        while ($word !== false) {
            $tokenized[] = strtolower($word);
            $word = strtok($this->tokens);
        }
        
        // see if this post contains stuff that we want
        $matches = array_intersect($tokenized, $this->whitelist);
        
        if (! is_array($matches)) {
            $matches = array();
        }
        $matches = array_unique($matches);
        
        return $matches;
    }
 
    /**
     *
     * Return the first paragraph, or first two if smaller than X chars and strip html tags
     *
     * @var string
     * @return string plain text
     * */
    public function cleanContent($val)
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
