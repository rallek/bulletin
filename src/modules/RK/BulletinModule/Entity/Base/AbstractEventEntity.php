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

namespace RK\BulletinModule\Entity\Base;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Symfony\Component\Validator\Constraints as Assert;
use Zikula\Core\Doctrine\EntityAccess;
use RK\BulletinModule\Traits\EntityWorkflowTrait;
use RK\BulletinModule\Traits\StandardFieldsTrait;
use RK\BulletinModule\Validator\Constraints as BulletinAssert;

/**
 * Entity class that defines the entity structure and behaviours.
 *
 * This is the base entity class for event entities.
 * The following annotation marks it as a mapped superclass so subclasses
 * inherit orm properties.
 *
 * @ORM\MappedSuperclass
 *
 * @abstract
 */
abstract class AbstractEventEntity extends EntityAccess implements Translatable
{
    /**
     * Hook entity workflow field and behaviour.
     */
    use EntityWorkflowTrait;

    /**
     * Hook standard fields behaviour embedding createdBy, updatedBy, createdDate, updatedDate fields.
     */
    use StandardFieldsTrait;

    /**
     * @var string The tablename this object maps to
     */
    protected $_objectType = 'event';
    
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", unique=true)
     * @var integer $id
     */
    protected $id = 0;
    
    /**
     * the current workflow state
     * @ORM\Column(length=20)
     * @Assert\NotBlank()
     * @BulletinAssert\ListEntry(entityName="event", propertyName="workflowState", multiple=false)
     * @var string $workflowState
     */
    protected $workflowState = 'initial';
    
    /**
     * @ORM\Column(length=255)
     * @Assert\NotBlank()
     * @Assert\Length(min="0", max="255")
     * @var string $eventTitle
     */
    protected $eventTitle = '';
    
