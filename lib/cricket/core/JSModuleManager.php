<?php
/**
 * Created By: Brydon DeWitt
 * Date: 5/31/16
 * Time: 1:10 PM
 *
 * This class provides an API for creating and managing JS "modules"
 */

namespace cricket\core;


class JSModuleManager {

    public static $moduleList = null;

    public function _construct() {
        if(!JSModuleManager::$moduleList)
            JSModuleManager::$moduleList = [];
    }

    // ******* Public Methods *******


    /*
    $cricketJS = Container::resolveResourceUrl($page, get_class($page), "cricket/js/cricket.js");
    $pageID = $page->getInstanceID();
    return <<<END
    <script type="text/javascript" src="$cricketJS"></script>
        <script type="text/javascript">
    _CRICKET_PAGE_INSTANCE_ = '$pageID';
    </script>
    END;
    */


    /**
     * @param $pageID
     *
     * @return string
     * @param $pageID
     *
     */
    public function getRequiredHeadContributions($pageID) {
        $returnStr = '';

        // TODO: Make each js file into a module (except the moduleSystem)

        // Add JS Module system (moduleSystem.js) - creates define() and require() global functions (ideally, these will be the only global namespace pollution)
        $filePath = $this->getJSPath(__FILE__, 'moduleSystem.js');
        $fileContents = $this->getFileContents($filePath);
        $fileContents = $this->indentText($fileContents);
        $returnStr .= '<script id="module-system">' . PHP_EOL . $fileContents . PHP_EOL . '</script>' . PHP_EOL;

        if(\Mode::isDevelopment())
            $returnStr .= PHP_EOL . "<!-- ************ Start Default Modules ************ -->" . PHP_EOL;

        // Add cricket_* as module where already added to page (forces modules to use require)
        $filePath = $this->getJSPath(__FILE__, 'cricket.js');
        $returnStr .= $this->innerGetModuleDefinitionTag($filePath, false, true);
        $returnStr .= $this->indentText("<script>require('cricket').pageInstance='$pageID';</script>") . PHP_EOL;

        // Add a fix for IE
        $filePath = $this->getJSPath(__FILE__, 'ie10-viewport-bug-workaround.js');
        $returnStr .= $this->innerGetModuleDefinitionTag($filePath, false, true);
        $returnStr .= $this->indentText("<script>require('ie10-viewport-bug-workaround');</script>") . PHP_EOL;

        // Add a polyfill to enable the :scope selector inside of .querySelector() method
        $filePath = $this->getJSPath(__FILE__, 'scopeQuerySelectorPolyfill.js');
        $returnStr .= $this->innerGetModuleDefinitionTag($filePath, false, true);
        $returnStr .= $this->indentText("<script>require('scopeQuerySelectorPolyfill');</script>") . PHP_EOL;

        // Add a polyfill for missing `position: sticky` support
        $filePath = $this->getJSPath(__FILE__, 'stickyfill.js');
        $returnStr .= $this->innerGetModuleDefinitionTag($filePath, false, true);
        $returnStr .= $this->indentText("<script>require('stickyfill');</script>") . PHP_EOL;

        // Add module (eventMapping.js)
        $filePath = $this->getJSPath(__FILE__, 'eventMapping.js');
        $returnStr .= $this->innerGetModuleDefinitionTag($filePath, false);
        
        // Add module (component.js)
        $filePath = $this->getJSPath(__FILE__, 'component.js');
        $returnStr .= $this->innerGetModuleDefinitionTag($filePath, false, true);

        if(\Mode::isDevelopment())
            $returnStr .= "<!-- ************* End Default Modules ************* -->" . PHP_EOL . PHP_EOL;

        return $this->indentText($returnStr);
    }

    /**
     * @param $classFilePath
     * @param $fileName
     *
     * @return string
     */
    public function getJSPath($classFilePath, $fileName) {
        $relativePath =
            DIRECTORY_SEPARATOR . 'templates' .
            DIRECTORY_SEPARATOR . 'js' .
            DIRECTORY_SEPARATOR . $fileName
        ;

        if($classFilePath == __FILE__)
            $relativePath = $fileName;

        // get the absolute path to the JS file (remove the extension from the class file path)
        $jsFilePath = preg_replace('/.[^.]+$/', '', $classFilePath) . DIRECTORY_SEPARATOR . $relativePath;

        return $jsFilePath;
    }

