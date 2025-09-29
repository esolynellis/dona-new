<script src="{{ asset('vendor/vue/2.7/vue' . (!config('app.debug') ? '.min' : '') . '.js') }}"></script>
<script src="{{ asset('vendor/element-ui/index.js') }}"></script>
<link rel="stylesheet" href="{{ asset('vendor/element-ui/index.css') }}">
<div id="bk-commission_pay-app" v-cloak>
<button class="btn btn-primary" @click="checkedBtnCheckoutConfirm" type="button">支付</button>
</div>
<script>
  new Vue({
    el: '#bk-commission_pay-app',

    data: {

    },

    methods: {
      checkedBtnCheckoutConfirm() {
        $http.post(`/commission/shop/pay_order`, {order_no:@json($order->number ?? null)}).then((res) => {
          if (res.code == 0) {
            window.location.href = res.returnUrl
          }else{
            layer.msg(res.msg)
          }
        })

      }
    }
  })
</script>

