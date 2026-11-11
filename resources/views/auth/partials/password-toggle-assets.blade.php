{{-- Gaya + script tombol show/hide password. Ditaruh inline agar tidak tertahan cache login.css --}}
<style>
    .password-field {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .password-field input {
        flex: 1;
        min-width: 0;
    }

    .password-toggle {
        flex: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        margin: -10px -6px -8px 0;
        padding: 0;
        border: 0;
        border-radius: 4px;
        background: transparent;
        color: #8c7569;
        cursor: pointer;
        transition: 0.3s;
        -webkit-tap-highlight-color: transparent;
    }

    .password-toggle:hover {
        color: #55311c;
    }

    .password-toggle:focus-visible {
        outline: 2px solid #8c7569;
        outline-offset: -2px;
    }

    .password-toggle svg {
        width: 20px;
        height: 20px;
    }

    .password-toggle .icon-hide,
    .password-toggle[aria-pressed="true"] .icon-show {
        display: none;
    }

    .password-toggle[aria-pressed="true"] .icon-hide {
        display: block;
    }
</style>

<script>
    document.addEventListener("click", (event) => {
        const button = event.target.closest(".password-toggle");
        if (!button) return;

        const input = document.getElementById(button.getAttribute("aria-controls"));
        if (!input) return;

        const show = input.type === "password";
        const label = show ? "Sembunyikan password" : "Tampilkan password";

        input.type = show ? "text" : "password";
        button.setAttribute("aria-pressed", show ? "true" : "false");
        button.setAttribute("aria-label", label);
        button.title = label;

        // kembalikan fokus ke input, kursor di akhir teks
        input.focus();
        const end = input.value.length;
        input.setSelectionRange(end, end);
    });
</script>
