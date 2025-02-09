<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LevelMembershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'benchmark' => 'required|integer|min:0',
            'color' => 'required|string',
            'reward_memberships' => 'required|array',
            'id_reward_memberships' => 'nullable|array'
        ];
    }
}
