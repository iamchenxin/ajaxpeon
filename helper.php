<?php
/**
 * Helper Component for the xxx Plugin
 *
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_ajaxpeon extends DokuWiki_Plugin
{
    function make_searchbox(){
        $out="";

        $out=$out.'<div class="xxtest">';
        $out=$out.'<input id="xxpageid" type="text"></input> ';
        $out=$out.'<label id="xxdictck">';
        $out=$out.'<input id="xxckpage"  name="Page" type="checkbox">Page</input> ';
        $out=$out.'<input id="xxcktoc" name="Toc" type="checkbox">Toc</input> ';
        $out=$out.'<input id="xxckvoice" name="Voice" type="checkbox">Voice</input> ';
        $out=$out."</label>";
        $out=$out.'<input id="xxsearch" title="Search" type="button" value="Search"></input>';
        $out=$out.'<div class="xxresult" id="xxdirectrt"></div>';
        $out=$out.'</div>';
        echo $out;
        return $out;
    }
}
