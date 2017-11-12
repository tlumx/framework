<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test\Log;

use Psr\Log\Test\LoggerInterfaceTest;
use Tlumx\Log\Logger;

class LoggerTest extends LoggerInterfaceTest
{
    private $logHandler;    
    
    public function getLogger()
    {
        $logger = new Logger();
        $this->logHandler = $logger;
        
        return $logger;
    }
    
    public function getLogs()
    {
        $logger = $this->logHandler;
        
        $messages = $logger->getLogMessages();
        
        $return = array();
        foreach ($messages as $message) {
            $return[] = $message[1] . ' ' . $message[3];
        }
        
        return $return;
    }   
}
