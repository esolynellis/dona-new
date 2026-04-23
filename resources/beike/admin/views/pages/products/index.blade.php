@extends('admin::layouts.master')

@section('title', __('admin/common.product'))

@section('content')
  @if ($errors->has('error'))
    <x-admin-alert type="danger" msg="{{ $errors->first('error') }}" class="mt-4" />
  @endif

  @if (session()->has('success'))
    <x-admin-alert type="success" msg="{{ session('success') }}" class="mt-4" />
  @endif

  <div id="product-app">
    <div class="card h-min-600">
      <div class="card-body">
        <div class="bg-light p-4">
          <div class="row">
            <div class="col-xxl-20 col-xl-3 col-lg-4 col-md-4 d-flex align-items-center mb-3">
              <label class="filter-title">{{ __('product.name') }}</label>
              <input @keyup.enter="search" type="text" v-model="filter.name" class="form-control" placeholder="{{ __('product.name') }}">
            </div>
            <div class="col-xxl-20 col-xl-3 col-lg-4 col-md-4 d-flex align-items-center mb-3">
              <label class="filter-title">{{ __('product.sku') }}</label>
              <input @keyup.enter="search" type="text" v-model="filter.sku" class="form-control" placeholder="{{ __('product.sku') }}">
            </div>

            <div class="col-xxl-20 col-xl-3 col-lg-4 col-md-4 d-flex align-items-center mb-3">
              <label class="filter-title">{{ __('product.model') }}</label>
              <input @keyup.enter="search" type="text" v-model="filter.model" class="form-control" placeholder="{{ __('product.model') }}">
            </div>

            <div class="col-xxl-20 col-xl-3 col-lg-4 col-md-4 d-flex align-items-center mb-3">
              <label class="filter-title">{{ __('product.category') }}</label>
              <select v-model="filter.category_id" class="form-select">
                <option value="">{{ __('common.all') }}</option>
                @foreach ($categories as $_category)
                  <option :value="{{ $_category->id }}">{{ $_category->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-xxl-20 col-xl-3 col-lg-4 col-md-4 d-flex align-items-center mb-3">
              <label class="filter-title">{{ __('common.status') }}</label>
              <select v-model="filter.active" class="form-select">
                <option value="">{{ __('common.all') }}</option>
                <option value="1">{{ __('product.active') }}</option>
                <option value="0">{{ __('product.inactive') }}</option>
              </select>
            </div>

            @hook('admin.product.list.filter')
          </div>

          <div class="row">
            <label class="filter-title"></label>
            <div class="col-auto">
              <button type="button" @click="search" class="btn btn-outline-primary btn-sm">{{ __('common.filter') }}</button>
              <button type="button" @click="resetSearch" class="btn btn-outline-secondary btn-sm">{{ __('common.reset') }}</button>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between my-4">
          @if ($type != 'trashed')
          <a href="{{ admin_route('products.create') }}" class="me-1 nowrap">
            <button class="btn btn-primary">{{ __('admin/product.products_create') }}</button>
          </a>
          @else
            @if ($products->total())
              <button class="btn btn-primary" @click="clearRestore">{{ __('admin/product.clear_restore') }}</button>
            @endif
          @endif

          @if ($type != 'trashed' && $products->total())
            <div class="right nowrap">
              {{-- 添加批量上传按钮 --}}
              <button class="btn btn-success" @click="openBatchUploadModal">
                <i class="bi bi-upload"></i> {{ __('admin/product.batch_upload') }}
              </button>
              {{-- 添加导出和导入按钮 --}}
              <button class="btn btn-outline-info" :disabled="!selectedIds.length" @click="exportProducts">
                <i class="bi bi-download"></i> {{ __('admin/product.export') }}
              </button>
              <button class="btn btn-outline-info" @click="openImportModal">
                <i class="bi bi-upload"></i> {{ __('admin/product.import') }}
              </button>
              <button class="btn btn-outline-secondary" :disabled="!selectedIds.length" @click="batchDelete">{{ __('admin/product.batch_delete')  }}</button>
              <button class="btn btn-outline-secondary" :disabled="!selectedIds.length"
              @click="batchActive(true)">{{ __('admin/product.batch_active') }}</button>
              <button class="btn btn-outline-secondary" :disabled="!selectedIds.length"
              @click="batchActive(false)">{{ __('admin/product.batch_inactive') }}</button>
            </div>
          @endif
        </div>

        @if ($products->total())
          <div class="table-push">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th><input type="checkbox" v-model="allSelected" /></th>
                  <th>{{ __('common.id') }}</th>
                  <th>{{ __('product.image') }}</th>
                  <th>{{ __('product.name') }}</th>
                  <th>{{ __('product.price') }}</th>
                  <th>{{ __('admin/product.cost_price') }}</th>
                  <th>
                    <div class="d-flex align-items-center">
                        {{ __('common.created_at') }}
                      <div class="d-flex flex-column ml-1 order-by-wrap">
                        <i class="el-icon-caret-top" @click="checkedOrderBy('created_at:asc')"></i>
                        <i class="el-icon-caret-bottom" @click="checkedOrderBy('created_at:desc')"></i>
                      </div>
                    </div>
                  </th>

                  <th class="d-flex align-items-center">
                    <div class="d-flex align-items-center">
                        {{ __('common.sort_order') }}
                      <div class="d-flex flex-column ml-1 order-by-wrap">
                        <i class="el-icon-caret-top" @click="checkedOrderBy('position:asc')"></i>
                        <i class="el-icon-caret-bottom" @click="checkedOrderBy('position:desc')"></i>
                      </div>
                    </div>
                  </th>
                  @if ($type != 'trashed')
                    <th>{{ __('common.status') }}</th>
                  @endif
                  @hook('admin.product.list.column')
                  <th class="text-end">{{ __('common.action') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($products_format as $product)
                <tr>
                  <td><input type="checkbox" :value="{{ $product['id'] }}" v-model="selectedIds" /></td>
                  <td>{{ $product['id'] }}</td>
                  <td>
                    <div class="wh-60 border d-flex rounded-2 justify-content-center align-items-center"><img src="{{ $product['images'][0] ?? 'image/placeholder.png' }}" class="img-fluid max-h-100"></div>
                  </td>
                  <td>
                    <a href="{{ $product['url'] }}" target="_blank" title="{{ $product['name'] }}" class="text-dark">{{ $product['name'] }}</a>
                  </td>
                  <td>{{ $product['price_formatted'] }}</td>
                  <td>{{ $product['cost_price'] }}</td>
                  <td>{{ $product['created_at'] }}</td>
                  <td>{{ $product['position'] }}</td>
                  @if ($type != 'trashed')
                    <td>
                      <div class="form-check form-switch">
                        <input class="form-check-input cursor-pointer" type="checkbox" role="switch" data-active="{{ $product['active'] ? true : false }}" data-id="{{ $product['id'] }}" @change="turnOnOff($event)" {{ $product['active'] ? 'checked' : '' }}>
                      </div>
                    </td>
                  @endif
                  @hook('admin.product.list.column_value')
                  <td class="text-end text-nowrap">
                    @if ($product['deleted_at'] == '')
                      <a href="{{ admin_route('products.edit', [$product['id']]) }}" class="btn btn-outline-secondary btn-sm">{{ __('common.edit') }}</a>
                      <a href="javascript:void(0)" class="btn btn-outline-danger btn-sm" @click.prevent="deleteProduct({{ $loop->index }})">{{ __('common.delete') }}</a>
                      @hook('admin.product.list.action', $product)
                    @else
                      <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm" @click.prevent="restoreProduct({{ $loop->index }})">{{ __('common.restore') }}</a>
                      @hook('admin.products.trashed.action')
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          {{ $products->withQueryString()->links('admin::vendor/pagination/bootstrap-4') }}
        @else
          <x-admin-no-data />
        @endif
      </div>
    </div>
    <div class="modal fade" id="batchUploadModal" tabindex="-1" role="dialog" aria-hidden="true" v-if="showBatchUploadModal">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header" style="justify-content: space-between;">
            <h5 class="modal-title">{{ __('admin/product.batch_upload') }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="closeBatchUploadModal">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info">
              <h6>{{ __('admin/product.batch_upload_instructions') }}</h6>
              <ul class="mb-0 pl-3">
                <li>{{ __('admin/product.batch_upload_tips') }}</li>
                <li>{{ __('admin/product.csv_format_required') }}</li>
                <li>{{ __('admin/product.sku_must_unique') }}</li>
                <li>{{ __('admin/product.batch_upload_supported_fields') }}</li>
              </ul>
            </div>

            <form id="batchUploadForm" action="" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="form-group">
                <label for="csv_file">{{ __('admin/product.select_csv_file') }}</label>
                <input type="file" class="form-control" ref="batchFile" accept=".xls" required>
                <small class="form-text text-muted">{{ __('admin/product.max_file_size') }}</small>
              </div>

              <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" name="skip_errors" id="skip_errors" value="1">
                <label class="form-check-label" for="skip_errors">
                  {{ __('admin/product.skip_error_rows') }}
                </label>
              </div>
            </form>

            <div class="mt-3">
              <a href="{{ admin_route('products.batch_template') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-download"></i> {{ __('admin/product.download_template') }}
              </a>
              <button type="button" class="btn btn-sm btn-outline-secondary" @click="showFieldMapping = !showFieldMapping">
                <i class="bi bi-list"></i> {{ __('admin/product.view_field_mapping') }}
              </button>
            </div>

            <div class="mt-3" v-if="showFieldMapping">
              <div class="card">
                <div class="card-header">
                  <h6 class="mb-0">{{ __('admin/product.field_mapping') }}</h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <strong>{{ __('admin/product.required_fields') }}:</strong>
                      <ul class="mb-2">
                        <li>name_zh,name_en,name_mn,name_ru- {{ __('admin/product.field_name_zh') }}</li>
                        <li>gunit_max_zh,gunit_max_en,gunit_max_mn,gunit_max_ru- {{ __('admin/product.field_gunit_max_zh') }}</li>
                        <li>gunit_min_zh,gunit_min_en,gunit_min_mn,gunit_min_ru- {{ __('admin/product.field_gunit_min_zh') }}</li>
                        <li>min_purchasing_price - {{ __('admin/product.field_min_purchasing_price') }}</li>
                        <li>brand_name - {{ __('admin/product.field_brand_name') }}</li>
                        <li>price - {{ __('admin/product.field_price') }}</li>
                        <li>origin_price - {{ __('admin/product.field_origin_price') }}</li>
                        <li>cost_price - {{ __('admin/product.field_cost_price') }}</li>
                        <li>gnum_min - {{ __('admin/product.field_gnum_min') }}</li>
                        <li>quantity - {{ __('admin/product.field_quantity') }}</li>
                        <li>category_id - {{ __('admin/product.field_category_id') }}</li>
                        <li>goods_code - {{ __('admin/product.field_goods_code') }}</li>
                        <li>min - {{ __('admin/product.field_min') }}</li>
                        <li>images - {{ __('admin/product.field_images') }}</li>
                      </ul>
                    </div>
                    <div class="col-md-6">
                      <strong>{{ __('admin/product.optional_fields') }}:</strong>
                      <ul class="mb-0">
                        <li>gunit_midd_zh,gunit_midd_en,gunit_midd_mn,gunit_midd_ru - {{ __('admin/product.field_gunit_midd_zh') }}</li>
                        <li>gnum_midd - {{ __('admin/product.field_gnum_midd') }}</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeBatchUploadModal">{{ __('common.cancel') }}</button>
            <button type="button" class="btn btn-primary" @click="importBatch" :disabled="uploading">
              <span v-if="uploading">
                <i class="bi bi-hourglass-split"></i> {{ __('common.importing') }}
              </span>
              <span v-else>
                <i class="bi bi-upload"></i> {{ __('common.confirm') }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
    <!-- 将模态框移到 Vue 实例内部 -->
    <!-- 导入模态框 -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true" v-if="showImportModal">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header" style="justify-content: space-between;">
            <h5 class="modal-title">{{ __('admin/product.import_translations') }}</h5>
            {{-- 将关闭按钮移到右上方 --}}
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="closeImportModal">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info">
              <h6>{{ __('admin/product.import_instructions') }}</h6>
              <ul class="mb-0 pl-3">
                <li>{{ __('admin/product.import_tips') }}</li>
                <li>{{ __('admin/product.csv_format') }}</li>
                <li>{{ __('admin/product.import_supported_languages') }}</li>
              </ul>
            </div>
            <div class="form-group">
              <label>{{ __('admin/product.select_file') }}</label>
              <input type="file" class="form-control" ref="importFile" accept=".csv">
              <small class="form-text text-muted">{{ __('admin/product.csv_format') }}</small>
            </div>
            <div class="mt-3">
              <a href="{{ admin_route('products.download_template') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-download"></i> {{ __('admin/product.download_template') }}
              </a>
            </div>
          </div>
          <div class="modal-footer">
            {{-- 移除重复的关闭按钮，只保留取消按钮 --}}
            <button type="button" class="btn btn-secondary" @click="closeImportModal">{{ __('common.cancel') }}</button>
            <button type="button" class="btn btn-primary" @click="importProducts" :disabled="importing">
          <span v-if="importing">
            <i class="bi bi-hourglass-split"></i> {{ __('common.importing') }}
          </span>
              <span v-else>
            <i class="bi bi-upload"></i> {{ __('common.confirm') }}
          </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  @hook('admin.product.list.content.footer')
@endsection

@push('footer')
  <script>
    let app = new Vue({
      el: '#product-app',
      data: {
        url: '{{ $type == 'trashed' ? admin_route("products.trashed") : admin_route("products.index") }}',
        filter: {
          name: bk.getQueryString('name'),
          page: bk.getQueryString('page'),
          category_id: bk.getQueryString('category_id'),
          sku: bk.getQueryString('sku'),
          model: bk.getQueryString('model'),
          active: bk.getQueryString('active'),
          sort: bk.getQueryString('sort', ''),
          order: bk.getQueryString('order', ''),
        },
        showBatchUploadModal: false,
        showImportModal: false,
        showFieldMapping: false,
        uploading: false,
        importing: false,
        selectedIds: [],
        productIds: @json($products->pluck('id')),
      },

      computed: {
        allSelected: {
          get(e) {
            return this.selectedIds.length == this.productIds.length;
          },
          set(val) {
            return val ? this.selectedIds = this.productIds : this.selectedIds = [];
          }
        }
      },

      created() {
        bk.addFilterCondition(this);
      },

      methods: {
        // 打开批量上传模态框
        openBatchUploadModal() {
          this.showBatchUploadModal = true;
          this.$nextTick(() => {
            $('#batchUploadModal').modal('show');
          });
        },

        // 关闭批量上传模态框
        closeBatchUploadModal() {
          this.showBatchUploadModal = false;
          $('#batchUploadModal').modal('hide');
          this.showFieldMapping = false;
        },

        // 导入商品翻译信息
        importBatch() {
          const fileInput = this.$refs.batchFile;
          const file = fileInput.files[0];

          if (!file) {
            this.$message.warning('{{ __('admin/product.please_select_file') }}');
            return;
          }

          // 检查文件类型
          if (!file.name.toLowerCase().endsWith('.csv')&&!file.name.toLowerCase().endsWith('.xls')) {
            this.$message.warning('{{ __('admin/product.csv_format') }}');
            return;
          }

          this.uploading = true;

          const formData = new FormData();
          formData.append('csv_file', file);
          formData.append('_token', '{{ csrf_token() }}');

          $http.post('{{ admin_route("products.batch_store") }}', formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          }).then((res) => {
            this.$message.success(res.message);
            this.showBatchUploadModal = false;
            this.uploading = false;
            fileInput.value = '';
            // location.reload();
          }).catch((error) => {
            this.$message.error(error.message || '{{ __('common.import_failed') }}');
            this.uploading = false;
          });
        },

        // 打开导入模态框
        openImportModal() {
          this.showImportModal = true;
          // 使用 $nextTick 确保 DOM 更新后再初始化模态框
          this.$nextTick(() => {
            $('#importModal').modal('show');
          });
        },

        // 关闭导入模态框
        closeImportModal() {
          this.showImportModal = false;
          $('#importModal').modal('hide');
        },
        // 导出商品翻译信息
        exportProducts() {
          if (!this.selectedIds.length) {
            this.$message.warning('{{ __('admin/product.select_products_to_export') }}');
            return;
          }

          const params = {
            ids: this.selectedIds,
            _token: '{{ csrf_token() }}'
          };

          // 创建表单进行下载
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = '{{ admin_route("products.export_translations") }}';

          Object.keys(params).forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = params[key];
            form.appendChild(input);
          });

          document.body.appendChild(form);
          form.submit();
          document.body.removeChild(form);
        },

        // 导入商品翻译信息
        importProducts() {
          const fileInput = this.$refs.importFile;
          const file = fileInput.files[0];

          if (!file) {
            this.$message.warning('{{ __('admin/product.please_select_file') }}');
            return;
          }

          // 检查文件类型
          if (!file.name.toLowerCase().endsWith('.csv')) {
            this.$message.warning('{{ __('admin/product.csv_format') }}');
            return;
          }

          this.importing = true;

          const formData = new FormData();
          formData.append('file', file);
          formData.append('_token', '{{ csrf_token() }}');

          $http.post('{{ admin_route("products.import_translations") }}', formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          }).then((res) => {
            this.$message.success(res.message);
            this.showImportModal = false;
            this.importing = false;
            fileInput.value = '';
            location.reload();
          }).catch((error) => {
            this.$message.error(error.message || '{{ __('common.import_failed') }}');
            this.importing = false;
          });
        },
        turnOnOff(event) {
          let id = event.currentTarget.getAttribute("data-id");
          let checked = event.currentTarget.getAttribute("data-active");
          let type = true;
          if (checked) type = false;
          $http.post('products/status', {ids: [id], status: type}).then((res) => {
            layer.msg(res.message)
          })
        },

        batchDelete() {
          this.$confirm('{{ __('admin/product.confirm_batch_product') }}', '{{ __('common.text_hint') }}', {
            confirmButtonText: '{{ __('common.confirm') }}',
            cancelButtonText: '{{ __('common.cancel') }}',
            type: 'warning'
          }).then(() => {
            $http.delete('products/delete', {ids: this.selectedIds}).then((res) => {
              layer.msg(res.message)
              location.reload();
            })
          }).catch(()=>{});
        },

        batchActive(type) {
          this.$confirm('{{ __('admin/product.confirm_batch_status') }}', '{{ __('common.text_hint') }}', {
            confirmButtonText: '{{ __('common.confirm') }}',
            cancelButtonText: '{{ __('common.cancel') }}',
            type: 'warning'
          }).then(() => {
            $http.post('products/status', {ids: this.selectedIds, status: type}).then((res) => {
              layer.msg(res.message)
              location.reload();
            })
          }).catch(()=>{});
        },

        search() {
          this.filter.page = '';
          location = bk.objectToUrlParams(this.filter, this.url)
        },

        checkedOrderBy(orderBy) {
          this.filter.sort = orderBy.split(':')[0];
          this.filter.order = orderBy.split(':')[1];
          location = bk.objectToUrlParams(this.filter, this.url)
        },

        resetSearch() {
          this.filter = bk.clearObjectValue(this.filter)
          location = bk.objectToUrlParams(this.filter, this.url)
        },

        deleteProduct(index) {
          const id = this.productIds[index];

          this.$confirm('{{ __('common.confirm_delete') }}', '{{ __('common.text_hint') }}', {
            type: 'warning'
          }).then(() => {
            $http.delete('products/' + id).then((res) => {
              this.$message.success(res.message);
              location.reload();
            })
          }).catch(()=>{});;
        },

        restoreProduct(index) {
          const id = this.productIds[index];

          this.$confirm('{{ __('admin/product.confirm_batch_restore') }}', '{{ __('common.text_hint') }}', {
            type: 'warning'
          }).then(() => {
            $http.put('products/restore', {id: id}).then((res) => {
              location.reload();
            })
          }).catch(()=>{});;
        },

        clearRestore() {
          this.$confirm('{{ __('admin/product.confirm_delete_restore') }}', '{{ __('common.text_hint') }}', {
            type: 'warning'
          }).then(() => {
            $http.post('products/trashed/clear').then((res) => {
              location.reload();
            })
          }).catch(()=>{});;
        }
      }
    });
  </script>
@endpush
