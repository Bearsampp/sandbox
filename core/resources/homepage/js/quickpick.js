/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('select').forEach(function(selectElement) {
        selectElement.addEventListener('change', function() {
            var target = this.options[this.selectedIndex].getAttribute("data-target");
            var id = this.id;

            console.log("Selected target:", target);
            console.log("Select element ID:", id);

            // Hide all divs with IDs starting with the select element's ID
            document.querySelectorAll("div[id^='" + id + "']").forEach(function(divElement) {
                divElement.style.display = 'none';
            });

            // Show the div corresponding to the selected option's data-target attribute
            var targetDiv = document.getElementById(id + "-" + target);
            if (targetDiv) {
                console.log("Showing div:", targetDiv.id);
                targetDiv.style.display = 'block';
            } else {
                console.log("No div found for target:", id + "-" + target);
            }
        });
    });
});
