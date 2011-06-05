<?php
require_once(dirname(__FILE__) . '/autoloader.php');
require_once(dirname(__FILE__) . '/CurlClient.php');
$help = <<<PHP_EOL
Usage : 
php php2yuml.php -f <SourceDirectory> [-o <file>] [-s <size>] [-d <direction>]
    -f, --from-directory : Source Directory
    -o, --output    : Dump to an Output File (a yuml image)
    -s, --size      : A size, Default size :100;
    -d, --direction : The Direction (Orientation) of the diagram, One
                      From the following : 
                        - LR (Left to Right)
                        - TB (Top to Bottom)
                        - RL (Right to Left)
    -h, --help      : Displays the Help Message
PHP_EOL;
$help  = PHP_EOL . $help . PHP_EOL;
for ($i= 1; $i < $_SERVER['argc'];$i++) {
    switch ($_SERVER['argv'][$i]) {
    case '-f' :
    case '--from-directory' :
        $sourceFolder = $_SERVER['argv'][$i+1];
        break;
    case '-o':
    case '--output':
        $destinationFile = $_SERVER['argv'][$i+1];
        break;
    case '-s':
    case '--size':
        $scale = (int) $_SERVER['argv'][$i+1];  
        break;
    case '-d':
    case '--direction':
        $direction = $_SERVER['argv'][$i+1];
        if (!in_array($direction, array('LR', 'RL', 'TB'))) {
            echo ('The direction Parameter should be one of the following: LR, RL, TB');
            die($help);
        }
        break;
    case '-h':
    case '--help':
        die($help);
        break;
    }
}
if (!$sourceFolder) {
    echo 'The -f Parameter is mandatory' . PHP_EOL;
        die($help);
}
$autoloadManager = new AutoloadManager();
$autoloadManager->addFolder($sourceFolder);
$autoloadManager->register();
$components = array();
foreach ($autoloadManager->parseFolders() as $class => $file) {
    $reflection = new ReflectionClass($class);
    // class name
    //
    $class = '[' .$reflection->getName();
    // properties
    $properties = $reflection->getProperties();
    if (count($properties)) { 
        $class .= '|';
        foreach ($properties as $property) {
            if ($property->isPublic()) $class .= '+';
            if ($property->isPrivate()) $class .= '-';
            if ($property->isProtected()) $class .= '*';
            $class .= $property->getName() . ';';
        }
        $class = rtrim($class, ';');
    } 
    // get methods
    $methods = $reflection->getMethods();
    if (count($methods)) {
         $class .= '|';
        foreach ($methods as $method) {
            if ($method->isPublic()) $class .= '+';
            if ($method->isPrivate()) $class .= '-';
            if ($method->isProtected()) $class .= '*';
            $class .= $method->getName() . ';';
            // get parameters for dependencies
            $parameters = $method->getParameters();
            if (count($parameters)) {
                foreach ($parameters as $parameter) {
                    $paramClass =$parameter->getClass();
                    if ($paramClass && $paramClass instanceof ReflectionClass && $paramClass->isUserDefined()) {
                        $components[] = '[' . $class . ']uses -.->[' . $paramClass->name . ']';
                    }
                }
            }
        }
        $class = rtrim($class, ';');
    }
    $class .= ']';

    if ($parentClass = $reflection->getParentClass()) {
        $string = '[' . $parentClass->getName() . ']^-' . $class;
    }
   $components[] = $string;
    if ($interfaces = $reflection->getInterfaces()) {
        foreach ($interfaces as $name => $interface) {
            $components[] = '[<<' . $name . '>>]^-.-' .  $class;
        }
    }
}
$components = array_unique(array_filter($components));
$string = implode($components, ',');
$client = new CurlClient();
$direction = !empty($direction) ? 'dir:' . $direction . ';' : '';
$scale = !empty($scale) ? 'scale:' . $scale . ';' : '';
$params = ';' . $direction . $scale;
$client->setUrl('http://yuml.me/diagram/scruffy' . $params . '/class/' . rawurlencode($string));
$content = $client->call();
if (isset($destinationFile)) {
    file_put_contents($destinationFile, $content);
} else {
    echo $content;
}
