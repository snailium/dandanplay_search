# 关于这个项目 About this project

Since dandanplay version 11.1, the software no longer has DMHY search integrated.
According to the announcement from its author, search functionality is permanently removed.
However, user can host their own search script for "automatic download", using its "search node" API.
This project is created to implement the search node API.

从弹弹play 11.1版本开始，软件不再集成搜索功能。根据更新公告，搜索功能永久移除。
但是，用户仍然可以根据“搜索节点API”，通过自建脚本使用“自动下载”功能。
本项目便是“搜索节点API”的实现。

# 弹弹play搜索节点API dandanplay search node API

https://raw.githubusercontent.com/kaedei/dandanplay-libraryindex/master/api/ResourceService.md

# 推荐运行环境 Recommended deployment environment

- PHP 7
- cURL enabled.

# 运行效率问题 Script inefficiency

Since DHMY uses Cloudflare to protect their website, using normal search is likely to hit
Cloundflare's browser check. It requires quite a bit effort to setup proper Cookies to pass
the check. Therefore, this project uses DMHY RSS to search resources.

However, DMHY RSS doesn't provide all information required by the API, e.g. file size is missing.
Therefore, additional data are fetched from resource detail page. This causes scripts are running slow.
To limit the time, scripts will only return the first 20 results.

由于DMHY使用Cloudflare来保护网站，使用正常的搜索页面会随机进入CLoudflare浏览器检查页面。
绕过Cloudflare浏览器检查并不容易，所以，本项目使用DMHY RSS来获取搜索结果。

但是，RSS取回的结果并不完整，例如API里面要求的“文件大小”就无法通过RSS获得。
所以，仍需要通过资源页面进一步获取信息。这就造成了脚本运行时间被拖长。
为了减少脚本运行时间，脚本将只返回前20个搜索结果。
