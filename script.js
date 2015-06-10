/**
 * Created by z9764 on 2015/3/13.
 */

function xxajax_process(data){

    if (data.content.length<3){jQuery('#xxdirectrt').html("Missing word . . .");
    }else{
        jQuery('#xxdirectrt').html(data.content);
    }
    xxright_resize();
}

function xxajax_get(){

    var pageid= jQuery("#xxpageid").val();
    var mdata=new Object();
    mdata['pageid']="en:"+pageid;
    mdata['call']="ajaxpeon";
    var url = DOKU_BASE + 'lib/exe/ajax.php';
    if(jQuery("#xxckpage").prop("checked")==true){
        mdata['target']="page";
        jQuery.ajax({url:url,data:mdata,success:xxajax_process,dataType:"jsonp",crossDomain:true});
        jQuery('#xxdirectrt').html("Loading page. . . . . .");
    }else if(jQuery("#xxcktoc").prop("checked")==true){
        mdata['target']="toc";
        jQuery.ajax({url:url,data:mdata,success:xxajax_process,dataType:"jsonp",crossDomain:true});
        jQuery('#xxdirectrt').html("Loading toc. . . . . .");
    }
    if(jQuery("#xxckvoice").prop("checked")==true){
        voice_dict(pageid);
    }

//    jQuery.post("http://w.ct.com/lib/exe/ajax.php",mdata,ajax_process,"json");
//    jQuery.ajax(url:"http://w.ct.com/lib/exe/ajax.php",type:"get",dataType:"jsonp",data:mdata,success:ajax_process )

}


function xxright_resize(){
    var top_width = jQuery("#dokuwiki__header").width();
    var win_width =jQuery(window).width();

    if(win_width<1500){
       // jQuery(".desktop .xxtest").css("display","none");
        jQuery("#xxrightbar").css("display","none");
        return;
    }else{
        jQuery("#xxrightbar").css("display","block");
      //  jQuery(".desktop .xxtest").css("display","block");
    }

    var bar_width = (win_width-top_width)/2 - 35;

    jQuery(".desktop .xxtest").width(bar_width);
//    jQuery(".desktop .xxtest").maxWidth(bar_width);
}

function xxinit_ff(){

    jQuery("#xxpageid").keyup(
        function(event){
            if(event.keyCode == 13){
                jQuery("#xxsearch").click();
            }
    });
    jQuery("#xxsearch").click(xxajax_get);
    jQuery(window).resize(xxright_resize);
    xxright_resize();
}

// commit this to disable all this js init!!
// jQuery(xxinit_ff);