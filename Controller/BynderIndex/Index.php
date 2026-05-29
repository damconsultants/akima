<?php
/**
 * DamConsultants
 *
 * DamConsultants_Akima
 */

namespace DamConsultants\Akima\Controller\BynderIndex;

use DamConsultants\Akima\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\MediaGallerySynchronizationApi\Api\SynchronizeInterface;

class Index extends Action
{
    /**
     * @var Data
     */
    protected $b_datahelper;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var DriverFile
     */
    protected $driverFile;

    /**
     * @var SynchronizeInterface
     */
    protected $synchronize;

    /**
     * Constructor
     *
     * @param Context $context
     * @param File $file
     * @param DriverFile $driverFile
     * @param Data $bynderData
     * @param SynchronizeInterface $synchronize
     */
    public function __construct(
        Context $context,
        File $file,
        DriverFile $driverFile,
        Data $bynderData,
        SynchronizeInterface $synchronize
    ) {
        $this->b_datahelper = $bynderData;
        $this->file = $file;
        $this->driverFile = $driverFile;
        $this->synchronize = $synchronize;

        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $res_array = [
            "status"  => 0,
            "data"    => [],
            "message" => "Something went wrong. Please try again."
        ];

        $img_data_post = $this->getRequest()->getPost("img_data");
        $dir_path_post = $this->getRequest()->getPost("dir_path");

        /**
         * Validate AJAX Request
         */
        if (!$this->getRequest()->isAjax()) {

            $res_array["message"] = "Invalid request.";

            return $this->getResponse()->setBody(
                json_encode($res_array)
            );
        }

        /**
         * Validate Images
         */
        if (
            !isset($img_data_post) ||
            !is_array($img_data_post) ||
            count($img_data_post) <= 0
        ) {

            $res_array["message"] = "No images selected.";

            return $this->getResponse()->setBody(
                json_encode($res_array)
            );
        }

        /**
         * Validate Directory
         */
        if (
            !isset($dir_path_post) ||
            empty($dir_path_post)
        ) {

            $res_array["message"] = "Directory path missing.";

            return $this->getResponse()->setBody(
                json_encode($res_array)
            );
        }

        try {

            /**
             * Final Directory Path
             */
            $img_dir = BP . '/pub/media/wysiwyg/' . trim($dir_path_post, '/');

            /**
             * Create Directory If Not Exists
             */
            if (!$this->file->fileExists($img_dir, false)) {

                $this->file->mkdir(
                    $img_dir,
                    0755,
                    true
                );
            }

            /**
             * Sync Paths Array
             */
            $syncPaths = [];

            /**
             * Loop Images
             */
            foreach ($img_data_post as $item) {

                /**
                 * Clean URL
                 */
                $item_url = trim($item);

                $item_url = str_replace('?undefined', '', $item_url);
                $item_url = str_replace('&undefined', '', $item_url);

                if (empty($item_url)) {
                    continue;
                }

                /**
                 * Parse URL
                 */
                $parsedPath = parse_url(
                    $item_url,
                    PHP_URL_PATH
                );

                $fileInfo = $this->file->getPathInfo(
                    $parsedPath
                );

                $basename = $fileInfo['basename'];

                /**
                 * Fallback File Name
                 */
                if (empty($basename)) {

                    $basename = uniqid() . '.jpg';
                }

                /**
                 * Clean File Name
                 */
                $basename = urldecode($basename);

                /**
                 * Remove Special Characters
                 */
                $basename = preg_replace(
                    '/[^A-Za-z0-9\-\_\.]/',
                    '_',
                    $basename
                );

                /**
                 * DOWNLOAD IMAGE USING CURL
                 */
                $ch = curl_init($item_url);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);

                $fileContent = curl_exec($ch);

                $httpCode = curl_getinfo(
                    $ch,
                    CURLINFO_HTTP_CODE
                );

                $contentType = curl_getinfo(
                    $ch,
                    CURLINFO_CONTENT_TYPE
                );

                curl_close($ch);

                /**
                 * Validate Response
                 */
                if (
                    $httpCode != 200 ||
                    empty($fileContent)
                ) {

                    continue;
                }

                /**
                 * Detect Extension
                 */
                $extension = 'jpg';

                if (strpos($contentType, 'png') !== false) {

                    $extension = 'png';

                } elseif (strpos($contentType, 'jpeg') !== false) {

                    $extension = 'jpg';

                } elseif (strpos($contentType, 'gif') !== false) {

                    $extension = 'gif';

                } elseif (strpos($contentType, 'webp') !== false) {

                    $extension = 'webp';
                }

                /**
                 * Add Extension If Missing
                 */
                if (
                    !pathinfo(
                        $basename,
                        PATHINFO_EXTENSION
                    )
                ) {

                    $basename .= '.' . $extension;
                }

                /**
                 * Final File Name
                 */
                $file_name = strtolower($basename);

                /**
                 * Final Absolute Path
                 */
                $img_path = $img_dir . '/' . $file_name;

                /**
                 * SAVE IMAGE
                 */
                $this->file->write(
                    $img_path,
                    $fileContent
                );

                /**
                 * Proper Permissions
                 */
                chmod($img_path, 0644);

                /**
                 * Relative Media Path
                 */
                $relativePath = str_replace(
                    BP . '/pub/media/',
                    '',
                    $img_path
                );

                /**
                 * Add To Sync Array
                 */
                $syncPaths[] = $relativePath;
            }

            /**
             * Sync Media Gallery Automatically
             */
            if (!empty($syncPaths)) {

                $this->synchronize->execute($syncPaths);
            }

            $res_array["status"] = 1;
            $res_array["message"] = "Assets uploaded successfully.";

        } catch (\Exception $e) {

            $res_array["status"] = 0;
            $res_array["message"] = $e->getMessage();
        }

        return $this->getResponse()->setBody(
            json_encode($res_array)
        );
    }

    /**
     * Load Credential
     *
     * @return void
     */
    public function loadcredential()
    {
        $this->b_datahelper->getLoadCredential();
    }
}