<?php
/**
 * Config_Lite (Config/Lite.php)
 *
 * PHP version 5
 *
 * @file      Config/Lite.php
 * @category  Configuration
 * @package   Config_Lite
 * @author    Patrick C. Engel <info@pc-e.org>
 * @copyright 2010 info@pc-e.org
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version   Release: @package_version@
 * @link      https://github.com/pce/config_lite
 */

spl_autoload_register(array('Config_Lite', 'autoload'));

if (class_exists('Config_Lite_UnexpectedValueException', true) === false) {
    throw new Exception('Config_Lite_UnexpectedValueException not found');
}

/**
 * Config_Lite Class 
 *
 * read & save "INI-Style" Configuration Files,
 * fast and with the native php function under the hood.
 *
 * Inspired by Python's ConfigParser.
 *
 * A "Config_Lite" file consists of sections,
 * "[section]"
 * followed by "name = value" entries
 *
 * note: Config_Lite assumes that all name/value entries are in sections.
 *
 * @category  Configuration
 * @package   Config_Lite
 * @author    Patrick C. Engel <info@pc-e.org>
 * @copyright 2010 info@pc-e.org
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version   Release: @package_version@
 * @link      https://github.com/pce/config_lite
 */
class Config_Lite
{
    /**
     * sections, holds the config sections
     *
     * @var array
     */
    protected $sections;
    /**
     * filename
     *
     * @var string
     */
    protected $filename;
    /**
     * _booleans - alias of bool in a representable Configuration String Format
     *
     * @var array
     */
    private $_booleans = array('1' => true, 'on' => true, 
                               'true' => true, 'yes' => true, 
                               '0' => false, 'off' => false, 
                               'false' => false, 'no' => false);
    /**
     * read, note: always assumes and works with sections
     *
     * @param string $filename Filename
     * 
     * @return bool
     * @throws Config_Lite_Exception when file not exists
     */
    public function read($filename)
    {
        if (!file_exists($filename)) {
            throw new Config_Lite_RuntimeException('file not found: ' . $filename);
        }
        $this->filename = $filename;
        $this->sections = parse_ini_file($filename, true);
        if (false === $this->sections) {
            throw new Config_Lite_RuntimeException(
                'failure, can not parse the file: ' . $filename);
        }
    }
    /**
     * save (active record style)
     *
     * @return bool
     */
    public function save()
    {
        return $this->write($this->filename, $this->sections);
    }
    /**
     * write INI-Style Config File 
     * 
     * prepends a php exit if suffix is php,
     * it is valid to write an empty Config file,
     * file locking is not part of this Class
     * 
     * @param string $filename      filename
     * @param array  $sectionsarray array with sections
     * 
     * @return bool
     * @throws Config_Lite_Exception when file is not writeable
     */
    public function write($filename, $sectionsarray)
    {
        $content = '';
        if ('.php' === substr($filename, -4)) {
            $content .= ';<?php exit; ?>' . "\n";
        }
        $sections = '';
        if (!empty($sectionsarray)) {
            foreach ($sectionsarray as $section => $item) {
                if (is_array($item)) {
                    $sections.= "\n[{$section}]\n";
                    foreach ($item as $key => $value) {
                        if (is_bool($value)) {
                            $value = $this->to('bool', $value);
                        } elseif (is_string($value)) { // && strpos '|"
                            $value = '"'. $value .'"';
                        }
                        $sections.= $key .' = '. $value ."\n";
                    }
                }
            }
            $content.= $sections;
        }
        if (!$fp = fopen($filename, 'w')) {
            throw new Config_Lite_RuntimeException(
                    "failed to open file `{$filename}' for writing.");
        }
        if (!fwrite($fp, $content)) {
            throw new Config_Lite_RuntimeException(
                    "failed to write file `{$filename}'");
        }
        fclose($fp);
        return true;
    }
    /**
     * convert type to string or representable Config Format
     *
     * @param string $format `bool', `boolean'
     * @param string $value  value
     * 
     * @return mixed
     * @throws Config_Lite_Exception when format is unknown
     */
    public function to($format, $value)
    {
        switch ($format) {
        case 'bool':
        case 'boolean':
            if ($value === true) {
                return 'yes';
            }
            return 'no';
            break;

        default:
            // unknown format
            throw new Config_Lite_UnexpectedValueException(
                "no conversation made, unrecognized format `{$format}'");
            break;
        }
    }
    /**
     * getString
     * 
     * @param string $sec     Section
     * @param string $key     Key
     * @param mixed  $default default return value
     * 
     * @return string
     * @throws Config_Lite_Exception when config is empty 
     *         and no default value is given
     * @throws Config_Lite_Exception key not found and no default value is given
     */
    public function getString($sec, $key, $default = null)
    {
        if (is_null($this->sections) && is_null($default)) {
            throw new Config_Lite_RuntimeException(
                    'configuration seems to be empty, no sections.');
        }
        if (array_key_exists($key, $this->sections[$sec])) {
            return stripslashes($this->sections[$sec][$key]);
        }
        if (!is_null($default)) {
            return $default;
        }
        throw new Config_Lite_UnexpectedValueException(
                              'key not found, no default value given.'
                  );
    }

