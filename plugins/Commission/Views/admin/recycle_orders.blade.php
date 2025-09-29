@extends('admin::layouts.master')

@section('title', $name)

@section('content')
  <link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-chalk/index.css">
  <div id="app">
    <el-row>
      <el-col :span="4">
        状态：<el-select v-model="ordersPage.q" placeholder="请选择">
          <el-option
            key="0"
            label="全部"
            value="0">
          </el-option>
            <el-option
              key="1"
              label="回收中"
              value="apply_unpaid">
          </el-option>
          <el-option
            key="2"
            label="已回收"
            value="apply_paid">
          </el-option>
        </el-select>
      </el-col>

      <el-col :span="2">
        <el-button @click="getOrders" style="margin-left: 10px">搜索</el-button>
      </el-col>
      <!--
      <el-col :span="2">
        <el-button @click="getOrders" style="margin-left: 10px">导出</el-button>
      </el-col>
      -->
    </el-row>
    <br/>
    <el-table
      :data="ordersData"
      style="width: 100%">
      <el-table-column
        label="ID"
        prop="id"
      >
      </el-table-column>
      <el-table-column
        prop="customer.account"
        label="帐号">
      </el-table-column>
      <el-table-column
        prop="order.number"
        label="订单号">
      </el-table-column>
      <el-table-column
        prop="order_product.name"
        label="商品">
      </el-table-column>
      <el-table-column
        prop="order_product.quantity"
        label="数量">
      </el-table-column>
      <el-table-column
        prop="order_product.price"
        label="商品价格">
      </el-table-column>

      <el-table-column
        :formatter="priceFormat"
        label="商品总价">
      </el-table-column>
      <el-table-column
        prop="amount"
        label="回购价格">
      </el-table-column>
      <el-table-column
        prop="total_amount"
        label="回购总价">
      </el-table-column>
      <el-table-column
        :formatter="statusFormat"
        label="状态">
      </el-table-column>
      <el-table-column label="操作" width>
        <template slot-scope="scope">
          <!--
          <el-button

            size="mini"
            type="danger"
            v-if="scope.row.status == 'apply_unpaid'"
            @click="refund(scope.row)"
          >
            通过
          </el-button>

          <el-button

            size="mini"
            type="warning"
            v-if="scope.row.status == 'apply_unpaid'"
            @click="openAuditApply(scope.row)"
          >
            驳回
          </el-button>-->
          <el-button
            size="mini"
            type="warning"
            v-if="scope.row.status == '0'"
            @click="openAuditApply(scope.row)"
          >
            确认回购
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

    <el-dialog title="回购审批" :visible.sync="cashDialog.dialogFormVisible"
               :close-on-click-modal="cashDialog.close">
      <el-form :model="cashApply">
        <el-form-item label="回购金额" label-width="100px">
          <el-input-number v-model="cashApply.amount"></el-input-number>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="cashDialog.dialogFormVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSaveAuditApply">提交</el-button>
      </div>
    </el-dialog>


  </div>
  <!-- import Vue before Element -->
  <script src="https://unpkg.com/vue@2/dist/vue.js"></script>
  <!-- import JavaScript -->
  <script src="https://unpkg.com/element-ui/lib/index.js"></script>
  <script>
    new Vue({
      el: '#app',
      data: function () {
        return {
          status:"0",
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
            amount: 0,
            qua:1,
          },
        }
      },
      created() {
        this.getOrders();
      },
      methods: {
        openAuditApply(row) {
          this.cashApply.id = row.id;
          this.qua = row.order_product.quantity;
          this.cashApply.amount = row.order_product.price;
          this.cashDialog.dialogFormVisible = true;
        },
        handleSaveAuditApply() {
          if (this.cashApply.action == '' || this.cashApply.audit_note == '') {
            this.$message.warning("请正确填写审批金额");
            return;
          }
          $http.put(`/commission/recycle`, this.cashApply).then((res) => {
            if (res.code == 0) {
              this.$message.success("审批成功");
              this.cashDialog.dialogFormVisible = false;
              this.getOrders();
            } else {
              this.$message.warning(res.msg)
            }
          })
        },
        getOrders() {
          let that = this;
          $http.get(`/commission/recycle_orders`, this.ordersPage).then((res) => {
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
        statusFormat(row) {
          if (row.status == '0') {
            return "待回收"
          } else if (row.status == '1') {
            return "已完成"
          } else if (row.status == '2') {
            return "已取消"
          }
        },
        priceFormat(row){
          return row.order_product.price*row.order_product.quantity;
        }
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
@endsection
