<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Translation;

use Tlumx\Translation\Loader\LoaderInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Translator class.
 */
class Translator
{
    /**
     * @var array
     */
    protected $messages = array();

    /**
     * @var array
     */
    protected $files = array();

    /**
     * @var array
     */
    protected $loaders = array();

    /**
     * @var \Psr\Cache\CacheItemPoolInterface
     */
    protected $cache;

    /**
     * @var string
     */
    protected $language;

    /**
     * Construct
     * 
     * Example:
     * $options = array(
     * 		'language'  => 'en',
     * 		'cache'		=> $cacheObj,
     * 		'files'		=> array(
     * 			array('csv', 'file1','en','default'),
     * 			array('csv', 'file2','ua'),
     * 		)
     * )
     *
     *
     * @param array $options
     * @throws \InvalidArgumentException
     */
    public function __construct(array $options = array())
    {
        if(isset($options['language'])) {
            $this->language = $options['language'];
        }
    
        if (isset($options['cache'])) {
            $this->setCacheItemPool($options['cache']);
        }
    
        if (isset($options['files'])) {
            if (!is_array($options['files'])) {
                throw new \InvalidArgumentException('"file" should be an array');
            }
        
            foreach ($options['files'] as $file) {
                if(!is_array($file) || (count($file) < 3)) {
                    throw new \InvalidArgumentException('invalid fule option');
                }
    
                $category = (isset($file[3])) ? $file[3] : 'default';
    
                $this->addTranslationFile($file[0], $file[1], $file[2], $category);
            }            	
        }
    }

    /**
     * Set language
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = (string) $language;
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set cache object
     *
     * @param \Psr\Cache\CacheItemPoolInterface $cache
     */
    public function setCacheItemPool(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get cache object
     *
     * @return null|\Psr\Cache\CacheItemPoolInterface
     */
    public function getCacheItemPool()
    {
        return $this->cache;
    }

    /**
     * Add translation file
     *
     * @param string $loader
     * @param string $filename
     * @param string $language
     * @param string $category
     */
    public function addTranslationFile($loader, $filename, $language, $category = 'default')
    {
        $this->files[$language][] = array($loader, $filename, $category);
    }

    /**
     * Get translation loader
     *
     * @param string $loader
     * @return LoaderInterface
     * @throws \InvalidArgumentException
     */
    protected function getLoader($loader)
    {
        switch (strtolower($loader)) {
            case 'array':
                $loader = 'Tlumx\\Translation\\Loader\\PhpArray';
                break;
            case 'mo':
                $loader = 'Tlumx\\Translation\\Loader\\Mo';
                break;
            case 'po':
                $loader = 'Tlumx\\Translation\\Loader\\Po';
                break;
            case 'csv':
                $loader = 'Tlumx\\Translation\\Loader\\Csv';
                break;
            case 'ini':
                $loader = 'Tlumx\\Translation\\Loader\\Ini';
                break;
        }
    
        if(!class_exists($loader)) {
            throw new \InvalidArgumentException("invalid class");
        }
    
        $loader = new $loader;
    
        if(!$loader instanceof LoaderInterface) {
            throw new \InvalidArgumentException("not interface");
        }
    
        return $loader;
    }

    /**
     * Translate the message
     *
     * @param string $message
     * @param string $category
     * @param string $language
     * @return string
     */
    public function translate($message, $category = 'default', $language = null)
    {
        if($language === null) {
            $language = $this->getLanguage();
        }
    
        if ($message === '') {
            return '';
        }
        
        if(!isset($this->messages[$category][$language])) {
            if(!isset($this->messages[$category])) {
                $this->messages[$category] = [];
            }
    
            $loaded = false;
            $cache = $this->getCacheItemPool();
            if($cache) {
                $cacheId = 'Translator_' . md5($category . $language);
                $item = $cache->getItem($cacheId);
                if (!$item->isHit()) {
                    $loaded = true;
                    $this->loadFromFile($language, $category);
                } else {
                    $this->messages[$category][$language] = $item->get();
                }
            } else {
                $loaded = true;
                $this->loadFromFile($language, $category);
            }
    
            if ($cache && $loaded) {
                $item->set($this->messages[$category][$language]);
                $cache->saveDeferred($item);
            }
        }
    
        if (!isset($this->messages[$category][$language][$message])) {
            return $message;
        }
    
        return $this->messages[$category][$language][$message];
    }

    /**
     * Load translation messages from file
     *
     * @param string $language
     * @param string $category
     */
    protected function loadFromFile($language, $category = 'default')
    {
        if (isset($this->files[$language])) {
            foreach ($this->files[$language] as $file) {
                if($file[2] != $category) {
                    continue;
                }
                
                if(isset($this->loaders[$file[0]])) {
                    $loader = $this->loaders[$file[0]];
                } else {
                    $loader = $this->getLoader($file[0]);
                }
                
                if(isset($this->messages[$category][$language])) {
                    $this->messages[$category][$language] = array_replace($this->messages[$category][$language],
                        $loader->load($file[1]));
                } else {
                    $this->messages[$category][$language] = $loader->load($file[1]);
                }
            }
        }
    }
}