    /**
     * get
     * 
     * @param string $sec     Section
     * @param string $key     Key
     * @param mixed  $default default return value
     * 
     * @return string
     * @throws Config_Lite_Exception when config is empty 
     *         and no default value is given
     * @throws Config_Lite_Exception key not found and no default value is given
     */
    public function get($sec, $key, $default = null)
    {
        if (is_null($this->sections) && is_null($default)) {
            throw new Config_Lite_RuntimeException(
                    'configuration seems to be empty, no sections.');
        }
        if (array_key_exists($key, $this->sections[$sec])) {
            return $this->sections[$sec][$key];
        }
        if (!is_null($default)) {
            return $default;
        }
        throw new Config_Lite_UnexpectedValueException(
                              'key not found, no default value given.'
                  );
    }
    /**
     * getBool - returns on,yes,1,true as TRUE 
     * and no given value or off,no,0,false as FALSE
     *
     * @param string $sec     Section
     * @param string $key     Key
     * @param bool   $default default Value
     * 
     * @return bool
     * @throws Config_Lite_Exception when the configuration is empty 
     *         and no default value is given
     */
    public function getBool($sec, $key, $default = null)
    {
        if (is_null($this->sections) && is_null($default)) {
            throw new Config_Lite_RuntimeException(
                'configuration seems to be empty (no sections),' 
                . 'and no default value given.');
        }
        if (array_key_exists($key, $this->sections[$sec])) {
            if (empty($this->sections[$sec][$key])) {
                return false;
            }
            $value = strtolower($this->sections[$sec][$key]);
            if (!in_array($value, $this->_booleans) && is_null($default)) {
                throw new Config_Lite_UnexpectedValueException(sprintf(
                    'Not a boolean: %s, and no default value given.', $value
                ));
            } else {
                return $this->_booleans[$value];
            }
        }
        if (!is_null($default)) {
            return $default;
        }
        throw new Config_Lite_UnexpectedValueException(
                            'option not found, no default value given.'
                  );
    }
    /**
     * array get section
     *
     * @param string $sec     Section
     * @param array  $default default value
     * 
     * @return array
     * @throws Config_Lite_Exception when config is empty 
     *         and no default array is given
     * @throws Config_Lite_Exception when key not found 
     *         and no default array is given
     */
    public function getSection($sec, $default = null)
    {
        if (is_null($this->sections) && is_null($default)) {
            throw new Config_Lite_RuntimeException(
                'configuration seems to be empty, no sections.');
        }
        if (isset($this->sections[$sec])) {
            return $this->sections[$sec];
        }
        if (!is_null($default) && is_array($default)) {
            return $default;
        }
        throw new Config_Lite_UnexpectedValueException(
                           'section not found, no default array given.'
                  );
    }
    /**
     * has option
     *
     * @param string $sec Section
     * @param string $key Key
     * 
     * @return bool
     */
    public function has($sec, $key)
    {
        if (!$this->hasSection($sec)) {
            return false;
        }
        if (isset($this->sections[$sec][$key])) {
            return true;
        }
        return false;
    }
    /**
     * has section
     *
     * @param string $sec Section
     * 
     * @return bool
     */
    public function hasSection($sec)
    {
        if (isset($this->sections[$sec])) {
            return true;
        }
        return false;
    }
    /**
     * Remove option
     *
     * @param string $sec Section
     * @param string $key Key
     * 
     * @return void
     * @throws Config_Lite_Exception when given Section not exists
     */
    public function remove($sec, $key)
    {
        if (!isset($this->sections[$sec])) {
            throw new Config_Lite_UnexpectedValueException('No such Section.');
        }
        unset($this->sections[$sec][$key]);
    }
    /**
     * Remove section
     *
     * @param string $sec Section
     * 
     * @return void
     * @throws Config_Lite_Exception when given Section not exists
     */
    public function removeSection($sec)
    {
        if (!isset($this->sections[$sec])) {
            throw new Config_Lite_UnexpectedValueException('No such Section.');
        }
        unset($this->sections[$sec]);
    }
    /**
     * Set (string) key - add key/doublequoted value pairs to a section,
     * creates new section if necessary and overrides existing keys
     *
     * @param string $sec   Section
     * @param string $key   Key
     * @param mixed  $value Value
     * 
     * @return void
     * @throws Config_Lite_UnexpectedValueException when given key is an array
     */
    public function setString($sec, $key, $value = null)
    {
        if (!is_array($this->sections)) {
            $this->sections = array();
        }
        if (is_array($key)) {
            throw new Config_Lite_UnexpectedValueException(
            'string key expected, but array given.');
        }
        $this->sections[$sec][$key] = addslashes($value);
        return $this;
    }

