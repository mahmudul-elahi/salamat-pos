<?php

use App\Http\Controllers\Pos\Blog\BlogCategoryController;
use App\Http\Controllers\Pos\Blog\BlogPostController;
use App\Http\Controllers\Pos\Blog\BlogTagController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ecommerce', 'middleware' => 'auth.permission'], function (Router $router) {
    $router->apiResource('blog-categories', BlogCategoryController::class);
    $router->apiResource('blog-tags', BlogTagController::class);
    // Declared before the resource so "pinned" isn't captured as a {blog_post}.
    // Named ".lists"/".edit" so they map to the existing view_/update_
    // blog_posts permissions instead of minting new ones.
    $router->get('blog-posts/pinned', [BlogPostController::class, 'pinned'])
        ->name('blog-posts.lists');

    $router->post('blog-posts/pinned-order', [BlogPostController::class, 'updatePinnedOrder'])
        ->name('blog-posts.edit');

    $router->apiResource('blog-posts', BlogPostController::class);
    $router->patch('blog-posts/{blog_post}/toggle/{field}', [BlogPostController::class, 'toggle'])
        ->name('blog-posts.toggle');
});
