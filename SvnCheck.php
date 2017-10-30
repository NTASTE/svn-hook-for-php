<?php

/**
 * User: sunmingsheng
 * Date: 2017/10/28
 * Time: 10:26
 */

class svnCheck
{
    private $repoName       = '';
    private $trxNum         = '';
    private $commitErrMsg   = '备注信息不正确, 标准格式为:@author:author\r\n@description:description\r\n@review:review';
    private $commitParamArr = [ 'author', 'description', 'review' ];
    private $allowFileType  = [ 'php', 'js', 'html' ];
    private $commitFiles    = [ ];
    private $illegalInfo    = [ 'http://test' ,'https://test' ];
    private $tempPhpPath    = '/tmp/svn_temp.php';


    public function __construct($repoName,$trxNum)
    {
        $this->repoName = $repoName;
        $this->trxNum = $trxNum;
    }


    public function runCheck()
    {
        //检查提交的备注信息是否合法
        $commitErrMsg = $this->checkCommitMessage();
        if($commitErrMsg){
            return $commitErrMsg;
        }

        //检查文件类型是否允许提交
        $runCheckMsg = $this->FileCheck();
        if($runCheckMsg){
            return $runCheckMsg;
        }

        return '';
    }


    //获取提交到的文件备注信息
    private function getCommitMessage()
    {
        exec("svnlook log -t $this->trxNum $this->repoName", $mess);
        return implode("\n", $mess);
    }

    //获取提交的文件信息
    private function getCommitFiles()
    {
        exec("svnlook changed $this->repoName --transaction $this->trxNum", $changed);

        $commitedFiles = array();
        foreach ($changed as $line){
            if (in_array(substr($line,0,1), array('A', 'U'))){
                $filename = substr($line,4);
                unset($content);
                exec("svnlook cat $this->repoName $filename -t $this->trxNum", $content);
                $commitedFiles[$filename] = $content;
            }

        }
        return $commitedFiles;
    }

    //校验提交备注信息是否合法
    private function checkCommitMessage()
    {
        $commitMessage = $this->getCommitMessage();
        if(substr($commitMessage,0,1) != '@'){
            return $this->commitErrMsg;
        }
        $commitMessage = trim($commitMessage,'@');
        $commitMessageArr = explode('@',$commitMessage);

        if(count($commitMessageArr) < 3){
            return $this->commitErrMsg;
        }

        foreach($commitMessageArr as $key => $value){
            if(strpos($value,':') === false){
                return $this->commitErrMsg;
            }
            list($param_key,$param_value) = explode(':',$value);

            if(!in_array($param_key,$this->commitParamArr) || !trim($param_value)){
                return $this->commitErrMsg;
            }

        }

        return '';

    }

    private function FileCheck()
    {
        //获取提交的文件信息
        $this->commitFiles = $this->getCommitFiles();

        if($this->commitFiles){

            foreach($this->commitFiles as $fileName => $fileContent){

                //检查文件类型
                $position = strrpos($fileName,'.');
                $suffix = substr($fileName,$position + 1);
                $this->allowFileType[] = '';
                if( strpos($fileName,'vendor') !== false || $fileName == '.env' || ( $position !== false && !in_array($suffix,$this->allowFileType) )){
                    return $fileName.'不允许上传,该文件类型不能上传';
                }

                //检查文件编码
                $fileContent = implode("\r\n",$fileContent);

                if(!$this->checkFileEncoding($fileContent)){
                    return $fileName.'文件编码不正确,只允许上传UTF-8编码的文件';
                }

                //检查文件内容
                $illegal = $this->checkFileContent($fileContent);
                if(!$illegal['status']){
                    return $fileName.'文件中包含'.$illegal['illegal'].'等内容，不允许上传';
                }

                //检查语法
                if($suffix == 'php'){
                    $illegal = $this->checkFileSyntax($fileContent);
                    if($illegal['status'] == false){
                        return $illegal['illegal'];
                    }
                    if($illegal['status'] == true  && $illegal['illegal']){
                        return '存在语法错误: '.str_replace($this->tempPhpPath,$fileName,$illegal['illegal']);

                    }
                }


            }

        }


    }


    //检查文件编码
    private function checkFileEncoding($fileContent)
    {
        $temp = mb_convert_encoding($fileContent,'UTF-8','UTF-8');
        if( md5($fileContent) != md5($temp)){
            return false;
        }
        return true;
    }


    //检查php文件中是否存在不允许上传的信息
    private function checkFileContent($fileContent)
    {
        if($this->illegalInfo){
            foreach($this->illegalInfo as $illegal){
                 if(strpos($fileContent,$illegal) !== false){
                     return [ 'status' => false,'illegal' => $illegal ];
                 }
            }
        }
        return [ 'status' => true,'illegal' => '' ];
    }

    //检查php文件是否存在语法错误
    private function checkFileSyntax($fileContent)
    {
        if(!file_put_contents($this->tempPhpPath,$fileContent)){
            return [ 'status' => false , 'illegal' => 'php临时文件写入失败,请联系管理员' ];
        }

        $descSpec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );

        $cmd = ' php -l '.$this->tempPhpPath;
        $proc = proc_open($cmd, $descSpec, $pipes, null, null);

        if ($proc == false) {
            return [ 'status' => false , 'illegal' => 'svn语法检查执行失败，请联系管理员' ];
        } else {
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            proc_close($proc);
            if($stderr) {
                return [ 'status' => true , 'illegal' => $stderr ];
            }
        }

        return [ 'status' => true , 'illegal' => '' ];

    }


}


//获取指定参数
if (count($argv) < 2) {
    throw new Exception("参数缺失");
}
$repoName = $argv[1];
$trxNum = $argv[2];

$svnCheck = new svnCheck($repoName,$trxNum);

$svnInfo  = $svnCheck->runCheck();

if(!$svnInfo){
    exit(0);
}else{
    $stdErr = fopen('php://stderr', 'w');
    fwrite($stdErr, $svnInfo);
    exit(1);
}

