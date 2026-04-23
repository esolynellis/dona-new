## BeikeShop 前端接口

### 安装说明
1. 官方demo或者本地开发可以直接克隆git仓库到 /beike 目录下, `git clone git@guangdagit.com:beike/ShopAPI.git`
2. 如果给客户部署, 请下载 zip 压缩包并解压到客户网站 /beike 目录下，注意保持目录名为 `ShopAPI`
3. 打开网站根目录，执行命令 `php artisan jwt:secret`, 生成 secret
4. 访问 `/api/home`, 比如 `https://beike.gdemo.top/api/home` 可测试API是否部署成功
