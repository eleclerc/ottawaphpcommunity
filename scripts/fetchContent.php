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
    public $tokens = " \n\t!.,?_:;'\"#";
    
    public function __construct($application)
    {
        $application->getBootstrap()->bootstrap(array('autoload', 'doctrine'));

        $this->whitelist = Doctrine_Query::create()
            ->select('id, LOWER(tag) as tag')
            ->from('Default_Model_Tag')
            ->execute()
                ->toKeyValueArray('id', 'tag');
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
            $this->fetchTwitterSpecial($opts->getRemainingArgs());
        } else {
            $this->fetchBlogs();
            $this->fetchTwitter();

            // clear all cache
            echo PHP_EOL . '->clearing cache' . PHP_EOL;
            $cache = Zend_Cache::Factory('Page', 'File');
            $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
        }
    }
    
    /**
     * Fetch blogs content from rss/atom feed
     * */
    public function fetchBlogs()
    {
        foreach (Default_Model_BlogTable::getInstance()->findByLive(true) as $blog) {
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
                
                $post = new Default_Model_BlogPost();
                $post['blog_id'] = $blog->id;
                $post['title'] = $item->getTitle();
                $post['content'] = $this->cleanContent($item->getContent());
                $post['url'] = $item->getLink();
                $post['posted_on'] = date('Y-m-d H:i:s', $item->getDateCreated()->getTimestamp());
                $post['guid'] = $item->getId();
                $post['tags'] = Zend_Json::encode($tags);
        
                try { 
                    $post->save();
                    echo ' > ' . $post['title'] . PHP_EOL;
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

        foreach (Default_Model_TwitterTable::getInstance()->findByLive(true) as $account) {
            echo PHP_EOL . 'fetching tweet: ' . $account->screen_name . PHP_EOL;
        
            $client->setUri($timelineUrl . $account->screen_name . '.json');
            $response = $client->request();
            $content = Zend_Json::decode($response->getbody());

            foreach ($content as $tweet) { 
                $tags = $this->getTags($tweet['text']);
                if (!empty($tags)) {
                    $post = new Default_Model_TwitterPost();
                    $post['twitter_id'] = $account->id;
                    $post['content'] = $tweet['text'];
                    $post['posted_on'] = date('Y-m-d H:i:s', strtotime($tweet['created_at']));
                    $post['guid'] = (string) $tweet['id'];
                    $post['tags'] = Zend_Json::encode($tags);

                    try { 
                        $post->save();
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
    public function fetchTwitterSpecial(Array $terms) 
    {
        // to filter out account we already have
        $ourAccounts = array();
        foreach (Default_Model_TwitterTable::getInstance()->findByLive(true) as $account) {
            $ourAccounts[] = strtolower($account['screen_name']);
        }
        
        // search tweets from user located 25km from downtown ottawa
        if (empty($terms)) {
            $terms = array(
                'php',
                'symfony',
                'zend',
                'zf',
            );
            echo 'No search terms received from the CLI. Will use the defaults.' . PHP_EOL;
        } 
        echo 'Search terms: ' . implode(', ', $terms) . PHP_EOL;
        
        $q = implode('+OR+', $terms);
        $searchUrl = 'http://search.twitter.com/search.json?q=' . $q . '&geocode=45.420263%2C-75.701637%2C25km';

        $client = new Zend_Http_Client();
        $client->setUri($searchUrl);
        $response = $client->request();
        $content = Zend_Json::decode($response->getBody());

        if (!empty($content['results'])) {
            echo "Here's the PHP tweets from account we don't have:" . PHP_EOL;
        }
        
        foreach ($content['results'] as $tweet) {
            $tags = $this->getTags($tweet['text']);
            if (!empty($tags) && !in_array(strtolower($tweet['from_user']), $ourAccounts)) {
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
