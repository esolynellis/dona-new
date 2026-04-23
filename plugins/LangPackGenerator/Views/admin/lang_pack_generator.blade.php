@extends('admin::layouts.master')

@section('title', $name)

@section('content')
  <div id="tax-classes-app" class="card" v-cloak>
    <div class="card-body h-min-600">
      {{-- <div class="d-flex justify-content-between mb-4"> --}}
      {{-- <button type="button" class="btn btn-primary" @click="checkedCreate('add', null)">添加</button> --}}
      {{-- </div> --}}
      <div class="d-flex justify-content-between mb-4">
        <button type="button" class="btn btn-primary"
                @click="checkedCreate('add', null)">{{ __('LangPackGenerator::common.generator') }}</button>


        <el-button @click="checkedConfig"  type="warning"  ><i class="el-icon-setting"> </i> {{ __('LangPackGenerator::common.config') }}</el-button>
      </div>
      <div class="mb-3 alert alert-info">{{ __('LangPackGenerator::common.help') }}</div>
      <table class="table">
        <thead>
        <tr>
          <th>{{ __('LangPackGenerator::common.type') }}  </th>

          <th>{{ __('LangPackGenerator::common.from_name') }}  </th>
          <th>{{ __('LangPackGenerator::common.to_name') }}  </th>
          <th>
            {{ __('LangPackGenerator::common.status') }}
          </th>
          <th>{{ __('LangPackGenerator::common.progress') }}  </th>
          <th class="text-end">{{ __('common.action') }}</th>
        </tr>
        </thead>
        <tbody>

        <tr v-for="item, index in lists" :key="index">
          <td>

            <el-tag v-if="parseInt(item.type) === 1" type="warning">{{ __('LangPackGenerator::common.plugin_lang') }}:
              @{{ item?.plugin?.name }}
            </el-tag>
            <el-tag v-else>{{ __('LangPackGenerator::common.system_lang') }}</el-tag>
          </td>

          <td>
            @{{ item.from_name }}(@{{ item.from_code }})
            <span class="badge bg-success"
                  v-if="settingLocale == item.from_code">{{ __('common.default') }}</span>
          </td>
          <td :title="item?.to_name">

            <el-tooltip  effect="dark" :content="item?.to_name" placement="top-start">
              <div>@{{ item?.custom_name || item?.to_name }}(@{{ item?.to_code }})</div>
            </el-tooltip>

          </td>
          <td>
                        <span class="badge bg-secondary"
                              v-if="parseInt(item.status)===0">@{{ item?.status_text }}</span>
            <span class="badge bg-warning" v-else-if="parseInt(item.status)===1">

               @{{ item?.status_text }}
             </span>
            <span class="badge bg-success"
                  v-else-if="parseInt(item.status)===2">@{{ item?.status_text }}</span>
            <span class="badge bg-warning"
                  v-else-if="parseInt(item.status)===3">@{{ item?.status_text }}</span>
            <span class="badge bg-danger"
                  v-else-if="parseInt(item.status)===4">@{{ item?.status_text }}</span>

          </td>
          <td style="width: 400px">
            <div class="el-row--flex">
              <el-progress style="width: 300px" :width="100" :color="customColorMethod"
                           :percentage="item.progress"></el-progress>
              <i v-if="parseInt(item.status)===1" class="el-icon-loading"></i>
            </div>

            <div class="el-row--flex  ">
              <div class="mr-4" style="margin-right: 10px">
                <i class="el-icon-wind-power"> </i> <span>@{{ item?.run_task_number }}/@{{ item?.task_number }} </span>
              </div>
              <div
                v-if="item?.timeout">
                <i class="el-icon-timer"> </i>
                <span>  @{{ item?.timeout }}s </span>
              </div>
            </div>
          </td>
          <td class="text-end">
            <button v-if="parseInt(item.status)===0 || parseInt(item.status)===3 "
                    class="btn btn-outline-primary  btn-sm"
                    @click="checkedRun(item, index)">{{ __('LangPackGenerator::common.run') }}</button>

            <button v-if="parseInt(item.status)===1 " class="btn btn-outline-danger  btn-sm"
                    @click="checkedStop(item, index)">{{ __('LangPackGenerator::common.stop') }}</button>

            <button v-if="parseInt(item.type)===0 " class="btn btn-outline-secondary btn-sm"
                                @click="checkedCreate('edit', index)">{{ __('common.edit') }}</button>

            <el-button v-if="parseInt(item.status)===2 " size="mini" plain @click="checkImport(item, index)"
                       type="success">{{ __('LangPackGenerator::common.import') }}</el-button>

            <el-button v-if="parseInt(item.status)===4 || parseInt(item.status)===2 " size="mini" plain
                       @click="checkedRestart(item, index)"
                       type="warning">{{ __('LangPackGenerator::common.restart') }}</el-button>

            <el-button v-if="parseInt(item.status)!==0 " size="mini" plain @click="logOpen(item, index)"
                       type="warning">{{ __('LangPackGenerator::common.log') }}</el-button>

            <el-button
              v-if="parseInt(item.status)===4 || parseInt(item.status)===2 || parseInt(item.status)===0"
              size="mini" plain @click="checkDelete(item, index)"
              type="danger">{{ __('LangPackGenerator::common.delete') }}</el-button>

            <el-button size="mini" @click="checkedBackups(item, index)"
            >{{ __('LangPackGenerator::common.backups_log') }}</el-button>
          </td>
        </tr>
        </tbody>
      </table>

    </div>

    <el-dialog :title="`${ dialog.title || '' }{{ __('LangPackGenerator::common.plugin_name') }}`"
               :visible.sync="dialog.show" width="80%"
               @close="closeCustomersDialog('form')" :close-on-click-modal="false">

      <el-form ref="form" :rules="rules" :model="dialog.form" label-width="100px">
        <el-form-item label="{{ __('LangPackGenerator::common.type') }}" class="mb-3">
          <el-radio v-model="dialog.form.type" :label="0">{{ __('LangPackGenerator::common.system_lang') }}</el-radio>
          <el-radio v-if="!parseInt(dialog.form.id)" v-model="dialog.form.type" :label="1">{{ __('LangPackGenerator::common.plugin_lang') }}</el-radio>
{{--          <el-radio v-model="dialog.form.type" :label="2">{{ __('LangPackGenerator::common.file_lang') }}</el-radio>--}}
        </el-form-item>

        <div class="d-flex w-100">
          <div>



            <el-form-item v-if="parseInt(dialog.form.type) === 1"
                          label="{{ __('LangPackGenerator::common.plugin name') }}"
                          class="mb-3">
              <el-select v-model="dialog.form.plugin_code" filterable
                         placeholder="{{ __('LangPackGenerator::common.plugin name') }}">
                <el-option
                  v-for="plugin, index in plugins" :key="index"
                  :key="plugin.code"
                  :label="`${plugin.name}(${plugin.code})`"
                  :value="plugin.code">
                </el-option>
              </el-select>
            </el-form-item>

            <el-form-item label="{{ __('LangPackGenerator::common.from_code') }}" class="mb-3">
              <el-select disabled v-model="dialog.form.from_code" filterable
                         placeholder="{{ __('LangPackGenerator::common.from_code') }}">
                <el-option
                  v-for="language, index in languages" :key="index"
                  :key="language.code"
                  :label="`${language.name}(${language.code})`"
                  :value="language.code">
                </el-option>
              </el-select>
            </el-form-item>

            <el-form-item label="{{ __('LangPackGenerator::common.to_code') }}" class="mb-3">
              <el-select v-if="parseInt(dialog.form.type) === 1" v-if="dialog.to_type === 'select'" v-model="dialog.form.to_code" filterable
                         placeholder="{{ __('LangPackGenerator::common.to_code') }}"
                         :disabled="dialog.type==='add'?false:true" @change="changeToCode">

                <el-option
                        v-for="language, index in languages" :key="index"
                        :key="language.code"
                        :label="`${language.name}(${language.code})`"
                        :value="language.code">
                </el-option>
              </el-select>

              <el-select v-if="parseInt(dialog.form.type) === 0" v-if="dialog.to_type === 'select'" v-model="dialog.form.to_code" filterable
                         placeholder="{{ __('LangPackGenerator::common.to_code') }}"
                         :disabled="dialog.type==='add'?false:true" @change="changeToCode">
                <el-option
                        v-for="language, index in support_languages" :key="index"
                        v-if="language.code !== ''"
                        :key="language.code"
                        :label="`${language.zh}(${language.code})`"
                        :value="language.code">
                </el-option>
              </el-select>



              <el-input v-if="dialog.to_type === 'custom'" v-model="dialog.form.to_code"
                        :disabled="dialog.type==='add'?false:true"
                        placeholder="{{ __('LangPackGenerator::common.to_code') }}"></el-input>

            </el-form-item>
            <el-form-item v-if="parseInt(dialog.form.type) === 0" label="{{ __('LangPackGenerator::common.custom_name') }}"
                          class="mb-3">
                    <el-input v-if="parseInt(dialog.form.type) === 0" v-model="dialog.form.custom_name"

                        placeholder="{{ __('LangPackGenerator::common.custom_name') }}"></el-input>
            </el-form-item>

            <el-form-item v-if="dialog.to_type === 'custom'" label="{{ __('LangPackGenerator::common.to_name') }}"
                          class="mb-3">
              <el-input v-model="dialog.form.to_name" :disabled="false"
                        placeholder="{{ __('LangPackGenerator::common.to_name') }}"></el-input>
              <p style=" margin-bottom: 0px">{{ __('LangPackGenerator::common.reference world country shorthand code') }}
                :<a
                  target="_blank"
                  href="https://blog.csdn.net/tianya441523/article/details/104892794">{{ __('LangPackGenerator::common.look') }} </a><span
                > {{ __('LangPackGenerator::common.Filling errors may affect the translation results') }}
          </span>
              </p>


            </el-form-item>

            <el-form-item v-if="parseInt(dialog.form.type) === 2" label="{{ __('LangPackGenerator::common.file') }}"
                          class="mb-3">

              <el-select v-model="dialog.form.plugin_code" filterable
                         placeholder="{{ __('LangPackGenerator::common.file') }}">
                <el-option
                  v-for="plugin, index in plugins" :key="index"
                  :key="plugin.code"
                  :label="`${plugin.name}(${plugin.code})`"
                  :value="plugin.code">
                </el-option>
              </el-select>


            </el-form-item>
            <div style="color: #aaa;font-size: 12px">
              <div>提示</div>
              <div>新语言编码：根据【配置】线路决定。 每个配置线路的翻译编码是不同的，以下是每个线路的<a href="{{ admin_route('plugins.edit', ['code' => 'lang_pack_generator'])  }}">语言翻译列表</a></div>
              <div>当前语言编码：{{ \Plugin\LangPackGenerator\Libraries\LangPackGenerator::getCurrentPlatformInfo('label')  }}</div>
            </div>
          </div>
          <div class="w-100 ml-3" style="margin-left: 10px" v-if="parseInt(dialog.form.type) === 2">



            <el-table
              ref="multipleTable"
              :data="tableData"
              tooltip-effect="dark"
              style="width: 100%" >
              <el-table-column
                type="selection"
                width="55">
              </el-table-column>
              <el-table-column
                label="日期"
                width="120">
                <template slot-scope="scope">@{{ scope.row.date }}</template>
              </el-table-column>
              <el-table-column
                prop="name"
                label="姓名"
                width="120">
              </el-table-column>
              <el-table-column
                prop="address"
                label="地址"
                show-overflow-tooltip>
              </el-table-column>
            </el-table>


          </div>

        </div>


        <el-form-item class="mt-5" class="mb-3">

          <el-button  type="primary"
                     @click="addFormSubmit('form')">{{ __('LangPackGenerator::common.confirm') }}</el-button>
          <el-button @click="closeCustomersDialog('form')">{{ __('common.cancel') }}</el-button>
        </el-form-item>
      </el-form>

    </el-dialog>


    <el-dialog
      title="{{ __('LangPackGenerator::common.backups_log') }}"
      :visible.sync="backups.show"
      width="60%"
      :before-close="handleBackupsClose">
      <div>
        <table class="table">
          <thead>
          <tr>
            <th>备份文件</th>
            <th>操作</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="item, index in backups.list">
            <td>@{{ item.name }}</td>
            <td>
              <el-button size="mini" @click="confirmRestoreBackups(item, index)"
              >{{ __('LangPackGenerator::common.restore_backups') }}</el-button>
            </td>
          </tr>
          </tbody>

        </table>
      </div>
      <span slot="footer" class="dialog-footer">
  </span>
    </el-dialog>


    <el-dialog
      title="{{ __('LangPackGenerator::common.import') }}"
      :visible.sync="lang_import.show"
      width="30%"
      :before-close="handleClose">
      <span>{{ __('LangPackGenerator::common.whether to confirm') }} <span style="font-weight: bold;color: #000">@{{ lang_import.item?.to_name  }}(@{{ lang_import.item?.to_code  }})</span>  {{ __('LangPackGenerator::common.importing language packs into the system') }}?</span>
      <span slot="footer" class="dialog-footer">
    <el-button @click="lang_import.show = false">{{ __('common.cancel') }}</el-button>
    <el-button type="primary" @click="langImport">{{ __('LangPackGenerator::common.confirm') }}</el-button>
  </span>
    </el-dialog>

    <el-dialog
      title="{{ __('LangPackGenerator::common.config') }}"
      :visible.sync="config.show"
      width="70%"
      :before-close="handleClose">
      <div>
        <el-form ref="form" :rules="rules" :model="dialog.form" label-width="100px">
          <el-form-item label="{{ __('LangPackGenerator::common.platform') }}" class="mb-3">
            <el-select
              v-model="config.open_platform"
              multiple

              style="width: 100%"
              placeholder="请选择">
              <el-option
                v-for="item in open_platform"
                :key="item.id"
                :label="item.label"
                :value="item.id">
              </el-option>
            </el-select>
            <div class="help-text font-size-12 lh-base">
              {{ __('LangPackGenerator::common.config_platform_tips') }}

            </div>
          </el-form-item>
          <el-form-item label="{{ __('LangPackGenerator::common.frequency') }}" class="mb-3">

            <el-input v-model="config.sleep" type="number"
                      placeholder="{{ __('LangPackGenerator::common.frequency') }}"></el-input>
            <div class="help-text font-size-12 lh-base">
              {{ __('LangPackGenerator::common.frequency_tips') }}
            </div>
          </el-form-item>

          <h6>{{ __('LangPackGenerator::common.config_youdao') }}</h6>

          <el-form-item label="{{ __('LangPackGenerator::common.appId') }}" class="mb-3">

            <el-input v-model="config.app_id"
                      placeholder="{{ __('LangPackGenerator::common.appId') }}"></el-input>


          </el-form-item>


          <el-form-item label="{{ __('LangPackGenerator::common.appSecret') }}" class="mb-3">
            <el-input v-model="config.app_secret"
                      placeholder="{{ __('LangPackGenerator::common.appSecret') }}"></el-input>

            <div class="help-text font-size-12 lh-base">
              {{ __('LangPackGenerator::common.config_youdao_docs') }}:<a
                href="https://ai.youdao.com/DOCSIRMA/html/trans/api/wbfy/index.html"
                target="_blank">{{ __('LangPackGenerator::common.look_click') }}</a>


            </div>
          </el-form-item>

          <h6>{{ __('LangPackGenerator::common.config_baidu') }}</h6>

          <el-form-item label="{{ __('LangPackGenerator::common.appId') }}" class="mb-3">

            <el-input v-model="config.baidu_app_id"
                      placeholder="{{ __('LangPackGenerator::common.appId') }}"></el-input>


          </el-form-item>

          <el-form-item label="{{ __('LangPackGenerator::common.appSecret') }}" class="mb-3">
            <el-input v-model="config.baidu_app_secret"
                      placeholder="{{ __('LangPackGenerator::common.appSecret') }}"></el-input>
            <div class="help-text font-size-12 lh-base">
              {{ __('LangPackGenerator::common.config_baidu_docs') }}:<a href="https://fanyi-api.baidu.com/doc/21"
                                                                         target="_blank"> {{ __('LangPackGenerator::common.look_click') }}</a>
            </div>
          </el-form-item>

        </el-form>

      </div>
      <span slot="footer" class="dialog-footer">
    <el-button @click="config.show = false">{{ __('common.cancel') }}</el-button>
    <el-button type="primary" @click="configSave">{{ __('LangPackGenerator::common.save') }}</el-button>
  </span>
    </el-dialog>

    <el-dialog
      title="运行语言包生成器"
      :visible.sync="run_dialog.show"
      width="20%"
      :before-close="handleClose">
      <span>@{{ run_dialog.tips }}</span>
      <span slot="footer" class="dialog-footer">
    <el-button @click="run_dialog.show = false">{{ __('common.cancel') }}</el-button>
    <el-button type="primary" @click="startRun">{{ __('LangPackGenerator::common.run') }}</el-button>
  </span>
    </el-dialog>

    <el-dialog
      title="{{ __('LangPackGenerator::common.delete') }}"
      :visible.sync="del.show"
      width="20%">
      <span>{{ __('LangPackGenerator::common.confirm deletion') }}?</span>
      <span slot="footer" class="dialog-footer">
    <el-button @click="del.show = false">{{ __('common.cancel') }}</el-button>
    <el-button type="primary" @click="deleteItem">{{ __('LangPackGenerator::common.confirm') }}</el-button>
  </span>
    </el-dialog>

    <el-dialog
      title="{{ __('LangPackGenerator::common.stop') }}"
      :visible.sync="stop.show"
      width="30%"
      :before-close="handleClose">
      <span>{{ __('LangPackGenerator::common.confirm to stop generation') }}?</span>
      <span slot="footer" class="dialog-footer">
    <el-button @click="stop.show = false">{{ __('common.cancel') }}</el-button>
    <el-button type="primary" @click="postStop">{{ __('LangPackGenerator::common.confirm') }}</el-button>
  </span>
    </el-dialog>

    <el-dialog
      title="{{ __('LangPackGenerator::common.restore_backups') }}"
      :visible.sync="confirm_backups.show"
      :before-close="handleClose"
      width="30%">
      <span>{{ __('LangPackGenerator::common.restore_backups_tips') }}?</span>
      <span slot="footer" class="dialog-footer">
    <el-button @click="stop.show = false">{{ __('common.cancel') }}</el-button>
    <el-button type="primary"
               @click="submitConfirmRestoreBackups">{{ __('LangPackGenerator::common.confirm') }}</el-button>
  </span>
    </el-dialog>


    <el-drawer
      title="{{ __('LangPackGenerator::common.log details') }}"
      size="50%"
      :visible.sync="log.show"
      :before-close="handleClose"
    >

      <div class="d-flex  align-items-center" style="margin-left: 20px;height: 300px;overflow-y: auto">
        <div class="text-center">
          <el-progress type="circle" :percentage="log.item?.progress"></el-progress>
          <div class="mt-2 mb-5   ">{{ __('LangPackGenerator::common.running progress') }}:@{{
            log.item?.run_task_number
            }}/@{{ log.item?.task_number }}
          </div>

        </div>
        <div style="margin-left: 20px; padding: 10px; width: 100%; margin-right: 20px">
          <el-form ref="form" :rules="rules" :model="dialog.form" label-width="100px">
            <el-form-item label="{{ __('LangPackGenerator::common.file_count') }}">
              @{{ log.item?.task_number }}
            </el-form-item>
            <el-form-item label="{{ __('LangPackGenerator::common.task_name') }}">
              @{{ log?.item.from_name }}(@{{ log?.item.from_code }}) -> @{{ log?.item.to_name }}(@{{
              log?.item.to_code
              }})
            </el-form-item>

            <el-form-item label="{{ __('LangPackGenerator::common.start_time') }}">
              @{{ log.item?.start_time_format }}
            </el-form-item>
            <el-form-item label="{{ __('LangPackGenerator::common.timeout') }}">
              @{{ log.item?.timeout }} s
            </el-form-item>
            <el-form-item label="{{ __('LangPackGenerator::common.status') }}">
              <span class="badge bg-secondary" v-if="parseInt(log.item?.status)===0">@{{ log.item?.status_text }}</span>
              <span class="badge bg-warning" v-else-if="parseInt(log.item?.status)===1">

               @{{ log.item?.status_text }}
             </span>
              <span class="badge bg-success"
                    v-else-if="parseInt(log.item?.status)===2">@{{ log.item?.status_text }}</span>
              <span class="badge bg-warning"
                    v-else-if="parseInt(log.item?.status)===3">@{{ log.item?.status_text }}</span>
              <span class="badge bg-danger"
                    v-else-if="parseInt(log.item?.status)===4">@{{ log.item?.status_text }}</span>

            </el-form-item>


          </el-form>

        </div>

      </div>
      <div v-if="log.item?.errors" style="padding: 20px; background: #f9f9f9">

        <div v-for="item, index in log.item?.errors" :key="index">
          <pre style="margin-bottom: 0">
            <p class="font-weight-bold" style="color: red;font-weight: bold; ">{{ __('LangPackGenerator::common.error description') }}: @{{ item.title  }}</p>
        </pre>
          <pre>
            <p>{{ __('LangPackGenerator::common.trace') }}: @{{ item.trace  }}</p>
        </pre>
        </div>
      </div>
      <div class="flex-fill   align-content-between" style="display: flex;justify-content: space-between;margin:auto 10px">
        <h5 style="margin-left: 10px">运行日志</h5>
        <a  href="javascript:;" @click="logDown">下载日志</a>
      </div>
      <div class="pl-2 mt-2 mr-2" id="logs-box" style="padding:10px; margin: 10px; border-radius: 5px;background: #f3f3f3 ">
        <pre>@{{ log.lists }}</pre>
        {{--        <table class="table">--}}
        {{--          <thead>--}}
        {{--          <tr>--}}
        {{--            <th>{{ __('LangPackGenerator::common.date') }}  </th>--}}
        {{--            <th>{{ __('LangPackGenerator::common.log_result') }}  </th>--}}
        {{--          </tr>--}}
        {{--          </thead>--}}
        {{--          <tbody>--}}

        {{--          <tr v-for="item, index in log.lists.data" :index="index">--}}
        {{--            <td style="width: 200px">--}}
        {{--              @{{item.created_format}}--}}
        {{--            </td>--}}
        {{--            <td>--}}
        {{--              <div style="width:400px">--}}
        {{--                @{{item.result}}--}}
        {{--              </div>--}}
        {{--            </td>--}}

        {{--          </tr>--}}
        {{--          </tbody>--}}
        {{--        </table>--}}

      </div>
    </el-drawer>


  </div>

