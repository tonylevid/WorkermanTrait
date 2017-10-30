<?php

namespace WorkermanTrait;

use WorkermanTrait\Helper;

/**
 * Workerman Service Trait
 * <p>
 * 每一个控制器都可以定义多个服务，默认服务为static::$defaultServiceWorker，即 'default'。<br>
 * 声明一个以static::$servicePrefix（即 'service'）开头的方法即可定义一项服务。<br>
 * 如 public function serviceFoo()，则定义了一项名称为 'Foo' 的服务，并且此方法会在Worker实例化前自动调用。
 * </p>
 * <p>
 * 下面的钩子函数和Worker回调函数是默认服务的快捷方式。<br>
 * 如何对每一项定义的服务定义钩子函数和Worker回调函数呢？<br>
 * <pre>
 * public function setFooWorkerAfterInited(\Workerman\Worker $worker);
 * public function onFooWorkerStart(\Workerman\Worker $worker);
 * ...
 * </pre>
 * 如上述代码所示，只需将首字母大写的服务名称放入合适的位置即可。
 * </p>
 * <p>
 * 若既定义了快捷方式方法，又定义了全称方法：
 * <pre>
 * public function onWorkerStart(\Workerman\Worker $worker);
 * public function onDefaultWorkerStart(\Workerman\Worker $worker);
 * </pre>
 * 那么只会使用全称方法（如上述代码的 onDefaultWorkerStart）。
 * </p>
 * 
 * @method void setWorkerAfterInited(\Workerman\Worker $worker) 钩子函数：在Worker实例化后触发
 * @method void onWorkerStart(\Workerman\Worker $worker) 设置Worker启动时的回调函数
 * @method void onConnect(\Workerman\Connection\TcpConnection $connection) 当客户端与Worker建立连接时(TCP三次握手完成后)触发的回调函数
 * @method void onMessage(\Workerman\Connection\TcpConnection $connection, mixed $data) 当客户端通过连接发来数据时(Worker收到数据时)触发的回调函数
 * @method void onClose(\Workerman\Connection\TcpConnection $connection) 当客户端连接与Worker断开时触发的回调函数
 * @method void onError(\Workerman\Connection\TcpConnection $connection, int $code, string $msg) 当客户端连接发生错误时触发的回调函数
 * @method void onBufferFull(\Workerman\Connection\TcpConnection $connection) 当应用层发送缓冲区大小超过\Workerman\Connection\TcpConnection::$maxSendBufferSize上限时触发的回调函数
 * @method void onBufferDrain(\Workerman\Connection\TcpConnection $connection) 当应用层发送缓冲区数据全部发送完毕后触发的回调函数
 * @method void onWorkerStop(\Workerman\Worker $worker) 设置Worker停止时的回调函数
 * @method void onWorkerReload(\Workerman\Worker $worker) 设置Worker收到reload信号后执行的回调函数
 * 
 * @author Tony
 */
