<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Model;

use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\ClientEvent;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\ApiBundle\Entity\Client;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class ClientModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model
 */
class ClientModel extends FormModel
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        $this->repository     = 'MauticApiBundle:Client';
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
        if (!$entity instanceof Client) {
            throw new NotFoundHttpException('Entity must be of class Client()');
        }

        $params = (!empty($action)) ? array('action' => $action) : array();
        return $this->container->get('form.factory')->create('client', $entity, $params);
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
            return new Client();
        }

        return parent::getEntity($id);
    }


    /**
     *  {@inheritdoc}
     *
     * @param      $action
     * @param      $entity
     * @param bool $isNew
     * @param      $event
     * @throws \Symfony\Component\HttpKernel\NotFoundHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    {
        if (!$entity instanceof Client) {
            throw new NotFoundHttpException('Entity must be of class Client()');
        }

        if (empty($event)) {
            $event      = new ClientEvent($entity, $isNew);
            $event->setEntityManager($this->em);
        }

        $dispatcher = $this->container->get('event_dispatcher');
        switch ($action) {
            case "post_save":
                $dispatcher->dispatch(ApiEvents::CLIENT_POST_SAVE, $event);
                break;
            case "post_delete":
                $dispatcher->dispatch(ApiEvents::CLIENT_POST_DELETE, $event);
                break;
        }

        return $event;
    }

    public function getUserClients(User $user)
    {
        return $this->em->getRepository($this->repository)->getUserClients($user);
    }
}