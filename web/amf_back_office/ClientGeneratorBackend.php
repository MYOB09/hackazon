<?php
/**
 *  This file is part of amfPHP
 *
 * LICENSE
 *
 * This source file is subject to the license that is bundled
 * with this package in the file license.txt.
 * @package Amfphp_BackOffice_ClientGenerator
 */
/**
 * includes
 */
require_once(dirname(__FILE__) . '/ClassLoader.php');

// Initialize Access Manager and check access permissions
$accessManager = new Amfphp_BackOffice_AccessManager();
$isAccessGranted = $accessManager->isAccessGranted();
if (!$isAccessGranted) {
    die('User not logged in');
}

// Retrieve and decode service data
$servicesStr = null;
if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
    $servicesStr = $GLOBALS['HTTP_RAW_POST_DATA'];
} else {
    $servicesStr = file_get_contents('php://input');
}
$services = json_decode($servicesStr);

// Sanitize and validate generator class input
$generatorClass = null;
if (isset($_GET['generatorClass'])) {
    // Sanitize and whitelist allowed generator classes
    $allowedGenerators = ['ValidGenerator1', 'ValidGenerator2', 'ValidGenerator3']; // Example whitelist
    $generatorClass = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['generatorClass']); // Remove invalid characters

    if (!in_array($generatorClass, $allowedGenerators)) {
        die('Invalid generator class');
    }
} else {
    die('Generator class not provided');
}

// Load available generators
$generatorManager = new Amfphp_BackOffice_ClientGenerator_GeneratorManager();
$generators = $generatorManager->loadGenerators(array('ClientGenerator/Generators'));

// Ensure the generator exists
if (!isset($generators[$generatorClass])) {
    die('Generator not found');
}

$config = new Amfphp_BackOffice_Config();
$generator = $generators[$generatorClass];

// Safely construct folder names without using raw user data
$newFolderName = date("Ymd-His") . '-' . $generatorClass; // Include only sanitized generator class
$genRootRelativeUrl = 'ClientGenerator/Generated/';
$genRootFolder = AMFPHP_BACKOFFICE_ROOTPATH . $genRootRelativeUrl;
$targetFolder = $genRootFolder . $newFolderName;

// Generate project files
$generator->generate($services, $config->resolveAmfphpEntryPointUrl(), $targetFolder);
$urlSuffix = $generator->getTestUrlSuffix();

if ($urlSuffix !== false) {
    echo '<a target="_blank" href="' . htmlspecialchars($genRootRelativeUrl . $newFolderName . '/' . $urlSuffix) . '"> try your generated project here</a><br/><br/>';
}

if (Amfphp_BackOffice_ClientGenerator_Util::serverCanZip()) {
    $zipFileName = "$newFolderName.zip";
    $zipFilePath = $genRootFolder . $zipFileName;
    Amfphp_BackOffice_ClientGenerator_Util::zipFolder($targetFolder, $zipFilePath, $genRootFolder);
} else {
    echo "Server cannot create zip of the generated project because ZipArchive is not available.<br/><br/>";
    echo 'Client project written to ' . htmlspecialchars($targetFolder);
}
?>
