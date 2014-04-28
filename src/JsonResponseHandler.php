<?php


namespace Flow\PluploadEndpoint;

use Symfony\Component\HttpFoundation\Response;

class JsonResponseHandler
{
    /**
     * @var Pluploader
     */
    protected $pluploader;

    public function __construct(Pluploader $pluploader)
    {
        $this->pluploader = $pluploader;
    }

    /**
     * @return Response
     *
     */
    public function handle()
    {
        try {
            $this->pluploader->handle();
        } catch (PluploaderException $e) {
            return $e->createJsonResponse();
        }

        return new Response(
            json_encode(
                array(
                    "result" => null
                )
            ),
            201,
            array('Content-Type' => 'application/json')
        );
    }


} 