<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Translation\Loader;

/**
 * Mo translation loader class.
 */
class Mo implements LoaderInterface
{
    /**
     * @var string
     */
    protected $fContent;

    /**
     * @var int
     */
    protected $fContentPos;

    /**
     * @var bool
     */
    protected $bigEndian;

    /**
     * Loads messages from an MO file
     *
     * @param string $filename
     * @return array
     * @throws \InvalidArgumentException
     */
    public function load($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \InvalidArgumentException(sprintf('Could not open file %s for reading', $filename));
        }

        $handle = fopen($filename, 'rb');
        if (!$handle) {
            throw new \InvalidArgumentException(sprintf('Could not open file %s for reading', $filename));
        }

        $this->fContent = fread($handle, filesize($filename));
        fclose($handle);

        $this->bigEndian = false;

        $data = unpack('c', $this->readBytes(4));
        $magic = array_shift($data);
        if ($magic == -34) { //to make sure it works for 64-bit platforms
            $this->bigEndian = false;
        } elseif ($magic == -107) {
            $this->bigEndian = true;
        } else {
            throw new \InvalidArgumentException("not MO file (invalid)");
        }

        $this->readInteger();

        $total = $this->readInteger();//total string count
        $keys = $this->readInteger(); //offset of original table
        $translations = $this->readInteger(); //offset of translation table

        $this->seekto($keys);
        $tableKeys = $this->readIntegerArray($total * 2);
        $this->seekto($translations);
        $tableTranslations = $this->readIntegerArray($total * 2);

        $messages = [];
        for ($i = 0; $i < $total; $i++) {
            $this->seekto($tableKeys[$i * 2 + 2]);
            $key = $this->readBytes($tableKeys[$i * 2 + 1]);

            if ($key) {
                $this->seekto($tableTranslations[$i * 2 + 2]);
                $messages[$key] = $this->readBytes($tableTranslations[$i * 2 + 1]);
            }
        }

        return $messages;
    }

    /**
     * Reads a 4-byte integer
     *
     * @return int
     */
    protected function readInteger()
    {
        if ($this->bigEndian) {
            $read = unpack('N', $this->readBytes(4));
        } else {
            $read = unpack('V', $this->readBytes(4));
        }

        return current($read);
    }

    /**
     * Read Integer Array
     *
     * @param int $count
     * @return int
     */
    private function readIntegerArray($count)
    {
        if ($this->bigEndian) {
            // big endian
            return unpack('N'.$count, $this->readBytes(4 * $count));
        } else {
            // low endian
            return unpack('V'.$count, $this->readBytes(4 * $count));
        }
    }

    /**
     * Reads bytes
     *
     * @param int $bytes
     * @return string
     */
    protected function readBytes($bytes)
    {
        $data = substr($this->fContent, $this->fContentPos, $bytes);
        $this->fContentPos += $bytes;

        if (strlen($this->fContent) < $this->fContentPos) {
            $this->fContentPos = strlen($this->fContent);
        }

        return $data;
    }

    /**
     * Seekto
     * @param int $pos
     * @return int
     */
    protected function seekto($pos)
    {
        $this->fContentPos = (strlen($this->fContent) < $this->fContentPos) ? strlen($this->fContent) : $pos;
        return $this->fContentPos;
    }
}
