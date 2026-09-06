<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a1f5c">

    <title>SIAKAD STTMI - Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body>

<div class="login-wrapper">

    <div class="login-glow"></div>
    <div class="login-glow two"></div>

    <div class="login-card">

        <div class="login-logo">

            <img
                src="{{ asset('images/logo_sttmi.jpeg') }}"
                alt="Logo STTMI"
                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">

            <div class="logo-badge" style="display:none">
                <span class="lb-star">⭐</span>
                <span class="lb-text">STTMI</span>
                <span class="lb-est">EST.</span>
            </div>

            <h1>SIAKAD STTMI</h1>
            <p>Sistem Informasi Akademik</p>

        </div>

        @if ($errors->any())
            <div class="login-error show">
                {{ $errors->first() }}
            </div>
        @endif


        <form
            method="POST"
            action="{{ route('login') }}"
            class="login-form">

            @csrf

         

            <div class="form-group">

                <label>

                     NIM / NIDN

                </label>

                <div class="input-wrap">

                   <span class="input-icon">
                         <i data-lucide="user"></i>
                    </span>

                    <input
                        type="text"
                        id="login"
                        name="login"
                        value="{{ old('login') }}"
                        placeholder="Masukkan NIM atau NIDN"
                        autocomplete="username"
                        autofocus
                        required>
                                        </div>

            </div>

            <div class="form-group">

                <label>Kata Sandi</label>

                <div class="input-wrap">

                    <span class="input-icon">
                         <i data-lucide="lock"></i>
                    </span>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan Kata Sandi"
                        autocomplete="current-password"
                        required>

                    <button
                        type="button"
                        class="toggle-pass"
                        onclick="togglePass(this)"
                        aria-label="Tampilkan kata sandi"
                        aria-pressed="false"
                    >

                       <i id="eyeIcon" data-lucide="eye"></i>

                    </button>

                </div>

            </div>

            <div class="form-meta">

                <label class="remember">

                    <input
                        type="checkbox"
                        name="remember">

                    Ingat Saya

                </label>

                @if (Route::has('password.request'))

                    <a
                        href="{{ route('password.request') }}"
                        class="forgot-link">

                        Lupa Kata Sandi?

                    </a>

                @endif

            </div>

            <button
                type="submit"
                class="btn-login">

                Masuk

            </button>

        </form>

        <div class="login-footer">

            <p>

                Butuh bantuan?
                <span class="campus-name">

                    UPT SIAKAD STTMI

                </span>

            </p>

        </div>

    </div>

</div>

<script>

function togglePass(button){

    const input = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");
    const showPassword = input.type === "password";

    input.type = showPassword ? "text" : "password";
    icon.setAttribute("data-lucide", showPassword ? "eye-off" : "eye");
    button.setAttribute("aria-pressed", String(showPassword));
    button.setAttribute("aria-label", showPassword ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");

    lucide.createIcons();

}

    lucide.createIcons();
</script>
</body>
</html>
