<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class HelperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadGlobalHelpers();
    }

    /**
     * Load all global helper files from the Helpers/Global directory.
     */
    private function loadGlobalHelpers(): void
    {
        $helpersDirectory = app_path('Helpers'.DIRECTORY_SEPARATOR.'Global');
        $directoryIterator = new RecursiveDirectoryIterator($helpersDirectory);
        $iterator = new RecursiveIteratorIterator($directoryIterator);

        while ($iterator->valid()) {
            $currentFile = $iterator->current();

            if (
                $iterator->isFile() &&
                ! $iterator->isDot() &&
                $iterator->isReadable() &&
                $currentFile->getExtension() === 'php' &&
                str_contains($currentFile->getFilename(), 'Helper')
            ) {
                require_once $iterator->key();
            }

            $iterator->next();
        }
    }
}
