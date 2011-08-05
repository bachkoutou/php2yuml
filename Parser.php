<?php
namespace PHP2YUML;
class Parser
{
    public function getClassesFromFolder($folder, $regex = '/\.(inc|php)$/')
    {
        $classes = array();
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder));

        foreach ($files as $file)
        {
            if ($file->isFile() && preg_match($regex, $file->getFilename()))
            {
                if ($classNames = $this->getClassesFromFile($file->getPathname()))
                {
                    foreach ($classNames as $className)
                    {
                        // Adding class to map
                        $classes[$className] = $file->getPathname();
                    }
                }
            }
        }
        return $classes;
    }
    /**
     * Extract the classname contained inside the php file
     *
     * @param String $file Filename to process
     * @return Array Array of classname(s) and interface(s) found in the file
     */
    private function getClassesFromFile($file)
    {
        $namespace = null;
        $classes = array();
        $tokens = token_get_all(file_get_contents($file));
        $nbtokens = count($tokens);

        for ($i = 0 ; $i < $nbtokens ; $i++)
        {
            switch ($tokens[$i][0])
            {
                case T_NAMESPACE:
                    $i+=2;
                    while ($tokens[$i][0] === T_STRING || $tokens[$i][0] === T_NS_SEPARATOR)
                    {
                        $namespace .= $tokens[$i++][1];
                    }
                    break;
                case T_INTERFACE:
                case T_CLASS:
                case T_TRAIT:
                    $i+=2;
                    if ($namespace)
                    {
                        $classes[] = $namespace . '\\' . $tokens[$i][1];
                    }
                    else
                    {
                        $classes[] = $tokens[$i][1];
                    }
                    break;
            }
        }

        return $classes;
    }

}
