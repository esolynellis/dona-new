@extends('admin::layouts.master')
@section('page-bottom-btns')
  <button type="button" class="w-min-100 btn btn-primary submit-form btn-lg"
          form="form-specials">{{ __('common.save') }}</button>
  <button class="btn btn-lg btn-default w-min-100 ms-3" onclick="bk.back()">{{ __('common.return') }}</button>
@endsection
@section('title', '语言包安装配置')
@php
  $servicePath  = $plugin->getPath() . '/Services';

  $serviceFiles = Plugin\LangPackGenerator\Libraries\File::dir_class_files($servicePath);
  $platforms    = \Plugin\LangPackGenerator\Logic\LangPackGeneratorLogic::getServiceList();

   $config  = [];
   $columns = \Beike\Repositories\SettingRepo::getPluginColumns('lang_pack_generator');

   foreach ($columns as $item) {
       $config[$item['name']] = $item->toArray();
   }

   $configPlatForm = $config['platform']['value'] ?? [];
   if (is_array($configPlatForm)){

   }else if ($r = json_decode($configPlatForm, true)){
       $configPlatForm = $r;
   }else if (is_string($configPlatForm)) {
       $configPlatForm = explode(',', $configPlatForm);
   }

   $platformsStatus = $config['platform_status']['value']??'{}';
   $platformsStatus = is_array($platformsStatus)?:json_decode($platformsStatus, true);

   $filterPlatform = $config['filter_platform']['value']??'{"autoModel":1}';
   $filterPlatform = is_array($filterPlatform)?:json_decode($filterPlatform, true);
   $config =   [
         'sleep'            => $config['sleep']['value'] ?? '',
         'app_id'           => $config['app_id']['value'] ?? '',
         'app_secret'       => $config['app_secret']['value'] ?? '',
         'baidu_app_id'     => $config['baidu_app_id']['value'] ?? '',
         'baidu_app_secret' => $config['baidu_app_secret']['value'] ?? '',
         'platform'         => $configPlatForm,
         'master_platform'  => $config['master_platform']['value']??'',
         'platform_status' => $platformsStatus,
         'filter_platform' => $filterPlatform,
         'deepseek_app_key' => $config['deepseek_app_key']['value'] ?? '',
   ];

   // 获取配置安装
   $installFilesPath = $plugin->getPath().'/Install/config.php';
   $installConfig = include_once $installFilesPath;
   $installStatus = \Plugin\LangPackGenerator\Logic\LangPackGeneratorLogic::checkInstalled();
   $version = config('beike.version');
   $installVersion = \Plugin\LangPackGenerator\Logic\LangPackGeneratorLogic::getVersion();
    // 是否安装了有道翻译
    $isInstalledYoudao = plugin('youdao')->getStatus();

