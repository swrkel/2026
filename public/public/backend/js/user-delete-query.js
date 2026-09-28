$(document).on("click", ".open-model", function () {
    "use strict";
    $('#deleteModal').modal('show');
    var cardId = $(this).data('id');
    console.log(cardId);
    var link = "./card-status/"+ cardId;
    var preview = document.getElementById("plan_id"); //getElementById instead of querySelectorAll
    preview.setAttribute("href", link);
    // As pointed out in comments,
    // it is unnecessary to have to manually call the modal.
});

$(document).on("click", ".open-plan-model", function () {
    "use strict";
    $('#planModal').modal('show');
    var planId = $(this).data('id');
    var link = "./checkout/"+ planId;
    var preview = document.getElementById("plan_id"); //getElementById instead of querySelectorAll
    preview.setAttribute("href", link);
});

$(document).on("click", ".down-plan-model", function () {
    "use strict";
    $('#downPlanModel').modal('show');
});

$(document).on("click", ".open-qr", function() {
    "use strict";
    $('#openQR').modal('show');
    var cardurl = $(this).data('id');
    var url = SITE_DATA.ROOT_URL + "/" + cardurl;
    var link = "https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=" + url + "&choe=UTF-8";
    var preview = document.getElementById("cardURL"); //getElementById instead of querySelectorAll
    preview.setAttribute("src", link);
    
    var print_url = $(this).data('print-url');
    url = SITE_DATA.ROOT_URL + "/" + print_url;
    var print_qr = document.getElementById("print_qr_code").setAttribute("href", url);
    // As pointed out in comments,
    // it is unnecessary to have to manually call the modal.
});