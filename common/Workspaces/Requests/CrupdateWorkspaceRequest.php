<?php

namespace Common\Workspaces\Requests;

use Illuminate\Support\Facades\Auth;
use Common\Core\BaseFormRequest;
use Illuminate\Validation\Rule;

class CrupdateWorkspaceRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $required = $this->getMethod() === 'POST' ? 'required' : '';
        $ignore =
            $this->getMethod() === 'PUT' ? $this->route('workspace')->id : '';
        $userId = $this->route('workspace')
            ? $this->route('workspace')->user_id
            : Auth::guard('api')->id();

        return [
            'name' => [
                $required,
                'string',
                'min:3',
                Rule::unique('workspaces')
                    ->where('owner_id', $userId)
                    ->ignore($ignore),
            ],
        ];
    }
}
