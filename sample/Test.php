<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Service.php';

use \WorkermanTrait\Helper;

class Test {
    
    use \WorkermanTrait\Service;
    
    public function onWorkerStart(\Workerman\Worker $worker) {
        Helper::println("{$worker->name} counter: ");
        $i = 0;
        \Workerman\Lib\Timer::add(3, function() use(&$i) {
            Helper::println("Default: {$i}");
            $i++;
        });
    }
    
    public function serviceFoo() {
        
    }
    
    public function onFooWorkerStart(\Workerman\Worker $worker) {
        Helper::println("{$worker->name} counter: ");
        $i = 0;
        \Workerman\Lib\Timer::add(3, function() use(&$i) {
            Helper::println("Foo: {$i}");
            $i++;
        });
    }
    
}

//Test::$diffWorkers = ['foo'];
$test = new Test();
$test->run();
