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

namespace RK\BulletinModule\Helper\Base;

use RK\BulletinModule\Entity\Factory\BulletinFactory;

/**
 * Helper base class for model layer methods.
 */
abstract class AbstractModelHelper
{
    /**
     * @var BulletinFactory
     */
    protected $entityFactory;

    /**
     * ModelHelper constructor.
     *
     * @param BulletinFactory $entityFactory BulletinFactory service instance
     */
    public function __construct(BulletinFactory $entityFactory)
    {
        $this->entityFactory = $entityFactory;
    }

    /**
     * Determines whether creating an instance of a certain object type is possible.
     * This is when
     *     - no tree is used
     *     - it has no incoming bidirectional non-nullable relationships.
     *     - the edit type of all those relationships has PASSIVE_EDIT and auto completion is used on the target side
     *       (then a new source object can be created while creating the target object).
     *     - corresponding source objects exist already in the system.
     *
     * Note that even creation of a certain object is possible, it may still be forbidden for the current user
     * if he does not have the required permission level.
     *
     * @param string $objectType Name of treated entity type
     *
     * @return boolean Whether a new instance can be created or not
     *
     * @throws Exception If an invalid object type is used
     */
    public function canBeCreated($objectType)
    {
        $result = false;
    
        switch ($objectType) {
            case 'notice':
                $result = true;
                break;
            case 'picture':
                $result = true;
                break;
            case 'event':
                $result = true;
                break;
        }
    
        return $result;
    }

    /**
     * Determines whether there exists at least one instance of a certain object type in the database.
     *
     * @param string $objectType Name of treated entity type
     *
     * @return boolean Whether at least one instance exists or not
     *
     * @throws Exception If an invalid object type is used
     */
    protected function hasExistingInstances($objectType)
    {
        $repository = $this->entityFactory->getRepository($objectType);
        if (null === $repository) {
            return false;
        }
    
        return $repository->selectCount() > 0;
    }
}