Trait Service {
    
    /**
     * workerman重定向标准输出日志文件
     * @var string
     */
    public static $workerStdoutFile = './data/logs/workerman/stdout.log';
    
    /**
     * workerman自身相关的日志文件
     * @var string
     */
    public static $workerLogFile = './data/logs/workerman/workerman.log';
    
    /**
     * 不需要初始化的服务
     * @var array
     */
    public static $diffWorkers = [];
    
    /**
     * 默认服务Worker名称
     * @var string
     */
    public static $defaultServiceWorker = 'default';
    
    /**
     * Worker服务名前缀
     * @var string
     */
    public static $servicePrefix = 'service';

    /**
     * Worker实例数组
     * <pre>
     * 格式如下：
     * array(
     *     'default' => \Workerman\Worker $instance,
     *     ...
     * )
     * </pre>
     * @var \Workerman\Worker[]
     */
    protected $workers;
    
    /**
     * 监听地址数组
     * <pre>
     * 格式如下：
     * array(
     *     'default' => 'text://0.0.0.0:5678',
     *     ...
     * )
     * </pre>
     * @var string[]
     */
    protected $listens = [];
    
    /**
     * socket的上下文选项数组
     * <pre>
     * 键值参见：http://php.net/manual/zh/context.socket.php
     * 格式如下：
     * array(
     *     'default' => array(
     *          'socket' => array(
     *              'bindto' => '0:7000',
     *          ),
     *      ),
     *     ...
     * )
     * </pre>
     * @var array[]
     */
    protected $contexts = array();
    
    /**
     * 是否已设置workerman日志信息
     * @var bool 
     */
    private $workerLogsSetted = false;
    
    /**
     * 运行
     */
    public function run() {
        $this->checkEnv();
        $this->initWorker();
    }
    
    /**
     * 检查环境
     * @throws Exception
     */
    protected function checkEnv() {
        $errMsgs = array();
        if (strpos(strtolower(PHP_OS), 'win') === 0) {
            $errMsgs[] = "Do not support windows!";
        }
        if (!extension_loaded('pcntl')) {
            $errMsgs[] = "Please install pcntl extension. See http://doc3.workerman.net/appendices/install-extension.html";
        }
        if (!extension_loaded('posix')) {
            $errMsgs[] = "Please install posix extension. See http://doc3.workerman.net/appendices/install-extension.html";
        } 
        if (!empty($errMsgs)) {
            Helper::println("Service environment check failed! Error messages: ");
            foreach ($errMsgs as $i => $errMsg) {
                $printMsg = ($i+1) . '.' . $errMsg;
                Helper::println($printMsg);
            }
            exit("Please fix the issues above.");
        }
        Helper::println("Service environment check passed!");
    }
    
    /**
     * 获取当前类已定义的服务名
     * @return array
     */
    protected function getServices() {
        $services = array_filter([static::$defaultServiceWorker]); // 如果为空则删除默认服务Worker
        $methods = get_class_methods($this);
        if (is_array($methods) && !empty($methods)) {
            foreach ($methods as $method) {
                if (stripos($method, static::$servicePrefix) !== 0) {
                    continue;
                }
                $service = substr($method, strlen(static::$servicePrefix));
                if (!Helper::strInArray($service, $services, true)) {
                    $services[] = $service;
                }
            }
        }
        return $services;
    }

    /**
     * 初始化Worker
     */
    protected function initWorker() {
        $this->setWorkerLogs();
        $services = $this->getServices();
        $diffWorkers = array_filter(static::$diffWorkers);
        foreach ($services as $service) {
            // services not need to be inited
            if (Helper::strInArray($service, $diffWorkers, true)) {
                continue;
            }
            // before init
            $this->invokeServiceBeforeWorkerInited($service);
            // init
            $serviceListen = isset($this->listens[$service]) ? $this->listens[$service] : '';
            $serviceContext = isset($this->contexts[$service]) ? $this->contexts[$service] : array();
            $this->workers[$service] = new \Workerman\Worker($serviceListen, $serviceContext);
            // after init
            $this->setHookAfterWorkerInited($this->workers[$service], $service);
            $this->setWorkerCallbacks($this->workers[$service], $service);
        }
        $this->runWorker();
    }
    
    /**
     * 设置workerman日志信息
     */
    protected function setWorkerLogs() {
        if (!$this->workerLogsSetted) {
            Helper::createPath(dirname(static::$workerStdoutFile));
            Helper::createPath(dirname(static::$workerLogFile));
            \Workerman\Worker::$stdoutFile = static::$workerStdoutFile;
            \Workerman\Worker::$logFile = static::$workerLogFile;
            $this->workerLogsSetted = true;
        }
    }
    
    /**
     * 在Worker实例化前调用以static::$servicePrefix开头的方法
     * @param string $service 服务名
     */
    protected function invokeServiceBeforeWorkerInited($service) {
        $method = static::$servicePrefix . $service;
        if (method_exists($this, $method)) {
            call_user_func_array(array($this, $method), []);
        }
    }

    /**
     * 设置在Worker实例化后触发的钩子函数
     * @param \Workerman\Worker $serviceWorker 服务Worker实例
     * @param string $serviceName 服务名称
     */
    protected function setHookAfterWorkerInited($serviceWorker, $serviceName) {
        $serviceWorker->name = $serviceName;
        $callback = 'setWorkerAfterInited';
        $realCallback = 'set' . ucfirst($serviceName) . substr($callback, strlen('set'));
        // 设置默认服务Worker回调快捷方式
        if ($serviceName === static::$defaultServiceWorker && method_exists($this, $callback) && !method_exists($this, $realCallback)) {
            $realCallback = $callback;
        }
        if (method_exists($this, $realCallback)) {
            call_user_func_array(array($this, $realCallback), array($serviceWorker));
        }
    }

    /**
     * 设置Worker回调事件
     * @param \Workerman\Worker $serviceWorker 服务Worker实例
     * @param string $serviceName 服务名称
     */
    protected function setWorkerCallbacks($serviceWorker, $serviceName) {
        $validCallbacks = array(
            'onWorkerStart', 'onConnect', 'onMessage', 
            'onClose', 'onError', 'onBufferFull', 
            'onBufferDrain', 'onWorkerStop', 'onWorkerReload'
        );
        foreach ($validCallbacks as $callback) {
            $realCallback = 'on' . ucfirst($serviceName) . substr($callback, strlen('on'));
            // 设置默认服务Worker回调快捷方式
            if ($serviceName === static::$defaultServiceWorker && method_exists($this, $callback) && !method_exists($this, $realCallback)) {
                $realCallback = $callback;
            }
            if (method_exists($this, $realCallback)) {
                $serviceWorker->{$callback} = array($this, $realCallback);
            }
        }
    }

    /**
     * 运行所有Worker实例
     */
    protected function runWorker() {
        \Workerman\Worker::runAll();
    }
    
    /**
     * 停止当前进程（子进程）的所有Worker实例并退出
     */
    protected function stopWorker() {
        \Workerman\Worker::stopAll();
    }
    
}