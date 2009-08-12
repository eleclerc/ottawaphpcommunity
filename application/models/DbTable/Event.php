<?php
/**
 *
 * */
class Model_DbTable_Event extends Zend_Db_Table_Abstract
{
    protected $_name = 'event';
    protected $_primary = 'id';

    /**
     * When updating a row, update the 'updated_at' date
     *
     * @param  array        $data  Column-value pairs.
     * @param  array|string $where An SQL WHERE clause, or an array of SQL WHERE clauses.
     * @return int          The number of rows updated.
     */
    public function update(array $data, $where)
    {
        if (empty($data['updated_at'])) {
            $data['updated_at'] = new Zend_Db_Expr('now()');
        }
        
        return parent::update($data, $where);
    }
    
    /**
     * When inserting a new row, add a 'created_at' date
     *
     * @param  array  $data  Column-value pairs.
     * @return mixed         The primary key of the row inserted.
     */
    public function insert(array $data)
    {
        if (empty($data['created_at'])) {
            $data['created_at'] = new Zend_Db_Expr('now()');
        }
        if (empty($data['updated_at'])) {
            $data['updated_at'] = new Zend_Db_Expr('now()');
        }
        
        return parent::insert($data);
    }
    
    /**
     * Get the list of blog
     */
    public function getNextEvent()
    {
        $select = $this->select()
                       ->where('date_format(when, "%Y-%m-%d") >= date_format(now(), "%Y-%m-%d"') 
                       ->order('when ASC');
        
        return $this->fetchRow($select);
    }
}
