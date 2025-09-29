<!-- 添加音频元素用于播放提醒声音 -->
<audio id="alert-sound">
  <source src="{{ plugin_asset('Commission','sounds/notification.mp3') }}" type="audio/mpeg">
  您的浏览器不支持 audio 标签。
</audio>

@push('footer')
  <script>
    let log_id = 0;
    document.addEventListener('DOMContentLoaded', function () {
      const alertSound = document.getElementById('alert-sound');
      let isChecking = false;

      // 定时检测函数
      function checkForAlerts() {
        if (isChecking) return;
        isChecking = true;

        $http.get("{{ admin_route("cash_apply_new_alert") }}",{'log_id':log_id},{hload:true}).then((res) => {
          if (res.data.need_alert) {
            // 播放提醒声音
            alertSound.currentTime = 0;  // 重置到开始位置
            alertSound.volume = 0.5;     // 设置适中音量
            alertSound.play().catch(e => {
              console.error('播放失败:', e);
              layer.msg('请允许网站播放声音');
            });

            // 显示通知
            if (res.msg) {
              layer.msg(res.msg);
            }
            log_id = res.data.log_id;
          }
        })
        .catch(error => {
          console.error('检测失败:', error);
        })
        .finally(() => {
          isChecking = false;
        });
      }

      // 每5秒检测一次
      setInterval(checkForAlerts, 5000);

      // 初始检测
      checkForAlerts();
    });
  </script>
@endpush
