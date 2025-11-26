@echo off
chcp 65001 >nul
echo ====================================
echo  上传到 GitHub
echo ====================================
echo.

echo [1/7] 初始化 Git 仓库...
git init
if errorlevel 1 (
    echo 错误：初始化失败
    pause
    exit /b 1
)
echo ✓ 完成
echo.

echo [2/7] 添加所有文件...
git add .
if errorlevel 1 (
    echo 错误：添加文件失败
    pause
    exit /b 1
)
echo ✓ 完成
echo.

echo [3/7] 查看文件状态...
git status
echo.

echo [4/7] 提交到本地仓库...
git commit -m "Initial commit: Hypixel Mirror API v1.0"
if errorlevel 1 (
    echo 警告：提交失败或没有新变更
)
echo ✓ 完成
echo.

echo [5/7] 设置主分支名称...
git branch -M main
echo ✓ 完成
echo.

echo [6/7] 添加远程仓库...
git remote add origin https://github.com/weige0831/HypixelAPImirror.git 2>nul
if errorlevel 1 (
    echo 远程仓库已存在，跳过...
    git remote set-url origin https://github.com/weige0831/HypixelAPImirror.git
)
echo ✓ 完成
echo.

echo [7/7] 推送到 GitHub...
echo 注意：可能需要输入 GitHub 用户名和密码(Personal Access Token)
echo.
git push -u origin main
if errorlevel 1 (
    echo.
    echo ====================================
    echo  推送失败！
    echo ====================================
    echo 可能的原因：
    echo 1. 需要认证 - 使用 Personal Access Token
    echo 2. 网络问题 - 检查网络连接
    echo 3. 权限问题 - 确认仓库权限
    echo.
    echo 获取 Token: https://github.com/settings/tokens
    echo.
    pause
    exit /b 1
)
echo.

echo ====================================
echo  ✓ 上传成功！
echo ====================================
echo.
echo 访问您的仓库：
echo https://github.com/weige0831/HypixelAPImirror
echo.
pause

