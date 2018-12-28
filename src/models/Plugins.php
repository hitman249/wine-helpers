<?php

class Plugins
{
    /** @var Config */
    private $config;
    /** @var Command */
    private $command;
    /** @var Event */
    private $event;
    /** @var FileSystem */
    private $fs;
    /** @var Replaces */
    private $replaces;
    /** @var System */
    private $system;
    /** @var Monitor */
    private $monitor;
    /** @var AbstractPlugin[] */
    protected $plugins;

    /**
     * Plugins constructor.
     * @param Event $event
     * @param Config $config
     * @param Command $command
     * @param FileSystem $fs
     * @param System $system
     * @param Replaces $replaces
     * @param Monitor $monitor
     */
    public function __construct(Event $event, Config $config, Command $command, FileSystem $fs, System $system, Replaces $replaces, Monitor $monitor)
    {
        $this->command  = $command;
        $this->config   = $config;
        $this->event    = $event;
        $this->fs       = $fs;
        $this->replaces = $replaces;
        $this->system   = $system;
        $this->monitor  = $monitor;

        if (null === $this->plugins) {
            $this->plugins = [];
        }

        foreach (glob($this->config->getGameInfoDir() . '/plugins/*.php') as $phpFile) {
            $this->plugins[] = $this->getClassObjectFromFile($phpFile);
        }
    }

    public function getPlugins()
    {
        return $this->plugins;
    }

    public function setConfig(Config $config)
    {
        foreach ($this->plugins as $plugin) {
            $plugin->setConfig($config);
        }
    }


    /**
     * get the full name (name \ namespace) of a class from its file path
     * result example: (string) "I\Am\The\Namespace\Of\This\Class"
     *
     * @param $filePathName
     *
     * @return  string
     */
    public function getClassFullNameFromFile($filePathName)
    {
        return $this->getClassNamespaceFromFile($filePathName) . '\\' . $this->getClassNameFromFile($filePathName);
    }


    /**
     * build and return an object of a class from its file path
     *
     * @param $filePathName
     *
     * @return  mixed
     */
    public function getClassObjectFromFile($filePathName)
    {
        $classString = $this->getClassFullNameFromFile($filePathName);

        if (!$classString) {
            return null;
        }

        if (!class_exists($classString)) {
            include ($filePathName);
        }

        if (!class_exists($classString)) {
            return null;
        }

        $object = new $classString($this->event, $this->config, $this->command, $this->fs, $this->system, $this->replaces, $this->monitor);

        return $object;
    }


    /**
     * get the class namespace form file path using token
     *
     * @param $filePathName
     *
     * @return  null|string
     */
    protected function getClassNamespaceFromFile($filePathName)
    {
        $src          = file_get_contents($filePathName);
        $tokens       = token_get_all($src);
        $count        = count($tokens);
        $i            = 0;
        $namespace    = '';
        $namespace_ok = false;

        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                // Found namespace declaration
                while (++$i < $count) {
                    if ($tokens[$i] === ';') {
                        $namespace_ok = true;
                        $namespace    = trim($namespace);
                        break;
                    }
                    $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }
                break;
            }
            $i++;
        }
        if (!$namespace_ok) {
            return null;
        } else {
            return $namespace;
        }
    }

    /**
     * get the class name form file path using token
     *
     * @param $filePathName
     *
     * @return  mixed
     */
    protected function getClassNameFromFile($filePathName)
    {
        $php_code = file_get_contents($filePathName);
        $classes  = array();
        $tokens   = token_get_all($php_code);
        $count    = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
                $class_name = $tokens[$i][1];
                $classes[]  = $class_name;
            }
        }

        return $classes[0];
    }
}