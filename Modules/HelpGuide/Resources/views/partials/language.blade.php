<div class="hg-language-bar">
    <form method="get" action="{{ url()->current() }}" class="hg-language-select">
        <label for="hg_language">Language</label>
        <select id="hg_language" name="lang" onchange="this.form.submit()">
            @foreach($languages as $language)
                <option value="{{ $language->code }}" @selected($selectedLanguage===$language->code)>{{ $language->native_name ?: $language->name }}{{ $language->name !== ($language->native_name ?: $language->name) ? ' ('.$language->name.')' : '' }}</option>
            @endforeach
        </select>
    </form>
    <form method="post" action="{{ route('helpguide.language.default') }}" class="hg-language-default">@csrf
        <input type="hidden" name="language_code" value="{{ $selectedLanguage }}">
        <button class="hg-btn secondary" type="submit" @disabled($selectedLanguage===$defaultLanguage)>{{ $selectedLanguage===$defaultLanguage ? 'Default language' : 'Make default' }}</button>
    </form>
</div>
