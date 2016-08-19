<?php

namespace Collector;

class Collector
{
    public function run()
    {
        $edgar = new EDGAR();
        $years = $edgar->listDirs('/edgar/full-index');
        foreach ($years as $year) {
            $quarters = $edgar->listDirs($year);
            foreach ($quarters as $quarter) {
                $data = $edgar->getZipContent('company', $quarter);
                foreach ($data['content'] as $item) {
                    print_r($edgar->getHeader($item['fileName']));
                }
                die();
            }
        }
    }
}