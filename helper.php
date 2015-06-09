<?php
/**
 * Helper Component for the xxx Plugin
 *
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
require_once (DOKU_INC . 'inc/parserutils.php');
class helper_plugin_ajaxpeon extends DokuWiki_Plugin
{
    function make_searchbox(){
        $out="";

        $out=$out.'<div class="xxtest">';
        $out=$out.'<input id="xxpageid" type="text"/>';

        $out=$out.'<input id="xxckpage"  name="Page" type="checkbox"/><label for="xxckpage">Page</label> ';
        $out=$out.'<input id="xxcktoc" name="Toc" type="checkbox"/><label for="xxcktoc">Toc</label>';
        $out=$out.'<input id="xxckvoice" name="Voice" type="checkbox"/><label for="xxckvoice">Voice</label>';

        $out=$out.'<input id="xxsearch" title="Search" type="button" value="Search"/>';
        $out=$out.'<div class="xxresult" id="xxdirectrt"></div>';
        $out=$out.'</div>';
        echo $out;
        return $out;
    }

    function make_learn_cata(){
        global $conf;
        $metadir = $conf["metadir"];
        $learndir = $metadir."/learn/";
        $filelist = scandir($learndir);
        $learnlist = array();
        foreach($filelist as $fname){
            if(strpos($fname,".changes")){
                $tmphf = fopen($learndir.$fname,"r");
                $tmpstr= fgets($tmphf);
                $namelen =strlen($fname);
                $rawnlen = $namelen-8;
                if(strlen($tmpstr)>10){
                    $learnlist[]=substr($tmpstr,0,10)."\t".substr($fname,0,$rawnlen);
                }
            }
        }
        $learnlstr = join("\n",$learnlist);
        $outfile = fopen($metadir."/learn.list","w");
        fwrite($outfile,$learnlstr);
        fclose($outfile);
    }

    function get_learn_list(){

    }

    function get_learn_words($learn_list){

    }


    function get_file_list($dir){
        $file_array=array();
        $file_array["files"]=array();
        $filelist = scandir($dir);

        $predir= getcwd();
        chdir($dir);
        foreach($filelist as $fname){
            if(is_dir($fname)){
                if($fname!="." and $fname!=".."){
                    $file_array["dir"][$fname]=$this->get_file_list($fname);
                }
            }else{
                if(is_file($fname)) {
                    $file_array["files"][] = substr($fname,0,strpos($fname,"."));
                }
            }
        }
        chdir($predir);
        return $file_array;
    }


    function get_changes_list($file_list,$dirnm,$changes_dir,&$out_filedata,&$out_fileorev){
        $changes_ar=array();
        $changes_ar["files"]=array();

        if(isset($file_list["dir"])){
            foreach($file_list["dir"] as $dirname=>$dirvalue){
                $changes_ar["ns"][$dirnm.$dirname.":"]=$this->get_changes_list($dirvalue,$dirnm.$dirname.":",$changes_dir,$out_filedata,$out_fileorev);
            }
        }
        if(isset($file_list["files"])){
            foreach($file_list["files"] as $file){
                $nsfile = $dirnm.$file;
                $dirfile = utf8_encodeFN(str_replace(':', '/', $nsfile));
                $hfile=fopen($changes_dir."/".$dirfile.'.changes',"r");
                $tmpstr= fgets($hfile);
                $orev =substr($tmpstr,0,10);
                fclose($hfile);

                $hwordf = fopen($dirfile.'.txt',"r");
                $txtstr = fread($hwordf,filesize($dirfile.'.txt'));
                $rt = preg_match("@<WORDLIST\b(.*?)>(.*?)</WORDLIST>@",$txtstr,$match);
                $wordlist="";
                fclose($hwordf);
                if($rt===1){
                    $wordlist=$match[2];
                }

                $nsdata = array("orev"=>$orev,"wordlist"=>$wordlist);
                $out_filedata[$dirnm.$file]=$nsdata;
                $out_fileorev[$dirnm.$file]=$orev;
                $changes_ar["files"][]=$dirnm.$file;

            }
        }
        return $changes_ar;
    }

    function build_learn_list($update_span){
        global $conf;
        $metadir = $conf["metadir"];
        $pagedir = $conf['datadir'];
        $pretime=0;
        if(is_readable($metadir."/learn.log"))
        {
            $htmp=fopen($metadir."/learn.log","r");
            $pretime = intval(fgets($htmp));
            fclose($htmp);
        }
        if($pretime){
            if((time()-$pretime)<$update_span){
                return;
            }
        }

        $predir= getcwd();
        chdir($pagedir);
        $learndir_ls = $this->get_file_list("learn");
        $flat_data=array();
        $orev_data=array();
        $learn_ns_ls = $this->get_changes_list($learndir_ls,"learn:",$metadir,$flat_data,$orev_data);

        chdir($predir);

        $flearnlist = fopen($metadir."/learn.list","w");
        fwrite($flearnlist,json_encode($learn_ns_ls));
        fclose($flearnlist);
        $flearndata= fopen($metadir."/learn.flatdata","w");
        fwrite($flearndata,json_encode($flat_data));
        fclose($flearndata);
        $forev= fopen($metadir."/learn.orev","w");
        fwrite($forev,json_encode($orev_data));
        fclose($forev);
        $this->learnlog($metadir."/learn.log");
//    $flearnlog= fopen($metadir."/learn.data","w+");
    }


    function learnlog($logname){
        $outlog=null;
        $pretime=0;
        if(is_readable($logname)==false){
            $outlog=fopen($logname,"w");
        }else {
            $outlog=fopen($logname,"r+");
            $pretime = fgets($outlog);

            $orgtime = fgets($outlog);

            if ($orgtime) {
                if ((time() - intval($orgtime)) > 60 * 60 * 24) {
                    fclose($outlog);
                    $outlog = fopen($logname, "w");
                }
            }
        }
        fseek($outlog,0,SEEK_SET);
        fwrite($outlog,time()."\n");
        fseek($outlog,0,SEEK_END);
        fwrite($outlog,time()."\n");
        fclose($outlog);
        return intval($pretime);
    }


    function get_page_wordlists($page_list){
        $this->build_learn_list(60*10);
        global $conf;
        $metadir = $conf["metadir"];
        $pagedir = $conf['datadir'];
        $fstr = file_get_contents($metadir."/learn.flatdata");
        $flat_wlist = json_decode($fstr,true);
        $data=array();
        foreach($page_list as $page){
            $data[$page]=$flat_wlist[$page]["wordlist"];
        }
        return $data;
    }

    function get_learnlist(){
        $this->build_learn_list(60*10);
        global $conf;
        $metadir = $conf["metadir"];
        $pagedir = $conf['datadir'];
        $fstr = file_get_contents($metadir."/learn.list");
        return $fstr;
    }

    function get_learnorev(){
        global $conf;
        $metadir = $conf["metadir"];
        $filen= $metadir."/learn.orev";
        $fstr = file_get_contents($filen);
        $orev_list = json_decode($fstr,true);
        return $orev_list;
    }

    function merge_wordlist($w_txt1,$w_txt2){
        $list1 = explode(",",$w_txt1);
        $list2 = explode(",",$w_txt2);
        $list3 = array_merge($list1,$list2);
        sort($list3);
        $list_rt = array_unique($list3);
        return implode(",",$list_rt);
    }
    /*
    function get_wordlistss($page_list){
        $data=array();
        foreach($page_list as $page){
            $data[$page]=$this->get_wordlist($page);
        }
        return $data;
    }

    function get_wordlist($pageid){
        $filestr=rawWiki($pageid);
        $rt = preg_match("@<WORDLIST\b(.*?)>(.*?)</WORDLIST>@",$filestr,$match);
        if($rt!==1){
            return null;
        }
        return $match[2];
    }
    */


}
