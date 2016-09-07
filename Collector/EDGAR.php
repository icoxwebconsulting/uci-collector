<?php

namespace Collector;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\DomCrawler\Crawler;
use Touki\FTP\Connection\AnonymousConnection;
use Touki\FTP\FTP;
use Touki\FTP\FTPFactory;
use Touki\FTP\Model\Directory;

class EDGAR
{
    const FTP_HOST = 'ftp.sec.gov';
    const SEC_HOST = 'https://www.sec.gov';
    const HEADER_REGEX = '/<([A-Z\\-\\/0-9]+)>([\\w.\\-: ]*)/mi';

    /**
     * @var string
     */
    private $downloadDirectory = 'download';

    /**
     * @var FTP
     */
    private $ftp;

    /**
     * @var Client
     */
    private $guzzle;

    /**
     * Get dirs
     *
     * @param string $directory
     * @return array
     */
    public function listDirs(string $directory):array
    {
        if (!$this->ftp) {
            $this->initFTP();
        }

        $list = $this->ftp->findFilesystems(new Directory($directory));

        return array_reduce(
            $list,
            function ($carry, $current) {
                if ($current instanceof Directory) {
                    $carry[] = $current->getRealpath();
                }

                return $carry;
            },
            array()
        );
    }

    /**
     * Init FTP
     */
    private function initFTP()
    {
        $connection = new AnonymousConnection(EDGAR::FTP_HOST, $port = 21, $timeout = 900, $passive = true);
        $factory = new FTPFactory();
        $this->ftp = $factory->build($connection);
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
        if (!$this->ftp) {
            $this->initFTP();
        }

        try {
            $zipFileName = sprintf('%s.%s', $fileName, 'zip');
            $this->get($zipFileName, $inDirectory);
            $zip = new \ZipArchive();
            $resource = $zip->open(sprintf('%s/%s', $this->downloadDirectory, $zipFileName));
            if ($resource === true) {
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
     * @param string $directory
     */
    private function ensureDirectory(string $directory)
    {
        if (!is_dir($directory)) {
            mkdir($directory);
        }
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
                'fileName' => array(),
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
     * Get header of file from archive
     *
     * @param string $fileName
     * @param int $iteration
     * @return array
     */
    public function getHeader(string $fileName, int $iteration = 0):array
    {
        if (!$this->guzzle) {
            $this->initGuzzle();
        }

        try {
            $url = $this->buildArchiveURL($fileName);
            if (!empty($url)) {
                $request = new Request('GET', $url);
                $response = $this->guzzle->send($request);

                return $this->parseHeader($response->getBody()->getContents());
            }

            return array();
        } catch (ServerException $exception) {
        } catch (ConnectException $exception) {
        }

        // retry 5 times
        if ($iteration < 5) {
            return $this->getHeader($fileName, ++$iteration);
        } else {
            return array();
        }
    }

    /**
     * Init Guzzle
     */
    private function initGuzzle()
    {
        $this->guzzle = new Client(
            [
                // Base URI is used with relative requests
                'base_uri' => EDGAR::SEC_HOST,
                // You can set any number of default request options.
                'timeout' => 10,
            ]
        );
    }

    /**
     * @param string $fileName
     * @return string
     */
    private function buildArchiveURL(string $fileName):string
    {
        $fileName = substr($fileName, 0, strpos($fileName, '.txt'));
        $sections = explode('/', $fileName);

        if (count($sections) === 4) {
            $path = sprintf('%s/%s/%s', $sections[2], implode('', explode('-', $sections[3])), $sections[3]);

            return sprintf('%s/Archives/edgar/data/%s.hdr.sgml', EDGAR::SEC_HOST, $path);
        }

        return '';
    }

    /**
     * @param string $raw
     * @return array
     */
    private function parseHeader(string $raw):array
    {
        // split info
        $matches = array();
        preg_match_all(EDGAR::HEADER_REGEX, $raw, $matches);
        $tags = $matches[1];
        $content = $matches[2];
        // join them
        $join = array();
        foreach ($tags as $index => $tag) {
            $join[$tag] = $content[$index];
        }
        // create tree, assume non having close tag to be self closing
        $tree = [];
        foreach ($join as $tag => $content) {
            // on closing tag
            if (strpos($tag, '/') === 0) {
                if (count($tree) > 1) {
                    $inner = array_pop($tree);
                    $containerKey = array_keys($tree[count($tree) - 1])[0];
                    $container = array_pop($tree)[$containerKey];
                    foreach ($inner as $key => $item) {
                        $container[$key] = $item;
                    }
                    $tree[][$containerKey] = $container;
                }

                // do not include closing tags
                continue;
            }

            // if has closing tag create subtree
            if (array_key_exists(sprintf('/%s', $tag), $join)) {
                $tree[] = array($tag => array());
                continue;
            }

            $tree[count($tree) - 1][array_keys($tree[count($tree) - 1])[0]][$tag] = $content;
        }

        return $tree[0];
    }

    /**
     * @return array
     */
    public function getSICCodes():array
    {
        if (!$this->guzzle) {
            $this->initGuzzle();
        }

        $request = new Request('GET', 'info/edgar/siccodes.htm');
        $response = $this->guzzle->send($request);
        $html = $response->getBody()->getContents();
        $crawler = new Crawler($html);
        $crawler = $crawler->filterXPath('//body/table[2]/tr/td[3]/font/table/tr');

        $sicCodes = array();

        foreach ($crawler as $tr) {
            $index = 0;
            $item = array();
            foreach ($tr->childNodes as $td) {
                if ($td->nodeName === 'td' &&
                    !in_array(ord($td->nodeValue), array(10, 194))
                ) {
                    $content = $td->nodeValue;
                    switch ($index) {
                        case 0: {
                            $item['code'] = $content;
                            break;
                        }
                        case 1: {
                            $item['office'] = $content;
                            break;
                        }
                        case 2: {
                            $item['title'] = $content;
                            break;
                        }
                    }
                    $index++;
                }
            }

            if (!empty($item)) {
                $sicCodes[] = $item;
            }
        }

        return array_slice($sicCodes, 3, count($sicCodes) - 4);
    }
}