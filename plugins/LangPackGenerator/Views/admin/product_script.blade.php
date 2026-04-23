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
  <script src="{{ asset('vendor/tinymce/5.9.1/tinymce.min.js') }}"></script>
  @stack('header')
  {{-- <x-analytics /> --}}
</head>

@include('LangPackGenerator::components.auto-translation', ['type' => $component])

<script>

  window.addEventListener('message', function (event) {
    switch (event.data.type) {

      case 'GET_TINYMCE_CALLBACK':
        let tmp_object = $('.temp-editor');
        tmp_object.html(event.data.content);
        tmp_object.attr('id', event.data.tinyID)
        // console.log("接受文本内容：",event.data)
        let transBtn = $('.translate-btn');
        transBtn.attr('disabled', true);
        transBtn.html('<i class="el-icon-loading"></i>');
        translatePage(transBtn, event.data.content);
        // tinymce.setContent(event.data.content);
        break;
    }
  }, false);

  let locales = @json(locales());
  $(document).on('click', '.translate-btn', function () {
    $(this).attr('disabled', true);
    $(this).html('<i class="el-icon-loading"></i>');

    translatePage($(this));
  });
  function sleep(millisecond) {
    return new Promise(resolve => {
      setTimeout(() => {
        resolve()
      }, millisecond)
    })
  }

  async function translatePage(obj, isEditoCallback=false) {
    let _obj = obj;
    var from = obj.siblings('.from-locale-code').val();
    var to = obj.siblings('.to-locale-code').val();
    obj.siblings('.from-locale-code').attr('disabled', true);
    obj.siblings('.to-locale-code').attr('disabled', true);


    // 获取字段
    let fields = [
      {'name': '商品详情', 'value': 'content'}
    ];


    for (let f = 0; f < fields.length; f++) {
      let isRun = false;
      let field = fields[f].value;

      let $parents = $(window.frameElement).parent().parent().parent().parent();

      // let tabId = $parents.find('div[role="tabpanel"].active').attr('id');
      var tabId = $parents.find('div[role="tabpanel"].active').map(function() {
        // 使用正则表达式匹配 tab- 后面的文本
        var match = this.getAttribute('id').match(/tab-descriptions-(.*)/);
        return match ? match[1] : null;
      }).get();
      // from = tabId[0];
      // console.log(tabId, "tabId")
      // return ;
      // let tabName = $parents.find('.col-form-label').eq(0).text();
      // console.log('input[name="descriptions[' + from + '][' + field + ']"]')
      let text = $parents.find('input[name="descriptions[' + from + '][' + field + ']"]').val();
      // console.log( 'input[name="descriptions[' + from + '][' + field + ']"]')
      let fromText = obj.siblings('.from-locale-code').find("option:selected").text();
      if (!text) {

        text = $parents.find('textarea[name="descriptions[' + from + '][' + field + ']"]').val();
        if (!text) {
          parent.layer.msg('警告:翻译'+fromText+'【'+fields[f].name+'】'+(f+1)+'/'+fields.length+', 该文本为空, 自动过滤' , {
            shade: 0.01
          });
        }
        await sleep(1000);
      }


      if (text) {
        parent.layer.msg('正在翻译:'+fromText+'【'+fields[f].name+'】'+(f+1)+'/'+fields.length, {
          shade: 0.01,
          time: 60000
        }, function(){

        });
        try{
          let type = 'text';
          if (field === 'content'){
            type = 'editor'
          }
          if(type==='editor'){
            if(isEditoCallback===false){
              let editorObject = $parents.find('textarea[name="descriptions[' + from + '][' + field + ']"]');
              window.parent.postMessage({ type: 'GET_TINYMCE_CONTENT',   tinyID:editorObject.attr('id') }, '*');
              return;
            }else{
              text = isEditoCallback;
            }

          }
          sendTranslate($parents, _obj, {from, to, text, type}, field)

          await sleep(2000);
        } catch (e) {
          parent.layer.msg('网络繁忙:'+e);
        }


      }else{
        parent.layer.msg('没有内容', {
          shade: 0.01,
        }, function(){
          _obj.siblings('.from-locale-code').attr('disabled', false);
          _obj.siblings('.to-locale-code').attr('disabled', false);
          _obj.html('翻译')
          _obj.attr('disabled', false)
        });
      }

    }

  }

  function sendTranslate($parents,_obj, data, field){
    const token = document.querySelector('meta[name="csrf-token"]').content;

    $.ajax({
      url: '{{ admin_route('translation.translate') }}',
      type: 'POST',
      timeout:600000, //设置超时的时间10s
      headers: {'X-CSRF-TOKEN': token},
      data: data,
      success: function (res) {
        $('.temp-editor').html('');
        // console.log("translation result=", res)
        parent.layer.closeAll();
        if(res.status === "fail"){
          parent.layer.msg(res.message );
        }else{
          let isError = res.data[0]?.error || '';
          if (isError) {
            parent.layer.alert(isError, {title:'语言翻译助手', icon:2});
          }else{

            // $parents.find('.tinymce')[0].innerHTML = 'hello';
            // console.log($parents.find('.tinymce')[0],"tinymce")
            res.data.forEach((e) => {

              let obj = $parents.find('input[name="descriptions[' + e.locale + '][' + field + ']"]');
              let objTextarea = $parents.find('textarea[name="descriptions[' + e.locale + '][' + field + ']"]');

              let tinyID = objTextarea.attr('id');
              // console.log("tinyID=>",tinyID);
              // console.log("objTextarea=>", 'textarea[name="descriptions[' + e.locale + '][' + field + ']"]');
              window.parent.postMessage({ type: 'UPDATE_TINYMCE', content: e.result, tinyID:tinyID }, '*');

              obj[0]?.dispatchEvent(new Event('input'));
              objTextarea[0]?.dispatchEvent(new Event('textarea'));
              obj.focus()
              obj.blur();
              objTextarea.focus()
              objTextarea.blur();

            });
            parent.layer.msg('翻译完成' );
          }

        }
        _obj.siblings('.from-locale-code').attr('disabled', false);
        _obj.siblings('.to-locale-code').attr('disabled', false);
        _obj.html('翻译')
        _obj.attr('disabled', false)

      },error: function (e) {
        parent.layer.closeAll();
        let res = e.responseJSON;
        // console.log(e)
        _obj.siblings('.from-locale-code').attr('disabled', false);
        _obj.siblings('.to-locale-code').attr('disabled', false);
        _obj.html('翻译')
        _obj.attr('disabled', false)
        parent.layer.alert('翻译请求错误:'+res.message, {title:'语言翻译助手', icon:2});
      }
    })
  }


</script>

<style>
  html, body {
    background: none;
    height: 60px;
  }
</style>
