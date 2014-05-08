<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\UserBundle\Event\RoleEvent;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


/**
 * Class RoleModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class RoleModel extends FormModel
{

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        $this->repository     = 'MauticUserBundle:Role';
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @return int
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function saveEntity($entity)
    {
        if (!$entity instanceof Role) {
            throw new NotFoundHttpException('Entity must be of class Role()');
        }

        $isNew = ($entity->getId()) ? 0 : 1;

        if (!$isNew) {
            //delete all existing
            $this->em->getRepository('MauticUserBundle:Permission')->purgeRolePermissions($entity);
        }

        return parent::saveEntity($entity);
    }

    /**
     * Generate the role's permissions
     *
     * @param Role $entity
     * @param array $rawPermissions (i.e. from request)
     */
    public function setRolePermissions(Role &$entity, array $rawPermissions)
    {
        //set permissions if applicable and if the user is not an admin
        $permissions = (!$entity->isAdmin() && !empty($rawPermissions)) ?
            $this->container->get('mautic.security')->generatePermissions($rawPermissions) :
            array();

        foreach ($permissions as $permissionEntity) {
            $entity->addPermission($permissionEntity);
        }

    }
    /**
     * {@inheritdoc}
     *
     * @param      $entityId
     * @return null|object
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function deleteEntity($entityId)
    {
        $entity = $this->em->getRepository($this->repository)->find($entityId);

        $users = $this->em->getRepository('MauticUserBundle:User')->findByRole($entity);
        if (count($users)) {
            throw new PreconditionRequiredHttpException(
                $this->container->get('translator')->trans(
                    'mautic.user.role.error.deletenotallowed',
                    array(),
                    'flashes'
                )
            );
        }

        return parent::deleteEntity($entityId);
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param null $action
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $action = null)
    {
        if (!$entity instanceof Role) {
            throw new NotFoundHttpException('Entity must be of class Role()');
        }

        $params = (!empty($action)) ? array('action' => $action) : array();
        return $this->container->get('form.factory')->create('role', $entity, $params);
    }


    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = '')
    {
        if (empty($id)) {
            return new Role();
        }

        return parent::getEntity($id);
    }


    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $entity
     * @param $isNew
     * @param $event
     * @throws \Symfony\Component\HttpKernel\NotFoundHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    {
        if (!$entity instanceof Role) {
            throw new NotFoundHttpException('Entity must be of class Role()');
        }

        if (empty($event)) {
            $event = new RoleEvent($entity, $isNew);
            $event->setEntityManager($this->em);
        }
        $dispatcher = $this->container->get('event_dispatcher');
        switch ($action) {
            case "pre_save":
                $dispatcher->dispatch(UserEvents::ROLE_PRE_SAVE, $event);
                break;
            case "post_save":
                $dispatcher->dispatch(UserEvents::ROLE_POST_SAVE, $event);
                break;
            case "pre_delete":
                $dispatcher->dispatch(UserEvents::ROLE_PRE_DELETE, $event);
                break;
            case "post_delete":
                $dispatcher->dispatch(UserEvents::ROLE_POST_DELETE, $event);
                break;
        }

        return $event;
    }
}