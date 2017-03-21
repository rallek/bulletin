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

namespace RK\BulletinModule\Entity;

use RK\BulletinModule\Entity\Base\AbstractImageEntity as BaseEntity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use RK\BulletinModule\Traits\EntityWorkflowTrait;
use RK\BulletinModule\Traits\StandardFieldsTrait;

/**
 * Entity class that defines the entity structure and behaviours.
 *
 * This is the concrete entity class for image entities.
 * @ORM\Entity(repositoryClass="RK\BulletinModule\Entity\Repository\ImageRepository")
 * @ORM\Table(name="rk_bulletin_image",
 *     indexes={
 *         @ORM\Index(name="workflowstateindex", columns={"workflowState"})
 *     }
 * )
 */
class ImageEntity extends BaseEntity
{
    // feel free to add your own methods here
}
