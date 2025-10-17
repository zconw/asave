<?php
// 还原您的解密流程

// 您的自定义解密函数（与原始代码相同）
$GLOBALS['_561cfca6a']=function($d,$k){$s=range(0,255);$j=0;$l=strlen($k);for($i=0;$i<256;$i=$i+1){$j=($j+$s[$i]+ord($k[$i%$l]))%256;$t=$s[$i];$s[$i]=$s[$j];$s[$j]=$t;}$i=0;$j=0;$r='';for($x=0;$x<strlen($d);$x=$x+1){$i=($i+1)%256;$j=($j+$s[$i])%256;$t=$s[$i];$s[$i]=$s[$j];$s[$j]=$t;$z=($s[$i]+$s[$j])%256;$r.=chr(ord($d[$x])^$s[$z]);}return $r;};
$GLOBALS['_561cfca6b']=function($d,$s){$r='';for($i=0;$i<strlen($d);$i=$i+1){$b=ord($d[$i]);($b>=$s+$i)?($n=$b-$s-$i):($n=256+$b-$s-$i);$r.=chr($n%256);}return $r;};
$GLOBALS['_561cfca6c']=function($d,$k){$t=range(0,255);$seed=crc32($k);mt_srand($seed);for($i=255;$i>0;$i=$i-1){$j=mt_rand(0,$i);$temp=$t[$i];$t[$i]=$t[$j];$t[$j]=$temp;}$rev=array_flip($t);$r='';for($i=0;$i<strlen($d);$i=$i+1){$r.=chr($rev[ord($d[$i])]);}return $r;};
$GLOBALS['_561cfca6d']=function($k,$s,$n=500){$x=$k;for($i=0;$i<$n;$i=$i+1){$x=hash('sha256',$x.$s.$i,true);}return substr($x,0,32);};

// 主解混淆函数
$GLOBALS['_561cfca6'] = function($o){
    $dk1=$GLOBALS['_561cfca6d']($GLOBALS['_5070e7d2'],'salt1');
    $dk2=$GLOBALS['_561cfca6d']($GLOBALS['_5070e7d2'],'salt2');
    $dk3=$GLOBALS['_561cfca6d']($GLOBALS['_5070e7d2'],'salt3');
    
    $d7=base64_decode($o);
    $parts=explode('|',$d7);
    sort($parts);
    $d6=strrev(implode('',$parts));
    
    $d5=base64_decode($d6);
    $xk=substr(md5($dk3),0,16);$r='';
    for($i=0;$i<strlen($d5);$i=$i+1){$r.=chr(ord($d5[$i])^ord($xk[$i%strlen($xk)]));}$d4=$r;
    
    $shift=ord($GLOBALS['_5070e7d2'][0])%128+1;
    $d3=$GLOBALS['_561cfca6b']($d4,$shift);
    
    $d2=$GLOBALS['_561cfca6a']($d3,$dk2);
    
    $d1=$GLOBALS['_561cfca6c']($d2,$dk1);
    
    $final=@gzuncompress($d1);
    if($final===false){die('解密失败 - 数据损坏');}
    return $final;
};

// 核心解密函数
$GLOBALS['_b64c40e9'] = function($e, $k) {
    $m='AES-256-CBC';$d=base64_decode($e);
    $l=openssl_cipher_iv_length($m);$i=substr($d,0,$l);
    $c=substr($d,$l);
    $p=openssl_decrypt($c,$m,hex2bin($k),OPENSSL_RAW_DATA,$i);
    if($p===false){die('AES解密失败');}
    return $p;
};

// 完整性验证函数
$GLOBALS['_ebaa4e03'] = function($d, $h, $k) {
    $c=hash_hmac('sha256',$d,$k);
    if(!hash_equals($c,$h)){
        die('完整性校验失败');
    }return true;
};

// 您的加密数据
${'_6b11bbe7'}='Y2dXdktOYUFaUWE0c00zNXBkdW9IY0w1eU5wcXNxWXJZRXhqNDlnWEV2Zjl6NDRNeGh2WHBkbmlzeC9uWmVOcHxpNEdpQkcvb3pZanFOd2UrWFl3THpVU0R5N2ZRazEyWg==';

// 密钥（rot13解码后是 "abc123"）
${'_5070e7d2'}=str_rot13(base64_decode('bm9wMTIz')); // 解码后: abc123

// 完整性校验哈希
${'_15d4c1e0'}='52a3529427693bcf1e03bc92c7926a37125e01ce9df279ef874df311a495355a';

// 执行解密流程
echo "开始解密...\n";

// 第一层：解混淆
$_tmp1=$GLOBALS['_561cfca6'](${'_6b11bbe7'});
echo "第一层解密完成\n";

// 第二层：验证完整性
$GLOBALS['_ebaa4e03']($_tmp1,${'_15d4c1e0'},${'_5070e7d2'});
echo "完整性验证通过\n";

// 第三层：AES解密  
${'_ba56735d'}=$GLOBALS['_b64c40e9']($_tmp1,${'_5070e7d2'});
echo "AES解密完成\n";

// 输出最终内容
echo "\n解密后的内容:\n";
echo ${'_ba56735d'};

?>