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

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use Zikula\ExtensionsModule\Api\VariableApi;
use Zikula\UsersModule\Api\CurrentUserApi;

/**
 * Helper base class for upload handling.
 */
abstract class AbstractUploadHelper
{
    use TranslatorTrait;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CurrentUserApi
     */
    protected $currentUserApi;

    /**
     * @var VariableApi
     */
    protected $variableApi;

    /**
     * @var String
     */
    protected $dataDirectory;

    /**
     * @var array List of object types with upload fields
     */
    protected $allowedObjectTypes;

    /**
     * @var array List of file types to be considered as images
     */
    protected $imageFileTypes;

    /**
     * @var array List of dangerous file types to be rejected
     */
    protected $forbiddenFileTypes;

    /**
     * UploadHelper constructor.
     *
     * @param TranslatorInterface $translator     Translator service instance
     * @param SessionInterface    $session        Session service instance
     * @param LoggerInterface     $logger         Logger service instance
     * @param CurrentUserApi      $currentUserApi CurrentUserApi service instance
     * @param VariableApi         $variableApi    VariableApi service instance
     * @param String              $dataDirectory  The data directory name
     */
    public function __construct(
        TranslatorInterface $translator,
        SessionInterface $session,
        LoggerInterface $logger,
        CurrentUserApi $currentUserApi,
        VariableApi $variableApi,
        $dataDirectory)
    {
        $this->setTranslator($translator);
        $this->session = $session;
        $this->logger = $logger;
        $this->currentUserApi = $currentUserApi;
        $this->variableApi = $variableApi;
        $this->dataDirectory = $dataDirectory;

        $this->allowedObjectTypes = ['notice', 'image'];
        $this->imageFileTypes = ['gif', 'jpeg', 'jpg', 'png', 'swf'];
        $this->forbiddenFileTypes = ['cgi', 'pl', 'asp', 'phtml', 'php', 'php3', 'php4', 'php5', 'exe', 'com', 'bat', 'jsp', 'cfm', 'shtml'];
    }

    /**
     * Sets the translator.
     *
     * @param TranslatorInterface $translator Translator service instance
     */
    public function setTranslator(/*TranslatorInterface */$translator)
    {
        $this->translator = $translator;
    }

    /**
     * Process a file upload.
     *
     * @param string       $objectType Currently treated entity type
     * @param UploadedFile $file       The uploaded file
     * @param string       $fieldName  Name of upload field
     *
     * @return array Resulting file name and collected meta data
     */
    public function performFileUpload($objectType, $file, $fieldName)
    {
        $result = [
            'fileName' => '',
            'metaData' => []
        ];
    
        // check whether uploads are allowed for the given object type
        if (!in_array($objectType, $this->allowedObjectTypes)) {
            return $result;
        }
    
        // perform validation
        try {
            $this->validateFileUpload($objectType, $file, $fieldName);
        } catch (\Exception $e) {
            // skip this upload field
            return $result;
        }
    
        // build the file name
        $fileName = $file->getClientOriginalName();
        $fileNameParts = explode('.', $fileName);
        $extension = null !== $file->guessExtension() ? $file->guessExtension() : $file->guessClientExtension();
        if (null === $extension) {
            $extension = strtolower($fileNameParts[count($fileNameParts) - 1]);
        }
        $extension = str_replace('jpeg', 'jpg', $extension);
        $fileNameParts[count($fileNameParts) - 1] = $extension;
        $fileName = implode('.', $fileNameParts);
    
        $flashBag = $this->session->getFlashBag();
    
        // retrieve the final file name
        try {
            $basePath = $this->getFileBaseFolder($objectType, $fieldName);
        } catch (\Exception $e) {
            $flashBag->add('error', $e->getMessage());
            $this->logger->error('{app}: User {user} could not detect upload destination path for entity {entity} and field {field}.', ['app' => 'RKBulletinModule', 'user' => $this->currentUserApi->get('uname'), 'entity' => $objectType, 'field' => $fieldName]);
    
            return false;
        }
        $fileName = $this->determineFileName($objectType, $fieldName, $basePath, $fileName, $extension);
    
        $destinationFilePath = $basePath . $fileName;
        $targetFile = $file->move($basePath, $fileName);
    
        // validate image file
        $isImage = in_array($extension, $this->imageFileTypes);
        if ($isImage) {
            $imgInfo = getimagesize($destinationFilePath);
            if (!is_array($imgInfo) || !$imgInfo[0] || !$imgInfo[1]) {
                $flashBag->add('error', $this->__('Error! This file type seems not to be a valid image.'));
                $this->logger->error('{app}: User {user} tried to upload a file which is seems not to be a valid image.', ['app' => 'RKBulletinModule', 'user' => $this->currentUserApi->get('uname')]);
        
                return false;
            }
        }
    
        $isImage = in_array($extension, $this->imageFileTypes);
        if ($isImage) {
            // check if shrinking functionality is enabled
            $fieldSuffix = ucfirst($objectType) . ucfirst($fieldName);
            if (true === $this->variableApi->get('RKBulletinModule', 'enableShrinkingFor' . $fieldSuffix, false)) {
                // check for maximum size
                $maxWidth = $this->variableApi->get('RKBulletinModule', 'shrinkWidth' . $fieldSuffix, 800);
                $maxHeight = $this->variableApi->get('RKBulletinModule', 'shrinkHeight' . $fieldSuffix, 600);
    
                $imgInfo = getimagesize($destinationFilePath);
                if ($imgInfo[0] > $maxWidth || $imgInfo[1] > $maxHeight) {
                    // resize to allowed maximum size
                    $thumbManager = \ServiceUtil::get('systemplugin.imagine.manager');
                    $preset = new \SystemPlugin_Imagine_Preset('RKBulletinModule_Shrinker', [
                        'width' => $maxWidth,
                        'height' => $maxHeight,
                        'mode' => 'inset'
                    ]);
                    $thumbManager->setPreset($preset);
    
                    // create thumbnail image
                    $thumbFilePath = $thumbManager->getThumb($destinationFilePath, $maxWidth, $maxHeight);
    
                    // remove original image
                    unlink($destinationFilePath);
    
                    // rename thumbnail image to original image
                    rename($thumbFilePath, $destinationFilePath);
                }
            }
        }
    
        // collect data to return
        $result['fileName'] = $fileName;
        $result['metaData'] = $this->readMetaDataForFile($fileName, $destinationFilePath);
    
        return $result;
    }

