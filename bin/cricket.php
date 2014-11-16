#!/usr/bin/php
<?php

checkForArgument($argv,1);

/**
 * @todo have this create a basic page
 */
switch ($argv[1]) {
	case "new":
		checkForArgument($argv,2);
		checkForArgument($argv,3);
		
		$name = $argv[3];
		$extension = (array_key_exists(4, $argv)) ? $argv[4] : null;
		
		switch ($argv[2]) {
			case "project":
				newProject($name, $extension);
				break;
			case "page":
				newPage($name, $extension);
				break;
			case "component":
				newComponent($name, $extension);
				break;
			default:
				printHelp();
				exit(0);
		}
		break;
	default:
		printHelp();
		exit(0);
}

function checkForArgument($inArgv, $inIndex) {
	if (!array_key_exists($inIndex, $inArgv)) {
		printHelp();
		exit(0);	
	}
}

function printHelp() {
	print "Usage:\n";
}

function newProject($inName, $inExtension = null) {
	
	
	$php = <<<PHP
<?php

class Mode {
    static private \$mode = 'development';
    
    static public function isDevelopment() {
        return self::\$mode == 'development';
    }
		
	static public function isProduction() {
        return self::\$mode == 'production';
    }
    
}
PHP;
	file_put_contents("Mode.php", $php);

	
	$php = <<<PHP
<?php

//  THIS IS NOT A CRICKET REQUIRED FILE, JUST GOOD PRACTICE

class Config {
    
    static public function getContextRoot() {
        if(Mode::isDevelopment()) {
            return "/{$inName}";
        }else{
            return "";
        }
    }
    
    static public function default_url() {
        return self::getContextRoot() . "/page.php/home";
    }
    
    static public function session_cookie_path() {
        if(Mode::isDevelopment()) {
            return "/{$inName}";
        }else{
            return "/";
        }
    }

}
PHP;
	file_put_contents("Config.php", $php);
	
	
	$php = <<<PHP
<?php

require_once("cricket".DIRECTORY_SEPARATOR."entry.php");
use cricket\core\Dispatcher;

\$path = pathinfo(__FILE__);
\$contextRootPath = realpath(\$path['dirname']);

\$dispatcher = new Dispatcher(false, Config::getContextRoot(), \$contextRootPath,array(
    'resources/cricket' => ".DIRECTORY_SEPARATOR."usr".DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."php".DIRECTORY_SEPARATOR."cricket", // this has to be mapped in your apache config
));
\$dispatcher->dispatchRequest();
	
PHP;
	file_put_contents("page.php", $php);
	
	
	$php = <<<PHP
<?php

require_once("cricket".DIRECTORY_SEPARATOR."entry.php");

header("Location: " . Config::default_url());
PHP;
	file_put_contents("index.php", $php);
	
	exec("mkdir -p app".DIRECTORY_SEPARATOR."pages");
	exec("mkdir -p app".DIRECTORY_SEPARATOR."components");

	
	$php = <<<PHP
<?php

namespace app;

use cricket\core\Application as CricketApplication;

class Application extends CricketApplication {
			    
    public function __construct() {
        parent::__construct(array(
            // tell cricket we are using a regular PHP session
            self::CONFIG_SESSION => self::PHP_SESSION,
            // provide the php session configuration
            self::CONFIG_PHP_SESSION => array(
                self::PHP_SESSION_EXPIRE => 0,
                self::PHP_SESSION_NAME => "rmt",
                self::PHP_SESSION_PATH => \Config::session_cookie_path()
            )
        ));
    }
			
}
	
PHP;
	file_put_contents("app".DIRECTORY_SEPARATOR."Application.php", $php);
}

function newPage($inName, $inExtension = null) {
	if ($inExtension) {
		$extension = $inExtension;
		$splits = explode("\\", $inExtension);
		$className = $splits[count($splits)-1];
	} else {
		$extension = 'cricket\core\Page';
		$className = 'Page';
	}
	
	$php = <<<PHP
<?php

namespace app\pages;

use {$extension};

class Page{$inName} extends {$className} {

	public function init() {
	}

	public function render() {
		\$this->renderTemplate("templates/_Page{$inName}.php",array(
		));
	}

}
	
PHP;
	
	$name = "Page{$inName}";
	exec("mkdir -p app".DIRECTORY_SEPARATOR."pages".DIRECTORY_SEPARATOR."{$name}".DIRECTORY_SEPARATOR."templates");
	file_put_contents("app".DIRECTORY_SEPARATOR."pages".DIRECTORY_SEPARATOR."{$name}".DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."_{$name}.php", "");
	file_put_contents("app".DIRECTORY_SEPARATOR."pages".DIRECTORY_SEPARATOR."Page{$inName}.php", $php);
}

function newComponent($inName, $inExtension = null) {
	if ($inExtension) {
		$extension = $inExtension;
		$splits = explode("\\", $inExtension);
		$className = $splits[count($splits)-1];
	} else {
		$extension = 'cricket\core\Component';
		$className = 'Component';
	}
	
	$php = <<<PHP
<?php

namespace app\components;

use {$extension};

class {$inName} extends {$className} {
	
	public function render() {
		\$this->renderTemplate('templates/_{$inName}.php',array(
		));
	}

}	
PHP;
	
	$name = "{$inName}";
	exec("mkdir -p app".DIRECTORY_SEPARATOR."components".DIRECTORY_SEPARATOR."{$name}".DIRECTORY_SEPARATOR."templates");
	file_put_contents("app".DIRECTORY_SEPARATOR."components".DIRECTORY_SEPARATOR."{$name}".DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."_{$name}.php", "");
	file_put_contents("app".DIRECTORY_SEPARATOR."components".DIRECTORY_SEPARATOR."{$name}.php", $php);
}