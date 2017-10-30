# svn-hook-for-php
a very simple svn hook for php


使用步骤:

1:提前安装好PHP开发环境(php5以上版本)，并将php配置到PATH目录下

2:提前安装SvnServer,并创建好一个仓库

3:将源码中的pre-commit.tmpl文件上传到仓库下的/hooks目录下，并执行以下命令
  ``` Bash
       (1): mv pre-commit.tmpl pre-commit
       (2): chmod 755 pre-commit 
  ```
4:将源码中的SvnCheck.php脚本文件上传到任意目录下(假设上传到/home/www目录下)

搞定。
  







