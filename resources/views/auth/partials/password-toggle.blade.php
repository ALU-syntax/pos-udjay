{{--
    Tombol show/hide untuk input password.
    Pemakaian: bungkus input + partial ini dalam <div class="password-field">,
    lalu @include('auth.partials.password-toggle', ['target' => 'id-input'])
    Gaya dan script-nya ada di auth.partials.password-toggle-assets (cukup di-include sekali per halaman).
--}}
<button type="button" class="password-toggle" aria-controls="{{ $target }}" aria-label="Tampilkan password"
    aria-pressed="false" title="Tampilkan password">
    {{-- mata terbuka: tampil saat password disembunyikan --}}
    <svg class="icon-show" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
        <circle cx="12" cy="12" r="3" />
    </svg>
    {{-- mata dicoret: tampil saat password terlihat --}}
    <svg class="icon-hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
        <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
        <line x1="1" y1="1" x2="23" y2="23" />
    </svg>
</button>
