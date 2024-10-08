<?php

namespace Common\Workspaces\Rules;

use Illuminate\Support\Facades\Auth;
use Common\Workspaces\ActiveWorkspace;
use Illuminate\Validation\Rules\Unique;

class UniqueWorkspacedResource extends Unique
{
    public function __construct($table, $column = 'NULL', $userId = null)
    {
        parent::__construct($table, $column);
        if (!app(ActiveWorkspace::class)->isPersonal()) {
            $this->where('workspace_id', app(ActiveWorkspace::class)->id);
        } else {
            $this->where('user_id', $userId ?? Auth::guard('api')->id());
        }
    }
}
