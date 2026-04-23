<!DOCTYPE html>
<html lang="{{ admin_locale() }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
  <base href="{{ $admin_base_url }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="asset" content="{{ asset('/') }}">
  <meta name="editor_language" content="{{ locale() }}">
  <script src="{{ asset('vendor/vue/2.7/vue' . (!config('app.debug') ? '.min' : '') . '.js') }}"></script>
  <script src="{{ asset('vendor/element-ui/index.js') }}"></script>
  <script src="{{ asset('vendor/jquery/jquery-3.6.0.min.js') }}"></script>
  <script src="{{ asset('vendor/layer/3.5.1/layer.js') }}"></script>
  <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('vendor/cookie/js.cookie.min.js') }}"></script>
  <link href="{{ mix('/build/beike/admin/css/bootstrap.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('vendor/element-ui/index.css') }}">

  <link href="{{ mix('build/beike/admin/css/app.css') }}" rel="stylesheet">

  @stack('header')
  {{-- <x-analytics /> --}}
</head>

@include('LangPackGenerator::components.auto-translation', ['type' => $component])
<script>
  let locales = @json(locales());
  $(document).on('click','.translate-btn',function () {
    $(this).attr('disabled', true)
    $(this).html('<i class="el-icon-loading"></i>')
    translateSkus($(this));
  });
  function translateSkus(obj){

    let _obj = obj;
    var from = obj.siblings('.from-locale-code').val();
    var to = obj.siblings('.to-locale-code').val();
    let $parents = $(window.frameElement).siblings('.el-form-item');
    obj.siblings('.from-locale-code').attr('disabled', true);
    obj.siblings('.to-locale-code').attr('disabled', true);

    let text = $parents.find('.el-input__inner').val();
    let length = $parents.find('.el-input__inner').length;
    // console.log($('.langIframe', window.parent.document).html(), langName,langValue,$parents.find('.el-input-group__prepend').length )
    // 获取from的输入框key
    let fromIndex = '';
    let fromText = obj.siblings('.from-locale-code').find("option:selected").text();

    for ( i=0; i<length; i++){
      let langName = $parents.find('.el-input-group__prepend').eq(i).text();
      if (fromText === langName){
        text = $parents.find('.el-input__inner').eq(i).val();
        fromIndex = i;
      }
    }
    // 获取to的输入框key

    let toIndex = '';
    let toText = obj.siblings('.to-locale-code').find("option:selected").text();
    for ( i=0; i<length; i++){
      let langName = $parents.find('.el-input-group__prepend').eq(i).text();
      // console.log("toText",toText, "toIndex",toIndex,"langName",langName, "获取to的输入框key")
      if (toText === langName){
        toText = $parents.find('.el-input-group__prepend').eq(i).text();
        toIndex = i;
      }
    }



    if (!text) {
      return parent.layer.msg('请输入翻译内容:'+fromText, () => {});
    }
    const token = document.querySelector('meta[name="csrf-token"]').content;
    parent.layer.msg('正在翻译:'+text, {
      shade: 0.01
    });
    // // 发请求之前删除所有错样式
    $.ajax({
      url: '{{ admin_route('translation.translate') }}',
      type: 'POST',
      timeout:600000, //设置超时的时间10s
      headers: {'X-CSRF-TOKEN': token},
      data: {from, to, text},
      success: function (res) {
        // 获取所有元素
        for (  i=0; i<length; i++){
          // 排除from的输入框
          if (parseInt(fromIndex) !== parseInt(i)) {
            // console.log("i=",i);
            // 指定翻译文本进入此
            if (toIndex !== ''){
              // console.log('toIndex=', toIndex,"指定了翻译文本",res.data[0].result);
              $parents.find('.el-input__inner').eq(parseInt(toIndex)).val(res.data[0].result);
            }else{
              for(  j = 0; j<locales.length; j++) {
                let langCode  = '';
                let langName = $parents.find('.el-input-group__prepend').eq(i).text();
                // console.log("page=",langName,"===", "locales=",locales[j].name)
                // 如果名字相等, 取code进行匹配对应输入框
                if(langName === locales[j].name){
                  langCode = locales[j].code;
                  // 遍历匹配返回内容
                  res.data.forEach((e) => {

                    // 匹配成功写入输入框
                    if (langCode === e.locale) {
                      let obj = $parents.find('input.el-input__inner').eq(i)
                      obj.val(e.result);
                      obj[0].dispatchEvent(new Event('input'));
                      obj.focus()
                      obj.blur();
                    }
                  });
                }

              }
            }

          }
        }
        _obj.html('翻译')
        _obj.attr('disabled', false)
        obj.siblings('.from-locale-code').attr('disabled', false);
        obj.siblings('.to-locale-code').attr('disabled', false);
        parent.layer.msg('翻译完成' );
      }
    })

  }
  function translateDefault(obj){
    var from = obj.siblings('.from-locale-code').val();
    var to = obj.siblings('.to-locale-code').val();
    let $parents = obj.parents('.input-locale-wrap').length ? obj.parents('.input-locale-wrap') : obj.parents('.col-auto');
    var text = $parents.find('.input-' + from).val();
    if (!text) {
      return layer.msg(lang.translate_form, () => {});
    }

    // 发请求之前删除所有错样式
    $http.post('translation', {from, to, text}).then((res) => {
      $('.translation-error-text').remove()

      res.data.forEach((e) => {
        $parents.find('.input-' + e.locale).removeClass('translation-error');

        if (e.error) {
          $parents.find('.input-' + e.locale).addClass('translation-error');
          $parents.find('.input-' + e.locale).parents('.input-for-group').after('<div class="invalid-feedback translation-error-text mb-1 d-block" style="margin-left: 86px">' + e.error + '</div>');
        } else {
          $parents.find('.input-' + e.locale).val(e.result);
        }
      });
    })
  }

</script>

<style>
  html,body{
    background: none;
    height: 60px;
  }
</style>
