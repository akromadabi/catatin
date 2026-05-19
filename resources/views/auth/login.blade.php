<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" autocomplete="off">
        @csrf

        <!-- Email Address -->
        <div class="mb-4">
            <label for="email" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition-colors" placeholder="user@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-rose-500 text-xs" />
        </div>

        <!-- Password -->
        <div class="mb-4">
            <label for="password" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition-colors" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-rose-500 text-xs" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between mb-6">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500 w-4 h-4" name="remember">
                <span class="ms-2 text-xs font-semibold text-slate-600">Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-xs font-bold text-brand-600 hover:text-brand-700 transition" href="{{ route('password.request') }}">
                    Lupa password?
                </a>
            @endif
        </div>

        <button type="submit" class="w-full bg-brand-600 text-white rounded-xl py-3 font-bold text-sm shadow-lg shadow-brand-500/30 hover:bg-brand-700 transition-colors">
            Masuk Sekarang
        </button>
        
        <div class="relative flex items-center justify-center mt-6 mb-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-slate-200"></div>
            </div>
            <span class="relative bg-white px-4 text-xs font-semibold text-slate-400">Atau</span>
        </div>

        <div class="flex items-center justify-center">
            <a href="{{ route('google.login') }}" class="w-full flex items-center justify-center px-4 py-3 border border-slate-200 rounded-xl shadow-sm text-sm font-semibold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Masuk dengan Google
            </a>
        </div>
        
        @if(app()->environment('local'))
        <div class="mt-8 border-t border-slate-100 pt-6">
            <p class="text-[10px] font-bold text-center text-slate-400 mb-3 uppercase tracking-wider">Local Testing Bypass</p>
            <div class="flex space-x-3">
                <a href="{{ route('quick.login', 'admin@catatin.com') }}" class="flex-1 flex justify-center items-center py-2 px-4 border border-brand-200 rounded-lg text-xs font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 transition">
                    Sbg Admin
                </a>
                <a href="{{ route('quick.login', 'user@catatin.com') }}" class="flex-1 flex justify-center items-center py-2 px-4 border border-slate-200 rounded-lg text-xs font-bold text-slate-700 bg-slate-50 hover:bg-slate-100 transition">
                    Sbg User
                </a>
            </div>
        </div>
        @endif
    </form>
</x-guest-layout>
