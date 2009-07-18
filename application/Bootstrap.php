<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    /**
     * Autoload stuff from the default model in all modules 
     * (Api_, Form_, Model_, Model_DbTable, Plugin_)
     * */
    protected function _initAutoload()
    {
        $moduleLoader = new Zend_Application_Module_Autoloader(array(
            'namespace' => '',
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
     * Initialize the ZFDebug Bar
     */
    protected function _initZFDebug()
    {
        $zfdebugConfig = $this->getOption('zfdebug');

        if ($zfdebugConfig['enabled'] != 1) {
            return;
        }

        // Ensure db instance is present, and fetch it
        $this->bootstrap('db');
        $db = $this->getResource('db');

        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('ZFDebug');

        $options = array(
            'plugins' => array('Variables',
                               'Database' => array('adapter' => $db),
                               'File',
                               'Memory',
                               'Time',
                               'Exception'),
            //'jquery_path' => '/js/jquery-1.3.2.min.js'
            );
        $debug = new ZFDebug_Controller_Plugin_Debug($options);

        $this->bootstrap('frontController');
        $frontController = $this->getResource('frontController');
        $frontController->registerPlugin($debug);
    }
}