    /**
     * Check if an upload file meets all validation criteria.
     *
     * @param string       $objectType Currently treated entity type
     * @param UploadedFile $file       Reference to data of uploaded file
     * @param string       $fieldName  Name of upload field
     *
     * @return boolean true if file is valid else false
     */
    protected function validateFileUpload($objectType, $file, $fieldName)
    {
        $flashBag = $this->session->getFlashBag();
    
        // check if a file has been uploaded properly without errors
        if ($file->getError() != UPLOAD_ERR_OK) {
            $flashBag->add('error', $file->getErrorMessage());
            $this->logger->error('{app}: User {user} tried to upload a file with errors: ' . $file->getErrorMessage(), ['app' => 'RKBulletinModule', 'user' => $this->currentUserApi->get('uname')]);
    
            return false;
        }
    
        // extract file extension
        $fileName = $file->getClientOriginalName();
        $extension = null !== $file->guessExtension() ? $file->guessExtension() : $file->guessClientExtension();
        if (null === $extension) {
            $fileNameParts = explode('.', $fileName);
            $extension = strtolower($fileNameParts[count($fileNameParts) - 1]);
        }
        $extension = str_replace('jpeg', 'jpg', $extension);
    
        // validate extension
        $isValidExtension = $this->isAllowedFileExtension($objectType, $fieldName, $extension);
        if (false === $isValidExtension) {
            $flashBag->add('error', $this->__('Error! This file type is not allowed. Please choose another file format.'));
            $this->logger->error('{app}: User {user} tried to upload a file with a forbidden extension ("{extension}").', ['app' => 'RKBulletinModule', 'user' => $this->currentUserApi->get('uname'), 'extension' => $extension]);
    
            return false;
        }
    
        return true;
    }

    /**
     * Read meta data from a certain file.
     *
     * @param string $fileName  Name of file to be processed
     * @param string $filePath  Path to file to be processed
     *
     * @return array collected meta data
     */
    public function readMetaDataForFile($fileName, $filePath)
    {
        $meta = [];
        if (empty($fileName)) {
            return $meta;
        }
    
        $extensionarr = explode('.', $fileName);
        $meta['extension'] = strtolower($extensionarr[count($extensionarr) - 1]);
        $meta['size'] = filesize($filePath);
        $meta['isImage'] = in_array($meta['extension'], $this->imageFileTypes) ? true : false;
    
        if (!$meta['isImage']) {
            return $meta;
        }
    
        if ($meta['extension'] == 'swf') {
            $meta['isImage'] = false;
        }
    
        $imgInfo = getimagesize($filePath);
        if (!is_array($imgInfo)) {
            return $meta;
        }
    
        $meta['width'] = $imgInfo[0];
        $meta['height'] = $imgInfo[1];
    
        if ($imgInfo[1] < $imgInfo[0]) {
            $meta['format'] = 'landscape';
        } elseif ($imgInfo[1] > $imgInfo[0]) {
            $meta['format'] = 'portrait';
        } else {
            $meta['format'] = 'square';
        }
    
        return $meta;
    }

