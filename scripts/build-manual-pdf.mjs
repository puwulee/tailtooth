import { readFileSync } from 'fs';
import { marked } from 'marked';
import { chromium } from 'playwright-core';

const EXE = '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const ROOT = '/home/user/tailtooth/docs';
const md = readFileSync(`${ROOT}/operation-manual.md`, 'utf8');

// 影像相對路徑 → 絕對 file:// （讓 Chromium 讀得到截圖）
let html = marked.parse(md);
html = html.replace(/src="images\//g, `src="file://${ROOT}/images/`);

const styled = `<!doctype html><html lang="zh-Hant"><head><meta charset="utf-8"><style>
  @page { margin: 18mm 16mm; }
  body { font-family: "Noto Sans CJK TC","WenQuanYi Zen Hei",sans-serif; color:#1a1a1a; line-height:1.6; font-size:13px; }
  h1 { font-size:24px; border-bottom:3px solid #e0a900; padding-bottom:6px; margin-top:28px; }
  h2 { font-size:18px; color:#b8860b; border-left:5px solid #e0a900; padding-left:10px; margin-top:24px; }
  h3 { font-size:15px; color:#333; }
  table { border-collapse:collapse; width:100%; margin:10px 0; font-size:12px; }
  th,td { border:1px solid #ccc; padding:6px 9px; text-align:left; }
  th { background:#fff7e0; }
  img { max-width:100%; border:1px solid #ddd; border-radius:8px; margin:10px 0; }
  code { background:#f3f3f3; padding:1px 5px; border-radius:4px; font-size:12px; }
  pre { background:#f6f8fa; padding:12px; border-radius:8px; overflow:auto; }
  blockquote { border-left:4px solid #e0a900; margin:10px 0; padding:4px 14px; color:#555; background:#fffdf5; }
  ul,ol { margin:6px 0; }
  a { color:#b8860b; text-decoration:none; }
  hr { border:0; border-top:1px solid #e5e5e5; margin:20px 0; }
</style></head><body>${html}</body></html>`;

const browser = await chromium.launch({ executablePath: EXE, args: ['--no-sandbox'] });
const page = await browser.newPage();
await page.setContent(styled, { waitUntil: 'networkidle' });
await page.pdf({
  path: `${ROOT}/Tailtooth-操作手冊.pdf`,
  format: 'A4',
  printBackground: true,
  margin: { top: '16mm', bottom: '16mm', left: '14mm', right: '14mm' },
  displayHeaderFooter: true,
  headerTemplate: '<div></div>',
  footerTemplate: '<div style="width:100%;text-align:center;font-size:9px;color:#999;">Tailtooth 戰鬥陀螺競賽平台 · 操作手冊 — 第 <span class="pageNumber"></span> / <span class="totalPages"></span> 頁</div>',
});
await browser.close();
console.log('pdf done');
