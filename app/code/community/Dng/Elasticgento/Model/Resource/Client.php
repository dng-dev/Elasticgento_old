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
     * Snowball languages supported in elasticsearch
     *
     * @var array
     */
    protected $_languages = array(
        'Armenian',
        'Basque',
        'Catalan',
        'Danish',
        'Dutch',
        'English',
        'Finnish',
        'French',
        'German',
        'Hungarian',
        'Italian',
        'Kp',
        'Lovins',
        'Norwegian',
        'Porter',
        'Portuguese',
        'Romanian',
        'Russian',
        'Spanish',
        'Swedish',
        'Turkish',
    );

    /**
     * supported language languages codes present by Snowball or default in elasticsearch or lucene
     *
     * @var array
     */
    protected $_languageCodes = array(
        /**
         * SnowBall filter based
         */
        // Danish
        'da' => 'da_DK',
        // Dutch
        'nl' => 'nl_NL',
        // English
        'en' => array('en_AU', 'en_CA', 'en_NZ', 'en_GB', 'en_US'),
        // Finnish
        'fi' => 'fi_FI',
        // French
        'fr' => array('fr_CA', 'fr_FR'),
        // German
        'de' => array('de_DE', 'de_DE', 'de_AT'),
        // Hungarian
        'hu' => 'hu_HU',
        // Italian
        'it' => array('it_IT', 'it_CH'),
        // Norwegian
        'nb' => array('nb_NO', 'nn_NO'),
        // Portuguese
        'pt' => array('pt_BR', 'pt_PT'),
        // Romanian
        'ro' => 'ro_RO',
        // Russian
        'ru' => 'ru_RU',
        // Spanish
        'es' => array('es_AR', 'es_CL', 'es_CO', 'es_CR', 'es_ES', 'es_MX', 'es_PA', 'es_PE', 'es_VE'),
        // Swedish
        'sv' => 'sv_SE',
        // Turkish
        'tr' => 'tr_TR',

        /**
         * Lucene class based
         */
        // Czech
        'cs' => 'cs_CZ',
        // Greek
        'el' => 'el_GR',
        // Thai
        'th' => 'th_TH',
        // Chinese
        'zh' => array('zh_CN', 'zh_HK', 'zh_TW'),
        // Japanese
        'ja' => 'ja_JP',
        // Korean
        'ko' => 'ko_KR'
    );

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
    public function _escape($value)
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
    public function _escapePhrase($value)
    {
        $pattern = '/("|\\\)/';
        $replace = '\\\$1';

        return preg_replace($pattern, $replace, $value);
    }
}