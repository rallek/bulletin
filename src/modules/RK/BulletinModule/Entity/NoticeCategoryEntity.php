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

use RK\BulletinModule\Entity\Base\AbstractNoticeCategoryEntity as BaseEntity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity extension domain class storing notice categories.
 *
 * This is the concrete category class for notice entities.
 * @ORM\Entity(repositoryClass="\RK\BulletinModule\Entity\Repository\NoticeCategoryRepository")
 * @ORM\Table(name="rk_bulletin_notice_category",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="cat_unq", columns={"registryId", "categoryId", "entityId"})
 *     }
 * )
 */
class NoticeCategoryEntity extends BaseEntity
{
    // feel free to add your own methods here
}