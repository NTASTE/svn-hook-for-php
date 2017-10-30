# Svn-Hook-for-PHP
a very simple svn hook for php

Svn-Hook-for-PHP能做什么?

1:支持提交信息检查，避免无说明的提交。默认支持的提交信息格式为 @author:author\r\n@description:description\r\n@review:review,可以自定义扩展

2:支持文件编码检查，避免编码问题的出现。默认只允许提交UTF-8编码格式的代码文件

3:支持文件类型校验，避免提交敏感类型的文件。默认只允许提交.php .js .html 以及目录。默认不允许提交.env文件以及vendor目录

4:支持PHP代码语法检查，返回具体的错误信息，避免上线出现bug


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
  







