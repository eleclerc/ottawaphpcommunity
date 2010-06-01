<?php

/**
 * Default_Model_Base_TwitterPost
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property string $guid
 * @property string $content
 * @property timestamp $posted_on
 * @property integer $twitter_id
 * @property string $tags
 * @property Default_Model_Twitter $Twitter
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class Default_Model_Base_TwitterPost extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('twitter_post');
        $this->hasColumn('guid', 'string', 255, array(
             'type' => 'string',
             'length' => '255',
             ));
        $this->hasColumn('content', 'string', null, array(
             'type' => 'string',
             ));
        $this->hasColumn('posted_on', 'timestamp', null, array(
             'type' => 'timestamp',
             ));
        $this->hasColumn('twitter_id', 'integer', 4, array(
             'type' => 'integer',
             'length' => '4',
             ));
        $this->hasColumn('tags', 'string', 255, array(
             'type' => 'string',
             'length' => '255',
             ));
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasOne('Default_Model_Twitter as Twitter', array(
             'local' => 'twitter_id',
             'foreign' => 'id'));

        $timestampable0 = new Doctrine_Template_Timestampable();
        $this->actAs($timestampable0);
    }
}