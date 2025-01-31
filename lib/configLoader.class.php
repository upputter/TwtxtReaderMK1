<?php

class Config
{
    public array $settings = [];
    public $site = [];
    public $login = [];
    public function __construct(public $configFile)
    {
        if (!file_exists($configFile)) {
            $this->configFileError();
        }
        $configArray = parse_ini_file($configFile, true);
        // set twtxt configs
        $this->settings = ($configArray['settings']) ?? [];

        // set site configs
        $this->site = ($configArray['site']) ?? [];

        // set login information
        $this->login = ($configArray['login']) ?? [];
        if (!$this->login['password']) {
            die('Error: No password is set in config file.');
        }
    }

    public function configFileError()
    {
        die('Error: Config file "' . $this->configFile . '" does not exist!');
    }
}
