<?php

namespace AICW\Controllers;

use AICW\Services\LLM_Service;

class Chat_Controller
{
    public function process($request)
    {
        $message = $request->get_param('message');

        $response = LLM_Service::ask($message);

        return [
            'success' => true,
            'message' => $response
        ];
    }
}