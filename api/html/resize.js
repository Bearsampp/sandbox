/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

function initResizable(treeview) {
  let sidenav,navtree,content,header,footer,barWidth=6;
  const RESIZE_COOKIE_NAME = ''+'width';

  function resizeWidth() {
    const sidenavWidth = $(sidenav).outerWidth();
    content.css({marginLeft:parseInt(sidenavWidth)+"px"});
    if (typeof page_layout!=='undefined' && page_layout==1) {
      footer.css({marginLeft:parseInt(sidenavWidth)+"px"});
    }
    Cookie.writeSetting(RESIZE_COOKIE_NAME,sidenavWidth-barWidth);
  }

  function restoreWidth(navWidth) {
    content.css({marginLeft:parseInt(navWidth)+barWidth+"px"});
    if (typeof page_layout!=='undefined' && page_layout==1) {
      footer.css({marginLeft:parseInt(navWidth)+barWidth+"px"});
    }
    sidenav.css({width:navWidth + "px"});
  }

  function resizeHeight(treeview) {
    const headerHeight = header.outerHeight();
    const windowHeight = $(window).height();
    let contentHeight;
    if (treeview)
    {
      const footerHeight = footer.outerHeight();
      let navtreeHeight,sideNavHeight;
      if (typeof page_layout==='undefined' || page_layout==0) { /* DISABLE_INDEX=NO */
        contentHeight = windowHeight - headerHeight - footerHeight;
        navtreeHeight = contentHeight;
        sideNavHeight = contentHeight;
      } else if (page_layout==1) { /* DISABLE_INDEX=YES */
        contentHeight = windowHeight - footerHeight;
        navtreeHeight = windowHeight - headerHeight;
        sideNavHeight = windowHeight;
      }
      navtree.css({height:navtreeHeight + "px"});
      sidenav.css({height:sideNavHeight + "px"});
    }
    else
    {
      contentHeight = windowHeight - headerHeight;
    }
    content.css({height:contentHeight + "px"});
    if (location.hash.slice(1)) {
      (document.getElementById(location.hash.slice(1))||document.body).scrollIntoView();
    }
  }

  function collapseExpand() {
    let newWidth;
    if (sidenav.width()>0) {
      newWidth=0;
    } else {
      const width = Cookie.readSetting(RESIZE_COOKIE_NAME,375);
      newWidth = (width>375 && width<$(window).width()) ? width : 375;
    }
    restoreWidth(newWidth);
    const sidenavWidth = $(sidenav).outerWidth();
    Cookie.writeSetting(RESIZE_COOKIE_NAME,sidenavWidth-barWidth);
  }

  header  = $("#top");
  content = $("#doc-content");
  footer  = $("#nav-path");
  sidenav = $("#side-nav");
  if (!treeview) {
//    title   = $("#titlearea");
//    titleH  = $(title).height();
//    let animating = false;
//    content.on("scroll", function() {
//      slideOpts = { duration: 200,
//                    step: function() {
//                        contentHeight = $(window).height() - header.outerHeight();
//                        content.css({ height : contentHeight + "px" });
//                      },
//                    done: function() { animating=false; }
//                  };
//      if (content.scrollTop()>titleH && title.css('display')!='none' && !animating) {
//        title.slideUp(slideOpts);
//        animating=true;
//      } else if (content.scrollTop()<=titleH && title.css('display')=='none' && !animating) {
//        title.slideDown(slideOpts);
//        animating=true;
//      }
//    });
  } else {
    navtree = $("#nav-tree");
    $(".side-nav-resizable").resizable({resize: function(e, ui) { resizeWidth(); } });
    $(sidenav).resizable({ minWidth: 0 });
  }
  $(window).resize(function() { resizeHeight(treeview); });
  if (treeview)
  {
    const device = navigator.userAgent.toLowerCase();
    const touch_device = device.match(/(iphone|ipod|ipad|android)/);
    if (touch_device) { /* wider split bar for touch only devices */
      $(sidenav).css({ paddingRight:'20px' });
      $('.ui-resizable-e').css({ width:'20px' });
      $('#nav-sync').css({ right:'34px' });
      barWidth=20;
    }
    const width = Cookie.readSetting(RESIZE_COOKIE_NAME,375);
    if (width) { restoreWidth(width); } else { resizeWidth(); }
  }
  resizeHeight(treeview);
  const url = location.href;
  const i=url.indexOf("#");
  if (i>=0) window.location.hash=url.substr(i);
  const _preventDefault = function(evt) { evt.preventDefault(); };
  if (treeview)
  {
    $("#splitbar").bind("dragstart", _preventDefault).bind("selectstart", _preventDefault);
    $(".ui-resizable-handle").dblclick(collapseExpand);
  }
  $(window).on('load',resizeHeight);
}
/* @license-end */
