<?php

namespace App\Controllers;

use MADEV\Core\Controllers\BaseController;

class Welcome extends BaseController
{
    public function index()
    {
        $data            = [];
        $data['content'] = '';
        return $this->renderView('layouts/main_layout', $data);
    }
}
