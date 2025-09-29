
<div class="card mb-4" id="l_offline_app">
  <div class="card-header"><h6 class="card-title">线下支付凭证</h6></div>
  <div class="card-body">
    <div class="table-push">
      <div class="demo-image__preview">

        @foreach ($offline_imgs as $img)
          <el-image
            style="width: 100px; height: 100px"
            src="{{$img}}"
            :preview-src-list="srcList">
          </el-image>
        @endforeach

      </div>

    </div>
  </div>
</div>
@push('footer')
  <script>
    new Vue({
      el: '#l_offline_app',
      data: function () {
        return {
          url: 'https://fuss10.elemecdn.com/e/5d/4a731a90594a4af544c0c25941171jpeg.jpeg',
          srcList: @json($offline_imgs)
        }
      },
      created() {

      },
      methods: {
      }
    })
  </script>
@endpush
