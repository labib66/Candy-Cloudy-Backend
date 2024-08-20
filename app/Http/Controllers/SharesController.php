<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\FileEntry;
use App\Models\ShareableLink;
use App\Models\User;
use App\Notifications\FileEntrySharedNotif;
use App\Services\Shares\AttachUsersToEntry;
use App\Services\Shares\DetachUsersFromEntries;
use App\Services\Shares\GetUsersWithAccessToEntry;
use Common\Core\BaseController;
use Common\Files\Traits\LoadsAllChildEntries;
use Common\Settings\Settings;
use Common\Validation\Validators\EmailsAreValid;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class SharesController extends BaseController
{
    use LoadsAllChildEntries;

    public function __construct(
        private Request $request,
        private Settings $settings,
    ) {}

    /**
     * Import entry into current user's drive using specified shareable link.
     */
    public function addCurrentUser(
        int $linkId,
        AttachUsersToEntry $action,
        ShareableLink $linkModel,
    ): JsonResponse {
        /* @var ShareableLink $link */
        $user = $this->request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $link = $linkModel->with('entry')->findOrFail($linkId);

        // $this->authorize('show', [$link->entry, $link]);

        $permissions = [
            'view' => true,
            'edit' => $link->allow_edit,
            'download' => $link->allow_download,
        ];

        $action->execute(
            [$this->request->user()->email],
            [$link->entry_id],
            $permissions,
        );

        $users = app(GetUsersWithAccessToEntry::class)->execute(
            $link->entry_id,
        );

        return $this->success(['users' => $users]);
    }

    public function addUsers(int $entryId, AttachUsersToEntry $action)
    {
        $user = $this->request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Check if the file exists
        $file = File::find($entryId); // Assuming you have a File model
        if (!$file) {
            return response()->json([
                'status' => 'error',
                'message' => 'File not found. Please check the file ID and try again.',
            ], 404);
        }

        // // Get the emails and permissions from the request
        // $shareeEmails = $this->request->get('emails', []);
        // $permissions = $this->request->get('permissions', []);

        // Validate the input
        $this->validate(
            $this->request,
            [
                'emails' => ['required', 'array', new EmailsAreValid()],
                // 'permissions' => 'required', 'array',
            ],
            [],
            [
                'emails' => 'Please provide valid email addresses.',
                // 'permissions' => 'Please provide valid permissions.',
            ]
        );

        // Attach users to the entry with specified permissions
        // $sharees = $action->execute($shareeEmails, [$entryId], $permissions);

        $users = app(GetUsersWithAccessToEntry::class)->execute($entryId);

        // Send notification
        if ($this->settings->get('drive.send_share_notification')) {
            try {
                Notification::send(
                    $users,
                    new FileEntrySharedNotif([$entryId], Auth::user()),
                );
            } catch (Exception) {
                Log::error('Failed to send notification. Error: ' . $this->getMessage());
            }
        }

        // Notification::send($users, new FileEntrySharedNotif([$entryId], $user));

        // Return a success response
        return response()->json([
            'status' => 'success',
            'message' => 'Files shared successfully.',
            'data' => $users,
        ], 201);
    }


    public function changePermissions(int $entryId)
    {
        $this->request->validate([
            'permissions' => 'required|array',
            'userId' => 'required|int',
        ]);
        // $this->authorize('update', [FileEntry::class, [$entryId]]);
        DB::table('file_entry_models')
            ->where('model_id', $this->request->get('userId'))
            ->where('model_type', 'user')
            ->whereIn(
                'file_entry_id',
                $this->loadChildEntries([$entryId])->pluck('id'),
            )
            ->update([
                'permissions' => json_encode(
                    $this->request->get('permissions'),
                ),
            ]);
        $users = app(GetUsersWithAccessToEntry::class)->execute($entryId);
        return $this->success(['users' => $users]);
    }

    public function removeUser(
        string $entryIds,
        DetachUsersFromEntries $action,
    ): JsonResponse {
        $userId =
            $this->request->get('userId') === 'me'
            ? Auth::guard('api')->id()
            : (int) $this->request->get('userId');
        $entryIds = explode(',', $entryIds);

        // there's no need to authorize if user is
        // trying to remove himself from the entry
        if ($userId !== Auth::guard('api')->id() ) {
            // $this->authorize('update', [FileEntry::class, $entryIds]);
        }

        $action->execute(collect($entryIds), collect([$userId]));

        $users = app(GetUsersWithAccessToEntry::class)->execute(
            head($entryIds),
        );
        return $this->success(['users' => $users]);
    }
}
