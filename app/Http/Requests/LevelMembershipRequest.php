<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            // 'name' => 'required|string|max:255',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('level_memberships', 'name')
                    ->ignore($this->level)
            ],
            'benchmark' => [
                'required',
                'integer',
                'min:0',
                Rule::unique('level_memberships', 'benchmark')
                    ->ignore($this->level)
            ],
            'color' => 'required|string',
            'category_product_id' => 'nullable|array',
            'id_reward_memberships' => 'nullable|array',
            'icon' => 'nullable|array',
            'level-reward-desc' => 'nullable|array',
            'name_product' => 'nullable|array',
            'without_reward' => 'nullable'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama level wajib diisi.',
            'name.max' => 'Nama level maksimal 255 karakter.',

            'benchmark.required' => 'Benchmark wajib diisi.',
            'benchmark.integer' => 'Benchmark harus berupa angka.',
            'benchmark.min' => 'Benchmark tidak boleh kurang dari 0.',
            'benchmark.unique' => 'Benchmark sudah digunakan oleh level lain.',

            'color.required' => 'Warna level wajib diisi.',
        ];
    }
}
