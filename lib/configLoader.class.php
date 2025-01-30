<?php
Class Config {
    public $settings = [];
    public $site = [];
    public $login = [];
    public function __construct($configFile)
    {
        $configArray = parse_ini_file($configFile, true);
        // set twtxt configs
        $this->settings = ($configArray['settings']) ?? [];
        
        // set site configs
        $this->site = ($configArray['site']) ?? [];

        // set login information
        $this->login = ($configArray['login']) ?? [];
    }

}