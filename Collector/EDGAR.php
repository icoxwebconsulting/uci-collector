<?php

namespace Collector;

use Touki\FTP\Connection\AnonymousConnection;
use Touki\FTP\FTPFactory;
use Touki\FTP\Model\Directory;

class EDGAR
{
    /**
     * @var string
     */
    private $host = 'ftp.sec.gov';

    /**
     * @var string
     */
    private $downloadDirectory = 'download';

    /**
     * @param string $directory
     */
    private function ensureDirectory(string $directory)
    {
        if (!is_dir($directory)) {
            mkdir($directory);
        }
    }

    /**
     * @param string $directory
     */
    private function cleanUpDirectory(string $directory)
    {
        $this->ensureDirectory($directory);
        $files = glob(sprintf('%s/*', $directory));
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Get file content
     *
     * @param string $fileName
     * @param string $inDirectory
     * @return string
     */
    public function get(string $fileName, string $inDirectory = '/'):string
    {
        $content = 'UCI_EDGAR_ERROR';
        $this->cleanUpDirectory($this->downloadDirectory);
        $connection = new AnonymousConnection($this->host, $port = 21, $timeout = 900, $passive = true);
        $factory = new FTPFactory();
        $ftp = $factory->build($connection);
        $file = $ftp->findFileByName($fileName, $inDirectory = new Directory($inDirectory));
        $localFile = sprintf('%s/%s', $this->downloadDirectory, $fileName);
        $ftp->download($localFile, $file);
        $file = fopen($localFile, "r");
        if ($file) {
            $content = fread($file, filesize($localFile));
            fclose($file);
        }
        $this->cleanUpDirectory($this->downloadDirectory);
        return $content;
    }

    /**
     * Get json file
     *
     * @param string $fileName
     * @param string $inDirectory
     * @return array
     */
    public function getJSON(string $fileName, string $inDirectory = '/'):array
    {
        $content = $this->get($fileName, $inDirectory);
        $data = json_decode($content, true);
        return $data;
    }
}