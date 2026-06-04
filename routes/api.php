<?php
// asdsd
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\CheckinController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\Admin\AdminDonationController;
use App\Http\Controllers\Api\Admin\AdminContentController;
use App\Http\Controllers\Api\Admin\AdminRewardController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminArticleController;
use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\AdminTagController;

/*
|--------------------------------------------------------------------------
| API Routes — Sepatu Donasi
|--------------------------------------------------------------------------
*/

// ── Public routes ─────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

Route::get('content/homepage', [ContentController::class, 'homepage']);

// ── Public Artikel routes (NO AUTH) ───────────────────────────────────
Route::prefix('articles')->group(function () {
    Route::get('/',             [ArticleController::class, 'index']);              // GET /api/articles
    Route::get('/categories',   [ArticleController::class, 'getCategories']);     // GET /api/articles/categories
    Route::get('/tags',         [ArticleController::class, 'getTags']);           // GET /api/articles/tags
    Route::get('/{slug}',       [ArticleController::class, 'show']);              // GET /api/articles/{slug}
});

// ── Authenticated routes (Sanctum) ────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::get('me',     [AuthController::class, 'me']);
        Route::post('logout',[AuthController::class, 'logout']);
    });

    // Donasi
    Route::apiResource('donations', DonationController::class)
         ->only(['index', 'store', 'show']);

    // Check-in harian
    Route::prefix('checkin')->group(function () {
        Route::post('/',       [CheckinController::class, 'submit']);
        Route::get('/status',  [CheckinController::class, 'status']);
    });

    // Reward
    Route::prefix('rewards')->group(function () {
        Route::get('/',            [RewardController::class, 'index']);
        Route::post('{id}/claim',  [RewardController::class, 'claim']);
    });

    // ── Admin routes ──────────────────────────────────────────────────
    Route::prefix('admin')->middleware('role:admin')->group(function () {

        // Donasi management
        Route::get('donations',              [AdminDonationController::class, 'index']);
        Route::patch('donations/{id}',       [AdminDonationController::class, 'updateStatus']);
        Route::post('donations/{id}/update', [AdminDonationController::class, 'updateWithPhoto']);

        // CMS konten
        Route::get('content',           [AdminContentController::class, 'index']);
        Route::put('content/{key}',     [AdminContentController::class, 'updateText']);
        Route::post('content/image',    [AdminContentController::class, 'uploadImage']);

        // Reward management
        Route::get('rewards/claims',    [AdminRewardController::class, 'claims']);
        Route::apiResource('rewards', AdminRewardController::class)
             ->only(['index', 'store', 'update', 'destroy']);

        // User management
        Route::get('users',         [AdminUserController::class, 'index']);
        Route::patch('users/{id}',  [AdminUserController::class, 'toggleActive']);

        // ── Artikel Management (Admin) ────────────────────────────────
        Route::prefix('articles')->group(function () {
            Route::get('/',                 [AdminArticleController::class, 'index']);          // GET /api/admin/articles
            Route::post('/',                [AdminArticleController::class, 'store']);         // POST /api/admin/articles
            Route::get('/{id}',             [AdminArticleController::class, 'show']);          // GET /api/admin/articles/{id}
            Route::put('/{article}',        [AdminArticleController::class, 'update']);        // PUT /api/admin/articles/{id}
            Route::delete('/{article}',     [AdminArticleController::class, 'destroy']);       // DELETE /api/admin/articles/{id}
        });

        // ── Categories Management (Admin) ─────────────────────────────
        Route::prefix('categories')->group(function () {
            Route::get('/',                 [AdminCategoryController::class, 'index']);        // GET /api/admin/categories
            Route::post('/',                [AdminCategoryController::class, 'store']);        // POST /api/admin/categories
            Route::put('/{category}',       [AdminCategoryController::class, 'update']);       // PUT /api/admin/categories/{id}
            Route::delete('/{category}',    [AdminCategoryController::class, 'destroy']);      // DELETE /api/admin/categories/{id}
        });

        // ── Tags Management (Admin) ───────────────────────────────────
        Route::prefix('tags')->group(function () {
            Route::get('/',                 [AdminTagController::class, 'index']);             // GET /api/admin/tags
            Route::post('/',                [AdminTagController::class, 'store']);             // POST /api/admin/tags
            Route::put('/{tag}',            [AdminTagController::class, 'update']);            // PUT /api/admin/tags/{id}
            Route::delete('/{tag}',         [AdminTagController::class, 'destroy']);           // DELETE /api/admin/tags/{id}
        });
    });
});