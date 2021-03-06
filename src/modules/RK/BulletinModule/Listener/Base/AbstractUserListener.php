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

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Core\Event\GenericEvent;
use Zikula\UsersModule\Api\CurrentUserApi;
use Zikula\UsersModule\UserEvents;
use RK\BulletinModule\Entity\Factory\BulletinFactory;

/**
 * Event handler base class for user-related events.
 */
abstract class AbstractUserListener implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    
    /**
     * @var BulletinFactory
     */
    protected $entityFactory;
    
    /**
     * @var CurrentUserApi
     */
    protected $currentUserApi;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    /**
     * UserListener constructor.
     *
     * @param TranslatorInterface $translator     Translator service instance
     * @param BulletinFactory $entityFactory BulletinFactory service instance
     * @param CurrentUserApi      $currentUserApi CurrentUserApi service instance
     * @param LoggerInterface     $logger         Logger service instance
     *
     * @return void
     */
    public function __construct(
        TranslatorInterface $translator,
        BulletinFactory $entityFactory,
        CurrentUserApi $currentUserApi,
        LoggerInterface $logger
    ) {
        $this->translator = $translator;
        $this->entityFactory = $entityFactory;
        $this->currentUserApi = $currentUserApi;
        $this->logger = $logger;
    }
    
    /**
     * Makes our handlers known to the event system.
     */
    public static function getSubscribedEvents()
    {
        return [
            UserEvents::CREATE_ACCOUNT => ['create', 5],
            UserEvents::UPDATE_ACCOUNT => ['update', 5],
            UserEvents::DELETE_ACCOUNT => ['delete', 5]
        ];
    }
    
    /**
     * Listener for the `user.account.create` event.
     *
     * Occurs after a user account is created. All handlers are notified.
     * It does not apply to creation of a pending registration.
     * The full user record created is available as the subject.
     * This is a storage-level event, not a UI event. It should not be used for UI-level actions such as redirects.
     * The subject of the event is set to the user record that was created.
     *
     * @param GenericEvent $event The event instance
     */
    public function create(GenericEvent $event)
    {
    }
    
    /**
     * Listener for the `user.account.update` event.
     *
     * Occurs after a user is updated. All handlers are notified.
     * The full updated user record is available as the subject.
     * This is a storage-level event, not a UI event. It should not be used for UI-level actions such as redirects.
     * The subject of the event is set to the user record, with the updated values.
     *
     * @param GenericEvent $event The event instance
     */
    public function update(GenericEvent $event)
    {
    }
    
    /**
     * Listener for the `user.account.delete` event.
     *
     * Occurs after the deletion of a user account. Subject is $userId.
     * This is a storage-level event, not a UI event. It should not be used for UI-level actions such as redirects.
     *
     * @param GenericEvent $event The event instance
     */
    public function delete(GenericEvent $event)
    {
        $userId = $event->getSubject();
    
        
        $repo = $this->entityFactory->getRepository('notice');
        // set creator to admin (2) for all notices created by this user
        $repo->updateCreator($userId, 2, $this->translator, $this->logger, $this->currentUserApi);
        
        // set last editor to admin (2) for all notices updated by this user
        $repo->updateLastEditor($userId, 2, $this->translator, $this->logger, $this->currentUserApi);
        
        $logArgs = ['app' => 'RKBulletinModule', 'user' => $this->currentUserApi->get('uname'), 'entities' => 'notices'];
        $this->logger->notice('{app}: User {user} has been deleted, so we deleted/updated corresponding {entities}, too.', $logArgs);
        
        $repo = $this->entityFactory->getRepository('picture');
        // set creator to admin (2) for all pictures created by this user
        $repo->updateCreator($userId, 2, $this->translator, $this->logger, $this->currentUserApi);
        
        // set last editor to admin (2) for all pictures updated by this user
        $repo->updateLastEditor($userId, 2, $this->translator, $this->logger, $this->currentUserApi);
        
        $logArgs = ['app' => 'RKBulletinModule', 'user' => $this->currentUserApi->get('uname'), 'entities' => 'pictures'];
        $this->logger->notice('{app}: User {user} has been deleted, so we deleted/updated corresponding {entities}, too.', $logArgs);
        
        $repo = $this->entityFactory->getRepository('event');
        // set creator to admin (2) for all events created by this user
        $repo->updateCreator($userId, 2, $this->translator, $this->logger, $this->currentUserApi);
        
        // set last editor to admin (2) for all events updated by this user
        $repo->updateLastEditor($userId, 2, $this->translator, $this->logger, $this->currentUserApi);
        
        $logArgs = ['app' => 'RKBulletinModule', 'user' => $this->currentUserApi->get('uname'), 'entities' => 'events'];
        $this->logger->notice('{app}: User {user} has been deleted, so we deleted/updated corresponding {entities}, too.', $logArgs);
    }
}