@endphp
@section('content')

  <div id="tax-classes-app" class="card" v-cloak>
    @if($isInstalledYoudao)
    <el-alert
      class="mb-3"
      title="检测系统开启了有道翻译插件, 这可能会与《语言翻译助手》插件冲突, 建议关闭有道翻译插件"
      type="warning"
      show-icon>
    </el-alert>
    @endif
    <el-alert

      title="安装完成后请前往:系统->语言包生成器 中进行语言包生成"
      type="info"
      show-icon>
    </el-alert>


      <form id="formData" class="needs-validation" novalidate
            action="{{ admin_route('plugins.update', [$plugin->code]) }}"
            method="POST">
    <div class="card-body h-min-600">
      <el-tabs v-model="configTab"  >
        <el-tab-pane label="配置管理" name="config">

            @csrf
            {{ method_field('put') }}
            @if (session('success'))
              <x-admin-alert type="success" msg="{{ session('success') }}" class="mt-4"/>
            @endif
            <div class="row g-3 mb-3 ">

              <label class="wp-200 col-form-label text-end  ">
                <el-badge value="新" class="item">
                  {{ __('LangPackGenerator::common.install') }}
                </el-badge>
              </label>

              <div class="col-auto wp-200-">
                <div style="margin-left: 5px">
                  @if($installStatus == 1)
                    <span class="text-success">
              <i class="el-icon-success"></i>已安装:自动
              </span>
                    <a @click="unInstall" href="javascript:;" class="text-danger"> 卸载</a>
                  @elseif($installStatus == 2)
                    <span class="text-warning">
              <i class="el-icon-success"></i>已安装:手动
              </span>
                  @elseif($installStatus == 3)
                    <span class="text-warning">
              <i class="el-icon-warning"></i>已安装:检测到注入文件已被修改, 部分翻译功能可能会受影响, 如无法使用,请
              </span>
                    <a @click="openInstall" href="javascript:;" class="text-primary"> 重新安装</a>
                  @else
                    <span class="text-danger">
              <i class="el-icon-remove"></i>未安装
            </span>
                    <a @click="openInstall" href="javascript:;" class="text-success"> 点击安装</a>
                  @endif
                </div>

                <div style="margin-left: 5px"
                     class="help-text font-size-12 lh-base">  {{ __('LangPackGenerator::common.install_help') }}  </div>
              </div>
            </div>

            {{--        <x-admin::form.row  title="{{ __('LangPackGenerator::common.master_platform') }}" class="mb-3">--}}

            {{--          <select  class="form-control" name="master_platform"   style="width: 100%" placeholder="请选择">--}}
            {{--            @foreach($platforms as $item)--}}
            {{--              <option   value="{{ $item['id']  }}" @if($item['id'] ==  $config['master_platform']) selected @endif>{{ $item['label'] }}  </option>--}}
            {{--            @endforeach--}}

            {{--          </select>--}}

            {{--          <div--}}
            {{--            class="help-text font-size-12 lh-base">  {{ __('LangPackGenerator::common.config_master_platform_tips') }}  </div>--}}
            {{--        </x-admin::form.row>--}}

            <x-admin::form.row title="{{ __('LangPackGenerator::common.platform') }}" class="mb-3">

              {{--          <select  class="form-control" name="filter_platform[]" multiple style="width: 100%" placeholder="请选择">--}}
              {{--            @foreach($platforms as $item)--}}
              {{--              <option   value="{{ $item['id']  }}" @if($config['platform']) @if(in_array($item['id'], $config['platform'])) selected @endif @else selected @endif >{{ $item['label'] }}  </option>--}}
              {{--            @endforeach--}}

              {{--          </select>--}}
              {{--          <a href="" class="btn btn-primary text-right mb-2 mt-2" style="float: right">延迟测速</a>--}}
              <table class="table">
                <thead>
                <tr>

                  <th>线路</th>
                  <th>延迟</th>
                  <th>状态</th>
                </tr>
                </thead>
                <tbody>
                @foreach($platforms as $index => $item)
                  @if(ucfirst($item['id']) !== 'Official')
                    <tr>

                      <td title="{{ $item['id'] }}">
                        @if($item['is_new'])
                          <el-badge value="新" class="item">
                            {{ $item['label'] }}
                          </el-badge>
                        @else
                          {{ $item['label'] }}
                        @endif
                      </td>
                      <td>{{ (isset($config['platform_status'][ucfirst($item['id'])]['ms'])?$config['platform_status'][ucfirst($item['id'])]['ms'].'ms':'-')  }}</td>
                      <td>

                        <el-switch v-model="platform.{{ $item['id'] }}"
                                   name="filter_platform[{{ $item['id'] }}]" value="1"  active-value="1"
                                   inactive-value="0" @change="switchPlatformStatus{{ $item['id'] }}"/>
                        {{--                  <label  for="platform-{{ $item['id'] }}">--}}
                        {{--                    开启--}}
                        {{--                  </label>--}}
                        {{--                  <input   id="platform-{{ $item['id'] }}" type="checkbox"  />--}}
                      </td>
                    </tr>
                  @endif
                @endforeach
                </tbody>
              </table>
              <div
                class="help-text font-size-12 lh-base">  {{ __('LangPackGenerator::common.config_platform_tips') }}  </div>
            </x-admin::form.row>

            <x-admin::form.row title="{{ __('LangPackGenerator::common.frequency') }}" class="mb-3">

              <el-input v-model="config.sleep" name="sleep" type="number"
                        placeholder="{{ __('LangPackGenerator::common.frequency') }}"></el-input>
              <div class="help-text font-size-12 lh-base">
                {{ __('LangPackGenerator::common.frequency_tips') }}
              </div>
            </x-admin::form.row>

            <div v-if="parseInt(platform.youdao) !== 0 || parseInt(platform.autoModel) !== 0">
              <x-admin::form.row title="{{ __('LangPackGenerator::common.config_youdao') }}">
              </x-admin::form.row>
              <x-admin::form.row title="{{ __('LangPackGenerator::common.appId') }}" class="mb-3">
                <el-input v-model="config.app_id" name="app_id"
                          placeholder="{{ __('LangPackGenerator::common.appId') }}"></el-input>
              </x-admin::form.row>


              <x-admin::form.row title="{{ __('LangPackGenerator::common.appSecret') }}" class="mb-3">
                <el-input v-model="config.app_secret" name="app_secret"
                          placeholder="{{ __('LangPackGenerator::common.appSecret') }}"></el-input>

                <div class="help-text font-size-12 lh-base">
                  {{ __('LangPackGenerator::common.config_youdao_docs') }}:<a
                    href="https://ai.youdao.com/DOCSIRMA/html/trans/api/wbfy/index.html"
                    target="_blank">{{ __('LangPackGenerator::common.look_click') }}</a>


                </div>
              </x-admin::form.row>
            </div>

            <div v-if="parseInt(platform.baidu) !== 0 || parseInt(platform.autoModel) !== 0">
              <x-admin::form.row title="{{ __('LangPackGenerator::common.config_baidu') }}">
              </x-admin::form.row>
              <x-admin::form.row title="{{ __('LangPackGenerator::common.appId') }}" class="mb-3">

                <el-input name="baidu_app_id" v-model="config.baidu_app_id"
                          placeholder="{{ __('LangPackGenerator::common.appId') }}"></el-input>


              </x-admin::form.row>

              <x-admin::form.row title="{{ __('LangPackGenerator::common.appSecret') }}" class="mb-3">
                <el-input name="baidu_app_secret" v-model="config.baidu_app_secret"
                          placeholder="{{ __('LangPackGenerator::common.appSecret') }}"></el-input>
                <div class="help-text font-size-12 lh-base">
                  {{ __('LangPackGenerator::common.config_baidu_docs') }}:<a
                    href="https://fanyi-api.baidu.com/doc/21"
                    target="_blank"> {{ __('LangPackGenerator::common.look_click') }}</a>
                </div>
              </x-admin::form.row>
            </div>

            <div v-if="parseInt(platform.deepSeek) !== 0 || parseInt(platform.autoModel) !== 0">
              <x-admin::form.row title="{{ __('LangPackGenerator::common.config_deepseek') }}">
              </x-admin::form.row>
              <x-admin::form.row title="{{ __('LangPackGenerator::common.appId') }}" class="mb-3">

                <el-input name="deepseek_app_key" v-model="config.deepseek_app_key"
                          placeholder="{{ __('LangPackGenerator::common.appId') }}"></el-input>


              </x-admin::form.row>


            </div>

            <x-admin::form.row title="">
              <button type="submit" style="display:none"
                      class="btn btn-primary btn-lg mt-4">{{ __('common.submit') }}</button>
            </x-admin::form.row>


        </el-tab-pane>
        <el-tab-pane label="语言支持列表" name="second">
          @php
            $serverList = \Plugin\LangPackGenerator\Logic\LangPackGeneratorLogic::getServiceList();
          @endphp

            <el-tabs tab-position="left" v-model="langCodeTab"  >
              @foreach($serverList as $serverItem)
                @if($serverItem['id'] !== 'official')
              <el-tab-pane label="{{ $serverItem['label']  }}" name="{{ $serverItem['id']  }}">
                <table class="table">
                  <thead>
                  <tr>
                    <th>编码</th>
                    <th>名称</th>
                  </tr>
                  </thead>
                  <tbody>
                  @foreach(\Plugin\LangPackGenerator\Libraries\LangPackGenerator::getLangCodeList(strtolower($serverItem['id'])) as $langCode => $codeName)
                    <tr>
                      <td> {{ $langCode  }}</td>
                      <td> {{ $codeName  }} </td>
                    </tr>
                  </tbody>
                  @endforeach
                </table>
              </el-tab-pane>
                @endif
              @endforeach

            </el-tabs>

        </el-tab-pane>
        <el-tab-pane label="语言映射" name="lang_conversion">
          <el-alert
            class="mb-3"
            title="该功能用于将各平台语言编码同步系统语言编码。例如，内置演示线路2的日语编码是 ja， 而【系统语言编码】日语编码是 jp，因此在生成或翻译使用 jp 编码会导致【内置演示线路2】无法识别编码导致翻译异常。遇到这种情况可添加一个映射将【内置演示线路2】的编码 jp 映射到 【系统语言编码】ja"
            type="warning"
            show-icon>
          </el-alert>
          @php
            $serverList = \Plugin\LangPackGenerator\Logic\LangPackGeneratorLogic::getServiceList();
            foreach ($serverList as $k => $serverItem){
              if ($serverItem['id'] == 'autoModel'){
                unset($serverList[$k]);
              }
            }
          @endphp
          <input name="lang_conversion_list" type="hidden"/>
          <table class="table">
            <thead>
            <tr>
              <th>系统语言编码</th>
              <th>系统语言名称</th>
              @foreach($serverList as $serverItem)
                <th>{{ $serverItem['label']  }}</th>
              @endforeach
              <th>操作</th>
            </tr>
            </thead>
            <tbody>

              <tr v-for="(langCode,index) in lang_conversion_list" :key="index">
                <td>
                  <el-autocomplete
                    popper-class="my-autocomplete"
                    class="inline-input"
                    v-model="langCode.master"
                    :fetch-suggestions="handleSuggestionsMaster"
                    placeholder="请输入内容"
                    @select="(item) => selectSuggestions(item, index)"
                    :name="`lang_conversion_list[${index}][master]`"
                  >
                    <template slot-scope="{ item }">
                      <div class="name">@{{ item.value }}</div>
                      <span class="addr">@{{ item.name }}</span>
                    </template>
                  </el-autocomplete>
                </td>
                <td>
                    <el-input  v-model="langCode.name"  :name="`lang_conversion_list[${index}][name]`" placeholder="请输入语言名称"></el-input>
                </td>
                @foreach($serverList as $serverItem)

                  <td>
                    <el-autocomplete
                      popper-class="my-autocomplete"
                      class="inline-input "
                      v-model="langCode.{{$serverItem['id']}}"
                      :fetch-suggestions="handleSuggestions{{$serverItem['id']}}"
                      placeholder="请输入内容"
                      :name="`lang_conversion_list[${index}][{{$serverItem['id']}}]`"
                    >
                      <template slot-scope="{ item }">
                        <div class="name">@{{ item.value }}</div>
                        <span class="addr">@{{ item.name }}</span>
                      </template>
                    </el-autocomplete>
                  </td>
                @endforeach
                <td>
                  <el-button type="danger" @click="lang_conversion_list.splice(index, 1);" size="mini">移除</el-button>
                </td>
              </tr>
            </tbody>

          </table>
          <div style="text-align: center;margin: 10px"><el-button @click="addLangConversionItem" type="primary"  size="mini">+添加映射</el-button></div>

        </el-tab-pane>

      </el-tabs>

    </div>
      </form>
    <el-dialog
      title="安装翻译增强功能程序"
      :visible.sync="installVisible"
      width="60%"
    >
      <el-tabs v-model="activeName">
        <el-tab-pane label="自动安装" name="auto">

          <el-alert
            title="是否确认安装翻译增强功能？"
            type="warning"
            show-icon
            description=" 安装翻译增强将会更改以下文件, 请确保对应页面文件没有被修改, 如果您未修改操作过以下文件可放心安装.  如已自行更改过以下文件或未知请先备份或查看手动安装教程">
          </el-alert>
          <div class="mt-3 mb-3">
            <ul>
              @foreach($installConfig['install_files'] as $item)
                <li class="mb-2">
                  <div>{{ $item['name']  }}</div>
                  <div class="text-secondary">{{ $item['path']  }}</div>
                </li>
              @endforeach
            </ul>
          </div>

        </el-tab-pane>
        <el-tab-pane label="手动安装" name="manual">
          <el-alert
            title="手动安装"
            type="warning"
            show-icon
            description="你可以按需进行手动安装, 如你需要商品多规格sku支持翻译, 那么你可只需要在 商品表单页面中加入对应的代码即可, 以下是支持增强的代码列表">
          </el-alert>

          <div class="mt-3 mb-3">
            <el-collapse v-model="activeNames">
              @foreach($installConfig['install_files'] as $k => $item)
                <el-collapse-item title="{{ $k+1  }}.翻译增强:{{ $item['name']  }}" name="{{ $k+1  }}">
                  @foreach($item['line'][$installVersion] as $lineIndex =>  $line)
                    <div class="mb-3">
                      <p>{{ $lineIndex+1  }} 找到打开 <b>[{{ $item['name']  }}]</b> 文件,在文件
                        <span class="text-primary">{{ $item['path']  }}</span> 中第 <b
                          class="text-primary">{{ $line }}</b> 中添加以下代码:</p>
                      <div>
                                                <span class="text-secondary"
                                                      style="user-select:none;">{{ $line }}</span>
                        <el-divider direction="vertical"></el-divider>

                        @if(intval($item['code']) === 1)
                          <code>@php echo "@hook('".$item['hook']."')";  @endphp</code>
                        @else
                          <code>@php echo "@hook('".$item['hook']."')";  @endphp</code>
                        @endif

                      </div>
                    </div>
                  @endforeach
                </el-collapse-item>
              @endforeach
            </el-collapse>

          </div>
        </el-tab-pane>

      </el-tabs>

      <span slot="footer" class="dialog-footer">
      <el-button @click="installVisible = false">取 消</el-button>
      <el-button type="primary" @click="install">确 定 安 装</el-button>
    </span>
    </el-dialog>
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
    $('.submit-form').on('click', function () {

      let form = $(`form#formData`);
      const action = form.attr('action');
      // form.attr('action', bk.updateQueryStringParameter(action, 'action_type', 'stay'));
      form.find('button[type="submit"]')[0].click();
    })

    new Vue({
      el: '#tax-classes-app',

      data: {
        configTab:'config',
        activeName: 'auto',
        platform: {
          @foreach($platforms as $index => $item)
            @if($config['filter_platform'])
              {{$item['id']}}: @if(in_array($item['id'], array_keys($config['filter_platform']??[]))) '1'  @else '0' @endif,
            @else
              {{$item['id']}}: '0',
            @endif
          @endforeach
        },
        langCodeTab:'{{ $serverList[0]['id']??''  }}',
        open_platform:@json($platforms ?? []),
        installVisible: false,
        activeNames: [1],
        config: {
          show: false,
          sleep: '{{ $config['sleep']?:1  }}',
          open_platform: @json($config['platform'] ?? []),
          app_secret: '{{ $config['app_secret']  }}',
          app_id: '{{ $config['app_id']  }}',
          baidu_app_id: '{{ $config['baidu_app_id']  }}',
          baidu_app_secret: '{{ $config['baidu_app_secret']  }}',
          deepseek_app_key:'{{ $config['deepseek_app_key']  }}',
        },
        @php
          $lang_conversion_list = plugin_setting('lang_pack_generator.lang_conversion_list', []);
          if (!$lang_conversion_list){
            $lang_conversion_list = [];
          }
         @endphp
        lang_conversion_list: @json($lang_conversion_list),
        rules: {}
      },
      mounted: function () {

      },
      methods: {
        @foreach($platforms as $index => $item)
        switchPlatformStatus{{ $item['id'] }}(value){
          if (parseInt(value)>0){
            @foreach($platforms as $index => $item1)
              this.platform.{{ $item1['id'] }} = '0';
            @endforeach
          }
          this.platform.{{ $item['id'] }} = value;

        },
        selectSuggestions( item,index){
          this.lang_conversion_list[index].name = item.name;
          console.log(item,index,this.lang_conversion_list[index]);
        },
        handleSuggestions{{ $item['id'] }}(queryString, cb){
          @php
            $codes = [];
            $codeList = \Plugin\LangPackGenerator\Libraries\LangPackGenerator::getLangCodeList(strtolower($item['id']))
          @endphp
          @foreach($codeList as $code => $name)
            @php
            $codes[] = ['value' => $code, 'name' => $name];
            @endphp
          @endforeach
          var restaurants = @json($codes);
          var results = queryString ? restaurants.filter(this.createFilter(queryString)) : restaurants;
          // 调用 callback 返回建议列表的数据
          cb(results);
        },
        @endforeach
        handleSuggestionsMaster(queryString, cb){
          @php
            $codes = [];
            $codeList = \Plugin\LangPackGenerator\Libraries\LangPackGenerator::getSystemLangCodeList();
            $sysCodeList = \Beike\Admin\Services\LanguageService::all();

          @endphp
          @foreach($codeList as $code => $name)
          @php
            $codes[] = ['value' => $code, 'name' => $name];
          @endphp
          @endforeach
          @foreach($sysCodeList as $sysCodeKey => $sysCodeItem)
          @php
            $codes[] = ['value' => $sysCodeItem['code'], 'name' => $sysCodeItem['name']];
          @endphp
          @endforeach
          var restaurants = @json($codes);
          var results = queryString ? restaurants.filter(this.createFilter(queryString)) : restaurants;
          // 调用 callback 返回建议列表的数据
          cb(results);
        },
        createFilter(queryString) {
          return (restaurant) => {
            return (restaurant.value.toLowerCase().indexOf(queryString.toLowerCase()) === 0);
          };
        },
        addLangConversionItem(){
          this.lang_conversion_list.push({
            master: '',
            name:'',
            @foreach($platforms as $index => $item)
              {{$item['id']}}: '',
            @endforeach
          })
        },
        install(){
          let that = this;
          $http.post('lang_pack_generator_install_pro', this.config, {
            'hload': false,
            'hmsg': false
          }).then((res) => {

            this.$alert(res.message, '安装增强翻译功能', {
              confirmButtonText: '确定',
              callback: action => {
                location.reload()
              }
            });

          })
        },
        unInstall(){
          let that = this;
          this.$confirm('是否确认卸载增强功能?, 卸载后将还原会安装前的文件', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
          }).then(() => {
            $http.post('lang_pack_generator_uninstall_pro', this.config, {
              'hload': false,
              'hmsg': false
            }).then((res) => {
              this.$alert(res.message, '卸载增强翻译功能', {
                confirmButtonText: '确定',
                callback: action => {
                  location.reload()
                }
              });
            })
          }).catch(() => {
            this.$message({
              type: 'info',
              message: '已取消删除'
            });
          });

        },
        openInstall() {
          this.installVisible = true;
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

      }
    })
  </script>
  <style>
    .my-autocomplete {
      li {
        line-height: normal;
        padding: 7px;

        .name {
          text-overflow: ellipsis;
          overflow: hidden;
        }
        .addr {
          font-size: 12px;
          color: #b4b4b4;
        }

        .highlighted .addr {
          color: #ddd;
        }
      }
    }
  </style>
@endpush
