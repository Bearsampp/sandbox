/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */
function initMenu(relPath,searchEnabled,serverSide,searchPage,search) {
  function makeTree(data,relPath) {
    let result='';
    if ('children' in data) {
      result+='<ul>';
      for (let i in data.children) {
        let url;
        const link = data.children[i].url;
        if (link.substring(0,1)=='^') {
          url = link.substring(1);
        } else {
          url = relPath+link;
        }
        result+='<li><a href="'+url+'">'+
                                data.children[i].text+'</a>'+
                                makeTree(data.children[i],relPath)+'</li>';
      }
      result+='</ul>';
    }
    return result;
  }
  let searchBoxHtml;
  if (searchEnabled) {
    if (serverSide) {
      searchBoxHtml='<div id="MSearchBox" class="MSearchBoxInactive">'+
                 '<div class="left">'+
                  '<form id="FSearchBox" action="'+relPath+searchPage+
                    '" method="get"><span id="MSearchSelectExt">&#160;</span>'+
                  '<input type="text" id="MSearchField" name="query" value="" placeholder="'+search+
                    '" size="20" accesskey="S" onfocus="searchBox.OnSearchFieldFocus(true)"'+
                    ' onblur="searchBox.OnSearchFieldFocus(false)"/>'+
                  '</form>'+
                 '</div>'+
                 '<div class="right"></div>'+
                '</div>';
    } else {
      searchBoxHtml='<div id="MSearchBox" class="MSearchBoxInactive">'+
                 '<span class="left">'+
                  '<span id="MSearchSelect" onmouseover="return searchBox.OnSearchSelectShow()"'+
                     ' onmouseout="return searchBox.OnSearchSelectHide()">&#160;</span>'+
                  '<input type="text" id="MSearchField" value="" placeholder="'+search+
                    '" accesskey="S" onfocus="searchBox.OnSearchFieldFocus(true)" '+
                    'onblur="searchBox.OnSearchFieldFocus(false)" '+
                    'onkeyup="searchBox.OnSearchFieldChange(event)"/>'+
                 '</span>'+
                 '<span class="right"><a id="MSearchClose" '+
                  'href="javascript:searchBox.CloseResultsWindow()">'+
                  '<img id="MSearchCloseImg" border="0" src="'+relPath+
                  'search/close.svg" alt=""/></a>'+
                 '</span>'+
                '</div>';
    }
  }

  $('#main-nav').before('<div class="sm sm-dox"><input id="main-menu-state" type="checkbox"/>'+
                        '<label class="main-menu-btn" for="main-menu-state">'+
                        '<span class="main-menu-btn-icon"></span> '+
                        'Toggle main menu visibility</label>'+
                        '<span id="searchBoxPos1" style="position:absolute;right:8px;top:8px;height:36px;"></span>'+
                        '</div>');
  $('#main-nav').append(makeTree(menudata,relPath));
  $('#main-nav').children(':first').addClass('sm sm-dox').attr('id','main-menu');
  if (searchBoxHtml) {
    $('#main-menu').append('<li id="searchBoxPos2" style="float:right"></li>');
  }
  const $mainMenuState = $('#main-menu-state');
  let prevWidth = 0;
  if ($mainMenuState.length) {
    const initResizableIfExists = function() {
      if (typeof initResizable==='function') initResizable();
    }
    // animate mobile menu
    $mainMenuState.change(function() {
      const $menu = $('#main-menu');
      let options = { duration: 250, step: initResizableIfExists };
      if (this.checked) {
        options['complete'] = () => $menu.css('display', 'block');
        $menu.hide().slideDown(options);
      } else {
        options['complete'] = () => $menu.css('display', 'none');
        $menu.show().slideUp(options);
      }
    });
    // set default menu visibility
    const resetState = function() {
      const $menu = $('#main-menu');
      const newWidth = $(window).outerWidth();
      if (newWidth!=prevWidth) {
        if ($(window).outerWidth()<768) {
          $mainMenuState.prop('checked',false); $menu.hide();
          $('#searchBoxPos1').html(searchBoxHtml);
          $('#searchBoxPos2').hide();
        } else {
          $menu.show();
          $('#searchBoxPos1').empty();
          $('#searchBoxPos2').html(searchBoxHtml);
          $('#searchBoxPos2').show();
        }
        if (typeof searchBox!=='undefined') {
          searchBox.CloseResultsWindow();
        }
        prevWidth = newWidth;
      }
    }
    $(window).ready(function() { resetState(); initResizableIfExists(); });
    $(window).resize(resetState);
  }
  $('#main-menu').smartmenus();
}
/* @license-end */