    /**
     * Determines the allowed file extensions for a given object type.
     *
     * @param string $objectType Currently treated entity type
     * @param string $fieldName  Name of upload field
     * @param string $extension  Input file extension
     *
     * @return array the list of allowed file extensions
     */
    protected function isAllowedFileExtension($objectType, $fieldName, $extension)
    {
        // determine the allowed extensions
        $allowedExtensions = [];
        switch ($objectType) {
            case 'notice':
                $allowedExtensions = ['gif', 'jpeg', 'jpg', 'png'];
                    break;
            case 'image':
                $allowedExtensions = ['gif', 'jpeg', 'jpg', 'png'];
                    break;
        }
    
        if (count($allowedExtensions) > 0) {
            if (!in_array($extension, $allowedExtensions)) {
                return false;
            }
        }
    
        if (in_array($extension, $this->forbiddenFileTypes)) {
            return false;
        }
    
        return true;
    }

    /**
     * Determines the final filename for a given input filename.
     *
     * It considers different strategies for computing the result.
     *
     * @param string $objectType Currently treated entity type
     * @param string $fieldName  Name of upload field
     * @param string $basePath   Base path for file storage
     * @param string $fileName   Input file name
     * @param string $extension  Input file extension
     *
     * @return string the resulting file name
     */
    protected function determineFileName($objectType, $fieldName, $basePath, $fileName, $extension)
    {
        $backupFileName = $fileName;
    
        $namingScheme = 0;
    
        switch ($objectType) {
            case 'notice':
                $namingScheme = 0;
                    break;
            case 'image':
                $namingScheme = 0;
                    break;
        }
    
    
        $iterIndex = -1;
        do {
            if ($namingScheme == 0) {
                // original file name
                $fileNameCharCount = strlen($fileName);
                for ($y = 0; $y < $fileNameCharCount; $y++) {
                    if (preg_match('/[^0-9A-Za-z_\.]/', $fileName[$y])) {
                        $fileName[$y] = '_';
                    }
                }
                // append incremented number
                if ($iterIndex > 0) {
                    // strip off extension
                    $fileName = str_replace('.' . $extension, '', $backupFileName);
                    // add iterated number
                    $fileName .= (string) ++$iterIndex;
                    // readd extension
                    $fileName .= '.' . $extension;
                } else {
                    $iterIndex++;
                }
            } elseif ($namingScheme == 1) {
                // md5 name
                $fileName = md5(uniqid(mt_rand(), TRUE)) . '.' . $extension;
            } elseif ($namingScheme == 2) {
                // prefix with random number
                $fileName = $fieldName . mt_rand(1, 999999) . '.' . $extension;
            }
        }
        while (file_exists($basePath . $fileName)); // repeat until we have a new name
    
        // return the new file name
        return $fileName;
    }

    /**
     * Deletes an existing upload file.
     * For images the thumbnails are removed, too.
     *
     * @param object  $entity    Currently treated entity
     * @param string  $fieldName Name of upload field
     *
     * @return mixed Updated entity on success, else false
     */
    public function deleteUploadFile($entity, $fieldName)
    {
        $objectType = $entity->get_objectType();
        if (!in_array($objectType, $this->allowedObjectTypes)) {
            return false;
        }
    
        if (empty($entity[$fieldName])) {
            return $entity;
        }
    
        // remove the file
        if (is_array($entity[$fieldName]) && isset($entity[$fieldName][$fieldName])) {
            $entity[$fieldName] = $entity[$fieldName][$fieldName];
        }
        if (is_object($entity[$fieldName])) {
            $filePath = $entity[$fieldName]->getPathname();
            if (file_exists($filePath) && !unlink($filePath)) {
                return false;
            }
        }
    
        $entity[$fieldName] = null;
        $entity[$fieldName . 'Meta'] = [];
        $entity[$fieldName . 'Url'] = '';
    
        return $entity;
    }

    /**
     * Retrieve the base path for given object type and upload field combination.
     *
     * @param string  $objectType   Name of treated entity type
     * @param string  $fieldName    Name of upload field
     * @param boolean $ignoreCreate Whether to ignore the creation of upload folders on demand or not
     *
     * @return mixed Output
     *
     * @throws Exception If an invalid object type is used
     */
    public function getFileBaseFolder($objectType, $fieldName, $ignoreCreate = false)
    {
        $basePath = $this->dataDirectory . '/RKBulletinModule/';
    
        switch ($objectType) {
            case 'notice':
                $basePath .= 'notices/image/';
                break;
            case 'image':
                $basePath .= 'images/image/';
                break;
            default:
                throw new Exception($this->__('Error! Invalid object type received.'));
        }
    
        $result = $basePath;
        if (substr($result, -1, 1) != '/') {
            // reappend the removed slash
            $result .= '/';
        }
    
        if (!is_dir($result) && !$ignoreCreate) {
            $this->checkAndCreateAllUploadFolders();
        }
    
        return $result;
    }

