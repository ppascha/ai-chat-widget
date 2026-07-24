<?php

namespace AICW;

if (!defined('ABSPATH')) {
    exit;
}


require_once AICW_PATH . 'includes/class-assets.php';
require_once AICW_PATH . 'includes/class-widget.php';

require_once AICW_PATH . 'includes/API/class-rest-api.php';

require_once AICW_PATH . 'includes/Controllers/class-chat-controller.php';

require_once AICW_PATH . 'includes/Services/class-llm-service.php';


use AICW\API\Rest_API;
use AICW\Controllers\Chat_Controller;
use AICW\Services\LLM_Service;


class Plugin
{

    public static function init()
    {

        Assets::init();

        Widget::init();

        Rest_API::init();

    }

}