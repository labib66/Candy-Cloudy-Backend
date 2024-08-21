<?php

use App\Http\Controllers\DriveEntriesController;
use App\Http\Controllers\DuplicateEntriesController;
use App\Http\Controllers\EntrySyncInfoController;
use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\FileEntryTagsController;
use App\Http\Controllers\FolderPathController;
use App\Http\Controllers\FoldersController;
use App\Http\Controllers\MoveFileEntriesController;
use App\Http\Controllers\ShareableLinkPasswordController;
use App\Http\Controllers\ShareableLinksController;
use App\Http\Controllers\SharesController;
use App\Http\Controllers\SpaceUsageController;
use App\Http\Controllers\StarredEntriesController;
use App\Http\Controllers\UserFoldersController;
use Illuminate\Support\Facades\Route;

use Common\Tags\TagController;
use Common\Auth\Roles\RolesController;
use Common\Auth\Roles\UserRolesController;
use Common\Pages\CustomPageController;
use App\Http\Controllers\UserApiController;
use App\Http\Controllers\SubscriptionApiController;
use Common\Auth\Controllers\UserController;
use Common\Billing\Products\ProductsController;
use Common\Settings\SettingsController;
use Common\Workspaces\Controllers\WorkspaceController;
use Common\Files\Controllers\FileEntriesController;
use Common\Files\Controllers\RestoreDeletedEntriesController;
use Common\Admin\Analytics\AnalyticsController;

