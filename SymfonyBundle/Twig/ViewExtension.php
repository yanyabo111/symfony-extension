<?php

namespace Yanyabo\SymfonyBundle\Twig;

class ViewExtension extends \Twig_Extension
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        return ['view' => new \Twig_Function_Method($this, 'getView')];
    }

    /**
     * 页面有些部分的生成需要大量资源，更新又不频繁，这里用特定名称的方法生成这部分的 view， 然后存到 redis 里面
     *
     * @param string $name    包含 controller 名称和相应方法名称的字符串，比如"Default:website" 调用 DefaultCotroller 里面的 websiteView 方法
     * @param int    $seconds redis 的缓存时间
     * @return string         生成的 view，就是原始的 html 代码
     */
    public function getView($name, $seconds = 2)
    {
        $redis = $this->container->get('snc_redis.default');
        if ($cache = $redis->get($name)) return $cache;

        $forward = explode(':', $name);
        
        $controller = "\\$forward[0]\\$forward[1]\\Controller\\".ucfirst($forward[2]).'Controller';
        $controller = new $controller();
        $controller->setContainer($this->container);

        $method = strtolower($forward[3]).'View';
        $view   = $controller->$method();

        $redis->setex($name, $seconds, $view);

        return $view;
    }

    public function getName()
    {
        return 'view_extension';
    }
}