    /**
     * @param string $moduleFilePath
     * @param bool   $isLink
     *
     * @param bool   $hasDomAccess
     * @param string $libSrcFilePath
     *
     * @return string
     */
    public function getJSModuleDefinitionScriptTag($moduleFilePath, $isLink = false, $hasDomAccess = false, $libSrcFilePath = '') {
        return $this->innerGetModuleDefinitionTag($moduleFilePath, $isLink, $hasDomAccess, $libSrcFilePath);
    }

    /**
     * @param $divId
     * @param $parentDivId
     * @param $moduleName
     * @param $actionURLs
     * (@param ...$instanceParams - any parameters needed for instantiating the JS component)
     *
     * @return string
     */
    public function getJSModuleInstantiatorScriptTag($divId, $parentDivId, $moduleName, $actionURLs) {

        // implement pseudo-splat operator (...$instanceParams) by getting all parameters and removing the 3 we already defined above
        $instanceParams = func_get_args();
        for($i = 0; $i < 4; $i++) array_shift($instanceParams);

        $contents = $this->getJSModuleInstantiatorScriptText($moduleName, $divId, $parentDivId, $actionURLs, $instanceParams);
        $contents = $this->indentText($contents);

        return PHP_EOL . '<script class="instantiator" id="' . $divId . '-instantiator">' . PHP_EOL . $contents . PHP_EOL . '</script>' . PHP_EOL;

    }



    // ******* Private Methods *******

    /**
     * @param        $moduleFilePath
     * @param bool   $isLink
     * @param bool   $hasDomAccess
     * @param string $libSrcFilePath
     *
     * @return string
     */
    private function innerGetModuleDefinitionTag($moduleFilePath, $isLink = false, $hasDomAccess = false, $libSrcFilePath = '') {
        $fileName = $this->getFileNameFromPath($moduleFilePath);
        $noExtFileName = $this->replaceFileExtension($fileName);
        $noExtFileName = str_replace('Module', '', $noExtFileName);

        if(!JSModuleManager::$moduleList[$noExtFileName]) {
            // We don't already have a module defined...

            if($isLink) {

                // TODO: if isLinked, create a new link (that, when requested, acts like a "dispatcher"?)
                return '';

            } else {

                // Get the file's contents and wrap with the appropriate tag(s) and JS logic
                $contents = $this->indentText($this->getJSModuleDefinitionScriptText($moduleFilePath, $noExtFileName, $hasDomAccess, $libSrcFilePath));
                $scriptOuterHTML = '<script class="module" id="' . $noExtFileName . '-module">' . PHP_EOL . $contents . PHP_EOL . '</script>' . PHP_EOL;
                $scriptOuterHTML = $this->indentText($scriptOuterHTML);

                return $scriptOuterHTML;
            }

        } else {
            // We already have a module defined...

            error_log($noExtFileName . ' module already defined');
            return '';

        }
    }

    /**
     * Provides the text for a JS module script. If $libSrcFilePath is provided, it will merge a library JS file into a JS module "wrapper" file at {{code}}
     *
     * @param string $filePath       - Module JS file path
     * @param string $moduleName     - Module name (used by other scripts as require(moduleName))
     * @param bool   $hasDomAccess   -
     * @param string $libSrcFilePath - Library JS file path
     *
     * @return string
     */
    private function getJSModuleDefinitionScriptText($filePath, $moduleName, $hasDomAccess = false, $libSrcFilePath = '') {

        // If we are wrapping a library, $contents is the result of inserting one JS file into another
        if (strlen($libSrcFilePath) > 0) {
            $libraryContents = $this->getFileContents($libSrcFilePath);
            $contents = $this->getFileContents($filePath);

            // Replace {{code}} (couldn't use str_replace or preg_replace because of escape characters existing in files)
            $placeholder = '{{code}}';
            $placeholderPos = strpos($contents, $placeholder);
            $preCode = substr($contents, 0, $placeholderPos);
            $postCode = substr($contents, $placeholderPos + strlen($placeholder));

            $contents = $this->indentText($preCode . $libraryContents . $postCode);
        } else {
            $contents = $this->indentText($this->getFileContents($filePath));
        }

        if($hasDomAccess) {
            return "define('$moduleName', function(module, window, document, define) {\n$contents\n}, true);";
        } else {
            return "define('$moduleName', function(module, window, document, define) {\n$contents\n});";
        }
    }

