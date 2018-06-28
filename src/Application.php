<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\worker;

use think\App;
use Workerman\Protocols\Http;

/**
 * Worker应用对象
 */
class Application extends App
{
    /**
     * 处理Worker请求
     * @access public
     * @param  \Workerman\Connection\TcpConnection   $connection
     * @param  void
     */
    public function worker($connection)
    {
        try {
            ob_start();
            // 重置应用的开始时间和内存占用
            $this->beginTime = microtime(true);
            $this->beginMem  = memory_get_usage();

            // 销毁当前请求对象实例
            $this->delete('think\Request');

            $pathinfo = ltrim(strpos($_SERVER['REQUEST_URI'], '?') ? strstr($_SERVER['REQUEST_URI'], '?', true) : $_SERVER['REQUEST_URI'], '/');

            $this->request->setPathinfo($pathinfo);

            // 更新请求对象实例
            $this->route->setRequest($this->request);

            $response = $this->run();
            $response->send();
            $content = ob_get_clean();

            foreach ($response->getHeader() as $name => $val) {
                // 发送头部信息
                Http::header($name . (!is_null($val) ? ':' . $val : ''));
            }

            $connection->send($content);
        } catch (\Exception $e) {
            $connection->send($e->getMessage());
        } catch (\Throwable $e) {
            $connection->send($e->getMessage());
        }
    }

}
