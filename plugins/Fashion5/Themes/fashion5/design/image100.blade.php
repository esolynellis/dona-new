<section class="module-item {{ $design ? 'module-item-design' : ''}}" id="module-{{ $module_id }}">
  @include('design._partial._module_tool')

  <div class="module-image-banner module-info mb-3 mb-md-5 d-flex justify-content-center">
    <div class="{{ $content['module_size'] ?? 'container-fluid' }} {{ $content['full'] ? 'w-100' : '' }} d-flex justify-content-center">
      <a href="{{ $content['images'][0]['link']['link'] ?: 'javascript:void(0)' }}"><img src="{{ $content['images'][0]['image'] }}" alt="{{ $content['images'][0]['image_alt'] ?? '' }}" class="img-fluid seo-img"></a>
    </div>
  </div>
</section>

@pushOnce('add-scripts')
<style>
  @media (min-width: 992px) {
    .module-image-banner .container-fluid:not(.w-100) {
      padding-left: 50px;
      padding-right: 50px;
    }
  }
</style>
@endpushOnce