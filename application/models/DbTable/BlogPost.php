<?php
/**
 *
 * */
class Model_DbTable_BlogPost extends Zend_Db_Table_Abstract
{
    protected $_name = 'blog_post';
    protected $_primary = 'id';

    protected $_referenceMap = array(
        'Blog' => array(
            'columns'   =>  'blog_id',
            'refTableClass' => 'Model_DbTable_Blog',
            'refcolumns' => 'id')
        );

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
     * Get the latest blog posts from db, with blog info
     *
     * @param integer $limit number of results returned
     * @return Zend_Db_Table_Rowset
     * */
    public function getLatestPosts($limit=15)
    {
        // This rowset will not be updatable
        $select = $this->select(Zend_Db_Table::SELECT_WITH_FROM_PART)
            ->setIntegrityCheck(false)
            ->join('blog', 'blog.id=blog_post.blog_id', array('blogUrl' => 'url'))
            ->where('blog.live = ?', 1)
            ->order('blog_post.posted_on DESC')
            ->limit($limit);

        return $this->fetchAll($select);
    }
}
