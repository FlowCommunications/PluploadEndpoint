<?php
namespace Flow\PluploadEndpoint;

use Flow\PluploadEndpoint\JsonResponseHandler;
use Flow\PluploadEndpoint\Pluploader;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

require "../vendor/autoload.php";


class PluploaderTest extends \PHPUnit_Framework_TestCase
{
    protected $uploadPath = '/tmp/plupload-uploads';

    public function tearDown()
    {
        if (is_dir($this->uploadPath)) {
            exec('rm -r ' . $this->uploadPath);
        }
    }

    public function setUp()
    {
        if (is_dir($this->uploadPath)) {
            exec('rm -r ' . $this->uploadPath);
        }
    }

    /**
     * @expectedException \Flow\PluploadEndpoint\PluploaderException
     */
    public function testException()
    {
        $request    = Request::createFromGlobals();

        $filesystem = new Filesystem();

        $pluploader = new Pluploader($request, $filesystem, '/baz');

        $pluploader->handle();
    }

    /**
     *
     */
    public function testExceptionInResponseHandler()
    {
        $request = Request::createFromGlobals();

        $filesystem = new Filesystem();

        $pluploader = new Pluploader($request, $filesystem, '/baz');

        $handler = new JsonResponseHandler($pluploader);

        $response = $handler->handle();

        $this->assertEquals('{"error":{"code":104,"message":"Upload directory does not exist and could not be created"}}', $response->getContent());
    }

    public function testCleanup()
    {
        $request    = Request::createFromGlobals();
        $filesystem = new Filesystem();

        $pluploader = new Pluploader($request, $filesystem, $this->uploadPath);
        $pluploader->createUploadDir();

        touch($this->uploadPath . '/new.part');
        touch($this->uploadPath . '/old.part');
        touch($this->uploadPath . '/old.part', time() - 18001);

        $this->assertFileExists($this->uploadPath . '/new.part');
        $this->assertFileExists($this->uploadPath . '/old.part');

        $pluploader->handle();

        $this->assertFileExists($this->uploadPath . '/new.part');
        $this->assertFileNotExists($this->uploadPath . '/old.part');
    }

    public function testHtml4Upload()
    {
        $fileName = md5(rand()) . '.jpg';

        $tmpFilePath = '/tmp/' . $fileName;

        touch($tmpFilePath);

        $_POST = array(
            'name' => $fileName,
        );

        $_FILES = array(
            'file' =>
                array(
                    'name'     => $fileName,
                    'type'     => 'image/jpeg',
                    'tmp_name' => $tmpFilePath,
                    'error'    => 0,
                    'size'     => filesize($tmpFilePath),
                ),
        );

        $request = Request::createFromGlobals();

        $filesystem = new Filesystem();

        $pluploader = new Pluploader($request, $filesystem, $this->uploadPath);

        $pluploader->handle();

        $this->assertFileExists($this->uploadPath . '/' . $fileName);

        unlink($tmpFilePath);
    }

    public function testHtml5ChunkedUpload()
    {
        $fileName = md5(rand()) . '.jpg';

        $tmpFilePath = '/tmp/' . $fileName;

        touch($tmpFilePath);

        $_POST = array(
            'name'   => $fileName,
            'chunk'  => '0',
            'chunks' => '5',
        );

        $_FILES = array(
            'file' =>
                array(
                    'name'     => 'blob',
                    'type'     => 'application/octet-stream',
                    'tmp_name' => $tmpFilePath,
                    'error'    => 0,
                    'size'     => filesize($tmpFilePath),
                ),
        );

        $request = Request::createFromGlobals();

        $filesystem = new Filesystem();

        $pluploader = new Pluploader($request, $filesystem, $this->uploadPath);

        $pluploader->handle();

        $this->assertFileExists($this->uploadPath . '/' . $fileName . '.part');

        unlink($tmpFilePath);

    }


}