<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	/**
     * Autoload stuff from the default module
     * (Form, Model, etc...)
     * */
    protected function _initAutoload()
    {
        $moduleLoader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'Default',
            'basePath'  => APPLICATION_PATH));

        return $moduleLoader;
    }

    /**
     * Initialize Google Analytics Tracker
     * needs config in applications.ini as:
     *   analytics.tracker = "UA-codeFromAnalytics-01"
     * */
    protected function _initAnalytics()
    {
        $analytics = $this->getOption('analytics');

        if (!empty($analytics['tracker'])) {
            $this->bootstrap('layout');
            $layout = $this->getResource('layout');
            $layout->analyticsTracker = $analytics['tracker'];            
        }
    }
    
    /**
     * Initialize a default cache on some pages
     * */
    protected function _initPageCache()
    {
        $cacheConfig = $this->getOption('cache');

        if ($cacheConfig['enabled']) {
            $frontendOptions = array(
                'lifetime' => $cacheConfig['general']['lifetime'],
                'default_options' => array('cache_with_cookie_variables' => true),
                // Only enable for these pages
                'regexps' => array(
                    '^/feed' => array('cache' => true),
                    '^/$' => array('cache' => true)
                )
            );

            $cache = Zend_Cache::factory('Page',
                'File',
                $frontendOptions,
                $cacheConfig['backend']
            );
            
            $cache->start();
        }
    }
        
    protected function _initZFDebug()
    {
        $zfdebugConfig = $this->getOption('zfdebug');

        if ($zfdebugConfig['enabled'] != 1) {
            return;
        }
        
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('ZFDebug');
        
        // Ensure doctrine db instance is loaded
        $this->bootstrap('doctrine');
        
        $options = array(
            'plugins' => array('Variables',
                'Danceric_Controller_Plugin_Debug_Plugin_Doctrine',
                'File',
                'Memory',
                'Time',
                'Exception'
            ),
        );

        $debug = new ZFDebug_Controller_Plugin_Debug($options);

        $this->bootstrap('frontController');
        $this->getResource('frontController')->registerPlugin($debug);
    }

}

