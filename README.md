# Hypixel Mirror API

<p align="center">
  <a href="https://weige0831.github.io/HypixelAPImirror/">
    <img src="https://img.shields.io/badge/文档-在线查看-blue?style=for-the-badge" alt="文档">
  </a>
  <a href="https://api.everlastingness.net/Hypixelmirror/public/">
    <img src="https://img.shields.io/badge/演示站点-访问-green?style=for-the-badge" alt="演示站点">
  </a>
  <a href="https://github.com/weige0831/HypixelAPImirror/blob/main/LICENSE">
    <img src="https://img.shields.io/badge/许可证-MIT-orange?style=for-the-badge" alt="License">
  </a>
</p>

<p align="center">
  <strong>智能的 Hypixel API 缓存代理服务</strong>
</p>

<p align="center">
  这是一个用于缓存 Hypixel API 数据的代理服务，帮助避免直接请求时的速率限制问题。
</p>

---

## 📚 快速链接

- 📖 **[完整文档](https://weige0831.github.io/HypixelAPImirror/)** - GitHub Pages 托管的完整文档
- 🌐 **[演示站点](https://api.everlastingness.net/Hypixelmirror/public/)** - 在线演示和 API 测试
- 💻 **[源代码](https://github.com/weige0831/HypixelAPImirror)** - GitHub 仓库
- 🐛 **[问题反馈](https://github.com/weige0831/HypixelAPImirror/issues)** - 提交 Bug 或建议

## ✨ 特性

- 🚀 **智能缓存**: 数据缓存 3 小时，自动过期刷新
- 🔄 **透明刷新**: 访问过期缓存时自动从官方 API 获取新数据
- 🔑 **多密钥管理**: 自动轮换 API 密钥，删除无效密钥
- 📊 **完整统计**: 请求日志、缓存命中率、密钥使用情况
- 🛠️ **管理工具**: Web 界面管理缓存和密钥
- 🔒 **安全认证**: 管理功能需要 HTTP Basic 认证

## 📋 系统要求

- PHP 7.4+
- MySQL 5.7+
- Nginx 1.25+ (或其他 Web 服务器)
- PHP 扩展: PDO, PDO_MySQL, cURL, JSON

## 🚀 快速开始

### 1. 克隆项目
```bash
git clone https://github.com/weige0831/HypixelAPImirror.git
cd HypixelAPImirror
```

### 2. 配置数据库
```bash
# 登录 MySQL
mysql -u root -p

# 创建数据库(如果使用现有数据库可跳过)
CREATE DATABASE IF NOT EXISTS api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 导入数据库结构
mysql -u root -p api < database.sql
```

### 3. 配置文件
```bash
# 复制配置模板
cp config.example.php config.php

# 编辑配置文件
nano config.php
```

修改以下内容：
- 数据库连接信息
- 管理员用户名和密码

### 4. 配置 Nginx
```bash
# 复制并修改 nginx 配置
cp nginx.conf /etc/nginx/sites-available/hypixel-api
ln -s /etc/nginx/sites-available/hypixel-api /etc/nginx/sites-enabled/

# 重启 Nginx
systemctl restart nginx
```

### 5. 运行初始化脚本
访问 `http://api.example.com/setup.php` (需要管理员认证)

### 6. 添加 Hypixel API 密钥

首先在 [Hypixel 开发者面板](https://developer.hypixel.net/) 获取 API 密钥。

然后使用以下命令添加：

```bash
# 方式一：GET 请求（推荐）
curl -u admin:password "http://api.example.com/hypixel-keys.php?add_key=YOUR_HYPIXEL_API_KEY"

# 方式二：POST 请求
curl -u admin:password -X POST \
  -H "Content-Type: application/json" \
  -d '{"hypixel_key":"YOUR_HYPIXEL_API_KEY"}' \
  "http://api.example.com/hypixel-keys.php"

# 验证密钥已添加
curl -u admin:password "http://api.example.com/hypixel-keys.php"
```

## 📖 API 使用

### 获取玩家数据
```
GET /index.php?uuid={player_uuid}
GET /index.php?name={player_name}
```

**示例:**
```bash
curl "https://api.example.com/index.php?uuid=f7c77d999f154a66a87dc4a51ef30d19"
curl "https://api.example.com/index.php?name=Notch"
```

**响应:**
```json
{
  "success": true,
  "data": {
    "player": { ... },
    "fetch_time": "2025-11-25 10:30:00"
  }
}
```

## 🛠️ 管理功能

所有管理接口需要 HTTP Basic 认证。

### 查看统计
- **统计面板**: `/stats.php`
- **缓存统计**: `/cache-stats.php`

### 缓存管理
- **快速清理**: `/cleanup.php` 或 `/cleanup.php?force=1`
- **维护工具**: `/maintenance.php?action=<action>`

### 密钥管理
- **密钥管理**: `/hypixel-keys.php`

完整文档请访问: `/docs.html`

## 📊 缓存策略

- **缓存时长**: 3 小时 (10800 秒)
- **过期处理**: 访问过期缓存时自动删除并重新获取
- **批量清理**: 系统自动在后台清理大量过期数据
- **防止重复**: 自动删除重复的缓存条目

## 📁 项目结构

```
hypixelmirro/
├── src/                    # 核心源码
│   ├── Database.php        # 数据库连接
│   ├── AdminAuth.php       # 管理员认证
│   ├── HypixelKeyManager.php  # 密钥管理
│   ├── CacheManager.php    # 缓存管理
│   └── HypixelAPI.php      # API 控制器
├── public/                 # 公共访问目录
│   ├── index.php           # API 主入口
│   ├── setup.php           # 初始化脚本
│   ├── maintenance.php     # 维护工具
│   ├── cleanup.php         # 清理接口
│   ├── stats.php           # 统计面板
│   ├── cache-stats.php     # 缓存统计
│   ├── hypixel-keys.php    # 密钥管理
│   └── docs.html           # 完整文档
├── config.php              # 配置文件
├── database.sql            # 数据库结构
└── nginx.conf              # Nginx 配置

```

## 🔧 维护

### 手动清理缓存
```bash
# 通过 API
curl -u admin:password "https://api.example.com/cleanup.php?force=1"

# 或通过维护工具
curl -u admin:password "https://api.example.com/maintenance.php?action=full_maintenance"
```

### 查看统计
```bash
curl "https://api.example.com/stats.php"
```

## 📝 更新日志

### v1.1
- ✅ 缓存时长从 3 天改为 3 小时
- ✅ 实现即时缓存过期检测和刷新
- ✅ 添加完整的管理工具和文档
- ✅ 优化缓存清理机制
- ✅ 改进密钥管理系统

## 📄 许可证

MIT License

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📧 联系方式

如有问题，请提交 Issue。 