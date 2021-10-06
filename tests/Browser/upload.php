<?php
require_once ("../../plugin/autoload.php");

$logFile        = "upload.log";
$bodyFile       = "upload.body";
$uploadFile     = "upload.pdf";
file_put_contents($logFile, "-----".PHP_EOL, FILE_APPEND);
file_put_contents($logFile, print_r($_REQUEST, true).PHP_EOL, FILE_APPEND);
/*
$request = (new HttpMessageAdapter)->getServerRequestFromGlobals(
    new HttpStreamFactory()
);
*/
function handleBody(string $bodyFile, string $logFile) {
    $inputHandler   = fopen('php://input', "r");
    $fileHandler    = fopen($bodyFile, "w+");
    while(true) {
        $buffer = fgets($inputHandler, 4096);
        file_put_contents($logFile, "body bufferSize: ".strlen($buffer).PHP_EOL, FILE_APPEND);
        if (strlen($buffer) === 0) {
            fclose($inputHandler);
            fclose($fileHandler);
            return true;
        }
        fwrite($fileHandler, $buffer);
    }
}
function handleFiles(string $uploadFile, string $logFile) {
    $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
    $chunks= isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
    foreach ($_FILES as $FILE) {
        file_put_contents($logFile, print_r($FILE, true).PHP_EOL, FILE_APPEND);
        $targetFile = basename($FILE["tmp_name"]) . "." . $uploadFile;
        handleSingleFile($logFile, $FILE["tmp_name"], $targetFile, $chunk, $chunks);
    }
}
function handleSingleFile(string $logFile, string $sourceFile, string $targetFile, int $chunk, int $chunks) : bool {
    $bufferSize     = 1024;
    $targetFilePart = "{$targetFile}.part";
    $out            = @fopen($targetFilePart, $chunk === 0 ? "wb" : "ab");
    if ($out) {
        $in = @fopen($sourceFile, "rb");
        if ($in) {
            while ($buff = fread($in, $bufferSize)) {
                file_put_contents($logFile, "write to $targetFilePart with size $bufferSize".PHP_EOL, FILE_APPEND);
                fwrite($out, $buff);
            }
        } else {
            return false;
            // problem
        }
        @fclose($in);
        @fclose($out);
        @unlink($sourceFile);
    } else {
        return false;
        // problem
    }
    if (!$chunks || $chunk === $chunks -1) {
        rename("{$targetFile}.part", "{$targetFile}");
    }
    return true;
}
//handleBody($bodyFile, $logFile);
handleFiles($uploadFile, $logFile);
