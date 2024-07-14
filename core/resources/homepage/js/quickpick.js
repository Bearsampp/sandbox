/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

document.addEventListener("DOMContentLoaded", function() {
    var selects = document.querySelectorAll('select');
    selects.forEach(function(select) {
        select.addEventListener('change', function() {
            var selectedOption = select.options[select.selectedIndex];
            var target = selectedOption.getAttribute('data-target');
            var id = select.id;
            var divs = document.querySelectorAll("div[id^='" + id + "']");
            divs.forEach(function(div) {
                div.style.display = 'none';
            });
            var targetDiv = document.getElementById(id + "-" + target);
            if (targetDiv) {
                targetDiv.style.display = 'block';
            }

            // New code to handle module installation
            var module = select.getAttribute('data-module');
            var version = selectedOption.value;
            if (module && version) {
                installModule(module, version);
            }
        });
    });

    function installModule(module, version) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "/../core/resources/ajax/ajax.quickpick.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                console.log(xhr.responseText);
                // Handle the response if needed
            }
        };
        xhr.send("module=" + encodeURIComponent(module) + "&version=" + encodeURIComponent(version));
    }
});