    /**
     * startpoint of the event
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     * @var DateTime $startDate
     */
    protected $startDate;
    
    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     * @Assert\Expression("value > this.getStartDate()")
     * @var DateTime $endDate
     */
    protected $endDate;
    
    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="text", length=5000)
     * @Assert\NotNull()
     * @Assert\Length(min="0", max="5000")
     * @var text $description
     */
    protected $description = '';
    
    
    /**
     * Used locale to override Translation listener's locale.
     * this is not a mapped field of entity metadata, just a simple property.
     *
     * @Assert\Locale()
     * @Gedmo\Locale
     * @var string $locale
     */
    protected $locale;
    
    /**
     * @ORM\OneToMany(targetEntity="\RK\BulletinModule\Entity\EventCategoryEntity", 
     *                mappedBy="entity", cascade={"all"}, 
     *                orphanRemoval=true)
     * @var \RK\BulletinModule\Entity\EventCategoryEntity
     */
    protected $categories = null;
    
    /**
     * Bidirectional - One event [event] is linked by one notice [notice] (INVERSE SIDE).
     *
     * @ORM\OneToOne(targetEntity="RK\BulletinModule\Entity\NoticeEntity", inversedBy="event")
     * @ORM\JoinTable(name="rk_bull_notice")
     * @Assert\Type(type="RK\BulletinModule\Entity\NoticeEntity")
     * @var \RK\BulletinModule\Entity\NoticeEntity $notice
     */
    protected $notice;
    
    
    /**
     * EventEntity constructor.
     *
     * Will not be called by Doctrine and can therefore be used
     * for own implementation purposes. It is also possible to add
     * arbitrary arguments as with every other class method.
     */
    public function __construct()
    {
        $this->startDate = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'));
        $this->endDate = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'));
        $this->initWorkflow();
        $this->categories = new ArrayCollection();
    }
    
    /**
     * Returns the _object type.
     *
     * @return string
     */
    public function get_objectType()
    {
        return $this->_objectType;
    }
    
    /**
     * Sets the _object type.
     *
     * @param string $_objectType
     *
     * @return void
     */
    public function set_objectType($_objectType)
    {
        $this->_objectType = $_objectType;
    }
    
    
    /**
     * Returns the id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Sets the id.
     *
     * @param integer $id
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = intval($id);
    }
    
    /**
     * Returns the workflow state.
     *
     * @return string
     */
    public function getWorkflowState()
    {
        return $this->workflowState;
    }
    
    /**
     * Sets the workflow state.
     *
     * @param string $workflowState
     *
     * @return void
     */
    public function setWorkflowState($workflowState)
    {
        $this->workflowState = isset($workflowState) ? $workflowState : '';
    }
    
    /**
     * Returns the event title.
     *
     * @return string
     */
    public function getEventTitle()
    {
        return $this->eventTitle;
    }
    
    /**
     * Sets the event title.
     *
     * @param string $eventTitle
     *
     * @return void
     */
    public function setEventTitle($eventTitle)
    {
        $this->eventTitle = isset($eventTitle) ? $eventTitle : '';
    }
    
    /**
     * Returns the start date.
     *
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }
    
    /**
     * Sets the start date.
     *
     * @param DateTime $startDate
     *
     * @return void
     */
    public function setStartDate($startDate)
    {
        if (is_object($startDate) && $startDate instanceOf \DateTime) {
            $this->startDate = $startDate;
        } else {
            $this->startDate = new \DateTime($startDate);
        }
    }
    
    /**
     * Returns the end date.
     *
     * @return DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }
    
    /**
     * Sets the end date.
     *
     * @param DateTime $endDate
     *
     * @return void
     */
    public function setEndDate($endDate)
    {
        if (is_object($endDate) && $endDate instanceOf \DateTime) {
            $this->endDate = $endDate;
        } else {
            $this->endDate = new \DateTime($endDate);
        }
    }
    
    /**
     * Returns the description.
     *
     * @return text
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    /**
     * Sets the description.
     *
     * @param text $description
     *
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = isset($description) ? $description : '';
    }
    
    /**
     * Returns the locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }
    
    /**
     * Sets the locale.
     *
     * @param string $locale
     *
     * @return void
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }
    
    /**
     * Returns the categories.
     *
     * @return ArrayCollection[]
     */
    public function getCategories()
    {
        return $this->categories;
    }
    
    
    /**
     * Sets the categories.
     *
     * @param ArrayCollection $categories
     *
     * @return void
     */
    public function setCategories(ArrayCollection $categories)
    {
        foreach ($this->categories as $category) {
            if (false === $key = $this->collectionContains($categories, $category)) {
                $this->categories->removeElement($category);
            } else {
                $categories->remove($key);
            }
        }
        foreach ($categories as $category) {
            $this->categories->add($category);
        }
    }
    
    /**
     * Checks if a collection contains an element based only on two criteria (categoryRegistryId, category).
     *
     * @param ArrayCollection $collection
     * @param \RK\BulletinModule\Entity\EventCategoryEntity $element
     *
     * @return bool|int
     */
    private function collectionContains(ArrayCollection $collection, \RK\BulletinModule\Entity\EventCategoryEntity $element)
    {
        foreach ($collection as $key => $category) {
            /** @var \RK\BulletinModule\Entity\EventCategoryEntity $category */
            if ($category->getCategoryRegistryId() == $element->getCategoryRegistryId()
                && $category->getCategory() == $element->getCategory()
            ) {
                return $key;
            }
        }
    
        return false;
    }
    
    /**
     * Returns the notice.
     *
     * @return \RK\BulletinModule\Entity\NoticeEntity
     */
    public function getNotice()
    {
        return $this->notice;
    }
    
    /**
     * Sets the notice.
     *
     * @param \RK\BulletinModule\Entity\NoticeEntity $notice
     *
     * @return void
     */
    public function setNotice($notice = null)
    {
        $this->notice = $notice;
    }
    
    
    /**
     * Returns the formatted title conforming to the display pattern
     * specified for this entity.
     *
     * @return string The display title
     */
    public function getTitleFromDisplayPattern()
    {
        $formattedTitle = ''
                . $this->getEventTitle();
    
        return $formattedTitle;
    }
    
    /**
     * Return entity data in JSON format.
     *
     * @return string JSON-encoded data
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
    
    /**
     * Creates url arguments array for easy creation of display urls.
     *
     * @return array The resulting arguments list
     */
    public function createUrlArgs()
    {
        $args = [];
    
        $args['id'] = $this['id'];
    
        if (property_exists($this, 'slug')) {
            $args['slug'] = $this['slug'];
        }
    
        return $args;
    }
    
    /**
     * Create concatenated identifier string (for composite keys).
     *
     * @return String concatenated identifiers
     */
    public function createCompositeIdentifier()
    {
        $itemId = $this['id'];
    
        return $itemId;
    }
    
    /**
     * Determines whether this entity supports hook subscribers or not.
     *
     * @return boolean
     */
    public function supportsHookSubscribers()
    {
        return true;
    }
    
    /**
     * Return lower case name of multiple items needed for hook areas.
     *
     * @return string
     */
    public function getHookAreaPrefix()
    {
        return 'rkbulletinmodule.ui_hooks.events';
    }
    
    /**
     * Returns an array of all related objects that need to be persisted after clone.
     * 
     * @param array $objects The objects are added to this array. Default: []
     * 
     * @return array of entity objects
     */
    public function getRelatedObjectsToPersist(&$objects = []) 
    {
        return [];
    }
    
    /**
     * ToString interceptor implementation.
     * This method is useful for debugging purposes.
     *
     * @return string The output string for this entity
     */
    public function __toString()
    {
        return 'Event ' . $this->createCompositeIdentifier() . ': ' . $this->getTitleFromDisplayPattern();
    }
    
    /**
     * Clone interceptor implementation.
     * This method is for example called by the reuse functionality.
     * Performs a quite simple shallow copy.
     *
     * See also:
     * (1) http://docs.doctrine-project.org/en/latest/cookbook/implementing-wakeup-or-clone.html
     * (2) http://www.php.net/manual/en/language.oop5.cloning.php
     * (3) http://stackoverflow.com/questions/185934/how-do-i-create-a-copy-of-an-object-in-php
     */
    public function __clone()
    {
        // if the entity has no identity do nothing, do NOT throw an exception
        if (!($this->id)) {
            return;
        }
    
        // otherwise proceed
    
        // unset identifiers
        $this->setId(0);
    
        // reset workflow
        $this->resetWorkflow();
    
        $this->setCreatedBy(null);
        $this->setCreatedDate(null);
        $this->setUpdatedBy(null);
        $this->setUpdatedDate(null);
    
    
        // clone categories
        $categories = $this->categories;
        $this->categories = new ArrayCollection();
        foreach ($categories as $c) {
            $newCat = clone $c;
            $this->categories->add($newCat);
            $newCat->setEntity($this);
        }
    }
}
