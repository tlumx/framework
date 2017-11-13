<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\View;

/**
 * View class.
 */
class View implements ViewInterface
{
    const DOCTYPE_HTML4_01_TRANSITIONAL = 'DOCTYPE_HTML4_01_TRANSITIONAL';
    const DOCTYPE_HTML4_01_STRICT       = 'DOCTYPE_HTML4_01_STRICT';
    const DOCTYPE_HTML4_01_FRAMESET     = 'DOCTYPE_HTML4_01_FRAMESET';
    const DOCTYPE_XHTML1_0_TRANSITIONAL = 'DOCTYPE_XHTML1_0_TRANSITIONAL';
    const DOCTYPE_XHTML1_0_STRICT       = 'DOCTYPE_XHTML1_0_STRICT';
    const DOCTYPE_XHTML1_0_FRAMESET     = 'DOCTYPE_XHTML1_0_FRAMESET';
    const DOCTYPE_HTML5                 = 'DOCTYPE_HTML5';

    /**
     * @var array
     */
    protected $doctypes = [
        self::DOCTYPE_HTML4_01_TRANSITIONAL => '<!DOCTYPE HTML PUBLIC "'.
        '-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
        self::DOCTYPE_HTML4_01_STRICT       => '<!DOCTYPE HTML PUBLIC "'.
        '-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
        self::DOCTYPE_HTML4_01_FRAMESET     => '<!DOCTYPE HTML PUBLIC "'.
        '-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
        self::DOCTYPE_XHTML1_0_TRANSITIONAL => '<!DOCTYPE html PUBLIC "'.
        '-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        self::DOCTYPE_XHTML1_0_STRICT       => '<!DOCTYPE html PUBLIC "'.
        '-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
        self::DOCTYPE_XHTML1_0_FRAMESET     => '<!DOCTYPE html PUBLIC "'.
        '-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
        self::DOCTYPE_HTML5                 => '<!DOCTYPE html>'
    ];

    /**
     * @var string
     */
    protected $doctype = self::DOCTYPE_HTML5;

    /**
     * @var string
     */
    protected $title = 'Tlumx framework 2 !!!';

    /**
     * @var array
     */
    protected $headmeta = [];

    /**
     * @var array
     */
    protected $links = [];

    /**
     * @var string
     */
    protected $icon = '';

    /**
     * @var array
     */
    protected $stylesheets = [];

    /**
     * @var array
     */
    protected $plainCss = '';

    /**
     * @var array
     */
    protected $headScripts = [];

    /**
     * @var array
     */
    protected $afterBodyScripts = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $templatesPath;

    /**
     * @var array
     */
    protected $widgets = [];

    /**
     * Set doctype
     *
     * @param string $doctype
     * @throws \InvalidArgumentException
     */
    public function setDoctype($doctype)
    {
        if (!array_key_exists($doctype, $this->doctypes)) {
            throw new \InvalidArgumentException(sprintf('Invalid Doctype "%s"', $doctype));
        }

        $this->doctype = $doctype;
    }

