<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Log;

class InstallDemoDataController extends Controller
{
    public function demoData()
    {
        try {
//            \Illuminate\Support\Facades\Artisan::call('migrate');
//            \Illuminate\Support\Facades\Artisan::call('db:seed', [
//                '--class' => 'Database\Seeders\Permission\PermissionSeeder',
//                '--force' => true,
//            ]);
            $this->storageLink();

            return [
                'status' => true,
                'message' => "seed data import successfully"
            ];
        } catch (Exception $exception) {
            return [
                'status' => false,
                'message' => $exception->getMessage()
            ];
        }

//        return [
//            'status' => false,
//            'message' => "seed data import failed"
//        ];
    }

    public function storageLink(): self
    {
        try {
            $target = storage_path("app/public");
            $explode_base_path = explode(DIRECTORY_SEPARATOR, base_path());
            array_pop($explode_base_path);
            $explode_base_path[] = 'storage';
            $link = implode(DIRECTORY_SEPARATOR, $explode_base_path);

            // Check and remove existing link or directory
            if (is_link($link) || is_dir($link)) {
                if (is_link($link)) {
                    unlink($link); // Remove the symlink
                } elseif (is_dir($link)) {
                    // Recursively remove directory if it exists
                    $this->deleteDirectory($link);
                }
            }

            // Create the new symlink
            symlink($target, $link);
        } catch (\Exception $e) {
            Log::error('Failed to create symbolic link: ' . $e->getMessage());
        }
        return $this;
    }

    protected function deleteDirectory($dir): bool
    {
        if (!file_exists($dir)) {
            return false;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            $this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item);
        }

        return rmdir($dir);
    }
}
