<?php

namespace App\CoreService;

use Illuminate\Support\Facades\DB;

class CallService
{
    public static function run(string $serviceName, $input)
    {
        try {
            $result = self::call($serviceName, $input);
            return CoreResponse::ok($result);
        } catch (CoreException $ex) {
            return CoreResponse::fail($ex);
        } catch (\Exception $ex) {
            return CoreResponse::error(new CoreException($ex->getMessage(), 500));
        }
    }

    public static function execute(string $serviceName, $input)
    {
        try {
            $object = app()->make($serviceName);

            if (isset($object->task) && !hasPermission($object->task)) {
                throw new CoreException(__('message.403'), 403);
            }

            $result = self::call($serviceName, $input);
            return CoreResponse::ok($result);
        } catch (CoreException $ex) {
            return CoreResponse::fail($ex);
        } catch (\Exception $ex) {
            return CoreResponse::error(new CoreException($ex->getMessage(), 500));
        }
    }

    public static function call(string $serviceName, $input)
    {
        $object = app()->make($serviceName);

        if (!empty($object->transaction)) {
            DB::beginTransaction();

            try {
                $result = $object->execute($input);
                DB::commit();
                return $result;
            } catch (\Throwable $ex) {
                DB::rollBack();
                throw $ex;
            }
        }

        return $object->execute($input);
    }
}