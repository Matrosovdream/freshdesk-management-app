<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller
{
    public function show(Request $request, string $signed): Response
    {
        // TODO: resolve signed payload → stream file via FileStorageService.
        abort(404);
    }
}
