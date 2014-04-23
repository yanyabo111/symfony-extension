<?php

namespace Yanyabo\SymfonyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 扩展 Symfony 自带 controller 的功能
 */
class Controller extends BaseController
{
    /**
     * 用于获取相应地 model, 跟 repository 的用法一致，为了更好地进行代码隔离，就不用公共的 repository 了
     *
     * @param string $name model 的名字，通常 model 的名字类似 userModel
     * @return model       一个实例化的 model 对象
     */
    public function __get($name)
    {
        $entityName = substr($name, 0, strlen($name) - 5);

        $nameSpace = explode('\\', get_class($this));
        $modelName = '\\'.$nameSpace[0].'\\'.$nameSpace[1].'\\Model\\'.ucfirst($entityName).'Model';

        return new $modelName($this->container);
    }

    /**
     * 返回 render 生成的 html 代码，而不是 response，方便缓存
     *
     * @param string $fileName twig 的文件名，基本上都是当前 action 的名字
     * @param array  $vars     传到 twig 里面的数据
     * @return response        html 代码
     */
    protected function view($fileName, $vars = [])
    {
        $nameSpace = explode('\\', get_class($this));
        $className = array_pop($nameSpace);

        $twigFile = $nameSpace[0].$nameSpace[1].
                        ':'.substr($className, 0 ,strlen($className)-10).
                        ':'.$fileName.'.html.twig';

        return $this->renderView($twigFile, $vars);
    }

    /**
     * 由于几乎所有 controller 进行 render 的时候，twig 文件名和位置都是固定的，这里就弄一个别名方法，简化废话
     *
     * @param string $fileName twig 的文件名，基本上都是当前 action 的名字
     * @param array  $vars     传到 twig 里面的数据
     * @param int    $maxAge   httpCache 的缓存时间，浏览器和 nginx 会根据这个时间进行缓存
     * @return response        标准的 symfony response 
     */
    protected function response($fileName, $vars = [], $maxAge = null)
    {
        $nameSpace = explode('\\', get_class($this));
        $className = array_pop($nameSpace);

        $twigFile = $nameSpace[0].$nameSpace[1].
                        ':'.substr($className, 0 ,strlen($className)-10).
                        ':'.$fileName.'.html.twig';

        $response = $this->render($twigFile, $vars);

        $maxAge && $response->setCache(['max_age' => $maxAge, 'public'  => true]);
            
        return $response;
    }

    /**
     * 这个 flash 消息常用，弄了个简单地别名
     *
     * @param string $tag     消息的标识符
     * @param string $message 消息的具体内容
     */
    protected function flash($tag, $message)
    {
        $this->get('session')->getFlashBag()->set($tag, $message);
    }

    /**
     * json response
     */
    protected function json($data, $maxAge = 60)
    {
        $response = new \Symfony\Component\HttpFoundation\JsonResponse($data);

        $response->setCache(['max_age' => $maxAge, 'public' => true]);

        return $response;
    }

    /**
     * createNotFoundException 这个名字的长度太惊人了
     *
     * @param string     $message  可以在 404 页面显示的消息
     * @param \Exception $previous 不知道干嘛的，反正没用过
     * @return response            400页面
     */
    protected function notFound($message = 'Not Found', \Exception $previous = null)
    {
        return new NotFoundHttpException($message, $previous);
    }

    /**
     * 一个删除操作的简化方法
     */
    protected function remove($entity)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($entity);
        $em->flush();
    }

    /**
     * 一个保存操作的简化方法
     */
    protected function save($entity)
    {
        $em = $this->getDoctrine()->getManager();

        $em->persist($entity);
        $em->flush();

        return $entity;
    }
}