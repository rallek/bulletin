<?php
/**
 * Bulletin.
 *
 * @copyright Ralf Koester (RK)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Ralf Koester <ralf@familie-koester.de>.
 * @link http://k62.de
 * @link http://zikula.org
 * @version Generated by ModuleStudio (http://modulestudio.de).
 */

namespace RK\BulletinModule\Listener\Base;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Zikula\Core\Doctrine\EntityAccess;
use RK\BulletinModule\BulletinEvents;
use RK\BulletinModule\Event\FilterNoticeEvent;
use RK\BulletinModule\Event\FilterPictureEvent;
use RK\BulletinModule\Event\FilterEventEvent;

/**
 * Event subscriber base class for entity lifecycle events.
 */
abstract class AbstractEntityLifecycleListener implements EventSubscriber, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * EntityLifecycleListener constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        if (null === $container) {
            $container = \ServiceUtil::getManager();
        }
        $this->setContainer($container);
    }

    /**
     * Returns list of events to subscribe.
     *
     * @return array list of events
     */
    public function getSubscribedEvents()
    {
        return [
            Events::preRemove,
            Events::postRemove,
            Events::prePersist,
            Events::postPersist,
            Events::preUpdate,
            Events::postUpdate,
            Events::postLoad
        ];
    }

    /**
     * The preRemove event occurs for a given entity before the respective EntityManager
     * remove operation for that entity is executed. It is not called for a DQL DELETE statement.
     *
     * @param LifecycleEventArgs $args Event arguments
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$this->isEntityManagedByThisBundle($entity) || !method_exists($entity, 'get_objectType')) {
            return;
        }

        // create the filter event and dispatch it
        $filterEventClass = '\\RK\\BulletinModule\\Event\\Filter' . ucfirst($entity->get_objectType()) . 'Event';
        $event = new $filterEventClass($entity);
        $this->container->get('event_dispatcher')->dispatch(constant('\\RK\\BulletinModule\\BulletinEvents::' . strtoupper($entity->get_objectType()) . '_PRE_REMOVE'), $event);
        if ($event->isPropagationStopped()) {
            return false;
        }
        
        // delete workflow for this entity
        $workflowHelper = $this->container->get('rk_bulletin_module.workflow_helper');
        $workflowHelper->normaliseWorkflowData($entity);
        $workflow = $entity['__WORKFLOW__'];
        if ($workflow['id'] > 0) {
            $entityManager = $this->container->get('doctrine.orm.default_entity_manager');
            $result = true;
            try {
                $workflow = $entityManager->find('Zikula\Core\Doctrine\Entity\WorkflowEntity', $workflow['id']);
                $entityManager->remove($workflow);
                $entityManager->flush();
            } catch (\Exception $e) {
                $result = false;
            }
            if (false === $result) {
                $flashBag = $this->container->get('session')->getFlashBag();
                $flashBag->add('error', $this->container->get('translator.default')->__('Error! Could not remove stored workflow. Deletion has been aborted.'));
        
                return false;
            }
        }
    }

    /**
     * The postRemove event occurs for an entity after the entity has been deleted. It will be
     * invoked after the database delete operations. It is not called for a DQL DELETE statement.
     *
     * Note that the postRemove event or any events triggered after an entity removal can receive
     * an uninitializable proxy in case you have configured an entity to cascade remove relations.
     * In this case, you should load yourself the proxy in the associated pre event.
     *
     * @param LifecycleEventArgs $args Event arguments
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$this->isEntityManagedByThisBundle($entity) || !method_exists($entity, 'get_objectType')) {
            return;
        }

        $objectType = $entity->get_objectType();
        $objectId = $entity->createCompositeIdentifier();
        
        $uploadHelper = $this->container->get('rk_bulletin_module.upload_helper');
        $uploadFields = $this->getUploadFields($objectType);
        foreach ($uploadFields as $uploadField) {
            if (empty($entity[$uploadField])) {
                continue;
            }
        
            // remove upload file
            $uploadHelper->deleteUploadFile($entity, $uploadField);
        }
        
        $logger = $this->container->get('logger');
        $logArgs = ['app' => 'RKBulletinModule', 'user' => $this->container->get('zikula_users_module.current_user')->get('uname'), 'entity' => $objectType, 'id' => $objectId];
        $logger->debug('{app}: User {user} removed the {entity} with id {id}.', $logArgs);
        
        // create the filter event and dispatch it
        $filterEventClass = '\\RK\\BulletinModule\\Event\\Filter' . ucfirst($objectType) . 'Event';
        $event = new $filterEventClass($entity);
        $this->container->get('event_dispatcher')->dispatch(constant('\\RK\\BulletinModule\\BulletinEvents::' . strtoupper($objectType) . '_POST_REMOVE'), $event);
    }

    /**
     * The prePersist event occurs for a given entity before the respective EntityManager
     * persist operation for that entity is executed. It should be noted that this event
     * is only triggered on initial persist of an entity (i.e. it does not trigger on future updates).
     *
     * Doctrine will not recognize changes made to relations in a prePersist event.
     * This includes modifications to collections such as additions, removals or replacement.
     *
     * @param LifecycleEventArgs $args Event arguments
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$this->isEntityManagedByThisBundle($entity) || !method_exists($entity, 'get_objectType')) {
            return;
        }

        $uploadFields = $this->getUploadFields($entity->get_objectType());
        foreach ($uploadFields as $uploadField) {
            if (empty($entity[$uploadField])) {
                continue;
            }
        
            if (!($entity[$uploadField] instanceof File)) {
                $entity[$uploadField] = new File($entity[$uploadField]);
            }
            $entity[$uploadField] = $entity[$uploadField]->getFilename();
        }
        
        
        // create the filter event and dispatch it
        $filterEventClass = '\\RK\\BulletinModule\\Event\\Filter' . ucfirst($entity->get_objectType()) . 'Event';
        $event = new $filterEventClass($entity);
        $this->container->get('event_dispatcher')->dispatch(constant('\\RK\\BulletinModule\\BulletinEvents::' . strtoupper($entity->get_objectType()) . '_PRE_PERSIST'), $event);
        if ($event->isPropagationStopped()) {
            return false;
        }
    }

    /**
     * The postPersist event occurs for an entity after the entity has been made persistent.
     * It will be invoked after the database insert operations. Generated primary key values
     * are available in the postPersist event.
     *
     * @param LifecycleEventArgs $args Event arguments
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$this->isEntityManagedByThisBundle($entity) || !method_exists($entity, 'get_objectType')) {
            return;
        }

        $objectId = $entity->createCompositeIdentifier();
        $logger = $this->container->get('logger');
        $logArgs = ['app' => 'RKBulletinModule', 'user' => $this->container->get('zikula_users_module.current_user')->get('uname'), 'entity' => $entity->get_objectType(), 'id' => $objectId];
        $logger->debug('{app}: User {user} created the {entity} with id {id}.', $logArgs);
        
        // create the filter event and dispatch it
        $filterEventClass = '\\RK\\BulletinModule\\Event\\Filter' . ucfirst($entity->get_objectType()) . 'Event';
        $event = new $filterEventClass($entity);
        $this->container->get('event_dispatcher')->dispatch(constant('\\RK\\BulletinModule\\BulletinEvents::' . strtoupper($entity->get_objectType()) . '_POST_PERSIST'), $event);
    }

    /**
     * The preUpdate event occurs before the database update operations to entity data.
     * It is not called for a DQL UPDATE statement nor when the computed changeset is empty.
     *
     * @see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#preupdate
     *
     * @param PreUpdateEventArgs $args Event arguments
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$this->isEntityManagedByThisBundle($entity) || !method_exists($entity, 'get_objectType')) {
            return;
        }

        $uploadFields = $this->getUploadFields($entity->get_objectType());
        foreach ($uploadFields as $uploadField) {
            if (empty($entity[$uploadField])) {
                continue;
            }
        
            if (!($entity[$uploadField] instanceof File)) {
                $entity[$uploadField] = new File($entity[$uploadField]);
            }
            $entity[$uploadField] = $entity[$uploadField]->getFilename();
        }
        
        
        // create the filter event and dispatch it
        $filterEventClass = '\\RK\\BulletinModule\\Event\\Filter' . ucfirst($entity->get_objectType()) . 'Event';
        $event = new $filterEventClass($entity, $args->getEntityChangeSet());
        $this->container->get('event_dispatcher')->dispatch(constant('\\RK\\BulletinModule\\BulletinEvents::' . strtoupper($entity->get_objectType()) . '_PRE_UPDATE'), $event);
        if ($event->isPropagationStopped()) {
            return false;
        }
    }

    /**
     * The postUpdate event occurs after the database update operations to entity data.
     * It is not called for a DQL UPDATE statement.
     *
     * @param LifecycleEventArgs $args Event arguments
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$this->isEntityManagedByThisBundle($entity) || !method_exists($entity, 'get_objectType')) {
            return;
        }

        $objectId = $entity->createCompositeIdentifier();
        $logger = $this->container->get('logger');
        $logArgs = ['app' => 'RKBulletinModule', 'user' => $this->container->get('zikula_users_module.current_user')->get('uname'), 'entity' => $entity->get_objectType(), 'id' => $objectId];
        $logger->debug('{app}: User {user} updated the {entity} with id {id}.', $logArgs);
        
        // create the filter event and dispatch it
        $filterEventClass = '\\RK\\BulletinModule\\Event\\Filter' . ucfirst($entity->get_objectType()) . 'Event';
        $event = new $filterEventClass($entity);
        $this->container->get('event_dispatcher')->dispatch(constant('\\RK\\BulletinModule\\BulletinEvents::' . strtoupper($entity->get_objectType()) . '_POST_UPDATE'), $event);
    }

    /**
     * The postLoad event occurs for an entity after the entity has been loaded into the current
     * EntityManager from the database or after the refresh operation has been applied to it.
     *
     * Note that, when using Doctrine\ORM\AbstractQuery#iterate(), postLoad events will be executed
     * immediately after objects are being hydrated, and therefore associations are not guaranteed
     * to be initialized. It is not safe to combine usage of Doctrine\ORM\AbstractQuery#iterate()
     * and postLoad event handlers.
     *
     * @param LifecycleEventArgs $args Event arguments
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$this->isEntityManagedByThisBundle($entity) || !method_exists($entity, 'get_objectType')) {
            return;
        }

        // prepare helper fields for uploaded files
        $uploadFields = $this->getUploadFields($entity->get_objectType());
        if (count($uploadFields) > 0) {
            $request = $this->container->get('request_stack')->getCurrentRequest();
            $baseUrl = $request->getSchemeAndHttpHost() . $request->getBasePath();
            $uploadHelper = $this->container->get('rk_bulletin_module.upload_helper');
            foreach ($uploadFields as $fieldName) {
                $uploadHelper->initialiseUploadField($entity, $fieldName, $baseUrl);
            }
        }

        
        // create the filter event and dispatch it
        $filterEventClass = '\\RK\\BulletinModule\\Event\\Filter' . ucfirst($entity->get_objectType()) . 'Event';
        $event = new $filterEventClass($entity);
        $this->container->get('event_dispatcher')->dispatch(constant('\\RK\\BulletinModule\\BulletinEvents::' . strtoupper($entity->get_objectType()) . '_POST_LOAD'), $event);
    }

    /**
     * Checks whether this listener is responsible for the given entity or not.
     *
     * @param EntityAccess $entity The given entity
     *
     * @return boolean True if entity is managed by this listener, false otherwise
     */
    protected function isEntityManagedByThisBundle($entity)
    {
        if (!($entity instanceof EntityAccess)) {
            return false;
        }

        $entityClassParts = explode('\\', get_class($entity));

        return ($entityClassParts[0] == 'RK' && $entityClassParts[1] == 'BulletinModule');
    }

    /**
     * Returns list of upload fields for the given object type.
     *
     * @param string $objectType The object type
     *
     * @return array List of upload fields
     */
    protected function getUploadFields($objectType)
    {
        $uploadFields = [];
        switch ($objectType) {
            case 'notice':
                $uploadFields = ['image'];
                break;
            case 'picture':
                $uploadFields = ['image'];
                break;
        }

        return $uploadFields;
    }
}
