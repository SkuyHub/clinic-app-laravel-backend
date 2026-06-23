<?php

namespace App\Http\Controllers;

use App\CoreService\CallService;
use Illuminate\Http\Request;

class CrudController extends Controller
{
    public function index (Request $request, string $model)
    {
        return CallService::run('App\Services\Crud\Get', array_merge(
            $request->all(),
            ['model' => $model]
        ));
    }

    public function dataset(Request $request, string $model)
    {
        return CallService::run('App\Services\Crud\Get', array_merge(
            $request->all(),
            ['model' => $model]
        ));
    }

    public function show(Request $request, string $model, string $id)
    {
        return CallService::run('App\Services\Crud\Find', array_merge(
            $request->all(),
            ['model' => $model, 'id' => $id]
        ));
    }

    public function create(Request $request, string $model)
    {
        return CallService::run('App\Services\Crud\Add', array_merge(
            $request->all(),
            ['model' => $model]
        ));
    }

    public function update(Request $request, string $model)
    {
        return CallService::run('App\Services\Crud\Edit', array_merge(
            $request->all(),
            ['model' => $model]
        ));
    }

    public function delete(Request $request, string $model)
    {
        return CallService::run('App\Services\Crud\Delete', array_merge(
            $request->all(),
            ['model' => $model]
        ));
    }
}
