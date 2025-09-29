@extends('admin::layouts.master')

@section('title', $name)

@section('content')
  <div id="app">
    <el-row>
      <el-alert
        title="{{ __('Commission::cash_apply_orders.commission_desc_title') }}"
        type="success"
        description="{{ __('Commission::cash_apply_orders.commission_desc_content') }}">
      </el-alert>
    </el-row>
    <br>
    <el-row>
      <el-col :span="4">
        <el-input placeholder="{{ __('Commission::cash_apply_orders.search_placeholder') }}" v-model="ordersPage.q" class="input-with-select" style="width: 100%">
        </el-input>
      </el-col>
      <el-col :span="4" style="margin-left: 10px">
        <el-date-picker
          style="width:100%;"
          v-model="ordersPage.start_at"
          type="datetime"
          value-format="yyyy-MM-dd HH:mm:ss"
          placeholder="{{ __('Commission::cash_apply_orders.start_time') }}">
        </el-date-picker>
      </el-col>
      <el-col :span="4" style="margin-left: 10px">
        <el-date-picker
          style="width:100%;"
          v-model="ordersPage.end_at"
          type="datetime"
          value-format="yyyy-MM-dd HH:mm:ss"
          placeholder="{{ __('Commission::cash_apply_orders.end_time') }}">
        </el-date-picker>
      </el-col>
      <el-col :span="2">
        <el-button @click="getOrders" style="margin-left: 10px">{{ __('Commission::cash_apply_orders.search') }}</el-button>
      </el-col>
      <!--
      <el-col :span="2">
        <el-button @click="getOrders" style="margin-left: 10px">{{ __('Commission::cash_apply_orders.export') }}</el-button>
      </el-col>
      -->
    </el-row>
    <br/>
    <el-table
      :data="ordersData"
      style="width: 100%">
      <el-table-column
        prop="action_format"
        label="{{ __('Commission::cash_apply_orders.column_action') }}"
        :show-overflow-tooltip="true"
      >
      </el-table-column>
      <el-table-column
        prop="customer.name"
        label="{{ __('Commission::cash_apply_orders.column_member_name') }}">
      </el-table-column>
      <el-table-column
        prop="customer.email"
        label="{{ __('Commission::cash_apply_orders.column_member_email') }}">
      </el-table-column>
      <el-table-column
        prop="level"
        label="{{ __('Commission::cash_apply_orders.column_commission_level') }}">
      </el-table-column>
      <el-table-column
        prop="c_base_amount"
        label="{{ __('Commission::cash_apply_orders.column_commission_base') }}">
      </el-table-column>
      <el-table-column
        prop="rate2"
        label="{{ __('Commission::cash_apply_orders.column_commission_rate') }}">
      </el-table-column>
      <el-table-column
        prop="c_amount"
        label="{{ __('Commission::cash_apply_orders.column_commission') }}">
      </el-table-column>
      <el-table-column
        prop="date_at"
        label="{{ __('Commission::cash_apply_orders.column_time') }}">
      </el-table-column>
      <el-table-column
        prop="status_format"
        label="{{ __('Commission::cash_apply_orders.column_settlement_status') }}">
      </el-table-column>
      <el-table-column label="{{ __('Commission::cash_apply_orders.column_operation') }}">
        <template slot-scope="scope">
          <el-button
            size="mini"
            type="success"
            v-if="scope.row.action != 'apply_close'"
            @click="openAuditApply2(scope.row)"
          >
            {{ __('Commission::cash_apply_orders.btn_view') }}
          </el-button>

          <el-button
            size="mini"
            type="warning"
            v-if="scope.row.action == 'apply_close'"
            @click="openAuditApply(scope.row)"
          >
            {{ __('Commission::cash_apply_orders.btn_audit') }}
          </el-button>
        </template>
      </el-table-column>
    </el-table>
    <div class="Pagination">
      <el-pagination
        @current-change="handleOrdersPageChange"
        :current-page="ordersPage.page"
        :page-size="ordersPage.pageSize"
        layout="total, prev, pager, next"
        :total="ordersPage.total">
      </el-pagination>
    </div>

    <el-dialog title="{{ __('Commission::cash_apply_orders.dialog_title') }}" :visible.sync="cashDialog.dialogFormVisible"
               :close-on-click-modal="cashDialog.close">
      <ol class="list-group list-group-numbered lh-lg text-secondary">
        <li>{{ __('Commission::cash_apply_orders.payment_amount') }}({{current_currency_code()}}) @{{ -(cashApplyData.amount/100) }}</li>
        <li>{{ __('Commission::cash_apply_orders.payment_platform') }} @{{ cashApplyData.c_apply_data.withdrawal_group_name }}</li>

        <li :key="index"
            v-for="(item,index) in cashApplyData.c_apply_data.withdrawal_items"> @{{ item.name }}: @{{
          item.value }}
        </li>
      </ol>
      <el-form :model="cashApply">
        <el-form-item label="{{ __('Commission::cash_apply_orders.audit_label') }}" label-width="100px">
          <el-select v-model="cashApply.action" placeholder="{{ __('Commission::cash_apply_orders.audit_placeholder') }}" :disabled="!cashApply.can_audit">
            <el-option
              key="1"
              label="{{ __('Commission::cash_apply_orders.audit_option_paid') }}"
              value="yes">
            </el-option>
            <el-option
              key="2"
              label="{{ __('Commission::cash_apply_orders.audit_option_refuse') }}"
              value="refuse">
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="{{ __('Commission::cash_apply_orders.audit_note') }}" label-width="100px">
          <el-input v-model="cashApply.audit_note" :disabled="!cashApply.can_audit"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer" v-if="cashApply.can_audit">
        <el-button @click="cashDialog.dialogFormVisible = false">{{ __('Commission::cash_apply_orders.btn_cancel') }}</el-button>
        <el-button type="primary" @click="handleSaveAuditApply">{{ __('Commission::cash_apply_orders.btn_submit') }}</el-button>
      </div>
    </el-dialog>


  </div>

