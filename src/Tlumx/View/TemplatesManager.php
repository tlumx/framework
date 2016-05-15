<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\View;

/**
 * Templates manager class.
 */
class TemplatesManager
{
    /**
     * Template paths
     *
     * @var array
     */
    protected $paths = [];

    /**
     * Template map
     *
     * @var array
     */
    protected $templateMap = [];

    /**
     * Normalize a path
     *
     * @param string $path
     * @return string
     */
    protected function normalizePath($path)
    {        
        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Set (overwrite) template paths
     *
     * @param array $paths
     */
    public function setTemplatePaths(array $paths)
    {
        $this->paths = [];
        foreach ($paths as $namespace => $path) {
            $this->addTemplatePath($namespace, $path);
        }
    }

    /**
     * Add template path
     *
     * @param string $namespace
     * @param string $path
     * @throws \InvalidArgumentException
     */
    public function addTemplatePath($namespace, $path)
    {
        if(!is_string($namespace) || !is_string($path) || empty($namespace) || empty($path)) {
            throw new \InvalidArgumentException('Template namespace and path must be a not empty string');
        }
        
        $this->paths[$namespace] = $this->normalizePath($path);
    }

    /**
     * Has template path?
     *
     * @param string $namespace
     * @return bool
     */
    public function hasTemplatePath($namespace)
    {
        return array_key_exists($namespace, $this->paths);
    }

    /**
     * Get themplate path by namespace
     *
     * @param string $namespace
     * @return string
     * @throws \RuntimeException
     */
    public function getTemplatePath($namespace)
    {
        if(!$this->hasTemplatePath($namespace)) {
            throw new \RuntimeException("Path with namespace \"".$namespace."\" is not exist");
        }        
        
        $path = $this->paths[$namespace];
        
        if(!is_dir($path)) {
            throw new \RuntimeException("Invalid template path with namespace \"".$namespace."\"");
        }
        
        return $path;
    }

    /**
     * Get template paths
     *
     * @return array
     */
    public function getTemplatePaths()
    {
        return $this->paths;
    }

    /**
     * Clear template paths
     */
    public function clearTemplatePaths()
    {
        $this->paths = [];
    }

    /**
     * Set (overwrite) template map
     *
     * @param array $templateMap
     */
    public function setTemplateMap(array $templateMap)
    {
        $this->templateMap = [];
        foreach ($templateMap as $name => $filename) {
            $this->addTemplate($name, $filename);
        }
    }

    /**
     * Add template to the map
     *
     * @param string $name
     * @param string $filename
     * @throws \InvalidArgumentException
     */
    public function addTemplate($name, $filename)
    {        
        if(!is_string($name) || !is_string($filename) || empty($name) || empty($filename)) {            
            throw new \InvalidArgumentException('Template name and filename must be a not empty string');
        }
        
        $this->templateMap[$name] = $filename;
    }

    /**
     * Has template in the template map?
     *
     * @param string $name
     * @return bool
     */
    public function hasTemplate($name)
    {
        return array_key_exists($name, $this->templateMap);
    }

    /**
     * Get template filename
     *
     * @param string $name
     * @return string
     * @throws \RuntimeException
     */
    public function getTemplate($name)
    {
        if(!$this->hasTemplate($name)) {
            throw new \RuntimeException("Template with name \"".$name."\" is not exist");
        }
        
        $filename = $this->templateMap[$name];
        
        if(!file_exists($filename)) {
            throw new \RuntimeException("Invalid template filename with name \"".$name."\"");
        }
        
        return $filename;
    }

    /**
     * Get template map
     *
     * @return array
     */
    public function getTemplateMap()
    {
        return $this->templateMap;
    }    

    /**
     * Clear template map
     */
    public function clearTemplateMap()
    {
        $this->templateMap = [];
    }
}