@endsection

@push('footer')
  @include('admin::shared.vue-image')
  <style>
    /*.lang-pack-generator-text-info{*/
    /*  color: red;*/
    /*}*/
    .el-form-item {
      margin-bottom: 0px;
    }
  </style>
  <script>
    new Vue({
      el: '#tax-classes-app',

      data: {
        log_timer:false,
        tableData: [  ],
        timer: null,
        open_platform:@json($open_platform ?? []),
        plugins: @json($plugins ?? []),
        languages: @json($languages ?? []),
        support_languages: @json($support_languages ?? []),
        lists: @json($records['data'] ?? []),
        settingLocale: @json(system_setting('base.locale') ?? 'zh_cn'),
        customColors: [
          {color: '#f56c6c', percentage: 20},
          {color: '#e6a23c', percentage: 40},
          {color: '#5cb87a', percentage: 60},
          {color: '#1989fa', percentage: 80},
          {color: '#6f7ad3', percentage: 100}
        ],
        log: {
          show: false,
          lists: [],
          item: {},
        },
        stop: {
          show: false,
          item: {},
          index: 0,
        },
        del: {
          show: false,
          item: {},
          index: 0,
        },
        run_dialog: {
          show: false,
          item: {},
          index: 0,
          type: 0,
          tips: '是否确认运行程序?',
        },
        dialog: {
          show: false,
          index: null,
          type: 'add',
          title: '',
          to_type: 'select',
          form: {
            custom_name:'',
            plugin_code: '',
            type: 0,
            id: 0,
            from_code: 'zh_cn',
            from_name: '',
            to_code: '',
            to_name: '',
            // image: '',

          }
        },
        lang_import: {
          show: false,
          item: {},
          index: 0,
        },
        config: {
          show: false,
          sleep: '{{ $config['sleep']?:1  }}',
          open_platform: @json($config['platform'] ?? []),
          app_secret: '{{ $config['app_secret']  }}',
          app_id: '{{ $config['app_id']  }}',
          baidu_app_id: '{{ $config['baidu_app_id']  }}',
          baidu_app_secret: '{{ $config['baidu_app_secret']  }}',
        },
        backups: {
          show: false,
          item: {},
          index: 0,
          list: []
        },
        confirm_backups: {
          show: false,
          item: {},
          index: 0,
          list: []
        },
        rules: {}
      },
      mounted: function () {
        let that = this;
        this.$nextTick(function () {

          that.isRun();
        })
      },
      methods: {
        checkedCreate(type, index) {
          this.dialog.show = true
          this.dialog.type = type
          this.dialog.index = index

          if (type == 'edit') {
            this.dialog.title = '{{ __('common.edit' ) }}';
            this.dialog.form = this.lists[index];

          }
          if (type == 'add') {
            this.dialog.form = {
              custom_name:'',
              plugin_code: '',
              type: 0,
              id: 0,
              from_code: 'zh_cn',
              from_name: '',
              to_code: '',
              to_name: '',
              // image: '',

            };
            this.dialog.title = '{{ __('common.add' ) }}';
          }
        },
        checkedConfig() {
          return location.href="{{ admin_route('plugins.edit', ['code' => 'lang_pack_generator'])  }}"
          // this.config.show = true
        },
        configSave() {
          let that = this;
          $http.post('lang_pack_generator_config', this.config, {
            'hload': false,
            'hmsg': false
          }).then((res) => {
            that.config.show = false;
            this.$forceUpdate();
          })
        },
        confirmRestoreBackups(item, index) {
          this.confirm_backups.item = item;
          this.confirm_backups.index = index;
          this.confirm_backups.show = true;
        },
        submitConfirmRestoreBackups() {
          let that = this;
          $http.post('lang_pack_generator_submit_restore_backups', {
            file: that.confirm_backups.item.name,
            id: that.backups.item.id
          }, {
            'hload': false,
            'hmsg': false
          }).then((res) => {
            that.confirm_backups.show = false;
            this.$message.success(res.message || '{{ __('LangPackGenerator::common.request_success') }}');
            this.$forceUpdate();
          })
        },
        checkedRun(item, index) {
          this.run_dialog.item = item;
          this.run_dialog.index = index;
          this.run_dialog.show = true;
        },
        customColorMethod(percentage) {
          if (percentage < 30) {
            return '#909399';
          } else if (percentage < 70) {
            return '#e6a23c';
          } else {
            return '#67c23a';
          }
        },
        addFormSubmit(form) {
          const self = this;
          const type = this.dialog.type == 'add' ? 'post' : 'post';
          const url = this.dialog.type == 'add' ? 'lang_pack_generator_add' : 'lang_pack_generator_edit';

          this.$refs[form].validate((valid) => {
            if (!valid) {
              this.$message.error('{{ __('common.error_form') }}');
              return;
            }
            if (this.dialog.form.to_code === 'zh_hk'){
              this.dialog.form.to_code = code = 'zh-CHT';
            }
            if (this.dialog.form.to_code === 'zh_cn'){
              this.dialog.form.to_code = code = 'zh-CHS';
            }

            $http[type](url, this.dialog.form).then((res) => {
              if (parseInt(res.code) !== 200) {
                this.$message.error(res.message || '{{ __('LangPackGenerator::common.request_fail') }}');
              } else {
                this.$message.success(res.message || '{{ __('LangPackGenerator::common.request_success') }}');
              }

              this.updateData();
              this.dialog.show = false
            })
          });
        },
        checkedStop(item, index) {
          this.stop.item = item;
          this.stop.index = index;
          this.stop.show = true;
        },
        checkedBackups(item, index) {
          let that = this;
          this.backups.item = item;
          this.backups.index = index;
          this.backups.show = true;
          $http.get('lang_pack_generator_backups_log', {id: item.id}).then((res) => {
            if (parseInt(res.code) !== 200) {
              this.$message.error(res.message);
              this.run_dialog.item.status_text = '{{ __('LangPackGenerator::common.errors') }}';
            } else {
              that.backups.list = res.data;

            }
            this.$forceUpdate();
          })
        },
        postStop() {
          let that = this;
          let id = this.stop.item?.id;
          $http.post('lang_pack_generator_stop', {id}).then((res) => {

            this.$message.success(res.message);
            that.stop.show = false;
            this.$forceUpdate();
          })
        },
        isRun() {
          let that = this;
          let is_run = false;
          //调用需要执行的方法
          this.lists.forEach(function (item, index) {
            if (parseInt(item.status) === 1) {
              is_run = true;
            }
          })
          if (is_run === true) {
            that.progress()
          }
          return is_run;
        },
        progress() {
          let that = this;
          try {


            $http.get('lang_pack_generator_progress', {}, {'hload': true, 'hmsg': true}).then((res) => {

              that.lists = res.data;
              let is_run = false;
              //调用需要执行的方法
              this.lists.forEach(function (item, index) {
                if (parseInt(item.status) === 1) {
                  is_run = true;
                }
              })
              if (is_run === true) {
                that.progress();
              }
              this.$forceUpdate();
            })
          } catch (err) {
            that.progress();
            console.error(err, "catch")
          }

        },
        updateData() {
          let that = this;

          $http.get('lang_pack_generator_lists', {}, {'hload': true, 'hmsg': true}).then((res) => {

            that.lists = res.data;
            let is_run = false;
            //调用需要执行的方法
            this.lists.forEach(function (item, index) {
              if (parseInt(item.status) === 1) {
                is_run = true;
              }
            })
            if (is_run === false) {
              clearInterval(that.timer);
            }
            this.$forceUpdate();
          })
        },

        logOpen(item, index) {
          let that = this;

          that.logRequest(item, index)
          that.log.show = true;

        },
        logRequest(item, index) {
          let that = this;
          let id = item?.id;
          return $http.get('lang_pack_generator_logs?id=' + id+'&r='+Math.random() , {},{hload:true}).then(res=>{
            that.log.lists = res?.content||'暂无日志';
            if(res?.item){
              item = res?.item;
            }

            this.$forceUpdate();
            this.log_timer = setTimeout(function() {
              that.logRequest(item, index)
            }, 3000);
            that.scrollToBottom();
            that.log.item = item;


            if (parseInt(item.status) !== 1 ){
              clearInterval(that.log_timer);
              return false;
            }
          });
        },

        logDown(){
          let item = this.log.item
          location.href = '{{ admin_route('LangPackGenerator.logs')  }}?id='+item.id+'&type=down';
        },
        checkDelete(item, index) {
          this.del.item = item;
          this.del.index = index;
          this.del.show = true;
        },
        deleteItem() {

          $http.post('lang_pack_generator_del', {'id': this.del.item?.id}).then((res) => {
            this.$message.success(res.message);
            this.del.show = false;
            this.updateData();
            this.$forceUpdate();
          })
        },
        handleClose() {
          this.lang_import.show = false;
          this.confirm_backups.show = false;
          this.dialog.show = false;
          this.del.show = false;
          this.log.show = false;
          this.stop.show = false;
          this.run_dialog.show = false;
          this.log_timer = false;
          this.config.show = false;
        },
        handleBackupsClose() {
          this.backups.show = false;
        },
        startRun() {
          let that = this;
          let item = this.run_dialog.item;

          $http.post('lang_pack_generator_run', {id: item.id, type: that.run_dialog.type}).then((res) => {
            if (parseInt(res.code) !== 200) {
              this.$message.error(res.message);
              this.run_dialog.item.status = 4;
              this.run_dialog.item.status_text = '{{ __('LangPackGenerator::common.errors') }}';
            } else {
              this.run_dialog.item.status = 1;
              this.run_dialog.item.status_text = '{{ __('LangPackGenerator::common.run') }}';
              this.$message.success(res.message);
              that.isRun();
            }

            this.$forceUpdate();
          })
          this.run_dialog.show = false;
        },
        checkedRestart(item, index) {
          this.run_dialog.item = item;
          this.run_dialog.index = index;
          this.run_dialog.type = 1;
          this.run_dialog.show = true;
          this.run_dialog.tips = '{{ __('LangPackGenerator::common.confirm to rerun program') }}';
        },
        scrollToBottom() {

        },
        progressFormat(percentage, item) {

          return `${percentage}%(${item.run_task_number}/${item.task_number})`;
        },
        checkImport(item, index) {
          this.lang_import.item = item;
          this.lang_import.index = index;
          this.lang_import.show = true;
        },
        langImport() {
          let that = this;
          let item = this.lang_import.item;

          $http.post('lang_pack_generator_import', {id: item.id}).then((res) => {
            if (parseInt(res.code) !== 200) {
              this.$message.error(res.message || '{{ __('LangPackGenerator::common.request_fail') }}');
            } else {
              this.$message.success(res.message || '{{ __('LangPackGenerator::common.request_success') }}');
            }

            this.$forceUpdate();
          })
          this.lang_import.show = false;
        },
        changeToCode(v) {
          if (v === 'custom') {
            this.dialog.to_type = 'custom';
          } else {
            let name = '';
            this.support_languages.forEach(function (item, index) {
              //  'zh-CHT' => 'zh_hk',
              // 'zh-CHS' => 'zh_cn',
              let code = item.code;
              if(item.code === 'zh-CHT') {
                code = 'zh_hk';
              }
              if (item.code === 'zh-CHS'){
                code = 'zh_cn';
              }
              if (code === v) {
                name = item.zh;

              }
            })


            this.dialog.form.to_name = name;
            this.dialog.to_type = 'select';
          }
        },
        closeCustomersDialog(form) {
          this.dialog.show = false
        }
      }
    })
  </script>
@endpush