@endsection

@push('footer')
  <script>
    new Vue({
      el: '#app',
      data: function () {
        return {
          ordersPage: {
            page: 1,
            pageSize: 20,
            q: '',
            total: 0,
            start_time: '',
            end_time: ''
          },
          ordersData: [],
          cashDialog: {
            dialogFormVisible: false,
            close: false,
          },
          cashApply: {
            id: 0,
            action: '',
            audit_note: '',
            can_audit: true,
          },
          cashApplyData: {
            c_apply_data: {}
          }
        }
      },
      created() {
        this.getOrders();
      },
      methods: {
        openAuditApply(row) {
          console.log("========")
          this.cashApplyData = row;
          this.cashApply.id = row.id;
          this.cashApply.action = '';
          this.cashApply.audit_note = '';
          this.cashApply.can_audit = true;
          this.cashDialog.dialogFormVisible = true;
        },
        openAuditApply2(row) {
          console.log("-------------")
          this.cashApplyData = row;
          this.cashApply.id = row.id;
          this.cashApply.action = row.action == 'refuse_close' ? "refuse" : "yes";
          this.cashApply.audit_note = row.audit_note;
          this.cashApply.can_audit = false;


          this.cashDialog.dialogFormVisible = true;
        },
        handleSaveAuditApply() {
          if (this.cashApply.action == '' || this.cashApply.audit_note == '') {
            this.$message.warning("{{ __('Commission::cash_apply_orders.msg_input_required') }}");
            return;
          }
          $http.put("{{admin_route('audit_cash_apply')}}", this.cashApply).then((res) => {
            if (res.code == 0) {
              this.$message.success("{{ __('Commission::cash_apply_orders.msg_audit_success') }}");
              this.cashDialog.dialogFormVisible = false;
              this.getOrders();
            } else {
              this.$message.warning(res.msg)
            }
          })
        },
        getOrders() {
          let that = this;
          $http.get("{{admin_route('orders')}}?is_cash_apply=1", this.ordersPage).then((res) => {
            //console.log(res);
            that.ordersData = res.data;
            that.ordersPage.page = res.current_page;
            that.ordersPage.pageSize = res.per_page;
            that.ordersPage.total = res.total;

          })
        },
        handleOrdersPageChange(val) {
          this.ordersPage.page = val;
          this.ordersPage.offset = (val - 1) * this.ordersPage.pageSize;
          this.getOrders()
        },

      }
    })
  </script>
@endpush
