@if (has_translator())

    <div class="mt-1 auto-translation-wrap">
      <div class="auto-translation-info d-flex align-items-center">
        <span style="width: 40px">{{ __('LangPackGenerator::common.auto_translation') }}：</span>

        <select class="form-select form-select-sm w-auto from-locale-code" >
          @foreach (locales() as $locale)
            <option
              value="{{ $locale['code'] }}"  >{{ $locale['name'] }}</option>
          @endforeach
        </select>

        <span class="mx-1"><i class="bi bi-arrow-right"></i></span>

        <select class="form-select form-select-sm w-auto to-locale-code">
          <option value="all">{{ __('admin/common.all_others') }}</option>
          @foreach (locales() as $locale)
            <option value="{{ $locale['code'] }}">{{ $locale['name'] }}</option>
          @endforeach
        </select>
        @if($type === 'article')
          <span class="mx-1"><i class="bi bi-arrow-right"></i></span>
          <select class="form-select form-select-sm w-auto to-article-field">

            <option value="all">{{ __('admin/common.all_others') }}</option>
            <option value="content">{{ __('admin/page.info_content') }}(新)</option>
            <option value="title">{{ __('admin/page.info_title') }}</option>
            <option value="summary">{{ __('page_category.text_summary') }}</option>
            <option value="meta_title">{{ __('admin/setting.meta_title') }}</option>
            <option value="meta_description">{{ __('admin/setting.meta_description') }}</option>
            <option value="meta_keywords">{{ __('admin/setting.meta_keywords') }}</option>

          </select>
        @endif

        @if($type === 'page_category')
          <span class="mx-1"><i class="bi bi-arrow-right"></i></span>
          <select class="form-select form-select-sm w-auto to-article-field">
            <option value="all">{{ __('admin/common.all_others') }}</option>
            <option value="title">{{ __('admin/page.info_title') }}</option>
            <option value="summary">{{ __('page_category.text_summary') }}</option>
            <option value="meta_title">{{ __('admin/setting.meta_title') }}</option>
            <option value="meta_description">{{ __('admin/setting.meta_description') }}</option>
            <option value="meta_keywords">{{ __('admin/setting.meta_keywords') }}</option>

          </select>
        @endif
        <button style="width: 60px" type="button"
                class="btn btn-outline-secondary btn-sm ms-2 translate-btn">{{ __('admin/common.text_translate') }}</button>
      </div>
    </div>

@endif