    /**
     * Prepares an upload field by transforming the file name into a File object.
     *
     * @param EntityAccess $entity    The entity object
     * @param string       $fieldName Name of upload field
     * @param string       $baseUrl   The base url to prepend
     */
    public function initialiseUploadField($entity, $fieldName, $baseUrl)
    {
        if (empty($fieldName)) {
            return;
        }
        $fileName = $entity[$fieldName];
        $filePath = $this->getFileBaseFolder($entity->get_objectType(), $fieldName) . $fileName;
        if (!empty($fileName) && file_exists($filePath)) {
            $entity[$fieldName] = new File($filePath);
            $entity[$fieldName . 'Url'] = $baseUrl . '/' . $filePath;
    
            // determine meta data if it does not exist
            if (!is_array($entity[$fieldName . 'Meta']) || !count($entity[$fieldName . 'Meta'])) {
                $entity[$fieldName . 'Meta'] = $this->readMetaDataForFile($fileName, $filePath);
            }
        } else {
            $entity[$fieldName] = null;
            $entity[$fieldName . 'Url'] = '';
            $entity[$fieldName . 'Meta'] = [];
        }
    }

    /**
     * Creates all required upload folders for this application.
     *
     * @return Boolean Whether everything went okay or not
     */
    public function checkAndCreateAllUploadFolders()
    {
        $result = true;
    
        $result &= $this->checkAndCreateUploadFolder('notice', 'image', 'gif, jpeg, jpg, png');
    
        $result &= $this->checkAndCreateUploadFolder('image', 'image', 'gif, jpeg, jpg, png');
    
        return $result;
    }

    /**
     * Creates upload folder including a subfolder for thumbnail and an .htaccess file within it.
     *
     * @param string $objectType        Name of treated entity type
     * @param string $fieldName         Name of upload field
     * @param string $allowedExtensions String with list of allowed file extensions (separated by ", ")
     *
     * @return Boolean Whether everything went okay or not
     */
    protected function checkAndCreateUploadFolder($objectType, $fieldName, $allowedExtensions = '')
    {
        $uploadPath = $this->getFileBaseFolder($objectType, $fieldName, true);
    
        $fs = new Filesystem();
        $flashBag = $this->session->getFlashBag();
    
        // Check if directory exist and try to create it if needed
        if (!$fs->exists($uploadPath)) {
            try {
                $fs->mkdir($uploadPath, 0777);
            } catch (IOExceptionInterface $e) {
                $flashBag->add('error', $this->__f('The upload directory "%path%" does not exist and could not be created. Try to create it yourself and make sure that this folder is accessible via the web and writable by the webserver.', ['%path%' => $e->getPath()]));
                $this->logger->error('{app}: The upload directory {directory} does not exist and could not be created.', ['app' => 'RKBulletinModule', 'directory' => $uploadPath]);
    
                return false;
            }
        }
    
        // Check if directory is writable and change permissions if needed
        if (!is_writable($uploadPath)) {
            try {
                $fs->chmod($uploadPath, 0777);
            } catch (IOExceptionInterface $e) {
                $flashBag->add('warning', $this->__f('Warning! The upload directory at "%path%" exists but is not writable by the webserver.', ['%path%' => $e->getPath()]));
                $this->logger->error('{app}: The upload directory {directory} exists but is not writable by the webserver.', ['app' => 'RKBulletinModule', 'directory' => $uploadPath]);
    
                return false;
            }
        }
    
        // Write a htaccess file into the upload directory
        $htaccessFilePath = $uploadPath . '/.htaccess';
        $htaccessFileTemplate = 'modules/RK/BulletinModule/Resources/docs/htaccessTemplate';
        if (!$fs->exists($htaccessFilePath) && $fs->exists($htaccessFileTemplate)) {
            try {
                $extensions = str_replace(',', '|', str_replace(' ', '', $allowedExtensions));
                $htaccessContent = str_replace('__EXTENSIONS__', $extensions, file_get_contents($htaccessFileTemplate, false));
                $fs->dumpFile($htaccessFilePath, $htaccessContent);
            } catch (IOExceptionInterface $e) {
                $flashBag->add('error', $this->__f('An error occured during creation of the .htaccess file in directory "%path%".', ['%path%' => $e->getPath()]));
                $this->logger->error('{app}: An error occured during creation of the .htaccess file in directory {directory}.', ['app' => 'RKBulletinModule', 'directory' => $uploadPath]);
            }
        }
    
        return true;
    }
}
