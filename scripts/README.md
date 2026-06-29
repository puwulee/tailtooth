# 文件工具

## 產生操作手冊 PDF

將 `docs/operation-manual.md`（含截圖）渲染為 PDF：

```bash
npm i marked playwright-core --no-save   # 一次性
node scripts/build-manual-pdf.mjs        # 產生 docs/Tailtooth-操作手冊.pdf
```

以 headless Chromium 渲染，CJK 字型與截圖皆正常。
