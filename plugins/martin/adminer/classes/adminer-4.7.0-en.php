<?php
/** Adminer - Compact database management
* @link https://www.adminer.org/
* @author Jakub Vrana, https://www.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 4.7.0
*/error_reporting(6135);$Vc=!preg_match('~^(unsafe_raw)?$~',ini_get("filter.default"));if($Vc||ini_get("filter.default_flags")){foreach(array('_GET','_POST','_COOKIE','_SERVER')as$X){$Ei=filter_input_array(constant("INPUT$X"),FILTER_UNSAFE_RAW);if($Ei)$$X=$Ei;}}if(function_exists("mb_internal_encoding"))mb_internal_encoding("8bit");function
connection(){global$g;return$g;}function
adminer(){global$b;return$b;}function
version(){global$ia;return$ia;}function
idf_unescape($u){$ne=substr($u,-1);return
str_replace($ne.$ne,$ne,substr($u,1,-1));}function
escape_string($X){return
substr(q($X),1,-1);}function
number($X){return
preg_replace('~[^0-9]+~','',$X);}function
number_type(){return'((?<!o)int(?!er)|numeric|real|float|double|decimal|money)';}function
remove_slashes($og,$Vc=false){if(get_magic_quotes_gpc()){while(list($y,$X)=each($og)){foreach($X
as$de=>$W){unset($og[$y][$de]);if(is_array($W)){$og[$y][stripslashes($de)]=$W;$og[]=&$og[$y][stripslashes($de)];}else$og[$y][stripslashes($de)]=($Vc?$W:stripslashes($W));}}}}function
bracket_escape($u,$Na=false){static$qi=array(':'=>':1',']'=>':2','['=>':3','"'=>':4');return
strtr($u,($Na?array_flip($qi):$qi));}function
min_version($Vi,$Ae="",$h=null){global$g;if(!$h)$h=$g;$jh=$h->server_info;if($Ae&&preg_match('~([\d.]+)-MariaDB~',$jh,$B)){$jh=$B[1];$Vi=$Ae;}return(version_compare($jh,$Vi)>=0);}function
charset($g){return(min_version("5.5.3",0,$g)?"utf8mb4":"utf8");}function
script($th,$pi="\n"){return"<script".nonce().">$th</script>$pi";}function
script_src($Ji){return"<script src='".h($Ji)."'".nonce()."></script>\n";}function
nonce(){return' nonce="'.get_nonce().'"';}function
target_blank(){return' target="_blank" rel="noreferrer noopener"';}function
h($P){return
str_replace("\0","&#0;",htmlspecialchars($P,ENT_QUOTES,'utf-8'));}function
nl_br($P){return
str_replace("\n","<br>",$P);}function
checkbox($C,$Y,$eb,$ke="",$qf="",$jb="",$le=""){$I="<input type='checkbox' name='$C' value='".h($Y)."'".($eb?" checked":"").($le?" aria-labelledby='$le'":"").">".($qf?script("qsl('input').onclick = function () { $qf };",""):"");return($ke!=""||$jb?"<label".($jb?" class='$jb'":"").">$I".h($ke)."</label>":$I);}function
optionlist($wf,$dh=null,$Ni=false){$I="";foreach($wf
as$de=>$W){$xf=array($de=>$W);if(is_array($W)){$I.='<optgroup label="'.h($de).'">';$xf=$W;}foreach($xf
as$y=>$X)$I.='<option'.($Ni||is_string($y)?' value="'.h($y).'"':'').(($Ni||is_string($y)?(string)$y:$X)===$dh?' selected':'').'>'.h($X);if(is_array($W))$I.='</optgroup>';}return$I;}function
html_select($C,$wf,$Y="",$pf=true,$le=""){if($pf)return"<select name='".h($C)."'".($le?" aria-labelledby='$le'":"").">".optionlist($wf,$Y)."</select>".(is_string($pf)?script("qsl('select').onchange = function () { $pf };",""):"");$I="";foreach($wf
as$y=>$X)$I.="<label><input type='radio' name='".h($C)."' value='".h($y)."'".($y==$Y?" checked":"").">".h($X)."</label>";return$I;}function
select_input($Ja,$wf,$Y="",$pf="",$ag=""){$Uh=($wf?"select":"input");return"<$Uh$Ja".($wf?"><option value=''>$ag".optionlist($wf,$Y,true)."</select>":" size='10' value='".h($Y)."' placeholder='$ag'>").($pf?script("qsl('$Uh').onchange = $pf;",""):"");}function
confirm($Ke="",$eh="qsl('input')"){return
script("$eh.onclick = function () { return confirm('".($Ke?js_escape($Ke):'Are you sure?')."'); };","");}function
print_fieldset($t,$se,$Yi=false){echo"<fieldset><legend>","<a href='#fieldset-$t'>$se</a>",script("qsl('a').onclick = partial(toggle, 'fieldset-$t');",""),"</legend>","<div id='fieldset-$t'".($Yi?"":" class='hidden'").">\n";}function
bold($Va,$jb=""){return($Va?" class='active $jb'":($jb?" class='$jb'":""));}function
odd($I=' class="odd"'){static$s=0;if(!$I)$s=-1;return($s++%2?$I:'');}function
js_escape($P){return
addcslashes($P,"\r\n'\\/");}function
json_row($y,$X=null){static$Wc=true;if($Wc)echo"{";if($y!=""){echo($Wc?"":",")."\n\t\"".addcslashes($y,"\r\n\t\"\\/").'": '.($X!==null?'"'.addcslashes($X,"\r\n\"\\/").'"':'null');$Wc=false;}else{echo"\n}\n";$Wc=true;}}function
ini_bool($Qd){$X=ini_get($Qd);return(preg_match('~^(on|true|yes)$~i',$X)||(int)$X);}function
sid(){static$I;if($I===null)$I=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$I;}function
set_password($Ui,$N,$V,$F){$_SESSION["pwds"][$Ui][$N][$V]=($_COOKIE["adminer_key"]&&is_string($F)?array(encrypt_string($F,$_COOKIE["adminer_key"])):$F);}function
get_password(){$I=get_session("pwds");if(is_array($I))$I=($_COOKIE["adminer_key"]?decrypt_string($I[0],$_COOKIE["adminer_key"]):false);return$I;}function
q($P){global$g;return$g->quote($P);}function
get_vals($G,$e=0){global$g;$I=array();$H=$g->query($G);if(is_object($H)){while($J=$H->fetch_row())$I[]=$J[$e];}return$I;}function
get_key_vals($G,$h=null,$mh=true){global$g;if(!is_object($h))$h=$g;$I=array();$H=$h->query($G);if(is_object($H)){while($J=$H->fetch_row()){if($mh)$I[$J[0]]=$J[1];else$I[]=$J[0];}}return$I;}function
get_rows($G,$h=null,$n="<p class='error'>"){global$g;$wb=(is_object($h)?$h:$g);$I=array();$H=$wb->query($G);if(is_object($H)){while($J=$H->fetch_assoc())$I[]=$J;}elseif(!$H&&!is_object($h)&&$n&&defined("PAGE_HEADER"))echo$n.error()."\n";return$I;}function
unique_array($J,$w){foreach($w
as$v){if(preg_match("~PRIMARY|UNIQUE~",$v["type"])){$I=array();foreach($v["columns"]as$y){if(!isset($J[$y]))continue
2;$I[$y]=$J[$y];}return$I;}}}function
escape_key($y){if(preg_match('(^([\w(]+)('.str_replace("_",".*",preg_quote(idf_escape("_"))).')([ \w)]+)$)',$y,$B))return$B[1].idf_escape(idf_unescape($B[2])).$B[3];return
idf_escape($y);}function
where($Z,$p=array()){global$g,$x;$I=array();foreach((array)$Z["where"]as$y=>$X){$y=bracket_escape($y,1);$e=escape_key($y);$I[]=$e.($x=="sql"&&preg_match('~^[0-9]*\.[0-9]*$~',$X)?" LIKE ".q(addcslashes($X,"%_\\")):($x=="mssql"?" LIKE ".q(preg_replace('~[_%[]~','[\0]',$X)):" = ".unconvert_field($p[$y],q($X))));if($x=="sql"&&preg_match('~char|text~',$p[$y]["type"])&&preg_match("~[^ -@]~",$X))$I[]="$e = ".q($X)." COLLATE ".charset($g)."_bin";}foreach((array)$Z["null"]as$y)$I[]=escape_key($y)." IS NULL";return
implode(" AND ",$I);}function
where_check($X,$p=array()){parse_str($X,$cb);remove_slashes(array(&$cb));return
where($cb,$p);}function
where_link($s,$e,$Y,$sf="="){return"&where%5B$s%5D%5Bcol%5D=".urlencode($e)."&where%5B$s%5D%5Bop%5D=".urlencode(($Y!==null?$sf:"IS NULL"))."&where%5B$s%5D%5Bval%5D=".urlencode($Y);}function
convert_fields($f,$p,$L=array()){$I="";foreach($f
as$y=>$X){if($L&&!in_array(idf_escape($y),$L))continue;$Ga=convert_field($p[$y]);if($Ga)$I.=", $Ga AS ".idf_escape($y);}return$I;}function
cookiem($C,$Y,$ve=2592000){global$ba;return
header("Set-Cookie: $C=".urlencode($Y).($ve?"; expires=".gmdate("D, d M Y H:i:s",time()+$ve)." GMT":"")."; path=".preg_replace('~\?.*~','',$_SERVER["REQUEST_URI"]).($ba?"; secure":"")."; HttpOnly; SameSite=lax",false);}function
restart_session(){if(!ini_bool("session.use_cookies"))session_start();}function
stop_session($bd=false){if(!ini_bool("session.use_cookies")||($bd&&@ini_set("session.use_cookies",false)!==false))session_write_close();}function&get_session($y){return$_SESSION[$y][DRIVER][SERVER][$_GET["username"]];}function
set_session($y,$X){$_SESSION[$y][DRIVER][SERVER][$_GET["username"]]=$X;}function
auth_url($Ui,$N,$V,$l=null){global$ec;preg_match('~([^?]*)\??(.*)~',remove_from_uri(implode("|",array_keys($ec))."|username|".($l!==null?"db|":"").session_name()),$B);return"$B[1]?".(sid()?SID."&":"").($Ui!="server"||$N!=""?urlencode($Ui)."=".urlencode($N)."&":"")."username=".urlencode($V).($l!=""?"&db=".urlencode($l):"").($B[2]?"&$B[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirectm($A,$Ke=null){if($Ke!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($A!==null?$A:$_SERVER["REQUEST_URI"]))][]=$Ke;}if($A!==null){if($A=="")$A=".";header("Location: $A");exit;}}function
query_redirect($G,$A,$Ke,$_g=true,$Cc=true,$Nc=false,$ci=""){global$g,$n,$b;if($Cc){$Ah=microtime(true);$Nc=!$g->query($G);$ci=format_time($Ah);}$wh="";if($G)$wh=$b->messageQuery($G,$ci,$Nc);if($Nc){$n=error().$wh.script("messagesPrint();");return
false;}if($_g)redirectm($A,$Ke.$wh);return
true;}function
queries($G){global$g;static$tg=array();static$Ah;if(!$Ah)$Ah=microtime(true);if($G===null)return
array(implode("\n",$tg),format_time($Ah));$tg[]=(preg_match('~;$~',$G)?"DELIMITER ;;\n$G;\nDELIMITER ":$G).";";return$g->query($G);}function
apply_queries($G,$S,$zc='table'){foreach($S
as$Q){if(!queries("$G ".$zc($Q)))return
false;}return
true;}function
queries_redirect($A,$Ke,$_g){list($tg,$ci)=queries(null);return
query_redirect($tg,$A,$Ke,$_g,false,!$_g,$ci);}function
format_time($Ah){return
sprintf('%.3f s',max(0,microtime(true)-$Ah));}function
remove_from_uri($Lf=""){return
substr(preg_replace("~(?<=[?&])($Lf".(SID?"":"|".session_name()).")=[^&]*&~",'',"$_SERVER[REQUEST_URI]&"),0,-1);}function
pagination($E,$Jb){return" ".($E==$Jb?$E+1:'<a href="'.h(remove_from_uri("page").($E?"&page=$E".($_GET["next"]?"&next=".urlencode($_GET["next"]):""):"")).'">'.($E+1)."</a>");}function
get_file($y,$Rb=false){$Tc=$_FILES[$y];if(!$Tc)return
null;foreach($Tc
as$y=>$X)$Tc[$y]=(array)$X;$I='';foreach($Tc["error"]as$y=>$n){if($n)return$n;$C=$Tc["name"][$y];$ki=$Tc["tmp_name"][$y];$zb=file_get_contents($Rb&&preg_match('~\.gz$~',$C)?"compress.zlib://$ki":$ki);if($Rb){$Ah=substr($zb,0,3);if(function_exists("iconv")&&preg_match("~^\xFE\xFF|^\xFF\xFE~",$Ah,$Fg))$zb=iconv("utf-16","utf-8",$zb);elseif($Ah=="\xEF\xBB\xBF")$zb=substr($zb,3);$I.=$zb."\n\n";}else$I.=$zb;}return$I;}function
upload_error($n){$He=($n==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($n?'Unable to upload a file.'.($He?" ".sprintf('Maximum allowed file size is %sB.',$He):""):'File does not exist.');}function
repeat_pattern($Yf,$te){return
str_repeat("$Yf{0,65535}",$te/65535)."$Yf{0,".($te%65535)."}";}function
is_utf8($X){return(preg_match('~~u',$X)&&!preg_match('~[\0-\x8\xB\xC\xE-\x1F]~',$X));}function
shorten_utf8($P,$te=80,$Ih=""){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{10FFFF}]",$te).")($)?)u",$P,$B))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$te).")($)?)",$P,$B);return
h($B[1]).$Ih.(isset($B[2])?"":"<i>...</i>");}function
format_number($X){return
strtr(number_format($X,0,".",','),preg_split('~~u','0123456789',-1,PREG_SPLIT_NO_EMPTY));}function
friendly_url($X){return
preg_replace('~[^a-z0-9_]~i','-',$X);}function
hidden_fields($og,$Fd=array()){$I=false;while(list($y,$X)=each($og)){if(!in_array($y,$Fd)){if(is_array($X)){foreach($X
as$de=>$W)$og[$y."[$de]"]=$W;}else{$I=true;echo'<input type="hidden" name="'.h($y).'" value="'.h($X).'">';}}}return$I;}function
hidden_fields_get(){echo(sid()?'<input type="hidden" name="'.session_name().'" value="'.h(session_id()).'">':''),(SERVER!==null?'<input type="hidden" name="'.DRIVER.'" value="'.h(SERVER).'">':""),'<input type="hidden" name="username" value="'.h($_GET["username"]).'">';}function
table_status1($Q,$Oc=false){$I=table_status($Q,$Oc);return($I?$I:array("Name"=>$Q));}function
column_foreign_keys($Q){global$b;$I=array();foreach($b->foreignKeys($Q)as$q){foreach($q["source"]as$X)$I[$X][]=$q;}return$I;}function
enum_input($T,$Ja,$o,$Y,$tc=null){global$b;preg_match_all("~'((?:[^']|'')*)'~",$o["length"],$Ce);$I=($tc!==null?"<label><input type='$T'$Ja value='$tc'".((is_array($Y)?in_array($tc,$Y):$Y===0)?" checked":"")."><i>".'empty'."</i></label>":"");foreach($Ce[1]as$s=>$X){$X=stripcslashes(str_replace("''","'",$X));$eb=(is_int($Y)?$Y==$s+1:(is_array($Y)?in_array($s+1,$Y):$Y===$X));$I.=" <label><input type='$T'$Ja value='".($s+1)."'".($eb?' checked':'').'>'.h($b->editVal($X,$o)).'</label>';}return$I;}function
inputm($o,$Y,$r){global$U,$b,$x;$C=h(bracket_escape($o["field"]));echo"<td class='function'>";if(is_array($Y)&&!$r){$Ea=array($Y);if(version_compare(PHP_VERSION,5.4)>=0)$Ea[]=JSON_PRETTY_PRINT;$Y=call_user_func_array('json_encode',$Ea);$r="json";}$Jg=($x=="mssql"&&$o["auto_increment"]);if($Jg&&!$_POST["save"])$r=null;$kd=(isset($_GET["select"])||$Jg?array("orig"=>'original'):array())+$b->editFunctions($o);$Ja=" name='fields[$C]'";if($o["type"]=="enum")echo
h($kd[""])."<td>".$b->editInput($_GET["edit"],$o,$Ja,$Y);else{$ud=(in_array($r,$kd)||isset($kd[$r]));echo(count($kd)>1?"<select name='function[$C]'>".optionlist($kd,$r===null||$ud?$r:"")."</select>".on_help("getTarget(event).value.replace(/^SQL\$/, '')",1).script("qsl('select').onchange = functionChange;",""):h(reset($kd))).'<td>';$Sd=$b->editInput($_GET["edit"],$o,$Ja,$Y);if($Sd!="")echo$Sd;elseif(preg_match('~bool~',$o["type"]))echo"<input type='hidden'$Ja value='0'>"."<input type='checkbox'".(preg_match('~^(1|t|true|y|yes|on)$~i',$Y)?" checked='checked'":"")."$Ja value='1'>";elseif($o["type"]=="set"){preg_match_all("~'((?:[^']|'')*)'~",$o["length"],$Ce);foreach($Ce[1]as$s=>$X){$X=stripcslashes(str_replace("''","'",$X));$eb=(is_int($Y)?($Y>>$s)&1:in_array($X,explode(",",$Y),true));echo" <label><input type='checkbox' name='fields[$C][$s]' value='".(1<<$s)."'".($eb?' checked':'').">".h($b->editVal($X,$o)).'</label>';}}elseif(preg_match('~blob|bytea|raw|file~',$o["type"])&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$C'>";elseif(($ai=preg_match('~text|lob~',$o["type"]))||preg_match("~\n~",$Y)){if($ai&&$x!="sqlite")$Ja.=" cols='50' rows='12'";else{$K=min(12,substr_count($Y,"\n")+1);$Ja.=" cols='30' rows='$K'".($K==1?" style='height: 1.2em;'":"");}echo"<textarea$Ja>".h($Y).'</textarea>';}elseif($r=="json"||preg_match('~^jsonb?$~',$o["type"]))echo"<textarea$Ja cols='50' rows='12' class='jush-js'>".h($Y).'</textarea>';else{$Je=(!preg_match('~int~',$o["type"])&&preg_match('~^(\d+)(,(\d+))?$~',$o["length"],$B)?((preg_match("~binary~",$o["type"])?2:1)*$B[1]+($B[3]?1:0)+($B[2]&&!$o["unsigned"]?1:0)):($U[$o["type"]]?$U[$o["type"]]+($o["unsigned"]?0:1):0));if($x=='sql'&&min_version(5.6)&&preg_match('~time~',$o["type"]))$Je+=7;echo"<input".((!$ud||$r==="")&&preg_match('~(?<!o)int(?!er)~',$o["type"])&&!preg_match('~\[\]~',$o["full_type"])?" type='number'":"")." value='".h($Y)."'".($Je?" data-maxlength='$Je'":"").(preg_match('~char|binary~',$o["type"])&&$Je>20?" size='40'":"")."$Ja>";}echo$b->editHint($_GET["edit"],$o,$Y);$Wc=0;foreach($kd
as$y=>$X){if($y===""||!$X)break;$Wc++;}if($Wc)echo
script("mixin(qsl('td'), {onchange: partial(skipOriginal, $Wc), oninput: function () { this.onchange(); }});");}}function
process_input($o){global$b,$m;$u=bracket_escape($o["field"]);$r=$_POST["function"][$u];$Y=$_POST["fields"][$u];if($o["type"]=="enum"){if($Y==-1)return
false;if($Y=="")return"NULL";return+$Y;}if($o["auto_increment"]&&$Y=="")return
null;if($r=="orig")return(preg_match('~^CURRENT_TIMESTAMP~i',$o["on_update"])?idf_escape($o["field"]):false);if($r=="NULL")return"NULL";if($o["type"]=="set")return
array_sum((array)$Y);if($r=="json"){$r="";$Y=json_decode($Y,true);if(!is_array($Y))return
false;return$Y;}if(preg_match('~blob|bytea|raw|file~',$o["type"])&&ini_bool("file_uploads")){$Tc=get_file("fields-$u");if(!is_string($Tc))return
false;return$m->quoteBinary($Tc);}return$b->processInput($o,$Y,$r);}function
fields_from_edit(){global$m;$I=array();foreach((array)$_POST["field_keys"]as$y=>$X){if($X!=""){$X=bracket_escape($X);$_POST["function"][$X]=$_POST["field_funs"][$y];$_POST["fields"][$X]=$_POST["field_vals"][$y];}}foreach((array)$_POST["fields"]as$y=>$X){$C=bracket_escape($y,1);$I[$C]=array("field"=>$C,"privileges"=>array("insert"=>1,"update"=>1),"null"=>1,"auto_increment"=>($y==$m->primary),);}return$I;}function
search_tables(){global$b,$g;$_GET["where"][0]["val"]=$_POST["query"];$gh="<ul>\n";foreach(table_status('',true)as$Q=>$R){$C=$b->tableName($R);if(isset($R["Engine"])&&$C!=""&&(!$_POST["tables"]||in_array($Q,$_POST["tables"]))){$H=$g->query("SELECT".limit("1 FROM ".table($Q)," WHERE ".implode(" AND ",$b->selectSearchProcess(fields($Q),array())),1));if(!$H||$H->fetch_row()){$kg="<a href='".h(ME."select=".urlencode($Q)."&where[0][op]=".urlencode($_GET["where"][0]["op"])."&where[0][val]=".urlencode($_GET["where"][0]["val"]))."'>$C</a>";echo"$gh<li>".($H?$kg:"<p class='error'>$kg: ".error())."\n";$gh="";}}}echo($gh?"<p class='message'>".'No tables.':"</ul>")."\n";}function
dump_headers($Cd,$Te=false){global$b;$I=$b->dumpHeaders($Cd,$Te);$If=$_POST["output"];if($If!="text")header("Content-Disposition: attachment; filename=".$b->dumpFilename($Cd).".$I".($If!="file"&&!preg_match('~[^0-9a-z]~',$If)?".$If":""));session_write_close();ob_flush();flush();return$I;}function
dump_csv($J){foreach($J
as$y=>$X){if(preg_match("~[\"\n,;\t]~",$X)||$X==="")$J[$y]='"'.str_replace('"','""',$X).'"';}echo
implode(($_POST["format"]=="csv"?",":($_POST["format"]=="tsv"?"\t":";")),$J)."\r\n";}function
apply_sql_function($r,$e){return($r?($r=="unixepoch"?"DATETIME($e, '$r')":($r=="count distinct"?"COUNT(DISTINCT ":strtoupper("$r("))."$e)"):$e);}function
get_temp_dir(){$I=ini_get("upload_tmp_dir");if(!$I){if(function_exists('sys_get_temp_dir'))$I=sys_get_temp_dir();else{$Uc=@tempnam("","");if(!$Uc)return
false;$I=dirname($Uc);unlink($Uc);}}return$I;}function
file_open_lock($Uc){$id=@fopen($Uc,"r+");if(!$id){$id=@fopen($Uc,"w");if(!$id)return;chmod($Uc,0660);}flock($id,LOCK_EX);return$id;}function
file_write_unlock($id,$Lb){rewind($id);fwrite($id,$Lb);ftruncate($id,strlen($Lb));flock($id,LOCK_UN);fclose($id);}function
password_file($i){$Uc=get_temp_dir()."/adminer.key";$I=@file_get_contents($Uc);if($I||!$i)return$I;$id=@fopen($Uc,"w");if($id){chmod($Uc,0660);$I=rand_string();fwrite($id,$I);fclose($id);}return$I;}function
rand_string(){return
md5(uniqid(mt_rand(),true));}function
select_value($X,$_,$o,$bi){global$b;if(is_array($X)){$I="";foreach($X
as$de=>$W)$I.="<tr>".($X!=array_values($X)?"<th>".h($de):"")."<td>".select_value($W,$_,$o,$bi);return"<table cellspacing='0'>$I</table>";}if(!$_)$_=$b->selectLink($X,$o);if($_===null){if(is_mail($X))$_="mailto:$X";if(is_url($X))$_=$X;}$I=$b->editVal($X,$o);if($I!==null){if(!is_utf8($I))$I="\0";elseif($bi!=""&&is_shortable($o))$I=shorten_utf8($I,max(0,+$bi));else$I=h($I);}return$b->selectVal($I,$_,$o,$X);}function
is_mail($qc){$Ha='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$dc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$Yf="$Ha+(\\.$Ha+)*@($dc?\\.)+$dc";return
is_string($qc)&&preg_match("(^$Yf(,\\s*$Yf)*\$)i",$qc);}function
is_url($P){$dc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return
preg_match("~^(https?)://($dc?\\.)+$dc(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i",$P);}function
is_shortable($o){return
preg_match('~char|text|json|lob|geometry|point|linestring|polygon|string|bytea~',$o["type"]);}function
count_rows($Q,$Z,$Yd,$nd){global$x;$G=" FROM ".table($Q).($Z?" WHERE ".implode(" AND ",$Z):"");return($Yd&&($x=="sql"||count($nd)==1)?"SELECT COUNT(DISTINCT ".implode(", ",$nd).")$G":"SELECT COUNT(*)".($Yd?" FROM (SELECT 1$G GROUP BY ".implode(", ",$nd).") x":$G));}function
slow_query($G){global$b,$mi,$m;$l=$b->database();$di=$b->queryTimeout();$qh=$m->slowQuery($G,$di);if(!$qh&&support("kill")&&is_object($h=connect())&&($l==""||$h->select_db($l))){$ie=$h->result(connection_id());echo'<script',nonce(),'>
var timeout = setTimeout(function () {
	ajax(\'',js_escape(ME),'script=kill\', function () {
	}, \'kill=',$ie,'&token=',$mi,'\');
}, ',1000*$di,');
</script>
';}else$h=null;ob_flush();flush();$I=@get_key_vals(($qh?$qh:$G),$h,false);if($h){echo
script("clearTimeout(timeout);");ob_flush();flush();}return$I;}function
get_token(){$wg=rand(1,1e6);return($wg^$_SESSION["token"]).":$wg";}function
verify_token(){list($mi,$wg)=explode(":",$_POST["token"]);return($wg^$_SESSION["token"])==$mi;}function
lzw_decompress($Ra){$Zb=256;$Sa=8;$lb=array();$Lg=0;$Mg=0;for($s=0;$s<strlen($Ra);$s++){$Lg=($Lg<<8)+ord($Ra[$s]);$Mg+=8;if($Mg>=$Sa){$Mg-=$Sa;$lb[]=$Lg>>$Mg;$Lg&=(1<<$Mg)-1;$Zb++;if($Zb>>$Sa)$Sa++;}}$Yb=range("\0","\xFF");$I="";foreach($lb
as$s=>$kb){$pc=$Yb[$kb];if(!isset($pc))$pc=$jj.$jj[0];$I.=$pc;if($s)$Yb[]=$jj.$pc[0];$jj=$pc;}return$I;}function
on_help($rb,$nh=0){return
script("mixin(qsl('select, input'), {onmouseover: function (event) { helpMouseover.call(this, event, $rb, $nh) }, onmouseout: helpMouseout});","");}function
edit_form($a,$p,$J,$Hi){global$b,$x,$mi,$n;$Nh=$b->tableName(table_status1($a,true));page_header(($Hi?'Edit':'Insert'),$n,array("select"=>array($a,$Nh)),$Nh);if($J===false)echo"<p class='error'>".'No rows.'."\n";echo'<form action="" method="post" enctype="multipart/form-data" id="form">
';if(!$p)echo"<p class='error'>".'You have no privileges to update this table.'."\n";else{echo"<table cellspacing='0' class='layout'>".script("qsl('table').onkeydown = editingKeydown;");foreach($p
as$C=>$o){echo"<tr><th>".$b->fieldName($o);$Sb=$_GET["set"][bracket_escape($C)];if($Sb===null){$Sb=$o["default"];if($o["type"]=="bit"&&preg_match("~^b'([01]*)'\$~",$Sb,$Fg))$Sb=$Fg[1];}$Y=($J!==null?($J[$C]!=""&&$x=="sql"&&preg_match("~enum|set~",$o["type"])?(is_array($J[$C])?array_sum($J[$C]):+$J[$C]):$J[$C]):(!$Hi&&$o["auto_increment"]?"":(isset($_GET["select"])?false:$Sb)));if(!$_POST["save"]&&is_string($Y))$Y=$b->editVal($Y,$o);$r=($_POST["save"]?(string)$_POST["function"][$C]:($Hi&&preg_match('~^CURRENT_TIMESTAMP~i',$o["on_update"])?"now":($Y===false?null:($Y!==null?'':'NULL'))));if(preg_match("~time~",$o["type"])&&preg_match('~^CURRENT_TIMESTAMP~i',$Y)){$Y="";$r="now";}inputm($o,$Y,$r);echo"\n";}if(!support("table"))echo"<tr>"."<th><input name='field_keys[]'>".script("qsl('input').oninput = fieldChange;")."<td class='function'>".html_select("field_funs[]",$b->editFunctions(array("null"=>isset($_GET["select"]))))."<td><input name='field_vals[]'>"."\n";echo"</table>\n";}echo"<p>\n";if($p){echo"<input type='submit' value='".'Save'."'>\n";if(!isset($_GET["select"])){echo"<input type='submit' name='insert' value='".($Hi?'Save and continue edit':'Save and insert next')."' title='Ctrl+Shift+Enter'>\n",($Hi?script("qsl('input').onclick = function () { return !ajaxForm(this.form, '".'Saving'."...', this); };"):"");}}echo($Hi?"<input type='submit' name='delete' value='".'Delete'."'>".confirm()."\n":($_POST||!$p?"":script("focus(qsa('td', qs('#form'))[1].firstChild);")));if(isset($_GET["select"]))hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));echo'<input type="hidden" name="referer" value="',h(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"]),'">
<input type="hidden" name="save" value="1">
<input type="hidden" name="token" value="',$mi,'">
</form>
';}if(isset($_GET["file"])){if($_SERVER["HTTP_IF_MODIFIED_SINCE"]){header("HTTP/1.1 304 Not Modified");exit;}header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");header("Cache-Control: immutable");if($_GET["file"]=="favicon.ico"){header("Content-Type: image/x-icon");echo
lzw_decompress("\0\0\0` \0�\0\n @\0�C��\"\0`E�Q����?�tvM'�Jd�d\\�b0\0�\"��fӈ��s5����A�XPaJ�0���8�#R�T��z`�#.��c�X��Ȁ?�-\0�Im?�.�M��\0ȯ(̉��/(%�\0");}elseif($_GET["file"]=="default.css"){header("Content-Type: text/css; charset=utf-8");echo
lzw_decompress("\n1̇�ٌ�l7��B1�4vb0��fs���n2B�ѱ٘�n:�#(�b.\rDc)��a7E����l�ñ��i1̎s���-4��f�	��i7������Fé�vt2���!�r0���t~�U�'3M��W�B�'c�P�:6T\rc�A�zr_�WK�\r-�VNFS%~�c���&�\\^�r����u�ŎÞ�ً4'7k����Q��h�'g\rFB\ryT7SS�P�1=ǤcI��:�d��m>�S8L�J��t.M���	ϋ`'C����889�� �Q����2�#8А����6m����j��h�<�����9/��:�J�)ʂ�\0d>!\0Z��v�n��o(���k�7��s��>��!�R\"*nS�\0@P\"��(�#[���@g�o���zn�9k�8�n���1�I*��=�n������0�c(�;�à��!���*c��>Ύ�E7D�LJ��1����`�8(��3M��\"�39�?E�e=Ҭ�~������Ӹ7;�C����E\rd!)�a*�5ajo\0�#`�38�\0��]�e���2�	mk��e]���AZs�StZ�Z!)BR�G+�#Jv2(���c�4<�#sB�0���6YL\r�=���[�73��<�:��bx��J=	m_ ���f�l��t��I��H�3�x*���6`t6��%�U�L�eق�<�\0�AQ<P<:�#u/�:T\\>��-�xJ�͍QH\nj�L+j�z��7���`����\nk��'�N�vX>�C-T˩�����4*L�%Cj>7ߨ�ި���`���;y���q�r�3#��} :#n�\r�^�=C�Aܸ�Ǝ�s&8��K&��*0��t�S���=�[��:�\\]�E݌�/O�>^]�ø�<����gZ�V��q����� ��x\\������޺��\"J�\\î��##���D��x6��5x�������\rH�l ����b��r�7��6���j|����ۖ*�FAquvyO��WeM����D.F��0�ECb�Q�<9b�hxNt��b��@pl�.'�֪�\$t�D�����������I<�\"�c+�L(1��ZEC��O	��E���.��\$&�\n#�IM&&\n�!�m�̡�t����B���洳�T����K-T����H��ŭ�� ��x�r�J��V��*�F�������߰cwq�L\$@n�t<%�T�r1߲܃��g�i�P)�L= \\@�T���,#Y�9_����N`Ѻ\"FR)�Y�;g|��i�,9X��\$�'�J=Bx��Z�@�����E�\"5F]�j�btFAk��,���'̹?Q�mE����8'���at�A����<\r��@b����P*\r�*i�u7�/�{v�Q�Z�8)���7�DI�=��y&��ea�s*hɕj�A�(꣢�\\���ni��V)��^�	|~լ��#!]�v8yRT&����2���62P�C��l&���xd!�|��9�`�_OY�=��G�[J	-eL�CvT� )�@�j-����pSk�.��=���ZE��\$\0�نkn�շ�\$���G+�I�P��.ځ� ;��qO��G%��Rj�Y[�XPf^��|��T!�;N��І�\rY�pq1�*��y~-CI|7�7�r,���7�(�̾B���;�+�����A�p����b��\r���7�\n�&b�I'�w�ZiRXl�.4o���m*�� @.��Y���ip�#���V��B��� Ha�.@G��� 0/l��:�s�^��=�H��A�[�x�`�g~L�8�H���tG�4>�<�T/�xO�'/��v`��X��/e�H/�Q��m#f��\n)���D�b`�0�\"�p��hnT��*¸&O.��W��\"*�!��ѓ��õN/՘�da�w����K�c��j�O3725�1�?z��7�w���Z�4)Ǚ�9d��]��M	��Me�m\n�-O�P	��)���)�d�\0l�vIk2jj���Z��-6�Ꟗd�l�`b\re׉u/�h�a6Z�� ¯3�c�+,��h8r���\"�dJˆ'���a}���\$�)����&��L�0���Gc/K�}6�1�\\~O�ӓ�[\\�T]\"��8�L���Pw�J�e�@�� �\0hC�W\$M/��n�!�v�L�`_zW��w������'B��W< ��=��C�Z��\r���=,X4�3�{GƝ���u+����c�N�\r� S`w�}&��{��7\\�̖ɞ֤�3�|�ps��=���\"n	{�5���L�<��X��y�<U�;����`}�CC,���-��P��8\r,���p���Y{���z*+:3j���lЯ>[� ��\n�OR΍N��-\0�`ˀ�#x�b�\nro`;Od���\0�~�)&lO����0�;��M�����#(&̎&�0��κ������N�\0�CL���P>�	����򊍎���\nh��h�ϭ���\0");}elseif($_GET["file"]=="functions.js"){header("Content-Type: text/javascript; charset=utf-8");echo
lzw_decompress("\n��B9�� 3NG89��e6�M�s�@s�LgCy� '#	�.t9M�x��C#��d��4�E��be���f0�M�C��9=7�γ����r�NF��m8�D���x(�u7F�&����(�C�q�`��d:OAH��\n��*����Aχ�E*�)N�S�������R;AP 6a��0A��#���M����	4�k7�֧��\n�F7�iu�u��U��)���~�t��Nf̝�+m��nw[�\$�#�(����Սu������/5���[����Y���\0b.��8���8�6\r�\n����B���iZ��	RY6�B�ܢn+�	�z|&\r(c��,��o<&Ȼc��/3��,KƱ�: ��C\"˳,���Q@0�k��pX�\r!\n��4��0�2�sڑ�� ٵҋ���	�]7�K���E�Ը��\n�09�(�/E1\\�)��k��0��/��'�C;�#�H<���8\r��P7(Cb�2L�#�#�2C�9*��`W��U3MӡC��t��W�,��ɵ��5N�t�,QA�.�����\"����?ڪ��0�t�J9ͩ5d�]!�N5̥Y�s���O�\0�@��d���LJ��M;��IҴ�wNS�`̪e��GU���%l�b�`�<�#�\\Ncv^��)��,�҃��4�ꣾ61j(�9\r�xީUN�2 �h�1\rON��i��֪\r�H�W�@趎z�UV�2;ZQ�\0�2��R/�l@:kB��<�^�2�[D	��r](2O��6���@!�b�����j��A��]m\$�7�â��7��`�Acj�n|b·;0����j\$0�H}l4�բ���p��u����m�բ!r�N5PQ&��(�88P/��a��\0W��^\$���^��>�HN��8H|��l7vx@�ϳ��9��_��|��o�P7�p����)\r!�4�p�Wj6D���w8C��Ada�/��`:�\r!�[6R�o�B5\\���\0�=�zO '_n\0�=��	����P�����Rq@<A�c3C�L`m[�h����C,%iv�\0��dZr��7�����-�Z,1�l�qm��<�6�c�q�	�;�\0�K��>Q�2��0#�����3�i�˫ێq�WB��]Ӝ�遒��r��8;4�<�P�ad����+�hE�R���25Tģ��M�q4�'.K��[%q�;�@��a�4Qf^!����k��W��tY�R*L��%Dp;;��M��-IQ��囖@z9mU�ɲ�C]'�ZwEB@S�V���u��'���(�A�:\0��x/�MLΉn��g���,f�t�����A�+?u����y�'�O))T+8\r�S)�!�@A�b���(K�(��<����9�ʤPZg!H`y��~�S��Ap2�B��Eh���o�8�e21���e�J��k��\"��&�a1�~�U��K�\"��Z��}+V\0��w�DY�a���f(Gm_��n��~}�pڡ���t6��'^����\nE���n�T|R����J6c'hn���R�qr#P����Uu�����[�?m�\$m�[N�9;�e�\rm�+�m�j!�M�@�i�����b��ե�V���\\۟(1�4���\"%y�I���+�x6V!x,Ъ��vD� C_a�2�P� �A3M����Q�������&�)ёG��T�t������g�HVĪ��7j\n)��Q?��\\���L���x1J~�*2�C�d���<7�P_R1q�f��Ŏe0.ʉ������Áv�a?�LÜ��5'Y?�]%Y��Ҿ�SKA�Ya�<�<����gL.���A����A2�\\��q�d�\n��;�՚)\n�jM�3	OP�a�>�ǜ�8^z/�L�\0R	,�'\r4\n�z��)q!��CY���:3�*Tb}]���D'LY�6Dt�r1�,jB�x5Bz�8�b�?�#+Z͂���հw�i�Z�J�u������ �~+f�\"h��el�xtI��/�6Ο��虤����p1q[�0x�,4F�^��\n<�B��D��}\$��:3�^�O��s�g��黖��6���t6����9<�\\C��^�:�,)_��ADA\0O�\$���Y\0Ϻ��A�<��c\r>��x=O�ud��K#�U<���j\r�сG��Q��\\\"��L-� �.�`��憌V�s1�\\��BC\"xx2#R�hN�	�6�HF[K����� ?7����,�>�Pag�h^P/��� {y��P^�o��kx2z ���~����MáH'�o�S'l��\rܝI�j���H��:qI\n����.�l��\r(P�(Z�Z��?b\$���@�k��O���P��r>����n�p4�,�w�r`�F��j�P?��E'��C��l^�����H�'�D��mB\0@�D��ү�Ι�*&-|y�e�<	�L�.R���^��>u�fA��RfZκ�->c�\0`P���P4��?� \r%����x���j�`��], ����wN��\0?N����d���Ε��ˣ��3��\rp�A���m@xnbLB�E��`D�f��QH!�6V��\0���bz1b��Ub��tZ��x��#�8��JU��\r�\0�yo����[��%g08���,0�f�V~P��:��^去1T�������oԅ��/����<��#�Q&v�23��c��9P�+B�ɇ	�M�\n�\n�F�\r�L\"���6O�u	p�Q�,���rRn�\r��r0�/�\0��1��p��l�q��M�.���\$�H�R��O/)RH#�qo�Q>]�������sH.���'��h��бgJ�k�+GR�p�����)�fR\"\r-�&}�.Jl���|O�W��<�䑂,ځl�z+��|��m˦��\rgtJ�\n���{M�,�.��lސ O2`�\$`�CS,��3)��2��k6��0�B�\n�1��*��=��8EV�\\E/RA�4#H�����/2�/�/���\0��-��y2�z��ߩ�<f���B��@�2�D� ��J(sG6Z���6Ε?r���:H���@��u�6�oC��*B��N�`Bv����^p�P\r�W7D^���8(�v�ļL��f�R�BN�!B�Q��h�lÓ	6�<�2���Gr�36��'I��+Fڑ��@����n����)HQ���Q�N`���D�t��`^�Qv|hׅ�������t��?Lq��T��I�L�ff�q�u �3�_`���I\$�@`���#|�G��#�^/+o7+��\$��*��֍U,\n�4+L�V�Cr�m�^�*���b\"i\rR\0�	f�IL�QRBO��X�(o1E�?R��#�K�kYR~����K��%�0/hP�͕�.�he�ws,d��J�\0C[.>d�fQ��&��P7'0#�y�«ѹ�!aJ(qY�SY�\"�K�^V�J�!5�oP��,@4�0Ma��=�Eac@4UV)b.�CV( ğ��cUu?���[��?�fb>�����FZ��6q����I�E�/P�5��7��\\�����O�D��(� ��-NVY�d��l��@�ok/����Q����U`_N��@����z�H��|\0�8'�\0v	�	hZ(�FO�\$	 �Wo��in<�\$�Dw0lS�\r�oi�r���hWB�Ut�n�9Bzf�[��lR�ĸ�lV\$��#r#�V�ͼ��w��CC�l��RrP3�~N\n���*�ঁd�bf�y�6�+y��(���M,܄�M0�w�*�����}�^\0�*\0�\\�4�CRu`��(�퀦�\r���?\"vW�}�	��<�b��N����i}�\0@B�J	v�|�4&�.0�p�\"@�+R��DCT՟\nr-m+<'@�	�s:оJ�:f���	<�:}3�҅�}��*0�̐��'�{x��آ ���d��x�tb}�������@ b���Ӄ�J�P67YꖫI\np����r�&o��v��	FL%�fՇP�����w�Vxt	�s��k\r��s�JԡB�#��@�|����X�4J!Ϻi8hE0�8�n_��2�D\nJՒ�S��8����H<�v���Q4L�x��Әnķ���c��/�5�vz܌9�-\"%i!:�1p<�N�+9�lp�&��Y�_ѭvV�����(�XxϾ��`������\0V��p�b���q�,�.P�������­Y�Q��_�Q��Rv��o�_����oO��Y������`M���`���)��/<�?쓓�U��b��/L�u�m�7��WU6�������(+Z�SL�[�vù� `�{�,�0\$kym�!�*��0�j�AX@�sbF����b�UO����#�@ѭ��\r�}�x����-�� bsv4�m\r��?z��ƾ��:Vyl�v��K���g��*��5# ���;/�yY�ЭUXpVz���y�{/�t1l�x阴p[��m�L�S\nzo�@f���(�ߴB�G�煥A+�|'��C	{]��=��fJ�:)`�TB0/�i��\r�!��D����	��;�0_��s@P�����(\"Ѿ1�w[ֺ��Zh�\\�o��'�=���3p|����m[Y�z��	~ղ4�k���a�k%U�,�թ��>\r\n������B{	;ޱ\0��[��[�1����:m��	(Q��PA;�i��F�7mپ2���̂�`Q�хQq�.�SV��fn�f�a6�AJ\\��F?�Ts�z�s�Xd��\\��٤�Skn��Pfv��P�1�)B�U�+��ff�6Ѵ<Dneh� ڊWq�D^	��.�Z(�Rf��]�ݮ\0�B���T�fՄ���0\rб9T?�#����wT?��W�D[`í�\r^]��U�m	D�\0�X�J���6�Y#�؆�/Q�M8d�H+�����_�]�׈�Y]�n��k��\0��(��w�4�,t>z�[��*�b2��wF03����`߮=��	�s�)ּK��*�6���հ�-�R#��@\nb8z����ݒ��:�O0�T �\\\rO6\r\0�����f��F��~k���>-�}��0����u�=���ޗ��%IX��&�~�S=�p����q{�b?ߨ�c~zv\"��>��>,�����K^�,|I��?6�˧�PˬSk�����TK�ؿp]������-���}�=#A��I�y n�\"U2\$J���ҏ�_�ADE\nu�D37�G�*��1~�OŽ��x� ө/~_s\0P��������F��>�t�����ʆ���3��%ެ�@�v%�\rk��\r\"\"hp��6V���w���ѯ6�@Aui,�Ȍ ��#���~{��������M�p�ߙ�̈́,�9��Q\0@�cH\0��uP�X��Y�=�C�K��Е�h���R{�ޔ�p�\0���'�	� 0;%�OFr�����L��m���w��<��ixb��n6V?�t�H5zx[�(�LAs^��S`|T�d�pU�SA����ɷ��[�H��<�Eb1;��lZ�C�E�A�2�σRy����%����ג3t�ɘ�)F�d�G,��p~^PtF���B'��S|��N.�%��5��vhE����dm\rN��[��BHV�\"Yň*��L�\r��1E*Kև!.�jg���n���2���S�9#[6�6]1��[U�IY��xjm����fe���jZ�Oֶn\0���3��i۔����\n�e��{h�B[1�Q�<\$7B&\$���\"-0��P�C��k�|?]D�)�U(0M2c�R*�ծ4�R�.l��� �plxM9��#�DN������`���U�\$Kt���a�������B�OC�`�8��A���9���0X�	��&�2q��g\"\n�3�(����nD��oV�L�qa/���+s6ab��+�<��	P�ǲp'�'�~Ml^wЦk4R�\r��x&�����E�:�cB�������d,p[��Jܮ�E?0��n zL��tFmѓ�eB7s2)}G0�(��G֧���Rks�B�&�U9D0K���b�A��[0��2���]I��Yn���y��mj�L��OV��٤NII��<�@9��i�;@�ϾbHt���!�1��GB��+��j�W[ �c5d�{\"���/�I���w��	�G� �( X6�b���!v��6=�CD� `�G�\"�dx{Ç,�Z*A`L�h\$3Ѓ��L,�FA��\r�`@UQX���2���\$�B�zK�^�L`J�bH��ж�`�Q2e�d U�&��@.�܇d>��>�I�����@�A��\$�KX�����4#rQ5�\$�Y0\r@#c�h�F�5�H�2A�T��?�0�0�P�:���^�<<���qy\n�l��Jm.����]yC�/a�]xcP?P9\0{�6ED�����ł���܀�ǌ�P�<ۂlC!�ʬn��s�G��e�-���\\��.�dJFVr�p��@VC�]������0�w�����Px��D����K�7�bdr��J�A2X\$��1��=H`&ؘ�%��d``Y\0^����]i7�\$������%�.p:K�����f'��l?�ɒ����ƃL\\�d'��%e@!��.�t�4�{�1ҳl�\r du6���fә:�>y�vbGr=���Ӯ��`,�XR�]*�lC9��.QG��H���\nw��R�rr)�E\"��ʀ}�/'L���J	Rʝ��蕔�%�+'͖V�g\$,��,�\"��«Y��O4��}�+�Rqfj�0*șmg�@�����L��	@Sx��iF��!�\"�u�X�5���b�k��\"c��[� �e#�o��f�~\\`�Q\$���.q�����(ޙ���i���[(����w3���{G�~E{G<<�T�KE80�Nu�a��#���L#�w����������&c�\$Y��T_�m�f����bS�x��e>��\n\$�A�#^<c��!|���R���󸣝& 	W�J{ ��r:�LN�\n��v��P��b!h�|��f�NҞ\0Ҕi��n���!�\n� �@*Sh_�Z�� \r�|AC������ʄ�(J}a�W�B!!TIR��K�\\�<Ԇ�a[1���ɧ�\0w*�%�EP)Q\\h4Y=\\G��\0���2�B�I̍B�!��NmY�\0��07�a�%e�{��w�0�!z��������@���G�:a���>H\0~��p���*W�Ƶ2�N�97���Ս�z���ɟ3�x�����%�����%ץ�\0\\/��^|`t��@P�I�8-����\\�hO^G�p��ٌ�1�ḆA�,@A�B���.�H=����~ ,�\$�HBB�&�*��\n&��-ai&�KG�3\n79QoE��J�o��(�b�d0!mMhT�`���F\"�pAwl�z�%�a�}�ɟ��Xǵf�'z��I�>ڏGkLH~�ޕr`���55��-���Q\$�},Nn���Sj6X���	������ą=��>P�f�ʠ�-�\n���y��}��.')LCuHN��Ka\\�:dK��76����\$Rz�T��N���-�!5ר@�\0002����O�h�q�T.h��pz���u{��@�_���C<���<��HUtҖ]�y*���8�y�.hI��(Ǒک�ڲ���ֹϣ��@ahyv�T0U�3]Qf�Rx�0��O*�D�DR��\n�D�ƀS��c������Ѕ�H6�ԃ�������]�G�I懝b'�&w�l�o�*�}�̌q6Ej��K���W���z���l��\"\"Ջ1����*�0	�@4\0�.K�aS�p����L��|���c**\rV��'?�F�� �H���T`�U��@���*�5�4��K�8��I?_-c�����)��alUd�C���0=X	�d�o���Eѭ�,�hv�&ֳSF&M���f��5a�1.��|�d0)�=���*퓫�w�:=�	� �OCMS�t��\0\n%QBR��#�\\0����+Q`����\"R�Y��L�d�G����;��C���,f��Zߠ��M!���`��Gh�vw1�<Yu[�F�&(������U��Z�&�#��͊ܘJ��ז-��lV���(:ʟ�)?lP͋#�b�\"}���Y�H��-�-�y���>���>�F){a��_S�0�,o�LxZ�qO�:���p�e�NI��~��=@b`���js������T����6�':V¡hg�	p-��,EMn�E��:\0t��f�Y��8�õ�5������^�L%��>�草�)���2\$��Z�t��]*Z��(P�@��8��?�>��P͌���=Q�; Y�Z�x�sD�3vak4�f�wS0K��۪#Ä۷:4D��+P�}�+���c�]\0M��`xy.W_8h)��x� �X&HՊTl�|-U=��\\³wV�:b���WW�\0��y+c�'�2�j^�kc�y������\$����J��Ё��c�w}�ƀ���۶�N��XB\$��1V����\0�`��E;���x��]�᧠\0��^�x���\"we�^��gY��Vu?��g�(��Pm\"�F�K��*��n�k��Y�y��zW�KE��@�}�n\"�Kj=��g\"�_,���cRP;^���3�O���}+�:SZcG!R��T\r�w��5�W~,`1c͂U��z���H��@N�S��*�ܲ%����l��O����+۷�z?W�[��U�\n�7�i�����'��>Ĺ}B����S���<�!?Ž��G�^\0��0�`���L&�`w���t�h~x�o���,�	�~G\r�p� ��G\r�Xy@�o���I8L��/�Qo�)�~x���t�s�>��C9��݇l4\r�qg]����U�':of��pD�'6�6�����LIaS\rTEU��){5�6�������`UZ\$��\"N\\�C�lH�D�#�R����7�:to�ZElA�'���B	ߞ=�Oý��.�B��.!e �v�؍(�/Pq+����UT�֮/W��,�'�H�dk��-~�!�k5���l^����,�`�,6�d ��),���L���1�\"X�/vq@\r���F�Y�ݑ�8��[�� gq\$�{<�ajMl�zG�.:u�����+���V�šH�'�	��\0��p��/���0!J�Z���5�\n�b�FQ2%QH�-\nR�%HM�ŐLK�6��S}� �����W�ۂ�0�e������[�:���(Y����ld9�q���UZ�E@'\r̑���U8މ�3x���5,����P���7�l��f���F��[�6ea��6V���e�DI¿%IY���!a���͢8�(� ��<�|�����N�k�wt�d\r�2\"l}�12:���y�)�=ͪ��DR*m�{�CSQ��č�=�����A��2\$�yy�R͟���0U#�s6{ܭ�h�B�!\$������O�N�fC0v�Zu�f�3�����\\S�y�z��������Nc�TLiprEõ.|�'GVL���X 8-�^�:\"C^��A`��C�Z}+ϩv6�^�d��_^��&=��\"�����8���TR)H9Q�ˑ`<�e�.�o�\r(�x����\0�Q�LP,�FGi����4�����i�U��Y�@g9fΚnJ�%1�,Ɓ�`�����5f���8\0N�\r��@ v�I��%i~��j\0����m�J��O�% cH\n)0��O�X�q�pSQ�0`�1is��V%§����H\0�����:�}��=��P�8���{�|`.W\$��qf�B��.J�):�n�����k���Ԁ��s��� mx�ů\n�	]fa#Ϊρ����/^����ۏ���1�]ک؀����oM��hc��a5�kwa�v��JJ=�l�c::&�����:�*\0S����3�\n��Η�(�>��]�Gp\r�T�x��w�YfiG�]�)�\0�\"�@4,�{���9[E/��r&�1��c�Q����oG�=Qa���64ˁ��3��%��A�Ҏ�Ʋ)\$KD��X�t�À���3o�t��U�\$m���.��\$	-�DK����ß<�q�����d8��ŭ�Ψ��O��ms@^�0RR�\0I*�p_z��c�\r���i?�9��g6�ˀ:	��V��g����ƣ�EKHǚ�@�ѣL��k\"~`Q�D3g����\nD���\\-0���a�Pyb�	@E�mB����}P���`jŽp9b�-��h+m��p;�G�&�5��%3Wlct跼�� z2v�3�I�E_t�7w�&x)0��6	�é�b�Q����j�|*t��U�rU�r��'�c��]PTb�&geG@fѪ�ݢ46\\�;L��3��;�a�A.�l01h_�2��0:x�[ûŗ� e�o���8���-��\$=L�q?9�\\h��3�!����y5����a06��ʞ&�1n+;���`f�1�{���a���(	����@��he�s�96w��mI��t�ν�:��<�=��� �Gk��=<��Ǉ=qϕ��P�y]�������Nh%���\"�[;�S���5C�fd�8�9�ڃ[tb�K��G��AǄV!��/�|hՏo�@jU�D�\0�e�����We6�|A����L��P۾��Ԋ��y�G��ݼK����Y�Ζpn���~̆�\0�B�jm�o7�d+}I�2��:��[x\$P�a�ͪ���9ޕ�Z���3Iy��^\"P�.P.(\n[��b�s<�,�9���_��tc�`t阪�^\"��|eR�7�46Q�� �P(vYکb=0Q^䜾����TX@� \"�h\0P��kj+B2�7��E�A]�v����8S�	�J�P�^@{�K[�ر��S�󗡊ϷA�zo��&�p�~���\$�2R�f_��aOl�|ЈD�X{��0%A�E���� ���J�x�c�ٺ;p\0Om��ؾ�t�<Yfm����@�\$�n�UL���v'��[�y���^ֿ@���t9\n/�n�?����KC�nҩ��*=ȃz��~�6�X}�M�e{ޕ�,�f�.�q�ۚ�Ǎ����2G}XY�eW7��]��&;ҴA�(��\0ը��Vr�x@��(�'xπ�p�aD<K�	�\\�0;����AD��kՖVIXA|M�(C�2-������\0O���VrX�#�\r4��~g��n�M��|O��7��P�\n gb�x�}������,�{��,����0�o��{\0.�6K�p;Xɖ�O;P��B�6��+%T��(�X4�!B&i|V�[E&R���FoC�hȞfp~�\rT��G��g@��\$�zE±fk��a�Ǌs�+����o\n�A�0����WP�Y>�BO-}�j�Fa��Z��T#��>�Б��ф:������\"�ٰ{Hh��sv��jt��@�\r2�Iyh��7���)��C�&p=�	?_EE��+_�G����Ƅm�����[t�+b}����+���������~\n�O��G�\"M�m��֙������Sԭ��D�\n��'@��َ+_���F���C,�2:��� Ա�w���ޕh���IgH�Ѳ��o�gy�k���	w�p�ާɛ!��}V�r��Z�z������Jt�y_c)�N�w\n���o�<c��R��u +i�W�p�6B�S����s����_���z7<�뎹T��1p���=t �%���%�'?������?8�̆(y�<��<��b	T��K?ߩkPȯɆ/Q'�|g\0u<�-�5q���q�` ��^��x�a�g;����n\0����gN|\\B�����r̻�j8��V�G���u�8AqCϞ��!?^��/�ۜ@4�6����������D:�͇���Gd�!���j\\[���E��k����&-#R\n�	b���2\0��QW.��sZY� ���3�A�(�P*��:����!@a��J>�m�\0�\"W�J��� *���I�\rDޠ�\rN���YM�-���{���`��D�|{ˤ���:�1�txF��:��@9<���A��o4A�<����ĄF�!?�<?�	cG5\0�sc�\$�#\0\$l�bhR3_A'�d杜���`ΣLE��fM������.���'�=lq;lY0���/u\0��g�?�;8�5j�@���o��B�ǆ�@X/df	@f	�~���\n�%̻���CAA������\rrd�<�f��O8��>�[1�`��RN���a���6��Y\\���2�;�xo�l\r��4y�R@H�Cm�{�چy��\0Z��.�Z�H��g����������8�m�t����m�tr�Q�rA��d�����շZ`R,���m:��=��'��� ��6�w��ڬd�4�ڬG�\\��,/�&��������� ���JIQ�/��΄(M���)͑\0P������ �p���Ĉ�0heC��N�^��.��Bq\nrg0��	\n!�ϫ��\n�)`/���!OP�9��?8�)N�7��'�����!�#r�If�U	T%�ģ�+PP�\n����:�Z��*�0���r�<�����r'�1�t��x�|��3��e\"PO1�#��S	Fu8�#h)(�֏-AF0{��?&��0�>e�t���p�!�p]9�k�[��\r���-��S� O�V���T�8������WA��b8�1ַ��a�����֒�Yld_Ch�6�����č����:�6+�8���9c�0�=�/h\0�ư�����WL=�l\n��\0��S�%a��gae;�t1+���Z��]x�n�4�~�O����q��;��A9�\n�a�j�\\G�O�@�H��Ď4F��+ds(c\$�01Q%�\"b����C.��B\r������g'T�J��2�S�^�J&/ɒ�ߊ�&[�X��'�d\$��(�:?<�@ ?D�J�(V�D��A����Bj��<&]E݉&\n��ɲ(�T�������U���\$`>�1�ೀTR&'d�_\0QQ\"�+��j��V���>ap\r�\r1OQT(�RTUq\\Et�V'+��n\n8-H��#�f��^aO�(�q_\"\0�K(�D�����	��H~�-��!J�`�,�nm��\0���\$:�t�cE� ��ޑ�S�N��*��W��E��\\&����S&���:\0fđ���P��BEX�*��a	�X�Y�����3��8q1���`�`��C(�v\",a�U���\r�o8�C �\0�\"��1�p;p\r� )������'�K����6��-ьE��`�\0�������d��N!�-�BE�J\$�8F�'�kq����kYm�	�A@S`��~\$\$k`��f\0�\0lp�����x����s���Ƥ�`����JB=@�X�y��\n,f��(o�f9�(���\$A&ظ�C�,���Dp,��3����J��g�6	a�Q�	��q@��m�EC��v�dH/�D�0����:*��������̱g<A�΢H�	���C���E�\"�Xq������p�R�',�z��6	B��������*��h,�ӕ���M��Z6�,�#JP(T>b�OD=���̠\r�D4PJ�)h�z��%���3�+��`�5>\$`9����;g͸���z�\0R�A�B��������\n�T4��x�7�̪� ϵ黺\$:�8V�W�x��p�:�!� �A�](̂��#:����0e�ca���S!x����P-c�h�쉐�A~��2SĒ4�R�*i�S|�J�\$��w�ÂTH?ﴒ�x�U8�d`6騅*;tME�ǔF���q|v�\n�I��-���D[�0𙪭aM��C�Ka�0l��ά������4����Am�+�>�U#�A�[�UJ�4�8@�6�nA��%�;\n��z�H�^�%q�dHԀod�\n'H�����ʛb�C�J������0�@`l�\"�}�Ç�jJTfђ�[�#�)�q�h�%��j8�wBb�%���j����L\0� ��,��\0.�L��p��H\n���-��\$���!��(f�I���1r\r�y��%������ู��R*\0�@6JJ������:�,�E��\nL_E����a���#0:+�I�DM1��)�: ! ��j7Q	�,�:(�.J;8\n@ �����*%��6��tt1��>;���:̡q����w��J�3�7�K �t����d��7(Wa/х�Q̯2��]b,�ǒ�4����\n�+13��Tsc�J�����I#y�7����k��I1�F力�,�eĿ��0��DcD`q��g�r�R���dZL�Ro%�E0h �dJ�?�!��2�!��r\"��ĥG�\$k�@�=.B��0}���*9���Ӏ����2nå'`?._+�,�艸-T�����(t2쟫`c����K�A�.`�:Gq���O�R�(�d���`&J�.@���Kc퍸��[.Ϸ��y;���_�a/�Ɣ#/l�7|Un���S����(���B���Uq3o�F�3��K�S)P�\0Z/�.���Ǥ�{C�{�\"83p ���&9�#�L�tR�D+,�'��\r�ĳq\r8i-kzB|9�Pۣ�o|H�:B�ѝ.��hDc.��I������#)p����t��-\"=�\n����-У��( h�Ln昹R�_@:��< %4Ȥ/�.��1���2d��'KX��FiK`,�?s*���d@�<`ƻw��L��x#�L�i�p��A��S�icU&�\r3\\�pF�����q/�Q\\�u�9�:Nx��5	��B�C��x�E4d���Q��Fs\\Yp��o3����KP��S;�=*dn����e(�RHj�~��3d#r�ɰq��B�AI�	�ȃb�ԿY�sM/�&��K�����h�3+\\�h�̚L��-����Qnz�(A�M����p�:��\\(��N*곬C#�\\��2,\0�g*/�A\n�E-?/3*�\rT��������_�j��NBR�&�<�#�o�@0�K�!7tܳ��9T�3T��e݆Nh�{��a�\$̋�L2�Ć[�2In���L����o�����XȢqzc�G�2+@@\0�p�\"�����󲛊�q���*��)3b����4��gp��\$:�fBs�L�4�2�v�\$&.\rN�2��wN�3RN���<�rb��5\\�sWL�;��Ɔ50S�3�,@QuJ�7a\\��8�D��\n8p��j6�5��̶����q��ODiQ�V͛1&���z9�?f���a��i�)�㝦3���l7C=��5�,�Фp\0�\$ �d���\"�ǧ�j}M�<��#\\�]�E� 7�����H(´�LCվt�01��(�j�`2�fS�f���)�ۭ�\\�/P*�*1�&9K@��S��\\��c�<�A(���L�9���uA\\�E��&�����)�/c-P]A����Nh(�\rVP,� �:�t �Au	�<Р�#�#��BSZ/¾��CzJ}��\$h挢  v ]��� ��6��ː\"؀ß�*E�I�:�Z�c������k�N��Bv��,LiC��'>t�xg2PXD3t�C�8�E�|5�%���BF���<��:���N��l�1\0�-����?��+,�+Q+D���>v�@@���� ���h	�#=Hp�D�\r\"z�\0���	\"��A��@!�p�5k�\0�+`\r�h1*i�,\\�OY�.@�/��u����u�;&���v] ���vZ��)ZF�s������+��� @&�H�b�R!���Gȋ\0���`��1�����Cb��j&�=d�<�4p� 	�/��X����8�Z�	�T��t�Q�`���!z�&��� �\0��;84�6�^rC�' 0�FǑ-��8�<���?'�k���2�SnQ�|pbU��\0N��i�5\nrhЋ�[�n�	�_�l�D\0������)7J���0�e�P0B�˾�F`��-��42��U�|E���R�i�/ ����\0C�[	�&,���%S��8�?�D��`�<����Q_���KSa�/�s�`��5i���K��b�L-0�\0�I��+�\$��[;����M�.�4mK�t�QM����\n�M�5p5@�VBL=��+�l�5�ɉAN;Tt�\"�Z��چ��8A\0��1���ˋoMe8&*S�b���\0ڕHV��PFN0#��S�=:F.S�M�=��Ӱ�COeZ\$`\"yn��S�F�R��H}�͖�:�M�w�@��4RZ�_�\r�a�7%����h#-6KΉ%NM1G�SI�f����]DGҪc1h�Y��t��T�5�A�}Er5S�\r�2����9A S�O��4�I�*�bS���!_TAHEEc�\0OO�@�ԉOM\$�j���8�+T�RН&:��Im6j�Sly*���\0��:���mP ���l�#�\$L�Bӄ%8T��O�u��QmJ�9?\n�v�@�c��qO�_��h�b�������P�&�R���\$l���Ur��,���������?I�1��RL��%\0��D4z�>�����,{M�܅�U�R5<�q�A�\0t!eI���K�.��H�\0 g��aV�V��ծ{ |��Vh�Cՠ�ZN�իV��j�UЖa%�\r�;��R�KА��t�#Xҩ��*���`4����;@�2�m=�����t�yWџbT����}�Ҁf�C��.���ko�B\n��4/Q�q�βc`	!��]	\n.��#!\\@N� �ap�Z=!05t���Ƈj�=+[YP�NC)� \"��U\"U,6�X�u��*m�_Nf�o�H�0KiF볚��Gu�WUL�Gp'ĸ���\"�M���U����K	r,����I�^��YEh�����vVJj����W�q��G��R�����Sp\na��,5�� ����1�Rz6�Lp/&K�����4KX+A&��\\��*�JP�H\"gy�\\=Pμ�j������=.	�-���\\�,U'@�P�t�{Q*a5�BP���^@���.������/S��.�i]��!΀�H���EOV�����y�&JJ4x\08W�c%?U�'^D���,�R���L��,+���Nw�kg�V����=Z�+��L[���F����]4[B�K72�)�s)��2E����o[z,�Đx��jU���[}r�,XA|tʩ�\\,���0��\0");}elseif($_GET["file"]=="jush.js"){header("Content-Type: text/javascript; charset=utf-8");echo
lzw_decompress("");}else{header("Content-Type: image/gif");switch($_GET["file"]){case"plus.gif":echo"GIF89a\0\0�\0001���\0\0����\0\0\0!�\0\0\0,\0\0\0\0\0\0!�����M��*)�o��) q��e���#��L�\0;";break;case"cross.gif":echo"GIF89a\0\0�\0001���\0\0����\0\0\0!�\0\0\0,\0\0\0\0\0\0#�����#\na�Fo~y�.�_wa��1�J�G�L�6]\0\0;";break;case"up.gif":echo"GIF89a\0\0�\0001���\0\0����\0\0\0!�\0\0\0,\0\0\0\0\0\0 �����MQN\n�}��a8�y�aŶ�\0��\0;";break;case"down.gif":echo"GIF89a\0\0�\0001���\0\0����\0\0\0!�\0\0\0,\0\0\0\0\0\0 �����M��*)�[W�\\��L&ٜƶ�\0��\0;";break;case"arrow.gif":echo"GIF89a\0\n\0�\0\0������!�\0\0\0,\0\0\0\0\0\n\0\0�i������Ӳ޻\0\0;";break;}}exit;}if($_GET["script"]=="version"){$id=file_open_lock(get_temp_dir()."/adminer.version");if($id)file_write_unlock($id,serialize(array("signature"=>$_POST["signature"],"version"=>$_POST["version"])));exit;}global$b,$g,$m,$ec,$mc,$wc,$n,$kd,$qd,$ba,$Rd,$x,$ca,$me,$of,$Zf,$Fh,$vd,$mi,$si,$U,$Gi,$ia;if(!$_SERVER["REQUEST_URI"])$_SERVER["REQUEST_URI"]=$_SERVER["ORIG_PATH_INFO"];if(!strpos($_SERVER["REQUEST_URI"],'?')&&$_SERVER["QUERY_STRING"]!="")$_SERVER["REQUEST_URI"].="?$_SERVER[QUERY_STRING]";if($_SERVER["HTTP_X_FORWARDED_PREFIX"])$_SERVER["REQUEST_URI"]=$_SERVER["HTTP_X_FORWARDED_PREFIX"].$_SERVER["REQUEST_URI"];$ba=($_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off"))||ini_bool("session.cookie_secure");@ini_set("session.use_trans_sid",false);if(!defined("SID")){session_cache_limiter("");session_name("adminer_sid");$Mf=array(0,preg_replace('~\?.*~','',$_SERVER["REQUEST_URI"]),"",$ba);if(version_compare(PHP_VERSION,'5.2.0')>=0)$Mf[]=true;call_user_func_array('session_set_cookie_params',$Mf);session_start();}remove_slashes(array(&$_GET,&$_POST,&$_COOKIE),$Vc);if(get_magic_quotes_runtime())set_magic_quotes_runtime(false);@set_time_limit(0);@ini_set("zend.ze1_compatibility_mode",false);@ini_set("precision",15);function
get_lang(){return'en';}function
lang($ri,$ff=null){if(is_array($ri)){$cg=($ff==1?0:1);$ri=$ri[$cg];}$ri=str_replace("%d","%s",$ri);$ff=format_number($ff);return
sprintf($ri,$ff);}if(extension_loaded('pdo')){class
Min_PDO
extends
PDO{var$_result,$server_info,$affected_rows,$errno,$error;function
__construct(){global$b;$cg=array_search("SQL",$b->operators);if($cg!==false)unset($b->operators[$cg]);}function
dsn($jc,$V,$F,$wf=array()){try{parent::__construct($jc,$V,$F,$wf);}catch(Exception$Ac){auth_error(h($Ac->getMessage()));}$this->setAttribute(13,array('Min_PDOStatement'));$this->server_info=@$this->getAttribute(4);}function
query($G,$Ai=false){$H=parent::query($G);$this->error="";if(!$H){list(,$this->errno,$this->error)=$this->errorInfo();if(!$this->error)$this->error='Unknown error.';return
false;}$this->store_result($H);return$H;}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result($H=null){if(!$H){$H=$this->_result;if(!$H)return
false;}if($H->columnCount()){$H->num_rows=$H->rowCount();return$H;}$this->affected_rows=$H->rowCount();return
true;}function
next_result(){if(!$this->_result)return
false;$this->_result->_offset=0;return@$this->_result->nextRowset();}function
result($G,$o=0){$H=$this->query($G);if(!$H)return
false;$J=$H->fetch();return$J[$o];}}class
Min_PDOStatement
extends
PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch(2);}function
fetch_row(){return$this->fetch(3);}function
fetch_field(){$J=(object)$this->getColumnMeta($this->_offset++);$J->orgtable=$J->table;$J->orgname=$J->name;$J->charsetnr=(in_array("blob",(array)$J->flags)?63:0);return$J;}}}$ec=array();class
Min_SQL{var$_conn;function
__construct($g){$this->_conn=$g;}function
select($Q,$L,$Z,$nd,$yf=array(),$z=1,$E=0,$kg=false){global$b,$x;$Yd=(count($nd)<count($L));$G=$b->selectQueryBuild($L,$Z,$nd,$yf,$z,$E);if(!$G)$G="SELECT".limit(($_GET["page"]!="last"&&$z!=""&&$nd&&$Yd&&$x=="sql"?"SQL_CALC_FOUND_ROWS ":"").implode(", ",$L)."\nFROM ".table($Q),($Z?"\nWHERE ".implode(" AND ",$Z):"").($nd&&$Yd?"\nGROUP BY ".implode(", ",$nd):"").($yf?"\nORDER BY ".implode(", ",$yf):""),($z!=""?+$z:null),($E?$z*$E:0),"\n");$Ah=microtime(true);$I=$this->_conn->query($G);if($kg)echo$b->selectQuery($G,$Ah,!$I);return$I;}function
delete($Q,$ug,$z=0){$G="FROM ".table($Q);return
queries("DELETE".($z?limit1($Q,$G,$ug):" $G$ug"));}function
update($Q,$O,$ug,$z=0,$M="\n"){$Si=array();foreach($O
as$y=>$X)$Si[]="$y = $X";$G=table($Q)." SET$M".implode(",$M",$Si);return
queries("UPDATE".($z?limit1($Q,$G,$ug,$M):" $G$ug"));}function
insert($Q,$O){return
queries("INSERT INTO ".table($Q).($O?" (".implode(", ",array_keys($O)).")\nVALUES (".implode(", ",$O).")":" DEFAULT VALUES"));}function
insertUpdate($Q,$K,$ig){return
false;}function
begin(){return
queries("BEGIN");}function
commit(){return
queries("COMMIT");}function
rollback(){return
queries("ROLLBACK");}function
slowQuery($G,$di){}function
convertSearch($u,$X,$o){return$u;}function
value($X,$o){return(method_exists($this->_conn,'value')?$this->_conn->value($X,$o):(is_resource($X)?stream_get_contents($X):$X));}function
quoteBinary($Wg){return
q($Wg);}function
warnings(){return'';}function
tableHelp($C){}}$ec["sqlite"]="SQLite 3";$ec["sqlite2"]="SQLite 2";if(isset($_GET["sqlite"])||isset($_GET["sqlite2"])){$fg=array((isset($_GET["sqlite"])?"SQLite3":"SQLite"),"PDO_SQLite");define("DRIVER",(isset($_GET["sqlite"])?"sqlite":"sqlite2"));if(class_exists(isset($_GET["sqlite"])?"SQLite3":"SQLiteDatabase")){if(isset($_GET["sqlite"])){class
Min_SQLite{var$extension="SQLite3",$server_info,$affected_rows,$errno,$error,$_link;function
__construct($Uc){$this->_link=new
SQLite3($Uc);$Vi=$this->_link->version();$this->server_info=$Vi["versionString"];}function
query($G){$H=@$this->_link->query($G);$this->error="";if(!$H){$this->errno=$this->_link->lastErrorCode();$this->error=$this->_link->lastErrorMsg();return
false;}elseif($H->numColumns())return
new
Min_Result($H);$this->affected_rows=$this->_link->changes();return
true;}function
quote($P){return(is_utf8($P)?"'".$this->_link->escapeString($P)."'":"x'".reset(unpack('H*',$P))."'");}function
store_result(){return$this->_result;}function
result($G,$o=0){$H=$this->query($G);if(!is_object($H))return
false;$J=$H->_result->fetchArray();return$J[$o];}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
__construct($H){$this->_result=$H;}function
fetch_assoc(){return$this->_result->fetchArray(SQLITE3_ASSOC);}function
fetch_row(){return$this->_result->fetchArray(SQLITE3_NUM);}function
fetch_field(){$e=$this->_offset++;$T=$this->_result->columnType($e);return(object)array("name"=>$this->_result->columnName($e),"type"=>$T,"charsetnr"=>($T==SQLITE3_BLOB?63:0),);}function
__desctruct(){return$this->_result->finalize();}}}else{class
Min_SQLite{var$extension="SQLite",$server_info,$affected_rows,$error,$_link;function
__construct($Uc){$this->server_info=sqlite_libversion();$this->_link=new
SQLiteDatabase($Uc);}function
query($G,$Ai=false){$Qe=($Ai?"unbufferedQuery":"query");$H=@$this->_link->$Qe($G,SQLITE_BOTH,$n);$this->error="";if(!$H){$this->error=$n;return
false;}elseif($H===true){$this->affected_rows=$this->changes();return
true;}return
new
Min_Result($H);}function
quote($P){return"'".sqlite_escape_string($P)."'";}function
store_result(){return$this->_result;}function
result($G,$o=0){$H=$this->query($G);if(!is_object($H))return
false;$J=$H->_result->fetch();return$J[$o];}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
__construct($H){$this->_result=$H;if(method_exists($H,'numRows'))$this->num_rows=$H->numRows();}function
fetch_assoc(){$J=$this->_result->fetch(SQLITE_ASSOC);if(!$J)return
false;$I=array();foreach($J
as$y=>$X)$I[($y[0]=='"'?idf_unescape($y):$y)]=$X;return$I;}function
fetch_row(){return$this->_result->fetch(SQLITE_NUM);}function
fetch_field(){$C=$this->_result->fieldName($this->_offset++);$Yf='(\[.*]|"(?:[^"]|"")*"|(.+))';if(preg_match("~^($Yf\\.)?$Yf\$~",$C,$B)){$Q=($B[3]!=""?$B[3]:idf_unescape($B[2]));$C=($B[5]!=""?$B[5]:idf_unescape($B[4]));}return(object)array("name"=>$C,"orgname"=>$C,"orgtable"=>$Q,);}}}}elseif(extension_loaded("pdo_sqlite")){class
Min_SQLite
extends
Min_PDO{var$extension="PDO_SQLite";function
__construct($Uc){$this->dsn(DRIVER.":$Uc","","");}}}if(class_exists("Min_SQLite")){class
Min_DB
extends
Min_SQLite{function
__construct(){parent::__construct(":memory:");$this->query("PRAGMA foreign_keys = 1");}function
select_db($Uc){if(is_readable($Uc)&&$this->query("ATTACH ".$this->quote(preg_match("~(^[/\\\\]|:)~",$Uc)?$Uc:dirname($_SERVER["SCRIPT_FILENAME"])."/$Uc")." AS a")){parent::__construct($Uc);$this->query("PRAGMA foreign_keys = 1");return
true;}return
false;}function
multi_query($G){return$this->_result=$this->query($G);}function
next_result(){return
false;}}}class
Min_Driver
extends
Min_SQL{function
insertUpdate($Q,$K,$ig){$Si=array();foreach($K
as$O)$Si[]="(".implode(", ",$O).")";return
queries("REPLACE INTO ".table($Q)." (".implode(", ",array_keys(reset($K))).") VALUES\n".implode(",\n",$Si));}function
tableHelp($C){if($C=="sqlite_sequence")return"fileformat2.html#seqtab";if($C=="sqlite_master")return"fileformat2.html#$C";}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
connect(){global$b;list(,,$F)=$b->credentials();if($F!="")return'Database does not support password.';return
new
Min_DB;}function
get_databases(){return
array();}function
limit($G,$Z,$z,$D=0,$M=" "){return" $G$Z".($z!==null?$M."LIMIT $z".($D?" OFFSET $D":""):"");}function
limit1($Q,$G,$Z,$M="\n"){global$g;return(preg_match('~^INTO~',$G)||$g->result("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')")?limit($G,$Z,1,0,$M):" $G WHERE rowid = (SELECT rowid FROM ".table($Q).$Z.$M."LIMIT 1)");}function
db_collation($l,$ob){global$g;return$g->result("PRAGMA encoding");}function
engines(){return
array();}function
logged_user(){return
get_current_user();}function
tables_list(){return
get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view') ORDER BY (name = 'sqlite_sequence'), name");}function
count_tables($k){return
array();}function
table_status($C=""){global$g;$I=array();foreach(get_rows("SELECT name AS Name, type AS Engine, 'rowid' AS Oid, '' AS Auto_increment FROM sqlite_master WHERE type IN ('table', 'view') ".($C!=""?"AND name = ".q($C):"ORDER BY name"))as$J){$J["Rows"]=$g->result("SELECT COUNT(*) FROM ".idf_escape($J["Name"]));$I[$J["Name"]]=$J;}foreach(get_rows("SELECT * FROM sqlite_sequence",null,"")as$J)$I[$J["name"]]["Auto_increment"]=$J["seq"];return($C!=""?$I[$C]:$I);}function
is_view($R){return$R["Engine"]=="view";}function
fk_support($R){global$g;return!$g->result("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");}function
fields($Q){global$g;$I=array();$ig="";foreach(get_rows("PRAGMA table_info(".table($Q).")")as$J){$C=$J["name"];$T=strtolower($J["type"]);$Sb=$J["dflt_value"];$I[$C]=array("field"=>$C,"type"=>(preg_match('~int~i',$T)?"integer":(preg_match('~char|clob|text~i',$T)?"text":(preg_match('~blob~i',$T)?"blob":(preg_match('~real|floa|doub~i',$T)?"real":"numeric")))),"full_type"=>$T,"default"=>(preg_match("~'(.*)'~",$Sb,$B)?str_replace("''","'",$B[1]):($Sb=="NULL"?null:$Sb)),"null"=>!$J["notnull"],"privileges"=>array("select"=>1,"insert"=>1,"update"=>1),"primary"=>$J["pk"],);if($J["pk"]){if($ig!="")$I[$ig]["auto_increment"]=false;elseif(preg_match('~^integer$~i',$T))$I[$C]["auto_increment"]=true;$ig=$C;}}$wh=$g->result("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($Q));preg_match_all('~(("[^"]*+")+|[a-z0-9_]+)\s+text\s+COLLATE\s+(\'[^\']+\'|\S+)~i',$wh,$Ce,PREG_SET_ORDER);foreach($Ce
as$B){$C=str_replace('""','"',preg_replace('~^"|"$~','',$B[1]));if($I[$C])$I[$C]["collation"]=trim($B[3],"'");}return$I;}function
indexes($Q,$h=null){global$g;if(!is_object($h))$h=$g;$I=array();$wh=$h->result("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($Q));if(preg_match('~\bPRIMARY\s+KEY\s*\((([^)"]+|"[^"]*"|`[^`]*`)++)~i',$wh,$B)){$I[""]=array("type"=>"PRIMARY","columns"=>array(),"lengths"=>array(),"descs"=>array());preg_match_all('~((("[^"]*+")+|(?:`[^`]*+`)+)|(\S+))(\s+(ASC|DESC))?(,\s*|$)~i',$B[1],$Ce,PREG_SET_ORDER);foreach($Ce
as$B){$I[""]["columns"][]=idf_unescape($B[2]).$B[4];$I[""]["descs"][]=(preg_match('~DESC~i',$B[5])?'1':null);}}if(!$I){foreach(fields($Q)as$C=>$o){if($o["primary"])$I[""]=array("type"=>"PRIMARY","columns"=>array($C),"lengths"=>array(),"descs"=>array(null));}}$zh=get_key_vals("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ".q($Q),$h);foreach(get_rows("PRAGMA index_list(".table($Q).")",$h)as$J){$C=$J["name"];$v=array("type"=>($J["unique"]?"UNIQUE":"INDEX"));$v["lengths"]=array();$v["descs"]=array();foreach(get_rows("PRAGMA index_info(".idf_escape($C).")",$h)as$Vg){$v["columns"][]=$Vg["name"];$v["descs"][]=null;}if(preg_match('~^CREATE( UNIQUE)? INDEX '.preg_quote(idf_escape($C).' ON '.idf_escape($Q),'~').' \((.*)\)$~i',$zh[$C],$Fg)){preg_match_all('/("[^"]*+")+( DESC)?/',$Fg[2],$Ce);foreach($Ce[2]as$y=>$X){if($X)$v["descs"][$y]='1';}}if(!$I[""]||$v["type"]!="UNIQUE"||$v["columns"]!=$I[""]["columns"]||$v["descs"]!=$I[""]["descs"]||!preg_match("~^sqlite_~",$C))$I[$C]=$v;}return$I;}function
foreign_keys($Q){$I=array();foreach(get_rows("PRAGMA foreign_key_list(".table($Q).")")as$J){$q=&$I[$J["id"]];if(!$q)$q=$J;$q["source"][]=$J["from"];$q["target"][]=$J["to"];}return$I;}function
viewm($C){global$g;return
array("select"=>preg_replace('~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\s+~iU','',$g->result("SELECT sql FROM sqlite_master WHERE name = ".q($C))));}function
collations(){return(isset($_GET["create"])?get_vals("PRAGMA collation_list",1):array());}function
information_schema($l){return
false;}function
error(){global$g;return
h($g->error);}function
check_sqlite_name($C){global$g;$Kc="db|sdb|sqlite";if(!preg_match("~^[^\\0]*\\.($Kc)\$~",$C)){$g->error=sprintf('Please use one of the extensions %s.',str_replace("|",", ",$Kc));return
false;}return
true;}function
create_database($l,$d){global$g;if(file_exists($l)){$g->error='File exists.';return
false;}if(!check_sqlite_name($l))return
false;try{$_=new
Min_SQLite($l);}catch(Exception$Ac){$g->error=$Ac->getMessage();return
false;}$_->query('PRAGMA encoding = "UTF-8"');$_->query('CREATE TABLE adminer (i)');$_->query('DROP TABLE adminer');return
true;}function
drop_databases($k){global$g;$g->__construct(":memory:");foreach($k
as$l){if(!@unlink($l)){$g->error='File exists.';return
false;}}return
true;}function
rename_database($C,$d){global$g;if(!check_sqlite_name($C))return
false;$g->__construct(":memory:");$g->error='File exists.';return@rename(DB,$C);}function
auto_increment(){return" PRIMARY KEY".(DRIVER=="sqlite"?" AUTOINCREMENT":"");}function
alter_table($Q,$C,$p,$cd,$tb,$uc,$d,$La,$Sf){$Mi=($Q==""||$cd);foreach($p
as$o){if($o[0]!=""||!$o[1]||$o[2]){$Mi=true;break;}}$c=array();$Gf=array();foreach($p
as$o){if($o[1]){$c[]=($Mi?$o[1]:"ADD ".implode($o[1]));if($o[0]!="")$Gf[$o[0]]=$o[1][0];}}if(!$Mi){foreach($c
as$X){if(!queries("ALTER TABLE ".table($Q)." $X"))return
false;}if($Q!=$C&&!queries("ALTER TABLE ".table($Q)." RENAME TO ".table($C)))return
false;}elseif(!recreate_table($Q,$C,$c,$Gf,$cd))return
false;if($La)queries("UPDATE sqlite_sequence SET seq = $La WHERE name = ".q($C));return
true;}function
recreate_table($Q,$C,$p,$Gf,$cd,$w=array()){if($Q!=""){if(!$p){foreach(fields($Q)as$y=>$o){if($w)$o["auto_increment"]=0;$p[]=process_field($o,$o);$Gf[$y]=idf_escape($y);}}$jg=false;foreach($p
as$o){if($o[6])$jg=true;}$hc=array();foreach($w
as$y=>$X){if($X[2]=="DROP"){$hc[$X[1]]=true;unset($w[$y]);}}foreach(indexes($Q)as$ge=>$v){$f=array();foreach($v["columns"]as$y=>$e){if(!$Gf[$e])continue
2;$f[]=$Gf[$e].($v["descs"][$y]?" DESC":"");}if(!$hc[$ge]){if($v["type"]!="PRIMARY"||!$jg)$w[]=array($v["type"],$ge,$f);}}foreach($w
as$y=>$X){if($X[0]=="PRIMARY"){unset($w[$y]);$cd[]="  PRIMARY KEY (".implode(", ",$X[2]).")";}}foreach(foreign_keys($Q)as$ge=>$q){foreach($q["source"]as$y=>$e){if(!$Gf[$e])continue
2;$q["source"][$y]=idf_unescape($Gf[$e]);}if(!isset($cd[" $ge"]))$cd[]=" ".format_foreign_key($q);}queries("BEGIN");}foreach($p
as$y=>$o)$p[$y]="  ".implode($o);$p=array_merge($p,array_filter($cd));if(!queries("CREATE TABLE ".table($Q!=""?"adminer_$C":$C)." (\n".implode(",\n",$p)."\n)"))return
false;if($Q!=""){if($Gf&&!queries("INSERT INTO ".table("adminer_$C")." (".implode(", ",$Gf).") SELECT ".implode(", ",array_map('idf_escape',array_keys($Gf)))." FROM ".table($Q)))return
false;$yi=array();foreach(triggers($Q)as$wi=>$ei){$vi=trigger($wi);$yi[]="CREATE TRIGGER ".idf_escape($wi)." ".implode(" ",$ei)." ON ".table($C)."\n$vi[Statement]";}if(!queries("DROP TABLE ".table($Q)))return
false;queries("ALTER TABLE ".table("adminer_$C")." RENAME TO ".table($C));if(!alter_indexes($C,$w))return
false;foreach($yi
as$vi){if(!queries($vi))return
false;}queries("COMMIT");}return
true;}function
index_sql($Q,$T,$C,$f){return"CREATE $T ".($T!="INDEX"?"INDEX ":"").idf_escape($C!=""?$C:uniqid($Q."_"))." ON ".table($Q)." $f";}function
alter_indexes($Q,$c){foreach($c
as$ig){if($ig[0]=="PRIMARY")return
recreate_table($Q,$Q,array(),array(),array(),$c);}foreach(array_reverse($c)as$X){if(!queries($X[2]=="DROP"?"DROP INDEX ".idf_escape($X[1]):index_sql($Q,$X[0],$X[1],"(".implode(", ",$X[2]).")")))return
false;}return
true;}function
truncate_tables($S){return
apply_queries("DELETE FROM",$S);}function
drop_views($Xi){return
apply_queries("DROP VIEW",$Xi);}function
drop_tables($S){return
apply_queries("DROP TABLE",$S);}function
move_tables($S,$Xi,$Vh){return
false;}function
trigger($C){global$g;if($C=="")return
array("Statement"=>"BEGIN\n\t;\nEND");$u='(?:[^`"\s]+|`[^`]*`|"[^"]*")+';$xi=trigger_options();preg_match("~^CREATE\\s+TRIGGER\\s*$u\\s*(".implode("|",$xi["Timing"]).")\\s+([a-z]+)(?:\\s+OF\\s+($u))?\\s+ON\\s*$u\\s*(?:FOR\\s+EACH\\s+ROW\\s)?(.*)~is",$g->result("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND name = ".q($C)),$B);$hf=$B[3];return
array("Timing"=>strtoupper($B[1]),"Event"=>strtoupper($B[2]).($hf?" OF":""),"Of"=>($hf[0]=='`'||$hf[0]=='"'?idf_unescape($hf):$hf),"Trigger"=>$C,"Statement"=>$B[4],);}function
triggers($Q){$I=array();$xi=trigger_options();foreach(get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($Q))as$J){preg_match('~^CREATE\s+TRIGGER\s*(?:[^`"\s]+|`[^`]*`|"[^"]*")+\s*('.implode("|",$xi["Timing"]).')\s*(.*)\s+ON\b~iU',$J["sql"],$B);$I[$J["name"]]=array($B[1],$B[2]);}return$I;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","UPDATE OF","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
begin(){return
queries("BEGIN");}function
last_id(){global$g;return$g->result("SELECT LAST_INSERT_ROWID()");}function
explain($g,$G){return$g->query("EXPLAIN QUERY PLAN $G");}function
found_rows($R,$Z){}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Zg){return
true;}function
create_sql($Q,$La,$Gh){global$g;$I=$g->result("SELECT sql FROM sqlite_master WHERE type IN ('table', 'view') AND name = ".q($Q));foreach(indexes($Q)as$C=>$v){if($C=='')continue;$I.=";\n\n".index_sql($Q,$v['type'],$C,"(".implode(", ",array_map('idf_escape',$v['columns'])).")");}return$I;}function
truncate_sql($Q){return"DELETE FROM ".table($Q);}function
use_sql($j){}function
trigger_sql($Q){return
implode(get_vals("SELECT sql || ';;\n' FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($Q)));}function
show_variables(){global$g;$I=array();foreach(array("auto_vacuum","cache_size","count_changes","default_cache_size","empty_result_callbacks","encoding","foreign_keys","full_column_names","fullfsync","journal_mode","journal_size_limit","legacy_file_format","locking_mode","page_size","max_page_count","read_uncommitted","recursive_triggers","reverse_unordered_selects","secure_delete","short_column_names","synchronous","temp_store","temp_store_directory","schema_version","integrity_check","quick_check")as$y)$I[$y]=$g->result("PRAGMA $y");return$I;}function
show_status(){$I=array();foreach(get_vals("PRAGMA compile_options")as$vf){list($y,$X)=explode("=",$vf,2);$I[$y]=$X;}return$I;}function
convert_field($o){}function
unconvert_field($o,$I){return$I;}function
support($Pc){return
preg_match('~^(columns|database|drop_col|dump|indexes|descidx|move_col|sql|status|table|trigger|variables|view|view_trigger)$~',$Pc);}$x="sqlite";$U=array("integer"=>0,"real"=>0,"numeric"=>0,"text"=>0,"blob"=>0);$Fh=array_keys($U);$Gi=array();$tf=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL");$kd=array("hex","length","lower","round","unixepoch","upper");$qd=array("avg","count","count distinct","group_concat","max","min","sum");$mc=array(array(),array("integer|real|numeric"=>"+/-","text"=>"||",));}$ec["pgsql"]="PostgreSQL";if(isset($_GET["pgsql"])){$fg=array("PgSQL","PDO_PgSQL");define("DRIVER","pgsql");if(extension_loaded("pgsql")){class
Min_DB{var$extension="PgSQL",$_link,$_result,$_string,$_database=true,$server_info,$affected_rows,$error,$timeout;function
_error($xc,$n){if(ini_bool("html_errors"))$n=html_entity_decode(strip_tags($n));$n=preg_replace('~^[^:]*: ~','',$n);$this->error=$n;}function
connect($N,$V,$F){global$b;$l=$b->database();set_error_handler(array($this,'_error'));$this->_string="host='".str_replace(":","' port='",addcslashes($N,"'\\"))."' user='".addcslashes($V,"'\\")."' password='".addcslashes($F,"'\\")."'";$this->_link=@pg_connect("$this->_string dbname='".($l!=""?addcslashes($l,"'\\"):"postgres")."'",PGSQL_CONNECT_FORCE_NEW);if(!$this->_link&&$l!=""){$this->_database=false;$this->_link=@pg_connect("$this->_string dbname='postgres'",PGSQL_CONNECT_FORCE_NEW);}restore_error_handler();if($this->_link){$Vi=pg_version($this->_link);$this->server_info=$Vi["server"];pg_set_client_encoding($this->_link,"UTF8");}return(bool)$this->_link;}function
quote($P){return"'".pg_escape_string($this->_link,$P)."'";}function
value($X,$o){return($o["type"]=="bytea"?pg_unescape_bytea($X):$X);}function
quoteBinary($P){return"'".pg_escape_bytea($this->_link,$P)."'";}function
select_db($j){global$b;if($j==$b->database())return$this->_database;$I=@pg_connect("$this->_string dbname='".addcslashes($j,"'\\")."'",PGSQL_CONNECT_FORCE_NEW);if($I)$this->_link=$I;return$I;}function
close(){$this->_link=@pg_connect("$this->_string dbname='postgres'");}function
query($G,$Ai=false){$H=@pg_query($this->_link,$G);$this->error="";if(!$H){$this->error=pg_last_error($this->_link);$I=false;}elseif(!pg_num_fields($H)){$this->affected_rows=pg_affected_rows($H);$I=true;}else$I=new
Min_Result($H);if($this->timeout){$this->timeout=0;$this->query("RESET statement_timeout");}return$I;}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($G,$o=0){$H=$this->query($G);if(!$H||!$H->num_rows)return
false;return
pg_fetch_result($H->_result,0,$o);}function
warnings(){return
h(pg_last_notice($this->_link));}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
__construct($H){$this->_result=$H;$this->num_rows=pg_num_rows($H);}function
fetch_assoc(){return
pg_fetch_assoc($this->_result);}function
fetch_row(){return
pg_fetch_row($this->_result);}function
fetch_field(){$e=$this->_offset++;$I=new
stdClass;if(function_exists('pg_field_table'))$I->orgtable=pg_field_table($this->_result,$e);$I->name=pg_field_name($this->_result,$e);$I->orgname=$I->name;$I->type=pg_field_type($this->_result,$e);$I->charsetnr=($I->type=="bytea"?63:0);return$I;}function
__destruct(){pg_free_result($this->_result);}}}elseif(extension_loaded("pdo_pgsql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_PgSQL",$timeout;function
connect($N,$V,$F){global$b;$l=$b->database();$P="pgsql:host='".str_replace(":","' port='",addcslashes($N,"'\\"))."' options='-c client_encoding=utf8'";$this->dsn("$P dbname='".($l!=""?addcslashes($l,"'\\"):"postgres")."'",$V,$F);return
true;}function
select_db($j){global$b;return($b->database()==$j);}function
quoteBinary($Wg){return
q($Wg);}function
query($G,$Ai=false){$I=parent::query($G,$Ai);if($this->timeout){$this->timeout=0;parent::query("RESET statement_timeout");}return$I;}function
warnings(){return'';}function
close(){}}}class
Min_Driver
extends
Min_SQL{function
insertUpdate($Q,$K,$ig){global$g;foreach($K
as$O){$Hi=array();$Z=array();foreach($O
as$y=>$X){$Hi[]="$y = $X";if(isset($ig[idf_unescape($y)]))$Z[]="$y = $X";}if(!(($Z&&queries("UPDATE ".table($Q)." SET ".implode(", ",$Hi)." WHERE ".implode(" AND ",$Z))&&$g->affected_rows)||queries("INSERT INTO ".table($Q)." (".implode(", ",array_keys($O)).") VALUES (".implode(", ",$O).")")))return
false;}return
true;}function
slowQuery($G,$di){$this->_conn->query("SET statement_timeout = ".(1000*$di));$this->_conn->timeout=1000*$di;return$G;}function
convertSearch($u,$X,$o){return(preg_match('~char|text'.(!preg_match('~LIKE~',$X["op"])?'|date|time(stamp)?|boolean|uuid|'.number_type():'').'~',$o["type"])?$u:"CAST($u AS text)");}function
quoteBinary($Wg){return$this->_conn->quoteBinary($Wg);}function
warnings(){return$this->_conn->warnings();}function
tableHelp($C){$we=array("information_schema"=>"infoschema","pg_catalog"=>"catalog",);$_=$we[$_GET["ns"]];if($_)return"$_-".str_replace("_","-",$C).".html";}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
connect(){global$b,$U,$Fh;$g=new
Min_DB;$Gb=$b->credentials();if($g->connect($Gb[0],$Gb[1],$Gb[2])){if(min_version(9,0,$g)){$g->query("SET application_name = 'Adminer'");if(min_version(9.2,0,$g)){$Fh['Strings'][]="json";$U["json"]=4294967295;if(min_version(9.4,0,$g)){$Fh['Strings'][]="jsonb";$U["jsonb"]=4294967295;}}}return$g;}return$g->error;}function
get_databases(){return
get_vals("SELECT datname FROM pg_database WHERE has_database_privilege(datname, 'CONNECT') ORDER BY datname");}function
limit($G,$Z,$z,$D=0,$M=" "){return" $G$Z".($z!==null?$M."LIMIT $z".($D?" OFFSET $D":""):"");}function
limit1($Q,$G,$Z,$M="\n"){return(preg_match('~^INTO~',$G)?limit($G,$Z,1,0,$M):" $G".(is_view(table_status1($Q))?$Z:" WHERE ctid = (SELECT ctid FROM ".table($Q).$Z.$M."LIMIT 1)"));}function
db_collation($l,$ob){global$g;return$g->result("SHOW LC_COLLATE");}function
engines(){return
array();}function
logged_user(){global$g;return$g->result("SELECT user");}function
tables_list(){$G="SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = current_schema()";if(support('materializedview'))$G.="
UNION ALL
SELECT matviewname, 'MATERIALIZED VIEW'
FROM pg_matviews
WHERE schemaname = current_schema()";$G.="
ORDER BY 1";return
get_key_vals($G);}function
count_tables($k){return
array();}function
table_status($C=""){$I=array();foreach(get_rows("SELECT c.relname AS \"Name\", CASE c.relkind WHEN 'r' THEN 'table' WHEN 'm' THEN 'materialized view' ELSE 'view' END AS \"Engine\", pg_relation_size(c.oid) AS \"Data_length\", pg_total_relation_size(c.oid) - pg_relation_size(c.oid) AS \"Index_length\", obj_description(c.oid, 'pg_class') AS \"Comment\", CASE WHEN c.relhasoids THEN 'oid' ELSE '' END AS \"Oid\", c.reltuples as \"Rows\", n.nspname
FROM pg_class c
JOIN pg_namespace n ON(n.nspname = current_schema() AND n.oid = c.relnamespace)
WHERE relkind IN ('r', 'm', 'v', 'f')
".($C!=""?"AND relname = ".q($C):"ORDER BY relname"))as$J)$I[$J["Name"]]=$J;return($C!=""?$I[$C]:$I);}function
is_view($R){return
in_array($R["Engine"],array("view","materialized view"));}function
fk_support($R){return
true;}function
fields($Q){$I=array();$Ca=array('timestamp without time zone'=>'timestamp','timestamp with time zone'=>'timestamptz',);$Dd=min_version(10)?"(a.attidentity = 'd')::int":'0';foreach(get_rows("SELECT a.attname AS field, format_type(a.atttypid, a.atttypmod) AS full_type, d.adsrc AS default, a.attnotnull::int, col_description(c.oid, a.attnum) AS comment, $Dd AS identity
FROM pg_class c
JOIN pg_namespace n ON c.relnamespace = n.oid
JOIN pg_attribute a ON c.oid = a.attrelid
LEFT JOIN pg_attrdef d ON c.oid = d.adrelid AND a.attnum = d.adnum
WHERE c.relname = ".q($Q)."
AND n.nspname = current_schema()
AND NOT a.attisdropped
AND a.attnum > 0
ORDER BY a.attnum")as$J){preg_match('~([^([]+)(\((.*)\))?([a-z ]+)?((\[[0-9]*])*)$~',$J["full_type"],$B);list(,$T,$te,$J["length"],$wa,$Fa)=$B;$J["length"].=$Fa;$db=$T.$wa;if(isset($Ca[$db])){$J["type"]=$Ca[$db];$J["full_type"]=$J["type"].$te.$Fa;}else{$J["type"]=$T;$J["full_type"]=$J["type"].$te.$wa.$Fa;}if($J['identity'])$J['default']='GENERATED BY DEFAULT AS IDENTITY';$J["null"]=!$J["attnotnull"];$J["auto_increment"]=$J['identity']||preg_match('~^nextval\(~i',$J["default"]);$J["privileges"]=array("insert"=>1,"select"=>1,"update"=>1);if(preg_match('~(.+)::[^)]+(.*)~',$J["default"],$B))$J["default"]=($B[1]=="NULL"?null:(($B[1][0]=="'"?idf_unescape($B[1]):$B[1]).$B[2]));$I[$J["field"]]=$J;}return$I;}function
indexes($Q,$h=null){global$g;if(!is_object($h))$h=$g;$I=array();$Oh=$h->result("SELECT oid FROM pg_class WHERE relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema()) AND relname = ".q($Q));$f=get_key_vals("SELECT attnum, attname FROM pg_attribute WHERE attrelid = $Oh AND attnum > 0",$h);foreach(get_rows("SELECT relname, indisunique::int, indisprimary::int, indkey, indoption , (indpred IS NOT NULL)::int as indispartial FROM pg_index i, pg_class ci WHERE i.indrelid = $Oh AND ci.oid = i.indexrelid",$h)as$J){$Gg=$J["relname"];$I[$Gg]["type"]=($J["indispartial"]?"INDEX":($J["indisprimary"]?"PRIMARY":($J["indisunique"]?"UNIQUE":"INDEX")));$I[$Gg]["columns"]=array();foreach(explode(" ",$J["indkey"])as$Nd)$I[$Gg]["columns"][]=$f[$Nd];$I[$Gg]["descs"]=array();foreach(explode(" ",$J["indoption"])as$Od)$I[$Gg]["descs"][]=($Od&1?'1':null);$I[$Gg]["lengths"]=array();}return$I;}function
foreign_keys($Q){global$of;$I=array();foreach(get_rows("SELECT conname, condeferrable::int AS deferrable, pg_get_constraintdef(oid) AS definition
FROM pg_constraint
WHERE conrelid = (SELECT pc.oid FROM pg_class AS pc INNER JOIN pg_namespace AS pn ON (pn.oid = pc.relnamespace) WHERE pc.relname = ".q($Q)." AND pn.nspname = current_schema())
AND contype = 'f'::char
ORDER BY conkey, conname")as$J){if(preg_match('~FOREIGN KEY\s*\((.+)\)\s*REFERENCES (.+)\((.+)\)(.*)$~iA',$J['definition'],$B)){$J['source']=array_map('trim',explode(',',$B[1]));if(preg_match('~^(("([^"]|"")+"|[^"]+)\.)?"?("([^"]|"")+"|[^"]+)$~',$B[2],$Be)){$J['ns']=str_replace('""','"',preg_replace('~^"(.+)"$~','\1',$Be[2]));$J['table']=str_replace('""','"',preg_replace('~^"(.+)"$~','\1',$Be[4]));}$J['target']=array_map('trim',explode(',',$B[3]));$J['on_delete']=(preg_match("~ON DELETE ($of)~",$B[4],$Be)?$Be[1]:'NO ACTION');$J['on_update']=(preg_match("~ON UPDATE ($of)~",$B[4],$Be)?$Be[1]:'NO ACTION');$I[$J['conname']]=$J;}}return$I;}function
viewm($C){global$g;return
array("select"=>trim($g->result("SELECT view_definition
FROM information_schema.views
WHERE table_schema = current_schema() AND table_name = ".q($C))));}function
collations(){return
array();}function
information_schema($l){return($l=="information_schema");}function
error(){global$g;$I=h($g->error);if(preg_match('~^(.*\n)?([^\n]*)\n( *)\^(\n.*)?$~s',$I,$B))$I=$B[1].preg_replace('~((?:[^&]|&[^;]*;){'.strlen($B[3]).'})(.*)~','\1<b>\2</b>',$B[2]).$B[4];return
nl_br($I);}function
create_database($l,$d){return
queries("CREATE DATABASE ".idf_escape($l).($d?" ENCODING ".idf_escape($d):""));}function
drop_databases($k){global$g;$g->close();return
apply_queries("DROP DATABASE",$k,'idf_escape');}function
rename_database($C,$d){return
queries("ALTER DATABASE ".idf_escape(DB)." RENAME TO ".idf_escape($C));}function
auto_increment(){return"";}function
alter_table($Q,$C,$p,$cd,$tb,$uc,$d,$La,$Sf){$c=array();$tg=array();foreach($p
as$o){$e=idf_escape($o[0]);$X=$o[1];if(!$X)$c[]="DROP $e";else{$Ri=$X[5];unset($X[5]);if(isset($X[6])&&$o[0]=="")$X[1]=($X[1]=="bigint"?" big":" ")."serial";if($o[0]=="")$c[]=($Q!=""?"ADD ":"  ").implode($X);else{if($e!=$X[0])$tg[]="ALTER TABLE ".table($Q)." RENAME $e TO $X[0]";$c[]="ALTER $e TYPE$X[1]";if(!$X[6]){$c[]="ALTER $e ".($X[3]?"SET$X[3]":"DROP DEFAULT");$c[]="ALTER $e ".($X[2]==" NULL"?"DROP NOT":"SET").$X[2];}}if($o[0]!=""||$Ri!="")$tg[]="COMMENT ON COLUMN ".table($Q).".$X[0] IS ".($Ri!=""?substr($Ri,9):"''");}}$c=array_merge($c,$cd);if($Q=="")array_unshift($tg,"CREATE TABLE ".table($C)." (\n".implode(",\n",$c)."\n)");elseif($c)array_unshift($tg,"ALTER TABLE ".table($Q)."\n".implode(",\n",$c));if($Q!=""&&$Q!=$C)$tg[]="ALTER TABLE ".table($Q)." RENAME TO ".table($C);if($Q!=""||$tb!="")$tg[]="COMMENT ON TABLE ".table($C)." IS ".q($tb);if($La!=""){}foreach($tg
as$G){if(!queries($G))return
false;}return
true;}function
alter_indexes($Q,$c){$i=array();$fc=array();$tg=array();foreach($c
as$X){if($X[0]!="INDEX")$i[]=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$X[2]).")");elseif($X[2]=="DROP")$fc[]=idf_escape($X[1]);else$tg[]="CREATE INDEX ".idf_escape($X[1]!=""?$X[1]:uniqid($Q."_"))." ON ".table($Q)." (".implode(", ",$X[2]).")";}if($i)array_unshift($tg,"ALTER TABLE ".table($Q).implode(",",$i));if($fc)array_unshift($tg,"DROP INDEX ".implode(", ",$fc));foreach($tg
as$G){if(!queries($G))return
false;}return
true;}function
truncate_tables($S){return
queries("TRUNCATE ".implode(", ",array_map('table',$S)));return
true;}function
drop_views($Xi){return
drop_tables($Xi);}function
drop_tables($S){foreach($S
as$Q){$Ch=table_status($Q);if(!queries("DROP ".strtoupper($Ch["Engine"])." ".table($Q)))return
false;}return
true;}function
move_tables($S,$Xi,$Vh){foreach(array_merge($S,$Xi)as$Q){$Ch=table_status($Q);if(!queries("ALTER ".strtoupper($Ch["Engine"])." ".table($Q)." SET SCHEMA ".idf_escape($Vh)))return
false;}return
true;}function
trigger($C,$Q=null){if($C=="")return
array("Statement"=>"EXECUTE PROCEDURE ()");if($Q===null)$Q=$_GET['trigger'];$K=get_rows('SELECT t.trigger_name AS "Trigger", t.action_timing AS "Timing", (SELECT STRING_AGG(event_manipulation, \' OR \') FROM information_schema.triggers WHERE event_object_table = t.event_object_table AND trigger_name = t.trigger_name ) AS "Events", t.event_manipulation AS "Event", \'FOR EACH \' || t.action_orientation AS "Type", t.action_statement AS "Statement" FROM information_schema.triggers t WHERE t.event_object_table = '.q($Q).' AND t.trigger_name = '.q($C));return
reset($K);}function
triggers($Q){$I=array();foreach(get_rows("SELECT * FROM information_schema.triggers WHERE event_object_table = ".q($Q))as$J)$I[$J["trigger_name"]]=array($J["action_timing"],$J["event_manipulation"]);return$I;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("FOR EACH ROW","FOR EACH STATEMENT"),);}function
routine($C,$T){$K=get_rows('SELECT routine_definition AS definition, LOWER(external_language) AS language, *
FROM information_schema.routines
WHERE routine_schema = current_schema() AND specific_name = '.q($C));$I=$K[0];$I["returns"]=array("type"=>$I["type_udt_name"]);$I["fields"]=get_rows('SELECT parameter_name AS field, data_type AS type, character_maximum_length AS length, parameter_mode AS inout
FROM information_schema.parameters
WHERE specific_schema = current_schema() AND specific_name = '.q($C).'
ORDER BY ordinal_position');return$I;}function
routines(){return
get_rows('SELECT specific_name AS "SPECIFIC_NAME", routine_type AS "ROUTINE_TYPE", routine_name AS "ROUTINE_NAME", type_udt_name AS "DTD_IDENTIFIER"
FROM information_schema.routines
WHERE routine_schema = current_schema()
ORDER BY SPECIFIC_NAME');}function
routine_languages(){return
get_vals("SELECT LOWER(lanname) FROM pg_catalog.pg_language");}function
routine_id($C,$J){$I=array();foreach($J["fields"]as$o)$I[]=$o["type"];return
idf_escape($C)."(".implode(", ",$I).")";}function
last_id(){return
0;}function
explain($g,$G){return$g->query("EXPLAIN $G");}function
found_rows($R,$Z){global$g;if(preg_match("~ rows=([0-9]+)~",$g->result("EXPLAIN SELECT * FROM ".idf_escape($R["Name"]).($Z?" WHERE ".implode(" AND ",$Z):"")),$Fg))return$Fg[1];return
false;}function
types(){return
get_vals("SELECT typname
FROM pg_type
WHERE typnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema())
AND typtype IN ('b','d','e')
AND typelem = 0");}function
schemas(){return
get_vals("SELECT nspname FROM pg_namespace ORDER BY nspname");}function
get_schema(){global$g;return$g->result("SELECT current_schema()");}function
set_schema($Yg){global$g,$U,$Fh;$I=$g->query("SET search_path TO ".idf_escape($Yg));foreach(types()as$T){if(!isset($U[$T])){$U[$T]=0;$Fh['User types'][]=$T;}}return$I;}function
create_sql($Q,$La,$Gh){global$g;$I='';$Og=array();$ih=array();$Ch=table_status($Q);$p=fields($Q);$w=indexes($Q);ksort($w);$Zc=foreign_keys($Q);ksort($Zc);if(!$Ch||empty($p))return
false;$I="CREATE TABLE ".idf_escape($Ch['nspname']).".".idf_escape($Ch['Name'])." (\n    ";foreach($p
as$Rc=>$o){$Pf=idf_escape($o['field']).' '.$o['full_type'].default_value($o).($o['attnotnull']?" NOT NULL":"");$Og[]=$Pf;if(preg_match('~nextval\(\'([^\']+)\'\)~',$o['default'],$Ce)){$hh=$Ce[1];$vh=reset(get_rows(min_version(10)?"SELECT *, cache_size AS cache_value FROM pg_sequences WHERE schemaname = current_schema() AND sequencename = ".q($hh):"SELECT * FROM $hh"));$ih[]=($Gh=="DROP+CREATE"?"DROP SEQUENCE IF EXISTS $hh;\n":"")."CREATE SEQUENCE $hh INCREMENT $vh[increment_by] MINVALUE $vh[min_value] MAXVALUE $vh[max_value] START ".($La?$vh['last_value']:1)." CACHE $vh[cache_value];";}}if(!empty($ih))$I=implode("\n\n",$ih)."\n\n$I";foreach($w
as$Id=>$v){switch($v['type']){case'UNIQUE':$Og[]="CONSTRAINT ".idf_escape($Id)." UNIQUE (".implode(', ',array_map('idf_escape',$v['columns'])).")";break;case'PRIMARY':$Og[]="CONSTRAINT ".idf_escape($Id)." PRIMARY KEY (".implode(', ',array_map('idf_escape',$v['columns'])).")";break;}}foreach($Zc
as$Yc=>$Xc)$Og[]="CONSTRAINT ".idf_escape($Yc)." $Xc[definition] ".($Xc['deferrable']?'DEFERRABLE':'NOT DEFERRABLE');$I.=implode(",\n    ",$Og)."\n) WITH (oids = ".($Ch['Oid']?'true':'false').");";foreach($w
as$Id=>$v){if($v['type']=='INDEX'){$f=array();foreach($v['columns']as$y=>$X)$f[]=idf_escape($X).($v['descs'][$y]?" DESC":"");$I.="\n\nCREATE INDEX ".idf_escape($Id)." ON ".idf_escape($Ch['nspname']).".".idf_escape($Ch['Name'])." USING btree (".implode(', ',$f).");";}}if($Ch['Comment'])$I.="\n\nCOMMENT ON TABLE ".idf_escape($Ch['nspname']).".".idf_escape($Ch['Name'])." IS ".q($Ch['Comment']).";";foreach($p
as$Rc=>$o){if($o['comment'])$I.="\n\nCOMMENT ON COLUMN ".idf_escape($Ch['nspname']).".".idf_escape($Ch['Name']).".".idf_escape($Rc)." IS ".q($o['comment']).";";}return
rtrim($I,';');}function
truncate_sql($Q){return"TRUNCATE ".table($Q);}function
trigger_sql($Q){$Ch=table_status($Q);$I="";foreach(triggers($Q)as$ui=>$ti){$vi=trigger($ui,$Ch['Name']);$I.="\nCREATE TRIGGER ".idf_escape($vi['Trigger'])." $vi[Timing] $vi[Events] ON ".idf_escape($Ch["nspname"]).".".idf_escape($Ch['Name'])." $vi[Type] $vi[Statement];;\n";}return$I;}function
use_sql($j){return"\connect ".idf_escape($j);}function
show_variables(){return
get_key_vals("SHOW ALL");}function
process_list(){return
get_rows("SELECT * FROM pg_stat_activity ORDER BY ".(min_version(9.2)?"pid":"procpid"));}function
show_status(){}function
convert_field($o){}function
unconvert_field($o,$I){return$I;}function
support($Pc){return
preg_match('~^(database|table|columns|sql|indexes|descidx|comment|view|'.(min_version(9.3)?'materializedview|':'').'scheme|routine|processlist|sequence|trigger|type|variables|drop_col|kill|dump)$~',$Pc);}function
kill_process($X){return
queries("SELECT pg_terminate_backend(".number($X).")");}function
connection_id(){return"SELECT pg_backend_pid()";}function
max_connections(){global$g;return$g->result("SHOW max_connections");}$x="pgsql";$U=array();$Fh=array();foreach(array('Numbers'=>array("smallint"=>5,"integer"=>10,"bigint"=>19,"boolean"=>1,"numeric"=>0,"real"=>7,"double precision"=>16,"money"=>20),'Date and time'=>array("date"=>13,"time"=>17,"timestamp"=>20,"timestamptz"=>21,"interval"=>0),'Strings'=>array("character"=>0,"character varying"=>0,"text"=>0,"tsquery"=>0,"tsvector"=>0,"uuid"=>0,"xml"=>0),'Binary'=>array("bit"=>0,"bit varying"=>0,"bytea"=>0),'Network'=>array("cidr"=>43,"inet"=>43,"macaddr"=>17,"txid_snapshot"=>0),'Geometry'=>array("box"=>0,"circle"=>0,"line"=>0,"lseg"=>0,"path"=>0,"point"=>0,"polygon"=>0),)as$y=>$X){$U+=$X;$Fh[$y]=array_keys($X);}$Gi=array();$tf=array("=","<",">","<=",">=","!=","~","!~","LIKE","LIKE %%","ILIKE","ILIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");$kd=array("char_length","lower","round","to_hex","to_timestamp","upper");$qd=array("avg","count","count distinct","max","min","sum");$mc=array(array("char"=>"md5","date|time"=>"now",),array(number_type()=>"+/-","date|time"=>"+ interval/- interval","char|text"=>"||",));}$ec["oracle"]="Oracle (beta)";if(isset($_GET["oracle"])){$fg=array("OCI8","PDO_OCI");define("DRIVER","oracle");if(extension_loaded("oci8")){class
Min_DB{var$extension="oci8",$_link,$_result,$server_info,$affected_rows,$errno,$error;function
_error($xc,$n){if(ini_bool("html_errors"))$n=html_entity_decode(strip_tags($n));$n=preg_replace('~^[^:]*: ~','',$n);$this->error=$n;}function
connect($N,$V,$F){$this->_link=@oci_new_connect($V,$F,$N,"AL32UTF8");if($this->_link){$this->server_info=oci_server_version($this->_link);return
true;}$n=oci_error();$this->error=$n["message"];return
false;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($j){return
true;}function
query($G,$Ai=false){$H=oci_parse($this->_link,$G);$this->error="";if(!$H){$n=oci_error($this->_link);$this->errno=$n["code"];$this->error=$n["message"];return
false;}set_error_handler(array($this,'_error'));$I=@oci_execute($H);restore_error_handler();if($I){if(oci_num_fields($H))return
new
Min_Result($H);$this->affected_rows=oci_num_rows($H);}return$I;}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($G,$o=1){$H=$this->query($G);if(!is_object($H)||!oci_fetch($H->_result))return
false;return
oci_result($H->_result,$o);}}class
Min_Result{var$_result,$_offset=1,$num_rows;function
__construct($H){$this->_result=$H;}function
_convert($J){foreach((array)$J
as$y=>$X){if(is_a($X,'OCI-Lob'))$J[$y]=$X->load();}return$J;}function
fetch_assoc(){return$this->_convert(oci_fetch_assoc($this->_result));}function
fetch_row(){return$this->_convert(oci_fetch_row($this->_result));}function
fetch_field(){$e=$this->_offset++;$I=new
stdClass;$I->name=oci_field_name($this->_result,$e);$I->orgname=$I->name;$I->type=oci_field_type($this->_result,$e);$I->charsetnr=(preg_match("~raw|blob|bfile~",$I->type)?63:0);return$I;}function
__destruct(){oci_free_statement($this->_result);}}}elseif(extension_loaded("pdo_oci")){class
Min_DB
extends
Min_PDO{var$extension="PDO_OCI";function
connect($N,$V,$F){$this->dsn("oci:dbname=//$N;charset=AL32UTF8",$V,$F);return
true;}function
select_db($j){return
true;}}}class
Min_Driver
extends
Min_SQL{function
begin(){return
true;}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
connect(){global$b;$g=new
Min_DB;$Gb=$b->credentials();if($g->connect($Gb[0],$Gb[1],$Gb[2]))return$g;return$g->error;}function
get_databases(){return
get_vals("SELECT tablespace_name FROM user_tablespaces");}function
limit($G,$Z,$z,$D=0,$M=" "){return($D?" * FROM (SELECT t.*, rownum AS rnum FROM (SELECT $G$Z) t WHERE rownum <= ".($z+$D).") WHERE rnum > $D":($z!==null?" * FROM (SELECT $G$Z) WHERE rownum <= ".($z+$D):" $G$Z"));}function
limit1($Q,$G,$Z,$M="\n"){return" $G$Z";}function
db_collation($l,$ob){global$g;return$g->result("SELECT value FROM nls_database_parameters WHERE parameter = 'NLS_CHARACTERSET'");}function
engines(){return
array();}function
logged_user(){global$g;return$g->result("SELECT USER FROM DUAL");}function
tables_list(){return
get_key_vals("SELECT table_name, 'table' FROM all_tables WHERE tablespace_name = ".q(DB)."
UNION SELECT view_name, 'view' FROM user_views
ORDER BY 1");}function
count_tables($k){return
array();}function
table_status($C=""){$I=array();$ah=q($C);foreach(get_rows('SELECT table_name "Name", \'table\' "Engine", avg_row_len * num_rows "Data_length", num_rows "Rows" FROM all_tables WHERE tablespace_name = '.q(DB).($C!=""?" AND table_name = $ah":"")."
UNION SELECT view_name, 'view', 0, 0 FROM user_views".($C!=""?" WHERE view_name = $ah":"")."
ORDER BY 1")as$J){if($C!="")return$J;$I[$J["Name"]]=$J;}return$I;}function
is_view($R){return$R["Engine"]=="view";}function
fk_support($R){return
true;}function
fields($Q){$I=array();foreach(get_rows("SELECT * FROM all_tab_columns WHERE table_name = ".q($Q)." ORDER BY column_id")as$J){$T=$J["DATA_TYPE"];$te="$J[DATA_PRECISION],$J[DATA_SCALE]";if($te==",")$te=$J["DATA_LENGTH"];$I[$J["COLUMN_NAME"]]=array("field"=>$J["COLUMN_NAME"],"full_type"=>$T.($te?"($te)":""),"type"=>strtolower($T),"length"=>$te,"default"=>$J["DATA_DEFAULT"],"null"=>($J["NULLABLE"]=="Y"),"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),);}return$I;}function
indexes($Q,$h=null){$I=array();foreach(get_rows("SELECT uic.*, uc.constraint_type
FROM user_ind_columns uic
LEFT JOIN user_constraints uc ON uic.index_name = uc.constraint_name AND uic.table_name = uc.table_name
WHERE uic.table_name = ".q($Q)."
ORDER BY uc.constraint_type, uic.column_position",$h)as$J){$Id=$J["INDEX_NAME"];$I[$Id]["type"]=($J["CONSTRAINT_TYPE"]=="P"?"PRIMARY":($J["CONSTRAINT_TYPE"]=="U"?"UNIQUE":"INDEX"));$I[$Id]["columns"][]=$J["COLUMN_NAME"];$I[$Id]["lengths"][]=($J["CHAR_LENGTH"]&&$J["CHAR_LENGTH"]!=$J["COLUMN_LENGTH"]?$J["CHAR_LENGTH"]:null);$I[$Id]["descs"][]=($J["DESCEND"]?'1':null);}return$I;}function
viewm($C){$K=get_rows('SELECT text "select" FROM user_views WHERE view_name = '.q($C));return
reset($K);}function
collations(){return
array();}function
information_schema($l){return
false;}function
error(){global$g;return
h($g->error);}function
explain($g,$G){$g->query("EXPLAIN PLAN FOR $G");return$g->query("SELECT * FROM plan_table");}function
found_rows($R,$Z){}function
alter_table($Q,$C,$p,$cd,$tb,$uc,$d,$La,$Sf){$c=$fc=array();foreach($p
as$o){$X=$o[1];if($X&&$o[0]!=""&&idf_escape($o[0])!=$X[0])queries("ALTER TABLE ".table($Q)." RENAME COLUMN ".idf_escape($o[0])." TO $X[0]");if($X)$c[]=($Q!=""?($o[0]!=""?"MODIFY (":"ADD ("):"  ").implode($X).($Q!=""?")":"");else$fc[]=idf_escape($o[0]);}if($Q=="")return
queries("CREATE TABLE ".table($C)." (\n".implode(",\n",$c)."\n)");return(!$c||queries("ALTER TABLE ".table($Q)."\n".implode("\n",$c)))&&(!$fc||queries("ALTER TABLE ".table($Q)." DROP (".implode(", ",$fc).")"))&&($Q==$C||queries("ALTER TABLE ".table($Q)." RENAME TO ".table($C)));}function
foreign_keys($Q){$I=array();$G="SELECT c_list.CONSTRAINT_NAME as NAME,
c_src.COLUMN_NAME as SRC_COLUMN,
c_dest.OWNER as DEST_DB,
c_dest.TABLE_NAME as DEST_TABLE,
c_dest.COLUMN_NAME as DEST_COLUMN,
c_list.DELETE_RULE as ON_DELETE
FROM ALL_CONSTRAINTS c_list, ALL_CONS_COLUMNS c_src, ALL_CONS_COLUMNS c_dest
WHERE c_list.CONSTRAINT_NAME = c_src.CONSTRAINT_NAME
AND c_list.R_CONSTRAINT_NAME = c_dest.CONSTRAINT_NAME
AND c_list.CONSTRAINT_TYPE = 'R'
AND c_src.TABLE_NAME = ".q($Q);foreach(get_rows($G)as$J)$I[$J['NAME']]=array("db"=>$J['DEST_DB'],"table"=>$J['DEST_TABLE'],"source"=>array($J['SRC_COLUMN']),"target"=>array($J['DEST_COLUMN']),"on_delete"=>$J['ON_DELETE'],"on_update"=>null,);return$I;}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Xi){return
apply_queries("DROP VIEW",$Xi);}function
drop_tables($S){return
apply_queries("DROP TABLE",$S);}function
last_id(){return
0;}function
schemas(){return
get_vals("SELECT DISTINCT owner FROM dba_segments WHERE owner IN (SELECT username FROM dba_users WHERE default_tablespace NOT IN ('SYSTEM','SYSAUX'))");}function
get_schema(){global$g;return$g->result("SELECT sys_context('USERENV', 'SESSION_USER') FROM dual");}function
set_schema($Zg){global$g;return$g->query("ALTER SESSION SET CURRENT_SCHEMA = ".idf_escape($Zg));}function
show_variables(){return
get_key_vals('SELECT name, display_value FROM v$parameter');}function
process_list(){return
get_rows('SELECT sess.process AS "process", sess.username AS "user", sess.schemaname AS "schema", sess.status AS "status", sess.wait_class AS "wait_class", sess.seconds_in_wait AS "seconds_in_wait", sql.sql_text AS "sql_text", sess.machine AS "machine", sess.port AS "port"
FROM v$session sess LEFT OUTER JOIN v$sql sql
ON sql.sql_id = sess.sql_id
WHERE sess.type = \'USER\'
ORDER BY PROCESS
');}function
show_status(){$K=get_rows('SELECT * FROM v$instance');return
reset($K);}function
convert_field($o){}function
unconvert_field($o,$I){return$I;}function
support($Pc){return
preg_match('~^(columns|database|drop_col|indexes|descidx|processlist|scheme|sql|status|table|variables|view|view_trigger)$~',$Pc);}$x="oracle";$U=array();$Fh=array();foreach(array('Numbers'=>array("number"=>38,"binary_float"=>12,"binary_double"=>21),'Date and time'=>array("date"=>10,"timestamp"=>29,"interval year"=>12,"interval day"=>28),'Strings'=>array("char"=>2000,"varchar2"=>4000,"nchar"=>2000,"nvarchar2"=>4000,"clob"=>4294967295,"nclob"=>4294967295),'Binary'=>array("raw"=>2000,"long raw"=>2147483648,"blob"=>4294967295,"bfile"=>4294967296),)as$y=>$X){$U+=$X;$Fh[$y]=array_keys($X);}$Gi=array();$tf=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL");$kd=array("length","lower","round","upper");$qd=array("avg","count","count distinct","max","min","sum");$mc=array(array("date"=>"current_date","timestamp"=>"current_timestamp",),array("number|float|double"=>"+/-","date|timestamp"=>"+ interval/- interval","char|clob"=>"||",));}$ec["mssql"]="MS SQL (beta)";if(isset($_GET["mssql"])){$fg=array("SQLSRV","MSSQL","PDO_DBLIB");define("DRIVER","mssql");if(extension_loaded("sqlsrv")){class
Min_DB{var$extension="sqlsrv",$_link,$_result,$server_info,$affected_rows,$errno,$error;function
_get_error(){$this->error="";foreach(sqlsrv_errors()as$n){$this->errno=$n["code"];$this->error.="$n[message]\n";}$this->error=rtrim($this->error);}function
connect($N,$V,$F){global$b;$l=$b->database();$xb=array("UID"=>$V,"PWD"=>$F,"CharacterSet"=>"UTF-8");if($l!="")$xb["Database"]=$l;$this->_link=@sqlsrv_connect(preg_replace('~:~',',',$N),$xb);if($this->_link){$Pd=sqlsrv_server_info($this->_link);$this->server_info=$Pd['SQLServerVersion'];}else$this->_get_error();return(bool)$this->_link;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($j){return$this->query("USE ".idf_escape($j));}function
query($G,$Ai=false){$H=sqlsrv_query($this->_link,$G);$this->error="";if(!$H){$this->_get_error();return
false;}return$this->store_result($H);}function
multi_query($G){$this->_result=sqlsrv_query($this->_link,$G);$this->error="";if(!$this->_result){$this->_get_error();return
false;}return
true;}function
store_result($H=null){if(!$H)$H=$this->_result;if(!$H)return
false;if(sqlsrv_field_metadata($H))return
new
Min_Result($H);$this->affected_rows=sqlsrv_rows_affected($H);return
true;}function
next_result(){return$this->_result?sqlsrv_next_result($this->_result):null;}function
result($G,$o=0){$H=$this->query($G);if(!is_object($H))return
false;$J=$H->fetch_row();return$J[$o];}}class
Min_Result{var$_result,$_offset=0,$_fields,$num_rows;function
__construct($H){$this->_result=$H;}function
_convert($J){foreach((array)$J
as$y=>$X){if(is_a($X,'DateTime'))$J[$y]=$X->format("Y-m-d H:i:s");}return$J;}function
fetch_assoc(){return$this->_convert(sqlsrv_fetch_array($this->_result,SQLSRV_FETCH_ASSOC));}function
fetch_row(){return$this->_convert(sqlsrv_fetch_array($this->_result,SQLSRV_FETCH_NUMERIC));}function
fetch_field(){if(!$this->_fields)$this->_fields=sqlsrv_field_metadata($this->_result);$o=$this->_fields[$this->_offset++];$I=new
stdClass;$I->name=$o["Name"];$I->orgname=$o["Name"];$I->type=($o["Type"]==1?254:0);return$I;}function
seek($D){for($s=0;$s<$D;$s++)sqlsrv_fetch($this->_result);}function
__destruct(){sqlsrv_free_stmt($this->_result);}}}elseif(extension_loaded("mssql")){class
Min_DB{var$extension="MSSQL",$_link,$_result,$server_info,$affected_rows,$error;function
connect($N,$V,$F){$this->_link=@mssql_connect($N,$V,$F);if($this->_link){$H=$this->query("SELECT SERVERPROPERTY('ProductLevel'), SERVERPROPERTY('Edition')");if($H){$J=$H->fetch_row();$this->server_info=$this->result("sp_server_info 2",2)." [$J[0]] $J[1]";}}else$this->error=mssql_get_last_message();return(bool)$this->_link;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($j){return
mssql_select_db($j);}function
query($G,$Ai=false){$H=@mssql_query($G,$this->_link);$this->error="";if(!$H){$this->error=mssql_get_last_message();return
false;}if($H===true){$this->affected_rows=mssql_rows_affected($this->_link);return
true;}return
new
Min_Result($H);}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
mssql_next_result($this->_result->_result);}function
result($G,$o=0){$H=$this->query($G);if(!is_object($H))return
false;return
mssql_result($H->_result,0,$o);}}class
Min_Result{var$_result,$_offset=0,$_fields,$num_rows;function
__construct($H){$this->_result=$H;$this->num_rows=mssql_num_rows($H);}function
fetch_assoc(){return
mssql_fetch_assoc($this->_result);}function
fetch_row(){return
mssql_fetch_row($this->_result);}function
num_rows(){return
mssql_num_rows($this->_result);}function
fetch_field(){$I=mssql_fetch_field($this->_result);$I->orgtable=$I->table;$I->orgname=$I->name;return$I;}function
seek($D){mssql_data_seek($this->_result,$D);}function
__destruct(){mssql_free_result($this->_result);}}}elseif(extension_loaded("pdo_dblib")){class
Min_DB
extends
Min_PDO{var$extension="PDO_DBLIB";function
connect($N,$V,$F){$this->dsn("dblib:charset=utf8;host=".str_replace(":",";unix_socket=",preg_replace('~:(\d)~',';port=\1',$N)),$V,$F);return
true;}function
select_db($j){return$this->query("USE ".idf_escape($j));}}}class
Min_Driver
extends
Min_SQL{function
insertUpdate($Q,$K,$ig){foreach($K
as$O){$Hi=array();$Z=array();foreach($O
as$y=>$X){$Hi[]="$y = $X";if(isset($ig[idf_unescape($y)]))$Z[]="$y = $X";}if(!queries("MERGE ".table($Q)." USING (VALUES(".implode(", ",$O).")) AS source (c".implode(", c",range(1,count($O))).") ON ".implode(" AND ",$Z)." WHEN MATCHED THEN UPDATE SET ".implode(", ",$Hi)." WHEN NOT MATCHED THEN INSERT (".implode(", ",array_keys($O)).") VALUES (".implode(", ",$O).");"))return
false;}return
true;}function
begin(){return
queries("BEGIN TRANSACTION");}}function
idf_escape($u){return"[".str_replace("]","]]",$u)."]";}function
table($u){return($_GET["ns"]!=""?idf_escape($_GET["ns"]).".":"").idf_escape($u);}function
connect(){global$b;$g=new
Min_DB;$Gb=$b->credentials();if($g->connect($Gb[0],$Gb[1],$Gb[2]))return$g;return$g->error;}function
get_databases(){return
get_vals("SELECT name FROM sys.databases WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')");}function
limit($G,$Z,$z,$D=0,$M=" "){return($z!==null?" TOP (".($z+$D).")":"")." $G$Z";}function
limit1($Q,$G,$Z,$M="\n"){return
limit($G,$Z,1,0,$M);}function
db_collation($l,$ob){global$g;return$g->result("SELECT collation_name FROM sys.databases WHERE name = ".q($l));}function
engines(){return
array();}function
logged_user(){global$g;return$g->result("SELECT SUSER_NAME()");}function
tables_list(){return
get_key_vals("SELECT name, type_desc FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ORDER BY name");}function
count_tables($k){global$g;$I=array();foreach($k
as$l){$g->select_db($l);$I[$l]=$g->result("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES");}return$I;}function
table_status($C=""){$I=array();foreach(get_rows("SELECT name AS Name, type_desc AS Engine FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ".($C!=""?"AND name = ".q($C):"ORDER BY name"))as$J){if($C!="")return$J;$I[$J["Name"]]=$J;}return$I;}function
is_view($R){return$R["Engine"]=="VIEW";}function
fk_support($R){return
true;}function
fields($Q){$I=array();foreach(get_rows("SELECT c.max_length, c.precision, c.scale, c.name, c.is_nullable, c.is_identity, c.collation_name, t.name type, CAST(d.definition as text) [default]
FROM sys.all_columns c
JOIN sys.all_objects o ON c.object_id = o.object_id
JOIN sys.types t ON c.user_type_id = t.user_type_id
LEFT JOIN sys.default_constraints d ON c.default_object_id = d.parent_column_id
WHERE o.schema_id = SCHEMA_ID(".q(get_schema()).") AND o.type IN ('S', 'U', 'V') AND o.name = ".q($Q))as$J){$T=$J["type"];$te=(preg_match("~char|binary~",$T)?$J["max_length"]:($T=="decimal"?"$J[precision],$J[scale]":""));$I[$J["name"]]=array("field"=>$J["name"],"full_type"=>$T.($te?"($te)":""),"type"=>$T,"length"=>$te,"default"=>$J["default"],"null"=>$J["is_nullable"],"auto_increment"=>$J["is_identity"],"collation"=>$J["collation_name"],"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),"primary"=>$J["is_identity"],);}return$I;}function
indexes($Q,$h=null){$I=array();foreach(get_rows("SELECT i.name, key_ordinal, is_unique, is_primary_key, c.name AS column_name, is_descending_key
FROM sys.indexes i
INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
WHERE OBJECT_NAME(i.object_id) = ".q($Q),$h)as$J){$C=$J["name"];$I[$C]["type"]=($J["is_primary_key"]?"PRIMARY":($J["is_unique"]?"UNIQUE":"INDEX"));$I[$C]["lengths"]=array();$I[$C]["columns"][$J["key_ordinal"]]=$J["column_name"];$I[$C]["descs"][$J["key_ordinal"]]=($J["is_descending_key"]?'1':null);}return$I;}function
viewm($C){global$g;return
array("select"=>preg_replace('~^(?:[^[]|\[[^]]*])*\s+AS\s+~isU','',$g->result("SELECT VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = SCHEMA_NAME() AND TABLE_NAME = ".q($C))));}function
collations(){$I=array();foreach(get_vals("SELECT name FROM fn_helpcollations()")as$d)$I[preg_replace('~_.*~','',$d)][]=$d;return$I;}function
information_schema($l){return
false;}function
error(){global$g;return
nl_br(h(preg_replace('~^(\[[^]]*])+~m','',$g->error)));}function
create_database($l,$d){return
queries("CREATE DATABASE ".idf_escape($l).(preg_match('~^[a-z0-9_]+$~i',$d)?" COLLATE $d":""));}function
drop_databases($k){return
queries("DROP DATABASE ".implode(", ",array_map('idf_escape',$k)));}function
rename_database($C,$d){if(preg_match('~^[a-z0-9_]+$~i',$d))queries("ALTER DATABASE ".idf_escape(DB)." COLLATE $d");queries("ALTER DATABASE ".idf_escape(DB)." MODIFY NAME = ".idf_escape($C));return
true;}function
auto_increment(){return" IDENTITY".($_POST["Auto_increment"]!=""?"(".number($_POST["Auto_increment"]).",1)":"")." PRIMARY KEY";}function
alter_table($Q,$C,$p,$cd,$tb,$uc,$d,$La,$Sf){$c=array();foreach($p
as$o){$e=idf_escape($o[0]);$X=$o[1];if(!$X)$c["DROP"][]=" COLUMN $e";else{$X[1]=preg_replace("~( COLLATE )'(\\w+)'~",'\1\2',$X[1]);if($o[0]=="")$c["ADD"][]="\n  ".implode("",$X).($Q==""?substr($cd[$X[0]],16+strlen($X[0])):"");else{unset($X[6]);if($e!=$X[0])queries("EXEC sp_rename ".q(table($Q).".$e").", ".q(idf_unescape($X[0])).", 'COLUMN'");$c["ALTER COLUMN ".implode("",$X)][]="";}}}if($Q=="")return
queries("CREATE TABLE ".table($C)." (".implode(",",(array)$c["ADD"])."\n)");if($Q!=$C)queries("EXEC sp_rename ".q(table($Q)).", ".q($C));if($cd)$c[""]=$cd;foreach($c
as$y=>$X){if(!queries("ALTER TABLE ".idf_escape($C)." $y".implode(",",$X)))return
false;}return
true;}function
alter_indexes($Q,$c){$v=array();$fc=array();foreach($c
as$X){if($X[2]=="DROP"){if($X[0]=="PRIMARY")$fc[]=idf_escape($X[1]);else$v[]=idf_escape($X[1])." ON ".table($Q);}elseif(!queries(($X[0]!="PRIMARY"?"CREATE $X[0] ".($X[0]!="INDEX"?"INDEX ":"").idf_escape($X[1]!=""?$X[1]:uniqid($Q."_"))." ON ".table($Q):"ALTER TABLE ".table($Q)." ADD PRIMARY KEY")." (".implode(", ",$X[2]).")"))return
false;}return(!$v||queries("DROP INDEX ".implode(", ",$v)))&&(!$fc||queries("ALTER TABLE ".table($Q)." DROP ".implode(", ",$fc)));}function
last_id(){global$g;return$g->result("SELECT SCOPE_IDENTITY()");}function
explain($g,$G){$g->query("SET SHOWPLAN_ALL ON");$I=$g->query($G);$g->query("SET SHOWPLAN_ALL OFF");return$I;}function
found_rows($R,$Z){}function
foreign_keys($Q){$I=array();foreach(get_rows("EXEC sp_fkeys @fktable_name = ".q($Q))as$J){$q=&$I[$J["FK_NAME"]];$q["table"]=$J["PKTABLE_NAME"];$q["source"][]=$J["FKCOLUMN_NAME"];$q["target"][]=$J["PKCOLUMN_NAME"];}return$I;}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Xi){return
queries("DROP VIEW ".implode(", ",array_map('table',$Xi)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$Xi,$Vh){return
apply_queries("ALTER SCHEMA ".idf_escape($Vh)." TRANSFER",array_merge($S,$Xi));}function
trigger($C){if($C=="")return
array();$K=get_rows("SELECT s.name [Trigger],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(s.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(s.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing],
c.text
FROM sysobjects s
JOIN syscomments c ON s.id = c.id
WHERE s.xtype = 'TR' AND s.name = ".q($C));$I=reset($K);if($I)$I["Statement"]=preg_replace('~^.+\s+AS\s+~isU','',$I["text"]);return$I;}function
triggers($Q){$I=array();foreach(get_rows("SELECT sys1.name,
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing]
FROM sysobjects sys1
JOIN sysobjects sys2 ON sys1.parent_obj = sys2.id
WHERE sys1.xtype = 'TR' AND sys2.name = ".q($Q))as$J)$I[$J["name"]]=array($J["Timing"],$J["Event"]);return$I;}function
trigger_options(){return
array("Timing"=>array("AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("AS"),);}function
schemas(){return
get_vals("SELECT name FROM sys.schemas");}function
get_schema(){global$g;if($_GET["ns"]!="")return$_GET["ns"];return$g->result("SELECT SCHEMA_NAME()");}function
set_schema($Yg){return
true;}function
use_sql($j){return"USE ".idf_escape($j);}function
show_variables(){return
array();}function
show_status(){return
array();}function
convert_field($o){}function
unconvert_field($o,$I){return$I;}function
support($Pc){return
preg_match('~^(columns|database|drop_col|indexes|descidx|scheme|sql|table|trigger|view|view_trigger)$~',$Pc);}$x="mssql";$U=array();$Fh=array();foreach(array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"int"=>10,"bigint"=>20,"bit"=>1,"decimal"=>0,"real"=>12,"float"=>53,"smallmoney"=>10,"money"=>20),'Date and time'=>array("date"=>10,"smalldatetime"=>19,"datetime"=>19,"datetime2"=>19,"time"=>8,"datetimeoffset"=>10),'Strings'=>array("char"=>8000,"varchar"=>8000,"text"=>2147483647,"nchar"=>4000,"nvarchar"=>4000,"ntext"=>1073741823),'Binary'=>array("binary"=>8000,"varbinary"=>8000,"image"=>2147483647),)as$y=>$X){$U+=$X;$Fh[$y]=array_keys($X);}$Gi=array();$tf=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");$kd=array("len","lower","round","upper");$qd=array("avg","count","count distinct","max","min","sum");$mc=array(array("date|time"=>"getdate",),array("int|decimal|real|float|money|datetime"=>"+/-","char|text"=>"+",));}$ec['firebird']='Firebird (alpha)';if(isset($_GET["firebird"])){$fg=array("interbase");define("DRIVER","firebird");if(extension_loaded("interbase")){class
Min_DB{var$extension="Firebird",$server_info,$affected_rows,$errno,$error,$_link,$_result;function
connect($N,$V,$F){$this->_link=ibase_connect($N,$V,$F);if($this->_link){$Ki=explode(':',$N);$this->service_link=ibase_service_attach($Ki[0],$V,$F);$this->server_info=ibase_server_info($this->service_link,IBASE_SVC_SERVER_VERSION);}else{$this->errno=ibase_errcode();$this->error=ibase_errmsg();}return(bool)$this->_link;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($j){return($j=="domain");}function
query($G,$Ai=false){$H=ibase_query($G,$this->_link);if(!$H){$this->errno=ibase_errcode();$this->error=ibase_errmsg();return
false;}$this->error="";if($H===true){$this->affected_rows=ibase_affected_rows($this->_link);return
true;}return
new
Min_Result($H);}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($G,$o=0){$H=$this->query($G);if(!$H||!$H->num_rows)return
false;$J=$H->fetch_row();return$J[$o];}}class
Min_Result{var$num_rows,$_result,$_offset=0;function
__construct($H){$this->_result=$H;}function
fetch_assoc(){return
ibase_fetch_assoc($this->_result);}function
fetch_row(){return
ibase_fetch_row($this->_result);}function
fetch_field(){$o=ibase_field_info($this->_result,$this->_offset++);return(object)array('name'=>$o['name'],'orgname'=>$o['name'],'type'=>$o['type'],'charsetnr'=>$o['length'],);}function
__destruct(){ibase_free_result($this->_result);}}}class
Min_Driver
extends
Min_SQL{}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
connect(){global$b;$g=new
Min_DB;$Gb=$b->credentials();if($g->connect($Gb[0],$Gb[1],$Gb[2]))return$g;return$g->error;}function
get_databases($ad){return
array("domain");}function
limit($G,$Z,$z,$D=0,$M=" "){$I='';$I.=($z!==null?$M."FIRST $z".($D?" SKIP $D":""):"");$I.=" $G$Z";return$I;}function
limit1($Q,$G,$Z,$M="\n"){return
limit($G,$Z,1,0,$M);}function
db_collation($l,$ob){}function
engines(){return
array();}function
logged_user(){global$b;$Gb=$b->credentials();return$Gb[1];}function
tables_list(){global$g;$G='SELECT RDB$RELATION_NAME FROM rdb$relations WHERE rdb$system_flag = 0';$H=ibase_query($g->_link,$G);$I=array();while($J=ibase_fetch_assoc($H))$I[$J['RDB$RELATION_NAME']]='table';ksort($I);return$I;}function
count_tables($k){return
array();}function
table_status($C="",$Oc=false){global$g;$I=array();$Lb=tables_list();foreach($Lb
as$v=>$X){$v=trim($v);$I[$v]=array('Name'=>$v,'Engine'=>'standard',);if($C==$v)return$I[$v];}return$I;}function
is_view($R){return
false;}function
fk_support($R){return
preg_match('~InnoDB|IBMDB2I~i',$R["Engine"]);}function
fields($Q){global$g;$I=array();$G='SELECT r.RDB$FIELD_NAME AS field_name,
r.RDB$DESCRIPTION AS field_description,
r.RDB$DEFAULT_VALUE AS field_default_value,
r.RDB$NULL_FLAG AS field_not_null_constraint,
f.RDB$FIELD_LENGTH AS field_length,
f.RDB$FIELD_PRECISION AS field_precision,
f.RDB$FIELD_SCALE AS field_scale,
CASE f.RDB$FIELD_TYPE
WHEN 261 THEN \'BLOB\'
WHEN 14 THEN \'CHAR\'
WHEN 40 THEN \'CSTRING\'
WHEN 11 THEN \'D_FLOAT\'
WHEN 27 THEN \'DOUBLE\'
WHEN 10 THEN \'FLOAT\'
WHEN 16 THEN \'INT64\'
WHEN 8 THEN \'INTEGER\'
WHEN 9 THEN \'QUAD\'
WHEN 7 THEN \'SMALLINT\'
WHEN 12 THEN \'DATE\'
WHEN 13 THEN \'TIME\'
WHEN 35 THEN \'TIMESTAMP\'
WHEN 37 THEN \'VARCHAR\'
ELSE \'UNKNOWN\'
END AS field_type,
f.RDB$FIELD_SUB_TYPE AS field_subtype,
coll.RDB$COLLATION_NAME AS field_collation,
cset.RDB$CHARACTER_SET_NAME AS field_charset
FROM RDB$RELATION_FIELDS r
LEFT JOIN RDB$FIELDS f ON r.RDB$FIELD_SOURCE = f.RDB$FIELD_NAME
LEFT JOIN RDB$COLLATIONS coll ON f.RDB$COLLATION_ID = coll.RDB$COLLATION_ID
LEFT JOIN RDB$CHARACTER_SETS cset ON f.RDB$CHARACTER_SET_ID = cset.RDB$CHARACTER_SET_ID
WHERE r.RDB$RELATION_NAME = '.q($Q).'
ORDER BY r.RDB$FIELD_POSITION';$H=ibase_query($g->_link,$G);while($J=ibase_fetch_assoc($H))$I[trim($J['FIELD_NAME'])]=array("field"=>trim($J["FIELD_NAME"]),"full_type"=>trim($J["FIELD_TYPE"]),"type"=>trim($J["FIELD_SUB_TYPE"]),"default"=>trim($J['FIELD_DEFAULT_VALUE']),"null"=>(trim($J["FIELD_NOT_NULL_CONSTRAINT"])=="YES"),"auto_increment"=>'0',"collation"=>trim($J["FIELD_COLLATION"]),"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),"comment"=>trim($J["FIELD_DESCRIPTION"]),);return$I;}function
indexes($Q,$h=null){$I=array();return$I;}function
foreign_keys($Q){return
array();}function
collations(){return
array();}function
information_schema($l){return
false;}function
error(){global$g;return
h($g->error);}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Yg){return
true;}function
support($Pc){return
preg_match("~^(columns|sql|status|table)$~",$Pc);}$x="firebird";$tf=array("=");$kd=array();$qd=array();$mc=array();}$ec["simpledb"]="SimpleDB";if(isset($_GET["simpledb"])){$fg=array("SimpleXML + allow_url_fopen");define("DRIVER","simpledb");if(class_exists('SimpleXMLElement')&&ini_bool('allow_url_fopen')){class
Min_DB{var$extension="SimpleXML",$server_info='2009-04-15',$error,$timeout,$next,$affected_rows,$_result;function
select_db($j){return($j=="domain");}function
query($G,$Ai=false){$Mf=array('SelectExpression'=>$G,'ConsistentRead'=>'true');if($this->next)$Mf['NextToken']=$this->next;$H=sdb_request_all('Select','Item',$Mf,$this->timeout);$this->timeout=0;if($H===false)return$H;if(preg_match('~^\s*SELECT\s+COUNT\(~i',$G)){$Jh=0;foreach($H
as$be)$Jh+=$be->Attribute->Value;$H=array((object)array('Attribute'=>array((object)array('Name'=>'Count','Value'=>$Jh,))));}return
new
Min_Result($H);}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
quote($P){return"'".str_replace("'","''",$P)."'";}}class
Min_Result{var$num_rows,$_rows=array(),$_offset=0;function
__construct($H){foreach($H
as$be){$J=array();if($be->Name!='')$J['itemName()']=(string)$be->Name;foreach($be->Attribute
as$Ia){$C=$this->_processValue($Ia->Name);$Y=$this->_processValue($Ia->Value);if(isset($J[$C])){$J[$C]=(array)$J[$C];$J[$C][]=$Y;}else$J[$C]=$Y;}$this->_rows[]=$J;foreach($J
as$y=>$X){if(!isset($this->_rows[0][$y]))$this->_rows[0][$y]=null;}}$this->num_rows=count($this->_rows);}function
_processValue($pc){return(is_object($pc)&&$pc['encoding']=='base64'?base64_decode($pc):(string)$pc);}function
fetch_assoc(){$J=current($this->_rows);if(!$J)return$J;$I=array();foreach($this->_rows[0]as$y=>$X)$I[$y]=$J[$y];next($this->_rows);return$I;}function
fetch_row(){$I=$this->fetch_assoc();if(!$I)return$I;return
array_values($I);}function
fetch_field(){$he=array_keys($this->_rows[0]);return(object)array('name'=>$he[$this->_offset++]);}}}class
Min_Driver
extends
Min_SQL{public$ig="itemName()";function
_chunkRequest($Ed,$va,$Mf,$Ec=array()){global$g;foreach(array_chunk($Ed,25)as$hb){$Nf=$Mf;foreach($hb
as$s=>$t){$Nf["Item.$s.ItemName"]=$t;foreach($Ec
as$y=>$X)$Nf["Item.$s.$y"]=$X;}if(!sdb_request($va,$Nf))return
false;}$g->affected_rows=count($Ed);return
true;}function
_extractIds($Q,$ug,$z){$I=array();if(preg_match_all("~itemName\(\) = (('[^']*+')+)~",$ug,$Ce))$I=array_map('idf_unescape',$Ce[1]);else{foreach(sdb_request_all('Select','Item',array('SelectExpression'=>'SELECT itemName() FROM '.table($Q).$ug.($z?" LIMIT 1":"")))as$be)$I[]=$be->Name;}return$I;}function
select($Q,$L,$Z,$nd,$yf=array(),$z=1,$E=0,$kg=false){global$g;$g->next=$_GET["next"];$I=parent::select($Q,$L,$Z,$nd,$yf,$z,$E,$kg);$g->next=0;return$I;}function
delete($Q,$ug,$z=0){return$this->_chunkRequest($this->_extractIds($Q,$ug,$z),'BatchDeleteAttributes',array('DomainName'=>$Q));}function
update($Q,$O,$ug,$z=0,$M="\n"){$Ub=array();$Td=array();$s=0;$Ed=$this->_extractIds($Q,$ug,$z);$t=idf_unescape($O["`itemName()`"]);unset($O["`itemName()`"]);foreach($O
as$y=>$X){$y=idf_unescape($y);if($X=="NULL"||($t!=""&&array($t)!=$Ed))$Ub["Attribute.".count($Ub).".Name"]=$y;if($X!="NULL"){foreach((array)$X
as$de=>$W){$Td["Attribute.$s.Name"]=$y;$Td["Attribute.$s.Value"]=(is_array($X)?$W:idf_unescape($W));if(!$de)$Td["Attribute.$s.Replace"]="true";$s++;}}}$Mf=array('DomainName'=>$Q);return(!$Td||$this->_chunkRequest(($t!=""?array($t):$Ed),'BatchPutAttributes',$Mf,$Td))&&(!$Ub||$this->_chunkRequest($Ed,'BatchDeleteAttributes',$Mf,$Ub));}function
insert($Q,$O){$Mf=array("DomainName"=>$Q);$s=0;foreach($O
as$C=>$Y){if($Y!="NULL"){$C=idf_unescape($C);if($C=="itemName()")$Mf["ItemName"]=idf_unescape($Y);else{foreach((array)$Y
as$X){$Mf["Attribute.$s.Name"]=$C;$Mf["Attribute.$s.Value"]=(is_array($Y)?$X:idf_unescape($Y));$s++;}}}}return
sdb_request('PutAttributes',$Mf);}function
insertUpdate($Q,$K,$ig){foreach($K
as$O){if(!$this->update($Q,$O,"WHERE `itemName()` = ".q($O["`itemName()`"])))return
false;}return
true;}function
begin(){return
false;}function
commit(){return
false;}function
rollback(){return
false;}function
slowQuery($G,$di){$this->_conn->timeout=$di;return$G;}}function
connect(){global$b;list(,,$F)=$b->credentials();if($F!="")return'Database does not support password.';return
new
Min_DB;}function
support($Pc){return
preg_match('~sql~',$Pc);}function
logged_user(){global$b;$Gb=$b->credentials();return$Gb[1];}function
get_databases(){return
array("domain");}function
collations(){return
array();}function
db_collation($l,$ob){}function
tables_list(){global$g;$I=array();foreach(sdb_request_all('ListDomains','DomainName')as$Q)$I[(string)$Q]='table';if($g->error&&defined("PAGE_HEADER"))echo"<p class='error'>".error()."\n";return$I;}function
table_status($C="",$Oc=false){$I=array();foreach(($C!=""?array($C=>true):tables_list())as$Q=>$T){$J=array("Name"=>$Q,"Auto_increment"=>"");if(!$Oc){$Pe=sdb_request('DomainMetadata',array('DomainName'=>$Q));if($Pe){foreach(array("Rows"=>"ItemCount","Data_length"=>"ItemNamesSizeBytes","Index_length"=>"AttributeValuesSizeBytes","Data_free"=>"AttributeNamesSizeBytes",)as$y=>$X)$J[$y]=(string)$Pe->$X;}}if($C!="")return$J;$I[$Q]=$J;}return$I;}function
explain($g,$G){}function
error(){global$g;return
h($g->error);}function
information_schema(){}function
is_view($R){}function
indexes($Q,$h=null){return
array(array("type"=>"PRIMARY","columns"=>array("itemName()")),);}function
fields($Q){return
fields_from_edit();}function
foreign_keys($Q){return
array();}function
table($u){return
idf_escape($u);}function
idf_escape($u){return"`".str_replace("`","``",$u)."`";}function
limit($G,$Z,$z,$D=0,$M=" "){return" $G$Z".($z!==null?$M."LIMIT $z":"");}function
unconvert_field($o,$I){return$I;}function
fk_support($R){}function
engines(){return
array();}function
alter_table($Q,$C,$p,$cd,$tb,$uc,$d,$La,$Sf){return($Q==""&&sdb_request('CreateDomain',array('DomainName'=>$C)));}function
drop_tables($S){foreach($S
as$Q){if(!sdb_request('DeleteDomain',array('DomainName'=>$Q)))return
false;}return
true;}function
count_tables($k){foreach($k
as$l)return
array($l=>count(tables_list()));}function
found_rows($R,$Z){return($Z?null:$R["Rows"]);}function
last_id(){}function
hmac($Ba,$Lb,$y,$yg=false){$Ua=64;if(strlen($y)>$Ua)$y=pack("H*",$Ba($y));$y=str_pad($y,$Ua,"\0");$ee=$y^str_repeat("\x36",$Ua);$fe=$y^str_repeat("\x5C",$Ua);$I=$Ba($fe.pack("H*",$Ba($ee.$Lb)));if($yg)$I=pack("H*",$I);return$I;}function
sdb_request($va,$Mf=array()){global$b,$g;list($Ad,$Mf['AWSAccessKeyId'],$bh)=$b->credentials();$Mf['Action']=$va;$Mf['Timestamp']=gmdate('Y-m-d\TH:i:s+00:00');$Mf['Version']='2009-04-15';$Mf['SignatureVersion']=2;$Mf['SignatureMethod']='HmacSHA1';ksort($Mf);$G='';foreach($Mf
as$y=>$X)$G.='&'.rawurlencode($y).'='.rawurlencode($X);$G=str_replace('%7E','~',substr($G,1));$G.="&Signature=".urlencode(base64_encode(hmac('sha1',"POST\n".preg_replace('~^https?://~','',$Ad)."\n/\n$G",$bh,true)));@ini_set('track_errors',1);$Tc=@file_get_contents((preg_match('~^https?://~',$Ad)?$Ad:"http://$Ad"),false,stream_context_create(array('http'=>array('method'=>'POST','content'=>$G,'ignore_errors'=>1,))));if(!$Tc){$g->error=$php_errormsg;return
false;}libxml_use_internal_errors(true);$kj=simplexml_load_string($Tc);if(!$kj){$n=libxml_get_last_error();$g->error=$n->message;return
false;}if($kj->Errors){$n=$kj->Errors->Error;$g->error="$n->Message ($n->Code)";return
false;}$g->error='';$Uh=$va."Result";return($kj->$Uh?$kj->$Uh:true);}function
sdb_request_all($va,$Uh,$Mf=array(),$di=0){$I=array();$Ah=($di?microtime(true):0);$z=(preg_match('~LIMIT\s+(\d+)\s*$~i',$Mf['SelectExpression'],$B)?$B[1]:0);do{$kj=sdb_request($va,$Mf);if(!$kj)break;foreach($kj->$Uh
as$pc)$I[]=$pc;if($z&&count($I)>=$z){$_GET["next"]=$kj->NextToken;break;}if($di&&microtime(true)-$Ah>$di)return
false;$Mf['NextToken']=$kj->NextToken;if($z)$Mf['SelectExpression']=preg_replace('~\d+\s*$~',$z-count($I),$Mf['SelectExpression']);}while($kj->NextToken);return$I;}$x="simpledb";$tf=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","IS NOT NULL");$kd=array();$qd=array("count");$mc=array(array("json"));}$ec["mongo"]="MongoDB";if(isset($_GET["mongo"])){$fg=array("mongo","mongodb");define("DRIVER","mongo");if(class_exists('MongoDB')){class
Min_DB{var$extension="Mongo",$server_info=MongoClient::VERSION,$error,$last_id,$_link,$_db;function
connect($Ii,$wf){return@new
MongoClient($Ii,$wf);}function
query($G){return
false;}function
select_db($j){try{$this->_db=$this->_link->selectDB($j);return
true;}catch(Exception$Ac){$this->error=$Ac->getMessage();return
false;}}function
quote($P){return$P;}}class
Min_Result{var$num_rows,$_rows=array(),$_offset=0,$_charset=array();function
__construct($H){foreach($H
as$be){$J=array();foreach($be
as$y=>$X){if(is_a($X,'MongoBinData'))$this->_charset[$y]=63;$J[$y]=(is_a($X,'MongoId')?'ObjectId("'.strval($X).'")':(is_a($X,'MongoDate')?gmdate("Y-m-d H:i:s",$X->sec)." GMT":(is_a($X,'MongoBinData')?$X->bin:(is_a($X,'MongoRegex')?strval($X):(is_object($X)?get_class($X):$X)))));}$this->_rows[]=$J;foreach($J
as$y=>$X){if(!isset($this->_rows[0][$y]))$this->_rows[0][$y]=null;}}$this->num_rows=count($this->_rows);}function
fetch_assoc(){$J=current($this->_rows);if(!$J)return$J;$I=array();foreach($this->_rows[0]as$y=>$X)$I[$y]=$J[$y];next($this->_rows);return$I;}function
fetch_row(){$I=$this->fetch_assoc();if(!$I)return$I;return
array_values($I);}function
fetch_field(){$he=array_keys($this->_rows[0]);$C=$he[$this->_offset++];return(object)array('name'=>$C,'charsetnr'=>$this->_charset[$C],);}}class
Min_Driver
extends
Min_SQL{public$ig="_id";function
select($Q,$L,$Z,$nd,$yf=array(),$z=1,$E=0,$kg=false){$L=($L==array("*")?array():array_fill_keys($L,true));$sh=array();foreach($yf
as$X){$X=preg_replace('~ DESC$~','',$X,1,$Db);$sh[$X]=($Db?-1:1);}return
new
Min_Result($this->_conn->_db->selectCollection($Q)->find(array(),$L)->sort($sh)->limit($z!=""?+$z:0)->skip($E*$z));}function
insert($Q,$O){try{$I=$this->_conn->_db->selectCollection($Q)->insert($O);$this->_conn->errno=$I['code'];$this->_conn->error=$I['err'];$this->_conn->last_id=$O['_id'];return!$I['err'];}catch(Exception$Ac){$this->_conn->error=$Ac->getMessage();return
false;}}}function
get_databases($ad){global$g;$I=array();$Qb=$g->_link->listDBs();foreach($Qb['databases']as$l)$I[]=$l['name'];return$I;}function
count_tables($k){global$g;$I=array();foreach($k
as$l)$I[$l]=count($g->_link->selectDB($l)->getCollectionNames(true));return$I;}function
tables_list(){global$g;return
array_fill_keys($g->_db->getCollectionNames(true),'table');}function
drop_databases($k){global$g;foreach($k
as$l){$Kg=$g->_link->selectDB($l)->drop();if(!$Kg['ok'])return
false;}return
true;}function
indexes($Q,$h=null){global$g;$I=array();foreach($g->_db->selectCollection($Q)->getIndexInfo()as$v){$Xb=array();foreach($v["key"]as$e=>$T)$Xb[]=($T==-1?'1':null);$I[$v["name"]]=array("type"=>($v["name"]=="_id_"?"PRIMARY":($v["unique"]?"UNIQUE":"INDEX")),"columns"=>array_keys($v["key"]),"lengths"=>array(),"descs"=>$Xb,);}return$I;}function
fields($Q){return
fields_from_edit();}function
found_rows($R,$Z){global$g;return$g->_db->selectCollection($_GET["select"])->count($Z);}$tf=array("=");}elseif(class_exists('MongoDB\Driver\Manager')){class
Min_DB{var$extension="MongoDB",$server_info=MONGODB_VERSION,$error,$last_id;var$_link;var$_db,$_db_name;function
connect($Ii,$wf){$jb='MongoDB\Driver\Manager';return
new$jb($Ii,$wf);}function
query($G){return
false;}function
select_db($j){$this->_db_name=$j;return
true;}function
quote($P){return$P;}}class
Min_Result{var$num_rows,$_rows=array(),$_offset=0,$_charset=array();function
__construct($H){foreach($H
as$be){$J=array();foreach($be
as$y=>$X){if(is_a($X,'MongoDB\BSON\Binary'))$this->_charset[$y]=63;$J[$y]=(is_a($X,'MongoDB\BSON\ObjectID')?'MongoDB\BSON\ObjectID("'.strval($X).'")':(is_a($X,'MongoDB\BSON\UTCDatetime')?$X->toDateTime()->format('Y-m-d H:i:s'):(is_a($X,'MongoDB\BSON\Binary')?$X->bin:(is_a($X,'MongoDB\BSON\Regex')?strval($X):(is_object($X)?json_encode($X,256):$X)))));}$this->_rows[]=$J;foreach($J
as$y=>$X){if(!isset($this->_rows[0][$y]))$this->_rows[0][$y]=null;}}$this->num_rows=$H->count;}function
fetch_assoc(){$J=current($this->_rows);if(!$J)return$J;$I=array();foreach($this->_rows[0]as$y=>$X)$I[$y]=$J[$y];next($this->_rows);return$I;}function
fetch_row(){$I=$this->fetch_assoc();if(!$I)return$I;return
array_values($I);}function
fetch_field(){$he=array_keys($this->_rows[0]);$C=$he[$this->_offset++];return(object)array('name'=>$C,'charsetnr'=>$this->_charset[$C],);}}class
Min_Driver
extends
Min_SQL{public$ig="_id";function
select($Q,$L,$Z,$nd,$yf=array(),$z=1,$E=0,$kg=false){global$g;$L=($L==array("*")?array():array_fill_keys($L,1));if(count($L)&&!isset($L['_id']))$L['_id']=0;$Z=where_to_query($Z);$sh=array();foreach($yf
as$X){$X=preg_replace('~ DESC$~','',$X,1,$Db);$sh[$X]=($Db?-1:1);}if(isset($_GET['limit'])&&is_numeric($_GET['limit'])&&$_GET['limit']>0)$z=$_GET['limit'];$z=min(200,max(1,(int)$z));$ph=$E*$z;$jb='MongoDB\Driver\Query';$G=new$jb($Z,array('projection'=>$L,'limit'=>$z,'skip'=>$ph,'sort'=>$sh));$Ng=$g->_link->executeQuery("$g->_db_name.$Q",$G);return
new
Min_Result($Ng);}function
update($Q,$O,$ug,$z=0,$M="\n"){global$g;$l=$g->_db_name;$Z=sql_query_where_parser($ug);$jb='MongoDB\Driver\BulkWrite';$Ya=new$jb(array());if(isset($O['_id']))unset($O['_id']);$Hg=array();foreach($O
as$y=>$Y){if($Y=='NULL'){$Hg[$y]=1;unset($O[$y]);}}$Hi=array('$set'=>$O);if(count($Hg))$Hi['$unset']=$Hg;$Ya->update($Z,$Hi,array('upsert'=>false));$Ng=$g->_link->executeBulkWrite("$l.$Q",$Ya);$g->affected_rows=$Ng->getModifiedCount();return
true;}function
delete($Q,$ug,$z=0){global$g;$l=$g->_db_name;$Z=sql_query_where_parser($ug);$jb='MongoDB\Driver\BulkWrite';$Ya=new$jb(array());$Ya->delete($Z,array('limit'=>$z));$Ng=$g->_link->executeBulkWrite("$l.$Q",$Ya);$g->affected_rows=$Ng->getDeletedCount();return
true;}function
insert($Q,$O){global$g;$l=$g->_db_name;$jb='MongoDB\Driver\BulkWrite';$Ya=new$jb(array());if(isset($O['_id'])&&empty($O['_id']))unset($O['_id']);$Ya->insert($O);$Ng=$g->_link->executeBulkWrite("$l.$Q",$Ya);$g->affected_rows=$Ng->getInsertedCount();return
true;}}function
get_databases($ad){global$g;$I=array();$jb='MongoDB\Driver\Command';$rb=new$jb(array('listDatabases'=>1));$Ng=$g->_link->executeCommand('admin',$rb);foreach($Ng
as$Qb){foreach($Qb->databases
as$l)$I[]=$l->name;}return$I;}function
count_tables($k){$I=array();return$I;}function
tables_list(){global$g;$jb='MongoDB\Driver\Command';$rb=new$jb(array('listCollections'=>1));$Ng=$g->_link->executeCommand($g->_db_name,$rb);$pb=array();foreach($Ng
as$H)$pb[$H->name]='table';return$pb;}function
drop_databases($k){return
false;}function
indexes($Q,$h=null){global$g;$I=array();$jb='MongoDB\Driver\Command';$rb=new$jb(array('listIndexes'=>$Q));$Ng=$g->_link->executeCommand($g->_db_name,$rb);foreach($Ng
as$v){$Xb=array();$f=array();foreach(get_object_vars($v->key)as$e=>$T){$Xb[]=($T==-1?'1':null);$f[]=$e;}$I[$v->name]=array("type"=>($v->name=="_id_"?"PRIMARY":(isset($v->unique)?"UNIQUE":"INDEX")),"columns"=>$f,"lengths"=>array(),"descs"=>$Xb,);}return$I;}function
fields($Q){$p=fields_from_edit();if(!count($p)){global$m;$H=$m->select($Q,array("*"),null,null,array(),10);while($J=$H->fetch_assoc()){foreach($J
as$y=>$X){$J[$y]=null;$p[$y]=array("field"=>$y,"type"=>"string","null"=>($y!=$m->primary),"auto_increment"=>($y==$m->primary),"privileges"=>array("insert"=>1,"select"=>1,"update"=>1,),);}}}return$p;}function
found_rows($R,$Z){global$g;$Z=where_to_query($Z);$jb='MongoDB\Driver\Command';$rb=new$jb(array('count'=>$R['Name'],'query'=>$Z));$Ng=$g->_link->executeCommand($g->_db_name,$rb);$li=$Ng->toArray();return$li[0]->n;}function
sql_query_where_parser($ug){$ug=trim(preg_replace('/WHERE[\s]?[(]?\(?/','',$ug));$ug=preg_replace('/\)\)\)$/',')',$ug);$hj=explode(' AND ',$ug);$ij=explode(') OR (',$ug);$Z=array();foreach($hj
as$fj)$Z[]=trim($fj);if(count($ij)==1)$ij=array();elseif(count($ij)>1)$Z=array();return
where_to_query($Z,$ij);}function
where_to_query($dj=array(),$ej=array()){global$b;$Lb=array();foreach(array('and'=>$dj,'or'=>$ej)as$T=>$Z){if(is_array($Z)){foreach($Z
as$Hc){list($mb,$rf,$X)=explode(" ",$Hc,3);if($mb=="_id"){$X=str_replace('MongoDB\BSON\ObjectID("',"",$X);$X=str_replace('")',"",$X);$jb='MongoDB\BSON\ObjectID';$X=new$jb($X);}if(!in_array($rf,$b->operators))continue;if(preg_match('~^\(f\)(.+)~',$rf,$B)){$X=(float)$X;$rf=$B[1];}elseif(preg_match('~^\(date\)(.+)~',$rf,$B)){$Nb=new
DateTime($X);$jb='MongoDB\BSON\UTCDatetime';$X=new$jb($Nb->getTimestamp()*1000);$rf=$B[1];}switch($rf){case'=':$rf='$eq';break;case'!=':$rf='$ne';break;case'>':$rf='$gt';break;case'<':$rf='$lt';break;case'>=':$rf='$gte';break;case'<=':$rf='$lte';break;case'regex':$rf='$regex';break;default:continue;}if($T=='and')$Lb['$and'][]=array($mb=>array($rf=>$X));elseif($T=='or')$Lb['$or'][]=array($mb=>array($rf=>$X));}}}return$Lb;}$tf=array("=","!=",">","<",">=","<=","regex","(f)=","(f)!=","(f)>","(f)<","(f)>=","(f)<=","(date)=","(date)!=","(date)>","(date)<","(date)>=","(date)<=",);}function
table($u){return$u;}function
idf_escape($u){return$u;}function
table_status($C="",$Oc=false){$I=array();foreach(tables_list()as$Q=>$T){$I[$Q]=array("Name"=>$Q);if($C==$Q)return$I[$Q];}return$I;}function
create_database($l,$d){return
true;}function
last_id(){global$g;return$g->last_id;}function
error(){global$g;return
h($g->error);}function
collations(){return
array();}function
logged_user(){global$b;$Gb=$b->credentials();return$Gb[1];}function
connect(){global$b;$g=new
Min_DB;list($N,$V,$F)=$b->credentials();$wf=array();if($V.$F!=""){$wf["username"]=$V;$wf["password"]=$F;}$l=$b->database();if($l!="")$wf["db"]=$l;try{$g->_link=$g->connect("mongodb://$N",$wf);if($F!=""){$wf["password"]="";try{$g->connect("mongodb://$N",$wf);return'Database does not support password.';}catch(Exception$Ac){}}return$g;}catch(Exception$Ac){return$Ac->getMessage();}}function
alter_indexes($Q,$c){global$g;foreach($c
as$X){list($T,$C,$O)=$X;if($O=="DROP")$I=$g->_db->command(array("deleteIndexes"=>$Q,"index"=>$C));else{$f=array();foreach($O
as$e){$e=preg_replace('~ DESC$~','',$e,1,$Db);$f[$e]=($Db?-1:1);}$I=$g->_db->selectCollection($Q)->ensureIndex($f,array("unique"=>($T=="UNIQUE"),"name"=>$C,));}if($I['errmsg']){$g->error=$I['errmsg'];return
false;}}return
true;}function
support($Pc){return
preg_match("~database|indexes|descidx~",$Pc);}function
db_collation($l,$ob){}function
information_schema(){}function
is_view($R){}function
convert_field($o){}function
unconvert_field($o,$I){return$I;}function
foreign_keys($Q){return
array();}function
fk_support($R){}function
engines(){return
array();}function
alter_table($Q,$C,$p,$cd,$tb,$uc,$d,$La,$Sf){global$g;if($Q==""){$g->_db->createCollection($C);return
true;}}function
drop_tables($S){global$g;foreach($S
as$Q){$Kg=$g->_db->selectCollection($Q)->drop();if(!$Kg['ok'])return
false;}return
true;}function
truncate_tables($S){global$g;foreach($S
as$Q){$Kg=$g->_db->selectCollection($Q)->remove();if(!$Kg['ok'])return
false;}return
true;}$x="mongo";$kd=array();$qd=array();$mc=array(array("json"));}$ec["elastic"]="Elasticsearch (beta)";if(isset($_GET["elastic"])){$fg=array("json + allow_url_fopen");define("DRIVER","elastic");if(function_exists('json_decode')&&ini_bool('allow_url_fopen')){class
Min_DB{var$extension="JSON",$server_info,$errno,$error,$_url;function
rootQuery($Wf,$zb=array(),$Qe='GET'){@ini_set('track_errors',1);$Tc=@file_get_contents("$this->_url/".ltrim($Wf,'/'),false,stream_context_create(array('http'=>array('method'=>$Qe,'content'=>$zb===null?$zb:json_encode($zb),'header'=>'Content-Type: application/json','ignore_errors'=>1,))));if(!$Tc){$this->error=$php_errormsg;return$Tc;}if(!preg_match('~^HTTP/[0-9.]+ 2~i',$http_response_header[0])){$this->error=$Tc;return
false;}$I=json_decode($Tc,true);if($I===null){$this->errno=json_last_error();if(function_exists('json_last_error_msg'))$this->error=json_last_error_msg();else{$yb=get_defined_constants(true);foreach($yb['json']as$C=>$Y){if($Y==$this->errno&&preg_match('~^JSON_ERROR_~',$C)){$this->error=$C;break;}}}}return$I;}function
query($Wf,$zb=array(),$Qe='GET'){return$this->rootQuery(($this->_db!=""?"$this->_db/":"/").ltrim($Wf,'/'),$zb,$Qe);}function
connect($N,$V,$F){preg_match('~^(https?://)?(.*)~',$N,$B);$this->_url=($B[1]?$B[1]:"http://")."$V:$F@$B[2]";$I=$this->query('');if($I)$this->server_info=$I['version']['number'];return(bool)$I;}function
select_db($j){$this->_db=$j;return
true;}function
quote($P){return$P;}}class
Min_Result{var$num_rows,$_rows;function
__construct($K){$this->num_rows=count($this->_rows);$this->_rows=$K;reset($this->_rows);}function
fetch_assoc(){$I=current($this->_rows);next($this->_rows);return$I;}function
fetch_row(){return
array_values($this->fetch_assoc());}}}class
Min_Driver
extends
Min_SQL{function
select($Q,$L,$Z,$nd,$yf=array(),$z=1,$E=0,$kg=false){global$b;$Lb=array();$G="$Q/_search";if($L!=array("*"))$Lb["fields"]=$L;if($yf){$sh=array();foreach($yf
as$mb){$mb=preg_replace('~ DESC$~','',$mb,1,$Db);$sh[]=($Db?array($mb=>"desc"):$mb);}$Lb["sort"]=$sh;}if($z){$Lb["size"]=+$z;if($E)$Lb["from"]=($E*$z);}foreach($Z
as$X){list($mb,$rf,$X)=explode(" ",$X,3);if($mb=="_id")$Lb["query"]["ids"]["values"][]=$X;elseif($mb.$X!=""){$Yh=array("term"=>array(($mb!=""?$mb:"_all")=>$X));if($rf=="=")$Lb["query"]["filtered"]["filter"]["and"][]=$Yh;else$Lb["query"]["filtered"]["query"]["bool"]["must"][]=$Yh;}}if($Lb["query"]&&!$Lb["query"]["filtered"]["query"]&&!$Lb["query"]["ids"])$Lb["query"]["filtered"]["query"]=array("match_all"=>array());$Ah=microtime(true);$ah=$this->_conn->query($G,$Lb);if($kg)echo$b->selectQuery("$G: ".print_r($Lb,true),$Ah,!$ah);if(!$ah)return
false;$I=array();foreach($ah['hits']['hits']as$_d){$J=array();if($L==array("*"))$J["_id"]=$_d["_id"];$p=$_d['_source'];if($L!=array("*")){$p=array();foreach($L
as$y)$p[$y]=$_d['fields'][$y];}foreach($p
as$y=>$X){if($Lb["fields"])$X=$X[0];$J[$y]=(is_array($X)?json_encode($X):$X);}$I[]=$J;}return
new
Min_Result($I);}function
update($T,$zg,$ug,$z=0,$M="\n"){$Uf=preg_split('~ *= *~',$ug);if(count($Uf)==2){$t=trim($Uf[1]);$G="$T/$t";return$this->_conn->query($G,$zg,'POST');}return
false;}function
insert($T,$zg){$t="";$G="$T/$t";$Kg=$this->_conn->query($G,$zg,'POST');$this->_conn->last_id=$Kg['_id'];return$Kg['created'];}function
delete($T,$ug,$z=0){$Ed=array();if(is_array($_GET["where"])&&$_GET["where"]["_id"])$Ed[]=$_GET["where"]["_id"];if(is_array($_POST['check'])){foreach($_POST['check']as$cb){$Uf=preg_split('~ *= *~',$cb);if(count($Uf)==2)$Ed[]=trim($Uf[1]);}}$this->_conn->affected_rows=0;foreach($Ed
as$t){$G="{$T}/{$t}";$Kg=$this->_conn->query($G,'{}','DELETE');if(is_array($Kg)&&$Kg['found']==true)$this->_conn->affected_rows++;}return$this->_conn->affected_rows;}}function
connect(){global$b;$g=new
Min_DB;list($N,$V,$F)=$b->credentials();if($F!=""&&$g->connect($N,$V,""))return'Database does not support password.';if($g->connect($N,$V,$F))return$g;return$g->error;}function
support($Pc){return
preg_match("~database|table|columns~",$Pc);}function
logged_user(){global$b;$Gb=$b->credentials();return$Gb[1];}function
get_databases(){global$g;$I=$g->rootQuery('_aliases');if($I){$I=array_keys($I);sort($I,SORT_STRING);}return$I;}function
collations(){return
array();}function
db_collation($l,$ob){}function
engines(){return
array();}function
count_tables($k){global$g;$I=array();$H=$g->query('_stats');if($H&&$H['indices']){$Md=$H['indices'];foreach($Md
as$Ld=>$Bh){$Kd=$Bh['total']['indexing'];$I[$Ld]=$Kd['index_total'];}}return$I;}function
tables_list(){global$g;$I=$g->query('_mapping');if($I)$I=array_fill_keys(array_keys($I[$g->_db]["mappings"]),'table');return$I;}function
table_status($C="",$Oc=false){global$g;$ah=$g->query("_search",array("size"=>0,"aggregations"=>array("count_by_type"=>array("terms"=>array("field"=>"_type")))),"POST");$I=array();if($ah){$S=$ah["aggregations"]["count_by_type"]["buckets"];foreach($S
as$Q){$I[$Q["key"]]=array("Name"=>$Q["key"],"Engine"=>"table","Rows"=>$Q["doc_count"],);if($C!=""&&$C==$Q["key"])return$I[$C];}}return$I;}function
error(){global$g;return
h($g->error);}function
information_schema(){}function
is_view($R){}function
indexes($Q,$h=null){return
array(array("type"=>"PRIMARY","columns"=>array("_id")),);}function
fields($Q){global$g;$H=$g->query("$Q/_mapping");$I=array();if($H){$ze=$H[$Q]['properties'];if(!$ze)$ze=$H[$g->_db]['mappings'][$Q]['properties'];if($ze){foreach($ze
as$C=>$o){$I[$C]=array("field"=>$C,"full_type"=>$o["type"],"type"=>$o["type"],"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),);if($o["properties"]){unset($I[$C]["privileges"]["insert"]);unset($I[$C]["privileges"]["update"]);}}}}return$I;}function
foreign_keys($Q){return
array();}function
table($u){return$u;}function
idf_escape($u){return$u;}function
convert_field($o){}function
unconvert_field($o,$I){return$I;}function
fk_support($R){}function
found_rows($R,$Z){return
null;}function
create_database($l){global$g;return$g->rootQuery(urlencode($l),null,'PUT');}function
drop_databases($k){global$g;return$g->rootQuery(urlencode(implode(',',$k)),array(),'DELETE');}function
alter_table($Q,$C,$p,$cd,$tb,$uc,$d,$La,$Sf){global$g;$qg=array();foreach($p
as$Mc){$Rc=trim($Mc[1][0]);$Sc=trim($Mc[1][1]?$Mc[1][1]:"text");$qg[$Rc]=array('type'=>$Sc);}if(!empty($qg))$qg=array('properties'=>$qg);return$g->query("_mapping/{$C}",$qg,'PUT');}function
drop_tables($S){global$g;$I=true;foreach($S
as$Q)$I=$I&&$g->query(urlencode($Q),array(),'DELETE');return$I;}function
last_id(){global$g;return$g->last_id;}$x="elastic";$tf=array("=","query");$kd=array();$qd=array();$mc=array(array("json"));$U=array();$Fh=array();foreach(array('Numbers'=>array("long"=>3,"integer"=>5,"short"=>8,"byte"=>10,"double"=>20,"float"=>66,"half_float"=>12,"scaled_float"=>21),'Date and time'=>array("date"=>10),'Strings'=>array("string"=>65535,"text"=>65535),'Binary'=>array("binary"=>255),)as$y=>$X){$U+=$X;$Fh[$y]=array_keys($X);}}$ec["clickhouse"]="ClickHouse (alpha)";if(isset($_GET["clickhouse"])){define("DRIVER","clickhouse");class
Min_DB{var$extension="JSON",$server_info,$errno,$_result,$error,$_url;var$_db='default';function
rootQuery($l,$G){@ini_set('track_errors',1);$Tc=@file_get_contents("$this->_url/?database=$l",false,stream_context_create(array('http'=>array('method'=>'POST','content'=>$this->isQuerySelectLike($G)?"$G FORMAT JSONCompact":$G,'header'=>'Content-type: application/x-www-form-urlencoded','ignore_errors'=>1,))));if($Tc===false){$this->error=$php_errormsg;return$Tc;}if(!preg_match('~^HTTP/[0-9.]+ 2~i',$http_response_header[0])){$this->error=$Tc;return
false;}$I=json_decode($Tc,true);if($I===null){$this->errno=json_last_error();if(function_exists('json_last_error_msg'))$this->error=json_last_error_msg();else{$yb=get_defined_constants(true);foreach($yb['json']as$C=>$Y){if($Y==$this->errno&&preg_match('~^JSON_ERROR_~',$C)){$this->error=$C;break;}}}}return
new
Min_Result($I);}function
isQuerySelectLike($G){return(bool)preg_match('~^(select|show)~i',$G);}function
query($G){return$this->rootQuery($this->_db,$G);}function
connect($N,$V,$F){preg_match('~^(https?://)?(.*)~',$N,$B);$this->_url=($B[1]?$B[1]:"http://")."$V:$F@$B[2]";$I=$this->query('SELECT 1');return(bool)$I;}function
select_db($j){$this->_db=$j;return
true;}function
quote($P){return"'".addcslashes($P,"\\'")."'";}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($G,$o=0){$H=$this->query($G);return$H['data'];}}class
Min_Result{var$num_rows,$_rows,$columns,$meta,$_offset=0;function
__construct($H){$this->num_rows=$H['rows'];$this->_rows=$H['data'];$this->meta=$H['meta'];$this->columns=array_column($this->meta,'name');reset($this->_rows);}function
fetch_assoc(){$J=current($this->_rows);next($this->_rows);return$J===false?false:array_combine($this->columns,$J);}function
fetch_row(){$J=current($this->_rows);next($this->_rows);return$J;}function
fetch_field(){$e=$this->_offset++;$I=new
stdClass;if($e<count($this->columns)){$I->name=$this->meta[$e]['name'];$I->orgname=$I->name;$I->type=$this->meta[$e]['type'];}return$I;}}class
Min_Driver
extends
Min_SQL{function
delete($Q,$ug,$z=0){return
queries("ALTER TABLE ".table($Q)." DELETE $ug");}function
update($Q,$O,$ug,$z=0,$M="\n"){$Si=array();foreach($O
as$y=>$X)$Si[]="$y = $X";$G=$M.implode(",$M",$Si);return
queries("ALTER TABLE ".table($Q)." UPDATE $G$ug");}}function
idf_escape($u){return"`".str_replace("`","``",$u)."`";}function
table($u){return
idf_escape($u);}function
explain($g,$G){return'';}function
found_rows($R,$Z){$K=get_vals("SELECT COUNT(*) FROM ".idf_escape($R["Name"]).($Z?" WHERE ".implode(" AND ",$Z):""));return
empty($K)?false:$K[0];}function
alter_table($Q,$C,$p,$cd,$tb,$uc,$d,$La,$Sf){foreach($p
as$o){if($o[1][2]===" NULL")$o[1][1]=" Nullable({$o[1][1]})";unset($o[1][2]);}}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Xi){return
drop_tables($Xi);}function
drop_tables($S){return
apply_queries("DROP TABLE",$S);}function
connect(){global$b;$g=new
Min_DB;$Gb=$b->credentials();if($g->connect($Gb[0],$Gb[1],$Gb[2]))return$g;return$g->error;}function
get_databases($ad){global$g;$H=get_rows('SHOW DATABASES');$I=array();foreach($H
as$J)$I[]=$J['name'];sort($I);return$I;}function
limit($G,$Z,$z,$D=0,$M=" "){return" $G$Z".($z!==null?$M."LIMIT $z".($D?", $D":""):"");}function
limit1($Q,$G,$Z,$M="\n"){return
limit($G,$Z,1,0,$M);}function
db_collation($l,$ob){}function
engines(){return
array('MergeTree');}function
logged_user(){global$b;$Gb=$b->credentials();return$Gb[1];}function
tables_list(){$H=get_rows('SHOW TABLES');$I=array();foreach($H
as$J)$I[$J['name']]='table';ksort($I);return$I;}function
count_tables($k){return
array();}function
table_status($C="",$Oc=false){global$g;$I=array();$S=get_rows("SELECT name, engine FROM system.tables WHERE database = ".q($g->_db));foreach($S
as$Q){$I[$Q['name']]=array('Name'=>$Q['name'],'Engine'=>$Q['engine'],);if($C===$Q['name'])return$I[$Q['name']];}return$I;}function
is_view($R){return
false;}function
fk_support($R){return
false;}function
convert_field($o){}function
unconvert_field($o,$I){if(in_array($o['type'],["Int8","Int16","Int32","Int64","UInt8","UInt16","UInt32","UInt64","Float32","Float64"]))return"to$o[type]($I)";return$I;}function
fields($Q){$I=array();$H=get_rows("SELECT name, type, default_expression FROM system.columns WHERE ".idf_escape('table')." = ".q($Q));foreach($H
as$J){$T=trim($J['type']);$df=strpos($T,'Nullable(')===0;$I[trim($J['name'])]=array("field"=>trim($J['name']),"full_type"=>$T,"type"=>$T,"default"=>trim($J['default_expression']),"null"=>$df,"auto_increment"=>'0',"privileges"=>array("insert"=>1,"select"=>1,"update"=>0),);}return$I;}function
indexes($Q,$h=null){return
array();}function
foreign_keys($Q){return
array();}function
collations(){return
array();}function
information_schema($l){return
false;}function
error(){global$g;return
h($g->error);}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Yg){return
true;}function
auto_increment(){return'';}function
last_id(){return
0;}function
support($Pc){return
preg_match("~^(columns|sql|status|table)$~",$Pc);}$x="clickhouse";$U=array();$Fh=array();foreach(array('Numbers'=>array("Int8"=>3,"Int16"=>5,"Int32"=>10,"Int64"=>19,"UInt8"=>3,"UInt16"=>5,"UInt32"=>10,"UInt64"=>20,"Float32"=>7,"Float64"=>16,'Decimal'=>38,'Decimal32'=>9,'Decimal64'=>18,'Decimal128'=>38),'Date and time'=>array("Date"=>13,"DateTime"=>20),'Strings'=>array("String"=>0),'Binary'=>array("FixedString"=>0),)as$y=>$X){$U+=$X;$Fh[$y]=array_keys($X);}$Gi=array();$tf=array("=","<",">","<=",">=","!=","~","!~","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL");$kd=array();$qd=array("avg","count","count distinct","max","min","sum");$mc=array();}$ec=array("server"=>"MySQL")+$ec;if(!defined("DRIVER")){$fg=array("MySQLi","MySQL","PDO_MySQL");define("DRIVER","server");if(extension_loaded("mysqli")){class
Min_DB
extends
MySQLi{var$extension="MySQLi";function
__construct(){parent::init();}function
connect($N="",$V="",$F="",$j=null,$bg=null,$rh=null){global$b;mysqli_report(MYSQLI_REPORT_OFF);list($Ad,$bg)=explode(":",$N,2);$_h=$b->connectSsl();if($_h)$this->ssl_set($_h['key'],$_h['cert'],$_h['ca'],'','');$I=@$this->real_connect(($N!=""?$Ad:ini_get("mysqli.default_host")),($N.$V!=""?$V:ini_get("mysqli.default_user")),($N.$V.$F!=""?$F:ini_get("mysqli.default_pw")),$j,(is_numeric($bg)?$bg:ini_get("mysqli.default_port")),(!is_numeric($bg)?$bg:$rh),($_h?64:0));$this->options(MYSQLI_OPT_LOCAL_INFILE,false);return$I;}function
set_charset($bb){if(parent::set_charset($bb))return
true;parent::set_charset('utf8');return$this->query("SET NAMES $bb");}function
result($G,$o=0){$H=$this->query($G);if(!$H)return
false;$J=$H->fetch_array();return$J[$o];}function
quote($P){return"'".$this->escape_string($P)."'";}}}elseif(extension_loaded("mysql")&&!((ini_bool("sql.safe_mode")||ini_bool("mysql.allow_local_infile"))&&extension_loaded("pdo_mysql"))){class
Min_DB{var$extension="MySQL",$server_info,$affected_rows,$errno,$error,$_link,$_result;function
connect($N,$V,$F){if(ini_bool("mysql.allow_local_infile")){$this->error=sprintf('Disable %s or enable %s or %s extensions.',"'mysql.allow_local_infile'","MySQLi","PDO_MySQL");return
false;}$this->_link=@mysql_connect(($N!=""?$N:ini_get("mysql.default_host")),("$N$V"!=""?$V:ini_get("mysql.default_user")),("$N$V$F"!=""?$F:ini_get("mysql.default_password")),true,131072);if($this->_link)$this->server_info=mysql_get_server_info($this->_link);else$this->error=mysql_error();return(bool)$this->_link;}function
set_charset($bb){if(function_exists('mysql_set_charset')){if(mysql_set_charset($bb,$this->_link))return
true;mysql_set_charset('utf8',$this->_link);}return$this->query("SET NAMES $bb");}function
quote($P){return"'".mysql_real_escape_string($P,$this->_link)."'";}function
select_db($j){return
mysql_select_db($j,$this->_link);}function
query($G,$Ai=false){$H=@($Ai?mysql_unbuffered_query($G,$this->_link):mysql_query($G,$this->_link));$this->error="";if(!$H){$this->errno=mysql_errno($this->_link);$this->error=mysql_error($this->_link);return
false;}if($H===true){$this->affected_rows=mysql_affected_rows($this->_link);$this->info=mysql_info($this->_link);return
true;}return
new
Min_Result($H);}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($G,$o=0){$H=$this->query($G);if(!$H||!$H->num_rows)return
false;return
mysql_result($H->_result,0,$o);}}class
Min_Result{var$num_rows,$_result,$_offset=0;function
__construct($H){$this->_result=$H;$this->num_rows=mysql_num_rows($H);}function
fetch_assoc(){return
mysql_fetch_assoc($this->_result);}function
fetch_row(){return
mysql_fetch_row($this->_result);}function
fetch_field(){$I=mysql_fetch_field($this->_result,$this->_offset++);$I->orgtable=$I->table;$I->orgname=$I->name;$I->charsetnr=($I->blob?63:0);return$I;}function
__destruct(){mysql_free_result($this->_result);}}}elseif(extension_loaded("pdo_mysql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_MySQL";function
connect($N,$V,$F){global$b;$wf=array(PDO::MYSQL_ATTR_LOCAL_INFILE=>false);$_h=$b->connectSsl();if($_h)$wf+=array(PDO::MYSQL_ATTR_SSL_KEY=>$_h['key'],PDO::MYSQL_ATTR_SSL_CERT=>$_h['cert'],PDO::MYSQL_ATTR_SSL_CA=>$_h['ca'],);$this->dsn("mysql:charset=utf8;host=".str_replace(":",";unix_socket=",preg_replace('~:(\d)~',';port=\1',$N)),$V,$F,$wf);return
true;}function
set_charset($bb){$this->query("SET NAMES $bb");}function
select_db($j){return$this->query("USE ".idf_escape($j));}function
query($G,$Ai=false){$this->setAttribute(1000,!$Ai);return
parent::query($G,$Ai);}}}class
Min_Driver
extends
Min_SQL{function
insert($Q,$O){return($O?parent::insert($Q,$O):queries("INSERT INTO ".table($Q)." ()\nVALUES ()"));}function
insertUpdate($Q,$K,$ig){$f=array_keys(reset($K));$gg="INSERT INTO ".table($Q)." (".implode(", ",$f).") VALUES\n";$Si=array();foreach($f
as$y)$Si[$y]="$y = VALUES($y)";$Ih="\nON DUPLICATE KEY UPDATE ".implode(", ",$Si);$Si=array();$te=0;foreach($K
as$O){$Y="(".implode(", ",$O).")";if($Si&&(strlen($gg)+$te+strlen($Y)+strlen($Ih)>1e6)){if(!queries($gg.implode(",\n",$Si).$Ih))return
false;$Si=array();$te=0;}$Si[]=$Y;$te+=strlen($Y)+2;}return
queries($gg.implode(",\n",$Si).$Ih);}function
slowQuery($G,$di){if(min_version('5.7.8','10.1.2')){if(preg_match('~MariaDB~',$this->_conn->server_info))return"SET STATEMENT max_statement_time=$di FOR $G";elseif(preg_match('~^(SELECT\b)(.+)~is',$G,$B))return"$B[1] /*+ MAX_EXECUTION_TIME(".($di*1000).") */ $B[2]";}}function
convertSearch($u,$X,$o){return(preg_match('~char|text|enum|set~',$o["type"])&&!preg_match("~^utf8~",$o["collation"])&&preg_match('~[\x80-\xFF]~',$X['val'])?"CONVERT($u USING ".charset($this->_conn).")":$u);}function
warnings(){$H=$this->_conn->query("SHOW WARNINGS");if($H&&$H->num_rows){ob_start();select($H);return
ob_get_clean();}}function
tableHelp($C){$_e=preg_match('~MariaDB~',$this->_conn->server_info);if(information_schema(DB))return
strtolower(($_e?"information-schema-$C-table/":str_replace("_","-",$C)."-table.html"));if(DB=="mysql")return($_e?"mysql$C-table/":"system-database.html");}}function
idf_escape($u){return"`".str_replace("`","``",$u)."`";}function
table($u){return
idf_escape($u);}function
connect(){global$b,$U,$Fh;$g=new
Min_DB;$Gb=$b->credentials();if($g->connect($Gb[0],$Gb[1],$Gb[2])){$g->set_charset(charset($g));$g->query("SET sql_quote_show_create = 1, autocommit = 1");if(min_version('5.7.8',10.2,$g)){$Fh['Strings'][]="json";$U["json"]=4294967295;}return$g;}$I=$g->error;if(function_exists('iconv')&&!is_utf8($I)&&strlen($Wg=iconv("windows-1250","utf-8",$I))>strlen($I))$I=$Wg;return$I;}function
get_databases($ad){$I=get_session("dbs");if($I===null){$G=(min_version(5)?"SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME":"SHOW DATABASES");$I=($ad?slow_query($G):get_vals($G));restart_session();set_session("dbs",$I);stop_session();}return$I;}function
limit($G,$Z,$z,$D=0,$M=" "){return" $G$Z".($z!==null?$M."LIMIT $z".($D?" OFFSET $D":""):"");}function
limit1($Q,$G,$Z,$M="\n"){return
limit($G,$Z,1,0,$M);}function
db_collation($l,$ob){global$g;$I=null;$i=$g->result("SHOW CREATE DATABASE ".idf_escape($l),1);if(preg_match('~ COLLATE ([^ ]+)~',$i,$B))$I=$B[1];elseif(preg_match('~ CHARACTER SET ([^ ]+)~',$i,$B))$I=$ob[$B[1]][-1];return$I;}function
engines(){$I=array();foreach(get_rows("SHOW ENGINES")as$J){if(preg_match("~YES|DEFAULT~",$J["Support"]))$I[]=$J["Engine"];}return$I;}function
logged_user(){global$g;return$g->result("SELECT USER()");}function
tables_list(){return
get_key_vals(min_version(5)?"SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME":"SHOW TABLES");}function
count_tables($k){$I=array();foreach($k
as$l)$I[$l]=count(get_vals("SHOW TABLES IN ".idf_escape($l)));return$I;}function
table_status($C="",$Oc=false){$I=array();foreach(get_rows($Oc&&min_version(5)?"SELECT TABLE_NAME AS Name, ENGINE AS Engine, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ".($C!=""?"AND TABLE_NAME = ".q($C):"ORDER BY Name"):"SHOW TABLE STATUS".($C!=""?" LIKE ".q(addcslashes($C,"%_\\")):""))as$J){if($J["Engine"]=="InnoDB")$J["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\1',$J["Comment"]);if(!isset($J["Engine"]))$J["Comment"]="";if($C!="")return$J;$I[$J["Name"]]=$J;}return$I;}function
is_view($R){return$R["Engine"]===null;}function
fk_support($R){return
preg_match('~InnoDB|IBMDB2I~i',$R["Engine"])||(preg_match('~NDB~i',$R["Engine"])&&min_version(5.6));}function
fields($Q){$I=array();foreach(get_rows("SHOW FULL COLUMNS FROM ".table($Q))as$J){preg_match('~^([^( ]+)(?:\((.+)\))?( unsigned)?( zerofill)?$~',$J["Type"],$B);$I[$J["Field"]]=array("field"=>$J["Field"],"full_type"=>$J["Type"],"type"=>$B[1],"length"=>$B[2],"unsigned"=>ltrim($B[3].$B[4]),"default"=>($J["Default"]!=""||preg_match("~char|set~",$B[1])?$J["Default"]:null),"null"=>($J["Null"]=="YES"),"auto_increment"=>($J["Extra"]=="auto_increment"),"on_update"=>(preg_match('~^on update (.+)~i',$J["Extra"],$B)?$B[1]:""),"collation"=>$J["Collation"],"privileges"=>array_flip(preg_split('~, *~',$J["Privileges"])),"comment"=>$J["Comment"],"primary"=>($J["Key"]=="PRI"),);}return$I;}function
indexes($Q,$h=null){$I=array();foreach(get_rows("SHOW INDEX FROM ".table($Q),$h)as$J){$C=$J["Key_name"];$I[$C]["type"]=($C=="PRIMARY"?"PRIMARY":($J["Index_type"]=="FULLTEXT"?"FULLTEXT":($J["Non_unique"]?($J["Index_type"]=="SPATIAL"?"SPATIAL":"INDEX"):"UNIQUE")));$I[$C]["columns"][]=$J["Column_name"];$I[$C]["lengths"][]=($J["Index_type"]=="SPATIAL"?null:$J["Sub_part"]);$I[$C]["descs"][]=null;}return$I;}function
foreign_keys($Q){global$g,$of;static$Yf='(?:`(?:[^`]|``)+`)|(?:"(?:[^"]|"")+")';$I=array();$Eb=$g->result("SHOW CREATE TABLE ".table($Q),1);if($Eb){preg_match_all("~CONSTRAINT ($Yf) FOREIGN KEY ?\\(((?:$Yf,? ?)+)\\) REFERENCES ($Yf)(?:\\.($Yf))? \\(((?:$Yf,? ?)+)\\)(?: ON DELETE ($of))?(?: ON UPDATE ($of))?~",$Eb,$Ce,PREG_SET_ORDER);foreach($Ce
as$B){preg_match_all("~$Yf~",$B[2],$th);preg_match_all("~$Yf~",$B[5],$Vh);$I[idf_unescape($B[1])]=array("db"=>idf_unescape($B[4]!=""?$B[3]:$B[4]),"table"=>idf_unescape($B[4]!=""?$B[4]:$B[3]),"source"=>array_map('idf_unescape',$th[0]),"target"=>array_map('idf_unescape',$Vh[0]),"on_delete"=>($B[6]?$B[6]:"RESTRICT"),"on_update"=>($B[7]?$B[7]:"RESTRICT"),);}}return$I;}function
viewm($C){global$g;return
array("select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\s+AS\s+~isU','',$g->result("SHOW CREATE VIEW ".table($C),1)));}function
collations(){$I=array();foreach(get_rows("SHOW COLLATION")as$J){if($J["Default"])$I[$J["Charset"]][-1]=$J["Collation"];else$I[$J["Charset"]][]=$J["Collation"];}ksort($I);foreach($I
as$y=>$X)asort($I[$y]);return$I;}function
information_schema($l){return(min_version(5)&&$l=="information_schema")||(min_version(5.5)&&$l=="performance_schema");}function
error(){global$g;return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",$g->error));}function
create_database($l,$d){return
queries("CREATE DATABASE ".idf_escape($l).($d?" COLLATE ".q($d):""));}function
drop_databases($k){$I=apply_queries("DROP DATABASE",$k,'idf_escape');restart_session();set_session("dbs",null);return$I;}function
rename_database($C,$d){$I=false;if(create_database($C,$d)){$Ig=array();foreach(tables_list()as$Q=>$T)$Ig[]=table($Q)." TO ".idf_escape($C).".".table($Q);$I=(!$Ig||queries("RENAME TABLE ".implode(", ",$Ig)));if($I)queries("DROP DATABASE ".idf_escape(DB));restart_session();set_session("dbs",null);}return$I;}function
auto_increment(){$Ma=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$v){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$v["columns"],true)){$Ma="";break;}if($v["type"]=="PRIMARY")$Ma=" UNIQUE";}}return" AUTO_INCREMENT$Ma";}function
alter_table($Q,$C,$p,$cd,$tb,$uc,$d,$La,$Sf){$c=array();foreach($p
as$o)$c[]=($o[1]?($Q!=""?($o[0]!=""?"CHANGE ".idf_escape($o[0]):"ADD"):" ")." ".implode($o[1]).($Q!=""?$o[2]:""):"DROP ".idf_escape($o[0]));$c=array_merge($c,$cd);$Ch=($tb!==null?" COMMENT=".q($tb):"").($uc?" ENGINE=".q($uc):"").($d?" COLLATE ".q($d):"").($La!=""?" AUTO_INCREMENT=$La":"");if($Q=="")return
queries("CREATE TABLE ".table($C)." (\n".implode(",\n",$c)."\n)$Ch$Sf");if($Q!=$C)$c[]="RENAME TO ".table($C);if($Ch)$c[]=ltrim($Ch);return($c||$Sf?queries("ALTER TABLE ".table($Q)."\n".implode(",\n",$c).$Sf):true);}function
alter_indexes($Q,$c){foreach($c
as$y=>$X)$c[$y]=($X[2]=="DROP"?"\nDROP INDEX ".idf_escape($X[1]):"\nADD $X[0] ".($X[0]=="PRIMARY"?"KEY ":"").($X[1]!=""?idf_escape($X[1])." ":"")."(".implode(", ",$X[2]).")");return
queries("ALTER TABLE ".table($Q).implode(",",$c));}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Xi){return
queries("DROP VIEW ".implode(", ",array_map('table',$Xi)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$Xi,$Vh){$Ig=array();foreach(array_merge($S,$Xi)as$Q)$Ig[]=table($Q)." TO ".idf_escape($Vh).".".table($Q);return
queries("RENAME TABLE ".implode(", ",$Ig));}function
copy_tables($S,$Xi,$Vh){queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");foreach($S
as$Q){$C=($Vh==DB?table("copy_$Q"):idf_escape($Vh).".".table($Q));if(!queries("CREATE TABLE $C LIKE ".table($Q))||!queries("INSERT INTO $C SELECT * FROM ".table($Q)))return
false;foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")))as$J){$vi=$J["Trigger"];if(!queries("CREATE TRIGGER ".($Vh==DB?idf_escape("copy_$vi"):idf_escape($Vh).".".idf_escape($vi))." $J[Timing] $J[Event] ON $C FOR EACH ROW\n$J[Statement];"))return
false;}}foreach($Xi
as$Q){$C=($Vh==DB?table("copy_$Q"):idf_escape($Vh).".".table($Q));$Wi=viewm($Q);if(!queries("CREATE VIEW $C AS $Wi[select]"))return
false;}return
true;}function
trigger($C){if($C=="")return
array();$K=get_rows("SHOW TRIGGERS WHERE `Trigger` = ".q($C));return
reset($K);}function
triggers($Q){$I=array();foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")))as$J)$I[$J["Trigger"]]=array($J["Timing"],$J["Event"]);return$I;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
routine($C,$T){global$g,$wc,$Rd,$U;$Ca=array("bool","boolean","integer","double precision","real","dec","numeric","fixed","national char","national varchar");$uh="(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";$_i="((".implode("|",array_merge(array_keys($U),$Ca)).")\\b(?:\\s*\\(((?:[^'\")]|$wc)++)\\))?\\s*(zerofill\\s*)?(unsigned(?:\\s+zerofill)?)?)(?:\\s*(?:CHARSET|CHARACTER\\s+SET)\\s*['\"]?([^'\"\\s,]+)['\"]?)?";$Yf="$uh*(".($T=="FUNCTION"?"":$Rd).")?\\s*(?:`((?:[^`]|``)*)`\\s*|\\b(\\S+)\\s+)$_i";$i=$g->result("SHOW CREATE $T ".idf_escape($C),2);preg_match("~\\(((?:$Yf\\s*,?)*)\\)\\s*".($T=="FUNCTION"?"RETURNS\\s+$_i\\s+":"")."(.*)~is",$i,$B);$p=array();preg_match_all("~$Yf\\s*,?~is",$B[1],$Ce,PREG_SET_ORDER);foreach($Ce
as$Lf){$C=str_replace("``","`",$Lf[2]).$Lf[3];$p[]=array("field"=>$C,"type"=>strtolower($Lf[5]),"length"=>preg_replace_callback("~$wc~s",'normalize_enum',$Lf[6]),"unsigned"=>strtolower(preg_replace('~\s+~',' ',trim("$Lf[8] $Lf[7]"))),"null"=>1,"full_type"=>$Lf[4],"inout"=>strtoupper($Lf[1]),"collation"=>strtolower($Lf[9]),);}if($T!="FUNCTION")return
array("fields"=>$p,"definition"=>$B[11]);return
array("fields"=>$p,"returns"=>array("type"=>$B[12],"length"=>$B[13],"unsigned"=>$B[15],"collation"=>$B[16]),"definition"=>$B[17],"language"=>"SQL",);}function
routines(){return
get_rows("SELECT ROUTINE_NAME AS SPECIFIC_NAME, ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ".q(DB));}function
routine_languages(){return
array();}function
routine_id($C,$J){return
idf_escape($C);}function
last_id(){global$g;return$g->result("SELECT LAST_INSERT_ID()");}function
explain($g,$G){return$g->query("EXPLAIN ".(min_version(5.1)?"PARTITIONS ":"").$G);}function
found_rows($R,$Z){return($Z||$R["Engine"]!="InnoDB"?null:$R["Rows"]);}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Yg){return
true;}function
create_sql($Q,$La,$Gh){global$g;$I=$g->result("SHOW CREATE TABLE ".table($Q),1);if(!$La)$I=preg_replace('~ AUTO_INCREMENT=\d+~','',$I);return$I;}function
truncate_sql($Q){return"TRUNCATE ".table($Q);}function
use_sql($j){return"USE ".idf_escape($j);}function
trigger_sql($Q){$I="";foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")),null,"-- ")as$J)$I.="\nCREATE TRIGGER ".idf_escape($J["Trigger"])." $J[Timing] $J[Event] ON ".table($J["Table"])." FOR EACH ROW\n$J[Statement];;\n";return$I;}function
show_variables(){return
get_key_vals("SHOW VARIABLES");}function
process_list(){return
get_rows("SHOW FULL PROCESSLIST");}function
show_status(){return
get_key_vals("SHOW STATUS");}function
convert_field($o){if(preg_match("~binary~",$o["type"]))return"HEX(".idf_escape($o["field"]).")";if($o["type"]=="bit")return"BIN(".idf_escape($o["field"])." + 0)";if(preg_match("~geometry|point|linestring|polygon~",$o["type"]))return(min_version(8)?"ST_":"")."AsWKT(".idf_escape($o["field"]).")";}function
unconvert_field($o,$I){if(preg_match("~binary~",$o["type"]))$I="UNHEX($I)";if($o["type"]=="bit")$I="CONV($I, 2, 10) + 0";if(preg_match("~geometry|point|linestring|polygon~",$o["type"]))$I=(min_version(8)?"ST_":"")."GeomFromText($I)";return$I;}function
support($Pc){return!preg_match("~scheme|sequence|type|view_trigger|materializedview".(min_version(8)?"":"|descidx".(min_version(5.1)?"":"|event|partitioning".(min_version(5)?"":"|routine|trigger|view")))."~",$Pc);}function
kill_process($X){return
queries("KILL ".number($X));}function
connection_id(){return"SELECT CONNECTION_ID()";}function
max_connections(){global$g;return$g->result("SELECT @@max_connections");}$x="sql";$U=array();$Fh=array();foreach(array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21),'Date and time'=>array("date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4),'Strings'=>array("char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295),'Lists'=>array("enum"=>65535,"set"=>64),'Binary'=>array("bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295),'Geometry'=>array("geometry"=>0,"point"=>0,"linestring"=>0,"polygon"=>0,"multipoint"=>0,"multilinestring"=>0,"multipolygon"=>0,"geometrycollection"=>0),)as$y=>$X){$U+=$X;$Fh[$y]=array_keys($X);}$Gi=array("unsigned","zerofill","unsigned zerofill");$tf=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","REGEXP","IN","FIND_IN_SET","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL");$kd=array("char_length","date","from_unixtime","lower","round","floor","ceil","sec_to_time","time_to_sec","upper");$qd=array("avg","count","count distinct","group_concat","max","min","sum");$mc=array(array("char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1","date|time"=>"now",),array(number_type()=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",));}define("SERVER",$_GET[DRIVER]);define("DB",$_GET["db"]);define("ME",preg_replace('~^[^?]*/([^?]*).*~','\1',$_SERVER["REQUEST_URI"]).'?'.(sid()?SID.'&':'').(SERVER!==null?DRIVER."=".urlencode(SERVER).'&':'').(isset($_GET["username"])?"username=".urlencode($_GET["username"]).'&':'').(DB!=""?'db='.urlencode(DB).'&'.(isset($_GET["ns"])?"ns=".urlencode($_GET["ns"])."&":""):''));$ia="4.7.0";class
Adminer{var$operators;function
name(){return"<a href='https://www.adminer.org/'".target_blank()." id='h1'>Adminer</a>";}function
credentials(){return
array(SERVER,$_GET["username"],get_password());}function
connectSsl(){}function
permanentLogin($i=false){return
password_file($i);}function
bruteForceKey(){return$_SERVER["REMOTE_ADDR"];}function
serverName($N){return
h($N);}function
database(){return
DB;}function
databases($ad=true){return
get_databases($ad);}function
schemas(){return
schemas();}function
queryTimeout(){return
2;}function
headers(){}function
csp(){return
csp();}function
head(){return
true;}function
css(){$I=array();$Uc="adminer.css";if(file_exists($Uc))$I[]=$Uc;return$I;}function
loginForm(){global$ec;echo"<table cellspacing='0' class='layout'>\n",$this->loginFormField('driver','<tr><th>'.'System'.'<td>',html_select("auth[driver]",$ec,DRIVER)."\n"),$this->loginFormField('server','<tr><th>'.'Server'.'<td>','<input name="auth[server]" value="'.h(SERVER).'" title="hostname[:port]" placeholder="localhost" autocapitalize="off">'."\n"),$this->loginFormField('username','<tr><th>'.'Username'.'<td>','<input name="auth[username]" id="username" value="'.h($_GET["username"]).'" autocapitalize="off">'.script("focus(qs('#username'));")),$this->loginFormField('password','<tr><th>'.'Password'.'<td>','<input type="password" name="auth[password]">'."\n"),$this->loginFormField('db','<tr><th>'.'Database'.'<td>','<input name="auth[db]" value="'.h($_GET["db"]).'" autocapitalize="off">'."\n"),"</table>\n","<p><input type='submit' value='".'Login'."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],'Permanent login')."\n";}function
loginFormField($C,$xd,$Y){return$xd.$Y;}function
login($xe,$F){if($F=="")return
sprintf('Adminer does not support accessing a database without a password, <a href="https://www.adminer.org/en/password/"%s>more information</a>.',target_blank());return
true;}function
tableName($Mh){return
h($Mh["Name"]);}function
fieldName($o,$yf=0){return'<span title="'.h($o["full_type"]).'">'.h($o["field"]).'</span>';}function
selectLinks($Mh,$O=""){global$x,$m;echo'<p class="links">';$we=array("select"=>'Select data');if(support("table")||support("indexes"))$we["table"]='Show structure';if(support("table")){if(is_view($Mh))$we["view"]='Alter view';else$we["create"]='Alter table';}if($O!==null)$we["edit"]='New item';$C=$Mh["Name"];foreach($we
as$y=>$X)echo" <a href='".h(ME)."$y=".urlencode($C).($y=="edit"?$O:"")."'".bold(isset($_GET[$y])).">$X</a>";echo
doc_link(array($x=>$m->tableHelp($C)),"?"),"\n";}function
foreignKeys($Q){return
foreign_keys($Q);}function
backwardKeys($Q,$Lh){return
array();}function
backwardKeysPrint($Oa,$J){}function
selectQuery($G,$Ah,$Nc=false){global$x,$m;$I="</p>\n";if(!$Nc&&($aj=$m->warnings())){$t="warnings";$I=", <a href='#$t'>".'Warnings'."</a>".script("qsl('a').onclick = partial(toggle, '$t');","")."$I<div id='$t' class='hidden'>\n$aj</div>\n";}return"<p><code class='jush-$x'>".h(str_replace("\n"," ",$G))."</code> <span class='time'>(".format_time($Ah).")</span>".(support("sql")?" <a href='".h(ME)."sql=".urlencode($G)."'>".'Edit'."</a>":"").$I;}function
sqlCommandQuery($G){return
shorten_utf8(trim($G),1000);}function
rowDescription($Q){return"";}function
rowDescriptions($K,$dd){return$K;}function
selectLink($X,$o){}function
selectVal($X,$_,$o,$Ff){$I=($X===null?"<i>NULL</i>":(preg_match("~char|binary|boolean~",$o["type"])&&!preg_match("~var~",$o["type"])?"<code>$X</code>":$X));if(preg_match('~blob|bytea|raw|file~',$o["type"])&&!is_utf8($X))$I="<i>".lang(array('%d byte','%d bytes'),strlen($Ff))."</i>";if(preg_match('~json~',$o["type"]))$I="<code class='jush-js'>$I</code>";return($_?"<a href='".h($_)."'".(is_url($_)?target_blank():"").">$I</a>":$I);}function
editVal($X,$o){return$X;}function
tableStructurePrint($p){echo"<div class='scrollable'>\n","<table cellspacing='0' class='nowrap'>\n","<thead><tr><th>".'Column'."<td>".'Type'.(support("comment")?"<td>".'Comment':"")."</thead>\n";foreach($p
as$o){echo"<tr".odd()."><th>".h($o["field"]),"<td><span title='".h($o["collation"])."'>".h($o["full_type"])."</span>",($o["null"]?" <i>NULL</i>":""),($o["auto_increment"]?" <i>".'Auto Increment'."</i>":""),(isset($o["default"])?" <span title='".'Default value'."'>[<b>".h($o["default"])."</b>]</span>":""),(support("comment")?"<td>".h($o["comment"]):""),"\n";}echo"</table>\n","</div>\n";}function
tableIndexesPrint($w){echo"<table cellspacing='0'>\n";foreach($w
as$C=>$v){ksort($v["columns"]);$kg=array();foreach($v["columns"]as$y=>$X)$kg[]="<i>".h($X)."</i>".($v["lengths"][$y]?"(".$v["lengths"][$y].")":"").($v["descs"][$y]?" DESC":"");echo"<tr title='".h($C)."'><th>$v[type]<td>".implode(", ",$kg)."\n";}echo"</table>\n";}function
selectColumnsPrint($L,$f){global$kd,$qd;print_fieldset("select",'Select',$L);$s=0;$L[""]=array();foreach($L
as$y=>$X){$X=$_GET["columns"][$y];$e=select_input(" name='columns[$s][col]'",$f,$X["col"],($y!==""?"selectFieldChange":"selectAddRow"));echo"<div>".($kd||$qd?"<select name='columns[$s][fun]'>".optionlist(array(-1=>"")+array_filter(array('Functions'=>$kd,'Aggregation'=>$qd)),$X["fun"])."</select>".on_help("getTarget(event).value && getTarget(event).value.replace(/ |\$/, '(') + ')'",1).script("qsl('select').onchange = function () { helpClose();".($y!==""?"":" qsl('select, input', this.parentNode).onchange();")." };","")."($e)":$e)."</div>\n";$s++;}echo"</div></fieldset>\n";}function
selectSearchPrint($Z,$f,$w){print_fieldset("search",'Search',$Z);foreach($w
as$s=>$v){if($v["type"]=="FULLTEXT"){echo"<div>(<i>".implode("</i>, <i>",array_map('h',$v["columns"]))."</i>) AGAINST"," <input type='search' name='fulltext[$s]' value='".h($_GET["fulltext"][$s])."'>",script("qsl('input').oninput = selectFieldChange;",""),checkbox("boolean[$s]",1,isset($_GET["boolean"][$s]),"BOOL"),"</div>\n";}}$ab="this.parentNode.firstChild.onchange();";foreach(array_merge((array)$_GET["where"],array(array()))as$s=>$X){if(!$X||("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators))){echo"<div>".select_input(" name='where[$s][col]'",$f,$X["col"],($X?"selectFieldChange":"selectAddRow"),"(".'anywhere'.")"),html_select("where[$s][op]",$this->operators,$X["op"],$ab),"<input type='search' name='where[$s][val]' value='".h($X["val"])."'>",script("mixin(qsl('input'), {oninput: function () { $ab }, onkeydown: selectSearchKeydown, onsearch: selectSearchSearch});",""),"</div>\n";}}echo"</div></fieldset>\n";}function
selectOrderPrint($yf,$f,$w){print_fieldset("sort",'Sort',$yf);$s=0;foreach((array)$_GET["order"]as$y=>$X){if($X!=""){echo"<div>".select_input(" name='order[$s]'",$f,$X,"selectFieldChange"),checkbox("desc[$s]",1,isset($_GET["desc"][$y]),'descending')."</div>\n";$s++;}}echo"<div>".select_input(" name='order[$s]'",$f,"","selectAddRow"),checkbox("desc[$s]",1,false,'descending')."</div>\n","</div></fieldset>\n";}function
selectLimitPrint($z){echo"<fieldset><legend>".'Limit'."</legend><div>";echo"<input type='number' name='limit' class='size' value='".h($z)."'>",script("qsl('input').oninput = selectFieldChange;",""),"</div></fieldset>\n";}function
selectLengthPrint($bi){if($bi!==null){echo"<fieldset><legend>".'Text length'."</legend><div>","<input type='number' name='text_length' class='size' value='".h($bi)."'>","</div></fieldset>\n";}}function
selectActionPrint($w){echo"<fieldset><legend>".'Action'."</legend><div>","<input type='submit' value='".'Select'."'>"," <span id='noindex' title='".'Full table scan'."'></span>","<script".nonce().">\n","var indexColumns = ";$f=array();foreach($w
as$v){$Kb=reset($v["columns"]);if($v["type"]!="FULLTEXT"&&$Kb)$f[$Kb]=1;}$f[""]=1;foreach($f
as$y=>$X)json_row($y);echo";\n","selectFieldChange.call(qs('#form')['select']);\n","</script>\n","</div></fieldset>\n";}function
selectCommandPrint(){return!information_schema(DB);}function
selectImportPrint(){return!information_schema(DB);}function
selectEmailPrint($rc,$f){}function
selectColumnsProcess($f,$w){global$kd,$qd;$L=array();$nd=array();foreach((array)$_GET["columns"]as$y=>$X){if($X["fun"]=="count"||($X["col"]!=""&&(!$X["fun"]||in_array($X["fun"],$kd)||in_array($X["fun"],$qd)))){$L[$y]=apply_sql_function($X["fun"],($X["col"]!=""?idf_escape($X["col"]):"*"));if(!in_array($X["fun"],$qd))$nd[]=$L[$y];}}return
array($L,$nd);}function
selectSearchProcess($p,$w){global$g,$m;$I=array();foreach($w
as$s=>$v){if($v["type"]=="FULLTEXT"&&$_GET["fulltext"][$s]!="")$I[]="MATCH (".implode(", ",array_map('idf_escape',$v["columns"])).") AGAINST (".q($_GET["fulltext"][$s]).(isset($_GET["boolean"][$s])?" IN BOOLEAN MODE":"").")";}foreach((array)$_GET["where"]as$y=>$X){if("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators)){$gg="";$vb=" $X[op]";if(preg_match('~IN$~',$X["op"])){$Hd=process_length($X["val"]);$vb.=" ".($Hd!=""?$Hd:"(NULL)");}elseif($X["op"]=="SQL")$vb=" $X[val]";elseif($X["op"]=="LIKE %%")$vb=" LIKE ".$this->processInput($p[$X["col"]],"%$X[val]%");elseif($X["op"]=="ILIKE %%")$vb=" ILIKE ".$this->processInput($p[$X["col"]],"%$X[val]%");elseif($X["op"]=="FIND_IN_SET"){$gg="$X[op](".q($X["val"]).", ";$vb=")";}elseif(!preg_match('~NULL$~',$X["op"]))$vb.=" ".$this->processInput($p[$X["col"]],$X["val"]);if($X["col"]!="")$I[]=$gg.$m->convertSearch(idf_escape($X["col"]),$X,$p[$X["col"]]).$vb;else{$qb=array();foreach($p
as$C=>$o){if((preg_match('~^[-\d.'.(preg_match('~IN$~',$X["op"])?',':'').']+$~',$X["val"])||!preg_match('~'.number_type().'|bit~',$o["type"]))&&(!preg_match("~[\x80-\xFF]~",$X["val"])||preg_match('~char|text|enum|set~',$o["type"])))$qb[]=$gg.$m->convertSearch(idf_escape($C),$X,$o).$vb;}$I[]=($qb?"(".implode(" OR ",$qb).")":"1 = 0");}}}return$I;}function
selectOrderProcess($p,$w){$I=array();foreach((array)$_GET["order"]as$y=>$X){if($X!="")$I[]=(preg_match('~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~',$X)?$X:idf_escape($X)).(isset($_GET["desc"][$y])?" DESC":"");}return$I;}function
selectLimitProcess(){return(isset($_GET["limit"])?$_GET["limit"]:"50");}function
selectLengthProcess(){return(isset($_GET["text_length"])?$_GET["text_length"]:"100");}function
selectEmailProcess($Z,$dd){return
false;}function
selectQueryBuild($L,$Z,$nd,$yf,$z,$E){return"";}function
messageQuery($G,$ci,$Nc=false){global$x,$m;restart_session();$yd=&get_session("queries");if(!$yd[$_GET["db"]])$yd[$_GET["db"]]=array();if(strlen($G)>1e6)$G=preg_replace('~[\x80-\xFF]+$~','',substr($G,0,1e6))."\n...";$yd[$_GET["db"]][]=array($G,time(),$ci);$yh="sql-".count($yd[$_GET["db"]]);$I="<a href='#$yh' class='toggle'>".'SQL command'."</a>\n";if(!$Nc&&($aj=$m->warnings())){$t="warnings-".count($yd[$_GET["db"]]);$I="<a href='#$t' class='toggle'>".'Warnings'."</a>, $I<div id='$t' class='hidden'>\n$aj</div>\n";}return" <span class='time'>".@date("H:i:s")."</span>"." $I<div id='$yh' class='hidden'><pre><code class='jush-$x'>".shorten_utf8($G,1000)."</code></pre>".($ci?" <span class='time'>($ci)</span>":'').(support("sql")?'<p><a href="'.h(str_replace("db=".urlencode(DB),"db=".urlencode($_GET["db"]),ME).'sql=&history='.(count($yd[$_GET["db"]])-1)).'">'.'Edit'.'</a>':'').'</div>';}function
editFunctions($o){global$mc;$I=($o["null"]?"NULL/":"");foreach($mc
as$y=>$kd){if(!$y||(!isset($_GET["call"])&&(isset($_GET["select"])||where($_GET)))){foreach($kd
as$Yf=>$X){if(!$Yf||preg_match("~$Yf~",$o["type"]))$I.="/$X";}if($y&&!preg_match('~set|blob|bytea|raw|file~',$o["type"]))$I.="/SQL";}}if($o["auto_increment"]&&!isset($_GET["select"])&&!where($_GET))$I='Auto Increment';return
explode("/",$I);}function
editInput($Q,$o,$Ja,$Y){if($o["type"]=="enum")return(isset($_GET["select"])?"<label><input type='radio'$Ja value='-1' checked><i>".'original'."</i></label> ":"").($o["null"]?"<label><input type='radio'$Ja value=''".($Y!==null||isset($_GET["select"])?"":" checked")."><i>NULL</i></label> ":"").enum_input("radio",$Ja,$o,$Y,0);return"";}function
editHint($Q,$o,$Y){return"";}function
processInput($o,$Y,$r=""){if($r=="SQL")return$Y;$C=$o["field"];$I=q($Y);if(preg_match('~^(now|getdate|uuid)$~',$r))$I="$r()";elseif(preg_match('~^current_(date|timestamp)$~',$r))$I=$r;elseif(preg_match('~^([+-]|\|\|)$~',$r))$I=idf_escape($C)." $r $I";elseif(preg_match('~^[+-] interval$~',$r))$I=idf_escape($C)." $r ".(preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i",$Y)?$Y:$I);elseif(preg_match('~^(addtime|subtime|concat)$~',$r))$I="$r(".idf_escape($C).", $I)";elseif(preg_match('~^(md5|sha1|password|encrypt)$~',$r))$I="$r($I)";return
unconvert_field($o,$I);}function
dumpOutput(){$I=array('text'=>'open','file'=>'save');if(function_exists('gzencode'))$I['gz']='gzip';return$I;}function
dumpFormat(){return
array('sql'=>'SQL','csv'=>'CSV,','csv;'=>'CSV;','tsv'=>'TSV');}function
dumpDatabase($l){}function
dumpTable($Q,$Gh,$ae=0){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($Gh)dump_csv(array_keys(fields($Q)));}else{if($ae==2){$p=array();foreach(fields($Q)as$C=>$o)$p[]=idf_escape($C)." $o[full_type]";$i="CREATE TABLE ".table($Q)." (".implode(", ",$p).")";}else$i=create_sql($Q,$_POST["auto_increment"],$Gh);set_utf8mb4($i);if($Gh&&$i){if($Gh=="DROP+CREATE"||$ae==1)echo"DROP ".($ae==2?"VIEW":"TABLE")." IF EXISTS ".table($Q).";\n";if($ae==1)$i=remove_definer($i);echo"$i;\n\n";}}}function
dumpData($Q,$Gh,$G){global$g,$x;$Ee=($x=="sqlite"?0:1048576);if($Gh){if($_POST["format"]=="sql"){if($Gh=="TRUNCATE+INSERT")echo
truncate_sql($Q).";\n";$p=fields($Q);}$H=$g->query($G,1);if($H){$Td="";$Xa="";$he=array();$Ih="";$Qc=($Q!=''?'fetch_assoc':'fetch_row');while($J=$H->$Qc()){if(!$he){$Si=array();foreach($J
as$X){$o=$H->fetch_field();$he[]=$o->name;$y=idf_escape($o->name);$Si[]="$y = VALUES($y)";}$Ih=($Gh=="INSERT+UPDATE"?"\nON DUPLICATE KEY UPDATE ".implode(", ",$Si):"").";\n";}if($_POST["format"]!="sql"){if($Gh=="table"){dump_csv($he);$Gh="INSERT";}dump_csv($J);}else{if(!$Td)$Td="INSERT INTO ".table($Q)." (".implode(", ",array_map('idf_escape',$he)).") VALUES";foreach($J
as$y=>$X){$o=$p[$y];$J[$y]=($X!==null?unconvert_field($o,preg_match(number_type(),$o["type"])&&$X!=''&&!preg_match('~\[~',$o["full_type"])?$X:q(($X===false?0:$X))):"NULL");}$Wg=($Ee?"\n":" ")."(".implode(",\t",$J).")";if(!$Xa)$Xa=$Td.$Wg;elseif(strlen($Xa)+4+strlen($Wg)+strlen($Ih)<$Ee)$Xa.=",$Wg";else{echo$Xa.$Ih;$Xa=$Td.$Wg;}}}if($Xa)echo$Xa.$Ih;}elseif($_POST["format"]=="sql")echo"-- ".str_replace("\n"," ",$g->error)."\n";}}function
dumpFilename($Cd){return
friendly_url($Cd!=""?$Cd:(SERVER!=""?SERVER:"localhost"));}function
dumpHeaders($Cd,$Te=false){$If=$_POST["output"];$Ic=(preg_match('~sql~',$_POST["format"])?"sql":($Te?"tar":"csv"));header("Content-Type: ".($If=="gz"?"application/x-gzip":($Ic=="tar"?"application/x-tar":($Ic=="sql"||$If!="file"?"text/plain":"text/csv")."; charset=utf-8")));if($If=="gz")ob_start('ob_gzencode',1e6);return$Ic;}function
importServerPath(){return"adminer.sql";}function
homepage(){echo'<p class="links">'.($_GET["ns"]==""&&support("database")?'<a href="'.h(ME).'database=">'.'Alter database'."</a>\n":""),(support("scheme")?"<a href='".h(ME)."scheme='>".($_GET["ns"]!=""?'Alter schema':'Create schema')."</a>\n":""),($_GET["ns"]!==""?'<a href="'.h(ME).'schema=">'.'Database schema'."</a>\n":""),(support("privileges")?"<a href='".h(ME)."privileges='>".'Privileges'."</a>\n":"");return
true;}function
navigation($Se){global$ia,$x,$ec,$g;echo'<h1>
',$this->name(),' <span class="version">',$ia,'</span>
<a href="https://www.adminer.org/#download"',target_blank(),' id="version">',(version_compare($ia,$_COOKIE["adminer_version"])<0?h($_COOKIE["adminer_version"]):""),'</a>
</h1>
';if($Se=="auth"){$Wc=true;foreach((array)$_SESSION["pwds"]as$Ui=>$kh){foreach($kh
as$N=>$Pi){foreach($Pi
as$V=>$F){if($F!==null){if($Wc){echo"<p id='logins'>".script("mixin(qs('#logins'), {onmouseover: menuOver, onmouseout: menuOut});");$Wc=false;}$Qb=$_SESSION["db"][$Ui][$N][$V];foreach(($Qb?array_keys($Qb):array(""))as$l)echo"<a href='".h(auth_url($Ui,$N,$V,$l))."'>($ec[$Ui]) ".h($V.($N!=""?"@".$this->serverName($N):"").($l!=""?" - $l":""))."</a><br>\n";}}}}}else{if($_GET["ns"]!==""&&!$Se&&DB!=""){$g->select_db(DB);$S=table_status('',true);}echo
script_src(preg_replace("~\\?.*~","",ME)."?file=jush.js&version=4.7.0");if(support("sql")){echo'<script',nonce(),'>
';if($S){$we=array();foreach($S
as$Q=>$T)$we[]=preg_quote($Q,'/');echo"var jushLinks = { $x: [ '".js_escape(ME).(support("table")?"table=":"select=")."\$&', /\\b(".implode("|",$we).")\\b/g ] };\n";foreach(array("bac","bra","sqlite_quo","mssql_bra")as$X)echo"jushLinks.$X = jushLinks.$x;\n";}$jh=$g->server_info;echo'bodyLoad(\'',(is_object($g)?preg_replace('~^(\d\.?\d).*~s','\1',$jh):""),'\'',(preg_match('~MariaDB~',$jh)?", true":""),');
</script>
';}$this->databasesPrint($Se);if(DB==""||!$Se){echo"<p class='links'>".(support("sql")?"<a href='".h(ME)."sql='".bold(isset($_GET["sql"])&&!isset($_GET["import"])).">".'SQL command'."</a>\n<a href='".h(ME)."import='".bold(isset($_GET["import"])).">".'Import'."</a>\n":"")."";if(support("dump"))echo"<a href='".h(ME)."dump=".urlencode(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."' id='dump'".bold(isset($_GET["dump"])).">".'Export'."</a>\n";}if($_GET["ns"]!==""&&!$Se&&DB!=""){echo'<a href="'.h(ME).'create="'.bold($_GET["create"]==="").">".'Create table'."</a>\n";if(!$S)echo"<p class='message'>".'No tables.'."\n";else$this->tablesPrint($S);}}}function
databasesPrint($Se){global$b,$g;$k=$this->databases();if($k&&!in_array(DB,$k))array_unshift($k,DB);echo'<form action="">
<p id="dbs">
';hidden_fields_get();$Ob=script("mixin(qsl('select'), {onmousedown: dbMouseDown, onchange: dbChange});");echo"<span title='".'database'."'>".'DB'."</span>: ".($k?"<select name='db'>".optionlist(array(""=>"")+$k,DB)."</select>$Ob":"<input name='db' value='".h(DB)."' autocapitalize='off'>\n"),"<input type='submit' value='".'Use'."'".($k?" class='hidden'":"").">\n";if($Se!="db"&&DB!=""&&$g->select_db(DB)){if(support("scheme")){echo"<br>".'Schema'.": <select name='ns'>".optionlist(array(""=>"")+$b->schemas(),$_GET["ns"])."</select>$Ob";if($_GET["ns"]!="")set_schema($_GET["ns"]);}}foreach(array("import","sql","schema","dump","privileges")as$X){if(isset($_GET[$X])){echo"<input type='hidden' name='$X' value=''>";break;}}echo"</p></form>\n";}function
tablesPrint($S){echo"<ul id='tables'>".script("mixin(qs('#tables'), {onmouseover: menuOver, onmouseout: menuOut});");foreach($S
as$Q=>$Ch){$C=$this->tableName($Ch);if($C!=""){echo'<li><a href="'.h(ME).'select='.urlencode($Q).'"'.bold($_GET["select"]==$Q||$_GET["edit"]==$Q,"select").">".'select'."</a> ",(support("table")||support("indexes")?'<a href="'.h(ME).'table='.urlencode($Q).'"'.bold(in_array($Q,array($_GET["table"],$_GET["create"],$_GET["indexes"],$_GET["foreign"],$_GET["trigger"])),(is_view($Ch)?"view":"structure"))." title='".'Show structure'."'>$C</a>":"<span>$C</span>")."\n";}}echo"</ul>\n";}}$b=(function_exists('adminer_object')?adminer_object():new
Adminer);if($b->operators===null)$b->operators=$tf;function
page_header($fi,$n="",$Wa=array(),$gi=""){global$ca,$ia,$b,$ec,$x;page_headers();if(is_ajax()&&$n){page_messages($n);exit;}$hi=$fi.($gi!=""?": $gi":"");$ii=strip_tags($hi.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".$b->name());echo'<!DOCTYPE html>
<html lang="en" dir="ltr">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<title>',$ii,'</title>
<link rel="stylesheet" type="text/css" href="',h(preg_replace("~\\?.*~","",ME)."?file=default.css&version=4.7.0"),'">
',script_src(preg_replace("~\\?.*~","",ME)."?file=functions.js&version=4.7.0");if($b->head()){echo'<link rel="shortcut icon" type="image/x-icon" href="',h(preg_replace("~\\?.*~","",ME)."?file=favicon.ico&version=4.7.0"),'">
<link rel="apple-touch-icon" href="',h(preg_replace("~\\?.*~","",ME)."?file=favicon.ico&version=4.7.0"),'">
';foreach($b->css()as$Ib){echo'<link rel="stylesheet" type="text/css" href="',h($Ib),'">
';}}echo'
<body class="ltr nojs">
';$Uc=get_temp_dir()."/adminer.version";if(!$_COOKIE["adminer_version"]&&function_exists('openssl_verify')&&file_exists($Uc)&&filemtime($Uc)+86400>time()){$Vi=unserialize(file_get_contents($Uc));$rg="-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwqWOVuF5uw7/+Z70djoK
RlHIZFZPO0uYRezq90+7Amk+FDNd7KkL5eDve+vHRJBLAszF/7XKXe11xwliIsFs
DFWQlsABVZB3oisKCBEuI71J4kPH8dKGEWR9jDHFw3cWmoH3PmqImX6FISWbG3B8
h7FIx3jEaw5ckVPVTeo5JRm/1DZzJxjyDenXvBQ/6o9DgZKeNDgxwKzH+sw9/YCO
jHnq1cFpOIISzARlrHMa/43YfeNRAm/tsBXjSxembBPo7aQZLAWHmaj5+K19H10B
nCpz9Y++cipkVEiKRGih4ZEvjoFysEOdRLj6WiD/uUNky4xGeA6LaJqh5XpkFkcQ
fQIDAQAB
-----END PUBLIC KEY-----
";if(openssl_verify($Vi["version"],base64_decode($Vi["signature"]),$rg)==1)$_COOKIE["adminer_version"]=$Vi["version"];}echo'<script',nonce(),'>
mixin(document.body, {onkeydown: bodyKeydown, onclick: bodyClick',(isset($_COOKIE["adminer_version"])?"":", onload: partial(verifyVersion, '$ia', '".js_escape(ME)."', '".get_token()."')");?>});
document.body.className = document.body.className.replace(/ nojs/, ' js');
var offlineMessage = '<?php echo
js_escape('You are offline.'),'\';
var thousandsSeparator = \'',js_escape(','),'\';
</script>

<div id="help" class="jush-',$x,' jsonly hidden"></div>
',script("mixin(qs('#help'), {onmouseover: function () { helpOpen = 1; }, onmouseout: helpMouseout});"),'
<div id="content">
';if($Wa!==null){$_=substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.h($_?$_:".").'">'.$ec[DRIVER].'</a> &raquo; ';$_=substr(preg_replace('~\b(db|ns)=[^&]*&~','',ME),0,-1);$N=$b->serverName(SERVER);$N=($N!=""?$N:'Server');if($Wa===false)echo"$N\n";else{echo"<a href='".($_?h($_):".")."' accesskey='1' title='Alt+Shift+1'>$N</a> &raquo; ";if($_GET["ns"]!=""||(DB!=""&&is_array($Wa)))echo'<a href="'.h($_."&db=".urlencode(DB).(support("scheme")?"&ns=":"")).'">'.h(DB).'</a> &raquo; ';if(is_array($Wa)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1)).'">'.h($_GET["ns"]).'</a> &raquo; ';foreach($Wa
as$y=>$X){$Wb=(is_array($X)?$X[1]:h($X));if($Wb!="")echo"<a href='".h(ME."$y=").urlencode(is_array($X)?$X[0]:$X)."'>$Wb</a> &raquo; ";}}echo"$fi\n";}}echo"<h2>$hi</h2>\n","<div id='ajaxstatus' class='jsonly hidden'></div>\n";restart_session();page_messages($n);$k=&get_session("dbs");if(DB!=""&&$k&&!in_array(DB,$k,true))$k=null;stop_session();define("PAGE_HEADER",1);}function
page_headers(){global$b;header("Content-Type: text/html; charset=utf-8");header("Cache-Control: no-cache");header("X-Frame-Options: deny");header("X-XSS-Protection: 0");header("X-Content-Type-Options: nosniff");header("Referrer-Policy: origin-when-cross-origin");foreach($b->csp()as$Hb){$wd=array();foreach($Hb
as$y=>$X)$wd[]="$y $X";header("Content-Security-Policy: ".implode("; ",$wd));}$b->headers();}function
csp(){return
array(array("script-src"=>"'self' 'unsafe-inline' 'nonce-".get_nonce()."' 'strict-dynamic'","connect-src"=>"'self'","frame-src"=>"https://www.adminer.org","object-src"=>"'none'","base-uri"=>"'none'","form-action"=>"'self'",),);}function
get_nonce(){static$cf;if(!$cf)$cf=base64_encode(rand_string());return$cf;}function
page_messages($n){$Ii=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$Oe=$_SESSION["messages"][$Ii];if($Oe){echo"<div class='message'>".implode("</div>\n<div class='message'>",$Oe)."</div>".script("messagesPrint();");unset($_SESSION["messages"][$Ii]);}if($n)echo"<div class='error'>$n</div>\n";}function
page_footer($Se=""){global$b,$mi;echo'</div>

';if($Se!="auth"){echo'<form action="" method="post">
<p class="logout">
<input type="submit" name="logout" value="Logout" id="logout">
<input type="hidden" name="token" value="',$mi,'">
</p>
</form>
';}echo'<div id="menu">
';$b->navigation($Se);echo'</div>
',script("setupSubmitHighlight(document);");}function
int32($Ve){while($Ve>=2147483648)$Ve-=4294967296;while($Ve<=-2147483649)$Ve+=4294967296;return(int)$Ve;}function
long2str($W,$Zi){$Wg='';foreach($W
as$X)$Wg.=pack('V',$X);if($Zi)return
substr($Wg,0,end($W));return$Wg;}function
str2long($Wg,$Zi){$W=array_values(unpack('V*',str_pad($Wg,4*ceil(strlen($Wg)/4),"\0")));if($Zi)$W[]=strlen($Wg);return$W;}function
xxtea_mx($mj,$lj,$Jh,$de){return
int32((($mj>>5&0x7FFFFFF)^$lj<<2)+(($lj>>3&0x1FFFFFFF)^$mj<<4))^int32(($Jh^$lj)+($de^$mj));}function
encrypt_string($Eh,$y){if($Eh=="")return"";$y=array_values(unpack("V*",pack("H*",md5($y))));$W=str2long($Eh,true);$Ve=count($W)-1;$mj=$W[$Ve];$lj=$W[0];$sg=floor(6+52/($Ve+1));$Jh=0;while($sg-->0){$Jh=int32($Jh+0x9E3779B9);$lc=$Jh>>2&3;for($Jf=0;$Jf<$Ve;$Jf++){$lj=$W[$Jf+1];$Ue=xxtea_mx($mj,$lj,$Jh,$y[$Jf&3^$lc]);$mj=int32($W[$Jf]+$Ue);$W[$Jf]=$mj;}$lj=$W[0];$Ue=xxtea_mx($mj,$lj,$Jh,$y[$Jf&3^$lc]);$mj=int32($W[$Ve]+$Ue);$W[$Ve]=$mj;}return
long2str($W,false);}function
decrypt_string($Eh,$y){if($Eh=="")return"";if(!$y)return
false;$y=array_values(unpack("V*",pack("H*",md5($y))));$W=str2long($Eh,false);$Ve=count($W)-1;$mj=$W[$Ve];$lj=$W[0];$sg=floor(6+52/($Ve+1));$Jh=int32($sg*0x9E3779B9);while($Jh){$lc=$Jh>>2&3;for($Jf=$Ve;$Jf>0;$Jf--){$mj=$W[$Jf-1];$Ue=xxtea_mx($mj,$lj,$Jh,$y[$Jf&3^$lc]);$lj=int32($W[$Jf]-$Ue);$W[$Jf]=$lj;}$mj=$W[$Ve];$Ue=xxtea_mx($mj,$lj,$Jh,$y[$Jf&3^$lc]);$lj=int32($W[0]-$Ue);$W[0]=$lj;$Jh=int32($Jh-0x9E3779B9);}return
long2str($W,true);}global$m;$g='';$vd=$_SESSION["token"];if(!$vd)$_SESSION["token"]=rand(1,1e6);$mi=get_token();$Zf=array();if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$X){list($y)=explode(":",$X);$Zf[$y]=$X;}}function
add_invalid_login(){global$b;$id=file_open_lock(get_temp_dir()."/adminer.invalid");if(!$id)return;$Wd=unserialize(stream_get_contents($id));$ci=time();if($Wd){foreach($Wd
as$Xd=>$X){if($X[0]<$ci)unset($Wd[$Xd]);}}$Vd=&$Wd[$b->bruteForceKey()];if(!$Vd)$Vd=array($ci+30*60,0);$Vd[1]++;file_write_unlock($id,serialize($Wd));}function
check_invalid_login(){global$b;$Wd=unserialize(@file_get_contents(get_temp_dir()."/adminer.invalid"));$Vd=$Wd[$b->bruteForceKey()];$bf=($Vd[1]>29?$Vd[0]-time():0);if($bf>0)auth_error(lang(array('Too many unsuccessful logins, try again in %d minute.','Too many unsuccessful logins, try again in %d minutes.'),ceil($bf/60)));}$Ka=$_POST["auth"];if($Ka){session_regenerate_id();$Ui=$Ka["driver"];$N=$Ka["server"];$V=$Ka["username"];$F=(string)$Ka["password"];$l=$Ka["db"];set_password($Ui,$N,$V,$F);$_SESSION["db"][$Ui][$N][$V][$l]=true;if($Ka["permanent"]){$y=base64_encode($Ui)."-".base64_encode($N)."-".base64_encode($V)."-".base64_encode($l);$lg=$b->permanentLogin(true);$Zf[$y]="$y:".base64_encode($lg?encrypt_string($F,$lg):"");cookiem("adminer_permanent",implode(" ",$Zf));}if(count($_POST)==1||DRIVER!=$Ui||SERVER!=$N||$_GET["username"]!==$V||DB!=$l)redirectm(auth_url($Ui,$N,$V,$l));}elseif($_POST["logout"]){if($vd&&!verify_token()){page_header('Logout','Invalid CSRF token. Send the form again.');page_footer("db");exit;}else{foreach(array("pwds","db","dbs","queries")as$y)set_session($y,null);unset_permanent();redirectm(substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1),'Logout successful.'.' '.'Thanks for using Adminer, consider <a href="https://www.adminer.org/en/donation/">donating</a>.');}}elseif($Zf&&!$_SESSION["pwds"]){session_regenerate_id();$lg=$b->permanentLogin();foreach($Zf
as$y=>$X){list(,$ib)=explode(":",$X);list($Ui,$N,$V,$l)=array_map('base64_decode',explode("-",$y));set_password($Ui,$N,$V,decrypt_string(base64_decode($ib),$lg));$_SESSION["db"][$Ui][$N][$V][$l]=true;}}function
unset_permanent(){global$Zf;foreach($Zf
as$y=>$X){list($Ui,$N,$V,$l)=array_map('base64_decode',explode("-",$y));if($Ui==DRIVER&&$N==SERVER&&$V==$_GET["username"]&&$l==DB)unset($Zf[$y]);}cookiem("adminer_permanent",implode(" ",$Zf));}function
auth_error($n){global$b,$vd;$lh=session_name();if(isset($_GET["username"])){header("HTTP/1.1 403 Forbidden");if(($_COOKIE[$lh]||$_GET[$lh])&&!$vd)$n='Session expired, please login again.';else{restart_session();add_invalid_login();$F=get_password();if($F!==null){if($F===false)$n.='<br>'.sprintf('Master password expired. <a href="https://www.adminer.org/en/extension/"%s>Implement</a> %s method to make it permanent.',target_blank(),'<code>permanentLogin()</code>');set_password(DRIVER,SERVER,$_GET["username"],null);}unset_permanent();}}if(!$_COOKIE[$lh]&&$_GET[$lh]&&ini_bool("session.use_only_cookies"))$n='Session support must be enabled.';$Mf=session_get_cookie_params();cookiem("adminer_key",($_COOKIE["adminer_key"]?$_COOKIE["adminer_key"]:rand_string()),$Mf["lifetime"]);page_header('Login',$n,null);echo"<form action='' method='post'>\n","<div>";if(hidden_fields($_POST,array("auth")))echo"<p class='message'>".'The action will be performed after successful login with the same credentials.'."\n";echo"</div>\n";$b->loginForm();echo"</form>\n";page_footer("auth");exit;}if(isset($_GET["username"])&&!class_exists("Min_DB")){unset($_SESSION["pwds"][DRIVER]);unset_permanent();page_header('No extension',sprintf('None of the supported PHP extensions (%s) are available.',implode(", ",$fg)),false);page_footer("auth");exit;}stop_session(true);if(isset($_GET["username"])){list($Ad,$bg)=explode(":",SERVER,2);if(is_numeric($bg)&&$bg<1024)auth_error('Connecting to privileged ports is not allowed.');check_invalid_login();$g=connect();$m=new
Min_Driver($g);}$xe=null;if(!is_object($g)||($xe=$b->login($_GET["username"],get_password()))!==true){$n=(is_string($g)?h($g):(is_string($xe)?$xe:'Invalid credentials.'));auth_error($n.(preg_match('~^ | $~',get_password())?'<br>'.'There is a space in the input password which might be the cause.':''));}if($Ka&&$_POST["token"])$_POST["token"]=$mi;$n='';if($_POST){if(!verify_token()){$Qd="max_input_vars";$Ie=ini_get($Qd);if(extension_loaded("suhosin")){foreach(array("suhosin.request.max_vars","suhosin.post.max_vars")as$y){$X=ini_get($y);if($X&&(!$Ie||$X<$Ie)){$Qd=$y;$Ie=$X;}}}$n=(!$_POST["token"]&&$Ie?sprintf('Maximum number of allowed fields exceeded. Please increase %s.',"'$Qd'"):'Invalid CSRF token. Send the form again.'.' '.'If you did not send this request from Adminer then close this page.');}}elseif($_SERVER["REQUEST_METHOD"]=="POST"){$n=sprintf('Too big POST data. Reduce the data or increase the %s configuration directive.',"'post_max_size'");if(isset($_GET["sql"]))$n.=' '.'You can upload a big SQL file via FTP and import it from server.';}function
select($H,$h=null,$Af=array(),$z=0){global$x;$we=array();$w=array();$f=array();$Ta=array();$U=array();$I=array();odd('');for($s=0;(!$z||$s<$z)&&($J=$H->fetch_row());$s++){if(!$s){echo"<div class='scrollable'>\n","<table cellspacing='0' class='nowrap'>\n","<thead><tr>";for($ce=0;$ce<count($J);$ce++){$o=$H->fetch_field();$C=$o->name;$_f=$o->orgtable;$zf=$o->orgname;$I[$o->table]=$_f;if($Af&&$x=="sql")$we[$ce]=($C=="table"?"table=":($C=="possible_keys"?"indexes=":null));elseif($_f!=""){if(!isset($w[$_f])){$w[$_f]=array();foreach(indexes($_f,$h)as$v){if($v["type"]=="PRIMARY"){$w[$_f]=array_flip($v["columns"]);break;}}$f[$_f]=$w[$_f];}if(isset($f[$_f][$zf])){unset($f[$_f][$zf]);$w[$_f][$zf]=$ce;$we[$ce]=$_f;}}if($o->charsetnr==63)$Ta[$ce]=true;$U[$ce]=$o->type;echo"<th".($_f!=""||$o->name!=$zf?" title='".h(($_f!=""?"$_f.":"").$zf)."'":"").">".h($C).($Af?doc_link(array('sql'=>"explain-output.html#explain_".strtolower($C),'mariadb'=>"explain/#the-columns-in-explain-select",)):"");}echo"</thead>\n";}echo"<tr".odd().">";foreach($J
as$y=>$X){if($X===null)$X="<i>NULL</i>";elseif($Ta[$y]&&!is_utf8($X))$X="<i>".lang(array('%d byte','%d bytes'),strlen($X))."</i>";else{$X=h($X);if($U[$y]==254)$X="<code>$X</code>";}if(isset($we[$y])&&!$f[$we[$y]]){if($Af&&$x=="sql"){$Q=$J[array_search("table=",$we)];$_=$we[$y].urlencode($Af[$Q]!=""?$Af[$Q]:$Q);}else{$_="edit=".urlencode($we[$y]);foreach($w[$we[$y]]as$mb=>$ce)$_.="&where".urlencode("[".bracket_escape($mb)."]")."=".urlencode($J[$ce]);}$X="<a href='".h(ME.$_)."'>$X</a>";}echo"<td>$X";}}echo($s?"</table>\n</div>":"<p class='message'>".'No rows.')."\n";return$I;}function
referencable_primary($fh){$I=array();foreach(table_status('',true)as$Nh=>$Q){if($Nh!=$fh&&fk_support($Q)){foreach(fields($Nh)as$o){if($o["primary"]){if($I[$Nh]){unset($I[$Nh]);break;}$I[$Nh]=$o;}}}}return$I;}function
textarea($C,$Y,$K=10,$qb=80){global$x;echo"<textarea name='$C' rows='$K' cols='$qb' class='sqlarea jush-$x' spellcheck='false' wrap='off'>";if(is_array($Y)){foreach($Y
as$X)echo
h($X[0])."\n\n\n";}else
echo
h($Y);echo"</textarea>";}function
edit_type($y,$o,$ob,$ed=array(),$Lc=array()){global$Fh,$U,$Gi,$of;$T=$o["type"];echo'<td><select name="',h($y),'[type]" class="type" aria-labelledby="label-type">';if($T&&!isset($U[$T])&&!isset($ed[$T])&&!in_array($T,$Lc))$Lc[]=$T;if($ed)$Fh['Foreign keys']=$ed;echo
optionlist(array_merge($Lc,$Fh),$T),'</select>
',on_help("getTarget(event).value",1),script("mixin(qsl('select'), {onfocus: function () { lastType = selectValue(this); }, onchange: editingTypeChange});",""),'<td><input name="',h($y),'[length]" value="',h($o["length"]),'" size="3"',(!$o["length"]&&preg_match('~var(char|binary)$~',$T)?" class='required'":"");echo' aria-labelledby="label-length">',script("mixin(qsl('input'), {onfocus: editingLengthFocus, oninput: editingLengthChange});",""),'<td class="options">',"<select name='".h($y)."[collation]'".(preg_match('~(char|text|enum|set)$~',$T)?"":" class='hidden'").'><option value="">('.'collation'.')'.optionlist($ob,$o["collation"]).'</select>',($Gi?"<select name='".h($y)."[unsigned]'".(!$T||preg_match(number_type(),$T)?"":" class='hidden'").'><option>'.optionlist($Gi,$o["unsigned"]).'</select>':''),(isset($o['on_update'])?"<select name='".h($y)."[on_update]'".(preg_match('~timestamp|datetime~',$T)?"":" class='hidden'").'>'.optionlist(array(""=>"(".'ON UPDATE'.")","CURRENT_TIMESTAMP"),(preg_match('~^CURRENT_TIMESTAMP~i',$o["on_update"])?"CURRENT_TIMESTAMP":$o["on_update"])).'</select>':''),($ed?"<select name='".h($y)."[on_delete]'".(preg_match("~`~",$T)?"":" class='hidden'")."><option value=''>(".'ON DELETE'.")".optionlist(explode("|",$of),$o["on_delete"])."</select> ":" ");}function
process_length($te){global$wc;return(preg_match("~^\\s*\\(?\\s*$wc(?:\\s*,\\s*$wc)*+\\s*\\)?\\s*\$~",$te)&&preg_match_all("~$wc~",$te,$Ce)?"(".implode(",",$Ce[0]).")":preg_replace('~^[0-9].*~','(\0)',preg_replace('~[^-0-9,+()[\]]~','',$te)));}function
process_type($o,$nb="COLLATE"){global$Gi;return" $o[type]".process_length($o["length"]).(preg_match(number_type(),$o["type"])&&in_array($o["unsigned"],$Gi)?" $o[unsigned]":"").(preg_match('~char|text|enum|set~',$o["type"])&&$o["collation"]?" $nb ".q($o["collation"]):"");}function
process_field($o,$zi){return
array(idf_escape(trim($o["field"])),process_type($zi),($o["null"]?" NULL":" NOT NULL"),default_value($o),(preg_match('~timestamp|datetime~',$o["type"])&&$o["on_update"]?" ON UPDATE $o[on_update]":""),(support("comment")&&$o["comment"]!=""?" COMMENT ".q($o["comment"]):""),($o["auto_increment"]?auto_increment():null),);}function
default_value($o){$Sb=$o["default"];return($Sb===null?"":" DEFAULT ".(preg_match('~char|binary|text|enum|set~',$o["type"])||preg_match('~^(?![a-z])~i',$Sb)?q($Sb):$Sb));}function
type_class($T){foreach(array('char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',)as$y=>$X){if(preg_match("~$y|$X~",$T))return" class='$y'";}}function
edit_fields($p,$ob,$T="TABLE",$ed=array(),$ub=false){global$Rd;$p=array_values($p);echo'<thead><tr>
';if($T=="PROCEDURE"){echo'<td>';}echo'<th id="label-name">',($T=="TABLE"?'Column name':'Parameter name'),'<td id="label-type">Type<textarea id="enum-edit" rows="4" cols="12" wrap="off" style="display: none;"></textarea>',script("qs('#enum-edit').onblur = editingLengthBlur;"),'<td id="label-length">Length
<td>','Options';if($T=="TABLE"){echo'<td id="label-null">NULL
<td><input type="radio" name="auto_increment_col" value=""><acronym id="label-ai" title="Auto Increment">AI</acronym>',doc_link(array('sql'=>"example-auto-increment.html",'mariadb'=>"auto_increment/",'sqlite'=>"autoinc.html",'pgsql'=>"datatype.html#DATATYPE-SERIAL",'mssql'=>"ms186775.aspx",)),'<td id="label-default">Default value
',(support("comment")?"<td id='label-comment'".($ub?"":" class='hidden'").">".'Comment':"");}echo'<td>',"<input type='image' class='icon' name='add[".(support("move_col")?0:count($p))."]' src='".h(preg_replace("~\\?.*~","",ME)."?file=plus.gif&version=4.7.0")."' alt='+' title='".'Add next'."'>".script("row_count = ".count($p).";"),'</thead>
<tbody>
',script("mixin(qsl('tbody'), {onclick: editingClick, onkeydown: editingKeydown, oninput: editingInput});");foreach($p
as$s=>$o){$s++;$Bf=$o[($_POST?"orig":"field")];$ac=(isset($_POST["add"][$s-1])||(isset($o["field"])&&!$_POST["drop_col"][$s]))&&(support("drop_col")||$Bf=="");echo'<tr',($ac?"":" style='display: none;'"),'>
',($T=="PROCEDURE"?"<td>".html_select("fields[$s][inout]",explode("|",$Rd),$o["inout"]):""),'<th>';if($ac){echo'<input name="fields[',$s,'][field]" value="',h($o["field"]),'" data-maxlength="64" autocapitalize="off" aria-labelledby="label-name">',script("qsl('input').oninput = function () { editingNameChange.call(this);".($o["field"]!=""||count($p)>1?"":" editingAddRow.call(this);")." };","");}echo'<input type="hidden" name="fields[',$s,'][orig]" value="',h($Bf),'">
';edit_type("fields[$s]",$o,$ob,$ed);if($T=="TABLE"){echo'<td>',checkbox("fields[$s][null]",1,$o["null"],"","","block","label-null"),'<td><label class="block"><input type="radio" name="auto_increment_col" value="',$s,'"';if($o["auto_increment"]){echo' checked';}echo' aria-labelledby="label-ai"></label><td>',checkbox("fields[$s][has_default]",1,$o["has_default"],"","","","label-default"),'<input name="fields[',$s,'][default]" value="',h($o["default"]),'" aria-labelledby="label-default">',(support("comment")?"<td".($ub?"":" class='hidden'")."><input name='fields[$s][comment]' value='".h($o["comment"])."' data-maxlength='".(min_version(5.5)?1024:255)."' aria-labelledby='label-comment'>":"");}echo"<td>",(support("move_col")?"<input type='image' class='icon' name='add[$s]' src='".h(preg_replace("~\\?.*~","",ME)."?file=plus.gif&version=4.7.0")."' alt='+' title='".'Add next'."'> "."<input type='image' class='icon' name='up[$s]' src='".h(preg_replace("~\\?.*~","",ME)."?file=up.gif&version=4.7.0")."' alt='↑' title='".'Move up'."'> "."<input type='image' class='icon' name='down[$s]' src='".h(preg_replace("~\\?.*~","",ME)."?file=down.gif&version=4.7.0")."' alt='↓' title='".'Move down'."'> ":""),($Bf==""||support("drop_col")?"<input type='image' class='icon' name='drop_col[$s]' src='".h(preg_replace("~\\?.*~","",ME)."?file=cross.gif&version=4.7.0")."' alt='x' title='".'Remove'."'>":"");}}function
process_fields(&$p){$D=0;if($_POST["up"]){$ne=0;foreach($p
as$y=>$o){if(key($_POST["up"])==$y){unset($p[$y]);array_splice($p,$ne,0,array($o));break;}if(isset($o["field"]))$ne=$D;$D++;}}elseif($_POST["down"]){$gd=false;foreach($p
as$y=>$o){if(isset($o["field"])&&$gd){unset($p[key($_POST["down"])]);array_splice($p,$D,0,array($gd));break;}if(key($_POST["down"])==$y)$gd=$o;$D++;}}elseif($_POST["add"]){$p=array_values($p);array_splice($p,key($_POST["add"]),0,array(array()));}elseif(!$_POST["drop_col"])return
false;return
true;}function
normalize_enum($B){return"'".str_replace("'","''",addcslashes(stripcslashes(str_replace($B[0][0].$B[0][0],$B[0][0],substr($B[0],1,-1))),'\\'))."'";}function
grant($ld,$ng,$f,$nf){if(!$ng)return
true;if($ng==array("ALL PRIVILEGES","GRANT OPTION"))return($ld=="GRANT"?queries("$ld ALL PRIVILEGES$nf WITH GRANT OPTION"):queries("$ld ALL PRIVILEGES$nf")&&queries("$ld GRANT OPTION$nf"));return
queries("$ld ".preg_replace('~(GRANT OPTION)\([^)]*\)~','\1',implode("$f, ",$ng).$f).$nf);}function
drop_create($fc,$i,$gc,$Zh,$ic,$A,$Ne,$Le,$Me,$kf,$Ye){if($_POST["drop"])query_redirect($fc,$A,$Ne);elseif($kf=="")query_redirect($i,$A,$Me);elseif($kf!=$Ye){$Fb=queries($i);queries_redirect($A,$Le,$Fb&&queries($fc));if($Fb)queries($gc);}else
queries_redirect($A,$Le,queries($Zh)&&queries($ic)&&queries($fc)&&queries($i));}function
create_trigger($nf,$J){global$x;$ei=" $J[Timing] $J[Event]".($J["Event"]=="UPDATE OF"?" ".idf_escape($J["Of"]):"");return"CREATE TRIGGER ".idf_escape($J["Trigger"]).($x=="mssql"?$nf.$ei:$ei.$nf).rtrim(" $J[Type]\n$J[Statement]",";").";";}function
create_routine($Sg,$J){global$Rd,$x;$O=array();$p=(array)$J["fields"];ksort($p);foreach($p
as$o){if($o["field"]!="")$O[]=(preg_match("~^($Rd)\$~",$o["inout"])?"$o[inout] ":"").idf_escape($o["field"]).process_type($o,"CHARACTER SET");}$Tb=rtrim("\n$J[definition]",";");return"CREATE $Sg ".idf_escape(trim($J["name"]))." (".implode(", ",$O).")".(isset($_GET["function"])?" RETURNS".process_type($J["returns"],"CHARACTER SET"):"").($J["language"]?" LANGUAGE $J[language]":"").($x=="pgsql"?" AS ".q($Tb):"$Tb;");}function
remove_definer($G){return
preg_replace('~^([A-Z =]+) DEFINER=`'.preg_replace('~@(.*)~','`@`(%|\1)',logged_user()).'`~','\1',$G);}function
format_foreign_key($q){global$of;return" FOREIGN KEY (".implode(", ",array_map('idf_escape',$q["source"])).") REFERENCES ".table($q["table"])." (".implode(", ",array_map('idf_escape',$q["target"])).")".(preg_match("~^($of)\$~",$q["on_delete"])?" ON DELETE $q[on_delete]":"").(preg_match("~^($of)\$~",$q["on_update"])?" ON UPDATE $q[on_update]":"");}function
tar_file($Uc,$ji){$I=pack("a100a8a8a8a12a12",$Uc,644,0,0,decoct($ji->size),decoct(time()));$gb=8*32;for($s=0;$s<strlen($I);$s++)$gb+=ord($I[$s]);$I.=sprintf("%06o",$gb)."\0 ";echo$I,str_repeat("\0",512-strlen($I));$ji->send();echo
str_repeat("\0",511-($ji->size+511)%512);}function
ini_bytes($Qd){$X=ini_get($Qd);switch(strtolower(substr($X,-1))){case'g':$X*=1024;case'm':$X*=1024;case'k':$X*=1024;}return$X;}function
doc_link($Xf,$ai="<sup>?</sup>"){global$x,$g;$jh=$g->server_info;$Vi=preg_replace('~^(\d\.?\d).*~s','\1',$jh);$Li=array('sql'=>"https://dev.mysql.com/doc/refman/$Vi/en/",'sqlite'=>"https://www.sqlite.org/",'pgsql'=>"https://www.postgresql.org/docs/$Vi/static/",'mssql'=>"https://msdn.microsoft.com/library/",'oracle'=>"https://download.oracle.com/docs/cd/B19306_01/server.102/b14200/",);if(preg_match('~MariaDB~',$jh)){$Li['sql']="https://mariadb.com/kb/en/library/";$Xf['sql']=(isset($Xf['mariadb'])?$Xf['mariadb']:str_replace(".html","/",$Xf['sql']));}return($Xf[$x]?"<a href='$Li[$x]$Xf[$x]'".target_blank().">$ai</a>":"");}function
ob_gzencode($P){return
gzencode($P);}function
db_size($l){global$g;if(!$g->select_db($l))return"?";$I=0;foreach(table_status()as$R)$I+=$R["Data_length"]+$R["Index_length"];return
format_number($I);}function
set_utf8mb4($i){global$g;static$O=false;if(!$O&&preg_match('~\butf8mb4~i',$i)){$O=true;echo"SET NAMES ".charset($g).";\n\n";}}function
connect_error(){global$b,$g,$mi,$n,$ec;if(DB!=""){header("HTTP/1.1 404 Not Found");page_header('Database'.": ".h(DB),'Invalid database.',true);}else{if($_POST["db"]&&!$n)queries_redirect(substr(ME,0,-1),'Databases have been dropped.',drop_databases($_POST["db"]));page_header('Select database',$n,false);echo"<p class='links'>\n";foreach(array('database'=>'Create database','privileges'=>'Privileges','processlist'=>'Process list','variables'=>'Variables','status'=>'Status',)as$y=>$X){if(support($y))echo"<a href='".h(ME)."$y='>$X</a>\n";}echo"<p>".sprintf('%s version: %s through PHP extension %s',$ec[DRIVER],"<b>".h($g->server_info)."</b>","<b>$g->extension</b>")."\n","<p>".sprintf('Logged as: %s',"<b>".h(logged_user())."</b>")."\n";$k=$b->databases();if($k){$Zg=support("scheme");$ob=collations();echo"<form action='' method='post'>\n","<table cellspacing='0' class='checkable'>\n",script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});"),"<thead><tr>".(support("database")?"<td>":"")."<th>".'Database'." - <a href='".h(ME)."refresh=1'>".'Refresh'."</a>"."<td>".'Collation'."<td>".'Tables'."<td>".'Size'." - <a href='".h(ME)."dbsize=1'>".'Compute'."</a>".script("qsl('a').onclick = partial(ajaxSetHtml, '".js_escape(ME)."script=connect');","")."</thead>\n";$k=($_GET["dbsize"]?count_tables($k):array_flip($k));foreach($k
as$l=>$S){$Rg=h(ME)."db=".urlencode($l);$t=h("Db-".$l);echo"<tr".odd().">".(support("database")?"<td>".checkbox("db[]",$l,in_array($l,(array)$_POST["db"]),"","","",$t):""),"<th><a href='$Rg' id='$t'>".h($l)."</a>";$d=h(db_collation($l,$ob));echo"<td>".(support("database")?"<a href='$Rg".($Zg?"&amp;ns=":"")."&amp;database=' title='".'Alter database'."'>$d</a>":$d),"<td align='right'><a href='$Rg&amp;schema=' id='tables-".h($l)."' title='".'Database schema'."'>".($_GET["dbsize"]?$S:"?")."</a>","<td align='right' id='size-".h($l)."'>".($_GET["dbsize"]?db_size($l):"?"),"\n";}echo"</table>\n",(support("database")?"<div class='footer'><div>\n"."<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>\n"."<input type='hidden' name='all' value=''>".script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^db/)); };")."<input type='submit' name='drop' value='".'Drop'."'>".confirm()."\n"."</div></fieldset>\n"."</div></div>\n":""),"<input type='hidden' name='token' value='$mi'>\n","</form>\n",script("tableCheck();");}}page_footer("db");}if(isset($_GET["status"]))$_GET["variables"]=$_GET["status"];if(isset($_GET["import"]))$_GET["sql"]=$_GET["import"];if(!(DB!=""?$g->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["variables"])||$_GET["script"]=="connect"||$_GET["script"]=="kill")){if(DB!=""||$_GET["refresh"]){restart_session();set_session("dbs",null);}connect_error();exit;}if(support("scheme")&&DB!=""&&$_GET["ns"]!==""){if(!isset($_GET["ns"]))redirectm(preg_replace('~ns=[^&]*&~','',ME)."ns=".get_schema());if(!set_schema($_GET["ns"])){header("HTTP/1.1 404 Not Found");page_header('Schema'.": ".h($_GET["ns"]),'Invalid schema.',true);page_footer("ns");exit;}}$of="RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";class
TmpFile{var$handler;var$size;function
__construct(){$this->handler=tmpfile();}function
write($_b){$this->size+=strlen($_b);fwrite($this->handler,$_b);}function
send(){fseek($this->handler,0);fpassthru($this->handler);fclose($this->handler);}}$wc="'(?:''|[^'\\\\]|\\\\.)*'";$Rd="IN|OUT|INOUT";if(isset($_GET["select"])&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["callf"]))$_GET["call"]=$_GET["callf"];if(isset($_GET["function"]))$_GET["procedure"]=$_GET["function"];if(isset($_GET["download"])){$a=$_GET["download"];$p=fields($a);header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=".friendly_url("$a-".implode("_",$_GET["where"])).".".friendly_url($_GET["field"]));$L=array(idf_escape($_GET["field"]));$H=$m->select($a,$L,array(where($_GET,$p)),$L);$J=($H?$H->fetch_row():array());echo$m->value($J[0],$p[$_GET["field"]]);exit;}elseif(isset($_GET["table"])){$a=$_GET["table"];$p=fields($a);if(!$p)$n=error();$R=table_status1($a,true);$C=$b->tableName($R);page_header(($p&&is_view($R)?$R['Engine']=='materialized view'?'Materialized view':'View':'Table').": ".($C!=""?$C:h($a)),$n);$b->selectLinks($R);$tb=$R["Comment"];if($tb!="")echo"<p class='nowrap'>".'Comment'.": ".h($tb)."\n";if($p)$b->tableStructurePrint($p);if(!is_view($R)){if(support("indexes")){echo"<h3 id='indexes'>".'Indexes'."</h3>\n";$w=indexes($a);if($w)$b->tableIndexesPrint($w);echo'<p class="links"><a href="'.h(ME).'indexes='.urlencode($a).'">'.'Alter indexes'."</a>\n";}if(fk_support($R)){echo"<h3 id='foreign-keys'>".'Foreign keys'."</h3>\n";$ed=foreign_keys($a);if($ed){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Source'."<td>".'Target'."<td>".'ON DELETE'."<td>".'ON UPDATE'."<td></thead>\n";foreach($ed
as$C=>$q){echo"<tr title='".h($C)."'>","<th><i>".implode("</i>, <i>",array_map('h',$q["source"]))."</i>","<td><a href='".h($q["db"]!=""?preg_replace('~db=[^&]*~',"db=".urlencode($q["db"]),ME):($q["ns"]!=""?preg_replace('~ns=[^&]*~',"ns=".urlencode($q["ns"]),ME):ME))."table=".urlencode($q["table"])."'>".($q["db"]!=""?"<b>".h($q["db"])."</b>.":"").($q["ns"]!=""?"<b>".h($q["ns"])."</b>.":"").h($q["table"])."</a>","(<i>".implode("</i>, <i>",array_map('h',$q["target"]))."</i>)","<td>".h($q["on_delete"])."\n","<td>".h($q["on_update"])."\n",'<td><a href="'.h(ME.'foreign='.urlencode($a).'&name='.urlencode($C)).'">'.'Alter'.'</a>';}echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'foreign='.urlencode($a).'">'.'Add foreign key'."</a>\n";}}if(support(is_view($R)?"view_trigger":"trigger")){echo"<h3 id='triggers'>".'Triggers'."</h3>\n";$yi=triggers($a);if($yi){echo"<table cellspacing='0'>\n";foreach($yi
as$y=>$X)echo"<tr valign='top'><td>".h($X[0])."<td>".h($X[1])."<th>".h($y)."<td><a href='".h(ME.'trigger='.urlencode($a).'&name='.urlencode($y))."'>".'Alter'."</a>\n";echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'trigger='.urlencode($a).'">'.'Add trigger'."</a>\n";}}elseif(isset($_GET["schema"])){page_header('Database schema',"",array(),h(DB.($_GET["ns"]?".$_GET[ns]":"")));$Ph=array();$Qh=array();$ea=($_GET["schema"]?$_GET["schema"]:$_COOKIE["adminer_schema-".str_replace(".","_",DB)]);preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$ea,$Ce,PREG_SET_ORDER);foreach($Ce
as$s=>$B){$Ph[$B[1]]=array($B[2],$B[3]);$Qh[]="\n\t'".js_escape($B[1])."': [ $B[2], $B[3] ]";}$ni=0;$Qa=-1;$Yg=array();$Dg=array();$re=array();foreach(table_status('',true)as$Q=>$R){if(is_view($R))continue;$cg=0;$Yg[$Q]["fields"]=array();foreach(fields($Q)as$C=>$o){$cg+=1.25;$o["pos"]=$cg;$Yg[$Q]["fields"][$C]=$o;}$Yg[$Q]["pos"]=($Ph[$Q]?$Ph[$Q]:array($ni,0));foreach($b->foreignKeys($Q)as$X){if(!$X["db"]){$pe=$Qa;if($Ph[$Q][1]||$Ph[$X["table"]][1])$pe=min(floatval($Ph[$Q][1]),floatval($Ph[$X["table"]][1]))-1;else$Qa-=.1;while($re[(string)$pe])$pe-=.0001;$Yg[$Q]["references"][$X["table"]][(string)$pe]=array($X["source"],$X["target"]);$Dg[$X["table"]][$Q][(string)$pe]=$X["target"];$re[(string)$pe]=true;}}$ni=max($ni,$Yg[$Q]["pos"][0]+2.5+$cg);}echo'<div id="schema" style="height: ',$ni,'em;">
<script',nonce(),'>
qs(\'#schema\').onselectstart = function () { return false; };
var tablePos = {',implode(",",$Qh)."\n",'};
var em = qs(\'#schema\').offsetHeight / ',$ni,';
document.onmousemove = schemaMousemove;
document.onmouseup = partialArg(schemaMouseup, \'',js_escape(DB),'\');
</script>
';foreach($Yg
as$C=>$Q){echo"<div class='table' style='top: ".$Q["pos"][0]."em; left: ".$Q["pos"][1]."em;'>",'<a href="'.h(ME).'table='.urlencode($C).'"><b>'.h($C)."</b></a>",script("qsl('div').onmousedown = schemaMousedown;");foreach($Q["fields"]as$o){$X='<span'.type_class($o["type"]).' title="'.h($o["full_type"].($o["null"]?" NULL":'')).'">'.h($o["field"]).'</span>';echo"<br>".($o["primary"]?"<i>$X</i>":$X);}foreach((array)$Q["references"]as$Wh=>$Eg){foreach($Eg
as$pe=>$Ag){$qe=$pe-$Ph[$C][1];$s=0;foreach($Ag[0]as$th)echo"\n<div class='references' title='".h($Wh)."' id='refs$pe-".($s++)."' style='left: $qe"."em; top: ".$Q["fields"][$th]["pos"]."em; padding-top: .5em;'><div style='border-top: 1px solid Gray; width: ".(-$qe)."em;'></div></div>";}}foreach((array)$Dg[$C]as$Wh=>$Eg){foreach($Eg
as$pe=>$f){$qe=$pe-$Ph[$C][1];$s=0;foreach($f
as$Vh)echo"\n<div class='references' title='".h($Wh)."' id='refd$pe-".($s++)."' style='left: $qe"."em; top: ".$Q["fields"][$Vh]["pos"]."em; height: 1.25em; background: url(".h(preg_replace("~\\?.*~","",ME)."?file=arrow.gif) no-repeat right center;&version=4.7.0")."'><div style='height: .5em; border-bottom: 1px solid Gray; width: ".(-$qe)."em;'></div></div>";}}echo"\n</div>\n";}foreach($Yg
as$C=>$Q){foreach((array)$Q["references"]as$Wh=>$Eg){foreach($Eg
as$pe=>$Ag){$Re=$ni;$Ge=-10;foreach($Ag[0]as$y=>$th){$dg=$Q["pos"][0]+$Q["fields"][$th]["pos"];$eg=$Yg[$Wh]["pos"][0]+$Yg[$Wh]["fields"][$Ag[1][$y]]["pos"];$Re=min($Re,$dg,$eg);$Ge=max($Ge,$dg,$eg);}echo"<div class='references' id='refl$pe' style='left: $pe"."em; top: $Re"."em; padding: .5em 0;'><div style='border-right: 1px solid Gray; margin-top: 1px; height: ".($Ge-$Re)."em;'></div></div>\n";}}}echo'</div>
<p class="links"><a href="',h(ME."schema=".urlencode($ea)),'" id="schema-link">Permanent link</a>
';}elseif(isset($_GET["dump"])){$a=$_GET["dump"];if($_POST&&!$n){$Cb="";foreach(array("output","format","db_style","routines","events","table_style","auto_increment","triggers","data_style")as$y)$Cb.="&$y=".urlencode($_POST[$y]);cookiem("adminer_export",substr($Cb,1));$S=array_flip((array)$_POST["tables"])+array_flip((array)$_POST["data"]);$Ic=dump_headers((count($S)==1?key($S):DB),(DB==""||count($S)>1));$Zd=preg_match('~sql~',$_POST["format"]);if($Zd){echo"-- Adminer $ia ".$ec[DRIVER]." dump\n\n";if($x=="sql"){echo"SET NAMES utf8;
SET time_zone = '+00:00';
".($_POST["data_style"]?"SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
":"")."
";$g->query("SET time_zone = '+00:00';");}}$Gh=$_POST["db_style"];$k=array(DB);if(DB==""){$k=$_POST["databases"];if(is_string($k))$k=explode("\n",rtrim(str_replace("\r","",$k),"\n"));}foreach((array)$k
as$l){$b->dumpDatabase($l);if($g->select_db($l)){if($Zd&&preg_match('~CREATE~',$Gh)&&($i=$g->result("SHOW CREATE DATABASE ".idf_escape($l),1))){set_utf8mb4($i);if($Gh=="DROP+CREATE")echo"DROP DATABASE IF EXISTS ".idf_escape($l).";\n";echo"$i;\n";}if($Zd){if($Gh)echo
use_sql($l).";\n\n";$Hf="";if($_POST["routines"]){foreach(array("FUNCTION","PROCEDURE")as$Sg){foreach(get_rows("SHOW $Sg STATUS WHERE Db = ".q($l),null,"-- ")as$J){$i=remove_definer($g->result("SHOW CREATE $Sg ".idf_escape($J["Name"]),2));set_utf8mb4($i);$Hf.=($Gh!='DROP+CREATE'?"DROP $Sg IF EXISTS ".idf_escape($J["Name"]).";;\n":"")."$i;;\n\n";}}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$J){$i=remove_definer($g->result("SHOW CREATE EVENT ".idf_escape($J["Name"]),3));set_utf8mb4($i);$Hf.=($Gh!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($J["Name"]).";;\n":"")."$i;;\n\n";}}if($Hf)echo"DELIMITER ;;\n\n$Hf"."DELIMITER ;\n\n";}if($_POST["table_style"]||$_POST["data_style"]){$Xi=array();foreach(table_status('',true)as$C=>$R){$Q=(DB==""||in_array($C,(array)$_POST["tables"]));$Lb=(DB==""||in_array($C,(array)$_POST["data"]));if($Q||$Lb){if($Ic=="tar"){$ji=new
TmpFile;ob_start(array($ji,'write'),1e5);}$b->dumpTable($C,($Q?$_POST["table_style"]:""),(is_view($R)?2:0));if(is_view($R))$Xi[]=$C;elseif($Lb){$p=fields($C);$b->dumpData($C,$_POST["data_style"],"SELECT *".convert_fields($p,$p)." FROM ".table($C));}if($Zd&&$_POST["triggers"]&&$Q&&($yi=trigger_sql($C)))echo"\nDELIMITER ;;\n$yi\nDELIMITER ;\n";if($Ic=="tar"){ob_end_flush();tar_file((DB!=""?"":"$l/")."$C.csv",$ji);}elseif($Zd)echo"\n";}}foreach($Xi
as$Wi)$b->dumpTable($Wi,$_POST["table_style"],1);if($Ic=="tar")echo
pack("x512");}}}if($Zd)echo"-- ".$g->result("SELECT NOW()")."\n";exit;}page_header('Export',$n,($_GET["export"]!=""?array("table"=>$_GET["export"]):array()),h(DB));echo'
<form action="" method="post">
<table cellspacing="0" class="layout">
';$Pb=array('','USE','DROP+CREATE','CREATE');$Rh=array('','DROP+CREATE','CREATE');$Mb=array('','TRUNCATE+INSERT','INSERT');if($x=="sql")$Mb[]='INSERT+UPDATE';parse_str($_COOKIE["adminer_export"],$J);if(!$J)$J=array("output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"table_style"=>"DROP+CREATE","data_style"=>"INSERT");if(!isset($J["events"])){$J["routines"]=$J["events"]=($_GET["dump"]=="");$J["triggers"]=$J["table_style"];}echo"<tr><th>".'Output'."<td>".html_select("output",$b->dumpOutput(),$J["output"],0)."\n";echo"<tr><th>".'Format'."<td>".html_select("format",$b->dumpFormat(),$J["format"],0)."\n";echo($x=="sqlite"?"":"<tr><th>".'Database'."<td>".html_select('db_style',$Pb,$J["db_style"]).(support("routine")?checkbox("routines",1,$J["routines"],'Routines'):"").(support("event")?checkbox("events",1,$J["events"],'Events'):"")),"<tr><th>".'Tables'."<td>".html_select('table_style',$Rh,$J["table_style"]).checkbox("auto_increment",1,$J["auto_increment"],'Auto Increment').(support("trigger")?checkbox("triggers",1,$J["triggers"],'Triggers'):""),"<tr><th>".'Data'."<td>".html_select('data_style',$Mb,$J["data_style"]),'</table>
<p><input type="submit" value="Export">
<input type="hidden" name="token" value="',$mi,'">

<table cellspacing="0">
',script("qsl('table').onclick = dumpClick;");$hg=array();if(DB!=""){$eb=($a!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$eb>".'Tables'."</label>".script("qs('#check-tables').onclick = partial(formCheck, /^tables\\[/);",""),"<th style='text-align: right;'><label class='block'>".'Data'."<input type='checkbox' id='check-data'$eb></label>".script("qs('#check-data').onclick = partial(formCheck, /^data\\[/);",""),"</thead>\n";$Xi="";$Sh=tables_list();foreach($Sh
as$C=>$T){$gg=preg_replace('~_.*~','',$C);$eb=($a==""||$a==(substr($a,-1)=="%"?"$gg%":$C));$kg="<tr><td>".checkbox("tables[]",$C,$eb,$C,"","block");if($T!==null&&!preg_match('~table~i',$T))$Xi.="$kg\n";else
echo"$kg<td align='right'><label class='block'><span id='Rows-".h($C)."'></span>".checkbox("data[]",$C,$eb)."</label>\n";$hg[$gg]++;}echo$Xi;if($Sh)echo
script("ajaxSetHtml('".js_escape(ME)."script=db');");}else{echo"<thead><tr><th style='text-align: left;'>","<label class='block'><input type='checkbox' id='check-databases'".($a==""?" checked":"").">".'Database'."</label>",script("qs('#check-databases').onclick = partial(formCheck, /^databases\\[/);",""),"</thead>\n";$k=$b->databases();if($k){foreach($k
as$l){if(!information_schema($l)){$gg=preg_replace('~_.*~','',$l);echo"<tr><td>".checkbox("databases[]",$l,$a==""||$a=="$gg%",$l,"","block")."\n";$hg[$gg]++;}}}else
echo"<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";}echo'</table>
</form>
';$Wc=true;foreach($hg
as$y=>$X){if($y!=""&&$X>1){echo($Wc?"<p>":" ")."<a href='".h(ME)."dump=".urlencode("$y%")."'>".h($y)."</a>";$Wc=false;}}}elseif(isset($_GET["privileges"])){page_header('Privileges');echo'<p class="links"><a href="'.h(ME).'user=">'.'Create user'."</a>";$H=$g->query("SELECT User, Host FROM mysql.".(DB==""?"user":"db WHERE ".q(DB)." LIKE Db")." ORDER BY Host, User");$ld=$H;if(!$H)$H=$g->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");echo"<form action=''><p>\n";hidden_fields_get();echo"<input type='hidden' name='db' value='".h(DB)."'>\n",($ld?"":"<input type='hidden' name='grant' value=''>\n"),"<table cellspacing='0'>\n","<thead><tr><th>".'Username'."<th>".'Server'."<th></thead>\n";while($J=$H->fetch_assoc())echo'<tr'.odd().'><td>'.h($J["User"])."<td>".h($J["Host"]).'<td><a href="'.h(ME.'user='.urlencode($J["User"]).'&host='.urlencode($J["Host"])).'">'.'Edit'."</a>\n";if(!$ld||DB!="")echo"<tr".odd()."><td><input name='user' autocapitalize='off'><td><input name='host' value='localhost' autocapitalize='off'><td><input type='submit' value='".'Edit'."'>\n";echo"</table>\n","</form>\n";}elseif(isset($_GET["sql"])){if(!$n&&$_POST["export"]){dump_headers("sql");$b->dumpTable("","");$b->dumpData("","table",$_POST["query"]);exit;}restart_session();$zd=&get_session("queries");$yd=&$zd[DB];if(!$n&&$_POST["clear"]){$yd=array();redirectm(remove_from_uri("history"));}page_header((isset($_GET["import"])?'Import':'SQL command'),$n);if(!$n&&$_POST){$id=false;if(!isset($_GET["import"]))$G=$_POST["query"];elseif($_POST["webfile"]){$xh=$b->importServerPath();$id=@fopen((file_exists($xh)?$xh:"compress.zlib://$xh.gz"),"rb");$G=($id?fread($id,1e6):false);}else$G=get_file("sql_file",true);if(is_string($G)){if(function_exists('memory_get_usage'))@ini_set("memory_limit",max(ini_bytes("memory_limit"),2*strlen($G)+memory_get_usage()+8e6));if($G!=""&&strlen($G)<1e6){$sg=$G.(preg_match("~;[ \t\r\n]*\$~",$G)?"":";");if(!$yd||reset(end($yd))!=$sg){restart_session();$yd[]=array($sg,time());set_session("queries",$zd);stop_session();}}$uh="(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";$Vb=";";$D=0;$tc=true;$h=connect();if(is_object($h)&&DB!="")$h->select_db(DB);$sb=0;$yc=array();$Of='[\'"'.($x=="sql"?'`#':($x=="sqlite"?'`[':($x=="mssql"?'[':''))).']|/\*|-- |$'.($x=="pgsql"?'|\$[^$]*\$':'');$oi=microtime(true);parse_str($_COOKIE["adminer_export"],$xa);$kc=$b->dumpFormat();unset($kc["sql"]);while($G!=""){if(!$D&&preg_match("~^$uh*+DELIMITER\\s+(\\S+)~i",$G,$B)){$Vb=$B[1];$G=substr($G,strlen($B[0]));}else{preg_match('('.preg_quote($Vb)."\\s*|$Of)",$G,$B,PREG_OFFSET_CAPTURE,$D);list($gd,$cg)=$B[0];if(!$gd&&$id&&!feof($id))$G.=fread($id,1e5);else{if(!$gd&&rtrim($G)=="")break;$D=$cg+strlen($gd);if($gd&&rtrim($gd)!=$Vb){while(preg_match('('.($gd=='/*'?'\*/':($gd=='['?']':(preg_match('~^-- |^#~',$gd)?"\n":preg_quote($gd)."|\\\\."))).'|$)s',$G,$B,PREG_OFFSET_CAPTURE,$D)){$Wg=$B[0][0];if(!$Wg&&$id&&!feof($id))$G.=fread($id,1e5);else{$D=$B[0][1]+strlen($Wg);if($Wg[0]!="\\")break;}}}else{$tc=false;$sg=substr($G,0,$cg);$sb++;$kg="<pre id='sql-$sb'><code class='jush-$x'>".$b->sqlCommandQuery($sg)."</code></pre>\n";if($x=="sqlite"&&preg_match("~^$uh*+ATTACH\\b~i",$sg,$B)){echo$kg,"<p class='error'>".'ATTACH queries are not supported.'."\n";$yc[]=" <a href='#sql-$sb'>$sb</a>";if($_POST["error_stops"])break;}else{if(!$_POST["only_errors"]){echo$kg;ob_flush();flush();}$Ah=microtime(true);if($g->multi_query($sg)&&is_object($h)&&preg_match("~^$uh*+USE\\b~i",$sg))$h->query($sg);do{$H=$g->store_result();if($g->error){echo($_POST["only_errors"]?$kg:""),"<p class='error'>".'Error in query'.($g->errno?" ($g->errno)":"").": ".error()."\n";$yc[]=" <a href='#sql-$sb'>$sb</a>";if($_POST["error_stops"])break
2;}else{$ci=" <span class='time'>(".format_time($Ah).")</span>".(strlen($sg)<1000?" <a href='".h(ME)."sql=".urlencode(trim($sg))."'>".'Edit'."</a>":"");$za=$g->affected_rows;$aj=($_POST["only_errors"]?"":$m->warnings());$bj="warnings-$sb";if($aj)$ci.=", <a href='#$bj'>".'Warnings'."</a>".script("qsl('a').onclick = partial(toggle, '$bj');","");$Fc=null;$Gc="explain-$sb";if(is_object($H)){$z=$_POST["limit"];$Af=select($H,$h,array(),$z);if(!$_POST["only_errors"]){echo"<form action='' method='post'>\n";$ef=$H->num_rows;echo"<p>".($ef?($z&&$ef>$z?sprintf('%d / ',$z):"").lang(array('%d row','%d rows'),$ef):""),$ci;if($h&&preg_match("~^($uh|\\()*+SELECT\\b~i",$sg)&&($Fc=explain($h,$sg)))echo", <a href='#$Gc'>Explain</a>".script("qsl('a').onclick = partial(toggle, '$Gc');","");$t="export-$sb";echo", <a href='#$t'>".'Export'."</a>".script("qsl('a').onclick = partial(toggle, '$t');","")."<span id='$t' class='hidden'>: ".html_select("output",$b->dumpOutput(),$xa["output"])." ".html_select("format",$kc,$xa["format"])."<input type='hidden' name='query' value='".h($sg)."'>"." <input type='submit' name='export' value='".'Export'."'><input type='hidden' name='token' value='$mi'></span>\n"."</form>\n";}}else{if(preg_match("~^$uh*+(CREATE|DROP|ALTER)$uh++(DATABASE|SCHEMA)\\b~i",$sg)){restart_session();set_session("dbs",null);stop_session();}if(!$_POST["only_errors"])echo"<p class='message' title='".h($g->info)."'>".lang(array('Query executed OK, %d row affected.','Query executed OK, %d rows affected.'),$za)."$ci\n";}echo($aj?"<div id='$bj' class='hidden'>\n$aj</div>\n":"");if($Fc){echo"<div id='$Gc' class='hidden'>\n";select($Fc,$h,$Af);echo"</div>\n";}}$Ah=microtime(true);}while($g->next_result());}$G=substr($G,$D);$D=0;}}}}if($tc)echo"<p class='message'>".'No commands to execute.'."\n";elseif($_POST["only_errors"]){echo"<p class='message'>".lang(array('%d query executed OK.','%d queries executed OK.'),$sb-count($yc))," <span class='time'>(".format_time($oi).")</span>\n";}elseif($yc&&$sb>1)echo"<p class='error'>".'Error in query'.": ".implode("",$yc)."\n";}else
echo"<p class='error'>".upload_error($G)."\n";}echo'
<form action="" method="post" enctype="multipart/form-data" id="form">
';$Cc="<input type='submit' value='".'Execute'."' title='Ctrl+Enter'>";if(!isset($_GET["import"])){$sg=$_GET["sql"];if($_POST)$sg=$_POST["query"];elseif($_GET["history"]=="all")$sg=$yd;elseif($_GET["history"]!="")$sg=$yd[$_GET["history"]][0];echo"<p>";textarea("query",$sg,20);echo
script(($_POST?"":"qs('textarea').focus();\n")."qs('#form').onsubmit = partial(sqlSubmit, qs('#form'), '".remove_from_uri("sql|limit|error_stops|only_errors")."');"),"<p>$Cc\n",'Limit rows'.": <input type='number' name='limit' class='size' value='".h($_POST?$_POST["limit"]:$_GET["limit"])."'>\n";}else{echo"<fieldset><legend>".'File upload'."</legend><div>";$rd=(extension_loaded("zlib")?"[.gz]":"");echo(ini_bool("file_uploads")?"SQL$rd (&lt; ".ini_get("upload_max_filesize")."B): <input type='file' name='sql_file[]' multiple>\n$Cc":'File uploads are disabled.'),"</div></fieldset>\n";$Gd=$b->importServerPath();if($Gd){echo"<fieldset><legend>".'From server'."</legend><div>",sprintf('Webserver file %s',"<code>".h($Gd)."$rd</code>"),' <input type="submit" name="webfile" value="'.'Run file'.'">',"</div></fieldset>\n";}echo"<p>";}echo
checkbox("error_stops",1,($_POST?$_POST["error_stops"]:isset($_GET["import"])),'Stop on error')."\n",checkbox("only_errors",1,($_POST?$_POST["only_errors"]:isset($_GET["import"])),'Show only errors')."\n","<input type='hidden' name='token' value='$mi'>\n";if(!isset($_GET["import"])&&$yd){print_fieldset("history",'History',$_GET["history"]!="");for($X=end($yd);$X;$X=prev($yd)){$y=key($yd);list($sg,$ci,$oc)=$X;echo'<a href="'.h(ME."sql=&history=$y").'">'.'Edit'."</a>"." <span class='time' title='".@date('Y-m-d',$ci)."'>".@date("H:i:s",$ci)."</span>"." <code class='jush-$x'>".shorten_utf8(ltrim(str_replace("\n"," ",str_replace("\r","",preg_replace('~^(#|-- ).*~m','',$sg)))),80,"</code>").($oc?" <span class='time'>($oc)</span>":"")."<br>\n";}echo"<input type='submit' name='clear' value='".'Clear'."'>\n","<a href='".h(ME."sql=&history=all")."'>".'Edit all'."</a>\n","</div></fieldset>\n";}echo'</form>
';}elseif(isset($_GET["edit"])){$a=$_GET["edit"];$p=fields($a);$Z=(isset($_GET["select"])?($_POST["check"]&&count($_POST["check"])==1?where_check($_POST["check"][0],$p):""):where($_GET,$p));$Hi=(isset($_GET["select"])?$_POST["edit"]:$Z);foreach($p
as$C=>$o){if(!isset($o["privileges"][$Hi?"update":"insert"])||$b->fieldName($o)=="")unset($p[$C]);}if($_POST&&!$n&&!isset($_GET["select"])){$A=$_POST["referer"];if($_POST["insert"])$A=($Hi?null:$_SERVER["REQUEST_URI"]);elseif(!preg_match('~^.+&select=.+$~',$A))$A=ME."select=".urlencode($a);$w=indexes($a);$Ci=unique_array($_GET["where"],$w);$vg="\nWHERE $Z";if(isset($_POST["delete"]))queries_redirect($A,'Item has been deleted.',$m->delete($a,$vg,!$Ci));else{$O=array();foreach($p
as$C=>$o){$X=process_input($o);if($X!==false&&$X!==null)$O[idf_escape($C)]=$X;}if($Hi){if(!$O)redirectm($A);queries_redirect($A,'Item has been updated.',$m->update($a,$O,$vg,!$Ci));if(is_ajax()){page_headers();page_messages($n);exit;}}else{$H=$m->insert($a,$O);$oe=($H?last_id():0);queries_redirect($A,sprintf('Item%s has been inserted.',($oe?" $oe":"")),$H);}}}$J=null;if($_POST["save"])$J=(array)$_POST["fields"];elseif($Z){$L=array();foreach($p
as$C=>$o){if(isset($o["privileges"]["select"])){$Ga=convert_field($o);if($_POST["clone"]&&$o["auto_increment"])$Ga="''";if($x=="sql"&&preg_match("~enum|set~",$o["type"]))$Ga="1*".idf_escape($C);$L[]=($Ga?"$Ga AS ":"").idf_escape($C);}}$J=array();if(!support("table"))$L=array("*");if($L){$H=$m->select($a,$L,array($Z),$L,array(),(isset($_GET["select"])?2:1));if(!$H)$n=error();else{$J=$H->fetch_assoc();if(!$J)$J=false;}if(isset($_GET["select"])&&(!$J||$H->fetch_assoc()))$J=null;}}if(!support("table")&&!$p){if(!$Z){$H=$m->select($a,array("*"),$Z,array("*"));$J=($H?$H->fetch_assoc():false);if(!$J)$J=array($m->primary=>"");}if($J){foreach($J
as$y=>$X){if(!$Z)$J[$y]=null;$p[$y]=array("field"=>$y,"null"=>($y!=$m->primary),"auto_increment"=>($y==$m->primary));}}}edit_form($a,$p,$J,$Hi);}elseif(isset($_GET["create"])){$a=$_GET["create"];$Qf=array();foreach(array('HASH','LINEAR HASH','KEY','LINEAR KEY','RANGE','LIST')as$y)$Qf[$y]=$y;$Cg=referencable_primary($a);$ed=array();foreach($Cg
as$Nh=>$o)$ed[str_replace("`","``",$Nh)."`".str_replace("`","``",$o["field"])]=$Nh;$Df=array();$R=array();if($a!=""){$Df=fields($a);$R=table_status($a);if(!$R)$n='No tables.';}$J=$_POST;$J["fields"]=(array)$J["fields"];if($J["auto_increment_col"])$J["fields"][$J["auto_increment_col"]]["auto_increment"]=true;if($_POST&&!process_fields($J["fields"])&&!$n){if($_POST["drop"])queries_redirect(substr(ME,0,-1),'Table has been dropped.',drop_tables(array($a)));else{$p=array();$Da=array();$Mi=false;$cd=array();$Cf=reset($Df);$Aa=" FIRST";foreach($J["fields"]as$y=>$o){$q=$ed[$o["type"]];$zi=($q!==null?$Cg[$q]:$o);if($o["field"]!=""){if(!$o["has_default"])$o["default"]=null;if($y==$J["auto_increment_col"])$o["auto_increment"]=true;$pg=process_field($o,$zi);$Da[]=array($o["orig"],$pg,$Aa);if($pg!=process_field($Cf,$Cf)){$p[]=array($o["orig"],$pg,$Aa);if($o["orig"]!=""||$Aa)$Mi=true;}if($q!==null)$cd[idf_escape($o["field"])]=($a!=""&&$x!="sqlite"?"ADD":" ").format_foreign_key(array('table'=>$ed[$o["type"]],'source'=>array($o["field"]),'target'=>array($zi["field"]),'on_delete'=>$o["on_delete"],));$Aa=" AFTER ".idf_escape($o["field"]);}elseif($o["orig"]!=""){$Mi=true;$p[]=array($o["orig"]);}if($o["orig"]!=""){$Cf=next($Df);if(!$Cf)$Aa="";}}$Sf="";if($Qf[$J["partition_by"]]){$Tf=array();if($J["partition_by"]=='RANGE'||$J["partition_by"]=='LIST'){foreach(array_filter($J["partition_names"])as$y=>$X){$Y=$J["partition_values"][$y];$Tf[]="\n  PARTITION ".idf_escape($X)." VALUES ".($J["partition_by"]=='RANGE'?"LESS THAN":"IN").($Y!=""?" ($Y)":" MAXVALUE");}}$Sf.="\nPARTITION BY $J[partition_by]($J[partition])".($Tf?" (".implode(",",$Tf)."\n)":($J["partitions"]?" PARTITIONS ".(+$J["partitions"]):""));}elseif(support("partitioning")&&preg_match("~partitioned~",$R["Create_options"]))$Sf.="\nREMOVE PARTITIONING";$Ke='Table has been altered.';if($a==""){cookiem("adminer_engine",$J["Engine"]);$Ke='Table has been created.';}$C=trim($J["name"]);queries_redirect(ME.(support("table")?"table=":"select=").urlencode($C),$Ke,alter_table($a,$C,($x=="sqlite"&&($Mi||$cd)?$Da:$p),$cd,($J["Comment"]!=$R["Comment"]?$J["Comment"]:null),($J["Engine"]&&$J["Engine"]!=$R["Engine"]?$J["Engine"]:""),($J["Collation"]&&$J["Collation"]!=$R["Collation"]?$J["Collation"]:""),($J["Auto_increment"]!=""?number($J["Auto_increment"]):""),$Sf));}}page_header(($a!=""?'Alter table':'Create table'),$n,array("table"=>$a),h($a));if(!$_POST){$J=array("Engine"=>$_COOKIE["adminer_engine"],"fields"=>array(array("field"=>"","type"=>(isset($U["int"])?"int":(isset($U["integer"])?"integer":"")),"on_update"=>"")),"partition_names"=>array(""),);if($a!=""){$J=$R;$J["name"]=$a;$J["fields"]=array();if(!$_GET["auto_increment"])$J["Auto_increment"]="";foreach($Df
as$o){$o["has_default"]=isset($o["default"]);$J["fields"][]=$o;}if(support("partitioning")){$jd="FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = ".q(DB)." AND TABLE_NAME = ".q($a);$H=$g->query("SELECT PARTITION_METHOD, PARTITION_ORDINAL_POSITION, PARTITION_EXPRESSION $jd ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1");list($J["partition_by"],$J["partitions"],$J["partition"])=$H->fetch_row();$Tf=get_key_vals("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $jd AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION");$Tf[""]="";$J["partition_names"]=array_keys($Tf);$J["partition_values"]=array_values($Tf);}}}$ob=collations();$vc=engines();foreach($vc
as$uc){if(!strcasecmp($uc,$J["Engine"])){$J["Engine"]=$uc;break;}}echo'
<form action="" method="post" id="form">
<p>
';if(support("columns")||$a==""){echo'Table name: <input name="name" data-maxlength="64" value="',h($J["name"]),'" autocapitalize="off">
';if($a==""&&!$_POST)echo
script("focus(qs('#form')['name']);");echo($vc?"<select name='Engine'>".optionlist(array(""=>"(".'engine'.")")+$vc,$J["Engine"])."</select>".on_help("getTarget(event).value",1).script("qsl('select').onchange = helpClose;"):""),' ',($ob&&!preg_match("~sqlite|mssql~",$x)?html_select("Collation",array(""=>"(".'collation'.")")+$ob,$J["Collation"]):""),' <input type="submit" value="Save">
';}echo'
';if(support("columns")){echo'<div class="scrollable">
<table cellspacing="0" id="edit-fields" class="nowrap">
';$ub=($_POST?$_POST["comments"]:$J["Comment"]!="");if(!$_POST&&!$ub){foreach($J["fields"]as$o){if($o["comment"]!=""){$ub=true;break;}}}edit_fields($J["fields"],$ob,"TABLE",$ed,$ub);echo'</table>
</div>
<p>
Auto Increment: <input type="number" name="Auto_increment" size="6" value="',h($J["Auto_increment"]),'">
',checkbox("defaults",1,!$_POST||$_POST["defaults"],'Default values',"columnShow(this.checked, 5)","jsonly"),($_POST?"":script("editingHideDefaults();")),(support("comment")?"<label><input type='checkbox' name='comments' value='1' class='jsonly'".($ub?" checked":"").">".'Comment'."</label>".script("qsl('input').onclick = partial(editingCommentsClick, true);").' <input name="Comment" value="'.h($J["Comment"]).'" data-maxlength="'.(min_version(5.5)?2048:60).'"'.($ub?'':' class="hidden"').'>':''),'<p>
<input type="submit" value="Save">
';}echo'
';if($a!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$a));}if(support("partitioning")){$Rf=preg_match('~RANGE|LIST~',$J["partition_by"]);print_fieldset("partition",'Partition by',$J["partition_by"]);echo'<p>
',"<select name='partition_by'>".optionlist(array(""=>"")+$Qf,$J["partition_by"])."</select>".on_help("getTarget(event).value.replace(/./, 'PARTITION BY \$&')",1).script("qsl('select').onchange = partitionByChange;"),'(<input name="partition" value="',h($J["partition"]),'">)
Partitions: <input type="number" name="partitions" class="size',($Rf||!$J["partition_by"]?" hidden":""),'" value="',h($J["partitions"]),'">
<table cellspacing="0" id="partition-table"',($Rf?"":" class='hidden'"),'>
<thead><tr><th>Partition name<th>Values</thead>
';foreach($J["partition_names"]as$y=>$X){echo'<tr>','<td><input name="partition_names[]" value="'.h($X).'" autocapitalize="off">',($y==count($J["partition_names"])-1?script("qsl('input').oninput = partitionNameChange;"):''),'<td><input name="partition_values[]" value="'.h($J["partition_values"][$y]).'">';}echo'</table>
</div></fieldset>
';}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
',script("qs('#form')['defaults'].onclick();".(support("comment")?" editingCommentsClick.call(qs('#form')['comments']);":""));}elseif(isset($_GET["indexes"])){$a=$_GET["indexes"];$Jd=array("PRIMARY","UNIQUE","INDEX");$R=table_status($a,true);if(preg_match('~MyISAM|M?aria'.(min_version(5.6,'10.0.5')?'|InnoDB':'').'~i',$R["Engine"]))$Jd[]="FULLTEXT";if(preg_match('~MyISAM|M?aria'.(min_version(5.7,'10.2.2')?'|InnoDB':'').'~i',$R["Engine"]))$Jd[]="SPATIAL";$w=indexes($a);$ig=array();if($x=="mongo"){$ig=$w["_id_"];unset($Jd[0]);unset($w["_id_"]);}$J=$_POST;if($_POST&&!$n&&!$_POST["add"]&&!$_POST["drop_col"]){$c=array();foreach($J["indexes"]as$v){$C=$v["name"];if(in_array($v["type"],$Jd)){$f=array();$ue=array();$Xb=array();$O=array();ksort($v["columns"]);foreach($v["columns"]as$y=>$e){if($e!=""){$te=$v["lengths"][$y];$Wb=$v["descs"][$y];$O[]=idf_escape($e).($te?"(".(+$te).")":"").($Wb?" DESC":"");$f[]=$e;$ue[]=($te?$te:null);$Xb[]=$Wb;}}if($f){$Dc=$w[$C];if($Dc){ksort($Dc["columns"]);ksort($Dc["lengths"]);ksort($Dc["descs"]);if($v["type"]==$Dc["type"]&&array_values($Dc["columns"])===$f&&(!$Dc["lengths"]||array_values($Dc["lengths"])===$ue)&&array_values($Dc["descs"])===$Xb){unset($w[$C]);continue;}}$c[]=array($v["type"],$C,$O);}}}foreach($w
as$C=>$Dc)$c[]=array($Dc["type"],$C,"DROP");if(!$c)redirectm(ME."table=".urlencode($a));queries_redirect(ME."table=".urlencode($a),'Indexes have been altered.',alter_indexes($a,$c));}page_header('Indexes',$n,array("table"=>$a),h($a));$p=array_keys(fields($a));if($_POST["add"]){foreach($J["indexes"]as$y=>$v){if($v["columns"][count($v["columns"])]!="")$J["indexes"][$y]["columns"][]="";}$v=end($J["indexes"]);if($v["type"]||array_filter($v["columns"],'strlen'))$J["indexes"][]=array("columns"=>array(1=>""));}if(!$J){foreach($w
as$y=>$v){$w[$y]["name"]=$y;$w[$y]["columns"][]="";}$w[]=array("columns"=>array(1=>""));$J["indexes"]=$w;}echo'
<form action="" method="post">
<div class="scrollable">
<table cellspacing="0" class="nowrap">
<thead><tr>
<th id="label-type">Index Type
<th><input type="submit" class="wayoff">Column (length)
<th id="label-name">Name
<th><noscript>',"<input type='image' class='icon' name='add[0]' src='".h(preg_replace("~\\?.*~","",ME)."?file=plus.gif&version=4.7.0")."' alt='+' title='".'Add next'."'>",'</noscript>
</thead>
';if($ig){echo"<tr><td>PRIMARY<td>";foreach($ig["columns"]as$y=>$e){echo
select_input(" disabled",$p,$e),"<label><input disabled type='checkbox'>".'descending'."</label> ";}echo"<td><td>\n";}$ce=1;foreach($J["indexes"]as$v){if(!$_POST["drop_col"]||$ce!=key($_POST["drop_col"])){echo"<tr><td>".html_select("indexes[$ce][type]",array(-1=>"")+$Jd,$v["type"],($ce==count($J["indexes"])?"indexesAddRow.call(this);":1),"label-type"),"<td>";ksort($v["columns"]);$s=1;foreach($v["columns"]as$y=>$e){echo"<span>".select_input(" name='indexes[$ce][columns][$s]' title='".'Column'."'",($p?array_combine($p,$p):$p),$e,"partial(".($s==count($v["columns"])?"indexesAddColumn":"indexesChangeColumn").", '".js_escape($x=="sql"?"":$_GET["indexes"]."_")."')"),($x=="sql"||$x=="mssql"?"<input type='number' name='indexes[$ce][lengths][$s]' class='size' value='".h($v["lengths"][$y])."' title='".'Length'."'>":""),(support("descidx")?checkbox("indexes[$ce][descs][$s]",1,$v["descs"][$y],'descending'):"")," </span>";$s++;}echo"<td><input name='indexes[$ce][name]' value='".h($v["name"])."' autocapitalize='off' aria-labelledby='label-name'>\n","<td><input type='image' class='icon' name='drop_col[$ce]' src='".h(preg_replace("~\\?.*~","",ME)."?file=cross.gif&version=4.7.0")."' alt='x' title='".'Remove'."'>".script("qsl('input').onclick = partial(editingRemoveRow, 'indexes\$1[type]');");}$ce++;}echo'</table>
</div>
<p>
<input type="submit" value="Save">
<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["database"])){$J=$_POST;if($_POST&&!$n&&!isset($_POST["add_x"])){$C=trim($J["name"]);if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),'Database has been dropped.',drop_databases(array(DB)));}elseif(DB!==$C){if(DB!=""){$_GET["db"]=$C;queries_redirect(preg_replace('~\bdb=[^&]*&~','',ME)."db=".urlencode($C),'Database has been renamed.',rename_database($C,$J["collation"]));}else{$k=explode("\n",str_replace("\r","",$C));$Hh=true;$ne="";foreach($k
as$l){if(count($k)==1||$l!=""){if(!create_database($l,$J["collation"]))$Hh=false;$ne=$l;}}restart_session();set_session("dbs",null);queries_redirect(ME."db=".urlencode($ne),'Database has been created.',$Hh);}}else{if(!$J["collation"])redirectm(substr(ME,0,-1));query_redirect("ALTER DATABASE ".idf_escape($C).(preg_match('~^[a-z0-9_]+$~i',$J["collation"])?" COLLATE $J[collation]":""),substr(ME,0,-1),'Database has been altered.');}}page_header(DB!=""?'Alter database':'Create database',$n,array(),h(DB));$ob=collations();$C=DB;if($_POST)$C=$J["name"];elseif(DB!="")$J["collation"]=db_collation(DB,$ob);elseif($x=="sql"){foreach(get_vals("SHOW GRANTS")as$ld){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\.\*)?~',$ld,$B)&&$B[1]){$C=stripcslashes(idf_unescape("`$B[2]`"));break;}}}echo'
<form action="" method="post">
<p>
',($_POST["add_x"]||strpos($C,"\n")?'<textarea id="name" name="name" rows="10" cols="40">'.h($C).'</textarea><br>':'<input name="name" id="name" value="'.h($C).'" data-maxlength="64" autocapitalize="off">')."\n".($ob?html_select("collation",array(""=>"(".'collation'.")")+$ob,$J["collation"]).doc_link(array('sql'=>"charset-charsets.html",'mariadb'=>"supported-character-sets-and-collations/",'mssql'=>"ms187963.aspx",)):""),script("focus(qs('#name'));"),'<input type="submit" value="Save">
';if(DB!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',DB))."\n";elseif(!$_POST["add_x"]&&$_GET["db"]=="")echo"<input type='image' class='icon' name='add' src='".h(preg_replace("~\\?.*~","",ME)."?file=plus.gif&version=4.7.0")."' alt='+' title='".'Add next'."'>\n";echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["scheme"])){$J=$_POST;if($_POST&&!$n){$_=preg_replace('~ns=[^&]*&~','',ME)."ns=";if($_POST["drop"])query_redirect("DROP SCHEMA ".idf_escape($_GET["ns"]),$_,'Schema has been dropped.');else{$C=trim($J["name"]);$_.=urlencode($C);if($_GET["ns"]=="")query_redirect("CREATE SCHEMA ".idf_escape($C),$_,'Schema has been created.');elseif($_GET["ns"]!=$C)query_redirect("ALTER SCHEMA ".idf_escape($_GET["ns"])." RENAME TO ".idf_escape($C),$_,'Schema has been altered.');else
redirectm($_);}}page_header($_GET["ns"]!=""?'Alter schema':'Create schema',$n);if(!$J)$J["name"]=$_GET["ns"];echo'
<form action="" method="post">
<p><input name="name" id="name" value="',h($J["name"]),'" autocapitalize="off">
',script("focus(qs('#name'));"),'<input type="submit" value="Save">
';if($_GET["ns"]!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$_GET["ns"]))."\n";echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["call"])){$da=($_GET["name"]?$_GET["name"]:$_GET["call"]);page_header('Call'.": ".h($da),$n);$Sg=routine($_GET["call"],(isset($_GET["callf"])?"FUNCTION":"PROCEDURE"));$Hd=array();$Hf=array();foreach($Sg["fields"]as$s=>$o){if(substr($o["inout"],-3)=="OUT")$Hf[$s]="@".idf_escape($o["field"])." AS ".idf_escape($o["field"]);if(!$o["inout"]||substr($o["inout"],0,2)=="IN")$Hd[]=$s;}if(!$n&&$_POST){$Za=array();foreach($Sg["fields"]as$y=>$o){if(in_array($y,$Hd)){$X=process_input($o);if($X===false)$X="''";if(isset($Hf[$y]))$g->query("SET @".idf_escape($o["field"])." = $X");}$Za[]=(isset($Hf[$y])?"@".idf_escape($o["field"]):$X);}$G=(isset($_GET["callf"])?"SELECT":"CALL")." ".table($da)."(".implode(", ",$Za).")";$Ah=microtime(true);$H=$g->multi_query($G);$za=$g->affected_rows;echo$b->selectQuery($G,$Ah,!$H);if(!$H)echo"<p class='error'>".error()."\n";else{$h=connect();if(is_object($h))$h->select_db(DB);do{$H=$g->store_result();if(is_object($H))select($H,$h);else
echo"<p class='message'>".lang(array('Routine has been called, %d row affected.','Routine has been called, %d rows affected.'),$za)."\n";}while($g->next_result());if($Hf)select($g->query("SELECT ".implode(", ",$Hf)));}}echo'
<form action="" method="post">
';if($Hd){echo"<table cellspacing='0' class='layout'>\n";foreach($Hd
as$y){$o=$Sg["fields"][$y];$C=$o["field"];echo"<tr><th>".$b->fieldName($o);$Y=$_POST["fields"][$C];if($Y!=""){if($o["type"]=="enum")$Y=+$Y;if($o["type"]=="set")$Y=array_sum($Y);}inputm($o,$Y,(string)$_POST["function"][$C]);echo"\n";}echo"</table>\n";}echo'<p>
<input type="submit" value="Call">
<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["foreign"])){$a=$_GET["foreign"];$C=$_GET["name"];$J=$_POST;if($_POST&&!$n&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){$Ke=($_POST["drop"]?'Foreign key has been dropped.':($C!=""?'Foreign key has been altered.':'Foreign key has been created.'));$A=ME."table=".urlencode($a);if(!$_POST["drop"]){$J["source"]=array_filter($J["source"],'strlen');ksort($J["source"]);$Vh=array();foreach($J["source"]as$y=>$X)$Vh[$y]=$J["target"][$y];$J["target"]=$Vh;}if($x=="sqlite")queries_redirect($A,$Ke,recreate_table($a,$a,array(),array(),array(" $C"=>($_POST["drop"]?"":" ".format_foreign_key($J)))));else{$c="ALTER TABLE ".table($a);$fc="\nDROP ".($x=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($C);if($_POST["drop"])query_redirect($c.$fc,$A,$Ke);else{query_redirect($c.($C!=""?"$fc,":"")."\nADD".format_foreign_key($J),$A,$Ke);$n='Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.'."<br>$n";}}}page_header('Foreign key',$n,array("table"=>$a),h($a));if($_POST){ksort($J["source"]);if($_POST["add"])$J["source"][]="";elseif($_POST["change"]||$_POST["change-js"])$J["target"]=array();}elseif($C!=""){$ed=foreign_keys($a);$J=$ed[$C];$J["source"][]="";}else{$J["table"]=$a;$J["source"]=array("");}$th=array_keys(fields($a));$Vh=($a===$J["table"]?$th:array_keys(fields($J["table"])));$Bg=array_keys(array_filter(table_status('',true),'fk_support'));echo'
<form action="" method="post">
<p>
';if($J["db"]==""&&$J["ns"]==""){echo'Target table:
',html_select("table",$Bg,$J["table"],"this.form['change-js'].value = '1'; this.form.submit();"),'<input type="hidden" name="change-js" value="">
<noscript><p><input type="submit" name="change" value="Change"></noscript>
<table cellspacing="0">
<thead><tr><th id="label-source">Source<th id="label-target">Target</thead>
';$ce=0;foreach($J["source"]as$y=>$X){echo"<tr>","<td>".html_select("source[".(+$y)."]",array(-1=>"")+$th,$X,($ce==count($J["source"])-1?"foreignAddRow.call(this);":1),"label-source"),"<td>".html_select("target[".(+$y)."]",$Vh,$J["target"][$y],1,"label-target");$ce++;}echo'</table>
<p>
ON DELETE: ',html_select("on_delete",array(-1=>"")+explode("|",$of),$J["on_delete"]),' ON UPDATE: ',html_select("on_update",array(-1=>"")+explode("|",$of),$J["on_update"]),doc_link(array('sql'=>"innodb-foreign-key-constraints.html",'mariadb'=>"foreign-keys/",'pgsql'=>"sql-createtable.html#SQL-CREATETABLE-REFERENCES",'mssql'=>"ms174979.aspx",'oracle'=>"clauses002.htm#sthref2903",)),'<p>
<input type="submit" value="Save">
<noscript><p><input type="submit" name="add" value="Add column"></noscript>
';}if($C!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$C));}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["view"])){$a=$_GET["view"];$J=$_POST;$Ef="VIEW";if($x=="pgsql"&&$a!=""){$Ch=table_status($a);$Ef=strtoupper($Ch["Engine"]);}if($_POST&&!$n){$C=trim($J["name"]);$Ga=" AS\n$J[select]";$A=ME."table=".urlencode($C);$Ke='View has been altered.';$T=($_POST["materialized"]?"MATERIALIZED VIEW":"VIEW");if(!$_POST["drop"]&&$a==$C&&$x!="sqlite"&&$T=="VIEW"&&$Ef=="VIEW")query_redirect(($x=="mssql"?"ALTER":"CREATE OR REPLACE")." VIEW ".table($C).$Ga,$A,$Ke);else{$Xh=$C."_adminer_".uniqid();drop_create("DROP $Ef ".table($a),"CREATE $T ".table($C).$Ga,"DROP $T ".table($C),"CREATE $T ".table($Xh).$Ga,"DROP $T ".table($Xh),($_POST["drop"]?substr(ME,0,-1):$A),'View has been dropped.',$Ke,'View has been created.',$a,$C);}}if(!$_POST&&$a!=""){$J=viewm($a);$J["name"]=$a;$J["materialized"]=($Ef!="VIEW");if(!$n)$n=error();}page_header(($a!=""?'Alter view':'Create view'),$n,array("table"=>$a),h($a));echo'
<form action="" method="post">
<p>Name: <input name="name" value="',h($J["name"]),'" data-maxlength="64" autocapitalize="off">
',(support("materializedview")?" ".checkbox("materialized",1,$J["materialized"],'Materialized view'):""),'<p>';textarea("select",$J["select"]);echo'<p>
<input type="submit" value="Save">
';if($a!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$a));}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["event"])){$aa=$_GET["event"];$Ud=array("YEAR","QUARTER","MONTH","DAY","HOUR","MINUTE","WEEK","SECOND","YEAR_MONTH","DAY_HOUR","DAY_MINUTE","DAY_SECOND","HOUR_MINUTE","HOUR_SECOND","MINUTE_SECOND");$Dh=array("ENABLED"=>"ENABLE","DISABLED"=>"DISABLE","SLAVESIDE_DISABLED"=>"DISABLE ON SLAVE");$J=$_POST;if($_POST&&!$n){if($_POST["drop"])query_redirect("DROP EVENT ".idf_escape($aa),substr(ME,0,-1),'Event has been dropped.');elseif(in_array($J["INTERVAL_FIELD"],$Ud)&&isset($Dh[$J["STATUS"]])){$Xg="\nON SCHEDULE ".($J["INTERVAL_VALUE"]?"EVERY ".q($J["INTERVAL_VALUE"])." $J[INTERVAL_FIELD]".($J["STARTS"]?" STARTS ".q($J["STARTS"]):"").($J["ENDS"]?" ENDS ".q($J["ENDS"]):""):"AT ".q($J["STARTS"]))." ON COMPLETION".($J["ON_COMPLETION"]?"":" NOT")." PRESERVE";queries_redirect(substr(ME,0,-1),($aa!=""?'Event has been altered.':'Event has been created.'),queries(($aa!=""?"ALTER EVENT ".idf_escape($aa).$Xg.($aa!=$J["EVENT_NAME"]?"\nRENAME TO ".idf_escape($J["EVENT_NAME"]):""):"CREATE EVENT ".idf_escape($J["EVENT_NAME"]).$Xg)."\n".$Dh[$J["STATUS"]]." COMMENT ".q($J["EVENT_COMMENT"]).rtrim(" DO\n$J[EVENT_DEFINITION]",";").";"));}}page_header(($aa!=""?'Alter event'.": ".h($aa):'Create event'),$n);if(!$J&&$aa!=""){$K=get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ".q(DB)." AND EVENT_NAME = ".q($aa));$J=reset($K);}echo'
<form action="" method="post">
<table cellspacing="0" class="layout">
<tr><th>Name<td><input name="EVENT_NAME" value="',h($J["EVENT_NAME"]),'" data-maxlength="64" autocapitalize="off">
<tr><th title="datetime">Start<td><input name="STARTS" value="',h("$J[EXECUTE_AT]$J[STARTS]"),'">
<tr><th title="datetime">End<td><input name="ENDS" value="',h($J["ENDS"]),'">
<tr><th>Every<td><input type="number" name="INTERVAL_VALUE" value="',h($J["INTERVAL_VALUE"]),'" class="size"> ',html_select("INTERVAL_FIELD",$Ud,$J["INTERVAL_FIELD"]),'<tr><th>Status<td>',html_select("STATUS",$Dh,$J["STATUS"]),'<tr><th>Comment<td><input name="EVENT_COMMENT" value="',h($J["EVENT_COMMENT"]),'" data-maxlength="64">
<tr><th><td>',checkbox("ON_COMPLETION","PRESERVE",$J["ON_COMPLETION"]=="PRESERVE",'On completion preserve'),'</table>
<p>';textarea("EVENT_DEFINITION",$J["EVENT_DEFINITION"]);echo'<p>
<input type="submit" value="Save">
';if($aa!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$aa));}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["procedure"])){$da=($_GET["name"]?$_GET["name"]:$_GET["procedure"]);$Sg=(isset($_GET["function"])?"FUNCTION":"PROCEDURE");$J=$_POST;$J["fields"]=(array)$J["fields"];if($_POST&&!process_fields($J["fields"])&&!$n){$Bf=routine($_GET["procedure"],$Sg);$Xh="$J[name]_adminer_".uniqid();drop_create("DROP $Sg ".routine_id($da,$Bf),create_routine($Sg,$J),"DROP $Sg ".routine_id($J["name"],$J),create_routine($Sg,array("name"=>$Xh)+$J),"DROP $Sg ".routine_id($Xh,$J),substr(ME,0,-1),'Routine has been dropped.','Routine has been altered.','Routine has been created.',$da,$J["name"]);}page_header(($da!=""?(isset($_GET["function"])?'Alter function':'Alter procedure').": ".h($da):(isset($_GET["function"])?'Create function':'Create procedure')),$n);if(!$_POST&&$da!=""){$J=routine($_GET["procedure"],$Sg);$J["name"]=$da;}$ob=get_vals("SHOW CHARACTER SET");sort($ob);$Tg=routine_languages();echo'
<form action="" method="post" id="form">
<p>Name: <input name="name" value="',h($J["name"]),'" data-maxlength="64" autocapitalize="off">
',($Tg?'Language'.": ".html_select("language",$Tg,$J["language"])."\n":""),'<input type="submit" value="Save">
<div class="scrollable">
<table cellspacing="0" class="nowrap">
';edit_fields($J["fields"],$ob,$Sg);if(isset($_GET["function"])){echo"<tr><td>".'Return type';edit_type("returns",$J["returns"],$ob,array(),($x=="pgsql"?array("void","trigger"):array()));}echo'</table>
</div>
<p>';textarea("definition",$J["definition"]);echo'<p>
<input type="submit" value="Save">
';if($da!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$da));}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["sequence"])){$fa=$_GET["sequence"];$J=$_POST;if($_POST&&!$n){$_=substr(ME,0,-1);$C=trim($J["name"]);if($_POST["drop"])query_redirect("DROP SEQUENCE ".idf_escape($fa),$_,'Sequence has been dropped.');elseif($fa=="")query_redirect("CREATE SEQUENCE ".idf_escape($C),$_,'Sequence has been created.');elseif($fa!=$C)query_redirect("ALTER SEQUENCE ".idf_escape($fa)." RENAME TO ".idf_escape($C),$_,'Sequence has been altered.');else
redirectm($_);}page_header($fa!=""?'Alter sequence'.": ".h($fa):'Create sequence',$n);if(!$J)$J["name"]=$fa;echo'
<form action="" method="post">
<p><input name="name" value="',h($J["name"]),'" autocapitalize="off">
<input type="submit" value="Save">
';if($fa!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$fa))."\n";echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["type"])){$ga=$_GET["type"];$J=$_POST;if($_POST&&!$n){$_=substr(ME,0,-1);if($_POST["drop"])query_redirect("DROP TYPE ".idf_escape($ga),$_,'Type has been dropped.');else
query_redirect("CREATE TYPE ".idf_escape(trim($J["name"]))." $J[as]",$_,'Type has been created.');}page_header($ga!=""?'Alter type'.": ".h($ga):'Create type',$n);if(!$J)$J["as"]="AS ";echo'
<form action="" method="post">
<p>
';if($ga!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$ga))."\n";else{echo"<input name='name' value='".h($J['name'])."' autocapitalize='off'>\n";textarea("as",$J["as"]);echo"<p><input type='submit' value='".'Save'."'>\n";}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["trigger"])){$a=$_GET["trigger"];$C=$_GET["name"];$xi=trigger_options();$J=(array)trigger($C)+array("Trigger"=>$a."_bi");if($_POST){if(!$n&&in_array($_POST["Timing"],$xi["Timing"])&&in_array($_POST["Event"],$xi["Event"])&&in_array($_POST["Type"],$xi["Type"])){$nf=" ON ".table($a);$fc="DROP TRIGGER ".idf_escape($C).($x=="pgsql"?$nf:"");$A=ME."table=".urlencode($a);if($_POST["drop"])query_redirect($fc,$A,'Trigger has been dropped.');else{if($C!="")queries($fc);queries_redirect($A,($C!=""?'Trigger has been altered.':'Trigger has been created.'),queries(create_trigger($nf,$_POST)));if($C!="")queries(create_trigger($nf,$J+array("Type"=>reset($xi["Type"]))));}}$J=$_POST;}page_header(($C!=""?'Alter trigger'.": ".h($C):'Create trigger'),$n,array("table"=>$a));echo'
<form action="" method="post" id="form">
<table cellspacing="0" class="layout">
<tr><th>Time<td>',html_select("Timing",$xi["Timing"],$J["Timing"],"triggerChange(/^".preg_quote($a,"/")."_[ba][iud]$/, '".js_escape($a)."', this.form);"),'<tr><th>Event<td>',html_select("Event",$xi["Event"],$J["Event"],"this.form['Timing'].onchange();"),(in_array("UPDATE OF",$xi["Event"])?" <input name='Of' value='".h($J["Of"])."' class='hidden'>":""),'<tr><th>Type<td>',html_select("Type",$xi["Type"],$J["Type"]),'</table>
<p>Name: <input name="Trigger" value="',h($J["Trigger"]),'" data-maxlength="64" autocapitalize="off">
',script("qs('#form')['Timing'].onchange();"),'<p>';textarea("Statement",$J["Statement"]);echo'<p>
<input type="submit" value="Save">
';if($C!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$C));}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["user"])){$ha=$_GET["user"];$ng=array(""=>array("All privileges"=>""));foreach(get_rows("SHOW PRIVILEGES")as$J){foreach(explode(",",($J["Privilege"]=="Grant option"?"":$J["Context"]))as$Ab)$ng[$Ab][$J["Privilege"]]=$J["Comment"];}$ng["Server Admin"]+=$ng["File access on server"];$ng["Databases"]["Create routine"]=$ng["Procedures"]["Create routine"];unset($ng["Procedures"]["Create routine"]);$ng["Columns"]=array();foreach(array("Select","Insert","Update","References")as$X)$ng["Columns"][$X]=$ng["Tables"][$X];unset($ng["Server Admin"]["Usage"]);foreach($ng["Tables"]as$y=>$X)unset($ng["Databases"][$y]);$Xe=array();if($_POST){foreach($_POST["objects"]as$y=>$X)$Xe[$X]=(array)$Xe[$X]+(array)$_POST["grants"][$y];}$md=array();$lf="";if(isset($_GET["host"])&&($H=$g->query("SHOW GRANTS FOR ".q($ha)."@".q($_GET["host"])))){while($J=$H->fetch_row()){if(preg_match('~GRANT (.*) ON (.*) TO ~',$J[0],$B)&&preg_match_all('~ *([^(,]*[^ ,(])( *\([^)]+\))?~',$B[1],$Ce,PREG_SET_ORDER)){foreach($Ce
as$X){if($X[1]!="USAGE")$md["$B[2]$X[2]"][$X[1]]=true;if(preg_match('~ WITH GRANT OPTION~',$J[0]))$md["$B[2]$X[2]"]["GRANT OPTION"]=true;}}if(preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~",$J[0],$B))$lf=$B[1];}}if($_POST&&!$n){$mf=(isset($_GET["host"])?q($ha)."@".q($_GET["host"]):"''");if($_POST["drop"])query_redirect("DROP USER $mf",ME."privileges=",'User has been dropped.');else{$Ze=q($_POST["user"])."@".q($_POST["host"]);$Vf=$_POST["pass"];if($Vf!=''&&!$_POST["hashed"]){$Vf=$g->result("SELECT PASSWORD(".q($Vf).")");$n=!$Vf;}$Fb=false;if(!$n){if($mf!=$Ze){$Fb=queries((min_version(5)?"CREATE USER":"GRANT USAGE ON *.* TO")." $Ze IDENTIFIED BY PASSWORD ".q($Vf));$n=!$Fb;}elseif($Vf!=$lf)queries("SET PASSWORD FOR $Ze = ".q($Vf));}if(!$n){$Pg=array();foreach($Xe
as$gf=>$ld){if(isset($_GET["grant"]))$ld=array_filter($ld);$ld=array_keys($ld);if(isset($_GET["grant"]))$Pg=array_diff(array_keys(array_filter($Xe[$gf],'strlen')),$ld);elseif($mf==$Ze){$jf=array_keys((array)$md[$gf]);$Pg=array_diff($jf,$ld);$ld=array_diff($ld,$jf);unset($md[$gf]);}if(preg_match('~^(.+)\s*(\(.*\))?$~U',$gf,$B)&&(!grant("REVOKE",$Pg,$B[2]," ON $B[1] FROM $Ze")||!grant("GRANT",$ld,$B[2]," ON $B[1] TO $Ze"))){$n=true;break;}}}if(!$n&&isset($_GET["host"])){if($mf!=$Ze)queries("DROP USER $mf");elseif(!isset($_GET["grant"])){foreach($md
as$gf=>$Pg){if(preg_match('~^(.+)(\(.*\))?$~U',$gf,$B))grant("REVOKE",array_keys($Pg),$B[2]," ON $B[1] FROM $Ze");}}}queries_redirect(ME."privileges=",(isset($_GET["host"])?'User has been altered.':'User has been created.'),!$n);if($Fb)$g->query("DROP USER $Ze");}}page_header((isset($_GET["host"])?'Username'.": ".h("$ha@$_GET[host]"):'Create user'),$n,array("privileges"=>array('','Privileges')));if($_POST){$J=$_POST;$md=$Xe;}else{$J=$_GET+array("host"=>$g->result("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)"));$J["pass"]=$lf;if($lf!="")$J["hashed"]=true;$md[(DB==""||$md?"":idf_escape(addcslashes(DB,"%_\\"))).".*"]=array();}echo'<form action="" method="post">
<table cellspacing="0" class="layout">
<tr><th>Server<td><input name="host" data-maxlength="60" value="',h($J["host"]),'" autocapitalize="off">
<tr><th>Username<td><input name="user" data-maxlength="80" value="',h($J["user"]),'" autocapitalize="off">
<tr><th>Password<td><input name="pass" id="pass" value="',h($J["pass"]),'" autocomplete="new-password">
';if(!$J["hashed"])echo
script("typePassword(qs('#pass'));");echo
checkbox("hashed",1,$J["hashed"],'Hashed',"typePassword(this.form['pass'], this.checked);"),'</table>

';echo"<table cellspacing='0'>\n","<thead><tr><th colspan='2'>".'Privileges'.doc_link(array('sql'=>"grant.html#priv_level"));$s=0;foreach($md
as$gf=>$ld){echo'<th>'.($gf!="*.*"?"<input name='objects[$s]' value='".h($gf)."' size='10' autocapitalize='off'>":"<input type='hidden' name='objects[$s]' value='*.*' size='10'>*.*");$s++;}echo"</thead>\n";foreach(array(""=>"","Server Admin"=>'Server',"Databases"=>'Database',"Tables"=>'Table',"Columns"=>'Column',"Procedures"=>'Routine',)as$Ab=>$Wb){foreach((array)$ng[$Ab]as$mg=>$tb){echo"<tr".odd()."><td".($Wb?">$Wb<td":" colspan='2'").' lang="en" title="'.h($tb).'">'.h($mg);$s=0;foreach($md
as$gf=>$ld){$C="'grants[$s][".h(strtoupper($mg))."]'";$Y=$ld[strtoupper($mg)];if($Ab=="Server Admin"&&$gf!=(isset($md["*.*"])?"*.*":".*"))echo"<td>";elseif(isset($_GET["grant"]))echo"<td><select name=$C><option><option value='1'".($Y?" selected":"").">".'Grant'."<option value='0'".($Y=="0"?" selected":"").">".'Revoke'."</select>";else{echo"<td align='center'><label class='block'>","<input type='checkbox' name=$C value='1'".($Y?" checked":"").($mg=="All privileges"?" id='grants-$s-all'>":">".($mg=="Grant option"?"":script("qsl('input').onclick = function () { if (this.checked) formUncheck('grants-$s-all'); };"))),"</label>";}$s++;}}}echo"</table>\n",'<p>
<input type="submit" value="Save">
';if(isset($_GET["host"])){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',"$ha@$_GET[host]"));}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["processlist"])){if(support("kill")&&$_POST&&!$n){$je=0;foreach((array)$_POST["kill"]as$X){if(kill_process($X))$je++;}queries_redirect(ME."processlist=",lang(array('%d process has been killed.','%d processes have been killed.'),$je),$je||!$_POST["kill"]);}page_header('Process list',$n);echo'
<form action="" method="post">
<div class="scrollable">
<table cellspacing="0" class="nowrap checkable">
',script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});");$s=-1;foreach(process_list()as$s=>$J){if(!$s){echo"<thead><tr lang='en'>".(support("kill")?"<th>":"");foreach($J
as$y=>$X)echo"<th>$y".doc_link(array('sql'=>"show-processlist.html#processlist_".strtolower($y),'pgsql'=>"monitoring-stats.html#PG-STAT-ACTIVITY-VIEW",'oracle'=>"../b14237/dynviews_2088.htm",));echo"</thead>\n";}echo"<tr".odd().">".(support("kill")?"<td>".checkbox("kill[]",$J[$x=="sql"?"Id":"pid"],0):"");foreach($J
as$y=>$X)echo"<td>".(($x=="sql"&&$y=="Info"&&preg_match("~Query|Killed~",$J["Command"])&&$X!="")||($x=="pgsql"&&$y=="current_query"&&$X!="<IDLE>")||($x=="oracle"&&$y=="sql_text"&&$X!="")?"<code class='jush-$x'>".shorten_utf8($X,100,"</code>").' <a href="'.h(ME.($J["db"]!=""?"db=".urlencode($J["db"])."&":"")."sql=".urlencode($X)).'">'.'Clone'.'</a>':h($X));echo"\n";}echo'</table>
</div>
<p>
';if(support("kill")){echo($s+1)."/".sprintf('%d in total',max_connections()),"<p><input type='submit' value='".'Kill'."'>\n";}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
',script("tableCheck();");}elseif(isset($_GET["select"])){$a=$_GET["select"];$R=table_status1($a);$w=indexes($a);$p=fields($a);$ed=column_foreign_keys($a);$if=$R["Oid"];parse_str($_COOKIE["adminer_import"],$ya);$Qg=array();$f=array();$bi=null;foreach($p
as$y=>$o){$C=$b->fieldName($o);if(isset($o["privileges"]["select"])&&$C!=""){$f[$y]=html_entity_decode(strip_tags($C),ENT_QUOTES);if(is_shortable($o))$bi=$b->selectLengthProcess();}$Qg+=$o["privileges"];}list($L,$nd)=$b->selectColumnsProcess($f,$w);$Yd=count($nd)<count($L);$Z=$b->selectSearchProcess($p,$w);$yf=$b->selectOrderProcess($p,$w);$z=$b->selectLimitProcess();if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$Di=>$J){$Ga=convert_field($p[key($J)]);$L=array($Ga?$Ga:idf_escape(key($J)));$Z[]=where_check($Di,$p);$I=$m->select($a,$L,$Z,$L);if($I)echo
reset($I->fetch_row());}exit;}$ig=$Fi=null;foreach($w
as$v){if($v["type"]=="PRIMARY"){$ig=array_flip($v["columns"]);$Fi=($L?$ig:array());foreach($Fi
as$y=>$X){if(in_array(idf_escape($y),$L))unset($Fi[$y]);}break;}}if($if&&!$ig){$ig=$Fi=array($if=>0);$w[]=array("type"=>"PRIMARY","columns"=>array($if));}if($_POST&&!$n){$gj=$Z;if(!$_POST["all"]&&is_array($_POST["check"])){$fb=array();foreach($_POST["check"]as$cb)$fb[]=where_check($cb,$p);$gj[]="((".implode(") OR (",$fb)."))";}$gj=($gj?"\nWHERE ".implode(" AND ",$gj):"");if($_POST["export"]){cookiem("adminer_import","output=".urlencode($_POST["output"])."&format=".urlencode($_POST["format"]));dump_headers($a);$b->dumpTable($a,"");$jd=($L?implode(", ",$L):"*").convert_fields($f,$p,$L)."\nFROM ".table($a);$pd=($nd&&$Yd?"\nGROUP BY ".implode(", ",$nd):"").($yf?"\nORDER BY ".implode(", ",$yf):"");if(!is_array($_POST["check"])||$ig)$G="SELECT $jd$gj$pd";else{$Bi=array();foreach($_POST["check"]as$X)$Bi[]="(SELECT".limit($jd,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$p).$pd,1).")";$G=implode(" UNION ALL ",$Bi);}$b->dumpData($a,"table",$G);exit;}if(!$b->selectEmailProcess($Z,$ed)){if($_POST["save"]||$_POST["delete"]){$H=true;$za=0;$O=array();if(!$_POST["delete"]){foreach($f
as$C=>$X){$X=process_input($p[$C]);if($X!==null&&($_POST["clone"]||$X!==false))$O[idf_escape($C)]=($X!==false?$X:idf_escape($C));}}if($_POST["delete"]||$O){if($_POST["clone"])$G="INTO ".table($a)." (".implode(", ",array_keys($O)).")\nSELECT ".implode(", ",$O)."\nFROM ".table($a);if($_POST["all"]||($ig&&is_array($_POST["check"]))||$Yd){$H=($_POST["delete"]?$m->delete($a,$gj):($_POST["clone"]?queries("INSERT $G$gj"):$m->update($a,$O,$gj)));$za=$g->affected_rows;}else{foreach((array)$_POST["check"]as$X){$cj="\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$p);$H=($_POST["delete"]?$m->delete($a,$cj,1):($_POST["clone"]?queries("INSERT".limit1($a,$G,$cj)):$m->update($a,$O,$cj,1)));if(!$H)break;$za+=$g->affected_rows;}}}$Ke=lang(array('%d item has been affected.','%d items have been affected.'),$za);if($_POST["clone"]&&$H&&$za==1){$oe=last_id();if($oe)$Ke=sprintf('Item%s has been inserted.'," $oe");}queries_redirect(remove_from_uri($_POST["all"]&&$_POST["delete"]?"page":""),$Ke,$H);if(!$_POST["delete"]){edit_form($a,$p,(array)$_POST["fields"],!$_POST["clone"]);page_footer();exit;}}elseif(!$_POST["import"]){if(!$_POST["val"])$n='Ctrl+click on a value to modify it.';else{$H=true;$za=0;foreach($_POST["val"]as$Di=>$J){$O=array();foreach($J
as$y=>$X){$y=bracket_escape($y,1);$O[idf_escape($y)]=(preg_match('~char|text~',$p[$y]["type"])||$X!=""?$b->processInput($p[$y],$X):"NULL");}$H=$m->update($a,$O," WHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($Di,$p),!$Yd&&!$ig," ");if(!$H)break;$za+=$g->affected_rows;}queries_redirect(remove_from_uri(),lang(array('%d item has been affected.','%d items have been affected.'),$za),$H);}}elseif(!is_string($Tc=get_file("csv_file",true)))$n=upload_error($Tc);elseif(!preg_match('~~u',$Tc))$n='File must be in UTF-8 encoding.';else{cookiem("adminer_import","output=".urlencode($ya["output"])."&format=".urlencode($_POST["separator"]));$H=true;$qb=array_keys($p);preg_match_all('~(?>"[^"]*"|[^"\r\n]+)+~',$Tc,$Ce);$za=count($Ce[0]);$m->begin();$M=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));$K=array();foreach($Ce[0]as$y=>$X){preg_match_all("~((?>\"[^\"]*\")+|[^$M]*)$M~",$X.$M,$De);if(!$y&&!array_diff($De[1],$qb)){$qb=$De[1];$za--;}else{$O=array();foreach($De[1]as$s=>$mb)$O[idf_escape($qb[$s])]=($mb==""&&$p[$qb[$s]]["null"]?"NULL":q(str_replace('""','"',preg_replace('~^"|"$~','',$mb))));$K[]=$O;}}$H=(!$K||$m->insertUpdate($a,$K,$ig));if($H)$H=$m->commit();queries_redirect(remove_from_uri("page"),lang(array('%d row has been imported.','%d rows have been imported.'),$za),$H);$m->rollback();}}}$Nh=$b->tableName($R);if(is_ajax()){page_headers();ob_start();}else
page_header('Select'.": $Nh",$n);$O=null;if(isset($Qg["insert"])||!support("table")){$O="";foreach((array)$_GET["where"]as$X){if($ed[$X["col"]]&&count($ed[$X["col"]])==1&&($X["op"]=="="||(!$X["op"]&&!preg_match('~[_%]~',$X["val"]))))$O.="&set".urlencode("[".bracket_escape($X["col"])."]")."=".urlencode($X["val"]);}}$b->selectLinks($R,$O);if(!$f&&support("table"))echo"<p class='error'>".'Unable to select the table'.($p?".":": ".error())."\n";else{echo"<form action='' id='form'>\n","<div style='display: none;'>";hidden_fields_get();echo(DB!=""?'<input type="hidden" name="db" value="'.h(DB).'">'.(isset($_GET["ns"])?'<input type="hidden" name="ns" value="'.h($_GET["ns"]).'">':""):"");echo'<input type="hidden" name="select" value="'.h($a).'">',"</div>\n";$b->selectColumnsPrint($L,$f);$b->selectSearchPrint($Z,$f,$w);$b->selectOrderPrint($yf,$f,$w);$b->selectLimitPrint($z);$b->selectLengthPrint($bi);$b->selectActionPrint($w);echo"</form>\n";$E=$_GET["page"];if($E=="last"){$hd=$g->result(count_rows($a,$Z,$Yd,$nd));$E=floor(max(0,$hd-1)/$z);}$ch=$L;$od=$nd;if(!$ch){$ch[]="*";$Bb=convert_fields($f,$p,$L);if($Bb)$ch[]=substr($Bb,2);}foreach($L
as$y=>$X){$o=$p[idf_unescape($X)];if($o&&($Ga=convert_field($o)))$ch[$y]="$Ga AS $X";}if(!$Yd&&$Fi){foreach($Fi
as$y=>$X){$ch[]=idf_escape($y);if($od)$od[]=idf_escape($y);}}$H=$m->select($a,$ch,$Z,$od,$yf,$z,$E,true);if(!$H)echo"<p class='error'>".error()."\n";else{if($x=="mssql"&&$E)$H->seek($z*$E);$sc=array();echo"<form action='' method='post' enctype='multipart/form-data'>\n";$K=array();while($J=$H->fetch_assoc()){if($E&&$x=="oracle")unset($J["RNUM"]);$K[]=$J;}if($_GET["page"]!="last"&&$z!=""&&$nd&&$Yd&&$x=="sql")$hd=$g->result(" SELECT FOUND_ROWS()");if(!$K)echo"<p class='message'>".'No rows.'."\n";else{$Pa=$b->backwardKeys($a,$Nh);echo"<div class='scrollable'>","<table id='table' cellspacing='0' class='nowrap checkable'>",script("mixin(qs('#table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true), onkeydown: editingKeydown});"),"<thead><tr>".(!$nd&&$L?"":"<td><input type='checkbox' id='all-page' class='jsonly'>".script("qs('#all-page').onclick = partial(formCheck, /check/);","")." <a href='".h($_GET["modify"]?remove_from_uri("modify"):$_SERVER["REQUEST_URI"]."&modify=1")."'>".'Modify'."</a>");$We=array();$kd=array();reset($L);$xg=1;foreach($K[0]as$y=>$X){if(!isset($Fi[$y])){$X=$_GET["columns"][key($L)];$o=$p[$L?($X?$X["col"]:current($L)):$y];$C=($o?$b->fieldName($o,$xg):($X["fun"]?"*":$y));if($C!=""){$xg++;$We[$y]=$C;$e=idf_escape($y);$Bd=remove_from_uri('(order|desc)[^=]*|page').'&order%5B0%5D='.urlencode($y);$Wb="&desc%5B0%5D=1";echo"<th>".script("mixin(qsl('th'), {onmouseover: partial(columnMouse), onmouseout: partial(columnMouse, ' hidden')});",""),'<a href="'.h($Bd.($yf[0]==$e||$yf[0]==$y||(!$yf&&$Yd&&$nd[0]==$e)?$Wb:'')).'">';echo
apply_sql_function($X["fun"],$C)."</a>";echo"<span class='column hidden'>","<a href='".h($Bd.$Wb)."' title='".'descending'."' class='text'> ↓</a>";if(!$X["fun"]){echo'<a href="#fieldset-search" title="'.'Search'.'" class="text jsonly"> =</a>',script("qsl('a').onclick = partial(selectSearch, '".js_escape($y)."');");}echo"</span>";}$kd[$y]=$X["fun"];next($L);}}$ue=array();if($_GET["modify"]){foreach($K
as$J){foreach($J
as$y=>$X)$ue[$y]=max($ue[$y],min(40,strlen(utf8_decode($X))));}}echo($Pa?"<th>".'Relations':"")."</thead>\n";if(is_ajax()){if($z%2==1&&$E%2==1)odd();ob_end_clean();}foreach($b->rowDescriptions($K,$ed)as$Ve=>$J){$Ci=unique_array($K[$Ve],$w);if(!$Ci){$Ci=array();foreach($K[$Ve]as$y=>$X){if(!preg_match('~^(COUNT\((\*|(DISTINCT )?`(?:[^`]|``)+`)\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\(`(?:[^`]|``)+`\))$~',$y))$Ci[$y]=$X;}}$Di="";foreach($Ci
as$y=>$X){if(($x=="sql"||$x=="pgsql")&&preg_match('~char|text|enum|set~',$p[$y]["type"])&&strlen($X)>64){$y=(strpos($y,'(')?$y:idf_escape($y));$y="MD5(".($x!='sql'||preg_match("~^utf8~",$p[$y]["collation"])?$y:"CONVERT($y USING ".charset($g).")").")";$X=md5($X);}$Di.="&".($X!==null?urlencode("where[".bracket_escape($y)."]")."=".urlencode($X):"null%5B%5D=".urlencode($y));}echo"<tr".odd().">".(!$nd&&$L?"":"<td>".checkbox("check[]",substr($Di,1),in_array(substr($Di,1),(array)$_POST["check"])).($Yd||information_schema(DB)?"":" <a href='".h(ME."edit=".urlencode($a).$Di)."' class='edit'>".'edit'."</a>"));foreach($J
as$y=>$X){if(isset($We[$y])){$o=$p[$y];$X=$m->value($X,$o);if($X!=""&&(!isset($sc[$y])||$sc[$y]!=""))$sc[$y]=(is_mail($X)?$We[$y]:"");$_="";if(preg_match('~blob|bytea|raw|file~',$o["type"])&&$X!="")$_=ME.'download='.urlencode($a).'&field='.urlencode($y).$Di;if(!$_&&$X!==null){foreach((array)$ed[$y]as$q){if(count($ed[$y])==1||end($q["source"])==$y){$_="";foreach($q["source"]as$s=>$th)$_.=where_link($s,$q["target"][$s],$K[$Ve][$th]);$_=($q["db"]!=""?preg_replace('~([?&]db=)[^&]+~','\1'.urlencode($q["db"]),ME):ME).'select='.urlencode($q["table"]).$_;if($q["ns"])$_=preg_replace('~([?&]ns=)[^&]+~','\1'.urlencode($q["ns"]),$_);if(count($q["source"])==1)break;}}}if($y=="COUNT(*)"){$_=ME."select=".urlencode($a);$s=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$Ci))$_.=where_link($s++,$W["col"],$W["val"],$W["op"]);}foreach($Ci
as$de=>$W)$_.=where_link($s++,$de,$W);}$X=select_value($X,$_,$o,$bi);$t=h("val[$Di][".bracket_escape($y)."]");$Y=$_POST["val"][$Di][bracket_escape($y)];$nc=!is_array($J[$y])&&is_utf8($X)&&$K[$Ve][$y]==$J[$y]&&!$kd[$y];$ai=preg_match('~text|lob~',$o["type"]);if(($_GET["modify"]&&$nc)||$Y!==null){$sd=h($Y!==null?$Y:$J[$y]);echo"<td>".($ai?"<textarea name='$t' cols='30' rows='".(substr_count($J[$y],"\n")+1)."'>$sd</textarea>":"<input name='$t' value='$sd' size='$ue[$y]'>");}else{$ye=strpos($X,"<i>...</i>");echo"<td id='$t' data-text='".($ye?2:($ai?1:0))."'".($nc?"":" data-warning='".h('Use edit link to modify this value.')."'").">$X</td>";}}}if($Pa)echo"<td>";$b->backwardKeysPrint($Pa,$K[$Ve]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n","</div>\n";}if(!is_ajax()){if($K||$E){$Bc=true;if($_GET["page"]!="last"){if($z==""||(count($K)<$z&&($K||!$E)))$hd=($E?$E*$z:0)+count($K);elseif($x!="sql"||!$Yd){$hd=($Yd?false:found_rows($R,$Z));if($hd<max(1e4,2*($E+1)*$z))$hd=reset(slow_query(count_rows($a,$Z,$Yd,$nd)));else$Bc=false;}}$Kf=($z!=""&&($hd===false||$hd>$z||$E));if($Kf){echo(($hd===false?count($K)+1:$hd-$E*$z)>$z?'<p><a href="'.h(remove_from_uri("page")."&page=".($E+1)).'" class="loadmore">'.'Load more data'.'</a>'.script("qsl('a').onclick = partial(selectLoadMore, ".(+$z).", '".'Loading'."...');",""):''),"\n";}}echo"<div class='footer'><div>\n";if($K||$E){if($Kf){$Fe=($hd===false?$E+(count($K)>=$z?2:1):floor(($hd-1)/$z));echo"<fieldset>";if($x!="simpledb"){echo"<legend><a href='".h(remove_from_uri("page"))."'>".'Page'."</a></legend>",script("qsl('a').onclick = function () { pageClick(this.href, +prompt('".'Page'."', '".($E+1)."')); return false; };"),pagination(0,$E).($E>5?" ...":"");for($s=max(1,$E-4);$s<min($Fe,$E+5);$s++)echo
pagination($s,$E);if($Fe>0){echo($E+5<$Fe?" ...":""),($Bc&&$hd!==false?pagination($Fe,$E):" <a href='".h(remove_from_uri("page")."&page=last")."' title='~$Fe'>".'last'."</a>");}}else{echo"<legend>".'Page'."</legend>",pagination(0,$E).($E>1?" ...":""),($E?pagination($E,$E):""),($Fe>$E?pagination($E+1,$E).($Fe>$E+1?" ...":""):"");}echo"</fieldset>\n";}echo"<fieldset>","<legend>".'Whole result'."</legend>";$bc=($Bc?"":"~ ").$hd;echo
checkbox("all",1,0,($hd!==false?($Bc?"":"~ ").lang(array('%d row','%d rows'),$hd):""),"var checked = formChecked(this, /check/); selectCount('selected', this.checked ? '$bc' : checked); selectCount('selected2', this.checked || !checked ? '$bc' : checked);")."\n","</fieldset>\n";if($b->selectCommandPrint()){echo'<fieldset',($_GET["modify"]?'':' class="jsonly"'),'><legend>Modify</legend><div>
<input type="submit" value="Save"',($_GET["modify"]?'':' title="'.'Ctrl+click on a value to modify it.'.'"'),'>
</div></fieldset>
<fieldset><legend>Selected <span id="selected"></span></legend><div>
<input type="submit" name="edit" value="Edit">
<input type="submit" name="clone" value="Clone">
<input type="submit" name="delete" value="Delete">',confirm(),'</div></fieldset>
';}$fd=$b->dumpFormat();foreach((array)$_GET["columns"]as$e){if($e["fun"]){unset($fd['sql']);break;}}if($fd){print_fieldset("export",'Export'." <span id='selected2'></span>");$If=$b->dumpOutput();echo($If?html_select("output",$If,$ya["output"])." ":""),html_select("format",$fd,$ya["format"])," <input type='submit' name='export' value='".'Export'."'>\n","</div></fieldset>\n";}$b->selectEmailPrint(array_filter($sc,'strlen'),$f);}echo"</div></div>\n";if($b->selectImportPrint()){echo"<div>","<a href='#import'>".'Import'."</a>",script("qsl('a').onclick = partial(toggle, 'import');",""),"<span id='import' class='hidden'>: ","<input type='file' name='csv_file'> ",html_select("separator",array("csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"),$ya["format"],1);echo" <input type='submit' name='import' value='".'Import'."'>","</span>","</div>";}echo"<input type='hidden' name='token' value='$mi'>\n","</form>\n",(!$nd&&$L?"":script("tableCheck();"));}}}if(is_ajax()){ob_end_clean();exit;}}elseif(isset($_GET["variables"])){$Ch=isset($_GET["status"]);page_header($Ch?'Status':'Variables');$Ti=($Ch?show_status():show_variables());if(!$Ti)echo"<p class='message'>".'No rows.'."\n";else{echo"<table cellspacing='0'>\n";foreach($Ti
as$y=>$X){echo"<tr>","<th><code class='jush-".$x.($Ch?"status":"set")."'>".h($y)."</code>","<td>".h($X);}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: text/javascript; charset=utf-8");if($_GET["script"]=="db"){$Kh=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach(table_status()as$C=>$R){json_row("Comment-$C",h($R["Comment"]));if(!is_view($R)){foreach(array("Engine","Collation")as$y)json_row("$y-$C",h($R[$y]));foreach($Kh+array("Auto_increment"=>0,"Rows"=>0)as$y=>$X){if($R[$y]!=""){$X=format_number($R[$y]);json_row("$y-$C",($y=="Rows"&&$X&&$R["Engine"]==($wh=="pgsql"?"table":"InnoDB")?"~ $X":$X));if(isset($Kh[$y]))$Kh[$y]+=($R["Engine"]!="InnoDB"||$y!="Data_free"?$R[$y]:0);}elseif(array_key_exists($y,$R))json_row("$y-$C");}}}foreach($Kh
as$y=>$X)json_row("sum-$y",format_number($X));json_row("");}elseif($_GET["script"]=="kill")$g->query("KILL ".number($_POST["kill"]));else{foreach(count_tables($b->databases())as$l=>$X){json_row("tables-$l",$X);json_row("size-$l",db_size($l));}json_row("");}exit;}else{$Th=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($Th&&!$n&&!$_POST["search"]){$H=true;$Ke="";if($x=="sql"&&$_POST["tables"]&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"]||$_POST["copy"]))queries("SET foreign_key_checks = 0");if($_POST["truncate"]){if($_POST["tables"])$H=truncate_tables($_POST["tables"]);$Ke='Tables have been truncated.';}elseif($_POST["move"]){$H=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$Ke='Tables have been moved.';}elseif($_POST["copy"]){$H=copy_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$Ke='Tables have been copied.';}elseif($_POST["drop"]){if($_POST["views"])$H=drop_views($_POST["views"]);if($H&&$_POST["tables"])$H=drop_tables($_POST["tables"]);$Ke='Tables have been dropped.';}elseif($x!="sql"){$H=($x=="sqlite"?queries("VACUUM"):apply_queries("VACUUM".($_POST["optimize"]?"":" ANALYZE"),$_POST["tables"]));$Ke='Tables have been optimized.';}elseif(!$_POST["tables"])$Ke='No tables.';elseif($H=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('idf_escape',$_POST["tables"])))){while($J=$H->fetch_assoc())$Ke.="<b>".h($J["Table"])."</b>: ".h($J["Msg_text"])."<br>";}queries_redirect(substr(ME,0,-1),$Ke,$H);}page_header(($_GET["ns"]==""?'Database'.": ".h(DB):'Schema'.": ".h($_GET["ns"])),$n,true);if($b->homepage()){if($_GET["ns"]!==""){echo"<h3 id='tables-views'>".'Tables and views'."</h3>\n";$Sh=tables_list();if(!$Sh)echo"<p class='message'>".'No tables.'."\n";else{echo"<form action='' method='post'>\n";if(support("table")){echo"<fieldset><legend>".'Search data in tables'." <span id='selected2'></span></legend><div>","<input type='search' name='query' value='".h($_POST["query"])."'>",script("qsl('input').onkeydown = partialArg(bodyKeydown, 'search');","")," <input type='submit' name='search' value='".'Search'."'>\n","</div></fieldset>\n";if($_POST["search"]&&$_POST["query"]!=""){$_GET["where"][0]["op"]="LIKE %%";search_tables();}}$cc=doc_link(array('sql'=>'show-table-status.html'));echo"<div class='scrollable'>\n","<table cellspacing='0' class='nowrap checkable'>\n",script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});"),'<thead><tr class="wrap">','<td><input id="check-all" type="checkbox" class="jsonly">'.script("qs('#check-all').onclick = partial(formCheck, /^(tables|views)\[/);",""),'<th>'.'Table','<td>'.'Engine'.doc_link(array('sql'=>'storage-engines.html')),'<td>'.'Collation'.doc_link(array('sql'=>'charset-charsets.html','mariadb'=>'supported-character-sets-and-collations/')),'<td>'.'Data Length'.$cc,'<td>'.'Index Length'.$cc,'<td>'.'Data Free'.$cc,'<td>'.'Auto Increment'.doc_link(array('sql'=>'example-auto-increment.html','mariadb'=>'auto_increment/')),'<td>'.'Rows'.$cc,(support("comment")?'<td>'.'Comment'.$cc:''),"</thead>\n";$S=0;foreach($Sh
as$C=>$T){$Wi=($T!==null&&!preg_match('~table~i',$T));$t=h("Table-".$C);echo'<tr'.odd().'><td>'.checkbox(($Wi?"views[]":"tables[]"),$C,in_array($C,$Th,true),"","","",$t),'<th>'.(support("table")||support("indexes")?"<a href='".h(ME)."table=".urlencode($C)."' title='".'Show structure'."' id='$t'>".h($C).'</a>':h($C));if($Wi){echo'<td colspan="6"><a href="'.h(ME)."view=".urlencode($C).'" title="'.'Alter view'.'">'.(preg_match('~materialized~i',$T)?'Materialized view':'View').'</a>','<td align="right"><a href="'.h(ME)."select=".urlencode($C).'" title="'.'Select data'.'">?</a>';}else{foreach(array("Engine"=>array(),"Collation"=>array(),"Data_length"=>array("create",'Alter table'),"Index_length"=>array("indexes",'Alter indexes'),"Data_free"=>array("edit",'New item'),"Auto_increment"=>array("auto_increment=1&create",'Alter table'),"Rows"=>array("select",'Select data'),)as$y=>$_){$t=" id='$y-".h($C)."'";echo($_?"<td align='right'>".(support("table")||$y=="Rows"||(support("indexes")&&$y!="Data_length")?"<a href='".h(ME."$_[0]=").urlencode($C)."'$t title='$_[1]'>?</a>":"<span$t>?</span>"):"<td id='$y-".h($C)."'>");}$S++;}echo(support("comment")?"<td id='Comment-".h($C)."'>":"");}echo"<tr><td><th>".sprintf('%d in total',count($Sh)),"<td>".h($x=="sql"?$g->result("SELECT @@storage_engine"):""),"<td>".h(db_collation(DB,collations()));foreach(array("Data_length","Index_length","Data_free")as$y)echo"<td align='right' id='sum-$y'>";echo"</table>\n","</div>\n";if(!information_schema(DB)){echo"<div class='footer'><div>\n";$Qi="<input type='submit' value='".'Vacuum'."'> ".on_help("'VACUUM'");$uf="<input type='submit' name='optimize' value='".'Optimize'."'> ".on_help($x=="sql"?"'OPTIMIZE TABLE'":"'VACUUM OPTIMIZE'");echo"<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>".($x=="sqlite"?$Qi:($x=="pgsql"?$Qi.$uf:($x=="sql"?"<input type='submit' value='".'Analyze'."'> ".on_help("'ANALYZE TABLE'").$uf."<input type='submit' name='check' value='".'Check'."'> ".on_help("'CHECK TABLE'")."<input type='submit' name='repair' value='".'Repair'."'> ".on_help("'REPAIR TABLE'"):"")))."<input type='submit' name='truncate' value='".'Truncate'."'> ".on_help($x=="sqlite"?"'DELETE'":"'TRUNCATE".($x=="pgsql"?"'":" TABLE'")).confirm()."<input type='submit' name='drop' value='".'Drop'."'>".on_help("'DROP TABLE'").confirm()."\n";$k=(support("scheme")?$b->schemas():$b->databases());if(count($k)!=1&&$x!="sqlite"){$l=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo"<p>".'Move to other database'.": ",($k?html_select("target",$k,$l):'<input name="target" value="'.h($l).'" autocapitalize="off">')," <input type='submit' name='move' value='".'Move'."'>",(support("copy")?" <input type='submit' name='copy' value='".'Copy'."'>":""),"\n";}echo"<input type='hidden' name='all' value=''>";echo
script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^(tables|views)\[/));".(support("table")?" selectCount('selected2', formChecked(this, /^tables\[/) || $S);":"")." }"),"<input type='hidden' name='token' value='$mi'>\n","</div></fieldset>\n","</div></div>\n";}echo"</form>\n",script("tableCheck();");}echo'<p class="links"><a href="'.h(ME).'create=">'.'Create table'."</a>\n",(support("view")?'<a href="'.h(ME).'view=">'.'Create view'."</a>\n":"");if(support("routine")){echo"<h3 id='routines'>".'Routines'."</h3>\n";$Ug=routines();if($Ug){echo"<table cellspacing='0'>\n",'<thead><tr><th>'.'Name'.'<td>'.'Type'.'<td>'.'Return type'."<td></thead>\n";odd('');foreach($Ug
as$J){$C=($J["SPECIFIC_NAME"]==$J["ROUTINE_NAME"]?"":"&name=".urlencode($J["ROUTINE_NAME"]));echo'<tr'.odd().'>','<th><a href="'.h(ME.($J["ROUTINE_TYPE"]!="PROCEDURE"?'callf=':'call=').urlencode($J["SPECIFIC_NAME"]).$C).'">'.h($J["ROUTINE_NAME"]).'</a>','<td>'.h($J["ROUTINE_TYPE"]),'<td>'.h($J["DTD_IDENTIFIER"]),'<td><a href="'.h(ME.($J["ROUTINE_TYPE"]!="PROCEDURE"?'function=':'procedure=').urlencode($J["SPECIFIC_NAME"]).$C).'">'.'Alter'."</a>";}echo"</table>\n";}echo'<p class="links">'.(support("procedure")?'<a href="'.h(ME).'procedure=">'.'Create procedure'.'</a>':'').'<a href="'.h(ME).'function=">'.'Create function'."</a>\n";}if(support("sequence")){echo"<h3 id='sequences'>".'Sequences'."</h3>\n";$ih=get_vals("SELECT sequence_name FROM information_schema.sequences WHERE sequence_schema = current_schema() ORDER BY sequence_name");if($ih){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Name'."</thead>\n";odd('');foreach($ih
as$X)echo"<tr".odd()."><th><a href='".h(ME)."sequence=".urlencode($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links'><a href='".h(ME)."sequence='>".'Create sequence'."</a>\n";}if(support("type")){echo"<h3 id='user-types'>".'User types'."</h3>\n";$Oi=types();if($Oi){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Name'."</thead>\n";odd('');foreach($Oi
as$X)echo"<tr".odd()."><th><a href='".h(ME)."type=".urlencode($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links'><a href='".h(ME)."type='>".'Create type'."</a>\n";}if(support("event")){echo"<h3 id='events'>".'Events'."</h3>\n";$K=get_rows("SHOW EVENTS");if($K){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Name'."<td>".'Schedule'."<td>".'Start'."<td>".'End'."<td></thead>\n";foreach($K
as$J){echo"<tr>","<th>".h($J["Name"]),"<td>".($J["Execute at"]?'At given time'."<td>".$J["Execute at"]:'Every'." ".$J["Interval value"]." ".$J["Interval field"]."<td>$J[Starts]"),"<td>$J[Ends]",'<td><a href="'.h(ME).'event='.urlencode($J["Name"]).'">'.'Alter'.'</a>';}echo"</table>\n";$_c=$g->result("SELECT @@event_scheduler");if($_c&&$_c!="ON")echo"<p class='error'><code class='jush-sqlset'>event_scheduler</code>: ".h($_c)."\n";}echo'<p class="links"><a href="'.h(ME).'event=">'.'Create event'."</a>\n";}if($Sh)echo
script("ajaxSetHtml('".js_escape(ME)."script=db');");}}}page_footer();