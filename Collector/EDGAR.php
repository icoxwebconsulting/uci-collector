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
    const HOST = 'ftp.sec.gov';

    /**
     * @var string
     */
    private $downloadDirectory = 'download';

    /**
     * @var
     */
    private $ftp;

    /**
     * EDGAR constructor.
     */
    public function __construct()
    {
        $connection = new AnonymousConnection(EDGAR::HOST, $port = 21, $timeout = 900, $passive = true);
        $factory = new FTPFactory();
        $this->ftp = $factory->build($connection);
    }

    /**
     * Get dirs
     *
     * @param string $directory
     * @return array
     */
    public function listDirs(string $directory):array
    {
        $list = $this->ftp->findFilesystems(new Directory($directory));
        return array_reduce($list, function ($carry, $current) {
            if ($current instanceof Directory) {
                $carry[] = $current->getRealpath();
            }
            return $carry;
        }, array());
    }

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
     * @throws \Exception
     */
    private function get(string $fileName, string $inDirectory = '/')
    {
        $this->cleanUpDirectory($this->downloadDirectory);
        $file = $this->ftp->findFileByName($fileName, new Directory($inDirectory));
        if ($file) {
            $localFile = sprintf('%s/%s', $this->downloadDirectory, $fileName);
            $this->ftp->download($localFile, $file);
        } else {
            throw new \Exception('File not found');
        }
    }

    /**
     * @param string $fileName
     * @return array
     * @throws \Exception
     */
    private function readFile(string $fileName):array
    {
        $fileFullName = sprintf('%s/%s.idx', $this->downloadDirectory, $fileName);
        $handle = fopen($fileFullName, "r");
        if ($handle) {
            $content = array();
            while (($line = fgets($handle)) !== false) {
                if (strlen(trim($line))) {
                    $content[] = $line;
                }
            }
            fclose($handle);
            return $content;
        } else {
            throw new \Exception(sprintf('Error reading file %s', $fileFullName));
        }
    }

    /**
     * @param array $content
     * @return array
     */
    private function parseIDXMeta(array $content):array
    {
        $meta = array();
        foreach ($content as $line) {
            $data = explode(': ', $line);
            if (count($data) > 1) {
                $meta[trim($data[0])] = trim($data[1]);
            }
        }
        return $meta;
    }

    /**
     * @param array $content
     * @return array
     */
    private function parseIDXData(array $content):array
    {
        $data = array();
        $content = array_slice($content, 2);
        foreach ($content as $line) {
            $companyUnformattedData = explode('  ', $line);
            $companyParsedData = array(
                'companyName' => array(),
                'formType' => array(),
                'cik' => array(),
                'dateFiled' => array(),
                'fileName' => array()
            );
            $index = 0;
            foreach ($companyUnformattedData as $value) {
                if (strlen(trim($value))) {
                    switch ($index) {
                        case 0: {
                            $companyParsedData['companyName'] = $value;
                            break;
                        }
                        case 1: {
                            $companyParsedData['formType'] = $value;
                            break;
                        }
                        case 2: {
                            $companyParsedData['cik'] = $value;
                            break;
                        }
                        case 3: {
                            $companyParsedData['dateFiled'] = $value;
                            break;
                        }
                        case 4: {
                            $companyParsedData['fileName'] = $value;
                            break;
                        }
                    }
                    $index++;
                }
            }
            $data[] = $companyParsedData;
        }
        return $data;
    }

    /**
     * @param string $fileName
     * @return array
     */
    private function parseIDX(string $fileName):array
    {
        $fileContent = $this->readFile($fileName);
        $content = array();
        $content['meta'] = $this->parseIDXMeta($fileContent);
        $fileContent = array_slice($fileContent, count($content['meta']));
        $content['content'] = $this->parseIDXData($fileContent);
        return $content;
    }

    /**
     * Get zip file content from edgar database
     *
     * @param string $fileName
     * @param string $inDirectory
     * @return array
     * @throws \Exception
     */
    public function getZipContent(string $fileName, string $inDirectory = '/'):array
    {
        try {
            $zipFileName = sprintf('%s.%s', $fileName, 'zip');
            $this->get($zipFileName, $inDirectory);
            $zip = new \ZipArchive();
            $resource = $zip->open(sprintf('%s/%s', $this->downloadDirectory, $zipFileName));
            if ($resource === TRUE) {
                $zip->extractTo($this->downloadDirectory);
                $zip->close();
            } else {
                throw new \Exception('Error unziping');
            }
            $content = $this->parseIDX($fileName);
            $this->cleanUpDirectory($this->downloadDirectory);
            return $content;
        } catch (\Exception $exception) {
            return array();
        }
    }
}