<?php
/**
 * Laravel - A clean and classy framework for PHP web development.
 *
 * @package  Laravel
 * @version  1.0.0 Beta 1
 * @author   Taylor Otwell
 * @license  MIT License
 * @link     http://laravel.com 
 */

// --------------------------------------------------------------
// Set the framework starting time.
// --------------------------------------------------------------
//todo 增加流程图

//开始时间定为常量 laravel_start
define('LARAVEL_START', microtime(true));

// --------------------------------------------------------------
// Define the framework paths.
// --------------------------------------------------------------
//定义路径:项目,系统,根目录
define('APP_PATH', realpath('../application').'/');
define('SYS_PATH', realpath('../system').'/');
define('BASE_PATH', realpath('../').'/');

// --------------------------------------------------------------
// Define the PHP file extension.
// --------------------------------------------------------------
//定义文件类型,好看?
define('EXT', '.php');

// --------------------------------------------------------------
// Load the configuration and string classes.
// --------------------------------------------------------------
//引入配置 处理字符编码
require SYS_PATH.'config'.EXT;
require SYS_PATH.'str'.EXT;

// --------------------------------------------------------------
// Register the auto-loader.
// --------------------------------------------------------------
//注册自动加载类
//加载模型,package...
spl_autoload_register(require SYS_PATH.'loader'.EXT);

// --------------------------------------------------------------
// Set the Laravel starting time in the Benchmark class.
// --------------------------------------------------------------
//应用开始时间下记录方法
System\Benchmark::$marks['laravel'] = LARAVEL_START;

// --------------------------------------------------------------
// Set the error reporting level.
// --------------------------------------------------------------
//根据配置设置错误输出级别,不配置输出全部错误
error_reporting((System\Config::get('error.detail')) ? E_ALL | E_STRICT : 0);

// --------------------------------------------------------------
// Register the error handlers.
// --------------------------------------------------------------
//设置异常处理由框架的错误处理方法接管
set_exception_handler(function($e)
{
	System\Error::handle($e);	
});

//设置自定义错误处理函数
set_error_handler(function($number, $error, $file, $line) 
{
	System\Error::handle(new ErrorException($error, 0, $number, $file, $line));
});

//执行完脚本后 如果有错误 抛出处理
register_shutdown_function(function()
{
	if ( ! is_null($error = error_get_last()))
	{
		System\Error::handle(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
	}	
});

// --------------------------------------------------------------
// Set the default timezone.
// --------------------------------------------------------------
//
//注册时区 读取配置更改
date_default_timezone_set(System\Config::get('application.timezone'));

// --------------------------------------------------------------
// Load the session.
// --------------------------------------------------------------
//session可以存在文件,数据库,缓存 取决于陪配置
if (System\Config::get('session.driver') != '')
{
	System\Session::load();
}

// --------------------------------------------------------------
// Execute the global "before" filter.
// --------------------------------------------------------------
//过滤器加载
$response = System\Filter::call('before');

// --------------------------------------------------------------
// Only execute the route function if the "before" filter did
// not override by sending a response.
// --------------------------------------------------------------
//判断是否有before过滤器
//
if (is_null($response))
{
	// ----------------------------------------------------------
	// Route the request to the proper route.
	// ----------------------------------------------------------
    //route($_SERVER['REQUEST_METHOD'], )

    //拿到一个路由对象
	$route = System\Router::route(Request::method(), Request::uri());

	// ----------------------------------------------------------
	// Execute the route function.
	// ----------------------------------------------------------
    //判断是否拿到路由对象,没有则模板输出404
    if ( ! is_null($route))
    {
        //拿着路由对象调用call()方法,取得一个响应示例
        $response = $route->call();
	}
	else
	{
	    //模板输出404
		$response = System\Response::view('error/404', 404);
	}
}
else
{
    //有过滤器的,检查是否属于响应类对象,
    //是响应类对象(包含正文,状态,header的),直接返回
    //不属于响应类实例,则新建响应类实例
	$response = ( ! $response instanceof System\Response) ? new System\Response($response) : $response;
}

// ----------------------------------------------------------
// Execute the global "after" filter.
// ----------------------------------------------------------
System\Filter::call('after', array($response));

// --------------------------------------------------------------
// Close the session.
// --------------------------------------------------------------
if (System\Config::get('session.driver') != '')
{
	System\Session::close();
}

// --------------------------------------------------------------
// Send the response to the browser.
// --------------------------------------------------------------
$response->send();

//阅读$response感想 就像洋葱圈模型 把一个东西进行各种调用处理 返回他 这样子调用者的意图很清晰
//原理概念 这更符合人类的思维逻辑 类似于做一盘土豆丝 开发者手里拿着生土豆进去 用水盆,用削皮刀,用菜刀,用锅子铲子,用盘子 最后拿出来一道炒好的土豆丝
// 否则一会准备案板 一会扫地 一会开火倒油 最后洗土豆切丝入锅 看起来让人 '被水淹没不知所措'
//能用来解决身边问题 项目中用调用者的思维方式来 要拿到处理结果 可以用 结果->洗土豆->切土豆->炒土豆->给土豆配盘子->上菜炒土豆