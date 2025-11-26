# 🌐 启用 GitHub Pages 文档站点

## ✅ 已完成的工作

我已经为您创建并推送了 `gh-pages` 分支，现在只需要在 GitHub 上启用即可！

---

## 🚀 启用步骤（3分钟完成）

### 1. 访问仓库设置
打开浏览器，访问：
```
https://github.com/weige0831/HypixelAPImirror/settings/pages
```

或者手动操作：
1. 打开您的仓库：https://github.com/weige0831/HypixelAPImirror
2. 点击顶部的 **Settings**（设置）
3. 在左侧菜单找到 **Pages**（在 "Code and automation" 部分）

### 2. 配置 GitHub Pages

在 **Build and deployment** 部分：

**Source（来源）：**
- 选择 **Deploy from a branch**

**Branch（分支）：**
- Branch: 选择 **`gh-pages`**
- Folder: 选择 **`/ (root)`**

点击 **Save** 保存。

### 3. 等待部署完成

GitHub 会自动开始部署，大约需要 1-2 分钟。

刷新页面后，您会看到：
```
Your site is live at https://weige0831.github.io/HypixelAPImirror/
```

---

## 🎉 完成！现在您有两个文档站点

### 📖 GitHub Pages 文档（推荐用于分享）
```
https://weige0831.github.io/HypixelAPImirror/
```
- ✅ 免费托管
- ✅ 自动更新（推送 gh-pages 分支即可）
- ✅ HTTPS 支持
- ✅ 适合分享给其他开发者

### 🌐 您的演示站点
```
https://api.everlastingness.net/Hypixelmirror/public/docs.html
```
- ✅ 展示完整的运行环境
- ✅ 可以测试 API 功能
- ✅ 自定义域名

---

## 📝 更新文档的方法

### 方式一：更新 docs.html 后自动同步到 GitHub Pages

```bash
# 1. 修改 public/docs.html
# 2. 提交到 main 分支
git add public/docs.html
git commit -m "Update documentation"
git push

# 3. 合并到 gh-pages
git checkout gh-pages
git checkout main -- public/docs.html
copy public\docs.html index.html
git add index.html
git commit -m "Update GitHub Pages documentation"
git push
git checkout main
```

### 方式二：创建自动化脚本

我为您创建了一个自动更新脚本 `update-docs.bat`

---

## 🎨 自定义域名（可选）

如果想使用自己的域名（如 `docs.yourdomain.com`）：

1. 在 GitHub Pages 设置中，**Custom domain** 输入您的域名
2. 在您的 DNS 提供商添加 CNAME 记录：
   ```
   docs.yourdomain.com  →  weige0831.github.io
   ```
3. 勾选 **Enforce HTTPS**

---

## 📊 README 中的徽章说明

我已经在 README.md 顶部添加了三个徽章：

- 📖 **文档** - 指向 GitHub Pages
- 🌐 **演示站点** - 指向您的实际演示站
- 📄 **许可证** - MIT License

这些徽章会让您的项目看起来更专业！

---

## 🔄 分支管理

现在您的仓库有两个分支：

- **`main`** - 主要代码分支（PHP 源码）
- **`gh-pages`** - 文档站点分支（只有 HTML）

通常您只需要在 `main` 分支工作，需要更新文档时再同步到 `gh-pages`。

---

## ❓ 常见问题

### Q: GitHub Pages 多久更新一次？
A: 推送到 `gh-pages` 后 1-2 分钟自动部署。

### Q: 可以删除 gh-pages 分支吗？
A: 可以，但会导致文档站点下线。

### Q: 如何查看部署状态？
A: 访问 https://github.com/weige0831/HypixelAPImirror/actions

### Q: 部署失败了怎么办？
A: 在 Actions 页面查看错误日志，通常是 HTML 格式问题。

---

## 🎯 下一步

1. ✅ 访问 GitHub 仓库设置启用 Pages
2. ✅ 等待 1-2 分钟部署完成
3. ✅ 访问 https://weige0831.github.io/HypixelAPImirror/ 查看效果
4. ✅ 在 README、社交媒体分享您的文档链接

恭喜！您的项目现在有了专业的在线文档！🎉

