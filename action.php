<?php
/**
 * Created by PhpStorm.
 * User: z9764
 * Date: 2015/3/8
 * Time: 2:29
 */
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');
require_once (DOKU_INC . 'inc/html.php');
require_once (DOKU_INC . 'inc/parserutils.php');

class  action_plugin_ajaxpeon extends DokuWiki_Action_Plugin{

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

        $out="";
        if($target=="page"){
            $out=$this->get_page($pageid);
        }
        if($target=="toc"){
            $out=$this->get_toc($pageid);
        }
        if($target=="rawpage"){
            $out = rawWiki($pageid);
        }

        $data = array("content" => $out);
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