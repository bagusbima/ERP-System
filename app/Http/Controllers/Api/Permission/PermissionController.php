<?php

namespace App\Http\Controllers\Api\Permission;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Support\Permissions;
use Illuminate\Http\JsonResponse;

/**
 * The permission registry behind the role editor.
 *
 * Serving the labels from here keeps the SPA and the backend on a single
 * definition of what every permission key means.
 */
class PermissionController extends Controller
{
    use ApiResponse;

    /**
     * List every permission key, grouped by module and labelled.
     */
    public function index(): JsonResponse
    {
        return $this->success(Permissions::grouped());
    }
}
