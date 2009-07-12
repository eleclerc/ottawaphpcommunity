<?php
 
class Model_Blog
{
    protected $_id;
    protected $_feed;
    protected $_url;
    protected $_status;
    protected $_created_at;
    protected $_updated_at;
 
    /**
    * Constructor
    *
    * @param array|null $options options to set object state
    * @return void
    */
    public function __construct(array $options = array())
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }
 
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }
 
        $local = '_' . $name;
        if (property_exists($this, $local)) {
            return $this->$local;
        }
 
        return null;
    }
 
    public function __set($name, $value)
    {
        $method = 'set' . ucfirst($name);
        if (method_exists($this, $method)) {
           $this->$method($value);
        }
 
        $local = '_' . $name;
        if (!property_exists($this, $local)) {
            throw new InvalidArgumentException();
        }
 
        $this->$local = $value;
    }
 
    /**
    * Set object state
    *
    * @param array $options
    * @return Model_Video
    */
    public function setOptions($options)
    {
        foreach ($options as $option => $value) {
            $this->$option = $value;
        }
 
        return $this;
    }
 
    public function toArray()
    {
        return array(
            'id' => $this->_id,
            'feed' => $this->_feed,
            'url' => $this->_url,
            'status' => $this->_status,
            'created_at' => $this->_created_at,
            'updated_at' => $this->_updated_at,
    }
}
