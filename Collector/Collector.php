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
                print_r($data);
                die();
            }
        }
    }
}