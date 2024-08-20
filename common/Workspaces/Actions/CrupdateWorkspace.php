<?php

namespace Common\Workspaces\Actions;

use Illuminate\Support\Facades\Auth;
use Common\Workspaces\Workspace;

class CrupdateWorkspace
{
    public function __construct(protected Workspace $workspace)
    {
    }

    public function execute(
        array $data,
        Workspace|null $initialWorkspace = null
    ) {
        if ($initialWorkspace) {
            $workspace = $initialWorkspace;
        } else {
            $workspace = $this->workspace->newInstance([
                'owner_id' => Auth::guard('api')->id() ?: 1,
            ]);
        }

        $attributes = [
            'name' => $data['name'],
        ];

        $workspace->fill($attributes)->save();

        if (!$initialWorkspace) {
            $workspace
                ->members()
                ->create(['user_id' => Auth::guard('api')->id() ?: 1, 'is_owner' => true]);
        }

        return $workspace;
    }
}
