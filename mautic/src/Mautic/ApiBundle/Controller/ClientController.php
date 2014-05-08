<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */



namespace Mautic\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\ApiBundle\Form\Type as FormType;

/**
 * Class ClientController
 *
 * @package Mautic\ApiBundle\Controller
 */
class ClientController extends FormController
{
    /**
     * Generate's default client list
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        if (!$this->get('mautic.security')->isGranted('api:clients:view')) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->container->getParameter('mautic.default_pagelimit');
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $orderBy    = $this->get('session')->get('mautic.client.orderby', 'c.name');
        $orderByDir = $this->get('session')->get('mautic.client.orderbydir', 'ASC');
        $filter     = $this->request->get('filter-client', $this->get('session')->get('mautic.client.filter', ''));
        $this->get('session')->set('mautic.client.filter', $filter);

        $clients = $this->container->get('mautic.model.client')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($clients);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ? : 1;
            }
            $this->get('session')->set('mautic.client.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_client_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticApiBundle:Client:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_client_index',
                    'route'         => $returnUrl,
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set('mautic.client.page', $page);

        //set some permissions
        $permissions = array(
            'create' => $this->get('mautic.security')->isGranted('api:clients:create'),
            'edit'   => $this->get('mautic.security')->isGranted('api:clients:editother'),
            'delete' => $this->get('mautic.security')->isGranted('api:clients:deleteother'),
        );

        $parameters = array(
            'filterValue' => $filter,
            'items'       => $clients,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions
        );

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction(array(
                'viewParameters'  => $parameters,
                'contentTemplate' => 'MauticApiBundle:Client:index.html.php',
                'passthroughVars' => array('route' => $this->generateUrl('mautic_client_index', array('page' => $page)))
            ));
        } else {
            return $this->render('MauticApiBundle:Client:index.html.php', $parameters);
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function authorizedClientsAction()
    {
        $me      = $this->get('security.context')->getToken()->getUser();
        $clients = $this->get('mautic.model.client')->getUserClients($me);

        return $this->render('MauticApiBundle:Client:authorized.html.php', array('clients' => $clients));
    }

    public function revokeAction($clientId)
    {
        $success = 0;
        $flashes = array();
        if ($this->request->getMethod() == 'POST') {
            $me      = $this->get('security.context')->getToken()->getUser();
            $client  = $this->container->get('mautic.model.client')->getEntity($clientId);

            if ($client === null || !$client->getId()) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.api.client.error.notfound',
                    'msgVars' => array('%id%' => $clientId)
                );
            } else {
                $name = $client->getName();

                //remove the user from the client
                $client->removeUser($me);
                $this->container->get('mautic.model.client')->saveEntity($client);

                $flashes[] = array(
                    'type'    => 'notice',
                    'msg'     => 'mautic.api.client.notice.revoked',
                    'msgVars' => array(
                        '%name%' => $name
                    )
                );
            }
        }
        $returnUrl = $this->generateUrl('mautic_user_account');
        return $this->postActionRedirect(array(
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticUserBundle:Profile:index',
            'passthroughVars' => array(
                'route'         => $returnUrl,
                'success'       => $success
            ),
            'flashes'         => $flashes
        ));
    }

    /**
     * Generate's form and processes new post data
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        if (!$this->get('mautic.security')->isGranted('api:clients:create')) {
            return $this->accessDenied();
        }

        $model      = $this->container->get('mautic.model.client');
        //retrieve the entity
        $client     = $model->getEntity();
        //set the return URL for post actions
        $returnUrl  = $this->generateUrl('mautic_client_index');

        //get the user form factory
        $action     = $this->generateUrl('mautic_client_action', array('objectAction' => 'new'));
        $form       = $model->createForm($client, $action);

        //remove the client id and secret fields as they'll be auto generated
        $form->remove("randomId");
        $form->remove("secret");
        $form->remove("publicId");

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = $this->checkFormValidity($form);

            if ($valid === 1) {
                //form is valid so process the data
                $model->saveEntity($client);
            }

            if (!empty($valid)) { //cancelled or success

                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'contentTemplate' => 'MauticApiBundle:Client:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_client_index',
                        'route'         => $returnUrl,
                    ),
                    'flashes'         =>
                        ($valid === 1) ? array( //success
                            array(
                                'type' => 'notice',
                                'msg'  => 'mautic.api.client.notice.created',
                                'msgVars' => array(
                                    '%name%'         => $client->getName(),
                                    '%clientId%'     => $client->getPublicId(),
                                    '%clientSecret%' => $client->getSecret()
                                )
                            )
                        ) : array()
                ));
            }
        }

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction(array(
                'viewParameters'  => array('form' => $form->createView()),
                'contentTemplate' => 'MauticApiBundle:Client:form.html.php',
                'passthroughVars' => array(
                    'ajaxForms'  => array('client'),
                    'activeLink' => '#mautic_client_new',
                    'route'      => $action
                )
            ));
        } else {
            return $this->render('MauticApiBundle:Client:form.html.php',
                array(
                    'form' => $form->createView()
                )
            );
        }
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId)
    {
        if (!$this->get('mautic.security')->isGranted('api:clients:editother')) {
            return $this->accessDenied();
        }
        $model     = $this->container->get('mautic.model.client');
        $client    = $model->getEntity($objectId);
        $returnUrl = $this->generateUrl('mautic_client_index');

        //client not found
        if ($client === null || !$client->getId()) {
            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'contentTemplate' => 'MauticApiBundle:Client:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_client_index',
                    'route'         => $returnUrl
                ),
                'flashes'         =>array(
                    array(
                        'type' => 'error',
                        'msg'  => 'mautic.api.client.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        }
        $action = $this->generateUrl('mautic_client_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($client, $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = $this->checkFormValidity($form);

            if ($valid === 1) {
                //form is valid so process the data
                $model->saveEntity($client);
            }

            if (!empty($valid)) { //cancelled or success

                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'contentTemplate' => 'MauticApiBundle:Client:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_client_index',
                        'route'         => $returnUrl,
                    ),
                    'flashes'         =>
                        ($valid === 1) ? array( //success
                            array(
                                'type' => 'notice',
                                'msg'  => 'mautic.api.client.notice.updated',
                                'msgVars' => array('%name%' => $client->getName())
                            )
                        ) : array()
                ));
            }
        }

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction(array(
                'viewParameters'  => array('form' => $form->createView()),
                'contentTemplate' => 'MauticApiBundle:Client:form.html.php',
                'passthroughVars' => array(
                    'ajaxForms'   => array('client'),
                    'activeLink'  => '#mautic_client_index',
                    'route'       => $action
                )
            ));
        } else {
            return $this->render('MauticApiBundle:Client:form.html.php',
                array(
                    'form' => $form->createView()
                )
            );
        }
    }

    /**
     * Deletes a user object
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId) {
        if (!$this->get('mautic.security')->isGranted('api:clients:delete')) {
            return $this->accessDenied();
        }

        $returnUrl   = $this->generateUrl('mautic_client_index');
        $success     = 0;
        $flashes     = array();
        if ($this->request->getMethod() == 'POST') {
            $result = $this->container->get('mautic.model.client')->deleteEntity($objectId);

            if (!$result === null || !$result->getId()) {
                $flashes[] = array(
                    'type' => 'error',
                    'msg'  => 'mautic.api.client.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } else {
                $name = $result->getName();
                $flashes[] = array(
                    'type' => 'notice',
                    'msg'  => 'mautic.api.client.notice.deleted',
                    'msgVars' => array(
                        '%name%' => $name,
                        '%id%'   => $objectId
                    )
                );
            }
        }

        return $this->postActionRedirect(array(
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticApiBundle:Client:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_client_index',
                'route'         => $returnUrl,
                'success'       => $success
            ),
            'flashes'         => $flashes
        ));
    }
}