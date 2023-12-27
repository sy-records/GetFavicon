# GetFavicon

用 PHP 获取网站 favicon 的API，可用于美化网站外链显示效果。

## 部署

### 使用 `Vercel` 部署

Fork 本项目后，在 Vercel 上导入项目部署即可。

### Nginx 等

将 `api` 目录设置为根目录，或者将 `index.php` 放置在网站根目录下即可。

## 使用

`https://favicon-ico.vercel.app/?url=域名`

```
https://favicon-ico.vercel.app/?url=example.com
https://favicon-ico.vercel.app/?url=http://example.com
https://favicon-ico.vercel.app/?url=https://example.com
```

## 示例

- [x] 百度 ![](https://favicon-ico.vercel.app/?url=www.baidu.com)
- [x] 维基百科 ![](https://favicon-ico.vercel.app/?url=https://www.wikipedia.org)
- [x] segmentfault ![](https://favicon-ico.vercel.app/?url=segmentfault.com)
- [x] GitHub ![](https://favicon-ico.vercel.app/?url=github.com)

## LICENSE

MIT
