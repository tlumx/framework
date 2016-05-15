<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Db;

/**
 * Db profiler class.
 */
class DbProfiler
{
    /**
     * @var array
     */
    private $_profiles = array();

    /**
     * Clear profiler
     */
    public function clear()
    {
        $this->_profiles = array();
    }

    /**
     * Start profiler
     *
     * @param string $sql
     * @param mixed $params
     * @return int
     */
    public function start($sql, $params = null)
    {
        $this->_profiles[] = array(
            'sql' => $sql,
            'params' => $params,
            'start' => microtime(true)
        );
        
        end($this->_profiles);
        
        return key($this->_profiles);
    }

    /**
     * End profiler
     *
     * @param mixed $key
     * @throws \InvalidArgumentException
     */
    public function end($key)
    {
        if (!isset($this->_profiles[$key])) {
            throw new \InvalidArgumentException("Profiler has no query with handle '$key'.");
        }
        
        $this->_profiles[$key]['end'] = microtime(true);
    }

    /**
     * Get profile by key
     * 
     * @param mixed $key
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getProfile($key)
    {
        if (!isset($this->_profiles[$key])) {
            throw new \InvalidArgumentException("Profiler has no query with handle '$key'.");
        }
        
        $end = isset($this->_profiles[$key]['end']) ? $this->_profiles[$key]['end'] : null;
        $total = $end ? ($this->_profiles[$key]['end'] - $this->_profiles[$key]['start']) : null;
        
        return array(
            'sql' => $this->_profiles[$key]['sql'],
            'params' => $this->_profiles[$key]['params'],
            'start' => $this->_profiles[$key]['start'],
            'end' => $end,
            'total' => $total
        );
    }

    /**
     * Get all profiles
     *
     * @return array
     */
    public function getProfiles()
    {
        $result = array();
        
        foreach ($this->_profiles as $key => $profile) {
            if(!isset($profile['end'])) {
                continue;
            }
            
            $result[] = array(
                'sql' => $profile['sql'],
                'params' => $profile['params'],
                'start' => $profile['start'],
                'end' => $profile['end'],
                'total' => ($profile['end'] - $profile['start'])
            );
        }
        
        return $result;
    }
}