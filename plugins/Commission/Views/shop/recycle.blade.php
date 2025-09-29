@extends('layout.master')

@section('body-class', 'page-account-order-list')
@push('header')
  <script src="{{ asset('vendor/vue/2.7/vue' . (!config('app.debug') ? '.min' : '') . '.js') }}"></script>
  <script src="{{ asset('vendor/element-ui/2.15.6/js.js') }}"></script>
  <link rel="stylesheet" href="{{ asset('vendor/element-ui/2.15.6/css.css') }}">
@endpush

@section('content')
  <div class="container" id="app">

    <x-shop-sidebar />

    <div class="row">
      @php
        use Jenssegers\Agent\Agent;
        $agent = new Agent();
      @endphp

      @if (!$agent->isMobile())
      <x-shop-sidebar/>
      @endif

      <div class="col-12 col-md-9">
        <div class="card mb-4 account-card order-wrap">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">{{ __('shop/account.order.index') }}</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table ">
                <thead>
                <tr>
                  <th>{{ __('shop/account.order.order_details') }}</th>
                  <th width="160px">{{ __('shop/account.order.amount') }}</th>
                  <th width="100px">{{ __('shop/account.order.state') }}</th>
                  <th width="100px" class="text-end">{{ __('common.action') }}</th>
                </tr>
                </thead>
                @if (count($orders))
                  @foreach ($orders as $order)
                    <tbody>
                    <tr class="sep-row">
                      <td colspan="4"></td>
                    </tr>
                    <tr class="head-tr">
                      <td colspan="4">
                        <span class="order-created me-4">{{ $order->created_at }}</span>
                        <span
                          class="order-number">{{ __('shop/account.order.order_number') }}：{{ $order->number }}</span>
                      </td>
                    </tr>
                    @foreach ($order->orderProducts as $product)
                      <tr class="first-tr">
                        <td>
                          <div class="product-info">
                            <div class="img border d-flex justify-content-between align-items-center"><img
                                src="{{ $product->image }}" class="img-fluid"></div>
                            <div class="name">
                              <a class="text-dark"
                                 href="{{ shop_route('products.show', ['product' => $product->product_id]) }}">{{ $product->name }}</a>
                              <div class="quantity mt-1 text-secondary">x {{ $product->quantity }}</div>
                            </div>
                          </div>
                        </td>
                        <td>
                          {{ $product->price * $product->quantity }}</td>
                        <td>
                          @if ($order->recycle == null)
                            {{ __('shop/common2.recycle.status.0') }}
                          @elseif ($order->recycle->status == 0)
                            {{ __('shop/common2.recycle.status.1') }}
                          @elseif ($order->recycle->status == 1)
                            {{ __('shop/common2.recycle.status.2') }}
                          @elseif ($order->recycle->status == 2)
                            {{ __('shop/common2.recycle.status.3') }}
                          @endif
                        </td>
                        <td class="text-end">
                          @if ($order->recycle == null)
                            <a href="javascript:void(0)" @click="recycle('{{ $order->number }}')"
                               class="btn btn-outline-secondary btn-sm">
                              {{ __('shop/common2.recycle.btn.0') }}
                            </a>
                          @elseif ($order->recycle->status == 1)
                            <a href="javascript:void(0)" @click="cancel_recycle({{ $product->product_id }})"
                               class="btn btn-outline-secondary btn-sm">
                              {{ __('shop/common2.recycle.btn.1') }}
                            </a>

                          @endif


                        </td>
                      </tr>
                    @endforeach
                    </tbody>
                  @endforeach
                @else
                  <tbody>
                  <tr>
                    <td colspan="4" class="border-0">
                      <x-shop-no-data/>
                    </td>
                  </tr>
                  </tbody>
                @endif
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
@push('add-scripts')
  <script>

    new Vue({
      el: '#app',

      data: {


      },
      methods: {
        recycle(order_number) {
          $http.post(`/wallet/recycle`, {order_number: order_number}).then((res) => {
            layer.msg(res.message)
            console.log(res)

            window.location.reload();
          })
        },
        cancel_recycle(id) {
          $http.put(`/wallet/cancel_recycle`, {id: id}).then((res) => {
            layer.msg(res.message)
            console.log(res)

            window.location.reload();
          })
        }
      }
    })
  </script>
@endpush
