# WorkermanTrait
Workerman Service Trait

###代码示例

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
如上诉代码所示，此扩展可以非常方便的集成至MVC框架。

###使用说明

每一个类都可以定义多个服务，默认服务为static::$defaultServiceWorker，即 'default'。

声明一个以static::$servicePrefix（即 'service'）开头的方法即可定义一项服务，
如 public function serviceFoo()，则定义了一项名称为 'Foo' 的服务，并且此方法会在Worker实例化前自动调用。

下面的钩子函数和Worker回调函数是默认服务的快捷方式。
    
    // 钩子函数：在Worker实例化后触发
    public function setWorkerAfterInited(\Workerman\Worker $worker)
    
    // 设置Worker启动时的回调函数
    public function onWorkerStart(\Workerman\Worker $worker)
    
    // 当客户端与Worker建立连接时(TCP三次握手完成后)触发的回调函数
    public function onConnect(\Workerman\Connection\TcpConnection $connection)
    
    // 当客户端通过连接发来数据时(Worker收到数据时)触发的回调函数
    public function onMessage(\Workerman\Connection\TcpConnection $connection, mixed $data)
    
    // 当客户端连接与Worker断开时触发的回调函数
    public function onClose(\Workerman\Connection\TcpConnection $connection)
    
    // 当客户端连接发生错误时触发的回调函数
    public function onError(\Workerman\Connection\TcpConnection $connection, int $code, string $msg)
    
    // 当应用层发送缓冲区大小超过\Workerman\Connection\TcpConnection::$maxSendBufferSize上限时触发的回调函数
    public function onBufferFull(\Workerman\Connection\TcpConnection $connection)
    
    // 当应用层发送缓冲区数据全部发送完毕后触发的回调函数
    public function onBufferDrain(\Workerman\Connection\TcpConnection $connection)
    
    // 设置Worker停止时的回调函数
    public function onWorkerStop(\Workerman\Worker $worker)
    
    // 设置Worker收到reload信号后执行的回调函数
    public function onWorkerReload(\Workerman\Worker $worker)

如何对每一项定义的服务定义钩子函数和Worker回调函数呢？

    public function setFooWorkerAfterInited(\Workerman\Worker $worker);
    public function onFooWorkerStart(\Workerman\Worker $worker);
    
如上述代码所示，只需将首字母大写的服务名称放入合适的位置即可。
若既定义了快捷方式方法，又定义了全称方法：

    public function onWorkerStart(\Workerman\Worker $worker);
    public function onDefaultWorkerStart(\Workerman\Worker $worker);

那么只会使用全称方法（如上述代码的 onDefaultWorkerStart）。