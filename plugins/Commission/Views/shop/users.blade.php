@extends('layout.master')

@section('body-class', 'page-account-order-list')

@section('content')
  <div class="breadcrumb-wrap">
    <div class="container">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          @foreach($breadcrumbs as $breadcrumb)
            <li class="breadcrumb-item"><a href="{{$breadcrumb['url']}}">{{$breadcrumb['title']}}</a></li>
          @endforeach

        </ol>
      </nav>
    </div>
  </div>
  <div class="container">
    <div class="row">
      <x-shop-sidebar/>

      <div class="col-12 col-md-9">
        <div class="card mb-4 account-card order-wrap h-min-600">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">{{ __('Commission::orders.membership') }}</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive" id="app">
              <el-row>
                <el-col :span="12">
                  <div style="margin-top: 10px">
                    <el-alert
                      :closable="false"
                      :title="'{{__('Commission::orders.share_url')}}: '+shareUrl"
                      type="success">
                    </el-alert>
                  </div>
                </el-col>
                <el-col :span="6">
                  <div style="margin: 10px">
                    <el-tooltip class="item" effect="dark" content="{{__('Commission::orders.share_url_des')}}"
                                placement="top-start">
                      <el-button type="success"
                                 size="small"
                                 @click="copyUrl()">{{__('Commission::orders.share')}}</el-button>
                    </el-tooltip>
                    <el-popover
                      placement="right"
                      trigger="click">
                      <div>
                        <div id="code-info"></div>
                        <br>
                        <a target="_blank"
                           id="download_a"
                           download="share.png" class="el-button el-button--danger el-button--mini">
                          {{__('Commission::orders.qrcode_download_btn')}}
                        </a>
                      </div>
                      <el-button type="success" size="small" slot="reference">{{__('Commission::orders.qrcode_title')}}</el-button>
                    </el-popover>
                  </div>
                </el-col>
              </el-row>
              <el-row>
                <el-col :span="8">
                  <el-input placeholder="{{__('Commission::orders.search_placeholder')}}" v-model="usersPage.q"
                            class="input-with-select" style="width: 100%">
                  </el-input>
                </el-col>
                <el-col :span="3">
                  <el-button @click="getUsers"
                             style="margin-left: 10px">{{__('Commission::orders.search_btn')}}</el-button>
                </el-col>
              </el-row>
              <br/>
              <el-table
                :data="usersData"
                style="width: 100%">
                <el-table-column :label="my_superior">
                  <el-table-column label=" {{ __('Commission::orders.my_subordinates') }}" style="text-align: center">
                    <el-table-column
                      prop="date_at"
                      label="{{__('Commission::orders.join_time')}}">
                    </el-table-column>
                    <el-table-column
                      prop="customer.name"
                      label="{{__('Commission::orders.mer_name')}}">
                    </el-table-column>
                    <el-table-column
                      width="180"
                      prop="account"
                      label="{{__('Commission::orders.mer_account')}}">
                    </el-table-column>
                    <el-table-column
                      prop="code"
                      label="{{__('Commission::orders.mer_code')}}">
                    </el-table-column>
                  </el-table-column>
                </el-table-column>
              </el-table>
              <div class="Pagination">
                <el-pagination
                  @current-change="handleusersPageChange"
                  :current-page="usersPage.page"
                  :page-size="usersPage.pageSize"
                  layout="total, prev, pager, next"
                  :total="usersPage.total">
                </el-pagination>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection

@push('header')
  <script src="{{ asset('vendor/qrcode/qrcode.min.js') }}"></script>
  <script src="{{ asset('vendor/vue/2.7/vue' . (!config('app.debug') ? '.min' : '') . '.js') }}"></script>
  <script src="{{ asset('vendor/element-ui/index.js') }}"></script>
  <link rel="stylesheet" href="{{ asset('vendor/element-ui/index.css') }}">
@endpush
@push('add-scripts')

  <script>
    new Vue({
      el: '#app',
      data: function () {
        return {
          usersPage: {
            page: 1,
            pageSize: 20,
            q: '',
            total: 0,
            start_time: '',
            end_time: ''
          },
          usersData: [],
          myParentCustomer: {},
          customer: {},
          my_superior: "",
          shareUrl: "",
          qrcode_base64:"",
        }
      },
      created() {
        this.getUsers();
      },
      methods: {

        getUsers() {
          let that = this;
          $http.get(`/commission/shop/users`, this.usersPage).then((res) => {
            //console.log(res);
            that.usersData = res.users.data;
            that.usersPage.page = res.users.current_page;
            that.usersPage.pageSize = res.users.per_page;
            that.usersPage.total = res.users.total;
            that.myParentCustomer = res.myParentCustomer;
            if (that.myParentCustomer != null) {
              that.my_superior = "{{__('Commission::orders.my_superior')}}: " + that.myParentCustomer.email;
            } else {
              that.my_superior = "{{__('Commission::orders.no_superior')}}";
            }
            that.customer = res.customer;
            that.shareUrl = res.shareUrl;

            new QRCode('code-info', {
              text: res.shareUrl,
              width: 170,
              height: 170,
              correctLevel: QRCode.CorrectLevel.M
            });

            setTimeout(function () {
              let img = $("#code-info").find('img')[0];
              console.log(img.src);
              let content = img.src;//'data:application/octet-stream;base64,'+
              content = content.replace("data:image/png;","data:application/octet-stream;");
              $("#download_a").attr("href",content);
            },1000)
          })
        },
        handleusersPageChange(val) {
          this.usersPage.page = val;
          this.usersPage.offset = (val - 1) * this.usersPage.pageSize;
          this.getUsers()
        },
        copyUrl() {
          //创建input标签
          var input = document.createElement('input')
          //将input的值设置为需要复制的内容
          input.value = this.shareUrl;
          //添加input标签
          document.body.appendChild(input)
          //选中input标签
          input.select()
          //执行复制
          document.execCommand('copy')
          //成功提示信息
          this.$message.success("{{__('Commission::orders.share_tips')}}")
          //移除input标签
          document.body.removeChild(input)
        },

      }
    })
  </script>
  <style>
    .Pagination {
      display: flex;
      justify-content: flex-start;
      margin-top: 8px;
    }
  </style>
@endpush