// prettier-ignore
Route::group(['middleware' => ['auth:sanctum','verified', 'verifyApiAccess']], function () {
  Route::group(['prefix' => 'v1'], function() {
    // , 'verified', 'verifyApiAccess'
    // Route::group(['middleware' => ['optionalAuth:sanctum', 'verified', 'verifyApiAccess']], function () {
    //acount Setting
        Route::get('user_settings', [
          UserController::class,
          'user_settings',
        ]);
        
        Route::post('user_settings', [
          UserController::class,
          'update_user_settings',
        ]);
    
        Route::post('user_settings/password/{id}', [
          UserController::class,
          'updatePassword',
        ]);
    
        
        Route::post('user_settings/delete/{id}', [
          UserController::class,
          'destroy',
        ]);
    
      



      // Route::get('users/{id}', [
      //   SharesController::class,
      //   'me',
      // ]);



      //workspace
      Route::group(['prefix' => 'workspace'], function () {
          Route::get('/', [WorkspaceController::class, 'index']);
          Route::get('/{workspace}', [WorkspaceController::class, 'show']);
          Route::post('/', [WorkspaceController::class, 'store']);
          Route::put('/{workspace}', [WorkspaceController::class, 'update']);
          Route::delete('/{ids}', [WorkspaceController::class, 'destroy']);
      });


      // upload
      Route::get('uploads/', [
        FileEntriesController::class,
        'index',
      ]);

      Route::post('uploads/', [
        FileEntriesController::class,
        'store',
      ]);


    // folders and files 
      Route::post('folders', 
        [FoldersController::class, 'store']);

        Route::get('drive/file-entries', [
          DriveEntriesController::class,
          'main',
        ]);

        
      Route::post('file-entries/move', [
        MoveFileEntriesController::class,
        'move',
      ]);

      Route::post('file-entries/restore', [
        RestoreDeletedEntriesController::class,
        'restore',
      ]);

      Route::middleware('auth:sanctum')->group(function () {
        Route::post('file-entries/duplicate', [DuplicateEntriesController::class, 'duplicate']);
    });
      

      Route::post('shareable-links/{linkId}/import', [
        SharesController::class,
        'addCurrentUser',
        ])->middleware('auth:api');

      // SHARING 

      // shareWithEmail
      Route::post('file-entries/{id}/share', [
        SharesController::class,
        'addUsers',
      ])->middleware('auth:api');

    //???
      // Route::post('file-entries/{linkId}/import', [
      //   SharesController::class,
      //   'addCurrentUser',
      // ]);

      Route::post('file-entries/{id}/unshare', [
        SharesController::class,
        'removeUser',
      ]);
      Route::put('file-entries/{id}/change-permissions', [
        SharesController::class,
        'changePermissions',
      ]);



      Route::get('file-entries/{id}/shareable-link', [
        ShareableLinksController::class,
        'show',
      ]);
      Route::post('file-entries/{id}/shareable-link', [
        ShareableLinksController::class,
        'store',
      ]);
      Route::put('file-entries/{id}/shareable-link', [
        ShareableLinksController::class,
        'update',
      ]);
      Route::delete('file-entries/{id}/shareable-link', [
        ShareableLinksController::class,
        'destroy',

      ]);

    Route::post('shareable-links/{linkHash}/check', [
      ShareableLinkPasswordController::class,
      'check',
    ]);


      // files
      Route::group(['prefix' => 'files'], function () {
          Route::get('/', [FileEntriesController::class, 'index']);
          Route::get('/{fileEntry}', [FileEntriesController::class, 'show']);
          Route::get('/{fileEntry}/model', [FileEntriesController::class, 'showModel']);
          Route::post('', [FileEntriesController::class, 'store']);
          Route::put('/{entryId}', [FileEntriesController::class, 'update']);
          Route::delete('/{entryIds?}', [FileEntriesController::class, 'destroy']);
        });

    
    // ENTRIES
      Route::get('drive/file-entries/{fileEntry}/model', [
        DriveEntriesController::class,
        'showModel',
      ]);

      Route::post('file-entries/sync-info', [
        EntrySyncInfoController::class,
        'index',
      ]);
      
      Route::post('file-entries/{fileEntry}/sync-tags', [
        FileEntryTagsController::class,
        'sync',
      ]);
      

      // FOLDERS
      
      Route::get('users/{userId}/folders', [
        UserFoldersController::class,
        'index',
      ]);
      Route::get('folders/{hash}/path', [
        FolderPathController::class,
        'show',
      ]);

      // Tags/Labels
      // Route::get('file-entry-tags', [
      //   FileEntryTagsController::class,
      //   'index',
      // ]);
      
      Route::post('file-entries/stared', [
        StarredEntriesController::class,
        'index',
      ]);
      
      Route::post('file-entries/star', [
        StarredEntriesController::class,
        'add',
      ]);

      Route::post('file-entries/unstar', [
        StarredEntriesController::class,
        'remove',
      ]);

      //SPACE USAGE
      Route::get('user/space-usage', [SpaceUsageController::class, 'index']);

      // FCM TOKENS
      Route::post('fcm-token', [FcmTokenController::class, 'store']);
    });
  
    });

    //admin
      
  // Route::group(['middleware' => ['auth:sanctum','verified', 'verifyApiAccess','admin']], function () {
    Route::controller(TagController::class)->group(function () {
      Route::get("allTags","indexApi");
      Route::get('/showTag/{id}', 'showApi'); 
      Route::post("addTags","storApi");
      Route::put('/updateTag/{id}', 'updateApi');
      Route::delete('/deleteTag/{id}', 'deleteApi');
    });

    Route::controller(RolesController::class)->group(function () {
      Route::get("allRole","indexApi");
      Route::get("showRole/{id}","showApi");
      Route::post("addRole","storApi");
      Route::put("updateRole/{id}", "updateApi");
      Route::delete("deleteRole/{id}", "deleteApi");
    });
    

    Route::post('/users/{userId}/roles/update', [UserRolesController::class, 'updateRoles']);
    Route::get('/users/{userId}/roles', [UserRolesController::class, 'getRoles']);

    Route::controller(CustomPageController::class)->group(function () {
      Route::get("allPages","indexApi");
      Route::get("showPage/{id}","showApi");
      Route::post("addPage","storApi");
      Route::put("updatePage/{id}", "updateApi");
      Route::delete("deletePage/{id}", "deleteApi");
    });

    Route::controller(UserApiController::class)->group(function () {
      Route::get("handleUsers", "handleUsers");
      Route::get("showUser/{id}", "showApi");
      Route::post("addUser", "storeApi");
      Route::put("updateUser/{id}", "update");
      Route::delete("deleteUser/{id}", "delete");
      Route::put("changPassword/{id}", "updatePassword");
      Route::put("changPassword/{id}", "updatePassword");
    });

    Route::controller(ProductsController::class)->group(function () {
      Route::get("allProducts", "indexApi");
      Route::get("showProduct/{id}", "showApi");
      Route::post("addProduct", "storeApi");
      Route::put("updateProduct/{id}", "updateApi");
      Route::delete("deleteProduct/{id}", "deleteApi");
    });

    Route::controller(SubscriptionApiController::class)->group(function () {
      Route::get("allSubscriptions", "indexApi");
      Route::get("showSubscription/{id}","showApi");
      Route::post("addSubscription", "storeApi");
      Route::put("updateSubscription/{id}", "updateApi");
      Route::delete("deleteSubscription/{id}", "deleteApi");
    });



      // Route::controller(LocalizationsController::class)->group(function () {
        //   Route::get("Localizations", "indexApi");
      //   Route::post("addLang", "storeApi");
      // });



      Route::get('/settings', [SettingsController::class, 'index']);
      Route::get('/allsettings', [SettingsController::class, 'indexApi']);
      Route::put('/editsettings', [SettingsController::class, 'persistApi']);

        //ali
        Route::get('admin/reports/visitors/{selected?}', [AnalyticsController::class, 'visitorsReport']);
        Route::get('admin/reports/mainReport', [AnalyticsController::class, 'mainReport']);


    // });


