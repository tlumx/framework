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
 * View interface.
 */
interface ViewInterface
{
    /**
     * Set data
     *
     * @param array $data
     */
    public function setData(array $data);

    /**
     * Get data
     *
     * @return array
     */
    public function getData();

    /**
     * Set template path
     *
     * @param string $path
     */    
    public function setTemplatesPath($path);

    /**
     * Get template path
     *
     * @return string
     */
    public function getTemplatesPath();

    /**
     * Display
     *
     * @param string $template
     */
    public function display($template);

    /**
     * Render template
     *
     * @param string $template
     * @return string
     */
    public function render($template);

    /**
     * Render file
     *
     * @param string $file
     * @return string
     * @throws \RuntimeException
     */
    public function renderFile($file);
}