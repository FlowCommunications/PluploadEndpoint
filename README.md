Plupload Endpoint
=================

An endpoint handler for the Plupload uploader.

External dependencies
---------------------
- `Symfony\Component\HttpFoundation`
- `Symfony\Component\Filesystem`

Install
-------
```
composer require flow/plupload-endpoint:0.1.x
```

Usage
-----

### Framework agnostic request/response

```php
use Flow\PluploadEndpoint\JsonResponseHandler;
use Flow\PluploadEndpoint\Pluploader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();

$filesystem = new Filesystem();

$pluploader = new Pluploader($request, $filesystem, './uploads');

$handler = new JsonResponseHandler($pluploader);

$response = $handler->handle(); // returns Symfony\Component\HttpFoundation\Response

$response->send(); // Sends JSON to browser
```

### Laravel integration
```php
class Uploads extends Controller
{
	public function upload()
	{
        $pluploader = new Pluploader(
            App::make('request'),
            new \Symfony\Component\Filesystem\Filesystem(),
            '../app/storage/uploads'
        );

		$handler = new JsonResponseHandler($pluploader);

		return $handler->handle();
	}
}
```

Caveats
=======
- Not tested for Windows/IIS environments
- This package has no security features however here are a few tips:
    - Check the file extensions of uploaded files and exclude all extensions you're not expecting (ie. *.php files)
    - Don't put the uploaded files in a publicly accessible directory (ie. in the public_html folder)
    - Obfuscate the file name on upload
