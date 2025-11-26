@echo off
chcp 65001 >nul
echo ====================================
echo  更新 GitHub Pages 文档
echo ====================================
echo.

echo [1/6] 确保在 main 分支...
git checkout main
if errorlevel 1 (
    echo 错误：切换分支失败
    pause
    exit /b 1
)
echo ✓ 完成
echo.

echo [2/6] 拉取最新代码...
git pull
echo ✓ 完成
echo.

echo [3/6] 切换到 gh-pages 分支...
git checkout gh-pages
if errorlevel 1 (
    echo 错误：gh-pages 分支不存在
    pause
    exit /b 1
)
echo ✓ 完成
echo.

echo [4/6] 从 main 分支复制最新文档...
git checkout main -- public/docs.html
copy public\docs.html index.html
echo ✓ 完成
echo.

echo [5/6] 提交更新...
git add index.html
git commit -m "Update documentation from main branch"
if errorlevel 1 (
    echo 没有新的更改
)
echo ✓ 完成
echo.

echo [6/6] 推送到 GitHub...
git push
if errorlevel 1 (
    echo 错误：推送失败
    pause
    exit /b 1
)
echo ✓ 完成
echo.

echo [完成] 切回 main 分支...
git checkout main
echo.

echo ====================================
echo  ✓ 文档更新完成！
echo ====================================
echo.
echo GitHub Pages 将在 1-2 分钟后更新
echo 访问：https://weige0831.github.io/HypixelAPImirror/
echo.
pause

