<?php
/**
 * DamConsultants
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 *  DamConsultants_BynderTheisens
 */
namespace DamConsultants\Akima\Controller\BynderIndex;

use DamConsultants\Akima\Helper\Data;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var $b_datahelper
     */
    protected $b_datahelper;
    /**
     * @var $file
     */
    protected $file;
    /**
     * @var $driverFile
     */
    protected $driverFile;
    /**
     * @var $resourceConnection
     */
    protected $resourceConnection;

    /**
     * Index.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Filesystem\Io\File $file
     * @param \Magento\Framework\Filesystem\Driver\File $driverFile
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param Data $bynderData
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Filesystem\Driver\File $driverFile,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        Data $bynderData
    ) {
        $this->b_datahelper = $bynderData;
        $this->file = $file;
        $this->driverFile = $driverFile;
        $this->resourceConnection = $resourceConnection;
        return parent::__construct($context);
    }
    /**
     * Execute
     *
     * @return $this
     */
    public function execute()
    {
        $res_array = [
            "status" => 0,
            "data" => 0,
            "message" => "something went wrong please try again. |
        please logout and login again"
        ];
        $img_data_post = $this->getRequest()->getPost("img_data");
        $dir_path_post = $this->getRequest()->getPost("dir_path");
        if ($this->getRequest()->isAjax()) {
            if (isset($img_data_post) && count($img_data_post) > 0) {
                if (isset($dir_path_post) && !empty($dir_path_post)) {
                    $img_dir = BP . '/pub/media/wysiwyg/' . $dir_path_post;
                    $thumb_dir = BP . '/pub/media/.thumbswysiwyg/'. $dir_path_post;

                    // Create main image directory if not exists
                    if (!$this->file->fileExists($img_dir)) {
                        $this->file->mkdir($img_dir, 0755, true);
                    }

                    // Create thumbnail directory if not exists
                    if (!$this->file->fileExists($thumb_dir)) {
                        $this->file->mkdir($thumb_dir, 0755, true);
                    }

                    $cookie_array = $img_data_post;
                    foreach ($cookie_array as $item) {
                        $item_url = trim($item);
                        if (!empty($item_url)) {
                            $fileInfo = $this->file->getPathInfo($item_url);
                            $basename = $fileInfo['basename'];
                            $file_name = explode("?", $basename);
                            $file_name = $file_name[0];
                            $file_name = str_replace("%20", " ", $file_name);
                            $img_url = $img_dir . "/" . $file_name;

                            // Write the main image
                            $this->file->write(
                                $img_url,
                                $this->driverFile->fileGetContents($item_url)
                            );

                            // Write the thumbnail image
                            $thumb_url = $thumb_dir . "/" . $file_name;
                            $this->file->write(
                                $thumb_url,
                                $this->driverFile->fileGetContents($item_url)
                            );
                        }
                    }

                    $relativeFilePath = 'wysiwyg/' . $dir_path_post . '/' . $file_name;
                    $absoluteFilePath = BP . '/pub/media/' . $relativeFilePath;
                    $filenameWithoutExtension = pathinfo($basename, PATHINFO_FILENAME);
                    $fileExtension = pathinfo($basename, PATHINFO_EXTENSION);
                    $connection = $this->resourceConnection->getConnection();
                    $mediaGalleryTable = $connection->getTableName('media_gallery_asset');
                    $imageDetails = getimagesize($absoluteFilePath);
                    if ($imageDetails !== false) {
                        $width = $imageDetails[0];
                        $height = $imageDetails[1];
                    }
                    $fileSize = filesize($absoluteFilePath);
                    if ($fileSize !== false) {
                        $fileSizeKB = round($fileSize / 1024, 2);
                    }
                    $record = [
                        'path' => $relativeFilePath,
                        'title' => $filenameWithoutExtension,
                        'source' => 'Local',
                        'hash' => sha1($relativeFilePath),
                        'content_type' => 'image/' . $fileExtension,
                        'width' => $width,
                        'height' => $height,
                        'size' => $fileSizeKB,
                    ];

                    $connection->insert($mediaGalleryTable, $record);
                    $res_array["status"] = 1;
                    $res_array["message"] = "successful ";
                } else {
                    $res_array["message"] = "Something went wrong.
                    Please reload the page and try again.";
                }
            } else {
                $res_array["message"] = "Sorry,
                you not selected any item ?.
                Please select item and try again";
            }
        }
        $json_data = json_encode($res_array);
        return $this->getResponse()->setBody($json_data);
    }
    /**
     * Local Credential
     *
     * @return $this
     */
    public function loadcredential()
    {
        $this->b_datahelper->getLoadCredential();
    }
}
