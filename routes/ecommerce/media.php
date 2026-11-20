<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Pos\Media\MediaController;

Route::group(['prefix' => 'ecommerce', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->get('medias', [MediaController::class, 'allMedias'])
        ->name('medias.index');

    $router->post('upload-medias/{folderId?}', [MediaController::class, 'updateMedia'])
        ->name('medias.update');

    $router->post('upload-medias-chunk/{folderId?}', [MediaController::class, 'uploadMediaChunk'])
        ->name('medias_video.upload');

    $router->delete('medias/{media}', [MediaController::class, 'delete'])
        ->name('medias.delete');

    $router->post('medias/bulk-delete', [MediaController::class, 'bulkDelete'])
        ->name('delete_medias.bulk');

    $router->post('folder-create', [MediaController::class, 'folderCreate'])
        ->name('media_folders.create');

    $router->patch('medias/alt-text/{media}', [MediaController::class, 'updateAltText'])
        ->name('media_alt_text.create');
});

// Without Permissions

Route::group(['prefix' => 'ecommerce'], function (Router $router) {
    $router->get('media-folders', [MediaController::class, 'allFolders'])
        ->name('media_folders.view');
});
