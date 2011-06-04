<?php
require_once(dirname(__FILE__) . '/autoloader.php');
require_once(dirname(__FILE__) . '/CurlClient.php');
$help = PHP_EOL. 'Usage : php php2yuml.php -s <Folder> -o <file>' . PHP_EOL;
for ($i= 1; $i < $_SERVER['argc'];$i++) {
    switch ($_SERVER['argv'][$i]) {
    case '-s' :
    case '--source' :
        $sourceFolder = $_SERVER['argv'][$i+1];
        break;
    case '-o':
    case '--output':
        $destinationFile = $_SERVER['argv'][$i+1];
        break;
    case '-h':
    case '--help':
        die($help);
        break;
    }
}
if (!$destinationFile) {
        die($help);
}
if (!$sourceFolder) {
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
        foreach ($reflection->getProperties() as $property) {
            if ($property->isPublic()) $class .= '+';
            if ($property->isPrivate()) $class .= '-';
            if ($property->isProtected()) $class .= '*';
            $class .= $property->getName() . ';';
        }
        $class = rtrim($class, ';') . ']';
    } else {
        $class .= ']';
    }

    if ($parentClass = $reflection->getParentClass()) {
        $string = '[' . $parentClass->getName() . ']^-' . $class;
    }
   $components[] = $string;
    if ($interfaces = $reflection->getInterfaces()) {
        foreach ($interfaces as $name => $interface) {
            $components[] = '[' . $name . ']^-.-' .  $class;
        }
    }
}
$components = array_unique(array_filter($components));
$string = implode($components, ',');
$client = new CurlClient();
$client->setUrl('http://yuml.me/diagram/scruffy/class/' . rawurlencode($string));
file_put_contents($destinationFile, $client->call());