    /**
     * Get doctype
     *
     * @return string
     */
    public function getDoctype()
    {
        return $this->doctypes[$this->doctype];
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = (string) $title;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Create title
     *
     * @return string
     */
    public function createTitle()
    {
        return '<title>' . $this->title . '</title>';
    }

    /**
     * Set meta
     *
     * @param string $type
     * @param string $typeValue
     * @param string $content
     * @param array $modifiers
     * @throws \InvalidArgumentException
     */
    public function setMeta($type = 'name', $typeValue = '', $content = '', array $modifiers = [])
    {
        if (!in_array($type, ['name', 'http-equiv', 'charset', 'property'])) {
            throw new \InvalidArgumentException(sprintf('Invalid meta type "%s"', $type));
        }

        $modifiersString = '';
        foreach ($modifiers as $key => $value) {
            if (!in_array($key, ['lang', 'scheme'])) {
                continue;
            }
            if ($this->doctype == self::DOCTYPE_HTML5 && $key == 'scheme') {
                throw new \InvalidArgumentException('Modifier "scheme" not supported by HTML5');
            }

            $modifiersString .= $key . '="' . $value . '" ';
        }
        if (!empty($modifiersString)) {
            $modifiersString = ' ' . rtrim($modifiersString);
        }

        if ($this->doctype == self::DOCTYPE_HTML5 && $type == 'charset') {
            $html = '<meta %s="%s">';
        } elseif ($this->doctype == self::DOCTYPE_XHTML1_0_FRAMESET ||
                $this->doctype == self::DOCTYPE_XHTML1_0_STRICT ||
                $this->doctype == self::DOCTYPE_XHTML1_0_TRANSITIONAL) {
            $html = '<meta %s="%s" content="%s"%s />';
        } else {
            $html = '<meta %s="%s" content="%s"%s>';
        }

        $meta = sprintf($html, $type, $typeValue, $content, $modifiersString);

        $this->headmeta[] = $meta;
    }

    /**
     * Get meta
     *
     * @return string
     */
    public function getMeta()
    {
        $metas = '';
        foreach ($this->headmeta as $meta) {
            $metas .= $meta . "\n";
        }
        $metas = rtrim($metas, "\n");
        return $metas;
    }

    /**
     * Create link
     *
     * @param array $options
     * @return string
     */
    public function createLink(array $options)
    {
        $str = ' ';
        foreach ($options as $key => $value) {
            if (($str !== ' ')) {
                $str .= ' '.$key.'="'.$value.'"';
            } else {
                $str .= $key.'="'.$value.'"';
            }
        }

        $html = '<link' . $str;
        if ($this->doctype == self::DOCTYPE_XHTML1_0_FRAMESET ||
                $this->doctype == self::DOCTYPE_XHTML1_0_STRICT ||
                $this->doctype == self::DOCTYPE_XHTML1_0_TRANSITIONAL) {
            $html .= '/>';
        } else {
            $html .= '>';
        }

        return $html;
    }

    /**
     * Set link
     *
     * @param array $options
     */
    public function setLink(array $options)
    {
        $this->links[] = $this->createLink($options);
    }

    /**
     * Get links
     *
     * @return string
     */
    public function getLinks()
    {
        $links = '';
        foreach ($this->links as $link) {
            $links .= $link . "\n";
        }
        $links = rtrim($links, "\n");
        return $links;
    }

    /**
     * Set icon
     *
     * @param string $href
     * @param string $type
     */
    public function setIcon($href, $type = 'png')
    {
        switch ($type) {
            case 'png':
                $this->icon = $this->createLink([
                    'rel'  => 'icon',
                    'type' => 'image/png',
                    'href' => $href
                ]);
                break;
            case 'ico':
                $this->icon = $this->createLink([
                    'rel'  => 'shortcut icon',
                    'type' => 'image/x-icon',
                    'href' => $href
                ]);
                break;
            default:
                $this->icon = '';
                break;
        }
    }

    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set stylesheet
     *
     * @param string $href
     * @param string $media
     */
    public function setStylesheet($href, $media = 'all')
    {
        $this->stylesheets[] = $this->createLink([
            'href'  => $href,
            'rel'   => 'stylesheet',
            'media' => $media
        ]);
    }

    /**
     * Get stylesheets
     *
     * @return string
     */
    public function getStylesheets()
    {
        $stylesheets = '';
        foreach ($this->stylesheets as $stylesheet) {
            $stylesheets .= $stylesheet . "\n";
        }
        $stylesheets = rtrim($stylesheets, "\n");
        return $stylesheets;
    }

    /**
     * Set plain css
     *
     * @param string $css
     */
    public function setPlainCss($css)
    {
        $this->plainCss = $css;
    }

    /**
     * Get plain css
     *
     * @return string
     */
    public function getPlainCss()
    {
        return '<style type="text/css">' . $this->plainCss . '</style>';
    }

    /**
     * Create script
     *
     * @param string $src
     * @param boll $isFile
     * @return string
     */
    public function createScript($src, $isFile = true)
    {
        if (!$isFile) {
            return '<script type="text/javascript">'.$src.'</script>';
        }

        return '<script src="'.$src.'" type="text/javascript"></script>';
    }

    /**
     * Append script to header
     *
     * @param string $src
     * @param bool $isFile
     */
    public function appendHeadScript($src, $isFile = true)
    {
        $this->headScripts[] = $this->createScript($src, $isFile);
    }

    /**
     * Prepend script to header
     *
     * @param string $src
     * @param bool $isFile
     */
    public function prependHeadScript($src, $isFile = true)
    {
        $script = $this->createScript($src, $isFile);
        $scripts = $this->headScripts;
        array_unshift($scripts, $script);
        $this->headScripts = $scripts;
    }

    /**
     * Get header script
     *
     * @return string
     */
    public function getHeadScripts()
    {
        $output = '';
        foreach ($this->headScripts as $script) {
            $output .= $script . "\n";
        }
        $output = rtrim($output, "\n");
        return $output;
    }

    /**
     * Append after body script
     *
     * @param string $src
     * @param bool $isFile
     */
    public function appendAfterBodyScript($src, $isFile = true)
    {
        $this->afterBodyScripts[] = $this->createScript($src, $isFile);
    }

    /**
     * Prepend after body script
     *
     * @param string $src
     * @param bool $isFile
     */
    public function prependAfterBodyScript($src, $isFile = true)
    {
        $script = $this->createScript($src, $isFile);
        $scripts = $this->afterBodyScripts;
        array_unshift($scripts, $script);
        $this->afterBodyScripts = $scripts;
    }

    /**
     * Get after body script
     *
     * @return string
     */
    public function getAfterBodyScripts()
    {
        $output = '';
        foreach ($this->afterBodyScripts as $script) {
            $output .= $script . "\n";
        }
        $output = rtrim($output, "\n");
        return $output;
    }

    /**
     * Set data
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set template path
     *
     * @param string $path
     */
    public function setTemplatesPath($path)
    {
        $this->templatesPath = rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Get template path
     *
     * @return string
     */
    public function getTemplatesPath()
    {
        return $this->templatesPath;
    }

    /**
     * Register widget
     *
     * @param string $name
     * @param \Closure $callable
     * @throws \InvalidArgumentException
     */
    public function registerWidget($name, \Closure $callable)
    {
        if (isset($this->widgets[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'A widget by the name "%s" already exists and cannot be overridden.',
                $name
            ));
        }

        $this->widgets[$name] = function (array $params = []) use ($callable) {
            static $instance;
            if (null === $instance) {
                $instance = $callable($params);
            }
            return $instance;
        };
    }

    /**
     * Get widget
     *
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function widget($name, array $params = [])
    {
        if (!isset($this->widgets[$name])) {
            throw new \InvalidArgumentException(sprintf('A widget by the name "%s" not exist.', $name));
        }

        $widget = $this->widgets[$name];

        return $widget($params);
    }

    /**
     * Display
     *
     * @param string $template
     */
    public function display($template)
    {
        echo $this->render($template);
    }

    /**
     * Render template
     *
     * @param string $template
     * @return string
     */
    public function render($template)
    {
        $file = $this->getTemplatesPath() . DIRECTORY_SEPARATOR .  ltrim($template, DIRECTORY_SEPARATOR);
        return $this->renderFile($file.'.phtml');
    }

    /**
     * Render file
     *
     * @param string $file
     * @return string
     * @throws \RuntimeException
     */
    public function renderFile($file)
    {
        if (!is_file($file)) {
            throw new \RuntimeException(sprintf('View cannot render file "%s" does not exist', $file));
        }

        ob_start();
        ob_implicit_flush(false);

        require $file;
        return ob_get_clean();
    }

    /**
     * Get variable
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Set variable
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Isset variable
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Unset variable
     *
     * @param string $key
     */
    public function __unset($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }
    }
}