    /**
     * @param $moduleName
     * @param $divId
     * @param $parentDivId
     * @param $actionURLs
     * @param $instanceParams
     *
     * @return string
     */
    private function getJSModuleInstantiatorScriptText($moduleName, $divId, $parentDivId, $actionURLs = [], $instanceParams) {

        $divIdQuoted = $divId === 'component_' ? 'null' : "'" . $divId . "'";
        $parentDivIdQuoted = $parentDivId === 'component_' ? 'null' : "'" . $parentDivId . "'";
        $actionURLsStr = $this->convertToJSON($actionURLs, 'object');

        $instanceParamStr = '';
        foreach($instanceParams as $singleParam) {
            $instanceParamStr .= ', ' . $this->convertToJSON($singleParam);
        }

        return $this->wrapInClosure(
            "var Constructor = require('$moduleName', document);" . PHP_EOL .
			"new Constructor($divIdQuoted, $parentDivIdQuoted, $actionURLsStr$instanceParamStr);"
        );
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    private function getFileContents($filePath) {
        $contents = file_get_contents($filePath);
        if($contents == false && \Mode::isDevelopment()) {
            die('JSModuleManager could not find JS file at ' . $filePath);
        } elseif ($contents == false) {
            $contents = '';
        }

        return $contents;
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    private function getFileNameFromPath($filePath) {
        // Extract file name from $filePath (if found, otherwise set to empty string)
        preg_match("/[^\\/]+$/", $filePath, $matches);

        return isset($matches[0]) ? $matches[0] : '';
    }

    /**
     * @param $codeString
     *
     * @return string
     */
    private function wrapInClosure($codeString) {
        if(strlen($codeString) > 0) {
            $codeString = $this->indentText($codeString);
            return '(function() {' . PHP_EOL . $codeString . PHP_EOL . '})();';
        } else {
            return '';
        }
    }

    /**
     * @param string $fileName
     * @param string $replacement
     *
     * @return string
     */
    private function replaceFileExtension($fileName, $replacement = '') {
        return preg_replace('/.[^.]+$/', $replacement, $fileName);
    }

    /**
     * @param string $text
     * @param int    $multiplier
     *
     * @return string
     */
    private function indentText($text, $multiplier = 1) {
        $indent = str_repeat("    ", $multiplier);
        return preg_replace('/^/m', $indent, $text);
    }

    /**
     * @param        $var
     *
     * @param string $nullType == 'null' || 'object' || 'array'
     *
     * @return string
     */
    private function convertToJSON($var, $nullType = 'null') {
        $returnStr = '';

        if(!$var) {
            switch($nullType) {
                case 'null':
                    $returnStr = 'null';
                    break;
                case 'object':
                    $returnStr = '{}';
                    break;
                case 'array':
                    $returnStr = '[]';
                    break;
            }
        } else if(method_exists($var, 'toJson')) {
            $returnStr = $var->toJson();
        } else if(is_array($var) && !$this->isAssociativeArray($var)) {
            $innerStr = '';
            foreach($var as $subItem) {
                $innerStr .= ', ' . json_encode($subItem);
            }
            $innerStr = substr($innerStr, 2);

            $returnStr .= '[' . $innerStr . ']';
        } else {
            $returnStr .= json_encode($var);
        }

        return $returnStr;
    }

    /**
     * @param $arr
     *
     * @return bool
     */
    private function isAssociativeArray($arr) {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
    
}