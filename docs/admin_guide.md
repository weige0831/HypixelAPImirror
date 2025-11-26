# Hypixel Mirror API 管理员指南

## API Key 管理

### 添加新的 API Key

1. 登录数据库管理界面（phpMyAdmin 或其他工具）
2. 找到 `hypixel_api_keys` 表
3. 添加新记录，需要填写以下字段：
   - `key`: Hypixel API key
   - `owner`: key 的所有者名称（可选）
   - `daily_limit`: 每日请求限制（默认 120000）
   - `minute_limit`: 每分钟请求限制（默认 300）
   - `enabled`: 1 表示启用，0 表示禁用
   - `last_used`: 系统自动更新，无需手动填写
   - `requests_today`: 系统自动更新，无需手动填写
   - `requests_minute`: 系统自动更新，无需手动填写

### 获取 Hypixel API Key

1. 登录 Hypixel 服务器
2. 输入指令 `/api new`
3. 复制生成的 API key

### API Key 管理建议

1. 定期检查 API key 的使用情况
2. 建议至少保持 3 个以上的可用 API key
3. 如果单个 key 的使用量接近限制，建议添加新的 key
4. 发现异常使用情况时，可以通过设置 `enabled = 0` 来临时禁用某个 key

## 缓存管理

### 缓存设置

- 玩家数据缓存时间：3 天
- 缓存存储在 `player_cache` 表中

### 清理缓存

如需手动清理缓存：

1. 登录数据库
2. 执行以下 SQL 语句清理过期缓存：
```sql
DELETE FROM player_cache WHERE last_updated < DATE_SUB(NOW(), INTERVAL 3 DAY);
```

## 系统维护

### 日常检查项目

1. 检查 API keys 使用情况
2. 监控服务器负载
3. 检查错误日志
4. 检查缓存数据库大小

### 故障排除

1. API 响应慢
   - 检查数据库连接
   - 检查 API keys 是否足够
   - 检查服务器负载

2. 缓存问题
   - 检查数据库空间
   - 检查缓存表索引
   - 必要时清理过期缓存

## 安全建议

1. 定期更换数据库密码
2. 保护好 API keys，不要泄露
3. 监控异常请求
4. 定期备份数据库

## 联系方式

如有紧急问题，请联系：
- Email: admin@example.com
- 网站: api.example.com

## 更新记录

- 2024-03: 初始版本 