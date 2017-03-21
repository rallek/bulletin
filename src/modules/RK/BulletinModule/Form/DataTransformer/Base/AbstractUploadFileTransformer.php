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

namespace RK\BulletinModule\Form\DataTransformer\Base;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use RK\BulletinModule\Form\Type\Field\UploadType;
use RK\BulletinModule\Helper\UploadHelper;

/**
 * Upload file transformer base class.
 *
 * This data transformer treats uploaded files.
 */
abstract class AbstractUploadFileTransformer implements DataTransformerInterface
{
    /**
     * @var UploadType
     */
    protected $formType = '';

    /**
     * @var Request
     */
    protected $request = '';

    /**
     * @var UploadHelper
     */
    protected $uploadHelper = '';

    /**
     * @var string
     */
    protected $fieldName = '';

    /**
     * UploadFileTransformer constructor.
     *
     * @param UploadType   $formType     The form type containing this transformer
     * @param RequestStack $requestStack RequestStack service instance
     * @param UploadHelper $uploadHelper UploadHelper service instance
     * @param string       $fieldName    The form field name
     */
    public function __construct(UploadType $formType, RequestStack $requestStack, UploadHelper $uploadHelper, $fieldName = '')
    {
        $this->formType = $formType;
        $this->request = $requestStack->getCurrentRequest();
        $this->uploadHelper = $uploadHelper;
        $this->fieldName = $fieldName;
    }

    /**
     * Transforms a filename to the corresponding file object.
     *
     * @param string|File|null $filePath
     *
     * @return File|null
     */
    public function transform($filePath)
    {
        if (empty($filePath)) {
            return null;
        }
        if ($filePath instanceof File) {
            return $filePath;
        }

        return [$this->fieldName => new File($filePath)];
    }

    /**
     * Transforms an uploaded file back to the filename string.
     *
     * @param mixed $data Uploaded file or parent object (if file deletion checkbox has been provided)
     *
     * @return string
     */
    public function reverseTransform($data)
    {
        $deleteFile = false;
        $uploadedFile = null;

        if ($data instanceof UploadedFile) {
            // no file deletion checkbox has been provided
            $uploadedFile = $data;
        } else {
            $children = $this->formType->getFormBuilder()->all();
            foreach ($children as $child) {
                $childForm = $child->getForm();
                if (false !== strpos($childForm->getName(), 'DeleteFile')) {
                    $deleteFile = $childForm->getData();
                } elseif ($childForm->getData() instanceof UploadedFile) {
                    $uploadedFile = $childForm->getData();
                }
            }
        }

        $entity = $this->formType->getEntity();
        $objectType = $entity->get_objectType();
        $fieldName = $this->fieldName;

        if (null === $uploadedFile) {
            // check files array
            $filesKey = 'rkbulletinmodule_' . $objectType;
            if ($this->request->files->has($filesKey)) {
                $files = $this->request->files->get($filesKey);
                if (isset($files[$fieldName]) && isset($files[$fieldName][$fieldName])) {
                    $uploadedFile = $files[$fieldName][$fieldName];
                }
            }
        }

        $oldFile = $entity[$fieldName];
        if (is_array($oldFile)) {
            $oldFile = $oldFile[$fieldName];
        }

        // check if an existing file must be deleted
        $hasOldFile = !empty($oldFile);
        $hasBeenDeleted = !$hasOldFile;
        if ($hasOldFile && true === $deleteFile) {
            // remove old upload file
            $entity = $this->uploadHelper->deleteUploadFile($entity, $fieldName);
            $hasBeenDeleted = true;
        }

        if (null === $uploadedFile) {
            // no file has been uploaded
            return $oldFile;
        }

        // new file has been uploaded; check if there is an old one to be deleted
        if ($hasOldFile && true !== $hasBeenDeleted) {
            // remove old upload file (and image thumbnails)
            $entity = $this->uploadHelper->deleteUploadFile($entity, $fieldName);
        }

        // do the actual upload (includes validation, physical file processing and reading meta data)
        $uploadResult = $this->uploadHelper->performFileUpload($objectType, $uploadedFile, $fieldName);

        $result = null;
        $metaData = [];
        if ($uploadResult['fileName'] != '') {
            $result = $this->uploadHelper->getFileBaseFolder($this->formType->getEntity()->get_objectType(), $fieldName) . $uploadResult['fileName'];
            $metaData = $uploadResult['metaData'];
        }

        // assign the upload file
        $setter = 'set' . ucfirst($fieldName);
        $entity->$setter(null !== $result ? new File($result) : $result);

        // assign the meta data
        $entity[$fieldName . 'Meta'] = $metaData;

        return $result;
    }
}
