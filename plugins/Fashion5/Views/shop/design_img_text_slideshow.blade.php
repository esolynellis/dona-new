@push('header')
  <script src="{{ asset('vendor/swiper/swiper-bundle.min.js') }}"></script>
  <link rel="stylesheet" href="{{ asset('vendor/swiper/swiper-bundle.min.css') }}">
@endpush

<section class="module-item {{ $design ? 'module-item-design' : ''}}" id="module-{{ $module_id }}">
  @include('design._partial._module_tool')

  <div class="module-info mb-3 mb-md-5">
    <div class="{{ $content['module_size'] ?? 'w-100' }}">
      <div class="swiper module-swiper-img-text-{{ $module_id }} module-img-text-slideshow">
        <div class="swiper-wrapper">
          @foreach($content['images'] as $image)
            <div class="swiper-slide">
              <div class="image-wrap" style="background-image: url({{ $image['image'] }})">
                <div class="container content-wrap {{ $image['text_position'] }}">
                  <div class="text-wrap" data-swiper-parallax-y="-100" data-swiper-parallax-duration="1000" data-swiper-parallax-opacity="0.5" >
                    @if ($image['sub_title'])
                      <div class="sub-title">{{ $image['sub_title'] }}</div>
                    @endif
                    @if ($image['title'])
                      <h2 class="title">{{ $image['title'] }}</h2>
                    @endif
                    @if ($image['description'])
                      <p class="description">{{ $image['description'] }}</p>
                    @endif
                    @if ($image['link']['link'])
                      <a href="{{ $image['link']['link'] ?: 'javascript:void(0)' }}" class="btn">{{ __('shop/account.check_details') }}</a>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
        <div class="swiper-pagination slideshow-pagination-{{ $module_id }}"></div>
      </div>
    </div>
  </div>

  <script>
    var moduleSwiperImgText_{{ $module_id }} = new Swiper ('.module-swiper-img-text-{{ $module_id }}', {
      loop: true,
      parallax : true,
      pauseOnMouseEnter: true,
      clickable :true,
      effect: 'fade',

      pagination: {
        el: '.slideshow-pagination-{{ $module_id }}',
        clickable: true
      },

      autoplay: {
        delay: 3000,
        disableOnInteraction: false
      },
    })

    $('.module-img-text-slideshow').hover(function() {
      moduleSwiperImgText_{{ $module_id }}.autoplay.pause();
    }, function() {
      swiper.autoplay.start(); // 启动自动播放
    });
  </script>
  @if (version_compare(config('beike.version'), '1.6.0') < 0)
  <style>
    .module-img-text-slideshow .swiper-pagination-bullet-active {
      background-color: #fff;
    }
  </style>
  @endif
</section>



