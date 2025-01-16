<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


class LoginKasirRequest extends FormRequest
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
            'email' => ['required', 'string', 'email'],  
            'pin' => ['required', 'string', 'size:6'], // Misalnya, PIN terdiri dari 4 digit  
        ];  ;
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void  
    {  
        $this->ensureIsNotRateLimited();  
  
        $user = User::where('email', $this->input('email'))->first();  

  
        if (!$user || !Hash::check($this->input('pin'), $user->pin)) {  
            RateLimiter::hit($this->throttleKey());  
  
            throw ValidationException::withMessages([  
                'email' => __('auth.failed'),  
                'pin' => "Pin Salah"
            ]);  
        }  
  
        // Jika menggunakan session atau token, Anda bisa menambahkan logika di sini  
        Auth::login($user);  
        
        RateLimiter::clear($this->throttleKey());  

    }  
  
    public function ensureIsNotRateLimited(): void  
    {  
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {  
            return;  
        }  
  
        event(new Lockout($this));  
  
        $seconds = RateLimiter::availableIn($this->throttleKey());  
  
        throw ValidationException::withMessages([  
            'email' => trans('auth.throttle', [  
                'seconds' => $seconds,  
                'minutes' => ceil($seconds / 60),  
            ]),  
        ]);  
    }  
  
    public function throttleKey(): string  
    {  
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());  
    }  
}
