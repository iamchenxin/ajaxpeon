<?php
/**
 * User: z9764
 * Date: 2015/3/8
 * Time: 2:29
 */
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');
require_once (DOKU_INC . 'inc/html.php');
require_once (DOKU_INC . 'inc/parserutils.php');
require_once(DOKU_INC.'inc/search.php');

class  action_plugin_ajaxpeon extends DokuWiki_Action_Plugin{

    var $helper;

    function action_plugin_ajaxpeon(){
        $this->helper = $this->loadHelper('ajaxpeon', false);
    }

    function register(&$controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE',  $this, '_ajax_call');
    }

    /**
     * handle ajax requests
     */
    function _ajax_call(&$event, $param)
    {
        if ($event->data !== 'ajaxpeon') {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        //e.g. access additional request variables
        global $INPUT; //available since release 2012-10-13 "Adora Belle"

        $pageid =$INPUT->str('pageid');

        $target = $INPUT->str("target");

        $reflect = $INPUT->str("reflect");

        $data = array();
        $out="";
        if($target=="page"){
            $out=$this->get_page($pageid);
        }
        if($target=="toc"){
            $out=$this->get_toc($pageid);
        }
        if($target=="rawpage"){
            if($INPUT->str('rev')=='ori'){
                $orev_list= $this->helper->get_bookorev();
                $out = rawWiki($pageid,$orev_list[$pageid]);
            }else {
                $out = rawWiki($pageid);
            }
        }
        if($target=="writeraw"){
            $ori_txt = rawWiki($pageid);
            $ori_len = strlen($ori_txt);
            $subtarget = $INPUT->str('sub');
            $rec_txt = $INPUT->str('txt');
            $store_txt=null;
            switch($subtarget){
                case "add":
                    $store_txt=$ori_txt.$rec_txt;
                    break;
                case "wordlist":
                    $store_txt = $this->helper->merge_wordlist($ori_txt,$rec_txt);
                    break;
                case "write":
                    $store_txt = $rec_txt;
                    break;
            }
            if($store_txt!=null){
                saveWikiText($pageid,$store_txt,"L".strlen($store_txt));
                $out="origin txt len:".$ori_len.",you add:".(strlen($store_txt)-$ori_len);
            }else{
                $out="do nothing,please ensure set the mdata['sub']";
            }
        }
        if($target=="catalog"){
            $ns=$INPUT->str('ns');
            if($ns==null){
                $ns="";
            }
            $out = $this->get_catalog($ns);
        }
        if($target=="page_wordlists"){
            $wdstr=$INPUT->str('pglist');
            $pglist=json_decode($wdstr,true);
            $out=$this->helper->get_page_wordlists($pglist);
        }
        if($target=="booklist"){
            $out=$this->helper->get_booklist();
        }
        if($target=="user"){
            $out=$_SERVER['REMOTE_USER'];
            if($out==null){
                $data["inf"]="not login";
            }

        }



        $data["content"]=$out;
        if($reflect!=null) {
            $data["reflect"] = $reflect;
        }
        //json library of DokuWiki
        require_once DOKU_INC . 'inc/JSON.php';
        $json = new JSON();
        //set content type
        header('Content-Type: application/json');
        if($_GET["callback"]){
            echo $_GET["callback"]."(".$json->encode($data).")";
        }else {
            echo $json->encode($data);
        }
    }






    function get_catalog($ns){
        $data=array();
        global $conf;
        $ns = utf8_encodeFN(str_replace(':', '/', $ns));
        search($data,$conf['datadir'], 'search_allpages', array(),$ns);

        $out=array();
        foreach($data as $ff){
            $out[]=$ff["id"];
        }
        return $out;
    }






    function get_toc22($pageid){
        global $ID;
        global $TOC;
        $ID=$pageid;
        $oldtoc = $TOC;
        $html   = p_wiki_xhtml($pageid, '', false);
        $outtoc=tpl_toc(true);
        $TOC    = $oldtoc;
        return $outtoc;
    }

    function get_toc($pageid){
        global $conf;
        $meta = p_get_metadata($pageid, false, METADATA_RENDER_USING_CACHE);
        if(isset($meta['internal']['toc'])) {
            $tocok = $meta['internal']['toc'];
        } else {
            $tocok = true;
        }
        $toc = isset($meta['description']['tableofcontents']) ? $meta['description']['tableofcontents'] : null;
        if(!$tocok || !is_array($toc) || !$conf['tocminheads'] || count($toc) < $conf['tocminheads']) {
            $toc = array();
        }

        trigger_event('TPL_TOC_RENDER', $toc, null, false);
        $html = html_TOC($toc);
        return $html;

    }

    function get_page($pageid){
        return tpl_include_page($pageid,false);
    }

    function myrecord(){
  //      saveWikiText("zh:fftest",$origin."\\\\ \n".$name,"fftest"); //this is save to zh/fftest.txt
 //          $origin = rawWiki("zh:fftest");    // read from         zh/fftest.txt
        //tpl_content

        //data
        $data = array("avg1" => "you are success");
        //json library of DokuWiki
        require_once DOKU_INC . 'inc/JSON.php';
        $json = new JSON();
        //set content type
        header('Content-Type: application/json');
        echo $json->encode($data);
    }

    function tpl_include_page($pageid, $print = true, $propagate = false) {
        if (!$pageid) return false;
        if ($propagate) $pageid = page_findnearest($pageid);

        global $TOC;
        $oldtoc = $TOC;
        $html   = p_wiki_xhtml($pageid, '', false);
        $TOC    = $oldtoc;

        if(!$print) return $html;
        echo $html;
        return $html;
    }

    function html_TOC($toc){
        if(!count($toc)) return '';
        global $lang;
        $out  = '<!-- TOC START -->'.DOKU_LF;
        $out .= '<div id="dw__toc">'.DOKU_LF;
        $out .= '<h3 class="toggle">';
        $out .= $lang['toc'];
        $out .= '</h3>'.DOKU_LF;
        $out .= '<div>'.DOKU_LF;
        $out .= html_buildlist($toc,'toc','html_list_toc','html_li_default',true);
        $out .= '</div>'.DOKU_LF.'</div>'.DOKU_LF;
        $out .= '<!-- TOC END -->'.DOKU_LF;
        return $out;
    }

}