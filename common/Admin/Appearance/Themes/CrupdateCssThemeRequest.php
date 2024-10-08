<?php

namespace Common\Admin\Appearance\Themes;

use Illuminate\Support\Facades\Auth;
use Common\Core\BaseFormRequest;
use Illuminate\Validation\Rule;

class CrupdateCssThemeRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $required = $this->getMethod() === 'POST' ? 'required' : '';
        $ignore = $this->getMethod() === 'PUT' ? $this->route('css_theme')->id : '';
        $userId = $this->route('css_theme') ? $this->route('css_theme')->user_id : Auth::guard('api')->id();

        return [
            'name' => [
                $required, 'string', 'min:3',
                Rule::unique('css_themes')->where('user_id', $userId)->ignore($ignore)
            ],
            'is_dark' => 'boolean',
            'default_dark' => 'boolean',
            'default_light' => 'boolean',
            'colors' => 'array',
        ];
    }
}
