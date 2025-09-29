@extends('admin::layouts.master')

@section('title', $name)

@section('content')
  <link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-chalk/index.css">
  <div class="container" id="address-app">



    <div class="row">

      <div class="col-12 col-md-9">
        <div class="card h-min-600">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">修改银行卡</h5>
          </div>
          <div class="card-body h-600">
            <div class="addresses-wrap" v-cloak>
              <div class="row"  v-if="bank.bank_user_name != ''">
                <div class="col-6">
                  <div class="item">
                    <div class="name-wrap">
                      <span class="name">开户名:@{{ bank.bank_user_name }}</span>
                    </div>
                    <div class="name-wrap">
                      <span class="name">开户银行:@{{ bank.bank_name }}</span>
                    </div>
                    <div class="name-wrap">
                      <span class="name">银行卡号:@{{ bank.bank_code }}</span>
                    </div>
                    <div class="address-bottom">
                      <div>
                        <a href="javascript:void(0)"
                           @click.stop="editBank">{{ __('shop/account.addresses.edit') }}</a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center">
                <button class="btn btn-dark mb-3" @click="editBank"><i class="bi bi-plus-square-dotted me-1"></i>
                  新增银行卡
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <el-dialog title="新增/修改银行卡" :visible.sync="dialog.dialogFormVisible" width="90%">
      <el-form :mode="bank">
        <el-form-item label="开户名" label-width="70px">
          <el-input v-model="bank.bank_user_name"></el-input>
        </el-form-item>
        <el-form-item label="开户银行" label-width="70px">
          <el-input v-model="bank.bank_name"></el-input>
        </el-form-item>
        <el-form-item label="卡号" label-width="70px">
          <el-input v-model="bank.bank_code"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialog.dialogFormVisible = false">{{ __('common.cancel') }}</el-button>
        <el-button type="primary" @click="handleSaveBank">{{ __('common.confirm') }}</el-button>
      </div>
    </el-dialog>

  </div>

<!-- import Vue before Element -->
<script src="https://unpkg.com/vue@2/dist/vue.js"></script>
<!-- import JavaScript -->
<script src="https://unpkg.com/element-ui/lib/index.js"></script>

  <script>
    new Vue({
      el: '#address-app',

      data: {
        editIndex: null,
        bank: @json($bank ?? ['bank_user_name'=>'','bank_name'=>'','bank_code'=>'']),
        addresses: [],
        customer_id:'{{ $customerId }}',
        dialog: {dialogFormVisible: false,}
      },

      // 实例被挂载后调用
      mounted() {
      },

      methods: {

        editBank(index) {
          this.dialog.dialogFormVisible = true;
        },

        handleSaveBank() {
          if (this.bank.bank_user_name == '' || this.bank.bank_name == '' || this.bank.bank_code == '') {
            this.$message.warning("{{__('shop/common2.apply_post_param_error')}}");
            return;
          }
          const type = this.bank.id ? 'put' : 'post';
          const url = '/commission/admin_bank?customer_id=' + this.customer_id

          $http[type](url, this.bank).then((res) => {
            window.location.reload();
          })
        },
      }
    })
  </script>

@endsection
