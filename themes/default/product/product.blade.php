@extends('layout.master')
@section('body-class', 'page-product')
@section('title', $product['meta_title'] ?: $product['name'])
@section('keywords', $product['meta_keywords'] ?: system_setting('base.meta_keyword'))
@section('description', $product['meta_description'] ?: system_setting('base.meta_description'))

@push('header')
  <script src="{{ asset('vendor/vue/2.7/vue' . (!config('app.debug') ? '.min' : '') . '.js') }}"></script>
  <script src="{{ asset('vendor/swiper/swiper-bundle.min.js') }}"></script>
  <script src="{{ asset('vendor/zoom/jquery.zoom.min.js') }}"></script>
  <link rel="stylesheet" href="{{ asset('vendor/swiper/swiper-bundle.min.css') }}">
  @if ($product['video'] && strpos($product['video'], '<iframe') === false)
    <script src="{{ asset('vendor/video/video.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('vendor/video/video-js.min.css') }}">
  @endif
  <style>
    /* ── Similar & Relations wrapper ── */
    .similar-wrap, .relations-wrap {
      background: linear-gradient(160deg, #fff8f5 0%, #fff3ee 40%, #fdf6ff 100%);
      padding: 56px 0 64px;
      border-top: 1px solid #ffe5d8;
      position: relative;
      overflow: hidden;
    }
    .similar-wrap::before, .relations-wrap::before {
      content: '';
      position: absolute;
      top: -80px; right: -80px;
      width: 320px; height: 320px;
      background: radial-gradient(circle, rgba(253,86,15,0.07) 0%, transparent 70%);
      pointer-events: none;
    }
    .similar-wrap::after, .relations-wrap::after {
      content: '';
      position: absolute;
      bottom: -60px; left: -60px;
      width: 260px; height: 260px;
      background: radial-gradient(circle, rgba(255,140,66,0.06) 0%, transparent 70%);
      pointer-events: none;
    }

    /* ── Section header ── */
    .similar-wrap .section-header, .relations-wrap .section-header {
      text-align: center;
      margin-bottom: 40px;
    }
    .similar-wrap .section-header .section-title, .relations-wrap .section-header .section-title {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-family: 'Nunito', sans-serif;
      font-size: 1.6rem;
      font-weight: 900;
      background: linear-gradient(135deg, #fd560f 0%, #ff8c42 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      letter-spacing: 0.3px;
      position: relative;
      padding-bottom: 14px;
    }
    .similar-wrap .section-header .section-title::before,
    .relations-wrap .section-header .section-title::before {
      content: '✦';
      font-size: 1rem;
      background: linear-gradient(135deg, #fd560f, #ff8c42);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .similar-wrap .section-header .section-title::after, .relations-wrap .section-header .section-title::after {
      content: '';
      position: absolute;
      bottom: 0; left: 50%;
      transform: translateX(-50%);
      width: 60px; height: 3px;
      background: linear-gradient(90deg, #fd560f, #ff8c42);
      border-radius: 99px;
    }

    /* ── Product card ── */
    .similar-wrap .product-wrap, .relations-wrap .product-wrap {
      border-radius: 18px;
      overflow: hidden;
      background: #fff;
      border: 1px solid rgba(253,86,15,0.08);
      box-shadow: 0 4px 16px rgba(0,0,0,0.06), 0 1px 4px rgba(253,86,15,0.04);
      transition: box-shadow 0.3s ease, transform 0.3s ease, border-color 0.3s ease;
      position: relative;
    }
    @media (min-width: 768px) {
      .similar-wrap .product-wrap:hover, .relations-wrap .product-wrap:hover {
        box-shadow: 0 16px 48px rgba(253,86,15,0.16), 0 4px 12px rgba(0,0,0,0.08);
        transform: translateY(-6px);
        border-color: rgba(253,86,15,0.2);
      }
    }

    /* ── Product image ── */
    .similar-wrap .product-wrap .image, .relations-wrap .product-wrap .image {
      aspect-ratio: 1 / 1;
      overflow: hidden;
      margin-bottom: 0;
      background: linear-gradient(135deg, #fafafa, #f5f5f5);
      position: relative;
    }
    .similar-wrap .product-wrap .image .image-old, .relations-wrap .product-wrap .image .image-old {
      width: 100%; height: 100%;
    }
    .similar-wrap .product-wrap .image img, .relations-wrap .product-wrap .image img {
      width: 100%; height: 100%;
      object-fit: cover;
      transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    .similar-wrap .product-wrap:hover .image img, .relations-wrap .product-wrap:hover .image img {
      transform: scale(1.08);
    }

    /* hover overlay on image */
    .similar-wrap .product-wrap .image::after, .relations-wrap .product-wrap .image::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, transparent 50%, rgba(253,86,15,0.08) 100%);
      opacity: 0;
      transition: opacity 0.3s ease;
      pointer-events: none;
    }
    .similar-wrap .product-wrap:hover .image::after, .relations-wrap .product-wrap:hover .image::after {
      opacity: 1;
    }

    /* ── Product info ── */
    .similar-wrap .product-wrap .product-bottom-info, .relations-wrap .product-wrap .product-bottom-info {
      padding: 12px 14px 16px;
      border-top: 1px solid #f8f0ec;
    }
    .similar-wrap .product-wrap .product-name, .relations-wrap .product-wrap .product-name {
      font-size: 0.82rem;
      font-weight: 600;
      color: #2d2d2d;
      line-height: 1.4;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      margin-bottom: 6px;
    }
    .similar-wrap .product-wrap .price-new, .relations-wrap .product-wrap .price-new {
      font-weight: 800;
      font-size: 0.95rem;
      color: #fd560f;
    }
    .similar-wrap .product-wrap .price-old, .relations-wrap .product-wrap .price-old {
      font-size: 0.75rem;
      color: #aaa;
      text-decoration: line-through;
      margin-left: 4px;
    }

    /* ── Action buttons ── */
    .similar-wrap .product-wrap .button-wrap, .relations-wrap .product-wrap .button-wrap {
      background: transparent;
    }
    .similar-wrap .product-wrap .btn-add-cart, .relations-wrap .product-wrap .btn-add-cart {
      background: linear-gradient(135deg, #fd560f, #ff7c35) !important;
      border: none !important;
      border-radius: 8px !important;
      font-size: 0.75rem !important;
      font-weight: 700 !important;
      letter-spacing: 0.3px;
    }

    /* ── Swiper nav ── */
    .similar-wrap .swiper-button-prev, .similar-wrap .swiper-button-next,
    .relations-wrap .swiper-button-prev, .relations-wrap .swiper-button-next {
      width: 42px; height: 42px;
      background: #fff;
      border-radius: 50%;
      box-shadow: 0 4px 16px rgba(253,86,15,0.18), 0 1px 4px rgba(0,0,0,0.08);
      border: 1.5px solid rgba(253,86,15,0.15);
      transition: background 0.2s, box-shadow 0.2s, border-color 0.2s;
    }
    .similar-wrap .swiper-button-prev:hover, .similar-wrap .swiper-button-next:hover,
    .relations-wrap .swiper-button-prev:hover, .relations-wrap .swiper-button-next:hover {
      background: linear-gradient(135deg, #fd560f, #ff8c42);
      border-color: transparent;
    }
    .similar-wrap .swiper-button-prev::after, .similar-wrap .swiper-button-next::after,
    .relations-wrap .swiper-button-prev::after, .relations-wrap .swiper-button-next::after {
      font-size: 13px; font-weight: 900; color: #fd560f;
    }
    .similar-wrap .swiper-button-prev:hover::after, .similar-wrap .swiper-button-next:hover::after,
    .relations-wrap .swiper-button-prev:hover::after, .relations-wrap .swiper-button-next:hover::after {
      color: #fff;
    }
    .similar-wrap .swiper-pagination-bullet, .relations-wrap .swiper-pagination-bullet {
      background: #ddd; opacity: 1;
      transition: background 0.2s, width 0.2s;
      border-radius: 99px;
    }
    .similar-wrap .swiper-pagination-bullet-active, .relations-wrap .swiper-pagination-bullet-active {
      background: linear-gradient(90deg, #fd560f, #ff8c42);
      width: 20px;
    }
  </style>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">
@endpush

@php
  $iframeClass = request('iframe') ? 'd-none' : '';
@endphp

@section('content')
  @if (!request('iframe'))
    <x-shop-breadcrumb type="product" :value="$product['id']" />
  @endif

  <div class="container {{ request('iframe') ? 'pt-4' : '' }}" id="product-app" v-cloak>
    <div class="row mb-md-5 mt-md-0" id="product-top">
      <div class="col-12 col-lg-6 mb-2">
        <div class="product-image d-flex align-items-start">
          @if(!is_mobile())
            <div class="left {{ $iframeClass }}"  v-if="images.length">
              <div class="swiper" id="swiper">
                <div class="swiper-wrapper">
                  <div class="swiper-slide" :class="!index ? 'active' : ''" v-for="image, index in images" :key="index">
                    <a href="javascript:;" :data-image="image.preview" :data-zoom-image="image.popup">
                      <img :src="image.thumb" class="img-fluid">
                    </a>
                  </div>
                </div>
                <div class="swiper-pager">
                  <div class="swiper-button-next new-feature-slideshow-next"></div>
                  <div class="swiper-button-prev new-feature-slideshow-prev"></div>
                </div>
              </div>
            </div>
            <div class="right" id="zoom">
              @include('product.product-video')
              <div class="product-img"><img :src="images.length ? images[0].preview : '{{ asset('image/placeholder.png') }}'" class="img-fluid"></div>
            </div>
          @else
            @include('product.product-video')
            <div class="swiper" id="swiper-mobile">
              <div class="swiper-wrapper">
                <div class="swiper-slide d-flex align-items-center justify-content-center" v-for="image, index in images" :key="index">
                  <img :src="image.preview" class="img-fluid">
                </div>
              </div>
              <div class="swiper-pagination mobile-pagination"></div>
            </div>
          @endif
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="peoduct-info product-mb-block">
          @hookwrapper('product.detail.name')
          <h1 class="mb-lg-4 mb-2 product-name">{{ $product['name'] }}</h1>
          @endhookwrapper
          @hookwrapper('product.detail.price')
          @if ((system_setting('base.show_price_after_login') and current_customer()) or !system_setting('base.show_price_after_login'))
            <div class="price-wrap d-flex align-items-end">
              <div class="new-price fs-1 lh-1 fw-bold me-2">@{{ product.price_format }}/{{ $product['gunit_min'] }}</div>
              <div class="old-price text-muted text-decoration-line-through" v-if="product.price != product.origin_price && product.origin_price !== 0">
                @{{ product.origin_price_format }}
              </div>
            </div>
          @else
            <div class="product-price">
              <div class="text-dark fs-6">{{ __('common.before') }} <a class="price-new fs-6 login-before-show-price" href="javascript:void(0);">{{ __('common.login') }}</a> {{ __('common.show_price') }}</div>
            </div>
          @endif

          @hook('product.detail.price.after')

          @endhookwrapper
          <div class="stock-and-sku mb-lg-4 mb-2">
            @hookwrapper('product.detail.quantity')
            <div class="d-lg-flex">
              <span class="title text-muted">{{ __('product.quantity') }}:</span>
              <span :class="product.quantity > 0 ? 'text-success' : 'text-secondary'">
                <template v-if="product.quantity > 0">{{ __('shop/products.in_stock') }}</template>
                <template v-else>{{ __('shop/products.out_stock') }}</template>
              </span>
            </div>
            @endhookwrapper

            @if ($product['brand_id'])
              @hookwrapper('product.detail.brand')
              <div class="d-lg-flex">
                <span class="title text-muted">{{ __('product.brand') }}:</span>
                <a href="{{ shop_route('brands.show', $product['brand_id']) }}">{{ $product['brand_name'] }}</a>
              </div>
              @endhookwrapper
            @endif

            @hookwrapper('product.detail.sku')
            <div class="d-lg-flex"><span class="title text-muted">SKU:</span>@{{ product.sku }}</div>
            @endhookwrapper
            @hookwrapper('product.detail.gunit_text')
            <div class="d-lg-flex"><span class="title text-muted">{{ __('product.spec') }}:</span>{{ $product['gunit_text'] }}</div>
            @endhookwrapper
            @hookwrapper('product.detail.quality')
            <div class="d-lg-flex"><span style="width: " class="title text-muted">{{ __('product.quality') }}:</span>{{ $product['quality'] }}</div>
            @endhookwrapper
            @hookwrapper('product.detail.min')
            <div class="d-lg-flex"><span class="title text-muted">{{ __('product.min_purchase') }}:</span>{{ $product['min'] }}</div>
            @endhookwrapper

            @hookwrapper('product.detail.model')
            <div class="d-lg-flex" v-if="product.model"><span class="title text-muted">{{ __('shop/products.model') }}:</span> @{{ product.model }}</div>
            @endhookwrapper
          </div>
          @if (0)
            <div class="rating-wrap d-lg-flex">
              <div class="rating">
                @for ($i = 0; $i < 5; $i++)
                  <i class="iconfont">&#xe628;</i>
                @endfor
              </div>
              <span class="text-muted">132 reviews</span>
            </div>
          @endif
          @hookwrapper('product.detail.variables')
          <div class="variables-wrap mb-md-4" v-if="source.variables.length">
            <div class="variable-group" v-for="variable, variable_index in source.variables" :key="variable_index">
              <p class="mb-2">@{{ variable.name }}</p>
              <div class="variable-info">
                <div
                  v-for="value, value_index in variable.values"
                  @click="checkedVariableValue(variable_index, value_index, value)"
                  :key="value_index"
                  data-bs-toggle="tooltip"
                  data-bs-placement="top"
                  :title="value.image ? value.name : ''"
                  :class="[value.selected ? 'selected' : '', value.disabled ? 'disabled' : '', value.image ? 'is-v-image' : '']">
                  <span class="image" v-if="value.image"><img :src="value.image" class="img-fluid"></span>
                  <span v-else>@{{ value.name }}</span>
                </div>
              </div>
            </div>
          </div>
          @endhookwrapper

          <div class="product-btns">
            @if ($product['active'])
              <div class="quantity-btns">
                @hook('product.detail.buy.before')
                @hookwrapper('product.detail.quantity.input')
                <div class="quantity-wrap">
                  <input
                    type="number"
                    class="form-control"
                    :disabled="!product.quantity"
                    v-model.number="quantity"
                    @blur="validateQuantity"
                    name="quantity"
                    :min="minQuantity"
                    :step="minQuantity"
                  >
                </div>
                @endhookwrapper
                @hookwrapper('product.detail.add_to_cart')
                <button
                  class="btn btn-outline-dark ms-md-3 add-cart fw-bold"
                  :product-id="product.id"
                  :product-price="product.price"
                  :disabled="!product.quantity"
                  @click="addCart(false, this)"
                ><i class="bi bi-cart-fill me-1"></i>{{ __('shop/products.add_to_cart') }}
                </button>
                @endhookwrapper
                @hookwrapper('product.detail.buy_now')
                <button
                  class="btn btn-dark ms-3 btn-buy-now fw-bold"
                  :disabled="!product.quantity"
                  :product-id="product.id"
                  :product-price="product.price"
                  @click="addCart(true, this)"
                ><i class="bi bi-bag-fill me-1"></i>{{ __('shop/products.buy_now') }}
                </button>
                @endhookwrapper
                @hook('product.detail.buy.after')
              </div>

              @if (current_customer() || !request('iframe'))
                @hookwrapper('product.detail.wishlist')
                <div class="add-wishlist">
                  <button class="btn btn-link ps-md-0 text-secondary" data-in-wishlist="{{ $product['in_wishlist'] }}" onclick="bk.addWishlist('{{ $product['id'] }}', this)">
                    <i class="bi bi-heart{{ $product['in_wishlist'] ? '-fill' : '' }} me-1"></i> <span>{{ __('shop/products.add_to_favorites') }}</span>
                  </button>
                </div>
                @endhookwrapper
              @endif
            @else
              <div class="text-danger"><i class="bi bi-exclamation-circle-fill"></i> {{ __('product.has_been_inactive') }}</div>
            @endif
          </div>

          @hook('product.detail.after')
        </div>
      </div>
    </div>

    <div class="product-description product-mb-block {{ $iframeClass }}">
      @if ($product['attributes'])
        <div class="nav nav-tabs nav-overflow justify-content-start justify-content-md-center border-bottom mb-3">
          <a class="nav-link fw-bold active fs-5" data-bs-toggle="tab" href="#product-description">
            {{ __('shop/products.product_details') }}
          </a>
          <a class="nav-link fw-bold fs-5" data-bs-toggle="tab" href="#product-attributes">
            {{ __('admin/attribute.index') }}
          </a>
          @hook('product.tab.after.link')
        </div>
      @endif
      <div class="tab-content">
        <div class="tab-pane fade show active" id="product-description" role="tabpanel">
          {!! $product['description'] !!}
        </div>
        <div class="tab-pane fade" id="product-attributes" role="tabpanel">
          <table class="table table-bordered attribute-table">
            @foreach ($product['attributes'] as $group)
              <thead class="table-light">
              <tr><td colspan="2"><strong>{{ $group['attribute_group_name'] }}</strong></td></tr>
              </thead>
              <tbody>
              @foreach ($group['attributes'] as $item)
                <tr>
                  <td>{{ $item['attribute'] }}</td>
                  <td>{{ $item['attribute_value'] }}</td>
                </tr>
              @endforeach
              </tbody>
            @endforeach
          </table>
        </div>
        @hook('product.tab.after.pane')
      </div>
    </div>
  </div>

  @if ($relations && !request('iframe'))
    <div class="relations-wrap product-mb-block">
      <div class="container position-relative">
        <div class="section-header">
          <span class="section-title">{{ __('admin/product.product_relations') }}</span>
        </div>
        <div class="product swiper-style-plus">
          <div class="swiper relations-swiper">
            <div class="swiper-wrapper">
              @foreach ($relations as $item)
                <div class="swiper-slide">
                  @include('shared.product', ['product' => $item])
                </div>
              @endforeach
            </div>
          </div>
          <div class="swiper-pagination relations-pagination mt-4"></div>
          <div class="swiper-button-prev relations-swiper-prev"></div>
          <div class="swiper-button-next relations-swiper-next"></div>
        </div>
      </div>
    </div>
  @endif

  @if (!empty($similar) && !request('iframe'))
    <div class="similar-wrap product-mb-block">
      <div class="container position-relative">
        <div class="section-header">
          <span class="section-title">{{ __('product.similar_products') }}</span>
        </div>
        <div class="product swiper-style-plus">
          <div class="swiper similar-swiper">
            <div class="swiper-wrapper">
              @foreach ($similar as $item)
                <div class="swiper-slide">
                  @include('shared.product', ['product' => $item])
                </div>
              @endforeach
            </div>
          </div>
          <div class="swiper-pagination similar-pagination mt-4"></div>
          <div class="swiper-button-prev similar-swiper-prev"></div>
          <div class="swiper-button-next similar-swiper-next"></div>
        </div>
      </div>
    </div>
  @endif

  @hook('product.detail.footer')
@endsection

@push('add-scripts')
  <script>
    let swiperMobile = null;
    const isIframe = bk.getQueryString('iframe', false);

    let app = new Vue({
      el: '#product-app',

      data: {
        selectedVariantsIndex: [], // 选中的变量索引
        images: [],
        product: {
          id: 0,
          images: "",
          model: "",
          origin_price: 0,
          origin_price_format: "",
          position: 0,
          price: 0,
          price_format: "",
          quantity: 0,
          sku: "",
        },
        quantity: 1,
        source: {
          skus: @json($product['skus']),
          variables: @json($product['variables'] ?? []),
        },
        minQuantity: {{ (int)$product['min'] }}, // 最小起订量
      },

      beforeMount () {
        const skus = JSON.parse(JSON.stringify(this.source.skus));
        const skuDefault = skus.find(e => e.is_default)
        this.selectedVariantsIndex = skuDefault.variants

        // 为 variables 里面每一个 values 的值添加 selected、disabled 字段
        if (this.source.variables.length) {
          this.source.variables.forEach(variable => {
            variable.values.forEach(value => {
              this.$set(value, 'selected', false)
              this.$set(value, 'disabled', false)
            })
          })

          this.checkedVariants()
          this.getSelectedSku(false);
          this.updateSelectedVariantsStatus()
        } else {
          // 如果没有默认的sku，则取第一个sku的第一个变量的第一个值
          this.product = skus[0];
          this.images = @json($product['images'] ?? []);
        }
        this.quantity = this.minQuantity; // 设置初始数量为最小起订量
        const di = document.getElementsByClassName('bi-chevron-up')
        console.log(di)
      },
      watch: {
        quantity(val, ol) {
          console.log(val)
        }
      },
      methods: {
        checkedVariableValue(variable_index, value_index, value) {
          $('.product-image .swiper .swiper-slide').eq(0).addClass('active').siblings().removeClass('active');
          this.source.variables[variable_index].values.forEach((v, i) => {
            v.selected = i == value_index
          })

          this.updateSelectedVariantsIndex();
          this.getSelectedSku();
          this.updateSelectedVariantsStatus()
        },

        // 把对应 selectedVariantsIndex 下标选中 variables -> values 的 selected 字段为 true
        checkedVariants() {
          this.source.variables.forEach((variable, index) => {
            variable.values[this.selectedVariantsIndex[index]].selected = true
          })
        },

        getSelectedSku(reload = true) {
          // 通过 selectedVariantsIndex 的值比对 skus 的 variables
          const sku = this.source.skus.find(sku => sku.variants.toString() == this.selectedVariantsIndex.toString())
          this.images = @json($product['images'] ?? [])

          if (reload) {
            this.images.unshift(...sku.images)
          }

          this.product = sku;

          if (swiperMobile) {
            swiperMobile.slideTo(0, 0, false)
          }

          this.$nextTick(() => {
            $('#zoom img').attr('src', $('#swiper a').attr('data-image'));
            $('#zoom').trigger('zoom.destroy');
            $('#zoom').zoom({url: $('#swiper a').attr('data-zoom-image')});
          })

          closeVideo()
        },

        addCart(isBuyNow = false) {
          bk.addCart({sku_id: this.product.id, quantity: this.quantity, isBuyNow}, null, () => {
            if (isIframe) {
              let index = parent.layer.getFrameIndex(window.name); //当前iframe层的索引
              parent.bk.getCarts();

              setTimeout(() => {
                parent.layer.close(index);

                if (isBuyNow) {
                  parent.location.href = 'checkout'
                } else {
                  parent.$('.btn-right-cart')[0].click()
                }
              }, 400);
            } else {
              if (isBuyNow) {
                location.href = 'checkout'
              }
            }
          });
        },

        updateSelectedVariantsIndex() {
          // 获取选中的 variables 内 value的 下标 index 填充到 selectedVariantsIndex 中
          this.source.variables.forEach((variable, index) => {
            variable.values.forEach((value, value_index) => {
              if (value.selected) {
                this.selectedVariantsIndex[index] = value_index
              }
            })
          })
        },

        updateSelectedVariantsStatus() {
          // skus 里面 quantity 不为 0 的 sku.variants
          const skus = this.source.skus.filter(sku => sku.quantity > 0).map(sku => sku.variants);
          this.source.variables.forEach((variable, index) => {
            variable.values.forEach((value, value_index) => {
              const selectedVariantsIndex = this.selectedVariantsIndex.slice(0);

              selectedVariantsIndex[index] = value_index;
              const selectedSku = skus.find(sku => sku.toString() == selectedVariantsIndex.toString());
              if (selectedSku) {
                value.disabled = false;
              } else {
                value.disabled = true;
              }
            })
          });
        },

        // 增加数量方法
        increaseQuantity() {
          // console.log(this.minQuantity)
          // this.quantity = parseInt(this.quantity) + parseInt(this.minQuantity);
        },

// 减少数量方法
        decreaseQuantity() {
          // const newQuantity = parseInt(this.quantity) - parseInt(this.minQuantity);
          // this.quantity = Math.max(this.minQuantity, newQuantity); // 确保不小于最小起订量
        },

        validateQuantity() {
          this.quantity = parseInt(this.quantity) || this.minQuantity;

          if (this.quantity < this.minQuantity) {
            this.quantity = this.minQuantity;
            return;
          }

          // 确保是最小起订量的整数倍
          const remainder = this.quantity % this.minQuantity;
          if (remainder !== 0) {
            this.quantity = Math.ceil(this.quantity / this.minQuantity) * this.minQuantity;
          }
        }
      }
    });

    $(document).on("mouseover", ".product-image #swiper .swiper-slide a", function() {
      $(this).parent().addClass('active').siblings().removeClass('active');
      $('#zoom').trigger('zoom.destroy');
      $('#zoom img').attr('src', $(this).attr('data-image'));
      $('#zoom').zoom({url: $(this).attr('data-zoom-image')});
      closeVideo()
    });

    var swiper = new Swiper("#swiper", {
      direction: "vertical",
      slidesPerView: 1,
      spaceBetween:3,
      breakpoints:{
        375:{
          slidesPerView: 3,
          spaceBetween:3,
        },
        480:{
          slidesPerView: 4,
          spaceBetween:27,
        },
        768:{
          slidesPerView: 6,
          spaceBetween:3,
        },
      },
      navigation: {
        nextEl: '.new-feature-slideshow-next',
        prevEl: '.new-feature-slideshow-prev',
      },
      observer: true,
      observeParents: true
    });

    var relationsSwiper = new Swiper ('.relations-swiper', {
      watchSlidesProgress: true,
      autoplay: { delay: 3500, disableOnInteraction: false, pauseOnMouseEnter: true },
      breakpoints: {
        320:  { slidesPerView: 2, spaceBetween: 10 },
        576:  { slidesPerView: 3, spaceBetween: 14 },
        768:  { slidesPerView: 4, spaceBetween: 16 },
        1200: { slidesPerView: 5, spaceBetween: 18 },
      },
      navigation: {
        nextEl: '.relations-swiper-next',
        prevEl: '.relations-swiper-prev',
      },
      pagination: {
        el: '.relations-pagination',
        clickable: true,
      },
    })

    var similarSwiper = new Swiper ('.similar-swiper', {
      watchSlidesProgress: true,
      autoplay: { delay: 3500, disableOnInteraction: false, pauseOnMouseEnter: true },
      breakpoints: {
        320:  { slidesPerView: 2, spaceBetween: 10 },
        576:  { slidesPerView: 3, spaceBetween: 14 },
        768:  { slidesPerView: 4, spaceBetween: 16 },
        1200: { slidesPerView: 5, spaceBetween: 18 },
      },
      navigation: {
        nextEl: '.similar-swiper-next',
        prevEl: '.similar-swiper-prev',
      },
      pagination: {
        el: '.similar-pagination',
        clickable: true,
      },
    })

    @if (is_mobile())
      swiperMobile = new Swiper("#swiper-mobile", {
      slidesPerView: 1,
      pagination: {
        el: ".mobile-pagination",
      },
      observer: true,
      observeParents: true
    });
    @endif

    $(document).ready(function () {
      $('#zoom').trigger('zoom.destroy');
      $('#zoom').zoom({url: $('#swiper a').attr('data-zoom-image')});
    });
    const selectedVariantsIndex = app.selectedVariantsIndex;
    const variables = app.source.variables;

    const selectedVariants = variables.map((variable, index) => {
      return variable.values[selectedVariantsIndex[index]]
    });
  </script>
@endpush
