<?php

namespace Yanyabo\SymfonyBundle\Controller;

use Yanyabo\SymfonyBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AdminController extends Controller
{
    /**
     * 编辑新建等的 route
     */
    public $route = [];

    /**
     * 自定义 controller 对应的 entity
     */
    public $entity = '';

    /**
     * 自定义 controller 对应的 formType
     */
    public $formType = '';

    /**
     * 设置 controller 对应的 route
     */
    public function __contruct()
    {
        $nameSpace = explode('\\', get_class($this));
        $className = strtolower(array_pop($nameSpace));
        $className = substr($className, 0 ,strlen($className)-10);

        $this->route = ['all'    => $className.'_all',
                        'edit'   => $className.'_edit',
                        'update' => $className.'_update',
                        'new'    => $className.'_new',
                        'create' => $className.'_create'];
    }

    public function newAction()
    {
        $form = $this->createForm(new $this->formType(), new $this->entity());

        return $this->response('new', ['form' => $form->createView()]);
    }

    public function createAction(Request $request)
    {
        $entity = new $this->entity();
        $form   = $this->createForm(new $this->formType(), $entity);

        $form->bind($request);

        if ($form->isValid())
        {
            $this->save($this->bind($entity, $form));

            return $this->redirect($this->generateUrl($this->route['edit'], ['id' => $entity->getId()]));
        }
        else
        {
            $this->flash('error', '数据错误，保存失败');

            return $this->response('new', ['form' => $form->createView()]);
        }
    }

    public function editAction($id)
    {
        $entity = $this->find($id);
        $form   = $this->createForm(new $this->formType(), $entity);

        return $this->response('edit', ['entity' => $entity, 'form'   => $form->createView()]);
    }

    public function updateAction($id)
    {
        $request = $this->getRequest();
        $entity  = $this->find($id);
        $form    = $this->createForm(new $this->formType(), $entity);

        $form->bind($request);
        
        $form->isValid() ?
            $this->save($this->bind($entity)) :
            $this->flash('error', '没保存成功');

        return $this->redirect($this->generateUrl($this->route['edit'], ['id' => $entity->getId()]));
    }

    public function deleteAction($id)
    {
        $this->remove($this->find($id));

        return $this->redirect($this->generateUrl($this->route['all']));
    }

    /**
     * 方便保存的时候对 entity 进行额外操作
     */
    protected function bind($entity, $form = null)
    {
        return $entity;
    }

    /**
     * 简化的查找操作
     */
    protected function find($id)
    {
        $nameSpace = explode('\\', get_class($this));
        $className = array_pop($nameSpace);
        $className = substr($className, 0 ,strlen($className)-10);
        $modelName = $className.'Model';

        $entity = $this->$modelName->find($id);

        if (!$entity) throw $this->notFound();

        return $entity;
    }

    protected function remove($entity)
    {
        parent::remove($entity);
        $this->flash('success', '删除成功了！[]~(￣▽￣)~*');
    }

    protected function save($entity)
    {
        $em = $this->getDoctrine()->getManager();

        $em->persist($entity);
        $em->flush();

        $this->flash('success', '保存成功了！＜（￣︶￣）／');

        return $entity;
    }
}