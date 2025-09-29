<el-form-item label="{{ __('RegisterCaptcha::login.code') }}" class="is-required" prop="code">
  <div class="el-input">
    <el-row>
      <el-col :span="16">
        <el-input v-model="registerForm.code"
                  placeholder="{{ __('RegisterCaptcha::login.code') }}"></el-input>
      </el-col>
      <el-col :span="8">
        <el-button type="primary" style="margin-left: 10px;margin-right: 20px" @click="sendRegCode" id="sendRegCode"
                   :loading="registerForm.getCodeLoading" id="sendRegEmailCode">{{ __('RegisterCaptcha::login.code_btn') }}
        </el-button>
      </el-col>
    </el-row>
  </div>
</el-form-item>


@push("add-scripts")
  @if(!empty($js))
    {!! $js !!}
  @endif
  <script>
    $(function () {
      app.$set(app.registerForm, "getCodeLoading", false);
      app.$set(app.registerForm, "code", "");
      @if($captcha_type == 1)
      app.$set(app.registerForm, "lot_number", "")
      app.$set(app.registerForm, "captcha_output", "")
      app.$set(app.registerForm, "pass_token", "")
      app.$set(app.registerForm, "gen_time", "")
      @endif
      @if($captcha_type == 2)
      app.$set(app.registerForm, "ticket", "")
      app.$set(app.registerForm, "randstr", "")
      @endif
    })

  </script>

  <script>
    @if($captcha_type == 2 && !empty($js))

    function regCallback(res) {
      console.log('callback:', res);
      if (res.ret === 0) {
        app.registerForm.ticket = res.ticket;
        app.registerForm.randstr = res.randstr;
        //验证是否通过
        app.postRegSend({ticket: res.ticket, randstr: res.randstr});

      }
    }

    // 定义验证码js加载错误处理函数
    function loadErrorCallback() {
      var appid = '{{$captcha_id}}'
      // 生成容灾票据或自行做其它处理
      var ticket = 'terror_1001_' + appid + Math.floor(new Date().getTime() / 1000);
      regCallback({
        ret: 0,
        randstr: '@' + Math.random().toString(36).substr(2),
        ticket: ticket,
        errorCode: 1001,
        errorMessage: 'jsload_error'
      });
    }


    @endif

  </script>

@endpush
@push('login.vue.method')
  sendRegCode(){
  this.$refs["registerForm"].validateField('email', (val) => {
  if (!val) {
  this.startGetRegCode()
  return;
  } else {
  return;
  }
  })
  },
  startGetRegCode(){
  let that = this;
  this.registerForm.getCodeLoading = true;
  @if($captcha_type == 1)
    initGeetest4({
        captchaId: '{{$captcha_id}}',
        product: 'bind'
        }, function (captchaObj) {
            // captcha为验证码实例
            captchaObj.onReady(function () {
            captchaObj.showCaptcha(); //显示验证码
        }).onSuccess(function () {
            //your code,结合您的业务逻辑重置验证码
            var result = captchaObj.getValidate();
            console.log(result);
            that.postRegSend(result);
            //captchaObj.reset()
        }).onError(function () {
            //your code
            $("#sendCode").attr("disabled", false);
            that.registerForm.getCodeLoading = false;
        })
    });

  @endif

  @if($captcha_type == 2)

    // 定义验证码触发事件
    var captcha = new TencentCaptcha('{{$captcha_id}}', regCallback, {userLanguage:"{{locale()}}"});
    // 调用方法，显示验证码
    captcha.show();

  @endif

  @if($captcha_type == 0)

    that.postRegSend({});

  @endif
  },
  postRegSend(result){
      result.email = this.registerForm.email
  //发送验证码
  $http.post(`/register/captcha`, result).then((res) => {
  //console.log(res);
  if (res.code == 0) {
  layer.msg("{{__('RegisterCaptcha::login.send_success')}}");

  //开始倒计时
  let time = 60;
  $("#sendRegCode").html(time + "s");
  interVal = setInterval(function () {
  time = time - 1;
  $("#sendRegCode").html(time + "s");
  console.log(time);
  if (time <= 0) {
  clearInterval(interVal);
  app.registerForm.getCodeLoading = false;
  $("#sendRegCode").html("{{ __('RegisterCaptcha::login.code_btn') }}");
  $("#sendRegCode").attr("disabled", false);
  }
  }, 1000);


  } else {
  if (interVal != null) {
  clearInterval(interVal);
  }
  this.registerForm.getCodeLoading = false;
  $("#sendRegCode").html("{{ __('RegisterCaptcha::login.code_btn') }}");
  $("#sendRegCode").attr("disabled", false);
  layer.msg(res.msg)
  }
  })
  }
@endpush



