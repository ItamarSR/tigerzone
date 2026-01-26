<?php

declare(strict_types=1);

namespace TigerZone\Controllers;

class ErrorController extends BaseController
{
    public function notFound(): void
    {
        http_response_code(404);
        $this->view('errors.404');
    }
}
