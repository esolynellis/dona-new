# Auto-Deploy тохируулга (1 удаагийн setup)

GitHub руу `git push` хийх болгонд **сервер автоматаар шинэчлэгдэх** болно.

## Хэрхэн ажиллах вэ

```
Локал → git push → GitHub → webhook → сервер → git pull + cache rebuild
```

Push-ээс хойш ~5-10 секундийн дотор сайт шинэчлэгдэнэ.

---

## Setup алхамууд

### 1. Кодыг GitHub руу push хийх

Энэ файл, `public/deploy-hook.php`, `bin/auto-deploy.sh`, шинэ `.gitignore`-г GitHub руу push хийнэ:

```bash
git add .
git commit -m "Add auto-deploy webhook"
git push origin master
```

### 2. Серверт өөрчлөлт татах

BT Panel terminal эсвэл SSH-ээр сервер рүү ороод:

```bash
cd /www/wwwroot/dona-new
git pull origin master
```

### 3. Setup команд ажиллуулах (нэг удаа)

Дараах командыг бүхэлд нь хуулж сервер дээр ажиллуулна:

```bash
cd /www/wwwroot/dona-new && \
  openssl rand -hex 32 > .deploy-hook-secret && \
  chmod 600 .deploy-hook-secret && \
  chmod +x bin/auto-deploy.sh && \
  chown www:www .deploy-hook-secret bin/auto-deploy.sh && \
  git config --global --add safe.directory /www/wwwroot/dona-new && \
  chown -R www:www .git && \
  echo "" && \
  echo "==== SECRET (copy this for GitHub) ====" && \
  cat .deploy-hook-secret && \
  echo "=======================================" && \
  echo "" && \
  echo "Webhook URL: https://dona-trade.com/deploy-hook.php"
```

Энэ команд:
- 32-байт санамсаргүй secret үүсгэнэ
- Файлын эрх, эзэмшилийг тохируулна
- `.git` хавтасыг `www` хэрэглэгчийн эзэмшил болгоно (PHP-ээс git pull хийхэд хэрэгтэй)
- Secret-ийг хэвлэнэ — энийг GitHub дээр оруулна

### 4. Webhook health check

Browser-аар нээж шалгана:
```
https://dona-trade.com/deploy-hook.php
```

Үр дүн нь:
```
deploy-hook.php is alive
secret file: configured
```

### 5. GitHub дээр webhook нэмэх

1. GitHub repo → **Settings** → **Webhooks** → **Add webhook**
2. Талбаруудыг бөглөх:
   - **Payload URL:** `https://dona-trade.com/deploy-hook.php`
   - **Content type:** `application/json`
   - **Secret:** (3-р алхамд сервер дээр гарсан 64-тэмдэгттэй secret)
   - **Which events:** "Just the push event"
   - **Active:** ✓
3. **Add webhook** дарна

GitHub шууд "ping" event илгээж шалгана. "Recent Deliveries" хэсэгт ✓ ногоон тэмдэг харагдвал амжилттай.

### 6. Тестлэх

Локал дээр жижиг өөрчлөлт хийгээд push хийнэ:

```bash
echo "" >> README.md
git add README.md
git commit -m "test auto-deploy"
git push origin master
```

5-10 секунд хүлээгээд GitHub → Settings → Webhooks → Recent Deliveries-аас үр дүн харагдана.

Серверт лог:
```bash
tail -f /www/wwwroot/dona-new/storage/logs/auto-deploy.log
```

---

## Тэмдэглэл

### Хувийн (private) repo бол

GitHub-ийн HTTPS URL дээр Personal Access Token (PAT) шаардагдана. Эсвэл SSH deploy key тохируулна. Нийтийн (public) repo бол ийм зүйл хэрэггүй.

PAT арга:
```bash
cd /www/wwwroot/dona-new
git remote set-url origin https://USERNAME:ghp_TOKEN@github.com/USERNAME/REPO.git
```

### Webhook идэвхгүй болгох

GitHub → Settings → Webhooks → таны webhook → **Disable** товч.

### Логийг харах

```bash
tail -50 /www/wwwroot/dona-new/storage/logs/auto-deploy.log
```

### Хүчээр deploy ажиллуулах (webhook-гүйгээр)

```bash
cd /www/wwwroot/dona-new && bash bin/auto-deploy.sh
```

### Том өөрчлөлт орвол

`auto-deploy.sh` нь хурдан байхын тулд `composer install` хийдэггүй. Хэрэв `composer.json` өөрчлөгдсөн бол гараар `deploy.sh` ажиллуулах хэрэгтэй:

```bash
cd /www/wwwroot/dona-new && bash deploy.sh
```

---

## Аюулгүй байдал

- `.deploy-hook-secret` файл нь `.gitignore`-д орсон — git-д commit хийгдэхгүй
- HMAC-SHA256 шалгалтаар зөвхөн жинхэнэ GitHub-аас ирсэн request хүлээж авна
- Зөвхөн `master`/`main` branch-ийн push-ийг гүйцэтгэнэ
- Webhook нь background-д ажилладаг тул GitHub-ийн timeout үүсэхгүй
