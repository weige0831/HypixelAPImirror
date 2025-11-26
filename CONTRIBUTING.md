# 贡献指南

感谢您考虑为 Hypixel Mirror API 做出贡献！

## 如何贡献

### 报告 Bug

如果您发现了 bug，请通过 GitHub Issues 提交，并包含以下信息：

- Bug 的详细描述
- 复现步骤
- 预期行为和实际行为
- 系统环境（PHP 版本、MySQL 版本、操作系统）
- 相关的错误日志

### 提交功能请求

如果您有新功能的想法：

1. 先检查 Issues 中是否已有类似请求
2. 创建新的 Issue，详细描述功能和使用场景
3. 等待维护者的反馈

### 提交代码

1. Fork 本仓库
2. 创建您的功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交您的更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 开启 Pull Request

### 代码规范

- 遵循 PSR-12 编码标准
- 添加必要的注释
- 确保代码可以正常运行
- 更新相关文档

## 开发设置

```bash
# 克隆仓库
git clone https://github.com/your-username/hypixelmirro.git
cd hypixelmirro

# 配置环境
cp config.example.php config.php
# 编辑 config.php 填入您的数据库信息

# 导入数据库
mysql -u root -p api < database.sql

# 访问测试
curl "http://localhost/setup.php"
```

## 许可证

提交代码即表示您同意您的贡献将以 MIT 许可证发布。

