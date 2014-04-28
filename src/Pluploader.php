<?php
namespace Flow\PluploadEndpoint;

use FilesystemIterator;
use GlobIterator;
use SplFileInfo;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Pluploader
{

    protected $cleanUp = true;

    protected $createUploadDir = true;

    protected $tempSuffix = '.part';

    protected $maxFileAge = 18000; // 5 hours

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $uploadDir;

    /**
     * @param Request $request
     * @param Filesystem $filesystem
     * @param string $uploadDir
     */
    function __construct(Request $request, Filesystem $filesystem, $uploadDir)
    {
        $this->uploadDir  = rtrim($uploadDir, "/");
        $this->filesystem = $filesystem;
        $this->request    = $request;
    }

    /**
     * @param boolean $cleanUp
     */
    public function setCleanUp($cleanUp)
    {
        $this->cleanUp = $cleanUp;
    }

    /**
     * @param boolean $createUploadDir
     */
    public function setCreateUploadDir($createUploadDir)
    {
        $this->createUploadDir = $createUploadDir;
    }

    /**
     * Update script time limit if not configured in php ini
     * @param int $minutes
     */
    public function setTimeLimit($minutes = 5)
    {
        set_time_limit($minutes * 60);
    }

    /**
     * Handle uploaded file
     * @return bool
     * @throws PluploaderException
     */
    public function handle()
    {
        if ($this->createUploadDir) {
            try {
                $this->createUploadDir();
            } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
            }
        }

        if (!is_dir($this->uploadDir)) {
            throw new PluploaderException(
                PluploaderException::E_DIR_CREATE_MESSAGE,
                PluploaderException::E_DIR_CREATE
            );
        }

        // Get a file name
        $fileName = $this->extractFileName();

        $uploadedFilePath = $this->uploadDir . DIRECTORY_SEPARATOR . $fileName;

        // Chunking might be enabled
        $chunk  = $this->request->get('chunk') ? (int) $this->request->get('chunk') : 0;
        $chunks = $this->request->get('chunks') ? (int) $this->request->get('chunks') : 0;

        if (!is_readable($this->uploadDir)) {
            throw new PluploaderException(
                PluploaderException::E_DIR_NOT_OPENABLE_MESSAGE,
                PluploaderException::E_DIR_NOT_OPENABLE
            );
        }

        // Remove old temp files
        if ($this->cleanUp) {
            $this->cleanUp();
        }

        // Open temp file
        $handleFlag        = $chunks ? "ab" : "wb"; // Select 'append' for chunked uploads
        $partialFileHandle = fopen($uploadedFilePath . $this->tempSuffix, $handleFlag);

        if (!$partialFileHandle) {
            throw new PluploaderException(
                PluploaderException::E_FILE_OUTPUT_MESSAGE,
                PluploaderException::E_FILE_OUTPUT
            );
        }

        if ($this->request->files->count() > 0) {
            /*
             * Handle HTML4-style non-chunked file upload
             */

            /**
             * @var UploadedFile $file
             */
            $file = $this->request->files->get('file');

            if ($file->getError() || !file_exists($file->getPathname())) {
                throw new PluploaderException(
                    PluploaderException::E_FILE_MOVE_MESSAGE,
                    PluploaderException::E_FILE_MOVE
                );
            }

            $inputHandle = fopen($file->getPathname(), "rb");

            // Read binary input stream and append it to temp file
            if (!$inputHandle) {
                throw new PluploaderException(
                    PluploaderException::E_FILE_INPUT_MESSAGE,
                    PluploaderException::E_FILE_INPUT
                );
            }
        } else {
            $inputHandle = @fopen("php://input", "rb");

            if (!$inputHandle) {
                throw new PluploaderException(
                    PluploaderException::E_FILE_INPUT_MESSAGE,
                    PluploaderException::E_FILE_INPUT
                );
            }
        }

        /**
         * Read input and write to partial file
         */
        while ($buff = fread($inputHandle, 4096)) {
            fwrite($partialFileHandle, $buff);
        }

        fclose($partialFileHandle);
        fclose($inputHandle);

        /*
         * Finally, rename the file if this is either
         * the last chunk or it is not a chunked upload
         */
        if (!$chunks || $chunk == $chunks - 1) {
            rename($uploadedFilePath . $this->tempSuffix, $uploadedFilePath);
        }

        // Return success response
        return true;
    }

    public function createUploadDir()
    {
        $this->filesystem->mkdir($this->uploadDir);
    }

    /**
     * Extract file name from request
     *
     * @return string
     */
    public function extractFileName()
    {
        if ($this->request->get('name')) {

            $fileName = $this->request->get('name');

        } elseif ($this->request->files->get('file')) {

            $fileName = $this->request->files->get('file')->getFilename();

        } else {

            $fileName = uniqid('file_');

        }

        return $fileName;
    }

    /**
     * Remove all old partially uploaded files
     */
    public function cleanUp()
    {
        $glob = new GlobIterator($this->uploadDir . '/*' . $this->tempSuffix);

        /**
         * @var SplFileInfo[] $glob
         */
        foreach ($glob as $file) {
            $globFilePath = $file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename();

            if ($file->getMTime() < time() - $this->maxFileAge) {
                $file = null;
                unlink($globFilePath);
            }
        }
    }
}