<?php

/**
 * Elasticgento Client
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @see \Elastica\Client
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
class Dng_Elasticgento_Model_Resource_Client extends \Elastica\Client
{
    /**
     * Search query params with their default values
     *
     * @var array
     */
    protected $_defaultQueryParams = array(
        'offset' => 0,
        'limit' => 100,
        'sort_by' => array(array('score' => 'desc')),
        'fields' => array(),
    );

    /**
     * index instances array cache
     *
     * @var array
     */
    protected $_indexInstances = array();

    /**
     * @todo fetch from config object
     */
    final public function __construct($options)
    {
        $config = array(
            'servers' => array(
                array('host' => '10.1.25.228', 'port' => 9200)
            ),
        );
        //call elastica constructor
        parent::__construct($config);
    }

    /**
     * get array of supported languages codes
     *
     * @return array
     */
    final public function getLanguageCodes()
    {
        return $this->_languageCodes;
    }

    /**
     * get a list of languages
     *
     * @return array
     */
    final public function getLanguages()
    {
        return $this->_languages;
    }

    /**
     * get index name by store id
     *
     * @param integer $storeId
     * @return string
     */
    final public function getIndexName($storeId = null)
    {
        return sprintf('%s_store_%s',
            (string)Mage::getConfig()->getNode('global/resources/default_setup/connection/dbname'),
            $storeId);
    }

    /**
     * get index instance
     *
     * @param integer $storeId
     * @return \Elastica\Index
     */
    final public function getIndex($storeId = null)
    {
        if (false === isset($this->_indexInstances[$this->getIndexName($storeId)])) {
            $this->_indexInstances[$this->getIndexName($storeId)] = parent::getIndex($this->getIndexName($storeId));
        }
        return $this->_indexInstances[$this->getIndexName($storeId)];
    }

    /**
     * escape reserved characters
     *
     * @param string $value
     * @return mixed
     */
    public function escape($value)
    {
        $pattern = '/(\+|-|&&|\|\||!|\(|\)|\{|}|\[|]|\^|"|~|\*|\?|:|\\\)/';
        $replace = '\\\$1';

        return preg_replace($pattern, $replace, $value);
    }

    /**
     * escapes specified phrase
     *
     * @param string $value
     * @return string
     */
    public function escapePhrase($value)
    {
        $pattern = '/("|\\\)/';
        $replace = '\\\$1';

        return preg_replace($pattern, $replace, $value);
    }

    /**
     * get a new Document
     *
     * @param integer|string $id
     * @param mixed $data
     */
    public function getDocument($id, $data)
    {
        return $document = new Elastica\Document($id, $data);
    }
}