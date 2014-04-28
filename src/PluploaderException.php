<?php


namespace Flow\PluploadEndpoint;


use Symfony\Component\HttpFoundation\Response;

class PluploaderException extends \Exception
{

    const E_DIR_NOT_OPENABLE = 100;
    const E_DIR_NOT_OPENABLE_MESSAGE = 'Failed to open uploads directory';
    const E_FILE_INPUT = 101;
    const E_FILE_INPUT_MESSAGE = 'Failed to open input stream';
    const E_FILE_OUTPUT = 102;
    const E_FILE_OUTPUT_MESSAGE = 'Failed to open output stream';
    const E_FILE_MOVE = 103;
    const E_FILE_MOVE_MESSAGE = 'Failed to move uploaded file';
    const E_DIR_CREATE = 104;
    const E_DIR_CREATE_MESSAGE = 'Upload directory does not exist and could not be created';
    const E_DIR_FIND_PATH = 105;
    const E_DIR_FIND_PATH_MESSAGE = 'Failed to obtain path to upload directory';

    public function createJsonResponse()
    {
        return new Response(
            json_encode(
                array(
                    "error" => array(
                        "code"    => $this->code,
                        "message" => $this->message
                    )
                )
            ),
            500,
            array('Content-Type' => 'application/json')
        );
    }
} 