    /**
     * Set key - add key/value pairs to a section,
     * creates new section if necessary and overrides existing keys
     *
     * @param string $sec   Section
     * @param string $key   Key
     * @param mixed  $value Value
     * 
     * @return void
     * @throws Config_Lite_Exception when given key is an array
     */
    public function set($sec, $key, $value = null)
    {
        if (!is_array($this->sections)) {
            $this->sections = array();
        }
        if (is_array($key)) {
            throw new Config_Lite_InvalidArgumentException(
            'string key expected, but array given.');
        }
        $this->sections[$sec][$key] = $value;
        return $this;
    }
    /**
     * Set section - add key/value pairs to a section, 
     * creates new section if necessary.
     *
     * @param string $sec   Section
     * @param array  $pairs Keys and Values as Array ('key' => 'value')
     * 
     * @return void|PEAR_Error
     */
    public function setSection($sec, $pairs)
    {
        if (!is_array($this->sections)) {
            $this->sections = array();
        }
        if (!is_array($pairs)) {
            throw new Config_Lite_UnexpectedValueException('array expected.');
        }
        $this->sections[$sec] = $pairs;
        return $this;
    }
    /**
     * Text presentation of the Configuration, since empy config is valid, 
     * theres no return of "The Configuration is empty.\n";
     *
     * @throws Config_Lite_RuntimeException
     * @return string
     */
    public function __toString()
    {
        $s = "";
        if ($this->sections != null) {
            foreach ($this->sections as $section => $name) {
                $s.= sprintf("[%s]\n", $section);
                if (is_array($name)) {
                    foreach ($name as $key => $val) {
                        $s.= sprintf("\t%s = %s\n", $key, $val);
                    }
                }
            }
            return $s;
        }
        if (!isset($this->filename)) {
            throw new Config_Lite_RuntimeException(
                                   'Did not read a Configuration File.'
                      );
        }
        return $s;
    }
    /**
     * Autoload static method for loading classes and interfaces.
     * includes Code from the PHP_CodeSniffer package by 
     * Greg Sherwood and Marc McIntyre
     * 
     * @param string $className - name of the class or interface.
     *
     * @return void
     */
    public static function autoload($className)
    {
        $package = 'Config_';
        $packageLen = strlen($package);
        if (substr($className, 0, $packageLen) === $package) {
            $newClassName = substr($className, $packageLen);
        } else {
            $newClassName = $className;
        }
        $path = str_replace('_', '/', $newClassName).'.php';
        if (is_file(dirname(__FILE__).'/'.$path) === true) {
            include dirname(__FILE__).'/'.$path;
        } else {
            file_exists($path) && (include $path);
        }
    }
    /**
     * Constructor optional takes a filename
     *
     * @param string $filename - INI Style Config File
     */
    public function __construct($filename = null)
    {
        if (($filename != null) && (file_exists($filename))) {
            $this->read($filename);
        }
    }
}
