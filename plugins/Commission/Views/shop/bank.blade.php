@extends('layout.master')

@section('body-class', 'page-account-address')

@push('header')
  <script src="{{ asset('vendor/vue/2.7/vue' . (!config('app.debug') ? '.min' : '') . '.js') }}"></script>
  <script src="{{ asset('vendor/element-ui/2.15.6/js.js') }}"></script>
  <link rel="stylesheet" href="{{ asset('vendor/element-ui/2.15.6/css.css') }}">
@endpush

@section('content')
  <div class="container" id="address-app">



    <div class="row">
      <x-shop-sidebar/>

      <div class="col-12 col-md-9">
        <div class="card h-min-600">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">{{ __('shop/common2.menu.bank') }}</h5>
          </div>
          <div class="card-body h-600">
            <div class="addresses-wrap" v-cloak>
              <div class="row"  v-if="bank.bank_user_name != ''">
                <div class="col-6">
                  <div class="item">
                    <div class="name-wrap">
                      <span class="name">{{ __('shop/common2.menu.bank.user_name') }}:@{{ bank.bank_user_name }}</span>
                    </div>
                    <div class="name-wrap">
                      <span class="name">{{ __('shop/common2.menu.bank.name') }}:@{{ bank.bank_name }}</span>
                    </div>
                    <div class="name-wrap">
                      <span class="name">{{ __('shop/common2.menu.bank.card.code') }}:@{{ bank.bank_code }}</span>
                    </div>
                    <div class="address-bottom">
                      <div>
                        <a class="me-2" @click.stop="deleteBank">{{ __('shop/account.addresses.delete') }}</a>
                        <a href="javascript:void(0)"
                           @click.stop="editBank">{{ __('shop/account.addresses.edit') }}</a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center">
                <x-shop-no-data/>
                <button class="btn btn-dark mb-3" @click="editBank"><i class="bi bi-plus-square-dotted me-1"></i>
                  {{ __('shop/common2.menu.bank.card.add') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <el-dialog title="{{ __('shop/common2.menu.bank.card.add') }}" :visible.sync="dialog.dialogFormVisible" width="90%">
      <el-form :mode="bank">
        <el-form-item label="{{ __('shop/common2.menu.bank.user_name') }}" label-width="70px">
          <el-input v-model="bank.bank_user_name"></el-input>
        </el-form-item>
        <el-form-item label="{{ __('shop/common2.menu.bank.name') }}" label-width="70px">
          <el-input v-model="bank.bank_name"></el-input>
        </el-form-item>
        <el-form-item label="{{ __('shop/common2.menu.bank.card.code') }}" label-width="70px">
          <el-input v-model="bank.bank_code"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialog.dialogFormVisible = false">{{ __('common.cancel') }}</el-button>
        <el-button type="primary" @click="handleSaveBank">{{ __('common.confirm') }}</el-button>
      </div>
    </el-dialog>

  </div>
@endsection

@push('add-scripts')
  @include('shared.address-form')
  <script>
    new Vue({
      el: '#address-app',

      data: {
        editIndex: null,
        bank: @json($bank ?? ['bank_user_name'=>'','bank_name'=>'','bank_code'=>'']),
        addresses: [],
        dialog: {dialogFormVisible: false,}
      },

      // 实例被挂载后调用
      mounted() {
      },

      methods: {

        editBank(index) {
          this.dialog.dialogFormVisible = true;
        },
        deleteBank() {
          this.$confirm('{{ __('common.confirm_delete') }}',
            '{{ __('shop/account.addresses.hint') }}', {
              confirmButtonText: '{{ __('common.confirm') }}',
              cancelButtonText: '{{ __('common.cancel') }}',
              type: 'warning'
            }).then(() => {
            $http.delete('/commission/shop/bank/' + this.bank.id).then((res) => {
              this.$message.success(res.message);
              window.location.reload();
            })
          }).catch(() => {
          })
        },

        handleSaveBank() {
          if (this.bank.bank_user_name == '' || this.bank.bank_name == '' || this.bank.bank_code == '') {
            this.$message.warning("{{__('shop/common2.apply_post_param_error')}}");
            return;
          }
          const type = this.bank.id ? 'put' : 'post';
          const url = `/commission/shop/bank${type == 'put' ? '/' + this.bank.id : ''}`;

          $http[type](url, this.bank).then((res) => {
            window.location.reload();
          })
        },
      }
    })
  </script>
@endpush
