<?php

namespace Psa\Core\Web;

class Request
{
    public function json()
    {
        return json_decode(file_get_contents('php://input'));
    }
}