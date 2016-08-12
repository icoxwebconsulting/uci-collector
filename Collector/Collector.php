<?php

namespace Collector;

class Collector
{
    public function run()
    {
        $edgar = new EDGAR();
        print_r($edgar->getJSON('index.json', 'edgar/full-index'));
    }
}